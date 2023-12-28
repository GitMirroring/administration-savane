<?php # -*- PHP -*-
# Group info.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Free Software Foundation, Inc.
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2008 Aleix Conchillo Flaque
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

require_once (dirname (__FILE__) . '/savane_error.php');
require_once (dirname (__FILE__) . '/utils.php');

define ('TRACKER_EVENT_NEW_ITEM', 1);
define ('TRACKER_EVENT_COMMENT', 2);
define ('TRACKER_FLAG_FACTOR', 100);
define ('TRACKER_PERM_ANON', 2);
define ('TRACKER_PERM_USER', 3);
define ('TRACKER_PERM_MEMBER', 5);
define ('TRACKER_PERM_NOBODY', 6);

$PROJECT_OBJ = [];

function group_get_object ($group_id)
{
  # Create a common set of group objects,
  # save a little wear on the database.

  global $PROJECT_OBJ;
  $idx = "_{$group_id}_";
  if (empty ($PROJECT_OBJ[$idx]))
    $PROJECT_OBJ[$idx] = new Group ($group_id);
  return $PROJECT_OBJ[$idx];
}

function project_get_object ($group_id)
{
  return group_get_object ($group_id);
}

class Group extends savane_error
{
  var $data_array; # Data from db.
  var $type_data_array;
  var $group_id;
  var $db_result; # Database result set handle.
  var $perm_data_array; # Permissions data row from db.
  var $is_admin; # Whether the user is an admin/super user of this project.

  function __construct ($id)
  {
    parent::__construct ();
    $this->group_id = $id;
    $this->db_result =
      db_execute ("SELECT * FROM groups WHERE group_id = ?", [$id]);
    $this->type_data_array = $this->data_array = [];
    if (db_numrows ($this->db_result) < 1)
      {
        # Function in class we extended.
        $this->setError ('Group Not Found');
        return;
      }
   # Set up an associative array for use by other functions.
   $this->data_array = db_fetch_array ($this->db_result);
   # Find group_type information.
   $type = $this->data_array['type'];
   $this->type_id = $type;
   $this->db_type_result =
     db_execute ("SELECT * FROM group_type WHERE type_id = ?", [$type]);
   if (db_numrows ($this->db_type_result) >= 1)
     $this->type_data_array = db_fetch_array ($this->db_type_result);
  }

  # Return database result handle for direct access.
  # Generally should NOT be used - here for supporting deprecated group.php.
  function getData ()
  {
    return $this->db_result;
  }

  # Group type info.
  function getTypeName ()
  {
    return gettext ($this->type_data_array['name']);
  }

  function getTypeDescription ()
  {
     return gettext ($this->type_data_array['description']);
  }

  function getTypeBaseHost ()
  {
     return $this->type_data_array['base_host'];
  }

  function getTypeAdminEmailAddress ()
  {
     return $this->type_data_array['admin_email_adress'];
  }

  function getTypeLicenseList ()
  {
     return $this->type_data_array['license_array'];
  }

  function getTypeDevelStatusList ()
  {
     return $this->type_data_array['devel_status_array'];
  }

  function getTypeVirtualHost ()
  {
     return $this->type_data_array['mailing_list_virtual_host'];
  }

  function getTypeUrl ($artifact)
  {
    return
      str_replace (
        "%PROJECT", $this->getUnixName(),
        $this->type_data_array["url_$artifact"]
      );
  }

  function getTypeDir ($artifact)
  {
    return
      str_replace (
        "%PROJECT", $this->getUnixName(),
        $this->type_data_array["dir_$artifact"]
      );
  }

  function CanUse ($artifact)
  {
    # Tolerate "bugs" to say "bug" and "mail" to "mailing_list".
    if ($artifact == "bugs")
      $artifact = "bug";
    if ($artifact == 'mail')
      $artifact = 'mailing_list';
    if (isset ($this->type_data_array["can_use_$artifact"]))
      return $this->type_data_array["can_use_$artifact"];
    return false;
  }

