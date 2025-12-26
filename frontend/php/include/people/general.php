<?php
# Functions for the people module.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2025 Ineiev <ineiev@gnu.org>
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

function people_fetch_name ($table, $id_field, $id)
{
  static $names = [];
  if (array_key_exists ($table, $names))
    return $names[$table];
  $result = db_execute ("SELECT `name`, `$id_field` AS `id` FROM `$table`");
  while ($row = db_fetch_array ($result))
    $names[$table][$row['id']] = $row['name'];
  if (!array_key_exists ($id, $names[$table]))
    return NULL;
  return $names[$table][$id];
}

function people_get_type_name ($type_id)
{
  return people_fetch_name ('group_type', 'type_id', $type_id);
}

function people_get_category_name ($category_id)
{
  return people_fetch_name ('people_job_category', 'category_id', $category_id);
}

function people_fetch_counts_by_category ()
{
  return db_execute ("
    SELECT `c`.`name`, `c`.`category_id`, `count`
    FROM
      `people_job_category` `c` LEFT JOIN
      (
        SELECT `category_id`, COUNT(`job_id`) AS `count`
        FROM `people_job` WHERE `status_id` = 1
        GROUP BY `category_id`
      ) `j`
      ON `c`.`category_id` = `j`.`category_id`
    ORDER BY `c`.`category_id`"
  );
}

function people_list_categories ()
{
  global $php_self;

  $result = people_fetch_counts_by_category ();
  if (!db_numrows ($result))
    return false;
  $ret = [];
  while ($row = db_fetch_array ($result))
    {
      if (empty ($row['count']))
        $row['count'] = 0;
      $ret[] =
        form_checkbox (
          'categories[]', 0,
          ['title' => $row['name'], 'value' => $row['category_id']]
        )
        . "<a href=\"$php_self?categories[]={$row['category_id']}\">"
        . "{$row['name']} ({$row['count']})</a>";
    }
  return join ("<br />\n", $ret);
}

function people_list_project_type ()
{
  global $php_self;

  $result = db_execute ("
    SELECT
      `gt`.`type_id`, `gt`.`name`, COUNT(`p`.`job_id`) AS `count`
    FROM
      `group_type` `gt`
      JOIN
      (`groups` `g` JOIN `people_job` `p` ON `g`.`group_id` = `p`.`group_id`)
      ON `gt`.`type_id` = `g`.`type`
    WHERE `status_id` = 1 GROUP BY `type_id`, `gt`.`name` ORDER BY `type_id`"
  );
  $rows = db_numrows ($result);
  if ($rows < 1)
    return false;
  $return = "";
  for ($i = 0; $i < $rows; $i++)
    {
      foreach (['type_id', 'name', 'count'] as $var)
        $$var = db_result ($result, $i, $var);
      $name = gettext ($name);
      $return .=
        form_checkbox (
          'types[]', 0, [ 'title' => $name, 'value' => $type_id]
        )
        . "<a href=\"$php_self?types[]=$type_id"
        . "\">$name ($count)</a><br />\n";
     }
  return $return;
}

function people_append_list (&$form_is_empty, $ret, $def)
{
  if ($ret === false)
    return "<p><strong>$def</strong></p>\n";
  $form_is_empty = false;
  return $ret;
}

# Show job selection controls.
function people_show_table ()
{
  $form_is_empty = true;
  $return = html_h (2, _("Category"));
  $return .= people_append_list (
    $form_is_empty, people_list_categories (), _("No categories found")
  );

  $return .= html_h (2, _("Group type"));
  $return .= people_append_list (
    $form_is_empty, people_list_project_type (), _("No jobs found")
  );
  if ($form_is_empty)
    return $return;
  return form_tag (['method' => 'get']) . "$return<hr />\n"
    . "<input type='submit' name='submit' value=\"" . _("Search")
    . "\" />\n</form>\n";
}

function people_fetch_categories ()
{
  $ret = [];
  $result = db_execute (
    "SELECT * FROM people_job_category ORDER BY category_id"
  );
  while ($row = db_fetch_array ($result))
    $ret[$row['category_id']] = $row;
  return $ret;
}

function people_fetch_job_counts ()
{
  $result = db_execute ("
     SELECT category_id, count(*) AS count FROM people_job
     WHERE status_id = 1 GROUP BY category_id"
  );
  $ret = [];
  while ($row = db_fetch_array ($result))
    $ret[] = $row;
  return $ret;
}

