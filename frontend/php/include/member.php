<?php
# Membership functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2023 Ineiev
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
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

require_once (dirname(__FILE__) . '/database.php');

# Add or update a user to a group; $status is the 'admin_flags'.
function member_add ($user_id, $group_id, $status = '')
{
  if (member_check ($user_id, $group_id))
    {
      fb (_("This user is already member of the group."));
      return 0;
    }
  $result = db_autoexecute ('user_group',
    ['user_id' => $user_id, 'group_id' => $group_id, 'admin_flags' => $status],
    DB_AUTOQUERY_INSERT
  );
  if (!$result)
    return $result;
  if ($status == 'P')
    $comment = 'User Requested Membership';
  elseif ($status == 'SQD')
    $comment = 'Created Squad';
  else
    $comment = 'Added User';
  group_add_history ($comment, user_getname ($user_id), $group_id);
  return $result;
}

# Approve a pending user for a group.
function member_approve ($user_id, $group_id)
{
  $result = db_execute ("
    UPDATE user_group SET admin_flags = '' WHERE user_id = ? AND group_id = ?",
    [$user_id, $group_id]
  );
  if ($result)
    group_add_history ('Approved User', user_getname ($user_id), $group_id);
  return $result;
}

function member_remove ($user_id, $group_id)
{
  # Find out if it is a squad.
  $result = db_execute ("
    SELECT admin_flags FROM user_group WHERE user_id = ? AND group_id = ?",
    [$user_id, $group_id]
  );
  if (!db_numrows ($result))
    return false;

  $admin_flags = db_result ($result, 0, 'admin_flags');

  $sql = '';
  $result = db_execute ("
    DELETE FROM user_group WHERE user_id = ? AND group_id = ?",
    [$user_id, $group_id]
  );
  if (!$result)
    return $result;
  if ($admin_flags != 'SQD')
    {
      group_add_history ('Removed User', user_getname ($user_id), $group_id);
      # If it is not a squad, make sure the user is no longer associated
      # to squads of the group.
      db_execute (
        "DELETE FROM user_squad WHERE user_id = ? AND group_id = ?",
        [$user_id, $group_id]
      );
      return $result;
    }
  group_add_history ('Deleted Squad', user_getname ($user_id), $group_id);
  db_execute ("DELETE FROM user WHERE user_id = ?", [$user_id]);
  db_execute (
    "DELETE FROM user_squad WHERE squad_id = ? AND group_id = ?",
    [$user_id, $group_id]
  );
  return $result;
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
  $ret = [];
  foreach ($uids as $u)
    {
      $value = member_getpermissions ($group_id, $flag_tracker, $u);
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
    WHERE user_id IN ($arg) AND group_id = ? AND admin_flags <> 'P'",
    array_merge ($uids, [$group_id])
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
  return !empty (member_check_array ([$user_id], $group_id, $flag, $strict));
}

function member_check_admin_flags ($user_id, $group_id, $flags)
{
  if (!$user_id)
    $user_id = user_getid ();

  $result = db_execute ("
    SELECT user_id FROM user_group
    WHERE user_id = ? AND group_id = ? AND admin_flags = ?",
    [$user_id, $group_id, $flags]
  );
  return db_numrows ($result) != 0;
}

# Additional function to check whether a member is pending for a group
# (partly member, so).
function member_check_pending ($user_id, $group_id)
{
  return member_check_admin_flags ($user_id, $group_id, 'P');
}

# Find out if the member is a squad or a normal uiser.
function member_check_squad ($user_id, $group_id)
{
  return member_check_admin_flags ($user_id, $group_id, 'SQD');
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
        # Not able to get a valid user id.
        return false;
      if (user_is_super_user ())
        # Site admins: always return true.
        return true;
      $user_id = user_getid ();
    }
  # Check if its a project admin. If so, give access.
  if (member_check ($user_id, $group_id, 'A'))
    return true;

  # Determine whether someone is member allowed to read private date
  # of a project or not.
  $res = db_execute ("
    SELECT user_id FROM user_group
    WHERE user_id = ? AND group_id = ? AND admin_flags <> 'P'
    AND privacy_flags = '1'", [$user_id, $group_id]
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
  return member_check ($user_id, $group_id, 'P');
}

function member_explain_roles ($role = 5)
{
  html_member_explain_roles ($role);
}
?>
