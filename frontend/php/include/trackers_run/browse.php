<?php
# List of tracker items, with various sorts & filters
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

# There are parameters that defined before, in the pages that include browse.

require_once (dirname (__FILE__) . '/../trackers/show.php');

$art = ARTIFACT;
extract (sane_import ('get',
  [
    'digits' =>
      [
        'report_id',
        ['msort', 'sumORdet', 'advsrch', 'history_search', [0, 1]],
        ['spamscore', [1, null]],
        ['history_date_yearfd', [1900, null]],
        ['history_date_monthfd', [1, 12]],
        ['history_date_dayfd', [1, 31]],
      ],
    'name' => 'history_field',
    'strings' =>
      [
        ['func', ['default' => 'browse', 'digest']],
        ['set', ['custom', 'open']],
        ['history_event', ['modified', 'not modified']]
      ],
    'preg' =>
      [
        ['history_date', '/^\d{4}-\d{1,2}-\d{1,2}$/'],
        ['order', '/^([_a-zA-Z-][_[:alnum:]-]*)?$/'],
        ['morder', '/^[,<>_[:alnum:]-]*$/']
      ],
  ]
));

html_nextprev_extract_params ();
# Number of search criteria (boxes) displayed in one row.
$fields_per_line = 5;

# Avoid undesired user input.
$browse_preamble = '';

# Digest mode? Set the digest variable to one.
$digest = $func == 'digest';

# Make sure offset is defined and has a correct value.
$offset = intval ($offset);

if ($history_field === null)
  $history_field = '0';

if ($morder === null)
  $morder = '';

$hdr = _('Browse Items');

# Make sure spamscore has a numeric value between 1 and 20
# (we will search for items that have score inferior to $spamscore).
# Default is 5, and only tracker admins can use other values.
if ($spamscore === null || !$is_trackeradmin)
  $spamscore = 5;
if ($spamscore > 20)
  $spamscore = 20;
$spamscore = intval ($spamscore);

# Get the list of bug fields used in the form (they are in the URL - GET
# method) and then build the preferences array accordingly.
# Exclude the group_id parameter# Extract the list of bug fields.
#
# NB: Note that trackers_extract_field_list function does build and
# return date arguments (using _dayfd|monthfd|yearfd boxes) whether
# or not they are tracker fields used by the project.

# $prefs renamed $url_params to avoid confusion with $pref_arr and $pref_stg
# used further down.
$url_params = trackers_extract_field_list (false);
unset ($url_params['group_id']);

# Get rid of url_params['history_date'] which has been included
# by trackers_extract_field_list.
unset ($url_params['history_date']);

# Make safe for inclusion in an URL (replace quotes with dots).
function sanitize_val ($x)
{
  if (is_string ($x))
    {
      $x = strtr ($x, '"\'', '..');
      return $x;
    }
  if (is_numeric ($x)) # Numbers are sane.
    return $x;
  return 'xxx'; # Some queer type fed.
}

function sanitize_field (&$x)
{
  if (is_array ($x))
    foreach ($x as $key => $val)
      $x[$key] = sanitize_val ($val);
  else
    $x = sanitize_val ($x);
  return $x;
}

function sanitize_value_id ($value_id)
{
  if (!is_array ($value_id))
    return [sanitize_field ($value_id)];
  $ret = [];
  foreach ($value_id as $key => $val)
    $ret[sanitize_field ($key)] = sanitize_field ($val);
  return $ret;
}

# Make sure all URL arguments are captured as array. For simple
# search they'll be arrays with only one element at index 0 (this
# will avoid to deal with scalar in simple search and array in
# advanced which would greatly complexifies the code).

foreach ($url_params as $field => $value_id)
  {
    unset ($url_params[$field]);
    sanitize_field ($field);
    $url_params[$field] = sanitize_value_id ($value_id);

    if (trackers_data_is_date_field ($field))
      {
        if ($advsrch)
          {
            $co_field = "{$field}_end";
            $names =
              ['preg' => [[$field, $co_field, '/^\d{4}-\d{1,2}-\d{1,2}$/']]];
          }
        else
          {
            $co_field = "{$field}_op";
            $names = ['strings' => [[$co_field, trackers_date_op_list ()]]];
          }
        $in = sane_import ('request', $names);
        $url_params[$co_field] = $in[$co_field];
        if ($advsrch && empty($in[$field]))
          unset ($url_params[$field]);
        if (!$advsrch && !$url_params[$co_field])
          $url_params[$co_field] = ['*'];
      }
  }