# Show a list of categories.
# Provide links to drill into a detail page that shows these categories.
function people_show_category_list ()
{
  $finalize = function ($r) { return "<ul class=\"boxli\">$r</ul>\n"; };
  $categories = people_fetch_categories ();
  if (empty ($categories))
    return $finalize ("<li>" . _("No categories found") . "</li>");
  $ret = ''; $j = 0;
  foreach (people_fetch_job_counts () as $row)
    {
      if (!$row['count'])
        continue;
      $cat_id = $row['category_id'];
      $ret .= '<li class="' . utils_altrow ($j++)
        . '"><span class="smaller">&nbsp;&nbsp;- <a href="'
        . '/people/?categories[]=' . $cat_id . '">'
        . $row['count'] . ' ' . $categories[$cat_id]['name']
        . "</a></span></li>\n";
    }
  return $finalize ($ret);
}

function people_job_status_box ($name = 'status_id', $checked = 'xyxy')
{
  # Add current job categories to i18n.
  $job_status_as_of_2017_06 = [
    # TRANSLATORS: this string is a job status.
    _("Open"),
    # TRANSLATORS: this string is a job status.
    _("Filled"),
    # TRANSLATORS: this string is a job status.
    _("Deleted")
  ];
  $result = db_execute ("SELECT * FROM people_job_status");
  return html_build_localized_select_box (
    $result, $name, $checked, true, 'None', false, 'Any', false,
    _('job status')
  );
}

function people_job_category_box ($name = 'category_id', $checked = 'xyxy')
{
  # Add current job categories to i18n.
  $job_categories_as_of_2017_06 = [
    # TRANSLATORS: this string is a job category.
    _("None"),
    # TRANSLATORS: this string is a job category.
    _("Developer"),
    # TRANSLATORS: this string is a job category.
    _("Group Manager"),
    # TRANSLATORS: this string is a job category.
    _("Unix Admin"),
    # TRANSLATORS: this string is a job category.
    _("Doc Writer"),
    # TRANSLATORS: this string is a job category.
    _("Tester"),
    # TRANSLATORS: this string is a job category.
    _("Support Manager"),
    # TRANSLATORS: this string is a job category.
    _("Graphic/Other Designer"),
    # TRANSLATORS: this string is a job category.
    _("Translator"),
    # TRANSLATORS: this string is a job category.
    _("Other")
  ];

  $result = db_execute ("SELECT * FROM people_job_category");
  return html_build_localized_select_box (
    $result, $name, $checked, true, 'None', false, 'Any', false,
    _('job category')
  );
}

function people_add_to_job_inventory (
  $job_id, $skill_id, $skill_level_id, $skill_year_id
)
{
  if (!user_isloggedin ())
    {
      fb (_("You must be logged in first"), 1);
      return;
    }
  # Check if they've already added this skill.
  $result = db_execute ("
    SELECT * FROM people_job_inventory WHERE job_id = ? AND skill_id = ?",
    [$job_id, $skill_id]
  );
  if (db_numrows ($result) > 0)
    {
      fb (_("Skill already in your inventory"), 1);
      return;
    }
  # Skill isn't already in this inventory.
  $result = db_autoexecute (
   'people_job_inventory',
    [
      'job_id' => $job_id,
      'skill_id' => $skill_id,
      'skill_level_id' => $skill_level_id,
      'skill_year_id' => $skill_year_id
    ], DB_AUTOQUERY_INSERT
  );
  if ($result && db_affected_rows ($result) > 0)
    {
      fb (_("Added to skill inventory"));
      return;
    }
  fb (
    # TRANSLATORS: this is an error message.
    _('Inserting into skill inventory'), 1
  );
  print db_error ();
}

