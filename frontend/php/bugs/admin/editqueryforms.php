<?php
# Edit query forms.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2001, 2002 Laurent Julliard, CodeX Team, Xerox
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
require_once ('../../include/trackers/general.php');
extract (sane_import ('request', ['digits' => 'report_id']));
extract (sane_import ('get', ['true' => ['show_report', 'new_report']]));
$names = [];
$submits = [
  'post_changes', 'set_default', 'create_report', 'update_report',
  'delete_report'
];
$names['true'] = $submits;
$names['specialchars'] = ['rep_name', 'rep_desc'];

$prefices = ['TFSRCH', 'TFREP', 'TFCW', 'CBSRCH', 'CBREP'];
$suffices = [
  'bug_id', 'submitted_by', 'date', 'close_date', 'planned_starting_date',
  'planned_close_date', 'category_id', 'priority', 'resolution_id',
  'privacy', 'vote', 'percent_complete', 'assigned_to', 'status_id',
  'discussion_lock', 'hours', 'summary', 'details', 'severity',
  'bug_group_id', 'originator_name', 'originator_email', 'originator_phone',
  'release', 'release_id', 'category_version_id', 'platform_version_id',
  'reproducibility_id', 'size_id', 'fix_release_id', 'comment_type_id',
  'plan_release_id', 'component_version', 'fix_release', 'plan_release',
  'keywords', 'updated'
];

$custom_suff = ['tf' => 10, 'ta' => 10, 'sb' => 10, 'df' => 5];

foreach ($custom_suff as $suf => $num)
  for ($i = 1; $i <= $num; $i++)
    $suffices[] = "custom_$suf$i";

$names['digits'] = [];
foreach ($prefices as $pref)
  foreach ($suffices as $suf)
    $names['digits'][] = "{$pref}_$suf";

extract (sane_import ('post', $names));
form_check ($submits);
# HELP: what we call now "query form" was previously called "report",
# that name is still in the database.

if (!$group_id)
  exit_no_group ();

if (!user_ismember ($group_id, MEMBER_FLAGS_ADMIN))
  exit_permission_denied ();

# Initialize global bug structures.
trackers_init ($group_id);

function rep_label ($suff, $label)
{
  return "<span class='preinput'>" . html_label ("rep_$suff", $label)
    . "</span><br />\n";
}

function rep_name_input ($val)
{
  return rep_label ('name', _("Name:"))
    . form_input ('text', 'rep_name', $val, "size='20' maxlength='20'");
}

function rep_desc_input ($val)
{
  return rep_label ('desc', _("Description:"))
    . form_input ('text', 'rep_desc', $val, "size='50' maxlength='120'");
}

function print_field_use_as_output ($field, $td)
{
  global $cb_report, $cb_report_chk, $tf_report, $tf_report_val;

  # If the current field is item id, we force it's presence on
  # the report with rank 0. This field is mandatory: otherwise
  # some links would be broken or there would be even no links.
  if ($field == 'bug_id')
    {
      print "\n$td" . form_hidden ([$cb_report => 1, $tf_report => 0])
        . "X</td>\n{$td}0</td>\n";
      return;
    }
  print "\n$td"
    . form_checkbox (
        $cb_report, $cb_report_chk, ['title' => _("Use as an Output Column")]
      )
    . "</td>\n$td"
    . form_input ('text', $tf_report, $tf_report_val,
        'title="' . _("Rank on Output") . "\" size='5' maxlen='5'"
      )
    . "</td>\n";
}

function print_field ($i, $field)
{
  global $cb_search, $cb_search_chk, $cb_attr, $rank_extra;
  global $tf_colwidth, $tf_colwidth_val, $tf_search, $tf_search_val;

  $td = '<td align="center">';
  print '<tr class="' . utils_altrow ($i) . '">';
  print "\n<td>" . trackers_data_get_label ($field)
    . "</td>\n<td>" . trackers_data_get_description ($field)
    . "</td>\n$td" . form_checkbox ($cb_search, $cb_search_chk, $cb_attr)
    . "</td>\n$td"
    . form_input ('text', $tf_search, $tf_search_val,
        'title="' . _("Rank on Search") . "\" size='5' maxlen='5' $rank_extra"
      )
    . "</td>\n";

  print_field_use_as_output ($field, $td);
  print "\n$td"
    . form_input ("text", $tf_colwidth, $tf_colwidth_val,
        'title="' . _("Column width (optional)") . "\" size='5' maxlen='5'"
      )
    . "</td>\n</tr>\n";
}

