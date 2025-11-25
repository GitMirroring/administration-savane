<?php
# General tracker functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2001, 2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2000-2006 Mathieu Roy
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

$dir_name = dirname (__FILE__);
foreach (
  ['../calendar', '../sendmail', 'data', 'format', '../utils', '../group'] as $i
)
  require_once ("$dir_name/$i.php");

define ('TRACKERS_CC_SUBMITTED', '-SUB-');
define ('TRACKERS_CC_COMMENTED', '-COM-');
define ('TRACKERS_CC_SUBSCRIBED', '-SSC-');
define ('TRACKERS_CC_UPDATED', '-UPD-');
define ('TRACKERS_CC_VOTED', '-VOT-');
define ('TRACKERS_CC_REGEX', '-...-');
define ('TRACKERS_CC_AFFIX', ' ');

# Generate URL arguments from a variable whether scalar or array.
function trackers_convert_to_url_arg ($varname, $var)
{
  if (is_array ($var))
    {
      foreach ($var as $v)
        $ret .= "&{$varname}[]=$v";
      return $ret;
    }

  return "&{$varname}=$var";
}

function trackers_check_artifact ($group_id)
{
  if ($group_id == GROUP_NONE && user_is_super_user ())
    return;
  $project = project_get_object ($group_id);

  foreach (['bugs', 'support', 'task', 'patch'] as $art)
    if (ARTIFACT == $art && !$project->Uses ($art))
      exit_error (_("This group has turned off this tracker."));
}

function trackers_header ($params, $prefix = '')
{
  global $group_id, $DOCUMENT_ROOT, $advsrch;

  # Required params for site_project_header().
  $params['group'] = $group_id;
  $params['context'] = $prefix . ARTIFACT;

  trackers_check_artifact ($group_id);
  print site_project_header ($params);
}

function trackers_header_admin ($params)
{
  trackers_header ($params, 'a');
}

function trackers_footer ($params = [])
{
  site_project_footer ($params);
}

function trackers_init ($group_id)
{
  # Set the global arrays for faster processing at init time.
  trackers_data_get_all_fields ($group_id, true);
}

function trackers_report_init ($report_id)
{
  # Set the global array with report information for faster processing.
  return trackers_data_get_all_report_fields ($report_id);
}

function trackers_list_all_fields ($sort_func = false, $by_field_id = false)
{
  global $BF_USAGE_BY_ID, $BF_USAGE_BY_NAME, $AT_START;

  # If its the first element we fetch then apply the sort function.
  if ($AT_START)
    {
      if (!$sort_func)
        $sort_func = 'cmp_place';
      uasort ($BF_USAGE_BY_ID, $sort_func);
      uasort ($BF_USAGE_BY_NAME, $sort_func);
      $AT_START = false;
    }

  # Return the next bug field in the list.  If the global
  # bug field usage array is not set then set it the
  # first time.
  # by_field_id: true return the list of field id, false returns the
  # list of field names.
  $idx = $by_field_id? 'bug_field_id': 'field_name';

  if (current ($BF_USAGE_BY_ID) === false)
    {
      trackers_data_rewind_bf_usage ();
      return false;
    }
  $field_array = current ($BF_USAGE_BY_ID);
  next ($BF_USAGE_BY_ID);
  return $field_array[$idx];
}

function trackers_data_description_link ($field, $label)
{
  global $group_id, $sys_home;
  $is_admin = user_is_group_admin ();
  $url = $sys_home . ARTIFACT . "/admin";
  $q_str = "group_id=$group_id&field=$field";
  if (trackers_data_is_select_box ($field))
    return
      "<a href=\"$url/field_values.php?$q_str&list_value=1\">$label</a>";
  if (!$is_admin)
    return $label;
  return
    "<a href=\"$url/field_usage.php?$q_str&update_field=1\">$label</a>";
}

function trackers_field_label_display (
  $field, $group_id, $break = false, $ascii = false, $tab = 25
)
{
  $label = trackers_data_get_label ($field) . ':';
  $output = '';
  $text = "<span class='preinput help' title=\""
    . trackers_data_get_description ($field) . '">' . "$label</span>";

  if (!$ascii)
    $output .= trackers_data_description_link ($field, $text);
  if ($break)
    return $output . ($ascii? "\n": '<br />');
  if (!$ascii)
    $output .= '&nbsp;';
  else
    $output .= sprintf ("%{$tab}s", $label) . ' ';
  return $output;
}

function trackers_field_display_sb_ro ($field_name, $value, $group_id, $args)
{
  # If multiple values are selected, return a list of <br />-separated values.
  $arr = $value;
  if (!is_array ($arr))
    $arr = [$arr];
  for ($i = 0;$i < count ($arr); $i++)
    {
      if ($arr[$i] == 100 && $field_name != 'percent_complete')
        $val = $args['text_none'];
      else
        $val = trackers_data_get_value ($field_name, $group_id, $arr[$i]);
      if ($val == 'None' && $arr[$i] == 0)
        $val = $args['text_any'];
      $arr[$i] = $val;
    }
  return join ('<br />', $arr);
}

function trackers_field_display_sb ($field_name, $value, $group_id, $args)
{
  if ($args['ro'])
    return trackers_field_display_sb_ro ($field_name, $value, $group_id, $args);
  # If it is a user name field (assigned_to, submitted_by) then make
  # sure to add the "None" entry in the menu 'coz it's not in the DB.
  if (trackers_data_is_username_field ($field_name))
    {
      $args['show_none'] = true;
      $args['text_none'] = _('None');
    }

  if (is_array ($value))
    return trackers_multiple_field_box (
      $field_name, '', $group_id, $value, $args['show_none'],
      $args['text_none'], $args['show_any'], $args['text_any']
    );
  return trackers_field_box (
    $field_name, '', $group_id, $value, $args['show_none'], $args['text_none'],
    $args['show_any'], $args['text_any'],
    $args['allowed_transition_only'], $args['show_unknown']
  );
}

function trackers_field_display_df ($field_name, $value, $group_id, $args)
{
  if ($args['ascii'])
    return utils_format_date ($value);
  if ($args['ro'])
    return utils_format_date ($value);
  return trackers_field_date (
    $field_name, empty ($value)? null: date ("Y-m-d", $value)
  );
}

function trackers_field_display_tf ($field_name, $value, $group_id, $args)
{
  if ($args['ascii'])
    return utils_unconvert_htmlspecialchars ($value);
  return $args['ro']? $value: trackers_field_text ($field_name, $value);
}

function trackers_field_display_ta ($field_name, $value, $group_id, $args)
{
  if ($args['ascii'])
    return markup_ascii ($value);
  return $args['ro']? markup_full ($value):
    trackers_field_textarea ($field_name, $value);
}

function trackers_field_display_unknown ($field_name, $value, $group_id, $args)
{
  $display_type = trackers_data_get_display_type ($field_name);
  $output .= 'Unknown ' . ARTIFACT . ' field display type '
    . '"' . utils_specialchars ($display_type) . '"';
}

function trackers_field_display_func ($field_name)
{
  $display = [
    'SB' => 'trackers_field_display_sb', 'DF' => 'trackers_field_display_df',
    'TF' => 'trackers_field_display_tf', 'TA' => 'trackers_field_display_ta'
  ];
  $display_type = trackers_data_get_display_type ($field_name);
  if (array_key_exists ($display_type, $display))
     return $display[$display_type];
  return 'trackers_field_display_unknown';
}

