<?php
# Modify user permissions, including squads and defaults.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Free Software Foundation, Inc.
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
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

require_once ('../../include/init.php');
session_require (['group' => $group_id, 'admin_flags' => 'A']);

# Internal function to determine if squad's permissions override user's
# permissions.
# * If the user has lower permissions, we override, we assume that the user
#   has been added to obtain at least the permissions given to the squad.
# * If the user has higher permissions, the user may be a member of a squad
#   that provides some permissions to its members, but the user has
#   other duties requiring in some cases more permissions.
# Return true in the first item when squad permissions will be used.
# The second item is the actual value.
function effective_permissions ($squad_perm, $user_perm)
{
  # We have to do some subtle comparisons because, unfortunately, perms
  # have a long history and are not completely consistent.
  #   'NULL': group default
  #   9: none
  #   1: technician
  #   3: manager
  #   2: technician & manager

  # If perms are equal, don't bother checking further.
  if ($squad_perm == $user_perm)
    return [false, $user_perm];

  # If squad perm is 9 (none) or default, keep user's perm.
  if ($squad_perm == "9" || $squad_perm == 'NULL')
    return [false, $user_perm];

  if ($squad_perm == "2") # Highest possible, therefore higher than $user_perm.
    return [true, $squad_perm];

  # If user's perm is 9 (none), take squad perm.
  if ($user_perm == "9")
    return [true, $squad_perm];

  # If user's perm and squad perm are 1 (technician) and  3 (manager), assume
  # that the user should be both technician and manager.
  if (($user_perm == "1" && $squad_perm == "3")
      || ($squad_perm == "1" && $user_perm == "3"))
    return [true, "2"];
  # Default to the user's perm.
  return [false, $user_perm];
}

function merge_user_squad_permissions ($user_id, $row, &$perms, &$sq_perms)
{
  global $trackers, $user_list;
  $squad_id = $row['squad_id']; $squad_perm_used = false;
  foreach ($trackers as $flag)
    {
      $perm = $sq_perms[$squad_id . $flag];
      list ($used, $perms[$flag]) =
        effective_permissions ($perm, $perms[$flag]);
      $squad_perm_used = $squad_perm_used || $used;
    }

  if ($sq_perms[$squad_id . 'privacy'] > $perms['privacy'])
    {
      $squad_perm_used = true;
      $perms['privacy'] = $sq_perms[$squad_id . 'privacy'];
    }
  if (!$squad_perm_used)
    return '';
  # TRANSLATORS: the argument is user's name.
  return sprintf (
    _("Personal permissions of %s were overridden "
      . "by squad permissions"), $user_list[$user_id]['user_name']
  ) . "\n";
}

function find_out_user_permissions ($user_id, $group_id, &$perms, &$sq_perms)
{
  $result = db_execute (
    "SELECT squad_id FROM user_squad WHERE user_id = ? AND group_id = ?",
    [$user_id, $group_id]
  );
  if (!db_numrows ($result))
    return '';
  $ret = '';
  while ($row = db_fetch_array ($result))
    $ret .= merge_user_squad_permissions ($user_id, $row, $perms, $sq_perms);
  return $ret;
}

function report_update_failed ($result, $is_squad, $name)
{
  if ($result)
    return null;
  if ($is_squad)
    return sprintf (_('Unable to change squad %s permissions'), $name) . "\n";
  return sprintf (_('Unable to change user %s permissions'), $name) . "\n";
}

function update_member_permissions (
  &$fld_val, $uid, $is_squad, $name, $group_id
)
{
  $result = db_autoexecute ('user_group', $fld_val, DB_AUTOQUERY_UPDATE,
    "user_id = ? AND group_id = ?", [$uid, $group_id]
  );
  if (null !== ($ret = report_update_failed ($result, $is_squad, $name)))
    return $ret;
  if ($is_squad)
    {
      $string = 'Changed Squad Permissions';
      $msg = sprintf (_('Changed Squad %s Permissions'), $name);
    }
  else
    {
      $string = 'Changed User Permissions';
      $msg = sprintf (_('Changed User %s Permissions'), $name);
    }
  group_add_history ($string, $name, $group_id);
  return "$msg\n";
}

function no_member_changes ($uid, &$fields, $is_squad)
{
  $lst = $is_squad? 'squad_list': 'user_list';
  foreach ($fields as $k => $v)
    {
      if ($v === 'NULL')
        $v = null;
      if (empty ($v) && empty ($GLOBALS[$lst][$uid][$k]))
        continue;
      if ($GLOBALS[$lst][$uid][$k] != $v)
        return false;
    }
  return true;
}

