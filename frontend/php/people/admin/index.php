<?php
# Edit job categories and skills.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

require_once('../../include/init.php');
require_once('../../include/form.php');

extract (sane_import ('request', ['true' => ['people_cat', 'people_skills']]));
extract (sane_import ('post',
  [
    'true' => 'post_changes',
    'specialchars' => ['skill_name', 'cat_name'],
  ]
));

# This page is for site admins only.
if (!user_ismember (1, 'A'))
  exit_permission_denied ();

if ($post_changes)
  {
    # Update the database.
    if ($people_cat)
      {
        $result = db_execute (
          "INSERT INTO people_job_category (name) VALUES (?)", [$cat_name]
        );
        if (!$result)
          {
            print db_error();
            fb (_("Error inserting value"));
          }
        fb (_("Category Inserted"));
      }
    elseif ($people_skills)
      {
        $result = db_execute ("INSERT INTO people_skill (name) VALUES (?)",
           [$skill_name]
        );
        if (!$result)
          {
            print db_error ();
            fb (_("Error inserting value"));
          }
        fb (_("Skill Inserted"));
      }
  }

if ($people_cat)
  {
    # Show categories and blank row.
    site_header (['title' => _('Change Categories')]);
    # List of possible categories for this group.
    $result = db_query ("SELECT category_id, name FROM people_job_category");
    if (db_numrows ($result) > 0)
      utils_show_result_set (
        $result, _("Existing Categories"), 'people_cat', '2'
      );
    else
      print '<p>' . _("No job categories"). "</p>\n". db_error ();
    print '<h2>'. _("Add a new job category:"). "</h2>\n"
      . form_tag ()
      . form_hidden (['people_cat' => 'y', 'post_changes' => 'y'])
      . "</p>\n<p><label for='cat_name'>"
      . _("New Category Name:") . "</label></p>\n"
      . "<input type='text' name='cat_name' id='cat_name' value='' size='15' "
      . "maxlength='30' /><br />\n<p><strong><span class='warn'>"
      . _("Once you add a category, it cannot be deleted")
      . "</span></strong></p>\n<p>\n"
      . '<input type="submit" name="submit" value="' . _("Add") . "\" /></p>\n"
      . "</form>\n";
  } # $people_cat
elseif ($people_skills)
  {
    # Show people_groups and blank row.
    site_header (['title' => _('Change People Skills')]);
    # List of possible people_groups for this group.
    $result = db_query ("SELECT skill_id, name FROM people_skill");
    print "<p>";
    if (db_numrows ($result) > 0)
      utils_show_result_set (
        $result, _("Existing Skills"), "people_skills", '2'
      );
    else
      {
        print db_error ();
        print "<p>" . _("No Skills Found") . "</p>\n";
      }
    print '<h2>' . _("Add a new skill:") . "</h2>\n";
    print "<p>" . form_tag ()
      . form_hidden (['people_skills' => 'y', 'post_changes' => 'y'])
      . "</p>\n<p><label for='skill_name'>"
      . _("New Skill Name:") . "</label></p>\n"
      . "<input type='text' name='skill_name' id='skill_name' value=''\n"
      . "size='15' maxlength='30' /><br />\n"
      . '<p><strong><span class="warn">'
      . _("Once you add a skill, it cannot be deleted")
      . "</span></strong></p>\n"
      . '<p><input type="submit" name="submit" value="' . _("Add")
      . "\" /></p>\n</form>\n";
  }
else # ! $people_skills
  {
    # Show main page.
    site_header (['title' => _('People Administration')]);
    print '<h1>' . _("Help Wanted Administration") . '</h1>';
    print "<p><a href=\"$php_self?people_cat=1\">"
      . _("Add Job Categories") . "</a><br />\n";
    print "\n<a href=\"$php_self?people_skills=1\">"
      . _("Add Job Skills") ."</a><br />\n";
  }
site_project_footer ();
?>