  function CanModifyUrl ($artifact)
  {
    if ($artifact == 'mail')
      $artifact = 'mailing_list';
    if (isset ($this->type_data_array["is_menu_configurable_$artifact"]))
      return $this->type_data_array["is_menu_configurable_$artifact"];
    return false;
  }

  function CanModifyDir ($artifact)
  {
     return $this->type_data_array["is_configurable_$artifact"];
  }

  function get_flags ($idx)
  {
    if (isset ($this->type_data_array[$idx]))
      return $this->type_data_array[$idx];
    return null;
  }

  function getTypePermissions ($flags)
  {
    return $this->get_flags ("{$flags}_flags");
  }

  function getTypeRestrictions ($flags)
  {
    return $this->get_flags ("{$flags}_rflags");
  }

  function getTypeMailingListHost ()
  {
    return $this->type_data_array['mailing_list_host'];
  }

  function getTypeListURL ($url_type, $list)
  {
    $list_url =
      str_replace ("%LIST", $list, $this->type_data_array[$url_type]);

    return str_replace ("%PROJECT", $this->getUnixName (), $list_url);
  }

  function getTypeMailingListListinfoUrl ($list = "%LIST")
  {
    return $this->getTypeListURL ('url_mailing_list_listinfo', $list);
  }

  function getTypeMailingListArchivesUrl ($list = "%LIST")
  {
    return $this->getTypeListURL ('url_mailing_list_archives', $list);
  }

  function getTypeMailingListArchivesPrivateUrl ($list = "%LIST")
  {
    return $this->getTypeListURL ('url_mailing_list_archives_private', $list);
  }

  function getTypeMailingListAdminUrl ($list = "%LIST")
  {
    return $this->getTypeListURL ('url_mailing_list_admin', $list);
  }

  function getTypeMailingListSubscribeUrl ($list = "%LIST")
  {
    return $this->getTypeListURL ('url_mailing_list_subscribe', $list);
  }

  function getTypeMailingListUnsubscribeUrl ($list = "%LIST")
  {
    return $this->getTypeListURL ('url_mailing_list_unsubscribe', $list);
  }

  function getTypeMailingListAddress ($list = "%LIST")
  {
    return $this->getTypeListURL ('mailing_list_address', $list);
  }

  function getTypeMailingListFormat ($list = "%NAME", $index = null)
  {
    $idx = $this->type_data_array['mailing_list_format'];
    if (isset ($index))
      {
        # Return format with number $index.
        $formats = explode (',', $idx);
        $idx = $formats[$index];
      }
    return
      str_replace (
        "%PROJECT", $this->getUnixName (), str_replace ("%NAME", $list, $idx)
      );
  }

  # Group info.
  function getGroupId ()
  {
     return $this->group_id;
  }

  function getType ()
  {
     return $this->data_array['type'];
  }

  function getStatus ()
  {
      return $this->data_array['status'];
  }

  function isActive ()
  {
    if ($this->getStatus () == 'A')
      return true;
    return false;
  }

  function getDescription ()
  {
     return $this->data_array['short_description'];
  }

  function getLongDescription ()
  {
     return $this->data_array['long_description'];
  }

  function isPublic ()
  {
      return $this->data_array['is_public'];
  }

  function getUnixName ()
  {
     return strtolower ($this->data_array['unix_group_name']);
  }

  function getPublicName ()
  {
    if (isset($this->data_array['group_name']))
      return $this->data_array['group_name'];
    # TRANSLATORS: this is a placeholder for the name of a deleted group;
    # the argument is group id (a number).
    return sprintf (_("[Deleted group #%s]"), $this->group_id);
  }

  fuNction getName ()
  {
    return $this->getPublicName ();
  }

  # Date the group was registered.
  function getStartDate ()
  {
     return $this->data_array['register_time'];
  }

  function getLicense ()
  {
    return $this->data_array['license'];
  }

  function getLicense_other ()
  {
    return $this->data_array['license_other'];
  }

