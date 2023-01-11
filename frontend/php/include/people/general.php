<?php
# Functions for the people module.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2004 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2013, 2017, 2018, 2022, 2023 Ineiev <ineiev--gnu.org>
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

function people_get_type_name ($type_id)
{
  $result = db_execute (
    "SELECT group_type.name FROM group_type WHERE type_id = ?", [$type_id]
  );
  if (!$result || db_numrows ($result) < 1)
    return 'Invalid ID';
  return db_result ($result, 0, 'name');
}

function people_get_category_name ($category_id)
{
  $result = db_execute (
    "SELECT name FROM people_job_category WHERE category_id = ?",
    [$category_id]
  );
  if (!$result || db_numrows ($result) < 1)
    return 'Invalid ID';
  return db_result ($result, 0, 'name');
}

function people_list_categories ()
{
  $result = db_query ("SELECT * FROM people_job_category ORDER BY category_id");
  $rows = db_numrows ($result);
  if (!$result || $rows < 1)
    return false;
  $return = "";
  $php_self = htmlentities ($_SERVER["PHP_SELF"]);
  for ($i = 0; $i < $rows; $i++)
    {
      $count_res = db_execute ("
        SELECT count(*) AS count FROM people_job
        WHERE category_id = ? AND status_id = 1",
        [db_result ($result, $i, 'category_id')]
      );
      print db_error ();
      $return .=
        form_checkbox (
          'categories[]', 0,
          [
            'title' => db_result ($result, $i, 'name'),
            'value' => db_result ($result, $i, 'category_id'),
          ]
        )
        . "<a href=\"$php_self?categories[]="
        . db_result ($result, $i, 'category_id') . '">'
        . db_result ($result, $i, 'name') . ' ('
        . db_result ($count_res, 0, 'count') . ")</a><br />\n";
    }
  return $return;
}

function people_list_project_type ()
{
  $result = db_query ("
    SELECT
      group_type.type_id, group_type.name, COUNT(people_job.job_id) AS count
    FROM
      group_type
      JOIN
      (groups JOIN people_job ON groups.group_id = people_job.group_id)
      ON group_type.type_id = groups.type
    WHERE status_id = 1 GROUP BY type_id ORDER BY type_id"
  );
  $rows = db_numrows ($result);
  if (!$result || $rows < 1)
    return false;
  $return = "";
  $php_self = htmlentities ($_SERVER["PHP_SELF"]);
  for ($i = 0; $i < $rows; $i++)
    $return .=
      form_checkbox (
        'types[]', 0,
        [
          'title' => db_result ($result, $i, 'name'),
          'value' => db_result ($result, $i, 'type_id'),
        ]
      )
      . "<a href=\"$php_self?types[]=" . db_result ($result, $i, 'type_id')
      . '">' . db_result ($result, $i, 'name')
      . ' (' . db_result ($result, $i, 'count') . ")</a><br />\n";
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
  $return = '<h2>' . _("Category") . "</h2>\n";
  $return .= people_append_list (
    $form_is_empty, people_list_categories (), _("No categories found")
  );

  $return .= '<h2>' . _("Project type") . "</h2>\n";
  $return .= people_append_list (
    $form_is_empty, people_list_project_type (), _("No types found")
  );
  if ($form_is_empty)
    return $return;
  return '<form action="'
    . htmlentities ($_SERVER["PHP_SELF"]) . "\" method='get'>\n$return<hr />\n"
    . "<input type='submit' name='submit' value=\"" . _("Search")
    . "\" />\n</form>\n";
}

# Show a list of categories.
# Provide links to drill into a detail page that shows these categories.
function people_show_category_list ()
{
  $finalize = function ($r) { return "<ul class=\"boxli\">$r</ul>\n"; };
  $sql = "SELECT * FROM people_job_category ORDER BY category_id";
  $result = db_query ($sql);
  $rows = db_numrows ($result);
  $return = '';
  if (!$result || $rows < 1)
    return $finalize ("<li>" . _("No categories found") . "</li>");
  for ($i = $j = 0; $i < $rows; $i++)
    {
      $count_res = db_execute ("
        SELECT count(*) AS count FROM people_job
        WHERE category_id = ? AND status_id = 1",
        [db_result ($result, $i, 'category_id')]
      );

      # Print only if there are results within the category.
      if (db_result ($count_res, 0, 'count') <= 0)
        continue;
      $j++;
      $return .= '<li class="' . utils_altrow ($j)
        . '"><span class="smaller">&nbsp;&nbsp;- <a href="'
        . $GLOBALS['sys_home'] . 'people/?categories[]='
        . db_result ($result, $i, 'category_id') . '">'
        . db_result ($count_res, 0, 'count')
        . ' ' . db_result ($result, $i, 'name') . "</a></span></li>\n";
    }
  return $finalize ($return);
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
  $result = db_query ("SELECT * FROM people_job_status");
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
    _("Project Manager"),
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

  $result = db_query ("SELECT * FROM people_job_category");
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
  if ($result && db_numrows ($result) > 0)
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
  return $result && db_numrows ($result) > 0;
}

