<?php
# Membership functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2024 Ineiev
#
# This file is part of Savane.
#
# Code written before 2008-03-30 (commit 8b757b2565ff) is distributed
# under the terms of the GNU General Public license version 3 or (at your
# option) any later version; further contributions are covered by
# the GNU Affero General Public license version 3 or (at your option)
# any later version.  The license notices for the AGPL and the GPL follow.
#
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
#
# Savane is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# Savane is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <https://www.gnu.org/licenses/>.

require_once (dirname(__FILE__) . '/database.php');
define ('MEMBER_FLAGS_MEMBER', '');
define ('MEMBER_FLAGS_ADMIN', 'A');
define ('MEMBER_FLAGS_PENDING', 'P');
define ('MEMBER_FLAGS_SQUAD', 'SQD');

function member_history_label_on_add ($status)
{
  if ($status === MEMBER_FLAGS_PENDING)
    return 'User Requested Membership';
  if ($status === MEMBER_FLAGS_SQUAD)
    return 'Created Squad';
  return 'Added User';
}

# Add or update a user to a group; $status is the 'admin_flags'.
function member_add ($user_id, $group_id, $status = '')
{
  if (member_check ($user_id, $group_id))
    {
      fb (_("This user is already member of the group."), 1);
      return 0;
    }
  $result = db_autoexecute ('user_group',
    ['user_id' => $user_id, 'group_id' => $group_id, 'admin_flags' => $status],
    DB_AUTOQUERY_INSERT
  );
  if (!$result)
    return $result;
  if ($status === MEMBER_FLAGS_MEMBER)
    member_update_group_file ($group_id);
  $comment = member_history_label_on_add ($status);
  group_add_history ($comment, user_getname ($user_id), $group_id);
  return $result;
}

function member_admin_flags_query ($group_id, $cond, $arg)
{
  $arg = utils_make_arg_array ($arg);
  return db_execute ("
    SELECT u.user_id, user_name, realname
    FROM user u JOIN user_group g ON u.user_id = g.user_id
    WHERE group_id = ? AND admin_flags $cond ORDER BY user_name",
    array_merge ([$group_id], $arg)
  );
}

function member_fetch_group_data ($group_id)
{
  $admin_flags = [MEMBER_FLAGS_MEMBER, MEMBER_FLAGS_ADMIN];
  $result = db_execute ("
    SELECT
      unix_group_name AS name,
      group_concat(user_name ORDER BY user_name) AS users
    FROM groups g, user u, user_group ug
    WHERE
      g.group_id = ? AND g.group_id = ug.group_id AND gidNumber IS NOT NULL
      AND uidNumber IS NOT NULL AND u.user_id = ug.user_id
      AND ug.admin_flags " . utils_in_placeholders ($admin_flags) . "
      GROUP BY unix_group_name", array_merge ([$group_id], $admin_flags)
  );
  if (!db_numrows ($result))
    return null;
  return db_fetch_array ($result);
}

function member_group_file_unavailable ()
{
  if (!array_key_exists ('sys_group_file', $GLOBALS))
    return true;
  if (!is_file ($GLOBALS['sys_group_file']))
    return true;
  return !is_readable ($GLOBALS['sys_group_file']);
}

function member_substitute_group ($arg)
{
  list ($tmp_file, $data) = $arg;
  $out = fopen ($tmp_file, 'w');
  $in = fopen ($GLOBALS['sys_group_file'], 'r');
  while (false !== ($line = fgets ($in)))
    {
      if (preg_match ("/^{$data['name']}:/", $line))
        $line = preg_replace ("/[^:]*\n$/", $data['users'] . "\n", $line);
      fwrite ($out, $line);
    }
  fclose ($in);
  fclose ($out);
  chmod ($tmp_file, 0644);
  rename ($tmp_file, $GLOBALS['sys_group_file']);
}

