<?php
# Output tracker statistics.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2001, 2002 Laurent Julliard, CodeX Team, Xerox
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

foreach (['init', 'graphs', 'trackers/general', 'group'] as $i)
  require_once ("../include/$i.php");

exit_if_no_group ();

extract (sane_import ('get', ['name' => 'field']));

define ('REPORT_ENTRY_NUM', 8);

function reporting_list_key ($start, $end, $use_end)
{
  if ($use_end)
    return utils_format_date ($end);
  # TRANSLATORS: the arguments are dates.
  return sprintf (
    _('%1$s to %2$s'), utils_format_date ($start), utils_format_date ($end)
  );
}

function reporting_list_data ($group_id, $sql, $time_now, $use_end = false)
{
  $week = 604800;
  for ($counter = 1; $counter <= REPORT_ENTRY_NUM; $counter++)
    {
      $start = $time_now - $counter * $week;
      $end = $start + $week;
      $key = reporting_list_key ($start, $end, $use_end);
      if ($use_end)
        $start = $end;
      $result = db_execute ($sql, [$group_id, $start, $end]);
      $data[$key] = db_result ($result, 0, 0);
    }
  return $data;
}

function reporting_build_graph (
  $title, $data, &$widths, &$graph_id, $field = null, $total = 0
)
{
  $ret = '';
  if (!empty ($title))
    $ret = html_h (3, $title);
  $db_direct = $field !== null;
  list ($id, $wd, $output) =
    graphs_build ($data, $field, $db_direct, $total, $graph_id);
  if ($graph_id != $id)
    {
      $widths = "$widths,$wd";
      $graph_id = $id;
    }
  return "$ret$output";
}

# Give access to this page to anybody: people can already collect such
# information since they are able to browse the trackers.
# It does not make sense to restrict access to this data, in this spirit.
# But if some specific installation need to do so for whatever reason,
# we can make that a configuration option.

# If artifact is not defined, we want statistics of all trackers.
if (ARTIFACT == "project")
  $artifact = join (',', utils_get_dependable_trackers ());
else
  $artifact = ARTIFACT;

# Specific function that list possible report.
function specific_reports_list ($thisfield, $group_id)
{
  if ($thisfield)
    print "<p>&nbsp;</p>\n" . html_h (2, _("Other statistics:"));
  print "<ul>\n";

  if ($thisfield != 'aging')
    print "<li><a href=\"reporting.php?group_id=$group_id&amp;field=aging\">"
      # TRANSLATORS: aging statistics is statistics by date.
      . _("Aging Statistics") . "</a></li>\n";

  while ($field = trackers_list_all_fields ())
    {
      if (trackers_data_is_special ($field) || $field  == $thisfield)
        continue;

      if (trackers_data_is_select_box ($field) && trackers_data_is_used ($field))
        {
          print "<li><a href=\"reporting.php?group_id="
                ."$group_id&amp;field=$field\">";
          # TRANSLATORS: the argument is field label.
          printf (_("Statistics by '%s'"), trackers_data_get_label ($field));
          print "</a></li>\n";
        }
    }
  print "</ul>\n";
}

# Initialize the global data structure before anything else.
trackers_init ($group_id);

$page = "";
$graph_id = 0;
$widths = "";

function finish_page ()
{
  global $field, $group_id, $widths, $page;

  $css = "";
  if ($widths != '')
    $css = '/css/graph-widths.php?widths=' . substr ($widths, 1);

  trackers_header (["title" => _("Statistics"), "css" => $css]);
  print $page;
  specific_reports_list ($field, $group_id);
  trackers_footer ();
  exit (0);
}

if (!$field)
  finish_page ();

if ($field == 'aging')
  {
    $cond =
      "`group_id` = ? AND `date` >= ? AND `date` <= ? AND `spamscore` < 5";
    # TRANSLATORS: aging statistics is statistics by date.
    $page .= html_h (2, _("Aging statistics:"));
    $time_now = time ();
    $sql = "
      SELECT IFNULL(ROUND(AVG((`close_date` - `date`) / 86400)), -1)
      FROM `$artifact` WHERE `close_date` > 0 AND $cond";
    $total = $data = reporting_list_data ($group_id, $sql, $time_now);
    foreach (array_keys ($total) as $k)
      $total[$k] = $data[$k] >= 0 ? null: -1;

    $page .= reporting_build_graph (
      _("Average Turnaround Time for Closed Items"),
      $data, $widths, $graph_id, null, $total
    );

    $page .= "<p>&nbsp;&nbsp;</p>\n";
    $sql = "SELECT count(*) FROM `$artifact` WHERE $cond";
    $data = reporting_list_data ($group_id, $sql, $time_now);
    $page .= reporting_build_graph (
      _("Number of Items Opened"), $data, $widths, $graph_id
    );

    $page .= "<p>&nbsp;&nbsp;</p>\n";
    $sql = "
      SELECT count(*) FROM `$artifact`
      WHERE `group_id` = ? AND `date` <= ? AND `spamscore` < 5
        AND (`close_date` >= ? OR `close_date` < 1 OR `close_date` is NULL)";
    $total = $data = reporting_list_data ($group_id, $sql, $time_now, true);
    foreach (array_keys ($data) as $k)
      $total[$k] = null;

    $page .= reporting_build_graph (
      _("Number of Items Still Open"), $data, $widths, $graph_id,
      null, $total
    );
    $page .= "<p>&nbsp;&nbsp;</p>\n";
    finish_page ();
  } # if ($field == 'aging')