# Display a bug field either as a read-only value or as a read-write
# making modification possible.
# - field_name: name of the column.
# - group_id: the group id.
# - value: the current value stored in this field (for select boxes type of field
#     it is the value_id actually. It can also be an array with multiple values.
# - break: true if a break line is to be inserted between the field label
#     and the field value.
# - label: if true display the field label.
# - ro: true if only the field value is to be displayed. Otherwise
#     display an HTML select box, text field or text area to modify the value.
# - ascii: if true do not use any HTML decoration just plain text (if true
#     then read-only (ro) flag is forced to true as well).
# - show_none: show the None entry in the select box if true (value_id 100).
# - text_none: text associated with the none value_id to display in the select box
# - show_any: show the Any entry in the select box if true (value_id 0).
# - text_any: text associated with the any value_id to display in the select box
# - allowed_transition_only: print only transition allowed.
function trackers_field_display (
  $field_name, $group_id, $value = 'xyxy', $break = false, $label = true,
  $ro = false, $ascii = false, $show_none = false, $text_none = 'None',
  $show_any = false, $text_any = 'Any', $allowed_transition_only = false,
  $show_unknown = false, $tab = 25
)
{
  $output = '';
  $arg_names = [
    'text_any', 'text_none', 'show_none', 'show_any', 'allowed_transition_only',
    'show_unknown', 'ro', 'ascii'
  ];
  $args = [];
  foreach ($arg_names as $a)
    $args[$a] = $$a;
  $display = [
    'SB' => 'trackers_field_display_sb', 'DF' => 'trackers_field_display_df',
    'TF' => 'trackers_field_display_tf', 'TA' => 'trackers_field_display_ta'
  ];
  $display_type = trackers_data_get_display_type ($field_name);

  if ($label)
    $output = trackers_field_label_display (
      $field_name, $group_id, $break, $ascii, $tab
    );
  $func = trackers_field_display_func ($field_name);
  return $output . $func ($field_name, $value, $group_id, $args);
}

function trackers_field_date ($field, $value)
{
  if ($value !== null)
    # Value is formatted as Y-m-d.
    $t = explode ('-', $value);
  $year = isset ($t[0])? $t[0]: null;
  $month = isset ($t[1])? $t[1]: null;
  $day = isset ($t[2])? $t[2]: null;

  # Date part are missing, take the date of the day.
  $today = localtime ();
  if (!$day)
    $day = $today[3];
  if (!$month)
    $month = $today[4] + 1;
  if (!$year)
    $year = $today[5] + 1900;

  $html = calendar_select_date (
    $day, $month, $year,
    ["{$field}_dayfd", "{$field}_monthfd", "{$field}_yearfd"]
  );
  return $html;
}

function trackers_multiple_field_date (
  $field_name, $date_begin = '', $date_end = '', $size = 0, $maxlength = 0,
  $ro = false
)
{
  if ($ro)
    {
      if ($date_begin || $date_end)
        return  _("Start:") . "&nbsp;$date_begin<br />" . _("End:")
          . "&nbsp;$date_end";
      return _('Any time');
    }
  if (!$size || !$maxlength)
    list ($size, $maxlength) = trackers_data_get_display_size ($field_name);

  $html = "<label for=\"$field_name\">" . _('Start:') . "</label><br />\n"
    . "<input type='text' name=\"$field_name\" id=\"$field_name"
    . "\" size=\"$size\" maxlength=\"$maxlength\" value=\"$date_begin\">"
    . _('(yyyy-mm-dd)') . "</td></tr>\n<tr><td>"
    . _('End:') . "<br />\n"
    . "<input type='text' name=\"{$field_name}_end\" id=\"{$field_name}_end"
    . "\" size=\"$size\" maxlength=\"$maxlength\" value=\"$date_end\">"
    . _('(yyyy-mm-dd)');

  return "<table><tr><td>$html</td></tr></table>\n";
}

function trackers_date_op_list ()
{
  return ['*', '>', '=', '<'];
}

function trackers_field_date_operator ($field_name, $value = '', $ro = false)
{
  if ($ro)
    return utils_specialchars ($value);
  $options = '';
  foreach (trackers_date_op_list () as $v)
    $options .= form_option ($v, $value, utils_specialchars ($v));
  return '<select title="' . _("comparison operator")
    . "\" name=\"{$field_name}_op\">$options</select>\n";
}

function trackers_max_rows_control ()
{
  global $max_rows;
  return html_label ('max_rows', _("Items to show at once:")) . '&nbsp;'
    . form_input ('text', 'max_rows', $max_rows, 'size="3" maxlength="5"');
}

function trackers_field_text (
  $field_name, $value = '', $size = 0, $maxlength = 0
)
{
  if (!$size || !$maxlength)
    list ($size, $maxlength) = trackers_data_get_display_size ($field_name);

  return "<input type='text' name=\"$field_name"
    . '" title="' . trackers_data_get_description ($field_name)
    . "\" size=\"$size\" maxlength=\"$maxlength\" value=\"$value\" />";
}

function trackers_field_textarea (
  $field_name, $value = '', $cols = 0, $rows = 0, $title = false
)
{
  if ($title === false)
    $title = trackers_data_get_description ($field_name);
  if (!$cols || !$rows)
    {
      list ($cols, $rows) = trackers_data_get_display_size ($field_name);
      # Nothing defined for this field? Use hardcoded default values.
      if (!$cols || !$rows)
        {
          $cols = "65";
          $rows = "16";
        }
    }

  return "<textarea id=\"$field_name\" name=\"$field_name\" title=\"$title"
    . "\" rows=\"$rows\" cols=\"$cols\" wrap='soft'>$value</textarea>";
}

# Return a select box populated with field values for this group.
# If box_name is given, then impose this name in the select box
# of the  HTML form otherwise use the field_name.
function trackers_field_box (
  $field_name, $box_name, $group_id, $checked = false, $show_none = false,
  $text_none = 'None', $show_any = false, $text_any = 'Any',
  $allowed_transition_only = false, $show_unknown = false
)
{
  if (!$group_id)
    return _('Error: no group defined');

  $title = trackers_data_get_description ($field_name);
  if ($title == '')
    $title= trackers_data_get_label ($field_name);

  $result = trackers_data_get_field_predefined_values (
    $field_name, $group_id, $checked
  );
  if ($box_name == '')
    $box_name = $field_name;

  if ($allowed_transition_only)
    {
      $field_id = trackers_data_get_field_id ($field_name);

      # First check if group has defined transitions for this field.
      $res = db_execute ("
        SELECT transition_default_auth
        FROM " . ARTIFACT . "_field_usage
        WHERE group_id = ? AND bug_field_id = ?",
        [$group_id, $field_id]
      );
      $default_auth = TRANSITION_ALLOWED;
      if (db_numrows ($res) > 1)
        $default_auth = db_result ($res, 0, 'transition_default_auth');
      # Avoid corrupted database content, if its not F, it must be A.
      if ($default_auth != "F")
        $default_auth = TRANSITION_ALLOWED;

      $trans_result = db_execute (
        "SELECT from_value_id,to_value_id,is_allowed,notification_list
         FROM trackers_field_transition
         WHERE group_id = ? AND artifact = ? AND field_id = ?
         AND (from_value_id = ? OR from_value_id = '0')",
        [$group_id, ARTIFACT, $field_id, $checked]
      );
      $forbidden_to_id = $allowed_to_id = [];
      $rows = db_numrows ($trans_result);
      if ($trans_result && $rows > 0 || $default_auth == "F")
        {
          while ($transition = db_fetch_array ($trans_result))
            if ($transition['is_allowed'] == 'F')
              $forbidden_to_id[$transition['to_value_id']] = 0;
            else
              $allowed_to_id[$transition['to_value_id']] = 0;

          # Get all the predefined values for this field.
          $rows = db_numrows ($result);

          if ($rows > 0)
            {
              $val_label = [];
              while ($val_row = db_fetch_array ($result))
                {
                  $value_id = $val_row['value_id'];
                  $value = $val_row['value'];
                  if ((($default_auth == TRANSITION_ALLOWED)
                        && (!array_key_exists($value_id, $forbidden_to_id)))
                      ||
                      (($default_auth == 'F')
                        && (array_key_exists($value_id, $allowed_to_id)))
                      ||
                      ($value_id == $checked))
                    $val_label[$value_id] = $value;
                }

              # Always add the any values cases.
              return html_build_select_box_from_arrays (
                array_keys ($val_label), array_values ($val_label), $box_name,
                $checked, $show_none, $text_none, $show_any, $text_any,
                $show_unknown, $title
              );
            }
        } # if ($trans_result && $rows > 0 || $default_auth == "F")
    } # if ($allowed_transition_only)

  # If no transition is defined, use 'normal' code.
  return html_build_select_box (
    $result, $box_name, $checked, $show_none, $text_none, $show_any, $text_any,
    $show_unknown, $title
  );
}