function people_show_job_inventory ($job_id)
{
  $result = db_execute ("
    SELECT
      s.name AS skill_name, l.name AS level_name, y.name AS year_name
    FROM
      people_skill_year y, people_skill_level l, people_skill s,
      people_job_inventory i
    WHERE
      y.skill_year_id = i.skill_year_id AND l.skill_level_id = i.skill_level_id
      AND s.skill_id = i.skill_id AND i.job_id = ?", [$job_id]
  );

  print html_build_list_table_top ([_("Skill"), _("Level"), _("Experience")]);

  $rows = db_numrows ($result);
  if (!$result)
    {
      print '<tr><td><p class="warn">(' . _("SQL Error:") . ")</p>\n";
      print db_error ();
      print "</td></tr>\n";
    }
  elseif ($rows < 1)
    {
      print '<tr><td><p class="warn">('
        . _("No skill inventory set up") . ")</p>\n";
      print "</td></tr>\n";
    }
  else
    for ($i = 0; $i < $rows; $i++)
      print "<tr class=\"" . utils_altrow ($i) . "\">\n"
        . '  <td>' . db_result ($result, $i, 'skill_name') . "</td>\n"
        . '  <td>' . db_result ($result, $i, 'level_name') . "</td>\n"
        . '  <td>' . db_result ($result, $i, 'year_name') . "</td>\n"
        . "</tr>\n";
  print "</table>\n";
}

function people_verify_job_group ($job_id, $group_id)
{
  $result = db_execute ("
    SELECT * FROM people_job WHERE job_id = ? AND group_id = ?",
    [$job_id, $group_id]
  );
  return db_numrows ($result) > 0;
}

function people_draw_skill_box ($result, $job_id = false, $group_id = false)
{
  if ($job_id === false)
    $infix = 'skill';
  else
    $infix = 'job';

  $title_arr = [_('Skill'), _('Level'), _('Experience'), _('Action')];
  if (!db_numrows ($result))
    {
      print html_build_list_table_top ($title_arr);
      print "\n<tr><td colspan='4'><strong>"
        . _("No skill inventory set up")
        . "</strong></td></tr>\n</table>\n";
      print db_error ();
    }
  for ($i = 0; $row = db_fetch_array ($result); $i++)
    {
      print form_tag ();
      print html_build_list_table_top ($title_arr);
      $hid = ['group_id' => $group_id];
      $k = "{$infix}_inventory_id";
      $hid[$k] = $row[$k];
      $k = "{$infix}_id";
      $hid[$k] = $row[$k];
      print "<tr class='" . utils_altrow ($i)
        . "'>\n<td>" . form_hidden ($hid)
        . "<span class='smaller'>{$row['skill_name']}"
        . "</span></td>\n<td><span class='smaller'>"
        . people_skill_level_box ('skill_level_id', $row['skill_level_id'])
        . "</span></td>\n<td><span class='smaller'>"
        . people_skill_year_box ('skill_year_id', $row['skill_year_id'])
        . "</span></td>\n<td nowrap><span class='smaller'>"
        . form_submit (_("Update"), "update_{$infix}_inventory") . "&nbsp;\n"
        . form_submit (_("Delete"), "delete_from_{$infix}_inventory")
        . "</span></td>\n</tr></table>\n"
        . "</form>\n";
    }
  print html_h (3, _("Add a New Skill"));
  print form_tag ();
  print html_build_list_table_top ($title_arr);
  print "\n<tr class='" . utils_altrow (0) . "'>\n<td>";

  if ($job_id !== false)
    print form_hidden (["{$infix}_id" => $job_id, 'group_id' => $group_id]);

  print '<span class="smaller">' . people_skill_box ('skill_id')
    . "</span></td>\n<td><span class='smaller'>"
    . people_skill_level_box ('skill_level_id')
    . "</span></td>\n<td><span class='smaller'>"
    . people_skill_year_box ('skill_year_id')
    . "</span></td>\n<td nowrap><span class='smaller'>"
    . form_submit (_("Add Skill"), "add_to_{$infix}_inventory")
    . "</span></td>\n</tr></table>\n</form>\n";
}

function people_edit_job_inventory ($job_id, $group_id)
{
  $result = db_execute ("
     SELECT *, s.name AS skill_name
     FROM people_job_inventory i, people_skill s
     WHERE job_id = ? AND s.skill_id = i.skill_id",
    [$job_id]
  );
  people_draw_skill_box ($result, $job_id, $group_id);
}

function people_job_line ($row, $i, $page)
{
  $name = gettext ($row['type_name']);
  return "<tr class=\"" . utils_altrow ($i)
    . '"><td><a href="' . "/people/$page?group_id="
    . $row['group_id'] . '&job_id=' . $row['job_id'] . '">'
    . $row['title'] . "</a></td>\n<td>" . $row['category_name'] . "</td>\n<td>"
    . utils_format_date ($row['date'], 'natural')
    . "</td>\n<td><a href=\"/projects/"
    . strtolower ($row['unix_group_name']) . '/">'
    . $row['group_name'] . "</a></td>\n<td>$name</td></tr>\n";
}