$def_query = group_get_preference ($group_id, ARTIFACT . "_default_query");
if ($def_query === false)
  $def_query = 100;

if ($set_default)
  {
    if (empty ($report_id))
      $report_id = $def_query;
    if ($def_query != $report_id)
      group_set_preference ($group_id, ARTIFACT . "_default_query", $report_id);
    $def_query = $report_id;
  }

if ($post_changes)
  {
    if ($update_report)
      {
        # Update report name and description and delete old report entries.
        $res = db_execute ("
          DELETE FROM " . ARTIFACT . "_report_field WHERE report_id = ?",
          [$report_id]
        );
        $res = db_autoexecute (
          ARTIFACT . '_report',
          [
            'name' => $rep_name, 'description' => $rep_desc, 'scope' => 'P'
          ],
          DB_AUTOQUERY_UPDATE, "report_id = ?", [$report_id]
        );
      }
    elseif ($create_report)
      {
        # Create a new report entry.
        $res = db_autoexecute (
          ARTIFACT . '_report',
          [
            'group_id' => $group_id, 'user_id' => user_getid (),
            'name' => $rep_name, 'description' => $rep_desc, 'scope' => 'P',
          ],
          DB_AUTOQUERY_INSERT
        );
        $report_id = db_insertid ($res);
      }

    # And now insert all the field entries in the trackers_report_field table.
    $sql = '
      INSERT INTO ' . ARTIFACT . '_report_field
        (
          report_id, field_name, show_on_query, show_on_result, place_query,
          place_result, col_width
        )
       VALUES ';
    $params = [];
    while ($field = trackers_list_all_fields ())
      {
        if ($field == 'group_id' || $field == 'comment_type_id')
          continue;

        $cb_search = "CBSRCH_$field";
        $cb_report = "CBREP_$field";
        $tf_search = "TFSRCH_$field";
        $tf_report = "TFREP_$field";
        $tf_colwidth = "TFCW_$field";

        if ($$cb_search || $$cb_report || $$tf_search || $$tf_report)
          {
            $cb_search_val = ($$cb_search? 1: 0);
            $cb_report_val = ($$cb_report? 1: 0);
            $tf_search_val = ($$tf_search? $$tf_search: null);
            $tf_report_val = ($$tf_report? $$tf_report: null);

            $tf_colwidth_val = null;
            if (
              array_key_exists ($tf_colwidth, get_defined_vars ())
              && $$tf_colwidth
            )
              $tf_colwidth_val = $$tf_colwidth;
            $sql .= "(?, ?, ?, ?, ?, ?, ?),";
            $params = array_merge (
              $params,
              [
                $report_id, $field, $cb_search_val, $cb_report_val,
                $tf_search_val, $tf_report_val, $tf_colwidth_val
              ]
            );
          }
      }
    $sql = substr ($sql, 0, -1);

    $res = db_execute ($sql, $params);
    $fb_name = utils_specialchars_decode ($rep_name, ENT_QUOTES);
    if ($res)
      {
        if ($create_report)
          fb (sprintf (_("Query form '%s' created successfully"), $fb_name));
        else
          fb (sprintf (_("Query form '%s' updated successfully"), $fb_name));
      }
    else
      {
        if ($create_report)
          fb (sprintf (_("Failed to create query form '%s'"), $fb_name), 1);
        else
          fb (sprintf (_("Failed to update query form '%s'"), $fb_name), 1);
      }
  } # if ($post_changes)