# Return a multiple select box populated with field values for this group.
# If box_name is given then impose this name in the select box
# of the  HTML form otherwise use the field_name.
function trackers_multiple_field_box (
  $field_name, $box_name, $group_id, $checked = false, $show_none = false,
  $text_none = 'None', $show_any = false, $text_any = 'Any',
  $show_value = false
)
{
  if (!$group_id)
    return _("Internal error: no group id");
  $result = trackers_data_get_field_predefined_values (
    $field_name, $group_id, $checked
  );
  if ($box_name == '')
    $box_name = $field_name . '[]';
  return html_build_multiple_select_box (
    $result, $box_name, $checked, 6, $show_none, $text_none, $show_any,
    $text_any, $show_value
  );
}

function trackers_extract_date_field (&$vfl, $key, $val)
{
  if (!preg_match ("/^(.*)_(day|month|year)fd$/", $key, $found))
    return false;
  trim ($val);
  if (!is_numeric ($val))
    return true;
  list ($ignore, $field_name, $field_name_part) = $found;
  if (!isset ($vfl[$field_name]))
    $vfl[$field_name] = '--';
  list ($year, $month, $day) = explode ("-", $vfl[$field_name]);
  if ($field_name_part  == 'day')
    $vfl[$field_name] = "$year-$month-$val";
  elseif ($field_name_part == 'month')
    $vfl[$field_name] = "$year-$val-$day";
  elseif ($field_name_part == 'year')
    $vfl[$field_name] = "$val-$month-$day";
  return true;
}

# Return the list of field names in the HTML Form corresponding
# to a field used by this group.
function trackers_extract_field_list ($post_method = true)
{
  global $BF_USAGE_BY_NAME;
  $vfl = $date = [];
  if ($post_method)
    $superglobal =& $_POST;
  else
    $superglobal =& $_GET;

  foreach ($superglobal as $key => $val)
    {
      if (trackers_extract_date_field ($vfl, $key, $val))
        continue;
      if (isset ($BF_USAGE_BY_NAME[$key]) || $key == 'comment')
        $vfl[$key] = $val;
    }
  return $vfl;
}

# Check whether a field was shown to the submitter
# (useful if a field is mandatory if shown to the submitter).
function trackers_check_is_shown_to_submitter (
  $field_name, $group_id, $submitter_id
)
{
  if ($submitter_id == 100) # Anonymous user.
    return trackers_data_is_showed_on_add_nologin ($field_name);

  if (member_check ($submitter_id, $group_id)) # Group member.
   return trackers_data_is_showed_on_add_members ($field_name);

  # Not a member of the group.
  return trackers_data_is_showed_on_add ($field_name);
}

function trackers_mandatory_field ($field_name, $new_item)
{
  global $item_id, $group_id, $mandatorycheck_submitter_id;

  $mandatory_flag = trackers_data_mandatory_flag ($field_name);
  if ($mandatory_flag == 1) # Not mandatory.
    return false;
  if ($mandatory_flag == 3) # Mandatory whenever possible.
    return true;

  # $mandatory_flag = 0: mandatory when shown to the submitter.
  if ($new_item)
    return true;

  if (!$mandatorycheck_submitter_id)
    {
      # Save that information for further mandatory checks,
      # to avoid avoid a SQL request per field checked.
      $submitter_res = db_execute ("
        SELECT submitted_by FROM " . ARTIFACT . "
        WHERE bug_id = ? AND group_id = ?",
        [$item_id, $group_id]
      );
      $mandatorycheck_submitter_id = db_result (
        $submitter_res, 0, 'submitted_by'
      );
    }

  $shown = trackers_check_is_shown_to_submitter (
    $field_name, $group_id, $mandatorycheck_submitter_id
  );
  if ($shown)
    return true;
  return false;
}

function trackers_set_empty_field_feedback ($bad_fields, $new_item)
{
  # If $new_item, there was no previous value to reset the entry.
  $joined = join (', ', $bad_fields);
  if ($new_item)
    {
      if (count ($bad_fields) > 1)
        # TRANSLATORS: The argument is comma-separated list of field names.
        $msg = sprintf (
          _("These fields are mandatory: %s.\n"
            . "Fill them and re-submit the form."),
          $joined
        );
      else
        $msg = sprintf (
          _("The field %s is mandatory.\n"
            . "Fill it and re-submit the form."),
          $joined
        );
      fb ($msg, 1);
      return;
    }
  if (count ($bad_fields) > 1)
    # TRANSLATORS: The argument is comma-separated list of field names.
    $msg = sprintf (
      _("These fields are mandatory: %s.\n"
        . "They have been reset to their previous value.\n"
        . "Check them and re-submit the form."),
      $joined
    );
  else
    $msg = sprintf (
      _("The field %s is mandatory.\n"
        . "It has been reset to its previous value.\n"
        . "Check it and re-submit the form."),
      $joined
    );
  fb ($msg, 1);
}

function trackers_ck_empty_fld ($field, $val, $new_item = true)
{
  global $previous_form_bad_fields;
  # Only the field percent_complete is allowed to use the special value
  # hundred.
  if ($field == "percent_complete")
    return;
  $non_empty = $val !== '';
  if (trackers_data_is_select_box ($field))
    $non_empty = $val != 100;
  if ($non_empty)
    return;

  if (!trackers_mandatory_field ($field, $new_item))
    return;
  $previous_form_bad_fields[$field] = trackers_data_get_label ($field);
}

# Check whether empty values are submitted in any mandatory fields.
# $field_array: associative array of field_name -> value.
# $new_item: whether checks for new items (as opposed to comments) are run.
# Return true when no empty fields to fill are found, false otherwise.
# Fill global $previous_form_bad_fields with labels for missing fields
# as array field_name => label.
# Call fb() with an error message when returning false.
function trackers_check_empty_fields ($field_array, $new_item = true)
{
  global $previous_form_bad_fields;
  $previous_form_bad_fields = [];

  foreach ($field_array as $field_name => $val)
    trackers_ck_empty_fld ($field_name, $val, $new_item);

  if (!count ($previous_form_bad_fields))
    return true;
  trackers_set_empty_field_feedback ($previous_form_bad_fields, $new_item);
  return false;
}

function trackers_canned_response_box ($group_id, $checked, $height, $result)
{
  if (empty ($checked))
    $checked = [];
  $ret = "<select name='canned_response[]' multiple='multiple' "
    . "size='$height'>\n";
  while ($row = db_fetch_array ($result))
    $ret .= form_option ($row[0], $checked, $row[1]);
  return "$ret</select>\n";
}

function trackers_artifact_is_sane (&$artifact)
{
  if (!$artifact)
    $artifact = ARTIFACT;

  if (ctype_alnum (strval ($artifact)))
    return true;

  # TRANSLATORS: the argument is name of artifact (like bugs or patches).
  util_die (sprintf (_('Invalid artifact %s'), "<em>$artifact</em>"));
  return false; # Just in case.
}

function trackers_trim_email ($email)
{
  $email = trim ($email);

  # The CC may have been added in the form like:
  #    THIS NAME <this@address.net>
  # So the validation check must be made only on the part in < >, if
  # it exists.
  if (preg_match ("/\<([\w\d\-\@\.]*)\>/", $email, $realaddress))
    $email = $realaddress[1];
  return $email;
}