# If history event additional constraint is used, add it.
if ($history_search)
  # Dates must numeric date, even can be only modified or unmodified
  # If there is crap in there, ignore silently
  if ($history_date_yearfd !== null && $history_date_monthfd !== null
      && $history_date_dayfd !== null)
    {
      $history_date =
        "$history_date_yearfd-$history_date_monthfd-$history_date_dayfd";
      $url_params['history'][] =
        "$history_search>$history_field>$history_event>$history_date";
    }

# Memorize order by field as a user preference if explicitly specified.
#
# $morder = comma separated list of sort criteria followed by < for
#   DESC and > for ASC order
# $order = last sort criteria selected in the UI
# $msort = 1 if multicolumn sort activated.
#
# if morder not defined then reuse the one in preferences.
$order_pref = "{$art}_browse_order$group_id";
$report_pref = "{$art}_browse_report$group_id";
$cust_pref = "{$art}_brow_cust$group_id";
if (user_isloggedin () && !isset ($morder))
  $morder = user_get_preference ($order_pref);

if ($order !== null)
  {
    if (!in_array ($order, ['', 'digest']))
      # Add the criteria to the list of existing ones.
      $morder = trackers_add_sort_criteria ($morder, $order, $msort);
    else
      # Reset list of sort criteria.
      $morder = '';
  }

if ($morder != '' && user_isloggedin ())
  if ($morder != user_get_preference ($order_pref))
    user_set_preference ($order_pref, $morder);

# If the report type is not defined then get it from the user preferences.
# If it is set then update the user preference.  Also initialize the
# bug report structures.
if (user_isloggedin ())
  {
    if (!isset ($report_id))
      $report_id = user_get_preference ($report_pref);
    elseif ($report_id != user_get_preference ($report_pref))
      user_set_preference ($report_pref, $report_id);
  }

# If the report type is not defined then get it from group preferences.
if (!$report_id)
  $report_id = group_get_preference ($group_id, "{$art}_default_query");

if (!$report_id)
  $report_id = 100; # Fallback to 'Basic' report.

if (!trackers_report_init ($report_id))
  {
    fb (sprintf (_("Query form #%s doesn't exist"), $report_id), 1);
    # Fall back to 'Basic' report.
    $report_id = 100;
    trackers_report_init ($report_id);
  }

function parse_history_field ($val)
{
  global $history_search, $history_field, $history_event, $history_date;
  global $url_params;
  $pref = explode ('>', $val);
  if (count ($pref) != 4)
    return;
  list ($history_search, $history_field, $history_event, $history_date) = $pref;

  # If not args in URL (means not after post) ...
  # set $url_params['history'] explicitly since 'history'
  # is not a tracker field and thus won't be set.
  $url_params['history'][] = $val;
}

function apply_single_pref ($expr)
{
  global $max_rows, $url_params;

  $fld_val = explode ('=', $expr, 2);
  if (count ($fld_val) < 2)
    return;
  list ($field, $value) = $fld_val;
  $field = str_replace ('[]', '', $field);
  $global_fields = ['advsrch', 'msort', 'spamscore', 'report_id', 'sumORdet'];
  if (in_array ($field, $global_fields))
    $GLOBALS[$field] = $value;
  elseif (in_array ($field, ['chunksz', 'max_rows']))
    {
      $max_rows = intval ($value);
      if ($max_rows <= 0)
        $max_rows = 50;
    }
  elseif ($field == 'history')
    parse_history_field ($value);
  else
    $url_params[$field][] = $value;
}

function apply_user_prefs ($cust_pref)
{
  global $set;
  if (!user_isloggedin ())
    return;
  $custom_pref = user_get_preference ($cust_pref);
  if (!$custom_pref)
    return;
  $set = 'custom';
  $pref_arr = explode ('&amp;', substr ($custom_pref, 5));
  foreach ($pref_arr as $expr)
    apply_single_pref ($expr);
}

function compile_pref_string ($url_params)
{
  # Use the list of fields built from the arguments and used by the project
  # (the group_id parameter has been excluded).
  # NB: Note that trackers_extract_field_list function did build and
  # return date arguments (using _dayfd|monthfd|yearfd boxes) whether
  # or not they were used by the group.

  $ret = '';
  foreach ($url_params as $field => $arr_val)
    {
      if (!is_array ($arr_val))
        $arr_val = [$arr_val];
      foreach ($arr_val as $value_id)
        if (is_scalar ($value_id))
          $ret .= "&amp;{$field}[]=$value_id";
    }
  $vars =
    ['advsrch', 'msort', 'max_rows', 'spamscore', 'report_id', 'sumORdet'];
  foreach ($vars as $v)
    $ret .= "&amp;$v={$GLOBALS[$v]}";
  return $ret;
}

