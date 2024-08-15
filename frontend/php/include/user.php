<?php
# User-related functions.
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

require_once (dirname (__FILE__) . '/member.php');
define ('USER_STATUS_ACTIVE', 'A');
define ('USER_STATUS_PENDING', 'P');
define ('USER_STATUS_SQUAD', MEMBER_FLAGS_SQUAD);
define ('USER_STATUS_SUSPENDED', 'S');
define ('USER_STATUS_DELETED', 'D');

# Unset these globals until register_globals if off everywhere.
unset ($USER_IS_SUPER_USER);
# User records in this array can be accessed by user_id as well as by user_name.
# the user_name keys are in the lower case, user names are case-insensitive.
$USER_ARR = [];

function user_status_is_removed ($status)
{
  return $status == USER_STATUS_SUSPENDED || $status == USER_STATUS_DELETED;
}

function user_isloggedin ()
{
  global $G_USER;
  return !empty ($G_USER['user_id']);
}

function user_can_be_super_user ($user_id = 0)
{
  global $USER_IS_SUPER_USER;
  if (isset ($USER_IS_SUPER_USER))
    return $USER_IS_SUPER_USER;
  $USER_IS_SUPER_USER = false;
  if (empty ($user_id))
    {
      if (!user_isloggedin ())
        return false;
      $user_id = user_getid ();
    }
  # Admins of sys_group_id are admins and have superuser privs site-wide.
  $result = db_execute ("
    SELECT * FROM user_group
    WHERE user_id = ? AND group_id = ? AND admin_flags = 'A'",
    [$user_id, $GLOBALS['sys_group_id']]
  );
  $USER_IS_SUPER_USER = (db_numrows ($result) >= 1);
  return $USER_IS_SUPER_USER;
}

function user_is_super_user ()
{
  # User is superuser only if they want, otherwise they are going to see
  # things like any other user + a link in the left menu.
  return user_can_be_super_user () && isset ($_COOKIE["session_su"])
    && $_COOKIE["session_su"] == "wannabe";
}

function user_ismember ($group_id, $type = 0)
{
  return member_check (0, $group_id, $type);
}

# Check the user role in a project  - deprecated.
function user_check_ismember ($user_id, $group_id, $type = 0)
{
  return member_check ($user_id, $group_id, $type);
}

# Get the groups to which a user belongs.
function user_groups ($uid)
{
  $result = db_execute ("SELECT * FROM user_group WHERE user_id = ", [$uid]);
  $arr = [];
  while ($val = db_fetch_array ($result))
    array_push ($arr, $val['group_id']);
  return $arr;
}

# Get the email of a user.
function user_get_email ($uid)
{
  return user_get_field ($uid, 'email');
}

function user_getrealname ($user_id = 0, $rfc822_compliant = 0)
{
  $uids = user_get_uids_array ($user_id);
  $ret = user_get_field ($uids, 'realname');
  if ($rfc822_compliant)
    $ret = array_map ('utils_comply_with_rfc822', $ret);
  foreach ($uids as $u)
    {
      if (array_key_exists ($u, $ret))
        continue;
      if (empty ($u))
        $ret[$u] = _("anonymous");
      else
        $ret[$u] = '<b>' . _("Invalid User ID") . '</b>';
    }
  return utils_return_val ($user_id, $ret);
}

function user_getname ($user_id = 0)
{
  $uids = user_get_uids_array ($user_id);
  $ret = user_get_field ($uids, 'user_name');
  foreach ($uids as $u)
    if (!array_key_exists ($u, $ret))
      # TRANSLATORS: "Not applicable".
      $ret[$u] = empty ($u)? _("NA"): "<b>#$u</b>";
  return utils_return_val ($user_id, $ret);
}

function user_getid ($username = 0)
{
  if (!$username)
    {
      # No username, return info for the current user.
      global $G_USER;
      if ($G_USER)
        return $G_USER['user_id'];
      return 0;
    }
  $user = user_get_array ($username);
  if (empty ($user))
    return 0;
  return $user['user_id'];
}

function user_squad_exists ($user_id)
{
  return $user_id && user_get_field ($user_id, 'status') == USER_STATUS_SQUAD;
}

function user_exists ($user_id)
{
  $a = user_get_field ($user_id, 'user_id');
  return $user_id && !empty ($a);
}

function user_is_active ($user_id)
{
  return user_get_field ($user_id, 'status') == USER_STATUS_ACTIVE;
}

function user_fetch_name ($user_id)
{
  $name = user_get_field ($user_id, 'user_name');
  return ($name === null)? '': $name;
}

function user_getemail ($user_id = 0)
{
  return user_get_field ($user_id, 'email');
}