function trackers_get_cc_list ($artifact, $item_id)
{
  return db_execute ("
    SELECT email, min(added_by) AS added_by FROM {$artifact}_cc
    WHERE bug_id = ? GROUP BY email LIMIT 150", [$item_id]
  );
}

function trackers_cc_is_to_be_ignored ($email, $added_by)
{
  if ($email != $added_by || !ctype_digit (strval ($email)))
    return false;
  # Here we have an integer as email address, it is likely to be
  # a CC automatically added.
  # (if an integer is passed by is not conform to added_by, we let
  # sendmail_mail() determine what to do with it).

  # Check if the users exists.
  if (!user_exists ($email))
    return true;

  # Always ignore anonymous.
  return $email == "100";
}
function trackers_convert_user_name_to_uid ($email, &$addresses_to_skip)
{
  # If we have a valid username, convert it to an uid.
  if (ctype_digit (strval ($email)) || !user_getid ($email))
    return $email;
  # Since is is will be registered, we can ignore it in further check.
  $addresses_to_skip[$email] = true;
  return user_getid ($email);
}

function trackers_convert_email_to_uid ($email, &$addresses_to_skip)
{
  # If we have a string that contains @, try to find it in the database
  # and convert it to an uid if found.
  if (!strpos ($email, "@"))
    return $email;
  $res = db_execute ("
    SELECT user_id FROM user WHERE email = ? AND user_id > 0 LIMIT 1", [$email]
  );
  if (db_numrows ($res) < 1)
    return $email;
  $user_id = db_result ($res, 0, 'user_id');
  $addresses_to_skip[$email] = true;
  return $user_id;
}

function trackers_initial_notification_list ($tracker, $item)
{
  $addresses = $addresses_to_skip = [];
  $current_uid = user_getid ();
  if (user_get_preference ("notify_unless_im_author"))
    $addresses_to_skip[$current_uid] = true;

  $res =
    db_execute ("SELECT assigned_to from $tracker WHERE bug_id = ?", [$item]);
  $assignee_uid = db_result ($res , 0, 'assigned_to');
  # Assigned to 100 == unassigned.
  if ($assignee_uid != "100" && empty ($addresses_to_skip[$assignee_uid]))
    $addresses[$assignee_uid] = true;
  return [$addresses, $addresses_to_skip];
}

function trackers_enforce_notif_per_user_prefs ($changes, $email)
{
  if (!ctype_digit (strval ($email)))
    return true;

  # We have a user ID: check specific user's prefs.

  $unless_closed = user_get_preference ("notify_item_closed", $email);
  $unless_status_changed =
     user_get_preference ("notify_item_statuschanged", $email);

  if (!$unless_closed && !$unless_status_changed)
    return true;

  if ($unless_closed && isset ($changes['status_id'])
      && $changes['status_id']['add-val'] == '3'
  )
    return true;

  return $unless_status_changed && isset ($changes['resolution_id']);
}

function trackers_filter_notif_address (
  $added_by, $email, $changes, &$addresses, &$addresses_to_skip
)
{
  if (!empty ($addresses[$email]) || !empty ($addresses_to_skip[$email]))
    return true;

  if (trackers_cc_is_to_be_ignored ($email, $added_by))
    return true;

  $email = trackers_convert_user_name_to_uid ($email, $addresses_to_skip);
  $email = trackers_convert_email_to_uid ($email, $addresses_to_skip);

  if (!empty ($addresses[$email]) || !empty ($addresses_to_skip[$email]))
    return true;

  if (trackers_enforce_notif_per_user_prefs ($changes, $email))
    return false;

  $addresses_to_skip[$email] = true;
  return true;
}

# Any person in the CC list and the assignee should be notified,
#
# - unless this person is the one that made the update and does not
# want to be get notifications for his own work,
# - unless this person wants to know only if the item is closed and the
# item is getting closed,
# - unless this person wants to know only if the item status changed and
# the item status changed.
function trackers_build_notification_list ($item, $changes, $artifact)
{
  $item_id = $item['bug_id'];
  list ($addresses, $addresses_to_skip) =
    trackers_initial_notification_list ($artifact, $item_id);
  if (!empty ($item['originator_email']))
    $addresses[$item['originator_email']] = true;

  $result = trackers_get_cc_list ($artifact, $item_id);
  while ($row = db_fetch_array ($result))
    {
      $added_by = $row['added_by'];
      $email = trackers_trim_email ($row['email']);
      $skip_it = trackers_filter_notif_address (
        $added_by,  $email, $changes, $addresses, $addresses_to_skip
      );
      if (!$skip_it)
        $addresses[$email] = true;
    }
  return array_keys ($addresses);
}

function trackers_probably_spam ()
{
  if (!$GLOBALS['int_probablyspam'])
    return false;
  fb (_("Presumed spam: no mail will be sent"), 1);
  return true;
}

function trackers_mail_fetch_followup (&$artifact, $item_id)
{
  if (trackers_probably_spam () || !trackers_artifact_is_sane ($artifact))
    return false;
  $res = db_execute ("SELECT * from $artifact WHERE bug_id = ?", [$item_id]);

  if (db_numrows ($res) > 0)
    return db_fetch_array ($res);

  fb (_("Could not send item update."), 0);
  return false;
}

function trackers_mail_bug_ref ($artifact, $item_id)
{
  global $sys_home, $sys_default_domain;
  return "https://$sys_default_domain$sys_home$artifact/?$item_id";
}

function trackers_build_mail ($artifact, $res, $item_id, $changes)
{
  global $int_delayspamcheck_comment_id;
  # Text of the mail must not be localized.
  $bug_ref = trackers_mail_bug_ref ($artifact, $item_id);
  $msg = [];
  if ($changes)
    $msg['body'] = format_item_changes ($changes, $item_id, $res) . "\n";
  else
    $msg['body'] = format_item_summary ($res, $bug_ref, $artifact);
  $msg['bug_ref'] = $bug_ref;
  $msg['subject'] = utils_specialchars_decode ($res['summary'], ENT_QUOTES);
  # Necessary to mention the comment id (for delayed mails).
  if ($int_delayspamcheck_comment_id)
    $item_id .= ":$int_delayspamcheck_comment_id";
  return [$msg, $item_id];
}

function trackers_exclude_list ($artifact, $group_id, $force_exclude, $privacy)
{
  $exclude = '';
  if ($privacy == '2')
    {
      $result = db_execute ("
        SELECT {$artifact}_private_exclude_address
        FROM groups WHERE group_id = ?", [$group_id]
      );
      $exclude = db_result ($result, 0, "{$artifact}_private_exclude_address");
    }
  return trim ("$exclude,$force_exclude", ',');
}

function trackers_followup_mail_addresses (
  $res, $more_addresses, $exclude, $tracker, $changes
)
{
  global $sys_mail_replyto, $sys_mail_domain;
  foreach (['group_id', 'bug_id', 'privacy'] as $var)
    $$var = $res[$var];
  # See who is going to receive the notification.
  # Plus append any other email given at the end of the list.
  $addresses =
    trackers_build_notification_list ($res, $changes, $tracker);
  $to = join (',', $addresses);
  $to = trim ("$to,$more_addresses", ',');
  $from = user_getrealname (0, 1) . " <$sys_mail_replyto@$sys_mail_domain>";

  $exclude = trackers_exclude_list ($tracker, $group_id, $exclude, $privacy);
  return [$from, $to, $exclude];
}

function trackers_item_is_public ($privacy, $group_id)
{
  return $privacy != 2 && group_get_object ($group_id)->isPublic ();
}

function trackers_reported_subject ($subject)
{
  global $sys_new_user_watch_days, $sys_watch_anon_posts;

  if (!user_isloggedin ())
    {
      if (empty ($sys_watch_anon_posts))
        return null;
      return "anonymous post - $subject";
    }
  if (empty ($sys_new_user_watch_days))
    return null;
  $date_limit = time () - $sys_new_user_watch_days * 24 * 3600;
  $uid = user_getid ();
  if ($date_limit > user_get_field ($uid, 'add_date'))
    return null;
  $name = user_getname ($uid);
  return "post of new user #$uid <$name> - $subject";
}