# See what type of bug set is requested (set is one of none, 'open', 'custom').
# * If no set is passed in, see if a preference was set ('custom' set).
# * If no preference and not logged in, the use 'open' set.
#  Preference is a string of the form
#  &amp;field1[]=value_id1&amp;field2[]=value_id2...
if (!$set)
  {
    $set = 'open';
    apply_user_prefs ($cust_pref);
  } # !$set

$user_id = user_getid ();
if ($set == 'custom')
  {
    $pref_stg = compile_pref_string ($url_params);
    if ($pref_stg != user_get_preference ($cust_pref))
      user_set_preference ($cust_pref, $pref_stg);
  }
else
  {
    # Force the status_id to open, set nothing else, trash the prefs.
    $url_params['status_id'][] = 1;
    user_unset_preference ($cust_pref);
  }

# At this point make sure that all parameters are defined
# as well as all the arguments that serves as selection criteria
# If not defined then defaults to ANY (0).
if ($advsrch === null)
  $advsrch = 0;
if ($msort === null)
  $msort = 0;

# Will be used later to find out if it make sense to look for items of the
# system group (meaningful on the cookbook).
$not_group_specific = 1;

while ($field = trackers_list_all_fields ())
  {
    # The select boxes for the bug DB search first.
    if (!(trackers_data_is_showed_on_query ($field)
          && trackers_data_is_select_box ($field)))
      continue;
    if (!isset ($url_params[$field]))
      $url_params[$field][] = 0;
  }

# Start building the SQL query (select and where clauses).

# Force the selection of priority because it is always shown as color code.
# Force the selection of privacy, we always want to be sure that no private
# item title is provided to everybody.
$full_field_list = $col_list = $width_list = $lbl_list = [];
$select = ['a.group_id', 'a.priority', 'a.privacy', 'a.status_id'];

$where = "WHERE\n    a.group_id = ?";
$where_params = [$group_id];

# Take into account the spamscore, but show items posted by the logged-in user.
$where .= " AND (a.spamscore < ?";
$where_params[] = $spamscore;
if ($user_id != 100)
  {
    $where .= ' OR a.submitted_by = ?';
    $where_params[] = $user_id;
  }
$where .= ")";

# If the user asked for more items to be shown than the configured limit,
# restrict to the limit: it would be too heavy on the database if this was
# done very frequently and we already found some group giving direct
# links to 500 the browse item page with 500 items shown by default.
# Save the wanted number of max_rows for later use.
$wanted_max_rows = $max_rows;
if (empty ($sys_max_items_per_page))
  $sys_max_items_per_page = 150;
if ($max_rows > $sys_max_items_per_page && !$digest)
  $max_rows = $sys_max_items_per_page;

$limit = "LIMIT ?, ?";
$limit_params = [$offset, $max_rows];

# Prepare for summary and original submission as 'special' criteria.
$summary_search = 0;
$details_search = 0;

foreach ($url_params as $field => $val)
  {
    if (!is_array ($val))
      continue;
    foreach ($val as $v)
      if (is_array ($v))
        {
          $msg = utils_specialchars ("$field = " . error_print_r ($v));
          exit_error (sprintf (_('Wrong parameter', $msg)));
        }
  }

