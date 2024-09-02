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
if (!$group_id)
  exit_no_group ();

if (!user_isloggedin ())
  exit_permission_denied ();

$is_admin = user_ismember ($group_id, MEMBER_FLAGS_ADMIN);
$editable_reports = trackers_data_get_editable_reports ($group_id, $is_admin);
$reports_updated = false;

$names = [];
$submits = [
  'post_changes', 'create_report', 'update_report', 'delete_report', 'copy'
];
if ($is_admin)
  $submits[] = 'set_default';
$names['true'] = $submits;
$names['specialchars'] = ['rep_name', 'rep_desc'];
$names['strings'] = [['rep_scope', scopes_sanitized ($is_admin, $group_id)]];

$prefixes = [
  'tf_search' => 'TFSRCH', 'tf_report' => 'TFREP', 'tf_colwidth' => 'TFCW',
  'cb_search' => 'CBSRCH', 'cb_report' => 'CBREP'
];
$suffixes = [
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
    $suffixes[] = "custom_$suf$i";

$names['digits'] = ['rep_copy_from'];
foreach ($prefixes as $pref)
  foreach ($suffixes as $suf)
    $names['digits'][] = "{$pref}_$suf";

extract (sane_import ('post', $names));
form_check ($submits);
# Historical note: what we call "query form" was previously called "report",
# that name is still in the database.

# Initialize global bug structures.
trackers_init ($group_id);

function check_copy_from ($report_id)
{
  global $copy, $rep_copy_from;
  if (empty ($copy) || empty ($rep_copy_from))
    return $report_id;
  return $rep_copy_from;
}

function fetch_reports ($group_id)
{
  $ret = [];
  $res = trackers_data_get_reports ($group_id, user_getid ());
  while ($row = db_fetch_array ($res))
    $ret[$row['report_id']] = $row;
  return $ret;
}

function rep_label ($suff, $label)
{
  return "<span class='preinput'>" . html_label ("rep_$suff", $label)
    . "</span>&nbsp;";
}

function rep_scope_input ($scope = null)
{
  global $is_admin, $group_id;
  if ($scope === null)
    $scope = scopes_sanitized ($is_admin, $group_id) ['default'];
  $vals = scopes_available ($is_admin, $group_id);
  $label = rep_label ('scope', _("Scope:"));
  if (count ($vals) > 1)
    {
      $texts = [];
      foreach ($vals as $v)
        $texts[] = scope_label ($v);
      $ret = html_build_select_box_from_arrays (
        $vals, $texts, 'rep_scope', $scope, false
      );
    }
  else
    $ret = scope_label ($scope) . form_hidden (['rep_scope' => $scope]);
  return "<p>$label&nbsp;$ret</p>\n";
}

function print_copyable_reports ()
{
  global $group_id, $report_list;
  print rep_label ('copy_from', _('Copy from query form:'));
  $vals = $texts = [];
  foreach ($report_list as $v => $row)
    {
      $vals[] = $v;
      $texts[] = $row['name'];
    }
  print html_build_select_box_from_arrays (
    $vals, $texts, 'rep_copy_from', 'x', false
  );
  print "\n&nbsp;" . form_submit (_('Copy'), 'copy') . "\n";
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
  global $cb_search_chk, $cb_attr, $rank_extra, $cb_search;
  global $tf_colwidth_val, $tf_colwidth, $tf_search_val, $tf_search;
  global $cb_report, $cb_report_chk, $tf_report, $tf_report_val;
  extract (report_common_field_names ($field));

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

function scope_list ()
{
  return ['P' => _('Group'), 'S' => _('System'), 'I' => _('Personal')];
}

function scopes_available ($is_admin, $group_id)
{
  if ($group_id == GROUP_NONE)
    return ['S'];
  $ret = ['I'];
  if ($is_admin)
    $ret[] = 'P';
  return $ret;
}

function scopes_sanitized ($is_admin, $group_id)
{
  $ret = scopes_available ($is_admin, $group_id);
  $d = array_pop ($ret);
  $ret['default'] = $d;
  return $ret;
}

function scope_label ($s)
{
  $s = strtoupper ($s);
  $scopes = scope_list ();
  if (array_key_exists ($s, $scopes))
    return $scopes[$s];
  return "[$s]";
}

function print_default_query_input ($report_list)
{
  global $group_id, $def_query, $is_admin;
  if ($group_id == GROUP_NONE || !$is_admin)
    return;
  print form_tag (["name" => 'default_query'])
    . form_hidden (["group_id" => $group_id, "set_default" => "y"]);

  # The default query form is selected from the form queries available
  # for anonumous users.
  $texts = $vals =  [];
  foreach ($report_list as $id => $row)
    if ($row['scope'] != 'I')
      {
        $vals[] = $id; $texts[] = $row['name'];
      }
  $form_query = html_build_select_box_from_arrays (
    $vals, $texts, 'report_id', $def_query, true, _('Basic')
  );
  printf (_("Browse with the %s query form by default.") . "\n", $form_query);
  print form_submit (_("Apply"), 'go_report', 'class="bold"') . "\n</form>\n";
}

function delete_report ($report_id)
{
  global $editable_reports, $rep_name, $group_id, $reports_updated;
  if (empty ($editable_reports[$report_id]))
    return;
  $reports_updated = true;
  $a = ARTIFACT;

  group_add_history (
   'Deleted query form', "$a, form #$report_id \"$rep_name\"", $group_id
  );
  db_execute  ("DELETE FROM {$a}_report WHERE report_id = ?", [$report_id]);
  db_execute (
    "DELETE FROM {$a}_report_field WHERE report_id = ?", [$report_id]
  );
}

function create_report ()
{
  global $group_id, $rep_name, $rep_desc, $rep_scope, $is_admin;
  global $reports_updated;
  if (!in_array ($rep_scope, scopes_available ($is_admin, $group_id)))
    return null;
  $reports_updated = true;
  $res = db_autoexecute (
    ARTIFACT . '_report',
    [
      'group_id' => $group_id, 'user_id' => user_getid (),
      'name' => $rep_name, 'description' => $rep_desc,
      'scope' => $rep_scope
    ],
    DB_AUTOQUERY_INSERT
  );
  return db_insertid ($res);
}

function pre_update_report ()
{
  global $editable_reports, $rep_name, $rep_scope, $rep_desc, $report_id;
  global $reports_updated;
  if (empty ($editable_reports[$report_id]))
    return null;
  $reports_updated = true;
  $res = db_execute ("
    DELETE FROM " . ARTIFACT . "_report_field WHERE report_id = ?",
    [$report_id]
  );
  $res = db_autoexecute (
    ARTIFACT . '_report',
    [
      'name' => $rep_name, 'description' => $rep_desc,
      'scope' => $rep_scope
    ],
    DB_AUTOQUERY_UPDATE, "report_id = ?", [$report_id]
  );
  return $report_id;
}

function update_res_msg ($res)
{
  global $create_report;
  if ($res)
    {
      if ($create_report)
        return [_("Query form '%s' created successfully"), 0];
      return [_("Query form '%s' updated successfully"), 0];
    }
  if ($create_report)
    return [_("Failed to create query form '%s'"), 1];
  return [_("Failed to update query form '%s'"), 1];
}

function report_update_result ($res, $rep_name)
{
  $fb_name = utils_specialchars_decode ($rep_name, ENT_QUOTES);
  list ($msg, $err) = update_res_msg ($res);
  fb (sprintf ($msg, $fb_name), $err);
}

function report_common_field_names ($field)
{
  $ret = [];
  foreach ($GLOBALS['prefixes'] as $var => $pref)
    $ret[$var] = "{$pref}_$field";
  return $ret;
}

function report_update_field_params ($report_id, $field)
{
  if ($field == 'group_id' || $field == 'comment_type_id')
    return null;
  extract (report_common_field_names ($field));
  global $$cb_search, $$cb_report, $$tf_search, $$tf_report, $$tf_colwidth;
  if (!($$cb_search || $$cb_report || $$tf_search || $$tf_report))
    return null;
  $cb_search_val = $$cb_search? 1: 0;
  $cb_report_val = $$cb_report? 1: 0;
  $tf_search_val = $$tf_search? $$tf_search: null;
  $tf_report_val = $$tf_report? $$tf_report: null;

  $tf_colwidth_val = null;
  if (!empty ($$tf_colwidth))
    $tf_colwidth_val = $$tf_colwidth;
  return [$report_id, $field, $cb_search_val, $cb_report_val,
    $tf_search_val, $tf_report_val, $tf_colwidth_val
  ];
}

function report_update_params ($report_id)
{
  $cnt = 0;
  $params = [];
  while ($field = trackers_list_all_fields ())
    {
      $fp = report_update_field_params ($report_id, $field);
      if ($fp === null)
        continue;
      $cnt++;
      $params = array_merge ($params, $fp);
    }
  return [utils_str_join (",\n", '(?, ?, ?, ?, ?, ?, ?)', $cnt), $params];
}

function update_fields ($report_id, $rep_name)
{
  if ($report_id === null)
    return;
  $sql = '
    INSERT INTO ' . ARTIFACT . '_report_field
      (
        report_id, field_name, show_on_query, show_on_result, place_query,
        place_result, col_width
      )
    VALUES ';
  list ($sql_tail, $params) = report_update_params ($report_id);
  $res = db_execute ("$sql$sql_tail", $params);
  report_update_result ($res, $rep_name);
}

function report_data_field_is_shown ($field)
{
  # Do not show fields not used in the tracker.
  if (!trackers_data_is_used ($field))
    return false;

  # Never show some special fields.
  if (trackers_data_is_special ($field))
    if (($field == 'group_id') || ($field == 'comment_type_id'))
      return false;
  return true;
}

function report_default_rank ($field)
{
  $def_vals = [
    'summary' => 5, 'resolution_id' => 10, 'category_id' => 25,
    'severity' => 25, 'vote' => 25, 'submitted_by' => 50, 'assigned_to' => 50
  ];
  if (empty ($def_vals[$field]))
    return 100;
  return $def_vals[$field];
}

function fetch_report ($tbl, $report_id)
{
  if (empty ($report_id))
    return null;
  $res = db_execute ("SELECT * FROM $tbl WHERE report_id = ?", [$report_id]);
  if (db_numrows ($res))
    return db_fetch_array ($res);
  # TRANSLATORS: the argument is report id (a number).
  exit_error (sprintf (_("Unknown Report ID (%s)"), $report_id));
}

function fetch_report_data ($report_id, $fld_report_id)
{
  $tbl = ARTIFACT . '_report';
  $rep = fetch_report ($tbl, $report_id);
  $res_fld = db_execute (
    "SELECT * FROM {$tbl}_field WHERE report_id = ?", [$fld_report_id]
  );
  $ret = [];
  while ($row = db_fetch_array ($res_fld))
    $ret[$row['field_name']] = $row;
  return [$ret, $rep];
}

function extract_report_name_description ($row)
{
  global $copy;
  if (!empty ($row))
    {
      $row['desc'] = $row['description'];
      return $row;
    }
  $row = ['name' => '', 'desc' => '', 'scope' => null];
  if (empty ($copy))
    return $row;
  foreach (array_keys ($row) as $k)
    if (!empty ($GLOBALS["rep_$k"]))
      $row[$k] = $GLOBALS["rep_$k"];
  return $row;
}

function print_mod_report_header ($title, $hidden, $title_arr, $row = null)
{
  $hid = array_merge (['post_changes' => 'y'], $hidden);
  trackers_header_admin (['title' => $title]);
  print form_tag () . form_hidden ($hid);
  $row = extract_report_name_description ($row);
  $name = utils_specialchars_decode ($row['name'], ENT_QUOTES);
  $desc = utils_specialchars_decode ($row['desc'], ENT_QUOTES);
  print '<p>' . rep_label ('name', _("Name:"))
    . form_input ('text', 'rep_name', $name, "size='20' maxlength='20'")
    . "</p>\n";
  print rep_scope_input ($row['scope']);
  print '<p>' . rep_label ('desc', _("Description:"))
    . form_input ('text', 'rep_desc', $desc, "size='50' maxlength='120'")
    . "</p>\n";
  print_copyable_reports ();
  print html_build_list_table_top ($title_arr);
}

function copy_tf_vals ($ff)
{
  foreach (
    [
      'search' => 'place_query', 'report' => 'place_result',
      'colwidth' => 'col_width',
    ] as $k => $v
  )
    {
      global ${"tf_{$k}_val"};
      ${"tf_{$k}_val"} = empty ($ff[$v])? '': $ff[$v];
    }
}

function fixup_updated_vals ()
{
  global $cb_search_chk, $cb_report_chk, $cb_attr, $rank_extra;
  $cb_search_chk = 0;
  $cb_attr['disabled'] = 'disabled';
  $rank_extra = " disabled='disabled'";
  $tf_search_val = '';
}

function copy_field_vals ($fld, $field)
{
  global $cb_search_chk, $cb_report_chk;
  $ff = [];
  if (array_key_exists ($field, $fld))
    $ff = $fld[$field];
  $cb_search_chk = !empty ($ff['show_on_query']);
  $cb_report_chk = !empty ($ff['show_on_result']);
  copy_tf_vals ($ff);
}

function show_mod_field ($field, $i, $fld = null)
{
  global $cb_report_chk, $cb_attr, $rank_extra;
  global $tf_report_val, $tf_colwidth_val;
  if (!report_data_field_is_shown ($field))
    return;
  extract (report_common_field_names ($field));
  if ($fld !== null)
    copy_field_vals ($fld, $field);
  else
    $tf_report_val = report_default_rank ($field);

  $cb_attr = ['title' => _("Use as a Search Criterion")];
  $rank_extra = '';
  if ($field == 'updated')
    fixup_updated_vals ();
  if ($fld === null)
    {
      $cb_report_chk = 0; $tf_colwidth_val = '';
    }
  print_field ($i, $field);
}

function rep_header_params ($group_id, $report_id)
{
  $params = ['group_id' => $group_id];
  $button = 'create_report';
  if ($report_id)
    {
      $params['report_id'] = $report_id;
      $button = 'update_report';
    }
  $params[$button] = 'y';
  return $params;
}

function show_mod_report ($group_id, $title_arr, $report_id = 0)
{
  $title = $report_id? _("Modify a Query Form"): _("Create a New Query Form");
  $params = rep_header_params ($group_id, $report_id);
  $row = $fld = null;
  $fld_report_id = check_copy_from ($report_id);
  if ($report_id || $fld_report_id)
    list ($fld, $row) = fetch_report_data ($report_id, $fld_report_id);
  print_mod_report_header ($title, $params, $title_arr, $row);
  $i = 0;
  while ($field = trackers_list_all_fields ())
    show_mod_field ($field, $i++, $fld);
  print "</table>\n" . form_footer (false, 'submit');
  trackers_footer ();
  exit (0);
}

function list_report_to_edit ($row, $group, $i)
{
  global $php_self;
  print '<tr class="' . utils_altrow ($i) . '"><td>';

  $url = "$php_self?group=$group&show_report=1&report_id={$row['report_id']}";
  print "<a href=\"$url\">{$row['report_id']}</a></td>\n";
  print "<td><a href=\"$url\">{$row['name']}</a></td>\n";
  print "\n<td>{$row['description']}</td>\n"
    . "\n<td align=\"center\">" . scope_label ($row['scope'])
    . '</td>' . "\n<td align=\"center\">";

  print form_tag ()
    . form_hidden([
        'delete_report' => 1, 'report_id' => $row['report_id'],
        'group' => $group, 'rep_name' => utils_urlencode ($row['name'])
      ])
    . form_image_trash ('del_rep') . "</form>\n";
  print "</td>\n</tr>\n";
}

function list_editable_reports ($editable_reports, $group)
{
  if (empty ($editable_reports))
    {
      print '<p>' . _("No query form defined yet.") . "</p>\n";
      return;
    }
  print html_h (2, _("Existing Query Forms"));
  $titles = [
    _("ID"), _("Query form name"), _("Description"), _("Scope"), _("Delete")
  ];
  print html_build_list_table_top ($titles);
  $i = 0;
  foreach ($editable_reports as $row)
    list_report_to_edit ($row, $group, $i++);
  print "</table>\n";
}

$def_query = group_get_preference ($group_id, ARTIFACT . "_default_query");
if ($def_query === false)
  $def_query = 100;

if (!empty ($set_default))
  {
    if (empty ($report_id))
      $report_id = $def_query;
    if ($def_query != $report_id)
      group_set_preference ($group_id, ARTIFACT . "_default_query", $report_id);
    $def_query = $report_id;
  }

if ($copy)
  {
    # Do nothing, just pre-fill the controls with different values.
    if ($create_report)
      $new_report = true;
    elseif ($update_report)
      $show_report = true;
  }
elseif ($post_changes)
  {
    if ($update_report)
      $report_id = pre_update_report ();
    elseif ($create_report)
      $report_id = create_report ();
    update_fields ($report_id, $rep_name);
  } # if ($post_changes)
elseif ($delete_report)
  delete_report ($report_id);

$title_arr = [
  _("Field Label"), _("Description"), _("Use as a Search Criterion"),
  _("Rank on Search"), _("Use as an Output Column"), _("Rank on Output"),
  _("Column width (optional)"),
];
$report_list = fetch_reports ($group_id);
if ($new_report)
  show_mod_report ($group_id, $title_arr);

if ($show_report)
  show_mod_report ($group_id, $title_arr, $report_id);

trackers_header_admin (['title' => _("Edit Query Forms")]);
print_default_query_input ($report_list);
if ($reports_updated)
  $editable_reports = trackers_data_get_editable_reports ($group_id, $is_admin);

list_editable_reports ($editable_reports, $group);
print '<p>';
printf (
  _("You can <a href=\"%s\"> create a new query form</a>."),
   "$php_self?group=$group&new_report=1"
);
print "</p>\n";
trackers_footer ();
?>