  function getDevelStatus()
  {
    if ($this->data_array['devel_status'] == '')
      return 0;
    return $this->data_array['devel_status'];
  }
  function getGPGKeyring ()
  {
    $keyring = "";
    $res =
      db_execute ("
        SELECT user_name, realname, gpg_key
        FROM user
        JOIN user_group ON user.user_id = user_group.user_id
        WHERE admin_flags <> 'P' AND status = 'A' AND group_id = ?",
        [$this->group_id]
      );
    $rows = db_numrows ($res);
    for ($j = 0; $j < $rows; $j++)
      {
        $key = db_result ($res, $j, 'gpg_key');
        $user = db_result ($res, $j, 'user_name');
        $name = db_result ($res, $j, 'realname');
        if (!$key)
          continue;
        # TRANSLATORS: the first argument is the full name,
        # the second is the login.
        $keyring .= sprintf (_("GPG keys of %s <%s>"), $name, $user);
        $keyring .= "\n$key\n";
      }
    return $keyring;
  }

  function getUrl ($artifact)
  {
    # Similar to getArtifactUrl but refers to GroupType in case nothing
    # is special, not to Savane core tools.
    if ($this->data_array["url_$artifact"] != "")
      return $this->data_array["url_$artifact"];
    return $this->getTypeUrl ($artifact);
  }

  function fallback_URL ($artifact)
  {
    return "{$GLOBALS['sys_home']}$artifact/?group=" . $this->getUnixName();
  }

  function getArtifactUrl ($artifact)
  {
    # There is a drawback here: if the value in the database is filled
    # while the Group Type no longer accept this value to be non-standard,
    # the value for the group will override the Group Type setting.
    # But it can be convenient in some situations and adding one more
    # test is not very exciting.
    # And it can be justified anyway to keep a value entered already.
    # The best would be the software to list conflicting cases to sysadmin.
    if (empty($this->data_array["url_$artifact"]))
      return $this->fallback_URL ($artifact);
    return $this->data_array["url_$artifact"];
  }

  function get_vcs_admin_url ($vcs)
  {
    if (!in_array ($vcs, ['cvs', 'git']))
      return null;
    return preg_replace (',.*/,', '$0admin/', $this->getArtifactUrl ($vcs));
  }

  function url_is_default ($artifact)
  {
    return
      $this->fallback_URL ($artifact) === $this->getArtifactUrl ($artifact);
  }

  function get_artifact_url ($artifact, $func = "", $file = "")
  {
    $ret = $this->getArtifactUrl ($artifact);
    if ($func !== "")
      $ret .= "&amp;func=$func";
    if ($file === "")
      return $ret;
    $pos = strrpos ($ret, "/");
    return substr ($ret, 0, $pos + 1) . $file . substr ($ret, $pos + 1);
  }

  function getDir ($artifact)
  {
    # Refers to GroupType if unset. These fields should not be often
    # set, it would defeat the purpose of group types.
    # As a matter of fact, this function will be rarely used, as
    # directories are backend specific.
    if ($this->data_array["dir_$artifact"] != "")
      return $this->data_array["dir_$artifact"];
    return $this->getTypeDir ($artifact);
  }

  function Uses ($artifact)
  {
    if (isset ($this->data_array["use_$artifact"])
        && $this->data_array["use_$artifact"] != '')
      return $this->data_array["use_$artifact"];
    return $this->CanUse ($artifact);
  }

  function UsesForHomepage ($artifact)
  {
    # Useful to determine whether the project is a specific artifact
    # to manage its homepage:
    #   - must use homepage
    #   - must be set as homepage SCM for the group type
    #   - the projet url must be empty or equal to the group setting
    return
      $this->Uses ("homepage")
      && $this->type_data_array['homepage_scm'] == $artifact
      && (
           $this->data_array['url_homepage'] == $this->getTypeUrl ('homepage')
           || $this->data_array['url_homepage'] == ""
         );
  }

  # Related to mail notification.
  function getNewBugAddress ()
  {
    return $this->data_array['new_bug_address'];
  }

  function getGNU ()
  {
    return $this->data_array['gnu'];
  }