foreach ($url_params as $field => $value_id)
  {
    # If the criteria is not in the field showed on query screen then
    # skip it. This is a sanity check to make sure that the SQL
    # query we run actually matches the displayed search criteria.
    if (!trackers_data_is_showed_on_query ($field))
      continue;

    if (trackers_data_is_select_box ($field)
        && !trackers_isvarany ($url_params[$field]))
      {
        # Only select box criteria to where clause if argument is not ANY.
        $where .= "\n    AND a.$field "
          . utils_in_placeholders ($url_params[$field]) . ' ';
        $where_params = array_merge ($where_params, $url_params[$field]);
      }
    elseif (trackers_data_is_date_field ($field) && $url_params[$field][0])
      {
        # Transform a date field into a unix time and use <, > or =.
        $param = $url_params[$field][0];
        list ($time, $ok) = utils_date_to_unixtime ($param);
        $ok = $ok && preg_match ("/\s*(\d+)-(\d+)-(\d+)/", $param, $match_arr);
        if ($ok)
          list (, $year, $month, $day) = $match_arr;
        $field_defined = "\n    AND a.$field <> 0 ";

        if ($advsrch)
          {
            list ($time_end, $ok_end) =
              utils_date_to_unixtime ($url_params["{$field}_end"]);
            if ($ok)
              {
                $where .= "\n    AND a.$field >= ?";
                $where_params[] = $time;
              }
            if ($ok_end)
              {
                $where .= "\n    AND a.$field <= ?";
                $where_params[] = $time_end;
              }
            if (!$ok && !$ok_end) # No limits, allow undefined dates.
              $field_defined = '';
          }
        else
          {
            $operator = $url_params["{$field}_op"][0];
            if ($operator == '*')
              $field_defined = ''; # Allow undefined dates in this case.
            elseif ($operator == '=')
              {
                # '=' means that day between 00:00 and 23:59.
                $time_end = mktime (23, 59, 59, $month, $day, $year);
                $where .= "\n    AND a.$field >= ? AND a.$field <= ?";
                $where_params[] = $time;
                $where_params[] = $time_end;
              }
            else
              {
                $time = mktime (0, 0, 0, $month, $day + 1, $year);
                $where .= "\n    AND a.$field $operator= ?";
                $where_params[] = $time;
              }
          }
        $where .= $field_defined;
      }
    elseif ((trackers_data_is_text_field ($field)
             || trackers_data_is_text_area ($field))
            && $url_params[$field][0])
      {
        # Buffer summary and original submission (details) to handle them later
        # in case we have an OR to do between the two, instead of the usual
        # AND.
        if ($sumORdet == 1 && ($field == 'summary' || $field == 'details'))
          {
            if ($field == 'summary')
              $summary_search = 1;
            elseif ($field == 'details')
              $details_search = 1;
          }
        else
          {
            # It's a text field accept. Process INT or TEXT, VARCHAR fields
            # differently.
            list ($expr, $params) =
              trackers_build_match_expression ($field, $url_params[$field][0]);
            $where .= "\n    AND $expr";
            $where_params = array_merge ($where_params, $params);
          }
      }
  } # foreach ($url_params as $field => $value_id)

# Handle summary and/or original submission now, if a AND is required.
if ($sumORdet == 1)
  {
    # We will process the usual normal AND case: there was something
    # for both fields.
    if ($details_search == 1 && $summary_search == 1)
      {
        $where .= "\n   AND ";
        $where .= '( ( ';
        list ($expr, $params) = trackers_build_match_expression (
          'details', $url_params['details'][0]
        );
        $where .= $expr;
        $where_params = array_merge ($where_params, $params);
        $where .= ' ) OR ( ';
        list ($expr, $params) = trackers_build_match_expression (
          'summary', $url_params['summary'][0]
        );
        $where .= $expr;
        $where_params = array_merge ($where_params, $params);
        $where .= ') )';
      }
    else
      {
        # Now we take care of the unusual, possible though, case where and
        # AND was asked but not both fields set.
        # Since the AND was asked, the fields haven't been taken care of before
        # and we need to do it now.
        # We do that in two IF, in case something went very wrong. In such case
        # we will proceed with a usual AND.
        if ($details_search == 1 && $url_params['details'][0])
          {
            $where .= "\n    AND ";
            list ($expr, $params) = trackers_build_match_expression (
              'details', $url_params['details'][0]
            );
            $where .= $expr;
            $where_params = array_merge ($where_params, $params);
          }
        if ($summary_search == 1 && $url_params['summary'][0])
          {
            $where .= "\n    AND ";
            list ($expr, $params) = trackers_build_match_expression (
              'summary', $url_params['summary'][0]);
            $where .= $expr;
            $where_params = array_merge ($where_params, $params);
          }
      }
  } # if ($sumORdet == 1)

# Loop through the list of used fields to define label and fields/boxes
# used as search criteria.

$ib = $is = 0;
$load_cal = false;

# Check if summary and original submission are criteria.
$summary_search = $details_search = 0;

