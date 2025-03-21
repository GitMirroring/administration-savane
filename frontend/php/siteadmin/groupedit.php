<?php
# Edit one group as superuser.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2008 Aleix Conchillo Flaque
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

foreach (['init', 'vars', 'form', 'html', 'project/admin'] as $i)
  require_once ("../include/$i.php");

session_require (['group' => SESSION_ADMIN_GROUP]);

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.

$submit_buttons = ['update', 'fastok', 'fastdel'];
$post_names = function ()
{
  global $submit_buttons;
  $names = [
    'true' => $submit_buttons,
    'name' => 'form_name',
    'digits' => ['group_type', 'form_public'],
    'specialchars' => ['form_license', 'form_license_other'],
    'strings' => [['form_status', ['A', 'D', 'P']]]
  ];
  return $names;
};

if (empty ($group_id))
  exit_no_group ();

if (project_get_object ($group_id)->getStatus () == 'X')
  {
    site_admin_header (
      ['title' => no_i18n ("Group List"), 'context' => 'admgroup']
    );
    print "<p>"
      . no_i18n ("This is a special group.  No controls are provided.")
      . "</p>\n";
    site_admin_footer ([]);
    exit (0);
  }

extract (sane_import ('post', $post_names ()));
form_check ($submit_buttons);

if (!form_vars_empty ($submit_buttons))
  {
    # Assign gidNumber before updating the status in order to
    # avoid the race condition with backend scripts.
    if ($form_status === 'A')
      group_assign_gidNumber ($group_id);
    # Full details update.
    if ($update)
      {
        $res = db_execute (
          "SELECT * FROM groups WHERE group_id = ?", [$group_id]
        );
        $row = db_fetch_array ($res);
        if (empty ($form_status))
          $form_status = $row['status'];

        if ($row['status'] != $form_status)
          group_add_history ('status', $row['status'], $group_id);
        if ($row['is_public'] != $form_public)
          group_add_history ('is_public', $row['is_public'], $group_id);
        if ($row['type'] != $group_type)
          group_add_history ('type', $row['type'], $group_id);
        if ($row['unix_group_name'] != $form_name)
          group_add_history (
            'unix_group_name', $row['unix_group_name'], $group_id
          );
        db_autoexecute ('groups',
          [ 'is_public' => $form_public, 'status' => $form_status,
            'license' => $form_license, 'license_other' => $form_license_other,
            'type' => $group_type, 'unix_group_name' => $form_name,
            'dir_cvs' => '', 'dir_arch' => '', 'dir_svn' => '',
            'dir_git' => '', 'dir_hg' => '', 'dir_bzr' => '',
            'dir_homepage' => '', 'dir_download' => ''],
          DB_AUTOQUERY_UPDATE, "group_id = ?", [$group_id]
        );
      } # $update
    if ($fastok || $fastdel)
      db_execute ("UPDATE groups SET status = ? WHERE group_id = ?",
        [$form_status, $group_id]
      );
    fb (no_i18n ("Updating group info"));
  }
# Get current information.
$res_grp = db_execute ("SELECT * FROM groups WHERE group_id = ?", [$group_id]);

site_admin_header (
  ['title' => no_i18n ("Group List"), 'context' => 'admgroup']
);

if (!db_numrows ($res_grp))
  {
    fb (no_i18n ("Invalid Group: Invalid group was passed in."), 1);
    site_admin_footer ([]);
    exit;
  }

$row_grp = db_fetch_array ($res_grp);

utils_get_content ("admin/groupedit_intro");

print '<p>';
print "<a href='../projects/{$row_grp['unix_group_name']}'>"
  . no_i18n ("Group main page") . "</a>";
print '</p>
';

function print_updatefast ($status, $image, $label, $name)
{
  global $group_id;
  print form_tag ();
  print form_hidden ([
    'form_status' => $status, 'group_id' => $group_id, $name => 1
  ]);
  # The variables from <input type="image" /> are ignored, the previously
  # defined <input type="hidden" /> is used instead.
  print form_image ($image, $label, "submit_$name");
  print "\n</form>\n<br />\n";
}

print html_h (2, no_i18n ("Registration Management Shortcuts"));
print_updatefast ('A', 'bool/ok.orig.png', no_i18n ("Approve"), 'fastok');
$res = db_execute (
  "SELECT COUNT(group_list_id) AS cnt FROM mail_group_list WHERE group_id = ?",
  [$group_id]
);
$no_lists = true;
$list_row = db_fetch_array ($res);
if (!empty ($list_row))
  $no_lists = $list_row['cnt'] < 1;