elseif ($delete_report)
  {
    group_add_history (
     'Deleted query form', ARTIFACT . ", form \"$rep_name\"", $group_id
    );

    db_execute  (
      "DELETE FROM " . ARTIFACT . "_report WHERE report_id = ?",
      [$report_id]
    );
    db_execute (
      "DELETE FROM " . ARTIFACT . "_report_field WHERE report_id = ?",
      [$report_id]
    );
  }

$title_arr = [
  _("Field Label"), _("Description"), _("Use as a Search Criterion"),
  _("Rank on Search"), _("Use as an Output Column"), _("Rank on Output"),
  _("Column width (optional)"),
];

if ($new_report)
  {
    trackers_header_admin (['title' => _("Create a New Query Form")]);

    print form_tag ();
    print form_hidden (
      ["create_report" => "y", "group_id" => $group_id, "post_changes" => "y"]
    );
    print '<p>' . rep_name_input ('') . "\n</p>\n";
    print "<p><span class='preinput'>" . _("Scope:") . "</span><br />\n"
      . _("Group");
    print "</p>\n<p>" . rep_desc_input ('') . "\n</p>\n";
    print html_build_list_table_top ($title_arr);
    $i = 0;
    while ($field = trackers_list_all_fields ())
      {
        # Do not show fields not used by the project.
        if (!trackers_data_is_used ($field))
          continue;

        # Do not show some special fields any way.
        if (trackers_data_is_special ($field))
          {
            if (($field == 'group_id') || ($field == 'comment_type_id'))
              continue;
          }

        $cb_search = "CBSRCH_$field";
        $cb_report = "CBREP_$field";
        $tf_search = "TFSRCH_$field";
        $tf_report = "TFREP_$field";
        $tf_colwidth = "TFCW_$field";

        # For the rank values, set defaults, for the common fields, as
        # it gets easily messy when not specified.
        $tf_report_val = 100;

        # Summary should be just after the item id.
        if ($field == 'summary')
          $tf_report_val = 5;
        # Statis should just after.
        if ($field == 'resolution_id')
          $tf_report_val = 10;
        # Moderately important fields.
        if ($field == 'category_id' || $field == 'severity' || $field == 'vote')
          $tf_report_val = 25;
        # Very moderately important fields.
        if ($field == 'submitted_by' || $field == 'assigned_to')
          $tf_report_val = 50;

        $cb_attr = ['title' => _("Use as a Search Criterion")];
        $rank_extra = '';
        if ($field == 'updated')
          {
            $cb_attr['disabled'] = 'disabled';
            $rank_extra = " disabled='disabled'";
          }
        $cb_report_chk = 0; $tf_colwidth_val = '';
        print_field ($i++, $field);
      }
    print "</table>\n<p><center><input type='submit' name='submit' value=\""
      . _('Submit') . "\" /></center></p>\n</form>\n";
    trackers_footer ();
    exit (0);
  } # if ($new_report)