$labels = $boxes = $html_select = '';
while ($field = trackers_list_all_fields ('cmp_place_query'))
  {
    if (!trackers_data_is_used ($field))
      continue;

    if (!trackers_data_is_showed_on_query ($field))
      continue;

    # Beginning of a new row.
    if ($ib % $fields_per_line == 0)
      {
        $align = "center";
        $labels .= "\n<tr align=\"$align\" valign='top'>";
        $boxes .= "\n<tr align=\"$align\" valign='top'>";
      }

    $labels .= '<td><span class="smaller">'
      . trackers_field_label_display ($field, $group_id)
      . "</span></td>\n";
    $boxes .= '<td><span class="smaller">';

    if (trackers_data_is_select_box ($field))
      {
        $fval = null;
        if ($advsrch)
          {
            if (isset ($url_params[$field]))
              $fval = $url_params[$field];
          }
        elseif (isset ($url_params[$field][0]))
          $fval = $url_params[$field][0];
        $boxes .=
          trackers_field_display (
            $field, $group_id, $fval, false, false, false, false, true,
            'None', true
          );
      }
    elseif (trackers_data_is_date_field ($field))
      {
        $end_value = '';
        if (isset ($url_params["{$field}_end"]))
          $end_value = $url_params["{$field}_end"];

        $op_value = '';
        if (isset ($url_params["{$field}_op"])
            && isset ($url_params["{$field}_op"][0]))
          $op_value = $url_params["{$field}_op"][0];

        $value = '';
        if (isset ($url_params[$field]) && isset ($url_params[$field][0]))
          $value = $url_params[$field][0];

        if ($advsrch)
          $boxes .= trackers_multiple_field_date ($field, $value, $end_value);
        else
          $boxes .= trackers_field_date_operator ($field, $op_value)
            . trackers_field_date ($field, $value);
      }
    elseif (trackers_data_is_text_field ($field)
            || trackers_data_is_text_area ($field))
      {
        if ($field == 'summary')
          $summary_search = 1;
        if ($field == 'details')
          $details_search = 1;

        if (!isset ($url_params[$field]))
          # Not passed as parameter yet, field just appeared due to
          # a change of the query form.
          $url_params[$field] = [null];

        $txt = $url_params[$field][0];
        $boxes .= trackers_field_text ($field, $txt, 15, 80);
      }
    $boxes .= "</span></td>\n";
    $ib++;

  # End of this row.
  if ($ib % $fields_per_line == 0)
    {
      $html_select .= "$labels</tr>\n$boxes</tr>\n";
      $labels = $boxes = '';
    }
  } # while ($field = trackers_list_all_fields ('cmp_place_query'))

# Make sure the last few cells are in the table.
if ($labels)
  $html_select .= "$labels</tr>\n$boxes</tr>\n";

# Fill the relevant SQL bit to be used later.
# Sensible default case: order by item_id from the recent to the older
# (only if not in multiple column sort, otherwise don't mess with it because
# the first thing to be set will matter a lot).
if ($morder == '' && !$msort)
  $morder = "bug_id<";
$order_by = null;
if ($morder != '')
  {
    $matching_morder = '';
    # Workaround the case when the list is sorted by a column that has been
    # removed from the query form (multicolumn sorting is not affected),
    # Savannah SR #107879.
    if (!$msort)
      {
        $matching_morder = preg_replace ('/[<>]$/', '', $morder);
        while ($field = trackers_list_all_fields ())
          {
            if (strcmp ($field, $matching_morder))
              continue;
            if (!trackers_data_is_used ($field))
              continue;
            if (!trackers_data_is_showed_on_result ($field))
              continue;
            $matching_morder = '';
            # Can't break here because tracker_list_all_fields ()
            # only supports full iteration.
          }
      }
    if (in_array ($matching_morder, ['', 'priority']))
      {
        $fields = trackers_criteria_list_to_query ($morder);
        if (!empty ($fields))
          $order_by = "ORDER BY $fields";
      }
  }

# Loop through the list of used fields to see what fields are in the
# result table and complement the SQL query accordingly.

# Add extra digest column, if necessary.
if ($digest)
  {
    $col_list[] = "digest";
    $width_list[] = "";
    $lbl_list[] = _("Digest");
  }

function lbl_item ($field, $crit)
{
  $img = trackers_sorting_order_img ($crit);
  return trackers_data_get_label ($field) . " $img";
}

$morder_icon_is_set = '';
$have_last_updated = false;
$froms = [];
while ($field = trackers_list_all_fields ('cmp_place_result'))
  {
    # Need the full list of used fields
    $full_field_list[] = $field;

    if (!trackers_data_is_used ($field)
        || !trackers_data_is_showed_on_result ($field))
      continue;

    if ($field == 'updated')
      $have_last_updated = true;
    $col_list[] = $field;
    $width_list[] = trackers_data_get_col_width ($field);

    if ($msort)
      {
        # Less simple in multicolumn, indeed.
        $morder_icon_is_set = 0;
        $morder_arr = explode (',', $morder);

        foreach ($morder_arr as $crit)
          {
            if (!($crit == "$field<" || $crit == "$field>"))
              continue;
            $lbl_list[] = lbl_item ($field, $crit);
            # If we found a criteria, go deal with the next column.
            $morder_icon_is_set = 1;
          }

        # If this field is not a sort criteria, we still have to create
        # the column.
        if (!$morder_icon_is_set)
          $lbl_list[] = trackers_data_get_label ($field);
      }
    else
      {
        # If we have the field that defines the order, add an icon.
        # Quite simple in monolcolumn.
        if ($morder_icon_is_set)
          $lbl_list[] = trackers_data_get_label ($field);
        else
          {
            if ($morder == "$field<" || $morder == "$field>")
              {
                $lbl_list[] = lbl_item ($field, $morder);
                $morder_icon_is_set = 1;
              }
            else
              $lbl_list[] = trackers_data_get_label ($field);
          }
      }

    if ($field == 'updated')
      {
        $select[] = "IFNULL(MAX(upd.date), a.date) AS updated";
        continue;
      }

    if (!trackers_data_is_username_field ($field))
      {
        # Select column as is.
        $select[] = "a.$field";
        continue;
      }
    # Display the username instead of the user_id.
    $select[] = "user_$field.user_name AS $field";
    $froms[] = "user user_$field";
    $where .= "\n    AND user_$field.user_id = a.$field";
  } # while ($field = trackers_list_all_fields ('cmp_place_result'))