if ($no_lists)
  print_updatefast ('D', 'bool/wrong.orig.png', no_i18n ("Discard"), 'fastdel');
else
  {
    $msg = sprintf (
      no_i18n ("This group has associated mailing lists;\n"
        . "<a href='%s'>unlink or remove them</a>\n"
        . "before discarding the group."),
      "{$sys_home}mail/admin/?group={$row_grp['unix_group_name']}"
    );
    print "<br />\n$msg<br />\n";
  }
print form_tag ();
print html_h (2, no_i18n ("Detailed Interface"));
$HTML->box1_top (no_i18n ("General Settings"));

print '<p><span class="preinput">' . no_i18n ("Group Type:")
  . " </span><br />\n";
print '<em>';
utils_get_content ("admin/groupedit_grouptype");
print "</em><br />\n";
print show_group_type_box ('group_type', $row_grp['type']);

function next_altrow ()
{
  global $i;
  print "</td>\n</tr>\n<tr><td class=\"" . utils_altrow ($i++) . '">';
}
$i = 0;
next_altrow ();
print '<p><span class="preinput"><label for="form_name">'
  . no_i18n ("System Name:") . "</label></span><br />\n";
print '<input type="text" name="form_name" id="form_name" value="'
  . $row_grp['unix_group_name'] . '" />';

next_altrow ();

print '<p><span class="preinput"><label for="form_status">'
  . no_i18n ("Status:") . "</label></span><br />\n";

print "<select name='form_status' id='form_status'> ";
foreach (
  [
    'A' => no_i18n ("Active"), 'P' => no_i18n ("Pending"),
    'D' => no_i18n ("Deleted"),
  ] as $k => $v
)
  print form_option ($k, $row_grp['status'], $v);
print "</select>\n";
print '<p class="warn">'
  . no_i18n (
     "On group approval, do not forget to run the script &ldquo;Trigger group "
     . "creation&rdquo; at the end of this page, otherwise this group could "
     . "end up partly misconfigured."
    )
  . "</p>\n";
print '<p>'
  . no_i18n (
      "Group marked as deleted will be removed from the database "
      . "by a cronjob.")
  . "</p>\n";

next_altrow ();
print '<p><span class="preinput"><label for="form_public">'
  . no_i18n ("Public?") . "</label></span><br />\n"
  . no_i18n (
      "A private group will be completely invisible from the web interface."
   )
  . "\n"
  . no_i18n (
      "You must clear the HTML repository field below when setting the "
      . "private flag otherwise unpredictable result will occur.")
  . "<br />\n<select name='form_public' id='form_public'>\n";
print form_option ('1', $row_grp['is_public'], no_i18n ("Yes"));
print form_option ('0', $row_grp['is_public'], no_i18n ("No"));
print "</select>\n";

next_altrow ();

print '<p><span class="preinput"><label for="form_license">'
  . no_i18n("License:") . "</label></span><br />\n";
print no_i18n (
  "Note: this has influence only if the group type of which this group "
  . "belongs to accepts this information."
);
print "<br />\n";
print "<select name='form_license' id='form_license'>\n";
print form_option ("none", null, no_i18n ("N/A"));
print form_option ("other", null, no_i18n ("Other license"));

foreach ($LICENSE_EN as $k => $v)
  print form_option ($k, $row_grp['license'], $v);
print '</select>
<br />
<label for="form_license_other">';
print no_i18n ("If other:") . "</label><br />\n";
print '<input type="text" name="form_license_other" id="form_license_other" '
  . 'value="' . $row_grp['license_other'] . '" />';
print "</p>\n";
print form_hidden (['group_id' => $group_id]);

next_altrow ();
print '<p><input type="submit" name="update" value="' . no_i18n ("Update")
  . '">';

$HTML->box1_bottom ();

print "<p>";
print form_tag (['action' => "triggercreation.php"]);
print form_hidden (['group_id' => $group_id]);
print form_submit (no_i18n (
  "Send new group instruction email and trigger group creation "
  . "(should be done only once)"
));
print "</p>\n";

$HTML->box1_top (no_i18n ("Submitted Information"));
project_admin_registration_info ($row_grp);

$HTML->box1_bottom ();
site_admin_footer ([]);
?>