# Take a result set from a query and show the jobs.
function people_show_job_list ($result, $edit = 0)
{
  $title_arr = [
    _("Title"), _("Category"), _("Date Opened"), _("Group"), _("Type")
  ];

  $page = 'viewjob.php';
  if ($edit)
    $page = 'editjob.php';
  $tail = "</table>\n";

  $return = html_build_list_table_top ($title_arr);
  $rows = db_numrows ($result);
  if ($rows < 1)
    return $return . '<tr><td colspan="3"><strong>'
      . _("None found") . '</strong>' . db_error () . "</td></tr>\n" . $tail;

  $i = 0;
  while ($row = db_fetch_array ($result))
    $return .= people_job_line ($row, $i++, $page);
  return $return . $tail;
}

function people_job_sql ()
{
  return "
  SELECT
    `j`.`group_id`, `j`.`job_id`, `j`.`title`, `j`.`date`,
    `g`.`unix_group_name`, `g`.`group_name`, `g`.`type`,
    `c`.`name` AS `category_name`, `gt`.`name` AS `type_name`
  FROM
    (`people_job` `j` JOIN `people_job_category` `c`
     ON `j`.`category_id` = `c`.`category_id`)
    JOIN `groups` `g` ON `j`.`group_id` = `g`.`group_id`
    JOIN `group_type` `gt` ON `gt`.`type_id` = `g`.`type`
 ";
}

# Show open jobs for this group.
function people_show_project_jobs ($group_id, $edit = 0)
{
  $result = db_execute (
    people_job_sql () . "
    WHERE
      `j`.`group_id` = ? AND `j`.`group_id` = `g`.`group_id`
      AND `j`.`category_id` = `c`.`category_id` AND `j`.`status_id` = 1
    ORDER BY `date` DESC",
    [$group_id]
  );
  return people_show_job_list ($result, $edit);
}

# Number of open jobs for this group.
function people_project_jobs_rows ($group_id)
{
  $result = db_execute ("
    SELECT `j`.`job_id`
    FROM `people_job` `j`, `people_job_category` `c`, `groups` `g`
    WHERE
      `j`.`group_id` = ?  AND `j`.`group_id` = `g`.`group_id`
      AND `j`.`category_id` = `c`.`category_id` AND `j`.`status_id` = 1",
    [$group_id]
  );
  return db_numrows ($result);
}

# Show open jobs for the given job categories and types of groups,
# or all open jobs when $categories and $types are empty.
function people_show_jobs ($categories, $types)
{
  $sql_args = [];
  $enum_ids =
    function ($id_arr, $field) use (&$sql_args)
    {
      if (empty ($id_arr))
        return '';
      $ret = "$field " . utils_in_placeholders ($id_arr);
      $sql_args = array_merge ($sql_args, $id_arr);
      return "AND $ret";
    };
  $cat_ids = $enum_ids ($categories, '`j`.`category_id`');
  $type_ids = $enum_ids ($types, '`g`.`type`');
  $result = db_execute (people_job_sql () . "
    WHERE `g`.`is_public` = 1 AND `j`.`status_id` = 1
    $cat_ids $type_ids ORDER BY `date` DESC",
    $sql_args
  );
  return people_show_job_list ($result);
}

function people_skill_box ($name = 'skill_id', $checked = 'xyxy')
{
  global $PEOPLE_SKILL;
  if (!$PEOPLE_SKILL)
    $PEOPLE_SKILL = db_execute ("SELECT * FROM people_skill ORDER BY name");
  return html_build_select_box (
    $PEOPLE_SKILL, $name, $checked, true, 'None', false, 'Any', false, 'skills'
  );
}

function people_skill_level_box ($name = 'skill_level_id', $checked = 'xyxy')
{
  global $PEOPLE_SKILL_LEVEL;

  $skill_levels_as_of_2017_06 = [
    # TRANSLATORS: this string is a skill level.
    _('Base Knowledge'),
    # TRANSLATORS: this string is a skill level.
    _('Good Knowledge'),
    # TRANSLATORS: this string is a skill level.
    _('Master'),
    # TRANSLATORS: this string is a skill level.
    _('Master Apprentice'),
    # TRANSLATORS: this string is a skill level.
    _('Expert')
  ];
  if (!$PEOPLE_SKILL_LEVEL)
    $PEOPLE_SKILL_LEVEL = db_execute ("SELECT * FROM people_skill_level");
  return html_build_localized_select_box (
    $PEOPLE_SKILL_LEVEL, $name, $checked, true, 'None', false, 'Any', false,
    _('skill level')
  );
}