  function getNewSupportAddress ()
  {
    return $this->data_array['new_support_address'];
  }

  function getNewTaskAddress()
  {
    return $this->data_array['new_task_address'];
  }

  function getHtmlCVS()
  {
    return $this->data_array['html_cvs'];
  }

  function getNewPatchAddress()
  {
    return $this->data_array['new_patch_address'];
  }


  # Boolean flags to determine whether or not to send
  # an email on every bug/patch/support update.
  function sendAllBugUpdates ()
  {
    return $this->data_array['send_all_bugs'];
  }

  function sendAllSupportUpdates ()
  {
    return $this->data_array['send_all_support'];
  }

  function sendAllTasksUpdates()
  {
    return $this->data_array['send_all_tasks'];
  }

  function sendAllPatchUpdates()
  {
    return $this->data_array['send_all_patches'];
  }
} # class Group extends savane_error

# Backward compatibiliy.
class Project extends Group
{

}

# Aliases:
# The object stuff do not allow to easily call a function depending on
# a variable. These aliases will just do that.
# (Non-object very convenient functions.)

function group_getname ($group_id = 0)
{
  $grp = project_get_object ($group_id);
  return $grp->getPublicName ();
}

function group_getunixname ($group_id)
{
  $grp = project_get_object ($group_id);
  return $grp->getUnixName ();
}

function group_getgnu ($group_id)
{
  $grp = project_get_object ($group_id);
  return $grp->getGNU ();
}

function group_get_result ($group_id = 0)
{
  $grp = project_get_object ($group_id);
  return $grp->getData ();
}

function group_getid ($group_name)
{
  $res =
    db_execute (
      "SELECT group_id FROM groups WHERE unix_group_name = ?", [$group_name]
    );
  if (db_numrows ($res) > 0)
    return db_result ($res, 0, 'group_id');
  return null;
}

function group_trimmed_array ($res, $suff)
{
  $a = db_fetch_array ($res);
  $ret = []; $len = strlen ($suff);
  foreach ($a as $k => $v)
    {
      if (is_int ($k))
        continue;
      $ret[substr ($k, 0, -$len)] = $v;
    }
  return $ret;
}

function group_null_perm ($art)
{
  if (!is_array ($art))
    return null;
  $ret = [];
  foreach ($art as $a)
    $ret[$a] = null;
  return $ret;
}

function group_get_perm_flags ($group_id, $artifact, $prefix = '')
{
  if (empty ($artifact))
    return group_null_perm ($artifact);
  $art = $artifact;
  if (is_scalar ($artifact))
    $art = [$art];
  $suff = "_{$prefix}flags";
  foreach ($art as $a)
    if (!preg_match ('/^[a-z]+$/', $a))
      util_die ('group_getpermissions: unvalid argument artifact');
  $fields = join ("$suff, ", $art) . $suff;
  $res = db_execute (
    "SELECT $fields FROM groups_default_permissions WHERE group_id = ?",
    [$group_id]
  );
  if (!db_numrows ($res))
    return group_null_perm ($artifact);
  if (is_scalar ($artifact))
    return db_result ($res, 0, "$artifact$suff");
  return group_trimmed_array ($res, $suff);
}

function group_getpermissions ($group_id, $artifact)
{
  return group_get_perm_flags ($group_id, $artifact);
}

function group_flag_value ($flag, $event)
{
  if ($flag === null)
    return $flag;

  if ($event == TRACKER_EVENT_NEW_ITEM)
    $flag = $flag % TRACKER_FLAG_FACTOR;
  if ($event == TRACKER_EVENT_COMMENT)
    $flag = (int)($flag / TRACKER_FLAG_FACTOR);
  # We really want group restrictions here, not group type ones if missing.
  return $flag;
}

function group_getrestrictions (
  $group_id, $artifact, $event = TRACKER_EVENT_NEW_ITEM
)
{
  $flag = group_get_perm_flags ($group_id, $artifact, 'r');
  if ($flag === null)
    return group_null_perm ($artifact);
  $f = $flag;
  if (is_scalar ($f))
    $f = [$f];
  $ret = [];
  foreach ($f as $k => $v)
    $ret[$k] = group_flag_value ($v, $event);
  if (is_scalar ($flag))
    return $ret[0];
  return $ret;
}