function trackers_send_followup ($addresses, $message, $context)
{
  global $sys_mail_admin, $sys_mail_domain;
  if (empty ($context['group']))
    $context['group'] = group_getunixname ($context['group_id']);
  sendmail_mail ($addresses, $message, $context);
  $addresses['to'] = "$sys_mail_admin@$sys_mail_domain";
  $message['subject'] = trackers_reported_subject ($message['subject']);
  if (empty ($message['subject']))
    return;
  sendmail_mail ($addresses, $message, $context);
}

function trackers_append_followup_notif_addresses (
  &$addresses, $item_id, $updated = true, $tracker = null
)
{
  $tracker = $tracker !== null? $tracker: ARTIFACT;
  $additional_address =
    trackers_data_get_item_notification_info ($item_id, $tracker, $updated);
  if ($additional_address !== '' && trim ($addresses) !== '')
    $addresses .= ', ';
  $addresses .= $additional_address;
}

function trackers_mail_followup (
  $item_id, $addresses = '', $changes = false,
  $exclude_list = false, $tracker = null
)
{
  $tracker = $tracker !== null? $tracker: ARTIFACT;
  $res = trackers_mail_fetch_followup ($tracker, $item_id);
  if ($res === false)
    return;

  list ($msg, $item) =
    trackers_build_mail ($tracker, $res, $item_id, $changes);
  list ($from, $to, $exclude) =
    trackers_followup_mail_addresses (
      $res, $addresses, $exclude_list, $tracker, $changes
    );
  trackers_send_followup (
    ['from' => $from, 'to' => $to, 'exclude' => $exclude], $msg,
    ['group_id' => $res['group_id'], 'tracker' => $tracker, 'item' => $item]
  );
}

function trackers_upload_err_ini_size ()
{
  $ret = _('Uploaded file size exceeds configured value.');
  $size = ini_get ('upload_max_filesize');
  if ($size === false || $size === null)
    return $ret;
  $size = utils_ini_size_bytes ($size);
  $ret .=  ' ' . sprintf (
    _('Maximum upload file size is %s.'), utils_human_readable_size ($size)
  );
  return $ret;
}

function trackers_add_file_fb ($file)
{
  if ($file['name'] === '')
    return;
  $code = $file['error'];
  $msg0 = sprintf (_("Error while uploading file '%s':"), $file['name']);
  $msg = sprintf (_('Unknown file upload error code: %s.'), $code);
  $errors = [
    UPLOAD_ERR_INI_SIZE => trackers_upload_err_ini_size (),
    # UPLOAD_ERR_FROM_FILE is intendedly omitted:
    # we don't use MAX_FILE_SIZE in HTML forms.
    UPLOAD_ERR_PARTIAL => _('The file was only partially uploaded.'),
    UPLOAD_ERR_NO_FILE => _('No file was uploaded.'),
    UPLOAD_ERR_NO_TMP_DIR => _('Missing temporary directory.'),
    UPLOAD_ERR_CANT_WRITE => _('Failed to write file to disk.'),
    UPLOAD_ERR_EXTENSION => _('Upload was stopped by some PHP extension.'),
  ];
  if (!empty ($errors[$code]))
    $msg = $errors[$code];
  fb ("$msg0\n$msg", 1);
  # exit_error ($msg);
}

function trackers_add_file ($item_id, $file, $file_description, &$changes)
{
  if (empty ($file['emailed']) && $file['error'] != UPLOAD_ERR_OK)
    {
      trackers_add_file_fb ($file);
      return null;
    }
  if (empty ($file['tmp_name']))
    return null;

  $id = trackers_attach_file ($item_id, $file, $file_description, $changes);
  if (!$id)
    {
      if (is_file ($file['tmp_name']))
        unlink ($file['tmp_name']);
      return null;
    }
  $changes['attach'][] = [
    'name' => $file['name'], 'size' => $file['size'], 'id' => $id,
    'description' => $file_description
  ];
  return "file #$id";
}

# Wrapper for trackers_attach_file that will find out if one or more files
# were attached.
function trackers_attach_several_files ($item_id, $group_id, &$changes)
{
  # Reset the global used to count the current upload size.
  $GLOBALS['current_upload_size'] = 0;
  $comment = $filenames = [];
  for ($i = 1; $i < 5; $i++)
    $filenames[] = "input_file$i";
  $files = sane_import ('files', ['pass' => $filenames]);
  extract (sane_import ('post', ['specialchars' => 'file_description']));
  foreach ($files as $file)
    $comment[] = trackers_add_file (
      $item_id, $file, $file_description, $changes
    );
  unset ($GLOBALS['current_upload_size']);
  $comment = array_filter ($comment);
  if (empty ($comment))
    return [false, ''];
  return [true, "\n\n(" . join (', ', $comment) . ")"];
}

function trackers_upload_size_invalid ($file_name)
{
  if ($GLOBALS['current_upload_size'] >= 0)
    return false;
  # TRANSLATORS: the argument is file name.
  $msg = sprintf (
    _("Unexpected error, disregarding file %s attachment"), $file_name
  );
  fb ($msg, 1);
  return true;
}

function trackers_upload_size_notice ()
{
  $size = $GLOBALS['current_upload_size'];
  if (!$size)
    return '';
  # Explanation added when an upload is refused,
  # if the upload count is involved.
  return ' ' . sprintf (
    ngettext (
      "You already uploaded %s kilobyte.", "You already uploaded %s kilobytes.",
      $size),
    $size
  );
}

function trackers_attachments_dir_unaccessible ()
{
  global $sys_trackers_attachments_dir;
  if (is_writable ($sys_trackers_attachments_dir))
    return false;
  $msg = sprintf (
    _("The upload directory '%s' is not writable."),
    $sys_trackers_attachments_dir
  );
  fb ($msg, 1);
  return true;
}

function trackers_not_uploaded_file ($input_file, $file_name)
{
  if (is_uploaded_file ($input_file))
    return false;
  $msg = sprintf (
    _("File %s not attached: unable to open it"), $file_name
  );
  fb ($msg, 1);
  return true;
}

function trackers_get_upload_size ($file)
{
  $filesize = round (filesize ($file) / 1024);
  return $filesize + $GLOBALS['current_upload_size'];
}

function trackers_upload_size_limit_note ()
{
  return sprintf (
    _("(Note: upload size limit is set to %s, after insertion of\n"
      . "the required escape characters.)"),
    utils_human_readable_size ($GLOBALS['sys_upload_max'] * 1024)
  );
}

function trackers_file_too_big ($file, $file_name, $comment)
{
  if (trackers_get_upload_size ($file) <= $GLOBALS['sys_upload_max'])
    return false;
  $msg_not_attached = sprintf (
    "File %s not attached: its size is %s.",
    $file_name, utils_filesize ($file_name)
  );
  $msg_max_size = sprintf (
    "Maximum allowed file size is %s, after escaping characters as required.",
    utils_human_readable_size ($GLOBALS['sys_upload_max'] * 1024)
  );
  fb ("$msg_not_attached $msg_max_size$comment", 1);
  return true;
}

function trackers_file_is_empty ($file, $file_name, $size_comment)
{
  clearstatcache (true, $file);
  if (filesize ($file))
    return false;
  fb (sprintf (_("File %s is empty."), $file_name) . $size_comment, 1);
  return true;
}

function trackers_check_upload_size ($file, $file_name)
{
  if (trackers_upload_size_invalid ($file_name))
    return true;
  $size_comment = trackers_upload_size_notice ();
  if (trackers_file_is_empty ($file, $file_name, $size_comment))
    return true;
  return trackers_file_too_big ($file, $file_name, $size_comment);
}