# The submitted_by column may be compared against the user id
# for access to private items, so make sure it's present.
$field = 'submitted_by';
$submitted_by_column = "user_$field.user_name AS $field";
if (!in_array ($submitted_by_column, $select))
  {
    $select[] = $submitted_by_column;
    $froms[] = "user user_$field";
    $where .= "\n    AND user_$field.user_id = a.$field";
  }

$art_h = "{$art}_history";

$next_from = "$art a";
if ($have_last_updated)
  $next_from .= " LEFT JOIN $art_h upd ON upd.bug_id = a.bug_id";
$froms[] = $next_from;

$from_params = [];
$more_from = '';
if (empty ($history_date))
  $history_date = '';
if ($history_search)
  {
    list ($unix_history_date, $ok) = utils_date_to_unixtime ($history_date);
    if ($history_event == "modified")
      {
        $froms[] = "$art_h hist";
        $where .= "\n    AND hist.bug_id = a.bug_id AND hist.date >= ?";
        $where_params[] = $unix_history_date;
        if ($history_field != '0')
          {
            $where .= "\n    AND hist.field_name = ?";
            $where_params[] = $history_field;
          }
      }
    else
      {
        $more_from .=
          "\n    LEFT JOIN $art_h hist ON"
          . "\n      (hist.bug_id = a.bug_id AND hist.date >= ?";
        $from_params[] = $unix_history_date;
        $where .= " AND hist.bug_id IS NULL";
        if ($history_field != '0')
          {
            $more_from .= " AND hist.field_name = ?";
            $from_params[] = $history_field;
          }
        $more_from .= ') ';
      }
  }

$from = "FROM " . join (", ", $froms) . $more_from;
$group_order_limit = [];
if ($have_last_updated)
  $group_order_limit[] = "GROUP BY a.bug_id";
if ($order_by !== null)
  $group_order_limit[] = $order_by;
$group_order_limit[] = $limit;
$group_order_limit = join (' ', $group_order_limit);

$sel = [];
while (!empty ($select))
  {
    for ($i = 0, $s = []; $i < 5; $i++)
      {
        $f = array_shift ($select);
        if ($f === null)
          break;
        $s[] = $f;
      }
    $sel[] = join (', ', $s);
  }
$select = join (",\n    ", $sel);

# Run 2 queries: one to count the total number of results, and the second
# one with the LIMIT argument. It is faster than selecting all
# rows (without LIMIT) because when the number of bugs is large it takes
# time to transfer all the results from the server to the client.
# It is also faster than using the SQL_CALC_FOUND_ROWS/FOUND_ROWS()
# capabilities of MySQL.
$sql = "SELECT count(DISTINCT a.bug_id) AS count\n  $from\n  $where";
$params = array_merge ($from_params, $where_params);
$result = db_execute ($sql, $params);
$totalrows = db_result ($result, 0, 'count');

$select = "SELECT DISTINCT\n    $select";
$sql = "$select\n  $from\n  $where\n  $group_order_limit";
$result = db_execute ($sql, array_merge ($params, $limit_params));

# Build the array that will be given to the function that make the item
# list. We cannot simply return the SQL results, since we have to remove
# private items if necessary and set $totalrows accordingly.
$result_array = [];
$skip_priv = !member_check_private (0, $group_id);
$uname = user_getname ();
while ($row = db_fetch_array ($result))
  {
    $id = $row['bug_id'];
    if ($row['privacy'] == '2' && $skip_priv && $row['submitted_by'] != $uname)
      {
        $totalrows--;
        continue;
      }
  $result_array[$id] = [];

  # Always store the group, it may be necessary later, in case we actually
  # look for items from different projects.
  $result_array[$id]["group_id"] = $row["group_id"];

  # Store each field that will be necessary later.
  foreach ($full_field_list as $f)
    {
      $result_array[$id][$f] = null;
      if (isset ($row[$f]))
        $result_array[$id][$f] = $row[$f];
    }
  } # while ($row = db_fetch_array ($result))

