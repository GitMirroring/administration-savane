<?php
# Edit one group as superuser.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007, 2008  Sylvain Beucler
# Copyright (C) 2008  Aleix Conchillo Flaque
# Copyright (C) 2017, 2018, 2020, 2023 Ineiev
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

require_once ('../include/init.php');
require_once ('../include/init.php');
require_once ('../include/form.php');
require_once ('../include/html.php');
# Needed for group history.
require_directory ("project");

session_require (['group' => $sys_group_id, 'admin_flags' => 'A']);

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
function no_i18n ($string)
{
  return $string;
}

$post_names = function ()
{
  $names = [
    'true' => 'update',
    'name' => 'form_name',
    'digits' => ['group_type', 'form_public'],
    'specialchars' => ['form_license', 'form_license_other'],
    'preg' => [['form_status', '/^[A-Z]$/']]
  ];
  $dirs = ['cvs', 'arch', 'svn', 'git', 'hg', 'bzr', 'homepage', 'download'];
  foreach ($dirs as $d)
    $names['specialchars'][] = "form_dir_$d";
  return $names;
};

extract (sane_import ('post', $post_names ()));
extract (sane_import ('get',
  ['true' => 'updatefast', 'preg' => [['status', '/^[A-Z]$/']]]
));

if ($update || $updatefast)
  {
    # Full details update.
    if ($update)
      {
        $res_grp = db_execute (
          "SELECT * FROM groups WHERE group_id = ?", [$group_id]
        );
        $res_type = db_execute (
          "SELECT * FROM group_type WHERE type_id = ?", [$group_type]
        );

        if (db_result ($res_grp, 0, 'status') != $form_status)
          group_add_history (
           'status', db_result ($res_grp, 0, 'status'), $group_id
          );
        if (db_result ($res_grp, 0, 'is_public') != $form_public)
          group_add_history (
            'is_public', db_result ($res_grp, 0, 'is_public'), $group_id
          );
        if (db_result ($res_grp, 0, 'type') != $group_type)
          group_add_history (
            'type', db_result ($res_grp, 0, 'type'), $group_id
          );
        if (db_result ($res_grp, 0, 'unix_group_name') != $form_name)
          group_add_history (
            'unix_group_name', db_result ($res_grp, 0, 'unix_group_name'),
            $group_id
          );
        db_autoexecute ('groups',
          [ 'is_public' => $form_public, 'status' => $form_status,
            'license' => $form_license, 'license_other' => $form_license_other,
            'type' => $group_type, 'unix_group_name' => $form_name,
            'dir_cvs' => $form_dir_cvs, 'dir_arch' => $form_dir_arch,
            'dir_svn' => $form_dir_svn, 'dir_git' => $form_dir_git,
            'dir_hg' => $form_dir_hg, 'dir_bzr' => $form_dir_bzr,
            'dir_homepage' => $form_dir_homepage,
            'dir_download' => $form_dir_download],
          DB_AUTOQUERY_UPDATE, "group_id=?", [$group_id]
        );
      } # $update
    if ($updatefast)
      db_execute ("UPDATE groups SET status = ? WHERE group_id = ?",
        [$status, $group_id]
      );
    fb (no_i18n ("Updating group info"));
  }
# Get current information.
$res_grp = db_execute("SELECT * FROM groups WHERE group_id=?", array($group_id));

site_admin_header(array('title'=>no_i18n("Group List"),'context'=>'admgroup'));

if (db_numrows($res_grp) < 1)
  {
    fb(no_i18n("Invalid Group: Invalid group was passed in."));
    site_admin_footer(array());
    exit;
  }

$row_grp = db_fetch_array($res_grp);

utils_get_content("admin/groupedit_intro");

print '<p>';
print "<a href='../projects/{$row_grp['unix_group_name']}'>"
  . no_i18n ("Group public page") . "</a>";
print '</p>
';

print '<h2>' . no_i18n("Registration Management Shortcuts") . "</h2>\n";
print "<a href=\"$php_self?status=A&amp;updatefast=1&amp;group_id=$group_id\">"
  . html_image ("bool/ok.orig.png", ['alt' => no_i18n ("Approve")])
  . '</a>&nbsp;&nbsp;&nbsp;';
print "<a href=\"$php_self?status=D&amp;updatefast=1&amp;group_id=$group_id\">"
  . html_image ("bool/wrong.orig.png", ['alt' => no_i18n ("Discard")])
  . '</a>&nbsp;&nbsp;&nbsp;';