# It's any of the select box field.
$label = trackers_data_get_label ($field);

# Title + field description.
# TRANSLATORS: the argument is field label.
$page .= html_h (2, sprintf (_("Statistics by '%s':"), $label))
  . '<p><i>' . _('Field Description:') . '</i> '
  . trackers_data_get_description ($field) . "</p>\n";

# Make sure it is a correct field.
if (trackers_data_is_special ($field) || !trackers_data_is_used ($field)
    || !trackers_data_is_select_box ($field))
  {
    # TRANSLATORS: the argument is field label.
    $page .= '<p class="error">'
     . sprintf (_("Can't generate report for field %s"), $label)
     . "</p>\n";
    finish_page ();
  }

# First graph the bug distribution for Open items only.
# Assigned to must be handled in a specific way.
# Meaningless in case of status field.
if ($field != 'status_id')
  {
    $page .= "\n" . html_h (3, _("Open Items"));

    if ($field == 'assigned_to')
      {
        $sql = "
          SELECT `user_name`, count(*) AS `cnt`
          FROM `user` `u` JOIN `$artifact` `ar` ON `user_id` = `assigned_to`
          WHERE `group_id` = ? AND `status_id` = '1' AND `ar`.`spamscore` < 5
          GROUP BY `user_name`";
        $params = [$group_id];
      }
    else
      {
        # Check if the project has its own instance of the value set.
        $result = db_execute ("
          SELECT `value` FROM `{$artifact}_field_value`
          WHERE `bug_field_id` = ? AND `group_id` = ?",
          [trackers_data_get_field_id ($field), $group_id]
        );
        # When the group does not have its own instance, use the default one.
        $gid = GROUP_NONE;
        if (db_numrows ($result))
          $gid = $group_id;

        $sql = "
          SELECT `value`, count(*) AS `cnt`
          FROM
            `{$artifact}_field_value` `fv` JOIN `{$artifact}` `ar`
            ON `fv`.`value_id` = `ar`.`$field`
          WHERE `spamscore` < 5
            AND `fv`.`bug_field_id` = ? AND `fv`.`group_id` = ?
            AND `ar`.`group_id` = ? AND `ar`.`status_id` = '1'
          GROUP BY `value` ORDER BY `order_id`";
        $params = [trackers_data_get_field_id ($field), $gid, $group_id];
      }

    $result = db_execute ($sql, $params);
    if (db_numrows ($result) > 0)
      $page .= reporting_build_graph (null, $result, $widths, $graph_id, $field);
    else
      $page .= _("No item found.");
    $page .= "<p>&nbsp;&nbsp;</p>\n";
   }

#Second  graph the bug distribution for all items.
$page .= "\n" . html_h (3, _("All Items"));

if ($field == 'assigned_to')
  {
    $sql = "
      SELECT `user_name`, count(*) AS `cnt`
      FROM `user`, `$artifact` `ar`
      WHERE
        `user_id` = `assigned_to` AND `group_id` = ? AND `ar`.`spamscore` < 5
      GROUP BY `user_name`";
    $params = [$group_id];
  }
else
  {
    $result = db_execute ("
      SELECT `value` FROM `{$artifact}_field_value`
      WHERE `bug_field_id` = ? AND `group_id` = ?",
      [trackers_data_get_field_id ($field), $group_id]
    );
    $gid = GROUP_NONE;
    if (db_numrows ($result))
      $gid = $group_id;

    $sql = "
      SELECT `value`, count(*) AS `cnt`
      FROM
        `{$artifact}_field_value` `fv` JOIN $artifact `ar`
        ON `fv`.`value_id` = `ar`.`$field`
      WHERE `fv`.`bug_field_id` = ?  AND `fv`.`group_id` = ?
        AND `ar`.`group_id` = ? AND `spamscore` < 5
      GROUP BY `value` ORDER BY `order_id`";
    $params = [trackers_data_get_field_id ($field), $gid, $group_id];
  }
$result = db_execute ($sql, $params);
if (db_numrows ($result) > 0)
  $page .= reporting_build_graph (null, $result, $widths, $graph_id, $field);
else
  $page .= _("No item found. This field is probably unused");

finish_page ();
?>