function member_update_group_file ($group_id)
{
  global $sys_group_file;
  if (member_group_file_unavailable ())
    return;
  $data = member_fetch_group_data ($group_id);
  if ($data === null)
    return;
  $tmp = utils_mktemp ('group');
  if ($tmp === null)
    return;
  utils_run_lock ($sys_group_file, 'member_substitute_group', [$tmp, $data]);
}

# Approve a pending user for a group.
function member_approve ($user_id, $group_id)
{
  $result = db_execute ("
    UPDATE user_group SET admin_flags = '' WHERE user_id = ? AND group_id = ?",
    [$user_id, $group_id]
  );
  if ($result)
    {
      group_add_history ('Approved User', user_getname ($user_id), $group_id);
      member_update_group_file ($group_id);
    }
  return $result;
}

function member_get_admin_flags ($user_id, $group_id)
{
  $result = db_execute ("
    SELECT admin_flags FROM user_group WHERE user_id = ? AND group_id = ?",
    [$user_id, $group_id]
  );
  if (!db_numrows ($result))
    return null;

  return db_result ($result, 0, 'admin_flags');
}

function member_purge_from_user_squad ($user_id, $group_id, $admin_flags)
{
  if ($admin_flags != MEMBER_FLAGS_SQUAD)
    # If it is not a squad, make sure the user is no longer associated
    # to squads of the group.
    return db_execute (
      "DELETE FROM user_squad WHERE user_id = ? AND group_id = ?",
      [$user_id, $group_id]
    );
  db_execute ("DELETE FROM user WHERE user_id = ?", [$user_id]);
  # A squad may only belong to a single group.
  return db_execute (
    "DELETE FROM user_squad WHERE squad_id = ? AND group_id = ?",
    [$user_id, $group_id]
  );
}

function member_remove ($user_id, $group_id)
{
  $admin_flags = member_get_admin_flags ($user_id, $group_id);
  if ($admin_flags === null)
    return false;

  $result = db_execute (
    "DELETE FROM user_group WHERE user_id = ? AND group_id = ?",
    [$user_id, $group_id]
  );
  if (!$result)
    return $result;
  if ($admin_flags == MEMBER_FLAGS_SQUAD)
    group_add_history ('Deleted Squad', user_getname ($user_id), $group_id);
  else
    {
      group_add_history ('Removed User', user_getname ($user_id), $group_id);
      member_update_group_file ($group_id);
    }
  return member_purge_from_user_squad ($user_id, $group_id, $admin_flags);
}

# Add a given member to a squad.
function member_add_to_squad ($user_id, $squad_id, $group_id)
{
  # First check if user is not already in.
  $result = db_execute ("
    SELECT user_id FROM user_squad
    WHERE user_id = ? AND squad_id = ? AND group_id = ?",
    [$user_id, $squad_id, $group_id]
  );
  if (db_numrows ($result))
    return false;
  # If we get here, we need to do an insert.
  $result = db_autoexecute ('user_squad',
    ['user_id' => $user_id, 'squad_id' => $squad_id, 'group_id' => $group_id],
    DB_AUTOQUERY_INSERT
  );
  if ($result)
    group_add_history (
      'Added User to Squad ' . user_getname ($squad_id),
      user_getname ($user_id), $group_id
    );
  return $result;
}

# Remove a given member from a squad.
function member_remove_from_squad ($user_id, $squad_id, $group_id)
{
  # First check if user is in.
  $result = db_execute ("
    SELECT user_id FROM user_squad
    WHERE user_id = ? AND squad_id = ? AND group_id = ?",
    [$user_id, $squad_id, $group_id]
  );
  if (!db_numrows ($result))
    return false;

  $result = db_execute ("
    DELETE FROM user_squad
    WHERE user_id = ? AND squad_id = ? AND group_id = ?",
    [$user_id, $squad_id, $group_id]
  );
  if ($result)
    group_add_history (
      'Removed User From Squad ' . user_getname ($squad_id),
      user_getname ($user_id), $group_id
    );
  return $result;
}