function compile_member_fields_values ($permissions)
{
  global $trackers;
  $fields['admin_flags'] = $permissions['admin'];
  $fields['onduty'] = $permissions['onduty'] !== null;
  foreach (array_merge ($trackers, ['privacy']) as $k)
    $fields[$k . '_flags'] =
      $permissions[$k] === 'NULL'? null: $permissions[$k];
  return $fields;
}

function import_member_permissions ($row)
{
  global $trackers, $perm_regexp;
  $uid = $row['user_id']; $ret = $names = [];
  foreach ($trackers as $flag)
    $names[] = "{$flag}_user_$uid";
  $names[] = $perm_regexp;
  $checkbox_names = ["onduty_user_$uid"];
  $checkbox_names[] = "privacy_user_$uid";

  $flags = [MEMBER_FLAGS_ADMIN, MEMBER_FLAGS_SQUAD, MEMBER_FLAGS_PENDING];
  $imported = sane_import ('post',
    [
      'preg' => [$names], 'true' => $checkbox_names,
      'strings' =>  [["admin_user_$uid", $flags]],
    ]
  );
  $len = strlen ("_user_$uid");
  foreach ($imported as $k => $v)
    $ret [substr ($k, 0, -$len)] = $v;
  return $ret;
}

function fetch_user_list  ($group_id)
{
  global $user_list, $squad_list;
  $flags = [MEMBER_FLAGS_PENDING, MEMBER_FLAGS_SQUAD];
  $cond = "NOT g.admin_flags " . utils_in_placeholders ($flags);
  $res = query_members ($group_id, $cond, $flags);
  $user_list = [];
  while ($row = db_fetch_array ($res))
    $user_list[$row['user_id']] = $row;
  $res = query_members ($group_id, "g.admin_flags = ?", [MEMBER_FLAGS_SQUAD]);
  $squad_list = [];
  while ($row = db_fetch_array ($res))
    $squad_list[$row['user_id']] = $row;
}

function fetch_group_data ($group_id)
{
  global $group_permissions, $group_post_restrictions,
    $group_comment_restrictions, $trackers;
  $group_permissions = group_getpermissions ($group_id, $trackers);
  $group_post_restrictions = group_getrestrictions ($group_id, $trackers);
  $group_comment_restrictions = group_getrestrictions ($group_id, $trackers, 2);
}

function sanitize_permissions ($uid, $is_squad)
{
  global $permissions;
  # Admins are not allowed to turn off their own admin flag.
  # It is too dangerous---set it back to 'A'.
  if (user_getid () == $uid && !user_is_super_user ())
    $permissions['admin'] = MEMBER_FLAGS_ADMIN;
  # Squads flag cannot be changed, squads should not be turned into normal
  # users.
  if ($is_squad)
    $permissions['admin'] = MEMBER_FLAGS_SQUAD;
  if (empty ($permissions['admin']))
    $permissions['admin'] = MEMBER_FLAGS_MEMBER;

  # Admins have the access to the private items.
  if ($permissions['admin'] == MEMBER_FLAGS_ADMIN)
    $permissions['privacy'] = '1';
}

function change_member ($row_dev, &$permissions, &$squad_permissions)
{
  global $group_id, $feedback_squad_override, $feedback_able, $squad_flags;
  $uid = $row_dev['user_id']; $name = $row_dev['user_name'];
  $is_squad = $row_dev['admin_flags'] == MEMBER_FLAGS_SQUAD;
  $permissions = import_member_permissions ($row_dev);
  sanitize_permissions ($uid, $is_squad);

  if ($is_squad)
    foreach ($squad_flags as $flag)
      $squad_permissions[$uid . $flag] = $permissions[$flag];
  else
    $feedback_squad_override .= find_out_user_permissions (
      $uid, $group_id, $permissions, $squad_permissions
    );
  $fld_val = compile_member_fields_values ($permissions);
  if (no_member_changes ($uid, $fld_val, $is_squad))
    return '';
  $feedback_able .= update_member_permissions (
    $fld_val, $uid, $is_squad, $name, $group_id
  );
}

function push_member_feedback ()
{
  global $feedback_able, $feedback_squad_override, $feedback_unable;
  if ($feedback_able)
    fb ($feedback_able);
  if ($feedback_squad_override)
    fb ($feedback_squad_override);
  if ($feedback_unable)
    fb ($feedback_unable, 1);
}