print "<a href=\"triggercreation.php?group_id=$group_id\">"
  . html_image (
      "contexts/preferences.orig.png",
      ['alt' => no_i18n ("Send new group instruction email and trigger "
                         . "group creation (should be done only once)")]
    )
  . '</a>';

print form_tag ();
print '<h2>' . no_i18n ("Detailed Interface") . "</h2>\n";
$HTML->box1_top (no_i18n ("General Settings"));

print '<p><span class="preinput">' . no_i18n ("Group Type:")
 .  " </span><br />\n";
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
  . no_i18n("System Name:") . "</label></span><br />\n";
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
    'M' => no_i18n ("Maintenance (accessible only to superuser)"),
    'I' => no_i18n ("Incomplete (failure during registration)"),
  ] as $k => $v
)
  {
    $sel = '';
    if ( $row_grp['status'] == $k)
      $sel = ' selected';
    print "<option$sel value='$k'>$v</option>\n";
  }
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
$sel = '';
if ($row_grp['is_public'] == 1)
$sel = ' selected';
print "<option$sel value='1'>" . no_i18n ("Yes") . "</option>\n";
if ($sel == '')
  $sel = ' selected';
else
  $sel = '';
print "<option$sel value='0'>" . no_i18n ("No") . "</option>\n";
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
print '<option value="none">' . no_i18n ("N/A") . "</option>\n";
print '<option value="other">' . no_i18n ("Other license") . "</option>\n";

foreach ($LICENSE_EN as $k => $v)
  {
    print "<OPTION value=\"$k\"";
    if ($k == $row_grp['license']) print " selected";
    print ">$v</option>\n";
  }
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

print "<p><a href='triggercreation.php?group_id=$group_id'>"
  . no_i18n (
      "Send new group instruction email and trigger group creation "
      . "(should be done only once)"
    )
  . "</a></p>\n";

$HTML->box1_top (no_i18n ("Submitted Information"));
project_admin_registration_info ($row_grp);
$HTML->box1_bottom ();

print '<p>';
$HTML->box1_top  (no_i18n("Specific Backend Settings"));
print no_i18n (
  "[BACKEND SPECIFIC] If this group must have specific directories\n"
  . "for homepage, sourcecode, download, which are not the default\n"
  . "of the group type it belongs to, you can fill in the following\n"
  . "fields.  You may need to also edit the urls in &ldquo;This group\n"
  . "active features.&rdquo; If possible, you should avoid using these\n"
  . "fields and consider creating new group types.  Exceptions are a pain\n"
  . "to handle in the long run."
);

$i = 0;
next_altrow ();

function vcs_directory ($vcs, $label, $row_grp)
{
  print "<p><span class='preinput'><label for='form_dir_$vcs'>"
    . "$label</label></span><br />\n";
  print '<input type="text" name="form_dir_' . $vcs . '" id="form_dir_' . $vcs
    .'" value="' . $row_grp["dir_$vcs"] . '" size="50" />';
  next_altrow ();
}

$titles = ['cvs' => no_i18n ("CVS directory:"),
  'arch' => no_i18n ("GNU Arch directory:"),
  'svn' => no_i18n ("Subversion directory:"),
  'git' => no_i18n ("Git directory:"),
  'hg' => no_i18n ("Mercurial directory:"),
  'bzr' => no_i18n ("Bazaar directory:"),
];

foreach ($titles as $k => $v)
  vcs_directory ($k, $v, $row_grp);

print '<p><span class="preinput"><label for="form_dir_homepage">'
  . no_i18n("Homepage directory:") . "</label></span><br />\n"
  . '<input type="text" name="form_dir_homepage" id="form_dir_homepage" '
  . 'value="' . $row_grp['dir_homepage'] . '" size="50" />';
next_altrow ();

print '<p><span class="preinput"><label for="form_dir_download">'
  . no_i18n ("Download directory:") . "</label></span><br />\n"
  . '<input type="text" name="form_dir_download" id="form_dir_download" '
  . 'value="' . $row_grp['dir_download'] . "\" size='50' /></p>\n";
print '<p><input type="submit" name="update" value="' . no_i18n("Update")
  . "\" /></p>\n";

$HTML->box1_bottom ();
site_admin_footer ([]);
?>
