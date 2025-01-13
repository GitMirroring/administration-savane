<?php
# Membership functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2025 Ineiev
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

$MEMBER_FLAGS_ACTIVE = [MEMBER_FLAGS_MEMBER => 1, MEMBER_FLAGS_ADMIN => 1];

function member_history_label_on_add ($status)
{
  if ($status === MEMBER_FLAGS_PENDING)
    return 'User Requested Membership';
  if ($status === MEMBER_FLAGS_SQUAD)
    return 'Created Squad';
  return 'Added User';
}

# Assign user.uidNumber (invoked whenever a user joins a group);
# update numbers cached in user_group.
function member_assign_uidNumber ($user_id)
{
  $uid = utils_assign_idNumber ($user_id);
  if (empty ($uid))
    return;
  member_update_file ($user_id, 'passwd');
  db_execute ("
    UPDATE `user_group` u, `groups` g
    SET u.`cache_uidNumber` = ?, u.`cache_gidNumber` = g.`gidNumber`,
      u.`cache_user_name` = ?
    WHERE `user_id` = ? AND u.`group_id` = g.`group_id`",
    [$uid, user_getname ($user_id), $user_id]
  );
}

function member_user_group_fields ($user_id, $group_id, $status)
{
  $uid = user_get_field ($user_id, 'uidNumber');
  $gid = group_get_gidNumber ($group_id);
  $cols = [
   'admin_flags' => $status, 'cache_gidNumber' => $gid,
   'cache_uidNumber' => $uid, 'cache_user_name' => user_getname ($user_id)
  ];
  $cond = ['user_id' => $user_id, 'group_id' => $group_id];
  return [$cols, $cond];
}

