<?php
# Resume editor.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2026 Ineiev
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
require_once ('../../include/form.php');
require_directory ("people");

if (!user_isloggedin ())
  exit_not_logged_in ();

$submits = [
  'update_profile', 'add_to_skill_inventory', 'update_skill_inventory',
  'delete_from_skill_inventory'
];
extract (sane_import ('post',
  [
    'true' => $submits,
    'digits' =>
      [
        'skill_id', 'skill_level_id', 'skill_year_id', 'skill_inventory_id',
        ['people_view_skills', [0, 1]],
      ],
    'pass' => 'people_resume'
  ]
));
form_check ($submits);

# Check if resume should be editable at all.
$allow_resume = false;
# Let edit resume when it already exists.
$result = db_execute (
 "SELECT people_resume FROM user WHERE user_id = ?", [user_getid ()]
);
if (db_numrows ($result))
  $allow_resume = ('' != db_result ($result, 0, 'people_resume'));
# Let members of any group edit their resume.
if (!$allow_resume)
  {
    $result = db_execute ("
      SELECT g.group_id
      FROM user_group u JOIN groups g ON g.group_id = u.group_id
      WHERE status = ? AND user_id = ? AND admin_flags != ?
      LIMIT 1", [USER_STATUS_ACTIVE, user_getid (), MEMBER_FLAGS_PENDING]
    );
    $allow_resume = db_numrows ($result);
  }

if ($update_profile)
  {
    $arg_arr = [$people_view_skills, user_getid ()];
    $sql_str = "people_view_skills = ?";
    if ($allow_resume)
      {
        if (!$people_resume)
          $people_resume = '';
        $arg_arr = [$people_view_skills, $people_resume, user_getid ()];
        $sql_str = "$sql_str, people_resume = ?";
      }
    $result = db_execute (
      "UPDATE user SET $sql_str WHERE user_id = ?", $arg_arr
    );
    if ($result)
      fb (_("Updated successfully"));
    else
      fb (_("Update failed"), 1);
  }
elseif ($add_to_skill_inventory)
  {
    if ($skill_id == 100 || $skill_level_id == 100 || $skill_year_id == 100)
      fb (_("Missing information: fill in all required fields"), 1);
    else
      people_add_to_skill_inventory (
        $skill_id, $skill_level_id, $skill_year_id
      );
  }
elseif ($update_skill_inventory)
  {
    if (
      $skill_level_id == 100 || $skill_year_id == 100  || !$skill_inventory_id
    )
      fb (_("Missing information: fill in all required fields"));
    else
      {
        $result = db_execute ("
          UPDATE people_skill_inventory
          SET skill_level_id = ?, skill_year_id = ?
          WHERE user_id = ? AND skill_inventory_id = ?",
          [$skill_level_id, $skill_year_id, user_getid (), $skill_inventory_id]
        );

        if (db_affected_rows ($result))
          fb (_("User Skills updated successfully"));
        else
          fb (_("User Skill update failed"), 1);
      }
  }
elseif ($delete_from_skill_inventory)
  {
    if (!$skill_inventory_id)
      exit_error (_("Missing information: fill in all required fields"));

    $result = db_execute ("
      DELETE FROM people_skill_inventory
      WHERE user_id = ? AND skill_inventory_id = ?",
      [user_getid (), $skill_inventory_id]
    );
    if (db_affected_rows ($result))
      fb (_("User Skill Deleted successfully"));
    else
      fb (_("User Skill Delete failed"), 1);
  }

# Fill in the info to edit the resume.
site_user_header (
  ['title' => _("Edit Your Resume & Skills"), 'context' => 'account']
);
print '<p>'
  . _("Details about your experience and skills may be of interest to other "
      . "users\nor visitors.")
  . "</p>\n";

$result = db_execute ("SELECT * FROM user WHERE user_id = ?", [user_getid ()]);
if (!db_numrows ($result))
  exit_user_not_found (user_getid ());
utils_get_content ("people/editresume");

print form_tag () . html_h (2, _("Publicly Viewable"))
  . '<span class="preinput">' . _("Do you want your resume to be activated?")
  . '</span>&nbsp;&nbsp;'
  . html_build_select_box_from_array (
      ["0" => _("No"), "1" => _("Yes")], 'people_view_skills',
      db_result ($result, 0, 'people_view_skills'), 0, _("Activate resume")
    );

if ($allow_resume)
  print
    html_h (2,
      html_label ("people_resume", _("Resume - Description of Experience"))
    )
    . "<p>" . markup_info ("full") . "</p>\n"
    . form_textarea ("people_resume",  db_result ($result, 0, 'people_resume'),
        ' rows="15" cols="60" wrap="soft"'
      )
    . "\n<br /><br />\n";

print form_footer (_("Update Profile"), 'update_profile');

print html_h (2, _("Skills"));
# Now show the list of desired skills.
people_edit_skill_inventory (user_getid ());

site_user_footer ([]);
?>
