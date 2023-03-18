<?php
# User-related functions.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2004-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2019, 2020, 2023 Ineiev
#
# This file is part of Savane.
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

require_once (dirname (__FILE__) . '/member.php');

# Unset these globals until register_globals if off everywhere.
unset ($USER_IS_SUPER_USER);
$USER_RES = [];

function user_isloggedin ()
{
  global $G_USER;
  return !empty ($G_USER['user_id']);
}

function user_can_be_super_user ()
{
  global $USER_IS_SUPER_USER;
  if (isset ($USER_IS_SUPER_USER))
    return $USER_IS_SUPER_USER;
  $USER_IS_SUPER_USER = false;
  if (!user_isloggedin ())
    return false;
  # Admins of sys_group_id are admins and have superuser privs site-wide.
  $result = db_execute ("
    SELECT * FROM user_group
    WHERE user_id = ? AND group_id = ? AND admin_flags = 'A'",
    [user_getid (), $GLOBALS['sys_group_id']]
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
  $result = db_execute ("SELECT * FROM user WHERE user_id = ?", [$uid]);
  $val = db_fetch_array ($result);
  return $val['email'];
}

function user_getname ($user_id = 0, $getrealname = 0)
{
  global $G_USER, $USER_NAMES;

  # Use current user if one is not passed in.
  if (!$user_id && $getrealname)
    $user_id = user_getid ();

  $prefix = 'realname';
  $column = 'realname';
  if (!$getrealname)
    {
      $prefix = 'user';
      $column = 'user_name';
    }
  if (!$user_id)
   {
      if ($getrealname)
        return _("anonymous");
      if ($G_USER)
        return $G_USER['user_name'];
      # TRANSLATORS: "Not applicable".
      return _("NA");
   }

  # Lookup name.
  if (!empty ($USER_NAMES["{$prefix}_$user_id"]))
    return $USER_NAMES["{$prefix}_$user_id"];
  # Fetch the user name and store it for future reference.
  $result = db_execute ("
    SELECT user_id, user_name, realname FROM user WHERE user_id = ?",
    [$user_id]
  );
  if (db_numrows ($result))
    {
      # Valid user - store and return.
      $USER_NAMES["{$prefix}_$user_id"] = db_result ($result, 0, $column);
      return $USER_NAMES["{$prefix}_$user_id"];
    }
  $uid = "#$user_id";
  if ($getrealname)
    $uid = _("Invalid User ID");
  $USER_NAMES["{$prefix}_$user_id"] = "<strong>$uid</strong>";
  return $USER_NAMES["{$prefix}_$user_id"];
}

function user_getid  ($username = 0)
{
  if (!$username)
    {
      # No username, return info for the current user.
      global $G_USER;
      if ($G_USER)
        return $G_USER['user_id'];
      return 0;
    }
  $result = db_execute (
    "SELECT user_id FROM user WHERE user_name = ?", [$username]
  );
  if (db_numrows ($result) > 0)
    return db_result ($result, 0, "user_id");
  return 0;
}

function user_squad_exists ($user_id)
{
  return $user_id && user_get_field ($user_id, 'status') == 'SQD';
}

function user_exists ($user_id)
{
  return $user_id && !empty (user_get_field ($user_id, 'user_id'));
}

function user_is_active ($user_id)
{
  return user_get_field ($user_id, 'status') == 'A';
}

function user_fetch_name ($user_id)
{
  $name = user_get_field ($user_id, 'user_name');
  return ($name === null)? '': $name;
}

function user_getrealname ($user_id = 0, $rfc822_compliant = 0)
{
  $ret = user_getname($user_id, 1);
  if ($rfc822_compliant)
    return utils_comply_with_rfc822 ($ret);
  return $ret;
}

function user_getemail($user_id=0)
{
  return user_get_field ($user_id, 'email');
}

# Fetch a row from user table by user_id unless already cached,
# put it to $USER_RES[$user_id].
function user_get_result_set ($user_id)
{
  global $USER_RES;
  if (empty ($user_id))
    return null;
  if (empty ($USER_RES[$user_id]))
    $USER_RES[$user_id] =
      db_execute ("SELECT * FROM user WHERE user_id = ?", [$user_id]);
  return $USER_RES[$user_id];
}

function user_get_field ($user_id, $field)
{
  if (!$user_id)
    $user_id = user_getid ();
  $result = user_get_result_set ($user_id);
  if (db_numrows ($result) > 0)
    return db_result ($result, 0, $field);
  return null;
}

# Fetch a row from user table by user_name, put it to $USER_RES[$user_id].
function user_get_result_set_from_user_name ($user_name)
{
  global $USER_RES;
  $res = db_execute ("SELECT * FROM user WHERE user_name = ?", [$user_name]);
  if (!db_numrows ($res))
    return null;
  $USER_RES[db_result ($res, 0, 'user_id')] = $res;
  return $res;
}

function user_get_timezone ()
{
  if (!user_isloggedin ())
    return '';
  return user_get_field (user_getid (), 'timezone');
}

function user_set_preference ($preference_name, $value)
{
  global $user_pref;
  if (!user_isloggedin ())
    return false;
      $preference_name = strtolower(trim($preference_name));
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
  $preference_name = strtolower (trim ($preference_name));
  $result = db_execute ("
    DELETE FROM user_preferences
    WHERE user_id = ? AND preference_name = ? LIMIT 1",
    [user_getid (), $preference_name]
  );
  if (isset ($user_pref))
    unset ($user_pref[$preference_name]);
  return true;
}

function user_get_preference ($preference_name, $user_id = false)
{
  global $user_pref;

  if ($user_id)
    {
      # Looking for information without being the user.
      $res = db_execute ("
        SELECT preference_value FROM user_preferences
        WHERE user_id = ? AND preference_name = ?",
        [$user_id, $preference_name]
      );
      if (db_numrows ($res) > 0)
        return db_result ($res, 0, 'preference_value');
      return null;
    }

  if (!user_isloggedin ())
    return false;
  $preference_name = strtolower (trim ($preference_name));
  # First check to see if we have already fetched the preferences.
  if ($user_pref)
    {
      if (!empty ($user_pref[$preference_name]))
        return $user_pref[$preference_name];
      return false;
    }
  # We haven't returned prefs - go to the DB.
  $result = db_execute ("
    SELECT preference_name, preference_value
    FROM user_preferences WHERE user_id = ?", [user_getid ()]
  );
  if (db_numrows ($result) < 1)
    return false;
  for ($i = 0; $i < db_numrows ($result); $i++)
    {
      $user_pref[db_result ($result, $i, 'preference_name')] =
        db_result ($result, $i, 'preference_value');
    }
  if (isset ($user_pref["$preference_name"]))
    return $user_pref["$preference_name"];
  return false;
}

# Find out if the user use the vote, very similar to
# trackers_votes_user_remains_count.
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
  $trackers = ['bugs', 'support', 'task', 'patch', 'cookbook'];
  $name = user_fetch_name ($user_id);
  if ($name == '')
    return false;
  foreach ($trackers as $tr)
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

# Completely remove account from the database; should only be done
# when there was no activity related to the account on trackers
# and in group_history.
function user_purge ($user_id)
{
  db_execute ("DELETE FROM user where user_id = ?", [$user_id]);
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
      return sprintf ('User <%s> alredy exists', $new_name);
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
  # Remove from any groups, if by any chances this was not done before
  # (normally, an user must quit groups before being allowed to delete his
  # account).
  db_execute ("DELETE FROM user_group WHERE user_id = ?", [$user_id]);
  db_execute ("DELETE FROM user_squad WHERE user_id = ?", [$user_id]);

  # Additionally, clean up sessions, remove prefs.
  db_execute ("DELETE FROM user_bookmarks WHERE user_id = ?", [$user_id]);
  db_execute ("DELETE FROM user_preferences WHERE user_id = ?", [$user_id]);
  db_execute ("DELETE FROM user_votes WHERE user_id = ?", [$user_id]);
  db_execute ("DELETE FROM session WHERE user_id = ?", [$user_id]);
  # Rename user; the name starts with '_' so it can't be registered manually,
  # and it shall be unique because it's derived from $user_id.
  user_rename ($user_id, "_$user_id");

  fb (_("Account deleted."));
  return true;
}
?>