# Add or update a user to a group; $status is the 'admin_flags'.
function member_add ($user_id, $group_id, $status = '')
{
  if (member_check ($user_id, $group_id))
    {
      fb (_("This user is already member of the group."), 1);
      return 0;
    }
  if ($status === MEMBER_FLAGS_MEMBER)
    # Assign uidNumber before backend scripts have a chance to
    # do that concurrently.
    member_assign_uidNumber ($user_id);
  list ($cols, $cond) = member_user_group_fields ($user_id, $group_id, $status);
  $result = db_autoexecute ('user_group', array_merge ($cols, $cond));
  if (!$result)
    return $result;
  if ($status === MEMBER_FLAGS_MEMBER)
    member_update_file ($group_id, 'group');
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
  $admin_flags = array_keys ($GLOBALS['MEMBER_FLAGS_ACTIVE']);
  $result = db_execute ("
    SELECT
      unix_group_name AS name, MAX(gidNumber) AS gid,
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
  $r = db_fetch_array ($result);
  $r['line'] = $r['name'] . ':x:' . $r['gid'] . ':' . $r['users'] . "\n";
  return $r;
}

function member_fetch_passwd_data ($user_id)
{
  global $sys_passwd_common_gid, $sys_passwd_home_dir, $sys_passwd_user_shell;
  $data = user_get_fields (['user_name', 'realname', 'uidNumber'], $user_id);
  if (empty ($data))
    return null;
  $data['name'] = $data['user_name'];
  $line = [$data['user_name'], 'x', $data['uidNumber'], $sys_passwd_common_gid];
  $line[] = str_replace (':', ' ', $data['realname']);
  $line[] = $sys_passwd_home_dir;
  $line[] = $sys_passwd_user_shell;
  $data['line'] = join (':', $line) . "\n";
  return $data;
}

function member_file_unavailable ($var)
{
  if (!array_key_exists ($var, $GLOBALS))
    return true;
  if (!is_file ($GLOBALS[$var]))
    return true;
  return !is_readable ($GLOBALS[$var]);
}

function member_substitute_line (&$data, $line)
{
  if (empty ($data['line']))
    return $line;
  if (!preg_match ("/^{$data['name']}:/", $line))
    return $line;
  $line = $data['line'];
  $data['line'] = null;
  return $line;
}

function member_substitute_file ($arg)
{
  list ($tmp_file, $id, $file, $fetch_func) = $arg;
  $data = $fetch_func ($id);
  if ($data === null)
    return;
  $out = fopen ($tmp_file, 'w');
  $in = fopen ($GLOBALS[$file], 'r');
  while (false !== ($line = fgets ($in)))
    fwrite ($out, member_substitute_line ($data, $line));
  if ($data['line'] !== null)
    fwrite ($out, $data['line']);
  fclose ($in);
  fclose ($out);
  chmod ($tmp_file, 0644);
  rename ($tmp_file, $GLOBALS[$file]);
}

function member_update_file ($id, $type)
{
  $files = ['group' => 'sys_group_file', 'passwd' => 'sys_passwd_file'];
  $fetch = [
    'group' => 'member_fetch_group_data', 'passwd' => 'member_fetch_passwd_data'
  ];
  if (member_file_unavailable ($files[$type]))
    return;
  $tmp = utils_mktemp ($type);
  if ($tmp === null)
    return;
  utils_run_lock (
    $GLOBALS[$files[$type]], 'member_substitute_file',
    [$tmp, $id, $files[$type], $fetch[$type]]
  );
}

# Approve a pending user for a group.
function member_approve ($user_id, $group_id)
{
  member_assign_uidNumber ($user_id);
  list ($cols, $cond) =
    member_user_group_fields ($user_id, $group_id, MEMBER_FLAGS_MEMBER);
  $where = $cond_list = [];
  foreach ($cond as $k => $v)
    {
      $cond_list[] = $v;
      $where[] = "$k = ?";
    }
  $result = db_autoexecute (
    'user_group', $cols, DB_AUTOQUERY_UPDATE, join (' AND ', $where), $cond_list
  );
  if (!$result)
    return $result;
  member_update_file ($group_id, 'group');
  group_add_history ('Approved User', user_getname ($user_id), $group_id);
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
      member_update_file ($group_id, 'group');
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
    util_die ('group_getpermissions: invalid argument flags');
  $flags .= '_flags';
  $members = member_get_group_members ($group_id);
  foreach ($user_ids as $u)
    if (array_key_exists ($u, $members))
      $ret[$u] = $members[$u][$flags];
  return $ret;
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

function member_check_single_perm ($value, $flag_level, $strict)
{
  # Compare the value and what is asked.
  if ($value == $flag_level)
    return true; # If the value is equal to the flag, $u is obviously included.
  if ($strict)
    return false;
  if (2 == $value && in_array ($flag_level, [1, 3]))
    # The value is equal to 2 (manager and tech) if tech (1)
    # or manager (3) is asked.
    return true;
  if (2 == $flag_level  && in_array ($value, [1, 3]))
    # If the value is equal to 3 (manager) or 1 (techn) if tech
    # and manager (2) is asked, it is "true".
    return true;
  return false;
}

function member_check_array_perms ($group_id, $flag, $uids, $strict)
{
  list ($flag_tracker, $flag_level) = member_check_split_flags ($flag);
  $values = member_array_getpermissions ($group_id, $flag_tracker, $uids);
  $def_perms = group_getpermissions ($group_id, $flag_tracker);
  if (!$def_perms)
    $def_perms = group_gettypepermissions ($group_id, $flag_tracker);
  if (!$def_perms)
    $def_perms = "ERROR";
  $ret = [];
  foreach ($uids as $u)
    {
      $value = $def_perms;
      if (!empty ($values[$u]))
        $value = $values[$u];
      if (member_check_single_perm ($value, $flag_level, $strict))
        $ret[] = $u;
    }
  return $ret;
}

function member_get_group_members ($group_id)
{
  static $cache = [];
  $members = [];
  if (!array_key_exists ($group_id, $cache))
    {
      $result = db_execute (
        "SELECT * FROM user_group WHERE group_id = ?", [$group_id]
      );
      while ($row = db_fetch_array ($result))
        $members[$row['user_id']] = $row;
      $cache[$group_id] = $members;
    }
  return $cache[$group_id];
}

function member_users_are_in_group ($group_id, $uids)
{
  $members = member_get_group_members ($group_id);
  $ret = [];
  foreach ($uids as $u)
    {
      if (empty ($members[$u]))
        continue;
      if ($members[$u]['admin_flags'] != MEMBER_FLAGS_PENDING)
        $ret[] = $u;
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
#       2 = technician AND manager
#       3 = manager
#
# The strict variable permit to have a return "true" only if the flag
# found is exactly equal to the flag asked. For instance, if you are
# looking for someone who is only technician, and not techn. and manager,
# you can use that flag.
function member_check_array ($user_id, $group_id, $flag = 0, $strict = 0)
{
  if (in_array ($flag, [1, 2, 3]))
    $flag = member_create_tracker_flag (ARTIFACT) . "$flag";
  list ($uids, $ret) = member_check_propagate_uids ($user_id);
  if (empty ($uids))
    return $ret;
  $uids = member_users_are_in_group ($group_id, $uids);
  if (empty ($uids))
    return $ret;
  if (!$flag) # Member of the group, not looking for specific permission.
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
  $members = member_get_group_members ($group_id);
  if (!array_key_exists ($user_id, $members))
    return false;
  return $members[$user_id]['privacy_flags'] == 1
    && $members[$user_id]['admin_flags'] != MEMBER_FLAGS_PENDING;
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