# Get all permissions for given users.
function member_array_getpermissions ($group_id, $flags, $user_ids)
{
  $ret = [];
  if (!$flags)
    {
      foreach ($user_ids as $u)
        $ret[$u] = 0;
      return $ret;
    }
  if (!preg_match ('/^[a-z]+$/', $flags))
    util_die ('group_getpermissions: unvalid argument flags');
  if (!count ($user_ids))
    return [];
  $flags .= '_flags';
  $sql =
    "SELECT user_id, $flags AS flags FROM user_group WHERE group_id = ?";
  $arg = utils_placeholders ($user_ids);
  $sql .= "AND user_id IN ($arg)";
  $res = db_execute ($sql, array_merge ([$group_id], $user_ids));
  while ($u = db_fetch_array ($res))
    $ret[$u['user_id']] = $u['flags'];
  return $ret;
}

function member_getpermissions ($group_id, $flags, $user_id = 0)
{
  if (!$user_id)
    $user_id = user_getid ();
  $ret = member_array_getpermissions ($group_id, $flags, [$user_id]);
  return $ret[$user_id];
}

function member_check_propagate_uids ($user_id)
{
  $ret = $uids = [];
  foreach ($user_id as $u)
    {
      if ($u)
        {
          $uids[] = $u;
          continue;
        }
      if (!user_isloggedin ())
        continue;
      if (user_is_super_user ())
        {
          $ret[] = $u;
          continue;
        }
      $u = user_getid ();
      $uids[] = $u;
    }
  return [$uids, $ret];
}

function member_check_split_flags ($flag)
{
  $flag = strtoupper ($flag);
  # When looking for permissions, first we look at the user permission,
  # if NULL at the group def permission, if NULL at the group type def
  # permission.
  $flag_tracker = substr ($flag, 0, 1);
  $flag_level = substr ($flag, 1, 2);
  if (!$flag_level)
    {
      # If flag_level does not exist, the level is the tracker flag
      # (like P or A for admin_flags).
      $flag_level = $flag_tracker;
      $flag_tracker = "admin";
    }

  # Get the tracker.
  $ft = member_create_tracker_flag ($flag_tracker, true);
  if ($ft !== null)
    $flag_tracker = $ft;
  return [$flag_tracker, $flag_level];
}

function member_check_array_perms ($group_id, $flag, $uids, $strict)
{
  list ($flag_tracker, $flag_level) = member_check_split_flags ($flag);
  $values = member_array_getpermissions ($group_id, $flag_tracker, $uids);
  $ret = [];
  foreach ($uids as $u)
    {
      $value = null;
      if (isset ($values[$u]))
        $value = $values[$u];
      if (!$value)
        {
          if (!isset ($group_perms))
            $group_perms = group_getpermissions ($group_id, $flag_tracker);
          $value = $group_perms;
        }
      if (!$value)
        {
          if (!isset ($type_perms))
            $type_perms = group_gettypepermissions ($group_id, $flag_tracker);
          $value = $type_perms;
        }
      if (!$value)
        $value = "ERROR";

      # Compare the value and what is asked.
      if ($value == $flag_level)
        {
          # If the value is equal to the flag, $u is obviously included.
          $ret[] = $u;
          continue;
        }
      if ($strict)
        continue;
      if (2 == $value && (1 == $flag_level || 3 == $flag_level))
        {
          # The value is equal to 2 (manager and tech) if tech (1)
          # or manager (3) is asked.
          $ret[] = $u;
          continue;
        }
      if (2 == $flag_level  && (1 == $value || 3 == $value))
        {
          # If the value is equal to 3 (manager) or 1 (techn) if tech
          # and manager (2) is asked, it is "true".
          $ret[] = $u;
        }
    }
  return $ret;
}