function user_get_uids_array ($u)
{
  return utils_make_arg_array ($u, user_getid ());
}

function user_get_field ($user_id, $field)
{
  $uids = user_get_uids_array ($user_id);
  $u = user_get_array ($uids);
  $ret = [];
  foreach ($u as $k => $v)
    $ret[$k] = $v[$field];
  return utils_return_val ($user_id, $ret);
}

function user_get_gpg_key ($user_id = 0, $minified = false)
{
  $uids = user_get_uids_array ($user_id);
  $user_key = user_get_field ($uids, 'gpg_key');
  $pref_key = user_get_preference ('gpg_key', $uids);
  $ret = [];
  foreach ($uids as $u)
    {
      if (empty ($user_key[$u]))
        $user_key[$u] = $pref_key[$u];
      if ($minified || empty ($pref_key[$u]))
        $ret[$u] = $user_key[$u];
      else
        $ret[$u] = $pref_key[$u];
    }
  return utils_return_val ($user_id, $ret);
}

function user_set_gpg_key ($key)
{
  if (!user_isloggedin ())
    return;
  list ($minified, $error) = gpg_minify_key ($key);
  if ($error)
    $minified = '';
  $res = db_autoexecute (
    'user', ['gpg_key' => $minified], DB_AUTOQUERY_UPDATE,
    "user_id = ?", [user_getid ()]
  );
  $ret = user_set_preference ('gpg_key', $key);
  return $res && $ret;
}

function user_filter_missing ($users)
{
  global $USER_ARR;
  $missing = $uids = $names = [];
  foreach ($users as $u)
    if (!array_key_exists ($u, $USER_ARR))
      $missing[] = $u;
  if (empty ($missing))
    return [$names, $uids];
  foreach ($missing as $u)
    if (ctype_digit (strval ($u)))
      $uids[] = $u;
    else
      $names[] = $u;
  return [$names, $uids];
}

# Fetch a row from user table by user_id or user_name unless already cached,
# put it to $USER_ARR under both user_id and user_name.
function user_fetch_data ($users)
{
  global $USER_ARR;
  $lower_users = array_map ('strtolower', $users);
  list ($names, $uids) = user_filter_missing ($lower_users);
  $arg = array_merge ($names, $uids);
  if (empty ($arg))
    return;
  $cond = [];
  if (!empty ($names))
    $cond[] = "user_name " . utils_in_placeholders ($names);
  if (!empty ($uids))
    $cond[] = "user_id " . utils_in_placeholders ($uids);
  $res = db_execute ("SELECT * FROM user WHERE " . join (' OR ', $cond), $arg);
  while ($arr = db_fetch_array ($res))
    {
      $idx = strtolower ($arr['user_name']);
      $USER_ARR[$arr['user_id']] = $USER_ARR[$idx] = $arr;
    }
  db_free_result ($res);
}

# Return user data array; when $user is an array, return an array of
# records with the indices from the input array.
function user_get_array ($user)
{
  global $USER_ARR;
  if (empty ($user))
    return [];
  $users = user_get_uids_array ($user);
  user_fetch_data ($users);
  $ret = [];
  foreach ($users as $name)
    {
      $idx = strtolower ($name);
      if (!empty ($USER_ARR[$idx]))
        $ret[$name] = $USER_ARR[$idx];
    }
  return utils_return_val ($user, $ret);
}

function user_get_timezone ()
{
  if (!user_isloggedin ())
    return '';
  return user_get_field (user_getid (), 'timezone');
}

function user_normalize_pref_name ($name)
{
  return strtolower (trim ($name));
}

function user_normalize_preference_names ($names)
{
  $ret = array_map ('user_normalize_pref_name', utils_make_arg_array ($names));
  return utils_return_val ($names, $ret);
}

function user_set_preference ($preference_name, $value)
{
  global $user_pref;
  if (!user_isloggedin ())
    return false;
  $preference_name = user_normalize_pref_name ($preference_name);
  $res = db_execute ("
    SELECT NULL FROM user_preferences
    WHERE user_id = ? AND preference_name = ?",
    [user_getid (), $preference_name]
  );
  if (db_numrows ($res) > 0)
    $result = db_autoexecute ('user_preferences',
      ['preference_value' => $value], DB_AUTOQUERY_UPDATE,
      "user_id = ? AND preference_name = ?", [user_getid (), $preference_name]
    );
  else
    $result = db_autoexecute ('user_preferences',
      [ 'user_id' => user_getid (), 'preference_name' => $preference_name,
        'preference_value' => $value],
      DB_AUTOQUERY_INSERT
    );
  if (isset ($user_pref))
    $user_pref[$preference_name] = $value;
  return true;
}