# Make sure that default permissions for the group exist.
function assert_default_permissions ($group_id)
{
  $res = db_execute (
    "SELECT * FROM groups_default_permissions WHERE group_id = ?", [$group_id]
  );
  if (!db_numrows ($res))
    db_execute (
      "INSERT INTO groups_default_permissions (group_id) VALUES (?)",
      [$group_id]
    );
}

function import_group_defaults ()
{
  global $trackers;
  $names = [];
  foreach ($trackers as $art)
    $names[] = "{$art}_user_";
  $names[] = $GLOBALS['perm_regexp'];
  return sane_import ('post', ['preg' => [$names]]);
}

function complile_group_default_values ()
{
  global $trackers;
  $fields = [];
  foreach ($trackers as $art)
    {
      $var = $art . '_user_';
      $fields[$art . '_flags'] = $GLOBALS[$var];
    }
  return $fields;
}

# Build reference list of fields from data fetched from the database
# to compare against the values submitted by the user.
function group_field_reference ()
{
  global $group_permissions, $group_post_restrictions,
    $group_comment_restrictions;
  $reference = [];
  foreach ($group_permissions as $k => $v)
    $reference["{$k}_flags"] = $v;
  foreach ($group_post_restrictions as $k => $v)
    $reference["{$k}_rflags"] = $v;
  foreach ($group_comment_restrictions as $k => $v)
    {
      $k1 = "{$k}_rflags";
      if (!array_key_exists ($k1, $reference))
        $reference[$k1] = 0;
      $reference[$k1] += $v * 100;
    }
  return $reference;
}

function no_group_changes (&$fields, &$reference)
{
  $f = $fields;
  foreach ($f as $k => $v)
    if ($v === 'NULL')
      $fields[$k] = 0;
  foreach ($fields as $k => $v)
    {
      if (!array_key_exists ($k, $reference))
        $reference[$k] = 0;
      if ($v != $reference[$k])
        return false;
    }
  return true;
}

function update_group (&$fields, $group_id, $titles)
{
  $reference = group_field_reference ();
  if (no_group_changes ($fields, $reference))
    return;
  $result = db_autoexecute ('groups_default_permissions',
    $fields, DB_AUTOQUERY_UPDATE, "group_id = ?", [$group_id]
  );
  if (!$result)
    {
      fb ($titles[0], 1);
      return;
    }
  group_add_history ($titles[1], '', $group_id);
  fb ($titles[2]);
}

function update_group_defaults (&$fields, $group_id)
{
  update_group ($fields, $group_id, [
      _("Unable to change group default permissions."),
      'Changed Group Default Permissions',
      _("Permissions for the group updated.")
    ]
  );
}

function import_posting_restrictions ()
{
  global $trackers;
  $names = [];
  foreach ($trackers as $art)
    foreach ([1, 2] as $ev_no)
      $names[] = "{$art}_restrict_event$ev_no";
  $names[] = $GLOBALS['perm_regexp'];
  return sane_import ('post', ['preg' => [$names]]);
}

function compile_posting_restrictions ()
{
  global $trackers;
  foreach ($trackers as $art)
    {
      $flags = $art . '_flags';
      $ev1 = $art . '_restrict_event1'; $ev2 = $art . '_restrict_event2';
      $GLOBALS[$flags] = intval ($GLOBALS[$ev2]) * 100
        + intval ($GLOBALS[$ev1]);
      if (!$GLOBALS[$flags])
        $GLOBALS[$flags] = 'NULL';
    }
  $fields = [];
  foreach ($trackers as $art)
    {
      $var = $GLOBALS[$art . '_flags'];
      $fields[$art . '_rflags'] = $var === 'NULL'? 0: $var;
    }
  return $fields;
}

function update_posting_restrictions ($fields, $group_id)
{
  update_group ($fields, $group_id, [
      _("Unable to change posting restrictions."),
      'Changed Posting Restrictions',
      _("Posting restrictions updated.")
    ]
  );
}

function finish_page ()
{
  site_project_footer ([]);
  exit (0);
}

function append_tracker_titles (&$titles, $project)
{
  global $tracker_titles;
  foreach ($tracker_titles as $art => $title)
    $titles[] = $title;
}

function query_members ($group_id, $cond, $arg)
{
  global $trackers;
  $tr = '';
  foreach ($trackers as $k)
    $tr .= " g.{$k}_flags,";
  return db_execute ("
    SELECT
      u.user_name, u.realname, u.user_id, g.admin_flags, g.onduty,
      g.onduty as onduty_flags,$tr g.privacy_flags
    FROM user u JOIN user_group g ON u.user_id = g.user_id
    WHERE g.group_id = ? AND $cond ORDER BY u.user_name",
    array_merge ([$group_id], $arg)
  );
}

