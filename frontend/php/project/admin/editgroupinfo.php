<?php
# Edit group public info.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Free Software Foundation, Inc.
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

require_once ('../../include/init.php');
require_once ('../../include/vars.php');
require_once ('../../include/gpg.php');
$gpg_heading_level = 3;
require (utils_get_content_filename ("gpg-sample"));

extract (sane_import ('post',
  [
    'true' =>
      [
        'update', 'update_keyring', 'reset_keyring', 'test_keyring',
        'upgrade_gpl',
      ],
    'pass' => ['new_keyring', 'form_longdesc'],
    'specialchars' => ['form_group_name', 'form_shortdesc'],
    'digits' => 'form_devel_status',
  ]
));

session_require (['group' => $group_id, 'admin_flags' => 'A']);

# Update info for page.
$res_grp = db_execute ("SELECT * FROM groups WHERE group_id = ?", [$group_id]);
if (db_numrows ($res_grp) < 1)
  exit_no_group( );
$row_grp = db_fetch_array( $res_grp);

$keyring = group_get_preference ($group_id, 'gpg_keyring');

if ($keyring === false)
  $keyring = '';

if ($reset_keyring)
  $new_keyring = $keyring;

if ($update)
  {
    group_add_history ('Changed Public Info', '', $group_id);

    $result = db_autoexecute('groups',
      [
        'group_name' => $form_group_name,
        'short_description' => $form_shortdesc,
        'long_description' => $form_longdesc,
        'devel_status' => $form_devel_status,
      ], DB_AUTOQUERY_UPDATE,
      "group_id = ?", [$group_id]
    );
    if (!$result)
      fb (_("Update failed."), 1);

    if ($row_grp['license'] == 'gpl' and $upgrade_gpl)
      db_execute (
        "UPDATE groups SET license = 'gplv3orlater' WHERE group_id = ?",
        [$group_id]
     );
  }

if ($test_keyring)
  $gpg_checks = gpg_run_checks ($new_keyring, false, '3');

if ($update_keyring)
  {
    if (group_set_preference ($group_id, 'gpg_keyring', $new_keyring))
      {
        group_add_history ('Updated Release GPG Keyring', '', $group_id);
        $keyring = $new_keyring;
      }
    else
      fb (_("Update failed."), 1);
  }

$res_grp = db_execute ("SELECT * FROM groups WHERE group_id = ?", [$group_id]);
if (db_numrows ($res_grp) < 1)
  exit_no_group ();
$row_grp = db_fetch_array ($res_grp);

site_project_header (
  [ 'title' => _("Editing Public Information"), 'group' => $group_id,
    'context' => 'ahome']
);

print form_header ($_SERVER['PHP_SELF'])
  . form_hidden (["group_id" => $group_id]);

$print_preinput = function ($label, $name, $markup = '')
{
  if (!empty ($markup))
    $markup = ' ' . markup_info ($markup);
  print "<p><span class='preinput'><label for='$name'>"
    . "$label</label>$markup</span><br />\n&nbsp;&nbsp;&nbsp;";
};

$print_preinput (_("Group Name:"), 'form_group_name');
print
  form_input (
    "text", "form_group_name",
    utils_specialchars_decode ($row_grp['group_name'], ENT_QUOTES),
   'size="60" maxlen="254"'
  )
  . "</p>\n";
$print_preinput (
  _("Short description (255 characters max)"), 'form_shortdesc', 'none'
);
print
  form_textarea (
    "form_shortdesc", $row_grp['short_description'],
    'cols="70" rows="3" wrap="virtual"'
  )
  . "</p>\n";
$print_preinput (_("Long Description"), 'form_longdesc', 'full');
print
  form_textarea (
    "form_longdesc", utils_specialchars ($row_grp['long_description']),
    'cols="70" rows="10" wrap="virtual"'
  )
  . "</p>\n";

$type_id = $row_grp['type'];
$result1 = db_execute ("SELECT * FROM group_type WHERE type_id = ?", [$type_id]);
$row_grp1 = db_fetch_array ($result1);
$DEVEL_STATUS1 = $row_grp1['devel_status_array'];
if ($DEVEL_STATUS1)
  $DEVEL_STATUS = preg_split ("/\n/", $DEVEL_STATUS1);

if ($project->CanUse ("devel_status"))
  {
    $print_preinput (_("Development Status:"), "form_devel_status");
    print  '<select name="form_devel_status" id="form_devel_status">';

    foreach ($DEVEL_STATUS as $k => $v)
      print form_option ($k, $row_grp['devel_status'], $v);
    print "</select></p>\n";
  }

print '<p><span class="preinput">'
  . _("License:") . "</span><br />&nbsp;&nbsp;\n"
  . _("License changes are moderated by the site administrators. Please\n"
      . "contact them to change your package license.")
  . "</p>\n";

if ($project->getLicense () == 'gpl')
  {
    print '<p><span class="preinput">' . _("GNU GPL v3:")
      . "</span>\n<br />&nbsp;&nbsp;";
    print form_checkbox ("upgrade_gpl");
    print "\n<label for=\"upgrade_gpl\">"
      . _("Upgrade license to &quot;GNU GPLv3 or later&quot;");
    print "</label></p>\n";
  }

print form_submit ();

print "\n<h2>" . _("GPG Keys Used for Releases") . "</h2>\n";

print $gpg_sample_text;

if ($project->getTypeBaseHost () == "savannah.gnu.org")
  print $gpg_gnu_maintainers_note;

if (!$new_keyring)
  $new_keyring = $keyring;

print form_textarea ("new_keyring",
  utils_specialchars ($new_keyring), 'cols="70" rows="10" wrap="virtual"'
);
print '<p>'
  . form_submit (_("Test GPG keys"), 'test_keyring') . "\n"
  . form_submit (_("Cancel"), 'reset_keyring') . "\n"
  . form_submit (_("Update"), 'update_keyring') . "\n"
  . "</p>\n</form>\n";

if (isset ($gpg_checks))
  print $gpg_checks;

site_project_footer ([]);
?>