function group_restriction_flag ($group_id, $artifact, $event)
{
  $flag = group_getrestrictions ($group_id, $artifact, $event);
  if ($flag)
    return $flag;
  if ($event == TRACKER_EVENT_COMMENT)
    # No restriction defined for comment, check the restriction for new items.
    $flag = group_getrestrictions ($group_id, $artifact);
  if ($flag)
    return $flag;

  # No restriction set for the group: use group type default.
  return group_gettyperestrictions ($group_id, $artifact);
}

function group_getrestrictions_explained (
  $group_id, $artifact, $event = TRACKER_EVENT_NEW_ITEM
)
{
  switch (group_restriction_flag ($group_id, $artifact, $event))
    {
    case TRACKER_PERM_ANON:
      return _("It is possible to post anonymously on this tracker.");
    case TRACKER_PERM_MEMBER:
      return _("Group membership is required to post on this tracker.");
    case TRACKER_PERM_USER:
      return _("Being logged-in is required to post on this tracker.");
    }
  return _("Posting on this tracker is disabled.");
}

function group_gettypepermissions ($group_id, $flags)
{
  $grp = project_get_object ($group_id);
  return $grp->getTypePermissions ($flags);
}

function group_gettyperestrictions ($group_id, $flags)
{
  $grp = project_get_object ($group_id);
  return $grp->getTypeRestrictions ($flags);
}

function group_restrictions_check (
  $group_id, $artifact, $event = TRACKER_EVENT_NEW_ITEM
)
{
  if (user_is_super_user ()) # No restriction for superusers.
    return true;

  $check_functions = [
    TRACKER_PERM_ANON => function ($group_id) { return true; },
    TRACKER_PERM_USER => function ($group_id) { return user_isloggedin (); },
    TRACKER_PERM_MEMBER => function ($gid) { return member_check (0, $gid); }
  ];
  $flag = group_restriction_flag ($group_id, $artifact, $event);
  if (array_key_exists ($flag, $check_functions))
    return $check_functions[$flag] ($group_id);
  # $flag should be TRACKER_PERM_NOBODY here.
  return false;
}

function group_get_history ($group_id = false)
{
  return db_execute ("
    SELECT
      group_history.field_name, group_history.old_value, group_history.date,
      user.user_name
    FROM group_history, user
    WHERE group_history.mod_by = user.user_id AND group_id = ?
    ORDER BY group_history.date DESC",
    [$group_id]
  );
}

# Handle the insertion of history for these parameters.
function group_add_history ($field_name, $old_value, $group_id)
{
  return db_autoexecute (
   'group_history',
    [
      'group_id' => $group_id, 'field_name' => $field_name,
      'old_value' => $old_value, 'mod_by' => user_getid (), 'date' => time ()
    ],
    DB_AUTOQUERY_INSERT
  );
}

# Return the standard URL for an artifact.
function group_get_artifact_url ($artifact, $hostname = 1)
{
  global $project, $sys_home;
  $type_urls = [
    "homepage", "download", "cvs_viewcvs", "cvs_viewcvs_homepage",
    "arch_viewcvs", "svn_viewcvs", "git_viewcvs", "hg_viewcvs", "bzr_viewcvs"
  ];
  if (in_array ($artifact, $type_urls))
    return $project->getTypeUrl ($artifact);

  if (!$hostname)
    return "{$sys_home}$artifact/?group=" . $project->getUnixName ();
  if ($project->getTypeBaseHost ())
    $host = $project->getTypeBaseHost ();
  else
    $host = $_SERVER['HTTP_HOST'];

  return "http://$host{$sys_home}$artifact/?group=" . $project->getUnixName ();
}