function print_squad_list_title ($project, $titles)
{
  print '<p>'
    . _("Squad members will automatically obtain their squad permissions.")
    . "</p>\n";
  print html_build_list_table_top ($titles);
}

function print_squad_entry ($row, $project, $class)
{
  global $trackers;
  $uid = $row['user_id']; $name = $row['user_name'];
  print "<tr class=\"$class\">\n<td align=\"center\" id=\"$name\">"
    . utils_user_link ($name, $row['realname']) . "</td>\n";
  print "<td class='smaller'>\n";
  print form_checkbox ("privacy_user_$uid", $row['privacy_flags'] == '1',
    ['label' => _("Private Items")]
  );
  print "\n</td>\n";

  foreach ($trackers as $art)
    html_select_permission_box ($art, $row);
  print "</tr>\n";
}

function print_squad_rows ($project, $titles)
{
  global $squad_list;
  $reprinttitle = $i = 0;
  foreach ($squad_list as $row)
    {
      if (++$reprinttitle >= 9)
        {
          print html_build_list_table_top ($titles, 0, 0);
          $reprinttitle = 0;
        }
      print_squad_entry ($row, $project, utils_altrow (++$i));
   }
}

function list_squads ($group_id, $project)
{
  global $squad_list;
  print html_h (2, _("Permissions per squad"));
  if (empty ($squad_list))
    {
      print '<p class="warn">' . _("No Squads Found") . "</p>\n";
      return;
    }
  $titles = [_("Squad"), _("General Permissions")];
  append_tracker_titles ($titles, $project);
  print_squad_list_title ($project, $titles);
  print_squad_rows ($project, $titles);

  print "</table>\n"
    . "<p class='center'>" . form_submit (_("Update Permissions")) . "</p>\n";
}

function print_member_list_title ($titles)
{
  print '<p class="warn">';
  print _("Group admins are always allowed to read private items.");
  print "</p>\n";
  print html_build_list_table_top ($titles);
}

function print_member_rank ($row)
{
  $uid = $row['user_id']; $admin = $row['admin_flags'];
  print '<td class="smaller">';
  if ($uid == user_getid () && !user_is_super_user ())
    print '<em>' . _("You are Admin") . '</em>';
  else
    print form_checkbox ("admin_user_$uid", $admin == 'A',
      ['value' => 'A', 'label' => _("Admin")]
    );
  if ($admin != 'A')
   {
     print "<br />\n";
     print form_checkbox ("privacy_user_$uid", $row['privacy_flags'] == '1',
       ['label' => _("Private Items")]
     );
   }
  else
    print form_hidden (["privacy_user_$uid" => 1]);
  print "</td>\n";
}

function print_member_entry ($row, $project, $class)
{
  global $trackers;
  $uid = $row['user_id']; $name = $row['user_name'];
  print " <tr class=\"$class\">\n" . "<td align='center' id=\"$name}\">"
    . utils_user_link ($name, $row['realname']) . "</td>\n";
  print_member_rank ($row);
  print '<td align="center">';
  print form_checkbox (
    "onduty_user_$uid", $row['onduty'] == '1', ['label' => _("On Duty")]
  );
  print "</td>\n";
  foreach ($trackers as $art)
    html_select_permission_box ($art, $row);
  print "</tr>\n";
}

function print_member_rows ($project, $titles)
{
  global $user_list;
  $reprinttitle = $i = 0;
  foreach ($user_list as $row)
    {
      if (++$reprinttitle == 9)
        {
          print html_build_list_table_top ($titles, 0, 0);
          $reprinttitle = 0;
        }
      print_member_entry ($row, $project, utils_altrow (++$i));
    }
}

function list_members ($group_id, $project)
{
  global $user_list;
  print html_h (2, _("Permissions per member"));
  if (empty ($user_list))
    {
      print '<p class="warn">' . _("No Members Found") . "</p>\n";
      # No point in changing permissions of an orphan group.
      finish_page ();
    }
  $titles = [_("Member"), _("General Permissions"), _("On Duty")];
  append_tracker_titles ($titles, $project);
  print_member_list_title ($titles);
  print_member_rows ($project, $titles);
  print "</table>\n";
}