# Display the HTML search form.

$form_submit = '';
trackers_header (['title' => $hdr]);

if ($browse_preamble)
  print $browse_preamble;

$form_opening =
  form_tag (['method' => 'get', 'name' => 'bug_form'], '#options');
$form = form_hidden (
  ['group' => $group, "func" => $func, "set" => "custom", "msort" => $msort]
);

# Show the list of available bug reports kind.
$res_report = trackers_data_get_reports ($group_id, $user_id);
$show_100 = true;
$form_query_type = html_build_select_box (
  $res_report, 'report_id', $report_id, $show_100,
  # TRANSLATORS: this string is as argument in
  # "Browse with the %s query form".
  _('Basic'), false, 'Any', false, _('query form')
);

# Start building the URL that we use to for hyperlink in the form.
$url = "$sys_home$art/?group="
  . "$group&amp;func=$func&amp;set=$set&amp;msort=$msort";
if ($set == 'custom')
  $url .= $pref_stg;
else
  $url .= "&amp;advsrch=$advsrch";

$url_nomorder = $url;
$url .= "&amp;morder=$morder";

# Build the URL for alternate Search.
if ($advsrch)
  {
    $url_alternate_search = str_replace ('advsrch=1', 'advsrch=0', $url);
    $advsrch = 1;
    $text = _("Simple Search");
  }
else
  {
    $advsrch = 0;
    $url_alternate_search = str_replace ('advsrch=0', 'advsrch=1', $url);
    $text = _("Advanced Search");
  }

$form_sel_type =
  '<select title="' . _("type of search") . "\" name='advsrch'>"
  # TRANSLATORS: this string is used to specify kind of selection.
  . form_option ('0', $advsrch, _("Simple"))
  # TRANSLATORS: this string is used to specify kind of selection.
  . form_option ('1', $advsrch, _("Multiple"))
  . "</select>\n";
$form_submit = '<input class="bold" value="' . _("Apply")
  . "\" name='go_report' type='submit' />\n";

if ($form_query_type !== null)
  $form .=
     sprintf (
       # TRANSLATORS: the first argument is kind of query form (like Basic),
       # the second argument is kind of selection (Simple or Multiple).
       _('Browse with the %1$s query form and %2$s selection.'),
       $form_query_type, $form_sel_type
     ) . "\n";

$form .= "<table cellpadding='0' cellspacing='5'>\n"
  . "<tr><td colspan=\"$fields_per_line\" nowrap='nowrap'>";
$form .= $html_select;
$form .= "</table>\n";

# If both 'summary' and 'original submission' are searched,
# propose an OR instead of AND.
if (($details_search == 1) && ($summary_search == 1))
  {
    $sum = rtrim (
      trackers_field_label_display ("summary", $group_id, false, true), ': '
    );
    $det = rtrim (
      trackers_field_label_display ("details", $group_id, false, true), ': '
    );
    $conj =
      '<select title="' . _("logical operation to apply")
      . '" name="sumORdet">' . "\n"
      # TRANSLATORS: this is a logical operator, used in string
      # "Use logical %s between '%s' and '%s' searches.
      . form_option ('0', $sumORdet, _("AND"))
      # TRANSLATORS: this is a logical operator, used in string
      # "Use logical %s between '%s' and '%s' searches.
      . form_option ('1', $sumORdet, _("OR")) . "</select>\n";

    $form .= '<p class="smaller">';
    $form .=
      # TRANSLATORS: the first argument is operator (AND or OR),
      # the second argument is label for 'summary' field,
      # the third argument is label for 'details field.
      sprintf (
        _('Use logical %1$s between \'%2$s\' and \'%3$s\' searches.'),
        $conj, $sum, $det
      );
    $form .= "</p>\n";

    # Update the URL.
    $url .= "&amp;sumOrdet=$sumORdet";
  }

# Propose to search for field updated since a certain date.
$fextracted = $fname = $flabel = [];

# Extract the list of relevant fields.
while ($field = trackers_list_all_fields ())
  {
    if (!trackers_data_is_used ($field))
      continue;

    # Special fields are usually not modifiable by users, it is the system
    # that defines their value. As such, they cannot be used
    # as additional constraint.
    if (trackers_data_is_special ($field))
      continue;

    $fextracted[$field] = trackers_data_get_label ($field);
  }

# Order them by name: in a select box, following the configure output order
# is not user-friendly.
asort ($fextracted);
foreach ($fextracted as $field => $label)
  {
    $fname[] = $field;
    $flabel[] = $label;
  }