# Check membership: by default, check only if someone is member of a project.
#
# With the flag option, you can check for specific right:
#    - the first letter of the flag should designate the tracker
#       (B = bugs, P = patch...
#        please use member_create_tracker_flag(ARTIFACT))
#    - the second letter, if specified, designate a role
#       1 = technician
#       2 = technican AND manager
#       3 = manager
#
# The strict variable permit to have a return "true" only if the flag
# found is exactly equal to the flag asked. For instance, if you are
# looking for someone who is only technician, and not techn. and manager,
# you can use that flag.
function member_check_array ($user_id, $group_id, $flag = 0, $strict = 0)
{
  list ($uids, $ret) = member_check_propagate_uids ($user_id);
  if (empty ($uids))
    return $ret;
  $arg = utils_placeholders ($uids);
  $result = db_execute ("
    SELECT user_id FROM user_group
    WHERE user_id IN ($arg) AND group_id = ? AND admin_flags <> ?",
    array_merge ($uids, [$group_id, MEMBER_FLAGS_PENDING])
  );

  if (!$result)
    return $ret;
  $uids = [];
  while ($member = db_fetch_array ($result))
    $uids[] = $member[0];
  if (!$flag)
    # Member of a project, not looking for specific permission.
    return array_merge ($ret, $uids);

  $vals = member_check_array_perms ($group_id, $flag, $uids, $strict);
  return array_merge ($ret, $vals);
}
function member_check ($user_id, $group_id, $flag = 0, $strict = 0)
{
  $a = member_check_array ([$user_id], $group_id, $flag, $strict);
  return !empty ($a);
}

function member_check_admin_flags ($user_id, $group_id, $flags)
{
  if (!$user_id)
    $user_id = user_getid ();
  return member_get_admin_flags ($user_id, $group_id) === $flags;
}

# Additional function to check whether a member is pending for a group
# (partly member, so).
function member_check_pending ($user_id, $group_id)
{
  return member_check_admin_flags ($user_id, $group_id, MEMBER_FLAGS_PENDING);
}

# Find out if the member is a squad or a normal uiser.
function member_check_squad ($user_id, $group_id)
{
  return member_check_admin_flags ($user_id, $group_id, MEMBER_FLAGS_SQUAD);
}

# Function like member_check() only checking if one specific user is allowed
# to read private content.
# This stuff was not included in member_check() to ease development, nothing
# else.
function member_check_private ($user_id, $group_id)
{
  if (!$user_id)
    {
      if (!user_isloggedin ())
        return false; # No valid user ID.
      if (user_is_super_user ())
        return true; # Site admins: always return true.
      $user_id = user_getid ();
    }
  if (member_check ($user_id, $group_id, MEMBER_FLAGS_ADMIN))
    return true; # Give access to admins of the group.

  # Determine whether the user is a member allowed to read private data.
  $res = db_execute ("
    SELECT user_id FROM user_group
    WHERE user_id = ? AND group_id = ? AND admin_flags <> ?
    AND privacy_flags = '1'", [$user_id, $group_id, MEMBER_FLAGS_PENDING]
  );
  return db_numrows ($res) != 0;
}

# Permit to keep the "simple" syntax of member_check but also
# to be able to generate this simple syntax on-fly depending on
# artifact.
function member_create_tracker_flag ($code, $reverse = false)
{
  $flags = [
    'bugs' => 'B', 'patch' => 'P', 'task' => 'T', 'support' => 'S',
    'news' => 'N', 'cookbook' => 'C'
  ];
  if ($reverse)
    foreach ($flags as $k => $v)
      {
        if ($v === $code)
          return $k;
      }
  elseif (isset ($flags[$code]))
    return $flags[$code];
  return null;
}

# Check if a user belongs to a group and is pending.
# Return value: The whole row of user_group.
function member_check_is_pending ($user_id, $group_id)
{
  return member_check ($user_id, $group_id, MEMBER_FLAGS_PENDING);
}

function member_explain_roles ($role = 5)
{
  html_member_explain_roles ($role);
}
?>