function trackers_check_upload_params ($file, $file_name)
{
  if (trackers_attachments_dir_unaccessible ())
    return true;
  if (
    empty ($file['emailed'])
    && trackers_not_uploaded_file ($file['tmp_name'], $file_name)
  )
    return true;
  return trackers_check_upload_size ($file['tmp_name'], $file_name);
}

function trackers_insert_attachment ($params)
{
  $params['artifact'] = ARTIFACT;
  $params['date'] = time ();
  $params['filetype'] = $params['file']['type'];
  $params['filesize'] = $params['file']['size'];
  unset ($params['file']);
  $res = db_autoexecute ('trackers_file', $params, DB_AUTOQUERY_INSERT);
  if ($res)
    return db_insertid ($res);
  fb (sprintf (_("Error while attaching file %s"), $file_name), 1);
  return false;
}

function trackers_move_attachment ($file, $file_id, $file_name)
{
  global $sys_trackers_attachments_dir;
  if (rename ($file, "$sys_trackers_attachments_dir/$file_id"))
    return false;
  fb (sprintf (_("Error while saving file %s on disk"), $file_name), 1);
  return true;
}

function trackers_post_attach ($item_id, $file_id, $file_name)
{
  # TRANSLATORS: the argument is file id (a number).
  fb (sprintf (_("file #%s attached"), $file_id));
  trackers_data_add_history (
    "Attached File", "-", "Added $file_name, #$file_id", $item_id, 0, 0, 1
  );
  if (user_isloggedin () && !user_get_preference ("skipcc_updateitem"))
    trackers_add_cc ($item_id, user_getid (), TRACKERS_CC_UPDATED);
}

function trackers_attach_file ($item_id, $file, $description, &$changes)
{
  global $current_upload_size;
  $file_name = preg_replace ('/[&<\s"\';?!*]/', '@', $file['name']);
  $user_id = (user_isloggedin ()? user_getid (): 100);
  if (trackers_check_upload_params ($file, $file_name))
    return false;
  $current_upload_size = trackers_get_upload_size ($file['tmp_name']);
  $file_id = trackers_insert_attachment ([
    'item_id' => $item_id, 'submitted_by' => $user_id,
    'description' => $description, 'filename' => $file_name, 'file' => $file
  ]);
  if (!$file_id)
    return false;
  if (trackers_move_attachment ($file['tmp_name'], $file_id, $file_name))
    return false;
  trackers_post_attach ($item_id, $file_id, $file_name);
  return $file_id;
}

function trackers_exist_cc ($item_id, $cc)
{
  $res = db_execute ("
    SELECT bug_cc_id FROM " . ARTIFACT . "_cc WHERE bug_id = ? AND email = ?",
    [$item_id, $cc]
  );
  return db_numrows ($res) >= 1;
}

function trackers_extract_cc_comment ($comment)
{
  if (!is_array ($comment))
    # The string wasn't directly supplied by the user; pass as is.
    return [$comment, false];
  $comment = join ('', $comment);
  if (preg_match ('/^' . TRACKERS_CC_REGEX . '$/', $comment))
    # Make sure the comment differs from special values.
    return [TRACKERS_CC_AFFIX . $comment, true];
  return [$comment, true];
}

function trackers_insert_cc ($item_id, $cc, $added_by, $comment, $date)
{
  list ($comment, $manual) = trackers_extract_cc_comment ($comment);
  $res = db_autoexecute (
    ARTIFACT . "_cc",
    [
      'bug_id' => $item_id, 'email' => $cc, 'added_by' => $added_by,
      'comment' => $comment, 'date' => $date
    ],
    DB_AUTOQUERY_INSERT
  );

  # Store the change in history only if the CC was a manual add, not a direct
  # effect of another action.
  if ($manual || $comment === TRACKERS_CC_SUBSCRIBED)
    trackers_data_add_history (
      "Carbon-Copy", "-", "Added $cc", $item_id, 0, 0, 1
    );
  return $res;
}

function trackers_add_cc_fb ($ok, $changed)
{
  if ($ok)
    {
      if ($changed)
        fb (_("CC added."));
      return;
    }
  fb (_("CC addition failed."), 1);
}

function trackers_add_cc_pref ($comment)
{
  if (is_array ($comment))
    return false;
  if (!user_isloggedin ())
    return false;
  $prefs = [
    TRACKERS_CC_COMMENTED => 'skip_postcomment',
    TRACKERS_CC_UPDATED => 'skip_updateitem'
  ];
  if (empty ($prefs[$comment]))
    return false;
  return user_get_preference ($prefs[$comment]);
}

function trackers_add_cc ($item_id, $email, $comment)
{
  $user_id = (user_isloggedin ()? user_getid (): 100);
  if (trackers_add_cc_pref ($comment))
    return;

  $arr_email = utils_split_emails ($email);
  $date = time();
  $ok = true;
  $changed = false;

  foreach ($arr_email as $cc)
    {
      if (trackers_exist_cc ($item_id, $cc))
        continue;
      $changed = true;
      if (!trackers_insert_cc ($item_id, $cc, $user_id, $comment, $date))
        $ok = false;
    }
  trackers_add_cc_fb ($ok, $changed);
  return $ok;
}

function trackers_add_cc_history_rm ($email, $item_id)
{
  fb (_("CC Removed"));
  trackers_data_add_history (
    "Carbon-Copy", "Removed $email", "-", $item_id, 0, 0, 1
  );
}

function trackers_delete_cc ($group_id, $item_id, $item_cc_id)
{
  # Extract data about the CC.
  $res1 = db_execute ("
    SELECT * FROM " . ARTIFACT . "_cc WHERE bug_cc_id = ?", [$item_cc_id]
  );
  if  (!db_numrows ($res1))
    {
      # No result? Stop here silently (assume that someone tried to remove
      # an already removed CC).
      return false;
    }

  # If both bug_id and bug_cc_id are given make sure the cc belongs
  # to this bug (it is a bit paranoid but...)
  if ($item_id && db_result ($res1, 0, 'bug_id') != $item_id)
    # No feedback, too weird case, probably malicious.
    return false;

  # If group id was passed, do checks on users privileges.
  if ($group_id)
    {
      $email = db_result ($res1, 0, 'email');
      $added_by = db_result ($res1, 0, 'added_by');
      $user_id = user_getid ();

      # Remove if
      # - current user is a tracker manager
      # - the CC name is the current user
      # - the CC email address matches the one of the current user
      # - the current user is the person who added a given name in CC list
      if (!member_check (0, $group_id, 2))
        if (
          $user_id != $email && $user_id != $added_by
          && user_getname ($user_id) != $email
          && user_getemail ($user_id) != $email
        )
          {
            fb (_("Removing CC is not allowed"), 1);
            return false;
          }
    } # if ($group_id)

  # Now delete the CC address.
  $res2 = db_execute ("
    DELETE FROM " . ARTIFACT . "_cc WHERE bug_cc_id = ?",
    [$item_cc_id]
  );
  if (!$res2)
    {
      fb (_("Failed to remove CC"), 1);
      return false;
    }
  trackers_add_cc_history_rm (db_result ($res1, 0, 'email'), $item_id);
  return true;
}

# Remove the uid from an item CC list.
function trackers_delete_cc_by_user ($item_id, $user_id)
{
  # A user may be in CC of an item in different ways: as uid, as username,
  # as email.  We will try them all, to make sure the user is properly removed
  # from CC.
  if (!user_exists ($user_id))
    return false;

  $result = db_execute ("
    DELETE FROM " . ARTIFACT . "_cc
    WHERE bug_id = ? AND (email = ? OR email = ? OR email = ?)",
    [$item_id, $user_id, user_getname ($user_id), user_getemail ($user_id)]
  );
  return $result === true || db_numrows ($result);
}