function people_draw_skill_box ($result, $job_id = false, $group_id = false)
{
  if ($job_id === false)
    $infix = 'skill';
  else
    $infix = 'job';

  $title_arr = [_('Skill'), _('Level'), _('Experience'), _('Action')];

  $rows = db_numrows ($result);
  if (!$result || $rows < 1)
    {
      print html_build_list_table_top ($title_arr);
      print "\n<tr><td colspan='4'><strong>"
        . _("No skill inventory set up")
        . "</strong></td></tr>\n</table>\n";
      print db_error ();
    }
  for ($i = 0; $i < $rows; $i++)
    {
      print "<form action='"
        . htmlentities ($_SERVER['PHP_SELF']) . "' method='POST'>\n";
      print html_build_list_table_top ($title_arr);
      print "<tr class='" . utils_altrow ($i)
        . "'>\n<td><input type='hidden' name='{$infix}_inventory_id' "
        . 'value="' . db_result ($result, $i, $infix . '_inventory_id')
        . "\" />\n<input type='hidden' name='{$infix}_id' "
        . "value='" . db_result ($result, $i, $infix . '_id')
        . "' />\n<input type='hidden' name='group_id' "
        . "value='$group_id' />\n<span class='smaller'>"
        . db_result ($result, $i, 'skill_name')
        . "</span></td>\n<td><span class='smaller'>"
        . people_skill_level_box (
            'skill_level_id', db_result ($result, $i, 'skill_level_id')
          )
        . "</span></td>\n<td><span class='smaller'>"
        . people_skill_year_box (
           'skill_year_id', db_result ($result, $i, 'skill_year_id')
          )
        . "</span></td>\n<td nowrap><span class='smaller'>"
        . "<input type='submit' name='update_{$infix}_inventory' "
        . "value='" . _("Update") . "'> &nbsp;\n"
        . "<input type='submit' name='delete_from_{$infix}_inventory' "
        . "value='" . _("Delete") . "'></span></td>\n</tr></table>\n"
        . "</form>\n";
    }

  print "\n<h3>" . _("Add a New Skill") . "</h3>\n";

  print "\n<form action='" . htmlentities ($_SERVER['PHP_SELF'])
    . "' method='POST'>\n";
  print html_build_list_table_top ($title_arr);
  print "\n<tr class='" . utils_altrow (0) . "'>\n<td>";

  if ($job_id !== false)
    print "<input type='hidden' name='{$infix}_id' value='$job_id' />"
      . "<input type='hidden' name='group_id' value='$group_id' />\n";

  print '<span class="smaller">' . people_skill_box ('skill_id')
    . "</span></td>\n<td><span class='smaller'>"
    . people_skill_level_box ('skill_level_id')
    . "</span></td>\n<td><span class='smaller'>"
    . people_skill_year_box ('skill_year_id')
    . "</span></td>\n<td nowrap><span class='smaller'><input type='submit' "
    . "name='add_to_{$infix}_inventory'\n value='" . _("Add Skill")
    . "'></span></td>\n</tr></table>\n</form>\n";
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

# Take a result set from a query and show the jobs.
function people_show_job_list ($result, $edit = 0)
{
  $title_arr = [
    _("Title"), _("Category"), _("Date Opened"), _("Project"), _("Type")
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

  for ($i = 0; $i < $rows; $i++)
    {
      $res_type = db_execute (
        "SELECT name FROM group_type WHERE type_id = ?",
        [db_result ($result, $i, 'type')]
      );
      $return .= "<tr class=\"" . utils_altrow ($i)
        . '"><td><a href="' . $GLOBALS['sys_home'] . "people/$page?group_id="
        . db_result ($result, $i, 'group_id') . '&job_id='
        . db_result ($result, $i, 'job_id') . '">'
        . db_result ($result, $i, 'title') . "</a></td>\n<td>"
        . db_result ($result, $i, 'category_name') . "</td>\n<td>"
        . utils_format_date (db_result ($result,$i,'date'), 'natural')
        . "</td>\n<td><a href=\"" . $GLOBALS['sys_home'] . 'projects/'
        . strtolower (db_result ($result, $i, 'unix_group_name')) . '/">'
        . db_result ($result, $i, 'group_name') . "</a></td>\n<td>"
        . db_result ($res_type, 0, 'name') . "</td></tr>\n";
    }
  return $return . $tail;
}

# Show open jobs for this project.
function people_show_project_jobs ($group_id, $edit = 0)
{
  $result = db_execute ("
    SELECT
      j.group_id, j.job_id, g.group_name, g.unix_group_name, g.type, j.title,
      j.date, c.name AS category_name
    FROM people_job j, people_job_category c, groups g
    WHERE
      j.group_id = ?  AND j.group_id = g.group_id
      AND j.category_id = c.category_id AND j.status_id = 1
    ORDER BY date DESC",
    [$group_id]
  );
  return people_show_job_list ($result, $edit);
}

# Show open jobs for this project.
function people_project_jobs_rows ($group_id)
{
  $result = db_execute ("
    SELECT
      j.group_id, j.job_id, g.group_name,
      j.title, j.date, c.name AS category_name
    FROM people_job j, people_job_category c, groups g
    WHERE
      j.group_id = ?  AND j.group_id = g.group_id
      AND j.category_id = c.category_id AND j.status_id = 1
    ORDER BY date DESC",
    [$group_id]
  );
  return db_numrows ($result);
}

# Show open jobs for the given job categories and types of projects,
# or all open jobs when $categories and $types are empty.
function people_show_jobs ($categories, $types)
{
  $sql_args = [];
  $enum_ids =
    function ($id_arr, $field) use (&$sql_args)
    {
      if (empty ($id_arr))
        return '';
      $ids = $pref = '';
      foreach ($id_arr as $cat)
        {
          $ids .= $pref . $field . ' = ?';
          $pref = ' OR ';
          $sql_args[] = $cat;
        }
      return 'AND (' . $ids . ')';
    };
  $cat_ids = $enum_ids ($categories, 'j.category_id');
  $type_ids = $enum_ids ($types, 'groups.type');
  $result = db_execute ("
    SELECT
      j.group_id, j.job_id, j.title, j.date,
      groups.unix_group_name, groups.group_name, groups.type,
      c.name AS category_name
    FROM
      (people_job j JOIN people_job_category c
       ON j.category_id = c.category_id)
      JOIN groups ON j.group_id = groups.group_id
    WHERE groups.is_public = 1 AND j.status_id = 1
    ${cat_ids} ${type_ids} ORDER BY date DESC",
    $sql_args
  );
  return people_show_job_list ($result);
}

function people_skill_box ($name = 'skill_id', $checked = 'xyxy')
{
  global $PEOPLE_SKILL;
  if (!$PEOPLE_SKILL)
    $PEOPLE_SKILL = db_query ("SELECT * FROM people_skill ORDER BY name ASC");
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
    $PEOPLE_SKILL_LEVEL = db_query ("SELECT * FROM people_skill_level");
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
    $PEOPLE_SKILL_YEAR = db_query ("SELECT * FROM people_skill_year");
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
  if ($result && db_numrows ($result) > 0)
    {
      fb (_('ERROR - skill already in your inventory'));
      return;
    }
  $result = db_autoexecute ('people_skill_inventory',
    [
      'user_id' => user_getid (), 'skill_id' => $skill_id,
      'skill_level_id' => $skill_level_id, 'skill_year_id' => $skill_year_id
    ],
    DB_AUTOQUERY_INSERT
  );
  if ($result && db_affected_rows ($result) > 0)
    {
      fb (_('Added to skill inventory'));
      return;
    }
  fb (_('ERROR inserting into skill inventory'), 1);
  print db_error ();
}

function people_show_skill_inventory ($user_id)
{
  $result = db_execute ("
    SELECT
      s.name AS skill_name, l.name AS level_name, y.name AS year_name
    FROM
      people_skill_year y, people_skill_level l, people_skill s,
      people_skill_inventory i
    WHERE
      y.skill_year_id = i.skill_year_id
      AND l.skill_level_id = i.skill_level_id
      AND s.skill_id = i.skill_id
      AND i.user_id = ?",
    [$user_id]
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
    print '<tr><td><p class="warn">(' . _("No skill inventory set up")
      . ")</p>\n</td></tr>\n";
  else
    for ($i = 0; $i < $rows; $i++)
      print "<tr class=\"" . utils_altrow ($i) . "\">\n"
        . "<td>" . db_result ($result, $i, 'skill_name')
        . "</td>\n<td>" . db_result ($result, $i, 'level_name')
        . "</td>\n<td>" . db_result ($result, $i, 'year_name')
        . "</td></tr>\n";
  print "</table>\n";
}

function people_edit_skill_inventory ($user_id)
{
  $result = db_execute ("
    SELECT *, s.name AS skill_name
    FROM people_skill_inventory i, people_skill s
    WHERE user_id = ? AND s.skill_id = s.skill_id",
    [$user_id]
  );
  people_draw_skill_box ($result);
}
?>