function print_group_posting_title ($project)
{
  print html_h (2, _("Group trackers posting restrictions"));
  $titles = [
    # TRANSLATORS: this is the header for a column with two rows,
    # "Posting new items" and "Posting comments".
    _("Applies when ...")
  ];
  append_tracker_titles ($titles, $project);

  print '<p>' . _("Here you can set the minimal authentication level required "
    . "in order to\npost on the trackers.");
  print "</p>\n";
  if (!empty ($GLOBALS['trackers']))
    print html_build_list_table_top ($titles);
}

function print_group_post_restrictions ($i)
{
  global $trackers, $group_post_restrictions;
  print "\n<tr class=\"" . utils_altrow ($i) . "\">\n";
  # TRANSLATORS: this is a column row whose header says "Applies when ...".
  print "<td>" . _("Posting new items") . "</td>\n";

  foreach ($trackers as $art)
    html_select_restriction_box (
      $art, $group_post_restrictions[$art], 'group'
    );
  print "</tr>\n";
}

function print_group_comment_restrictions ($i)
{
  global $trackers, $group_comment_restrictions;
  print '<tr class="' . utils_altrow (++$i) . "\">\n";
  # TRANSLATORS: this is a column row whose header says "Applies when ...".
  print "<td>" . _("Posting comments") . "</td>\n";

  foreach ($trackers as $art)
    if ($art != 'news')
       html_select_restriction_box (
         $art, $group_comment_restrictions[$art], '', '', 2
       );
    else # No comments in news.
      print '<td align="center">---</td>';
  print "</tr>\n";
}

function print_group_posting_defaults ($group_id, $project)
{
  global $trackers, $titles;
  print_group_posting_title ($project);
  if (empty ($trackers))
    return;
  $i = 0;
  print_group_post_restrictions (++$i);
  print_group_comment_restrictions (++$i);
  print "</table>\n";
  print "<p class='center'>" . form_submit (_("Update Permissions")) . "</p>\n";
}

function print_member_defaults ($group_id, $project)
{
  global $trackers, $group_permissions;
  print html_h (2, _("Group Default Permissions"));
  $titles = [];
  append_tracker_titles ($titles, $project);
  member_explain_roles ();
  print html_build_list_table_top ($titles);
  print "<tr>\n";
  foreach ($trackers as $art)
    html_select_permission_box ($art, $group_permissions[$art], 'group');
  print "</tr>\n</table>\n<p class='center'>"
    . form_submit (_("Update Permissions")) . "</p>\n";
}

function init_data ()
{
  global $trackers, $tracker_titles, $project, $squad_flags, $group_id;
  $all_trackers = [
    'support' => _("Support Tracker"), 'bugs' => _("Bug Tracker"),
    'task' => _("Task Tracker"), 'patch' => _("Patch Tracker"),
    'cookbook' => _("Cookbook Manager"), 'news' => _("News Manager")
  ];
  $GLOBALS['perm_regexp'] = '/^(\d+|NULL)$/';
  $project = project_get_object ($group_id);
  $tracker_titles = [];
  foreach ($all_trackers as $k => $v)
    if ($k == 'cookbook' || $project->Uses ($k))
      $tracker_titles[$k] = $v;
  $trackers = array_keys ($tracker_titles);
  fetch_user_list ($group_id);
  fetch_group_data ($group_id);
  $squad_flags = array_merge ($trackers, ['privacy', 'admin']);
}

init_data ();

extract (sane_import ('post', ['true' => 'update']));
form_check ('update');
if ($update)
  {
    $feedback_able = $feedback_unable = $feedback_squad_override = '';
    $squad_permissions = [];
    foreach (array_merge ($squad_list, $user_list) as $row_dev)
      change_member ($row_dev, $permissions, $squad_permissions);
    push_member_feedback ();

    extract (import_group_defaults ());
    assert_default_permissions ($group_id);
    $fields = complile_group_default_values ();
    update_group_defaults ($fields, $group_id);

    extract (import_posting_restrictions ());
    $fields = compile_posting_restrictions ();
    update_posting_restrictions ($fields, $group_id);

    fetch_user_list ($group_id);
    group_get_default_permissions ($group_id, true);
    fetch_group_data ($group_id);
  } # if ($update)

site_project_header (
  ['title' => _("Set Permissions"), 'group' => $group_id, 'context' => 'ahome']
);

print form_header () . form_hidden (["group" => $group]);
print_group_posting_defaults ($group_id, $project);
print_member_defaults ($group_id, $project);
list_squads ($group_id, $project);
list_members ($group_id, $project);
print form_footer (_("Update Permissions"));
finish_page ();
?>