function trackers_delete_dependency (
  $group_id, $item_id, $item_depends_on, $item_depends_on_artifact, &$changes
)
{
  # Can be done only by at least technicians.
  # Note that is it possible to fake the system by providing a false group_id.
  # But well, consequences would be small an it will be easy to identify
  # the criminal.

  if (member_check (0, $group_id, 1))
    $result = db_execute ("
      DELETE FROM " . ARTIFACT . "_dependencies
      WHERE
        item_id = ? AND is_dependent_on_item_id = ?
        AND is_dependent_on_item_id_artifact = ?",
      [$item_id, $item_depends_on, $item_depends_on_artifact]
    );

  if (!$result)
    {
      fb (_("Failed to delete dependency.") . db_error ($result), 0);
      return false;
    }
  fb (_("Dependency Removed."));
  trackers_data_add_history (
    "Dependencies",
    "Removed dependency to $item_depends_on_artifact #$item_depends_on",
    "-", $item_id, 0, 0, 1
  );
  trackers_data_add_history (
    "Dependencies",
    "Removed dependency from " . ARTIFACT . " #$item_id",
    "-", $item_depends_on, 0, $item_depends_on_artifact, 1
  );

  $changes['Dependency Removed']['add'] =
    "$item_depends_on_artifact #$item_depends_on";
  return true;
}

# The ANY value is 0. The simple fact that
# ANY (0) is one of the value means it is Any even if there are
# other non zero values in the array.
function trackers_isvarany ($var)
{
  if (!is_array ($var))
    return $var == 0;
  foreach ($var as $v)
    {
      if ($v == 0)
        return true;
    }
  return false;
}

# Check is a sort criteria is already in the list of comma-separated
# criteria. If so, invert the sort order, else simply add it.
function trackers_add_sort_criteria ($criteria_list, $order, $msort)
{
  $found = false;
  if ($criteria_list)
    {
      $arr = explode (',', $criteria_list);
      $i = 0;
      foreach ($arr as $attr)
        {
          preg_match ("/\s*([^<>]*)([<>]*)/", $attr, $match_arr);
          list (, $mattr, $mdir) = $match_arr;
          if ($mattr == $order)
            {
              if (($mdir == '>') || (!isset ($mdir)))
                $arr[$i] = "$order<";
              else
                $arr[$i] = "$order>";
              $found = true;
            }
          $i++;
        }
    }

  if ($found)
    return join (',', $arr);
  if (!$msort)
    $arr = [];
  if (
    $order == 'severity' || $order == 'hours'
    || trackers_data_is_date_field ($order)
  )
    # Severity, effort and dates sorted in descending order by default.
    $arr[] = "$order<";
  else
    $arr[] = "$order>";
  return join (',', $arr);
}

# Filter out invalid criteria.
function trackers_filter_morder ($criteria_list, $shown_fields)
{
  $criteria = explode (',', $criteria_list);
  $fields = ['bug_id' => 1, 'priority' => 1]; # These fields are always present.
  foreach ($shown_fields as $field)
    $fields[$field] = 1;
  $fields = array_keys ($fields);
  $regexp = "/^(" . join ('|', $fields) . ')[<>]?$/';
  $criteria_filtered = [];
  foreach ($criteria as $cr)
    if (preg_match ($regexp, $cr))
      $criteria_filtered[] = $cr;
  return join (',', $criteria_filtered);
}

# Transform criteria list to SQL query ('>' means ascending, '<' descending).
function trackers_criteria_list_to_query ($criteria_list)
{
  $criteria_list = str_replace (['>', '<'], [' ASC', ' DESC'], $criteria_list);
  # Undo the uid->user_name trick to avoid "Column 'submitted_by' in
  # order clause is ambiguous" error. This is pretty ugly. Also check
  # trackers_data_is_username_field().
  return str_replace (['submitted_by ', 'assigned_to '],
    ['user_submitted_by.user_name ', 'user_assigned_to.user_name '],
    $criteria_list
  );
}

function trackers_sorting_order_img ($crit)
{
  #TRANSLATORS: this string specifies sorting order.
  $so = ['image' => 'up', 'text' => _('up')];
  if (substr ($crit, -1) == '>')
    #TRANSLATORS: this string specifies sorting order.
    $so = ['image' => 'down', 'text' => _('down')];
  $img_src = "arrows/{$so['image']}.png";
  return html_image ($img_src,  ['alt' => $so['text'], 'class' => 'icon']);
}

# Return a single link from the list of criteria
# built in trackers_criteria_list_to_text ().
function trackers_sorting_link ($url, $morder, $crit)
{
  $field = str_replace (['<', '>'], '', $crit);
  return "<a href=\"$url&amp;morder=$morder#results\">"
    . trackers_data_get_label ($field) . '</a>'
    . trackers_sorting_order_img ($crit);
}

# Transform criteria list to readable text statement.
# $url must not contain the morder parameter.
function trackers_criteria_list_to_text ($criteria_list, $url)
{
  if (empty ($criteria_list))
    return '';
  $morder = '';
  foreach (explode (',', $criteria_list) as $crit)
    {
      $morder .= $crit;
      $links[] = trackers_sorting_link ($url, $morder, $crit);
      $morder .= ',';
    }
  # The links cut the sequence of ordering at the current field,
  # which makes no sense for the last item, so we make it change
  # the order asc <-> desc.
  $anc = '#results';
  $last = strtr (end ($links), [">$anc" => "<$anc", "<$anc" => ">$anc"]);
  $links [key ($links)] = $last;
  return join (' &gt; ', $links);
}

function trackers_build_match_text ($field, &$to_match)
{
  $to_match = utils_specialchars ($to_match);
  $params = [];
  # If it is sourrounded by /.../ the assume a regexp
  # else transform into a series of LIKE %word%.
  if (preg_match ('/^\s*\/(.*)\/\s*$/', $to_match, $matches))
    return [" $field RLIKE ? ", [$matches[1]]];
  $words = preg_split ('/\s+/', $to_match);
  foreach ($words as $i => $w)
    {
      $words[$i] = "$field LIKE ?";
      $params[] = "%$w%";
    }
  $expr = join (' AND ', $words);
  return [" ($expr) ", $params];
}

function trackers_build_match_int_cmp ($field, &$to_match, $matches)
{
  $matches[2] = (string)((int)$matches[2]);
  $to_match = $matches[1] . ' ' . $matches[2];
  return [" ($field {$matches[1]} ?) ", [$matches[2]]];
}

function trackers_build_match_int_range ($field, &$to_match, $matches)
{
  $matches[1] = (string)((int)$matches[1]);
  $matches[2] = (string)((int)$matches[2]);
  $params = [$matches[1], $matches[2]];
  $to_match = $matches[1] . '-' . $matches[2];
  return [" ($field >= ? AND $field <= ?) ", $params];
}

function trackers_build_match_int_exact ($field, &$to_match, $matches)
{
  $param = (string)((int)$matches[1]);
  $to_match = $param;
  return [" $field = ? ", [$param]];
}

function trackers_build_match_int ($field, &$to_match)
{
  # If it is sourrounded by /.../ then assume a regexp
  # else assume an equality.
  if (preg_match ('/\/(.*)\#/', $to_match, $matches))
    return [" $field RLIKE ? ", $matches[1]];
  $int_reg = '[+\-]*[0-9]+';
  if (preg_match ("/\s*(<|>|>=|<=)\s*($int_reg)/", $to_match, $matches))
    return trackers_build_match_int_cmp ($field, $to_match, $matches);
  if (preg_match ("/\s*($int_reg)\s*-\s*($int_reg)/", $to_match, $matches))
    return trackers_build_match_int_range ($field, $to_match, $matches);
  if (preg_match ("/\s*($int_reg)/", $to_match, $matches))
    trackers_build_match_int_exact ($field, $to_match, $matches);
  # Invalid syntax - no condition.
  $to_match = '';
  return [' 1 ', []];
}