function user_unset_preference ($preference_name)
{
  global $user_pref;
  if (!user_isloggedin ())
    return false;
  $preference_name = user_normalize_pref_name ($preference_name);
  $result = db_execute ("
    DELETE FROM user_preferences
    WHERE user_id = ? AND preference_name = ? LIMIT 1",
    [user_getid (), $preference_name]
  );
  if (isset ($user_pref))
    unset ($user_pref[$preference_name]);
  return true;
}

function user_fetch_preferences ($uids, $pref_names = [])
{
  $pref_cond = '';
  if (!empty ($pref_names))
    $pref_cond = "\n      preference_name "
      . utils_in_placeholders ($pref_names) . "\n      AND ";
  $res = db_execute ("
    SELECT preference_name, preference_value, user_id FROM user_preferences
    WHERE {$pref_cond}user_id " . utils_in_placeholders ($uids),
    array_merge ($pref_names, $uids)
  );
  $ret = [];
  foreach ($uids as $u)
    $ret[$u] = [];
  if (!empty ($pref_names))
    foreach ($uids as $u)
      foreach ($pref_names as $p)
        $ret[$u][$p] = null;
  while ($row = db_fetch_array ($res))
    $ret[$row['user_id']][$row['preference_name']] = $row['preference_value'];
  return $ret;
}

# Both $preference_names and $user_ids may be either scalars or lists;
# the return value is structured respectively; when the result is
# two-dimensional, the user ID is the first index: $ret[$uid][$pref_name].
function user_get_preference_by_id ($preference_names, $user_ids)
{
  $uids = user_get_uids_array ($user_ids);
  $pref_names = utils_make_arg_array ($preference_names);
  $prefs = user_fetch_preferences ($uids, $pref_names);
  if (is_array ($preference_names))
    return utils_return_val ($user_ids, $prefs);
  # Scalar $preference_names: exclude the redundant preference name
  # from the result, just like utils_return_val does.
  $ret = [];
  foreach ($prefs as $uid => $arr)
    foreach ($arr as $name => $val)
      $ret[$uid] = $val;
  return utils_return_val ($user_ids, $ret);
}

function user_get_preference ($preference_name, $user_id = false)
{
  global $user_pref;
  if ($user_id !== false)
    return user_get_preference_by_id ($preference_name, $user_id);

  if (!user_isloggedin ())
    return false;
  $uid = user_getid ();
  if (empty ($user_pref))
    foreach (user_fetch_preferences ([$uid])[$uid] as $name => $value)
      $user_pref[$name] = $value;
  $pref = utils_make_arg_array ($preference_name);
  $pref = user_normalize_preference_names ($pref);
  $ret = [];
  foreach ($pref as $p)
    $ret[$p] = isset ($user_pref[$p])? $user_pref[$p]: false;
  return utils_return_val ($preference_name, $ret);
}

# Find out if the user use the vote, very similar to
# trackers_votes_remaining.
function user_use_votes ($user_id = false)
{
  if (!$user_id)
    $user_id = user_getid ();

  $result = db_execute (
    "SELECT vote_id FROM user_votes WHERE user_id = ?", [$user_id]
  );
  return db_numrows ($result) > 0;
}

# Like context_guess, this will set a AUDIENCE constant that could be used
# later to determine specific page context, for instance to know which
# recipes are relevant.
# This should be called once in pre.
# The valid AUDIENCE names depends on include/trackers/cookbook.php
# except that role specific "managers" and "technicians" will not be handled
# here, as you can be both manager and technician, and since it implies that
# you are member of the pro.
function user_guess ()
{
  # $group_id should have been sanitized already.
  global $group_id;

  if (!user_isloggedin ())
    {
      define ('AUDIENCE', 'anonymous');
      return true;
    }
  # On a non-group page?
  if (!$group_id)
    {
      define ('AUDIENCE', 'loggedin');
      return true;
    }
  # On a group page without being member of the group?
  if (!member_check (0, $group_id))
    {
      define ('AUDIENCE', 'loggedin');
      return true;
    }
  # Being member.
  define ('AUDIENCE', 'members');
  return true;
}

$user_history_field_names = "(
  field_name IN (
    'Added User', 'User Requested Membership', 'Removed User', 'Approved User',
    'Changed User Permissions',
    'Set Active Features to the default for the Group Type',
    'Set Mail Notification to a sensible default',
    'Set Active Features to the default for the Group Type',
    'Set Mail Notification to a sensible default'
  )
  OR field_name LIKE 'Added User to Squad %'
  OR field_name LIKE 'Removed User from Squad %'
)";