# Normalize preference names before feeding it to SQL.
# $name is either a strig or an array listing names.
function group_normalize_pref_name (&$name)
{
  $norm_name = function (&$n, $idx = null, $arg = null)
  {
    $n = strtolower (trim ($n));
  };
  array_walk ($name, $norm_name);
}

# Make sure that the value is an array; used in further functions.
function group_make_array ($x)
{
  if (is_array ($x))
    return $x;
  return [$x];
}

# Return group preferences. If $preference_names is a string,
# return a single string or false if the preference isn't set;
# if $preference_names is an array, return an array of $name => $value,
# where $value is null when the prefererence isn't set.
function group_get_preference ($group_id, $preference_names)
{
  $pref_names = group_make_array ($preference_names);
  group_normalize_pref_name ($pref_names);
  $pref_arr = [];
  $val_arr = [];
  foreach ($pref_names as $name)
    {
      if (array_key_exists ($name, $val_arr))
        continue;
      $pref_arr[] = $name;
      $val_arr[$name] = null;
    }

  $arg_list = utils_placeholders ($pref_arr);
  $result =
    db_execute ("
      SELECT preference_name,preference_value FROM group_preferences
      WHERE group_id = ? AND preference_name IN ($arg_list)",
      array_merge ([$group_id], $pref_arr)
    );
  if (!is_array ($preference_names))
    {
      if (db_numrows ($result))
        return db_result ($result, 0, 'preference_value');
     return false;
    }

  while ($row = db_fetch_array ())
    $val_arr[$row['preference_name']] = $row['preference_value'];
  return $val_arr;
}

function group_set_pref_insert_sql ($group_id, $to_insert)
{
  if (empty ($to_insert))
    return '';
  # The order of arguments must be the same as
  # in group_set_pref_update_sql ().
  $sql =
    "INSERT INTO group_preferences
       (preference_value, group_id, preference_name)
       VALUES\n";
  $sql .= utils_str_join (",\n", "(?, ?, ?)", count ($to_insert));
  # Add trailing ';' so that the subsequent query could be added directly.
  return $sql . ";\n";
}

function group_set_pref_update_sql ($group_id, $to_update)
{
  if (empty ($to_update))
    return '';
  # The order of arguments must be the same as
  # in group_set_pref_insert_sql ().
  $single_query = "
    UPDATE group_preferences SET preference_value = ?
    WHERE group_id = ? AND preference_name = ?";
  $sql = utils_str_join (";", $single_query, count ($to_update));
  return $sql;
}

# Return argument array matching
# group_set_pref_insert_sql () and group_set_pref_update_sql ().
function group_set_pref_array ($group_id, $prefs, $names)
{
  if (empty ($names))
    return [];
  $ret = [];
  foreach ($names as $name)
    {
      $ret[] = $prefs[$name]; $ret[] = $group_id; $ret[] = $name;
    }
  return $ret;
}

function group_set_preference ($group_id, $preference_name, $value)
{
  if (!user_ismember ($group_id, 'A'))
    return false;

  $pref_names = group_make_array ($preference_name);
  $pref_vals = group_make_array ($value);
  if (count ($pref_names) != count ($pref_vals))
    return false;
  $prefs = array_combine ($pref_names, $pref_vals);

  group_normalize_pref_name ($pref_names);
  $current = group_get_preference ($group_id, $pref_names);
  $to_update = $to_insert = [];
  foreach ($pref_names as $n)
    {
      if ($current[$n] === null)
        $to_insert[] = $n;
      elseif ($current[$n] != $prefs[$n])
        $to_update[] = $n;
    }
  $insert_sql = group_set_pref_insert_sql ($group_id, $to_insert);
  $insert_arr = group_set_pref_array ($group_id, $prefs, $to_insert);
  $update_sql = group_set_pref_update_sql ($group_id, $to_update);
  $update_arr = group_set_pref_array ($group_id, $prefs, $to_update);
  $sql = $insert_sql . $update_sql;
  if (empty ($sql))
    return true;
  $arr = array_merge ($insert_arr, $update_arr);
  db_execute ($sql, $arr, true);
  return true;
}
?>
