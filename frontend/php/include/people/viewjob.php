<?php
# Functions for the people/viewjob.php.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2026 Ineiev <ineiev@gnu.org>
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

function people_fetch_job ($job_id, $group)
{
  $result = db_execute ("
    SELECT
      `g`.`group_name`, `g`.`type`, `g`.`unix_group_name`,
      `j`.`title` AS `job_title`, `j`.`date`, `j`.`description`,
      `j`.`category_id` AS `category_id`, `jc`.`name` AS `category_name`,
      `j`.`status_id`, `u`.`user_name`, `u`.`user_id`
    FROM
      (
        (`people_job` `j` JOIN `groups` `g` ON `j`.`group_id` = `g`.`group_id`)
        JOIN people_job_category jc ON `jc`.`category_id` = `j`.`category_id`
      )
      JOIN `user` `u` ON `u`.`user_id` = `j`.`created_by`
    WHERE `j`.`job_id` = ? AND `j`.`group_id` = ?",
    [$job_id, $group->getGroupId ()]
  );

  $msg = sprintf (_('Group %s has not job #%d.'), $group->GetName (), $job_id);
  if (!db_numrows ($result))
    exit_error ($msg, db_error ());
  return db_fetch_array ($result);
}

function people_fetch_job_inventory ($job_id)
{
  return db_execute ("
    SELECT
      `s`.`name` AS `skill`, `l`.`name` AS `level`, `y`.`name` AS `year`
    FROM
      (
        (
          `people_job_inventory` `i` JOIN `people_skill_year` `y`
          ON `y`.`skill_year_id` = `i`.`skill_year_id`
        )
        JOIN `people_skill_level` `l`
        ON `l`.`skill_level_id` = `i`.`skill_level_id`
      )
      JOIN `people_skill` `s` ON `s`.`skill_id` = `i`.`skill_id`
    WHERE `i`.`job_id` = ?", [$job_id]
  );
}

function print_job_inventory_row ($i, $row)
{
  print "<tr class=\"" . utils_altrow ($i) . "\">\n";
  foreach (['skill', 'level', 'year'] as $c)
    print '  <td>' . utils_specialchars (gettext ($row[$c])) . "</td>\n";
  print "</tr>\n";
}

function people_show_job_inventory ($job_id)
{
  $result = people_fetch_job_inventory ($job_id);
  if (!$result)
    {
      print '<p class="warn">(' . _("SQL Error:") . db_error () . ")</p>\n";
      return;
    }
  if (!db_numrows ($result))
    return;
  print html_h (2, _('Required Skills'));
  print html_build_list_table_top ([_("Skill"), _("Level"), _("Experience")]);
  $i = 0;
  while ($row = db_fetch_array ($result))
    print_job_inventory_row ($i++, $row);
  print "</table>\n";
}

function people_show_job_header ($group_id, $row)
{
  site_project_header (
    ['title' => _("View a Job"), 'group' => $group_id, 'context' => 'home']
  );

  $group_link = "<a href=\"/projects/"
    . $row['unix_group_name'] . '">' . $row['group_name'] . '</a>';
  # TRANSLATORS: the first argument is job title (like Tester or Developer),
  # the second argument is group name (like GNU Coreutils).
  $msg = sprintf (_('%1$s for %2$s'), $row['job_title'], $group_link);
  print html_h (1, $msg);
}

function people_show_job_params ($row)
{
  $user_name = $row['user_name'];
  $category = utils_specialchars (gettext ($row['category_name']));
  print "<p><span class='preinput'>" . _("Category:")
    . "</span> <a href=\"/people/?categories[]="
    . $row['category_id'] . '">' . "$category</a><br />\n"
    . '<span class="preinput">' . _("Submitter:") . '</span> '
    . "<a href='/users/$user_name'>$user_name</a><br />\n"
    . '<span class="preinput">' . _("Date:") . '</span> '
    . utils_format_date ($row['date'])
    . "<br />\n<span class=\"preinput\">" . _("Status:") . '</span> '
    . people_fetch_job_status ($row['status_id']) . "</p>\n";
}

function people_show_job_group_license ($group)
{
  global $LICENSE, $LICENSE_URL;
  $license = $group->getLicense ();
  print '<p><span class="preinput">' . _("License") . '</span> ';
  $lic_label = $LICENSE[$license];
  $lic_url = $LICENSE_URL[$license];
  if ($lic_url != "0")
    $lic_label = "<a href=\"{$lic_url}\" target=\"_blank\">$lic_label</a>";
  print "$lic_label</p>\n";
}

function people_show_job_group_devel_status ($group)
{
  global $DEVEL_STATUS;
  $devel_status_id = $group->getDevelStatus ();
  $devel_status = "&lt;" . _("Invalid status ID") . "&gt;";
  if (isset ($DEVEL_STATUS[$devel_status_id]))
    $devel_status = $DEVEL_STATUS[$devel_status_id];
  print "<span class=\"preinput\"><br />\n"
    . _("Development Status") . "</span>: $devel_status";
}

function people_show_job_group_descriptions ($group)
{
  if ($group->getTypeDescription ())
    print "<p>" . markup_full ($group->getTypeDescription ()) . "</p>\n";
  print "<p>";
  if ($group->getLongDescription ())
    print markup_full (utils_specialchars ($group->getLongDescription ()));
  elseif ($group->getDescription ())
    print $group->getDescription ();
  print "</p>\n";
  people_show_job_group_license ($group);
  people_show_job_group_devel_status ($group);
}

function people_show_job ($job_id, $group_id)
{
  $group = group_get_object ($group_id);
  $row = people_fetch_job ($job_id, $group);
  people_show_job_header ($group_id, $row);
  people_show_job_params ($row);
  people_show_job_group_descriptions ($group);
  print '<p><span class="preinput">'
    . _("Details (job description, contact ...):") . "</span></p>\n";
  print markup_full (utils_specialchars ($row['description']));
  people_show_job_inventory ($job_id);
  site_project_footer ([]);
}
?>