# Return true when account has any traces in the trackers and group_history,
# so it should not be removed from the database.
function user_has_history ($user_id)
{
  global $user_history_field_names;
  $name = user_fetch_name ($user_id);
  if ($name == '')
    return false;
  foreach (utils_get_tracker_list () as $tr)
    {
      $result = db_execute (
        "SELECT bug_id FROM $tr WHERE submitted_by = ? LIMIT 1", [$user_id]
      );
      if (db_numrows ($result) > 0)
        return true;
      $result = db_execute ("
        SELECT bug_history_id FROM {$tr}_history WHERE mod_by = ? LIMIT 1",
        [$user_id]
      );
      if (db_numrows ($result) > 0)
        return true;
    }
  $result = db_execute ("
    SELECT group_history_id FROM group_history
    WHERE
      mod_by = ? OR (old_value = ? AND $user_history_field_names)
    LIMIT 1", [$user_id, $name]
  );
  if (db_numrows ($result) > 0)
    return true;
  $result = db_execute ("
    SELECT group_forum_id FROM forum WHERE posted_by = ? LIMIT 1",
    [$user_id]
  );
  return db_numrows ($result) > 0;
}

# Delete personal query forms.
function user_delete_query_forms ($user_id, $art)
{
  $res = db_execute (
    "SELECT report_id FROM {$art}_report WHERE scope = ? AND user_id = ?",
    ['I', $user_id]
  );
  $report_ids = [];
  while ($row = db_fetch_array ($res))
    $report_ids[] = $row['report_id'];
  if (empty ($report_ids))
    return;
  db_execute ("
    DELETE FROM {$art}_report
    WHERE report_id " . utils_in_placeholders ($report_ids), $report_ids
  );
  db_execute ("
    DELETE FROM {$art}_report_field
    WHERE report_id " . utils_in_placeholders ($report_ids), $report_ids
  );
}

# Delete user's data that are not stored in the 'user' table.
function user_delete_aux_data ($user_id)
{
  foreach (['user_group', 'user_squad', 'user_bookmarks', 'user_preferences',
    'user_votes', 'session'] as $table)
    db_execute ("DELETE FROM $table WHERE user_id = ?", [$user_id]);
  foreach (utils_get_tracker_list () as $a)
    user_delete_query_forms ($user_id, $a);
}

# Completely remove account from the database; should only be done
# when there was no activity related to the account on trackers
# and in group_history.
function user_purge ($user_id)
{
  db_execute ("DELETE FROM user where user_id = ?", [$user_id]);
  user_delete_aux_data ($user_id);
}

# Rename account, with necessary history adjustments in the database (unless
# that would raise any concerns).
function user_rename ($user_id, $new_name)
{
  global $user_history_field_names;
  $old_name = user_fetch_name ($user_id);
  if ($old_name == '')
    return sprintf ('No user #%i in the database', $user_id);
  db_execute (
    "UPDATE user SET user_name = ? WHERE user_id = ?", [$new_name, $user_id]
  );
  $res = db_execute (
    "SELECT user_id FROM user WHERE user_name = ?", [$new_name]
  );
  if (db_numrows ($res) > 1)
    {
      db_execute (
        "UPDATE user SET user_name = ? WHERE user_id = ?",
        [$old_name, $user_id]
      );
      return sprintf ('User <%s> already exists', $new_name);
    }
  db_execute ("
    UPDATE group_history set old_value = ?
    WHERE old_value = ? AND $user_history_field_names",
    [$new_name, $old_name]
  );
  return '';
}

# Function that should always be used to remove an user account.
# This function should always be used in a secure context, when user_id
# is 100% sure.
# Best is to not to pass the user_id argument unless necessary.
function user_delete ($user_id = false, $confirm_hash = false)
{
  if (!$user_id)
    $user_id = user_getid ();

  # Serious deal, serious check of credentials: allowed only to superuser
  # and owner of the account.
  if (!user_is_super_user () && $user_id != user_getid ())
    exit_permission_denied ();

  $confirm_hash_test = '';
  $confirm_hash_param = [];
  # If self-destruct, the correct confirm_hash must be provided.
  if (!user_is_super_user ())
    {
      $confirm_hash_test = " confirm_hash = ? AND ";
      $confirm_hash_param = [$confirm_hash];
    }

  if (!user_has_history ($user_id))
    {
      user_purge ($user_id);
      fb (_("Account deleted."));
      return true;
    }

  $new_realname = '-*-';
  if ($user_id == user_getid ())
    $new_realname = '-';
  $success = db_autoexecute ('user',
    [ 'user_pw' => '!', 'realname' => $new_realname, 'status' => 'S',
      'email' => 'idontexist@example.net', 'confirm_hash' => '',
      'authorized_keys' => '', 'people_view_skills' => '0',
      'people_resume' => '', 'timezone' => 'GMT', 'theme' => '',
      'gpg_key' => '', 'email_new' => ''],
    DB_AUTOQUERY_UPDATE, "$confirm_hash_test user_id = ?",
    array_merge ($confirm_hash_param, [$user_id])
  );
  if (!$success)
    {
      fb (_("Failed to update the database."), 1);
      return false;
    }
  user_delete_aux_data ($user_id);
  # Rename user; the name starts with '_' so it can't be registered manually,
  # and it shall be unique because it's derived from $user_id.
  user_rename ($user_id, "_$user_id");
  fb (_("Account deleted."));
  return true;
}