# TRANSLATORS: the string is used like "<field-name> %s since <date>"
$hist_ev_text = [_("modified"), _("not modified")];
$hist_ev_value = ["modified", "not modified"];

$form_activated = '<select title="'
  . _("whether additional constraint is activated")
  . '" name="history_search">'
  # TRANSLATORS: this string is used as the argument in
  # 'Additional constraint %s'.
  . form_option ('0', $history_search, _("deactivated"))
  # TRANSLATORS: this string is used as the argument in
  # 'Additional constraint %s'.
  . form_option ('1', $history_search, _("activated"))
  . "</select>\n";
$form_separator = "<br />\n&nbsp;&nbsp;&nbsp;";
$form_fieldname = html_build_select_box_from_arrays (
  $fname, $flabel, 'history_field', $history_field, false, '', true,
  'Any', false, _("Field for criteria")
);
$form_modified = html_build_select_box_from_arrays (
  $hist_ev_value, $hist_ev_text, 'history_event', $history_event,
  false, '', false, '', false, _("modified or not"));
$form_since = trackers_field_date (
  'history_date', $history_date, 0, 0, false
);
if ($form_separator != '')
  $form .= '<p class="smaller"><span class="preinput">'
    # TRANSLATORS: the argument is 'activated' or 'deactivated'.
    . sprintf (_('Additional constraint %1$s:'), $form_activated)
    . "</span>$form_separator"
    # TRANSLATORS: the first argument is field name, the second argument is
    # either 'modified' or 'not modified', the third argument is date.
    . sprintf (
        _('%1$s %2$s since %3$s'), $form_fieldname, $form_modified,
        $form_since
      )
    . "</p>\n";

if ($history_search)
  $url .= "&amp;history_search=$history_search"
    . "&amp;history_field=$history_field&amp;history_event=$history_event"
    . "&amp;history_date=$history_date";

$form .= '<p class="smaller">';
$form .= trackers_max_rows_control () . ' ';
if ($is_trackeradmin)
  $form .=
    sprintf (
      _("Show items with a spam score lower than %s."),
      form_input ("text", "spamscore", $spamscore,
        'size="3" maxlength="2" title="'
        . _("Spam level of items to hide") . '"')
    );
if ($wanted_max_rows != $max_rows)
  {
    $fmt = ngettext (
      "Warning: only %s item can be shown at once.",
      "Warning: only %s items can be shown at once.",
      $max_rows
    );
    $form .= ' <span class="warn">' . sprintf ($fmt, $max_rows) . '</span>';
  }

$form .= "</p>\n";

if ($totalrows > 0)
  {
    $form .= '<p class="smaller">';
    $msg =
      _("Column heading links sort results (up or\ndown), you can also "
        . '<a href="%1$s">sort by priority</a> or <a href="%2$s">reset'
        . "\nsort</a>.");
    $form .= sprintf (
      $msg, "$url&amp;order=priority#results", "$url&amp;order=#results"
    );
    if ($msort)
      {
        $url_alternate_sort = str_replace ('msort=1', 'msort=0', $url)
          . '&amp;order=#results';
	$form .= sprintf (
          _("You can also <a href=\"%s\">deactivate multicolumn sort</a>."),
          $url_alternate_sort
        );
      }
    else
      {
        $url_alternate_sort = str_replace ('msort=0', 'msort=1', $url)
          . '&amp;order=#results';
	$form .= ' ' . sprintf (
          _("You can also <a href=\"%s\">activate multicolumn sort</a>."),
          $url_alternate_sort
        );
      }
    if ($morder)
      # TRANSLATORS: the argument is comma-separated list of field labels.
      $form .= " "
        . sprintf (_("Currently, results are sorted by %s."),
            trackers_criteria_list_to_text ($morder, $url_nomorder)
          );
    $form .= "</p>\n";
  }

print html_show_displayoptions ($form, $form_opening, $form_submit);
if ($totalrows > 0)
  {
    $nav_bar = html_nextprev_str ($url, $offset, $max_rows, $totalrows);
    print "<div id='results'>$nav_bar</div>\n";
    if ($digest)
      print form_tag (['method' => 'get'])
        . form_hidden (['group' => $group, 'func' => "digestselectfield"]);
    show_item_list ($result_array, $col_list, $lbl_list, $width_list, $url);
    if ($digest)
      print form_footer (_("Proceed to Digest next step"));
    print $nav_bar;
    show_priority_colors_key ();
  }
else
  {
    $msg = _("No matching items found. The display criteria may be "
      . "too restrictive.");
    fb ($msg . ' ' . db_error (), 1);
  }
trackers_footer ();
?>