function people_skill_year_box ($name = 'skill_year_id', $checked = 'xyxy')
{
  global $PEOPLE_SKILL_YEAR;
  $skill_years_as_of_2023_01 = [
    # TRANSLATORS: this string is an experience level.
    _('< 6 Months'),
    # TRANSLATORS: this string is an experience level.
    _('6 Mo - 2 yr'),
    # TRANSLATORS: this string is an experience level.
    _('2 yr - 5 yr'),
    # TRANSLATORS: this string is an experience level.
    _('5 yr - 10 yr'),
    # TRANSLATORS: this string is an experience level.
    _('> 10 years'),
    # TRANSLATORS: this string is an experience level.
    _('10 yr - 20 yr'),
    # TRANSLATORS: this string is an experience level.
    _('20 yr - 40 yr'),
    # TRANSLATORS: this string is an experience level.
    _('40 yr - 80 yr'),
    # TRANSLATORS: this string is an experience level.
    _('> 80 years')
  ];
  if (!$PEOPLE_SKILL_YEAR)
    $PEOPLE_SKILL_YEAR = db_execute ("SELECT * FROM people_skill_year");
  return html_build_localized_select_box (
    $PEOPLE_SKILL_YEAR, $name, $checked, true, 'None', false, 'Any', false,
    _('experience level')
  );
}

function people_add_to_skill_inventory (
  $skill_id, $skill_level_id, $skill_year_id
)
{
  global $feedback;
  if (!user_isloggedin ())
    {
      print '<p><strong>' . _('You must be logged in first') . '</strong></p>';
      return;
    }
  # Check if they've already added this skill.
  $result = db_execute ("
    SELECT * FROM people_skill_inventory WHERE user_id = ? AND skill_id = ?",
    [user_getid (), $skill_id]
  );
  if (db_numrows ($result))
    {
      fb (_('ERROR - skill already in your inventory'), 1);
      return;
    }
  $result = db_autoexecute ('people_skill_inventory',
    [
      'user_id' => user_getid (), 'skill_id' => $skill_id,
      'skill_level_id' => $skill_level_id, 'skill_year_id' => $skill_year_id
    ],
    DB_AUTOQUERY_INSERT
  );
  if ($result && db_affected_rows ($result))
    {
      fb (_('Added to skill inventory'));
      return;
    }
  fb (_('ERROR inserting into skill inventory'), 1);
  print db_error ();
}

function people_print_skill_table ($result)
{
  print html_build_list_table_top ([_("Skill"), _("Level"), _("Experience")]);
  $i = 0;
  while ($row = db_fetch_array ($result))
    {
      foreach (['skill_name', 'level_name', 'year_name'] as $v)
         $$v = gettext ($row[$v]);
      print "<tr class=\"" . utils_altrow ($i++) . "\">\n"
        . "<td>$skill_name</td>\n<td>$level_name</td>\n"
        . "<td>$year_name</td></tr>\n";
    }
  print "</table>\n";
}

function people_show_skill_inventory ($user_id)
{
  $result = db_execute ("
    SELECT s.name AS skill_name, l.name AS level_name, y.name AS year_name
    FROM
      people_skill_year y, people_skill_level l, people_skill s,
      people_skill_inventory i
    WHERE
      y.skill_year_id = i.skill_year_id AND l.skill_level_id = i.skill_level_id
      AND s.skill_id = i.skill_id AND i.user_id = ?",
    [$user_id]
  );
  if (!$result)
    {
      print '<p class="warn">' . _("SQL Error:") . "</p>\n";
      print db_error ();
      return;
    }
  if (db_numrows ($result) < 1)
    {
      print '<p class="warn">' . _("No skill inventory set up") . "</p>\n";
      return;
    }
  people_print_skill_table ($result);
}

function people_edit_skill_inventory ($user_id)
{
  $result = db_execute ("
    SELECT *, s.name AS skill_name
    FROM people_skill_inventory i, people_skill s
    WHERE user_id = ? AND s.skill_id = i.skill_id",
    [$user_id]
  );
  people_draw_skill_box ($result);
}
?>