function trackers_build_match_expression ($field, &$to_match)
{
  # First get the field type.
  $res = db_execute ("SHOW COLUMNS FROM " . ARTIFACT . " LIKE ?", [$field]);
  $type = db_result ($res, 0, 'Type');
  $field = "a.$field";

  if (preg_match ('/text|varchar|blob/i', $type))
    return trackers_build_match_text ($field, $to_match);
  if (preg_match ('/int/i', $type))
    return trackers_build_match_int ($field, $to_match);
  # For all other types, use =.
  return [" $field = ? ", [$to_match]];
}

# Register a msg id for an item update notification.
function trackers_register_msgid ($msgid, $artifact, $item_id)
{
  return db_affected_rows (
    db_autoexecute (
      "trackers_msgid",
      ['msg_id' => $msgid, 'artifact' => $artifact, 'item_id' => $item_id],
      DB_AUTOQUERY_INSERT
    )
  );
}

# Get a list of msg ids for item update notification
# (the References and In-Reply-To headers), in the reverse chronological order.
function trackers_get_msgid ($artifact, $item_id)
{
  $result = db_execute ("
    SELECT msg_id FROM trackers_msgid WHERE artifact = ? AND item_id = ?
    ORDER BY id DESC", [$artifact, $item_id]
  );
  $msg_ids = [];
  while ($row = db_fetch_array ($result))
    $msg_ids[] = "<{$row['msg_id']}>";
  return $msg_ids;
}

function trackers_fetch_item_access_data ($item_id, $user_id)
{
  $fields = utils_find_item (
    ARTIFACT, $item_id, ['privacy', 'discussion_lock', 'submitted_by'],
    function ($x) { return null; }
  );
  if ($fields === null)
    return null;

  $group = project_get_object ($fields['group_id']);
  if ($group->isError ())
    exit_no_group ();

  $fields['is_trackeradmin'] = member_check ($user_id, $fields['group_id'], 2);
  if (user_is_super_user () && $user_id == user_getid ())
    $fields['is_trackeradmin'] = true;
  return $fields;
}

function trackers_may_user_comment ($user_id, $item_id)
{
  $fields = trackers_fetch_item_access_data ($item_id, $user_id);
  if (empty ($fields))
    return [false, $fields];
  if (
    $fields['privacy'] == 2 && !$fields['is_trackeradmin']
    && !member_check ($user_id, $fields['group_id'])
    && $fields['submitted_by'] != $user_id
  )
    return [false, $fields];
  if ($fields['discussion_lock'] && !$fields['is_trackeradmin'])
    return [false, $fields];
  $ret = group_restrictions_check (
    $fields['group_id'], ARTIFACT, TRACKER_EVENT_COMMENT
  );
  return [$ret, $fields];
}

function trackers_search_report_failure ()
{
  print "<br />\n<span class='warn'>"
    . _("None found. Please note that only search words of more than\n"
        . "three characters are valid.");
  print '</span>';
  return 0;
}
function tracker_list_dependency ($row, $tracker, $only_group)
{
  global $item_id;
  # Avoid item depending on itself.  Hide private items unless accessible.
  if ($row['privacy'] == 2 && !member_check_private (0, $row['group_id']))
    return false;
  if ($row['bug_id'] == $item_id && $tracker === ARTIFACT)
    return false;
  print "<br />\n";
  $label = "$tracker #{$row['bug_id']}: {$row['summary']}";
  if (!$only_group)
    $label .= ', ' . _("group") . ' ' . group_getname ($row['group_id']);
  print '&nbsp;&nbsp;&nbsp;'
    . form_checkbox (
        "dependent_on_{$tracker}[]", 0,
        ['value' => $row['bug_id'], 'label' => $label]
      );
  return true;
}

function trackers_list_dependencies_in_tracker ($search, $tracker, $only_group)
{
  $result = search_run ($search, $tracker, 0);
  if (!db_numrows ($result))
    return false;
  $anything_found = 0;
  while ($row = db_fetch_array ($result))
    $anything_found |= tracker_list_dependency ($row, $tracker, $only_group);
  return $anything_found;
}

function trackers_search_dependencies_header ($search)
{
  print "<p><span class='preinput'>";
  printf (
    _("Please select a dependency to add in the result of your search\n"
      . "of '%s' in the database:"),
    utils_specialchars ($search)
  );
  print '</span>';
}

function trackers_search_deps ($search, $artifact, $only_group)
{
  # If we have less than 4 characters, to avoid giving lot of feedback
  # and put an exit to the report, just consider the search as a failure.
  if (strlen ($search) < 4)
    return false;
  if ($only_group)
    $GLOBALS['only_group_id'] = $GLOBALS['group_id'];
  $GLOBALS['exact'] = 0;  # Do not ask for all words,
  if ($artifact == "all")
    $artifacts = utils_get_dependable_trackers ();
  else
    $artifacts = [$artifact];
  $anything_found = false;
  foreach ($artifacts as $tracker)
    $anything_found |=
      trackers_list_dependencies_in_tracker ($search, $tracker, $only_group);
  return $anything_found;
}

function trackers_search_dependencies ($search, $artifact, $only_group)
{
  if (!$search)
    return;
  trackers_search_dependencies_header ($search);
  $only_group = ($only_group == 'notany');
  $anything_found = trackers_search_deps ($search, $artifact, $only_group);
  if ($anything_found)
    return;
  trackers_search_report_failure ();
}

function trackers_get_row_class ($field_name = '', $reset = false)
{
  static $j = 0;
  if ($reset !== false)
    {
      if ($reset >= 0)
        $j = $reset;
      if ($reset === true)
        $j = 0;
    }
  $ret = '';
  # We keep the original submission with the default background color.
  # We also use the boxitem background color only one time out of two,
  # to keep the page light.
  if ($j % 2 && $field_name != 'details')
    $ret = ' class="' . utils_altrow ($j + 1) . '"';
  if ($reset === false || $reset < 0)
    $j++;
  return $ret;
}

function trackers_handle_item_subscription ($func, $item_id, $group_id)
{
  if (!user_isloggedin ())
    return;
  if ($func === 'unsubscribe')
    {
      trackers_delete_cc_by_user ($item_id, user_getid ());
      trackers_add_cc_history_rm (user_getname (), $item_id);
    }
  else
    trackers_add_cc ($item_id, user_getname (), TRACKERS_CC_SUBSCRIBED);
  trackers_init ($group_id);
}

function trackers_get_field_class ($field)
{
  global $previous_form_bad_fields;
  if ($previous_form_bad_fields
      && array_key_exists ($field, $previous_form_bad_fields))
    return ' class="highlight"';
  return '';
}

function trackers_print_new_row (&$i, $field)
{
  global $fields_per_line;
  if ($i % $fields_per_line)
    return;
  $row_class = trackers_get_row_class ($field);
  print "\n<tr$row_class>";
  $i = 0;
}

function trackers_print_field_cell ($field, $label, $value)
{
  global $max_size, $fields_per_line;
  static $i = 0;
  list ($sz,) = trackers_data_get_display_size ($field);
  $td = "<td valign='middle'" . trackers_get_field_class ($field);
  # If field size is greater than max_size, then force it to appear alone
  # on a new line or it won't fit in the page.
  if ($sz > $max_size)
    {
      $i = 0;
      trackers_print_new_row ($i, $field);
      print "$td width='15%'>$label</td>\n$td colspan='"
        . (2 * $fields_per_line - 1) . "' width='75%'>$value</td>\n</tr>\n";
      return;
    }
  trackers_print_new_row ($i, $field);
  print "$td width='15%'>$label</td>\n$td width='35%'>$value</td>\n";
  print (++$i % $fields_per_line? "\n": "</tr>\n");
}

function trackers_anon_captcha ($value = '')
{
  return '<p class="noprint">'
    . _("Please enter the title of <a\n"
        . "href=\"https://en.wikipedia.org/wiki/George_Orwell\">George "
        . "Orwell</a>'s famous\ndystopian book (it's a date):")
    . "\n" . form_input ('text', 'check', $value) . "</p>\n";
}
?>