if ($show_report)
  {
    trackers_header_admin (['title' => _("Modify a Query Form")]);

    # Fetch the report to update.
    $res = db_execute (
      "SELECT * FROM " . ARTIFACT . "_report WHERE report_id = ?",
      [$report_id]
    );
    if (!db_numrows ($res))
      {
        # TRANSLATORS: the argument is report id (a number).
        exit_error (sprintf (_("Unknown Report ID (%s)"), $report_id));
      }

    $res_fld = db_execute (
      "SELECT * FROM " . ARTIFACT . "_report_field WHERE report_id = ?",
      [$report_id]
    );

    # Build the list of fields involved in this report.
    while ($arr = db_fetch_array ($res_fld))
      $fld[$arr['field_name']] = $arr;

    print form_tag ()
      . form_hidden ([
          "update_report" => "y", "group_id" => $group_id,
          "report_id" => $report_id, "post_changes" => "y"
        ]);
    print '<p>' . rep_name_input (db_result ($res, 0, 'name')) . "\n</p>\n";
    print "<p>" . rep_desc_input (db_result ($res, 0, 'description'))
      . "\n</p>\n";

    print html_build_list_table_top ($title_arr);
    $i = 0;
    while ($field = trackers_list_all_fields ())
      {
        # Do not show fields not used by the project.
        if (!trackers_data_is_used ($field))
          continue;

        # Do not show some special fields any way.
        if (trackers_data_is_special ($field))
          {
            if ($field == 'group_id' || $field == 'comment_type_id')
              continue;
          }

        $cb_search = "CBSRCH_$field";
        $cb_report = "CBREP_$field";
        $tf_search = "TFSRCH_$field";
        $tf_report = "TFREP_$field";
        $tf_colwidth = "TFCW_$field";

        $ff = [];
        if (array_key_exists ($field, $fld))
          $ff = $fld[$field];
        $cb_search_chk = !empty ($ff['show_on_query']);
        $cb_report_chk = !empty ($ff['show_on_result']);
        foreach (
          [
            'search' => 'place_query', 'report' => 'place_result',
            'colwidth' => 'col_width',
          ] as $k => $v
        )
          ${"tf_{$k}_val"} = (empty ($ff[$v])? '': $ff[$v]);

        $cb_attr = ['title' => _("Use as a Search Criterion")];
        $rank_extra = '';
        if ($field == 'updated')
          {
            $cb_search_chk = 0;
            $cb_attr['disabled'] = 'disabled';
            $rank_extra = " disabled='disabled'";
            $tf_search_val = '';
          }
        print_field ($i++, $field);
      }
    print "</table>\n"
      . '<p><center><input type="submit" name="submit" value="'
      . _("Submit") . "\" /></center></p>\n</form>\n";
    trackers_footer ();
    exit (0);
  } # if ($show_report)

trackers_header_admin (['title' => _("Edit Query Forms")]);

print form_tag (["name" => 'default_query'])
  . form_hidden (["group_id" => $group_id, "set_default" => "y"]);

$res_report = trackers_data_get_reports ($group_id, user_getid ());
$form_query_type = html_build_select_box (
  $res_report, 'report_id', $def_query, true, _('Basic'), false, 'Any', false,
  _('query form')
);

printf (
  _("Browse with the %s query form by default.") . "\n",
  $form_query_type
);
print '<input class="bold" value="' . _("Apply")
  . "\" name='go_report' type='submit' />\n</form>\n";

$res = db_execute ("
  SELECT * FROM " . ARTIFACT . "_report
  WHERE group_id = ? AND (user_id = ? OR scope = 'P')",
  [$group_id, user_getid ()]
);

if (db_numrows ($res))
  {
    print html_h (2, _("Existing Query Forms"));
    print
      html_build_list_table_top (
        [
          _("ID"), _("Query form name"), _("Description"), _("Scope"),
          _("Delete"),
        ]
      );
    $i = 0;
    while ($arr = db_fetch_array ($res))
      {
        print '<tr class="' . utils_altrow ($i++) . '"><td>';

        $url = $php_self
          . "?group=$group&show_report=1&report_id={$arr['report_id']}";
        print "<a href=\"$url\">{$arr['report_id']}</a></td>\n";
        print "<td><a href=\"$url\">{$arr['name']}</a></td>\n";
        print "\n<td>{$arr['description']}</td>\n"
          . "\n<td align=\"center\">"
          . (($arr['scope'] == 'P')? _("Group"): _("Personal")) . '</td>'
          . "\n<td align=\"center\">";

        print form_tag ()
          . form_hidden([
              'delete_report' => 1, 'report_id' => $arr['report_id'],
              'group' => $group, 'rep_name' => utils_urlencode ($arr['name'])
            ])
          . form_image_trash ('del_rep') . "</form>\n";
        print "</td>\n</tr>\n";
      }
    print "</table>\n";
  }
else
  print '<p>' . _("No query form defined yet.") . "</p>\n";

print '<p>';
printf (
  _("You can <a href=\"%s\"> create a new query form</a>."),
   "$php_self?group=$group&new_report=1"
);
print "</p>\n";
trackers_footer ();
?>