# List groups where we can track date of inclusion; private groups
# are not filtered out.
function user_list_groups_with_history ($uid, $skip)
{
  return db_execute ("
    SELECT
      g.group_name, g.group_id, g.unix_group_name, g.is_public,
      u.admin_flags, h.date
    FROM groups g, user_group u, group_history h, user a
    WHERE
      g.group_id = u.group_id AND h.group_id = u.group_id $skip
      AND u.user_id = ? AND a.user_id = ? AND h.old_value = a.user_name
      AND g.status = 'A' AND h.field_name IN ('Added User', 'Approved User')
    GROUP BY g.group_id ORDER BY g.group_id", [$uid, $uid]
  );
}

# Like user_list_group_with_history, but based only on actual membership.
function user_list_groups_without_history ($uid, $skip)
{
  return db_execute ("
    SELECT g.group_name, g.group_id, g.unix_group_name, g.is_public,
      u.admin_flags, 0 as date
    FROM groups g, user_group u
    WHERE g.group_id = u.group_id AND u.user_id = ? AND g.status = 'A' $skip
    GROUP BY g.group_id ORDER BY g.group_id", [$uid]
  );
}

# Merge results of user_list_groups_with_history
# and user_list_group_without_history into an array.
function user_list_merge_row (&$ret, &$row)
{
  if (array_key_exists ($row['group_id'], $ret))
    return;
  $fields = [
    'group_name', 'unix_group_name', 'date', 'admin_flags', 'is_public'
  ];
  $ret[$row['group_id']] = [];
  foreach ($fields as $f)
    $ret[$row['group_id']][$f] = $row[$f];
}

# Auxiliary function used in user_list_groups.
function user_enumerate_groups ($uid, $skip_pending)
{
  $skip = '';
  if ($skip_pending)
    $skip = "AND u.admin_flags != '" . MEMBER_FLAGS_PENDING . "'";
  $ret = [];
  $result = user_list_groups_with_history ($uid, $skip);
  while ($row = db_fetch_array ($result))
    user_list_merge_row ($ret, $row);
  $result = user_list_groups_without_history ($uid, $skip);
  while ($row = db_fetch_array ($result))
    user_list_merge_row ($ret, $row);
  return $ret;
}

# List private groups listed in $ret that the current user shouldn't see;
# used in user_list_groups.
function user_list_groups_to_hide (&$ret)
{
  $private = [];
  foreach ($ret as $group_id => $v)
    if (!$v['is_public'])
      $private[] = $group_id;
  if (empty ($private) || !user_isloggedin ())
    return $private;
  $gid_in = utils_in_placeholders ($private);
  $result = db_execute ("
    SELECT group_id FROM user_group
    WHERE user_id = ? AND admin_flags != ? AND group_id $gid_in",
    array_merge ([user_getid (), MEMBER_FLAGS_PENDING], $private)
  );
  $allowed = [];
  while ($row = db_fetch_array ($result))
    $allowed[] = $row['group_id'];
  return array_diff ($private, $allowed);
}

# Return groups $uid belongs in allowed to show up for the current user.
# The key is group_id, the items are arrays with keys 'group_name',
# 'unix_group_name', 'date' (zero when unavailable), 'admin_flags'.
function user_list_groups ($uid, $skip_pending)
{
  $ret = user_enumerate_groups ($uid, $skip_pending);
  if (empty ($ret) || user_is_super_user () || $uid == user_getid ())
    # No need to filter out private groups.
    return $ret;
  foreach (user_list_groups_to_hide ($ret) as $gid)
    unset ($ret[$gid]);
  return $ret;
}

function user_format_member_since ($date)
{
  if (empty ($date))
    return '';
  return '<span class="smaller">'
    . sprintf (_("Member since %s"), utils_format_date ($date))
    . '</span>';
}
?>
