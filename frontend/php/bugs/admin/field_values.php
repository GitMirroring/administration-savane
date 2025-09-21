<?php
# Edit or display field values.
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

require_once ('../../include/init.php');
require_once ('../../include/trackers/general.php');

exit_if_no_group ();
$is_admin = user_is_group_admin ();
if (!(group_get_object ($group_id)->isPublic () || user_ismember ($group_id)))
  exit_permission_denied ();

extract (sane_import ('get', ['true' => ['list_value']]));
extract (sane_import ('request', ['name' => 'field']));

$func = $post_changes = $by_field_id = $to = $from = $title = $update_value =
  $create_canned = $update_canned = null;
if ($is_admin)
  {
    extract (sane_import ('request',
      [
        'strings' => [['func', ['deltransition', 'delcanned']]],
        'true' => ['update_value', 'create_canned', 'update_canned'],
        'digits' => ['fv_id', 'item_canned_id']
      ]
    ));
    extract (sane_import ('get', ['digits' => 'transition_id']));
    extract (sane_import ('post',
      [
        'true' => ['post_changes', 'create_value', 'by_field_id', 'submit'],
        'specialchars' => ['title', 'description', 'body'],
        'digits' => ['order_id', 'from', 'to'],
        'strings' =>
          [
            ['allowed', $TRANSITION_STATUS_LIST],
            ['status', $FIELD_STATUS_LIST]
          ],
        'preg' => [['mail_list', '/^[-+_@.,\s\da-zA-Z]*$/']]
      ]
    ));
    form_check (['create_value', 'post_changes', 'submit']);
  } # $is_admin

if (empty ($order_id))
  $order_id = 0;

$delete_canned = $func === 'delcanned';

trackers_init ($group_id);

function delete_transition ($transition_id)
{
  $result = db_execute ("
    DELETE FROM trackers_field_transition WHERE transition_id = ? LIMIT 1",
    [$transition_id]
  );
  if ($result)
    fb (_("Transition deleted"));
  else
    fb (_("Error deleting transition"), 1);
}

function delete_response ($group_id, $item_canned_id)
{
  $result = db_execute ("
    DELETE FROM " . ARTIFACT . "_canned_responses
    WHERE group_id = ? AND bug_canned_id = ?",
    [$group_id, $item_canned_id]
  );
  if ($result)
    fb (_("Canned response deleted"));
  else
    fb (_("Error deleting canned response"), 1);
}

if ($func == "deltransition")
  delete_transition ($transition_id);
elseif ($delete_canned)
  delete_response ($group_id, $item_canned_id);
elseif ($post_changes)
  {
    # A form of some sort was posted to update or create an existing value.
    # Deleted Canned doesn't need a form, so let switch into this code.
    if ($create_value)
      trackers_data_create_value (
        $field, $group_id, $title, $description, $order_id
      );
    elseif ($update_value)
      trackers_data_update_value (
        $fv_id, $field, $group_id, $title, $description, $order_id, $status
      );
    elseif ($create_canned)
      trackers_data_create_canned ($title, $body, $order_id, $group_id);
    elseif ($update_canned)
      trackers_data_update_canned ($title, $body, $order_id, $item_canned_id);
  }

$field_id = $by_field_id? $field: trackers_data_get_field_id ($field);

if ($to != $from)
  {
    # A form was posted to update or create a transition.
    if (trackers_data_transition_exists ($field_id, $from, $to))
      trackers_data_update_transition (
        $field_id, $from, $to, $mail_list, $allowed
      );
    else
      trackers_data_create_transition (
        $field_id, $from, $to, $mail_list, $allowed
      );
  } # ($to != $from)

function td_select_box ($field)
{
  if (
    trackers_data_get_field_id ($field) && trackers_data_is_select_box ($field)
  )
    return;
  # TRANSLATORS: the argument is field.
  $msg = sprintf (
    _("The field you requested '%s' is not used by your group "
      . "or you are not\nallowed to customize it"), $field
  );
  exit_error ($msg);
}

function print_predefined_val_entry ($fld_val, &$defs)
{
  extract ($fld_val);
  # Non-active value are not important here.
  if ($status != FIELD_STATUS_ACTIVE)
    return;
  $defs["<b>$value</b> ($order_id)"] = $description;
}

function list_predefined_values ($field)
{
  $res = trackers_data_get_field_predefined_values (
    $field, '100', false, false, false
  );
  if (!db_numrows ($res))
    {
      $msg =
        _("No default values found. You should report this problem to\n"
          . "administrators.");
      fb ($msg, 1);
      return;
    }
  $defs = [];
  while ($fld_val = db_fetch_array ($res))
    print_predefined_val_entry ($fld_val, $defs);
  print html_dl ($defs);
}

function print_field_header ($field, $title, $group)
{
  global $sys_home, $is_admin;
  # TRANSLATORS: the argument is field label.
  $title =
    sprintf (_("Field values for '%s'"), trackers_data_get_label ($field));
  trackers_header_admin (['title' => $title]);
  print '<p><span class="smaller">';
  $url = "field_values.php?group=$group";
  # TRANSLATORS: this is a link.
  print utils_link ($url, _("Field list")) . "\n";
  $url = $sys_home . ARTIFACT . "/admin/field_usage.php?group=$group"
    . "&amp;update_field=1&amp;field=$field";
  if ($is_admin)
    # TRANSLATORS: this is a link.
    print "<br />\n" . utils_link ($url, _("This field usage")) . "\n";
  print '</span></p>';
}

function fetch_field_values ($field)
{
  global $group_id;
  $result = trackers_data_get_field_predefined_values (
    $field, $group_id, false, false, false
  );
  # First check that this field is used by the group and
  # it is in the group scope.
  if (!db_numrows ($result))
    # TRANSLATORS: the  argument is field label.
    printf (html_h (2, _("No values defined yet for %s")),
      trackers_data_get_label ($field)
    );
  return $result;
}

function value_table_header ($field, $is_group_scope)
{
  global $is_admin;
  $ret = html_h (2, _("Existing Values"));
  $title_arr =  [_("Value label"), _("Description")];
  if ($is_admin)
    array_push ($title_arr, _("Rank"), _("Status"), _("Occurrences"));
  if (!$is_group_scope && $is_admin)
    array_unshift ($title_arr, _('ID'));
  return $ret . html_build_list_table_top ($title_arr);
}

function put_field_val_in_tr ($status, $html)
{
  static $i = ['a' => 0, 'h' => 0];
  $suff = 'h';
  if (in_array ($status, $GLOBALS['FIELD_STATUS_ENABLED']))
    $suff = 'a';
  $class = utils_altrow ($i[$suff]);
  $html = "<tr class=\"$class\">$html</tr>\n";
  $i[$suff]++;
  if ($suff == 'h')
    return [$html, null];
  return [null, $html];

}

function get_usage_string ($field, $value_id, $status)
{
  global $group_id;
  $usage = trackers_data_count_field_value_usage ($group_id, $field, $value_id);
  if ($status == FIELD_STATUS_HIDDEN && $usage > 0)
    $usage = "<strong class='warn'>$usage</strong>";
  return "<td align='center'>$usage</td>\n";
}

function format_field_value ($fld_val, $field)
{
  global $is_admin, $php_self, $group_id;
  $txt_val = $fld_val['value'];

  if ($is_admin
        # The permanent values can't be modified.
    && $fld_val['status'] != FIELD_STATUS_PERMANENT
    && !in_array ($field, ['assigned_to', 'submitted_by']) # Neither can users.
  )
    $txt_val = "<a href=\"$php_self?update_value=1"
      . "&fv_id={$fld_val['bug_fv_id']}&field=$field&group_id=$group_id"
      . "\">$txt_val</a>";
  return "<td>$txt_val</td>\n";
}

function list_field_values ($fld_val, $field, $is_group_scope)
{
  global $none_rk, $is_admin;

  extract ($fld_val);
  $html = '';
  # Keep the rank of the 'None' value in mind if any.
  if ($value == 100)
    $none_rk = $order_id;

  # Show the value ID only for system-wide fields whose value id is fixed
  # and serve as a guide.
  if (!$is_group_scope && $is_admin)
    $html .= "<td>$value_id</td>\n";
  $html .= format_field_value ($fld_val, $field)
    .  "<td>$description</td>\n";
  if ($is_admin)
    $html .= "<td align='center'>$order_id</td>\n"
      . "<td align='center'>{$GLOBALS['FIELD_STATUS_LABELS'][$status]}</td>\n"
      . get_usage_string ($field, $value_id, $status);
  return put_field_val_in_tr ($status, $html);
}

function print_field_values ($hidden, $active, $hdr)
{
  global $is_admin;
  $ha = join ('', $active);
  $hh = '';
  if ($is_admin)
    {
      if (empty ($active))
        $hdr = '<p>'
          . _("No active value for this field. Create one or "
              . "reactivate a hidden value (if\nany)")
          . "</p>\n$hdr";
      else
        $ha = '<tr><td colspan="4" class="center"><b>'
          . _("---- ACTIVE VALUES ----") . "</b></tr>\n$ha";
      if (!empty ($hidden))
        $hh = "<tr><td colspan=\"4\"> &nbsp;</td></tr>\n"
          .' <tr><td colspan="4"><center><b>'
          . _("---- HIDDEN VALUES ----")
          . "</b></center></tr>\n" . join ('', $hidden);
    }
  print "$hdr$ha$hh</table>\n";
}

function show_existing_fields ($field, $is_group_scope)
{
  $result = fetch_field_values ($field);
  if (!db_numrows ($result))
    return;
  # Display the list of values in 2 blocks: active first,
  # hidden second.
  $hidden = $active = [];
  while ($fld_val = db_fetch_array ($result))
    {
      if (empty ($fld_val['description']))
        $fld_val['description'] = '&nbsp;';
      list ($hidden[], $active[]) =
        list_field_values ($fld_val, $field, $is_group_scope);
    }
  $hidden = array_filter ($hidden);
  $active = array_filter ($active);
  $hdr = value_table_header ($field, $is_group_scope);
  print_field_values ($hidden, $active, $hdr);
  return !empty ($hidden);
}

function print_create_caption ($field, $have_hidden)
{
  print html_h (2, _("Create a new field value"));
  if (!$have_hidden)
    return;
  print '<p>'
    . _("Before you create a new value make sure there isn't one "
    . "in the hidden list\nthat suits your needs.")
    . "</p>\n";
}

function print_create_form_hidden ($field)
{
  global $group_id;
  print form_tag () . form_hidden (
      [
        'post_changes' => 'y', 'create_value' => 'y', 'list_value' => 'y',
        'field' => $field, 'group_id' => $group_id
      ]
    );
}

function print_create_field_none_rk ()
{
  global $none_rk;
  if (!isset ($none_rk))
    return;
  print "&nbsp;&nbsp;<strong> ";
  # TRANSLATORS: the argument is minimum rank value;
  # the string is used like "Rank: (must be > %s)".
  printf (_("(must be &gt; %s)"), $none_rk);
  print "</strong></p>\n";
}

function show_create_field_value ($field, $have_hidden)
{
  global $group_id;
  print_create_caption ($field, $have_hidden);
  print_create_form_hidden ($field);
  print '<span class="preinput">'
    . html_label ('title', _("Value:")) . '</span>&nbsp;'
    . form_input ("text", "title", "", 'size="30" maxlength="60"')
    . "\n&nbsp;&nbsp;<span class='preinput'>"
    . html_label ('order_id', _("Rank:")) . '</span>&nbsp;'
    . form_input ("text", "order_id", "", 'size="6" maxlength="6"');
  print_create_field_none_rk ();
  print "<p><span class='preinput'>"
    . html_label ('description', _("Description (optional):"))
    . "</span><br />\n"
    . form_textarea ('description', '',
       "rows='4' cols='65' wrap='hard'")
    . "</p>\n" . form_footer (_("Update"), 'submit');
}

function show_reset_field_values ($field)
{
  global $group_id;
  print html_h (2, _("Reset values"));
  print '<p>'
    . _("You are currently using custom values. If you want "
        . "to reset values to the\ndefault ones, use the following "
        . "form:")
    . "</p>\n\n"
    . form_tag (
        ['action' => 'field_values_reset.php', 'class' => 'center']
      )
    . form_hidden (['group_id' => $group_id, 'field' => $field])
    . form_footer (_("Reset values"), 'submit') . "<p>"
    . _("For your information, the default active values are:")
    . "</p>\n";

  list_predefined_values ($field);
}

function get_val_label ($field, $by_field_id)
{
  global $group_id;
  $field_id = $by_field_id? $field: trackers_data_get_field_id ($field);
  $sql = '
    SELECT `value_id`, `value` FROM `' . ARTIFACT . '_field_value`
    WHERE `group_id` = ? AND `bug_field_id` = ?';
  # Get all the value_id - value pairs.
  $res = db_execute ($sql, [$group_id, $field_id]);

  if (!db_numrows ($res))
    $res = db_execute ($sql, [100, $field_id]);

  if (!db_numrows ($res))
    return [$field_id, []];
  $val_label = [];
  while ($val_row = db_fetch_array ($res))
    {
      $value_id = $val_row['value_id'];
      $value = $val_row['value'];
      $val_label[$value_id] = $value;
    }
  return [$field_id, $val_label];
}

function fetch_transitions ($field_id, $field)
{
  global $group_id;
  $result = db_execute ('
    SELECT
      `transition_id`, `from_value_id`, `to_value_id`, `is_allowed`,
      `notification_list`
    FROM `trackers_field_transition`
    WHERE `group_id` = ? AND `artifact` = ?  AND `field_id` = ?',
    [$group_id, ARTIFACT, $field_id]
  );
  if (db_numrows ($result))
    return $result;
  print "\n\n<p>&nbsp;</p>\n";
  # TRANSLATORS: the argument is field name.
  printf (html_h (2, _("No transition defined yet for %s")),
    trackers_data_get_label ($field)
  );
  return null;
}

function print_transition_label ($transition, $val_label)
{
  if (empty ($val_label[$transition['from_value_id']]))
    # TRANSLATORS: this refers to transitions.
    $txt = _("* - Any");
  else
    $txt = $val_label[$transition['from_value_id']];
  print "<td align='center'>$txt</td>\n";
}

function print_transition_allowed ($transition, $val_label)
{
  if ($transition['is_allowed'] == TRANSITION_ALLOWED)
    $allowed = _("Yes");
  else
    $allowed = _("No");

  print '<td align="center">'
    . $val_label[$transition['to_value_id']] . "</td>\n"
    . "<td align='center'>$allowed</td>\n";
}

function list_transition_registered_fields ($registered)
{
  global $group_id;
  if (!$registered)
    return _("Edit other fields update");
  $fields = '';
  while ($entry = db_fetch_array ($registered))
    {
      # Add one entry per registered other field update.
      $ufn =  $entry['update_field_name'];
      $l = trackers_data_get_label ($ufn);
      $v = trackers_data_get_value (
        $ufn, $group_id, $entry['update_value_id']
      );
      $fields .= "$l:$v, ";
    }
  return trim ($fields, ", ");
}

function print_transition_update ($transition)
{
  global $sys_home, $php_self, $group;
  if ($transition['is_allowed'] != TRANSITION_ALLOWED)
    {
      print "<td align='center'>---------</td>\n"
        . "<td align='center'>--------</td>\n";
      return;
    }
  $registered =
    trackers_transition_get_other_field_update ($transition['transition_id']);
  $fields = list_transition_registered_fields ($registered);

  print '<td align="center">';
  print utils_link (
    $sys_home . ARTIFACT
    . "/admin/field_values_transition-ofields-update.php?"
    . "group=$group&amp;transition_id={$transition['transition_id']}",
    $fields
  );
  print "</td>\n";
  print "<td align='center'>{$transition['notification_list']}</td>\n";
}

function print_delete_transition ($transition, $field)
{
  global $group, $php_self;
  print '<td align="center">';
  print utils_link (
    "$php_self?group=$group&amp;transition_id="
    . $transition['transition_id'] . '&amp;list_value=1&amp;'
    . "func=deltransition&amp;field=$field",
    html_image_trash (['alt' => _("Delete this transition")])
  );
  print "</td>\n";
}

function show_transitions ($field, $field_id, $val_label)
{
  if (($result = fetch_transitions ($field_id, $field)) === null)
    return;
  print "\n\n<p>&nbsp;</p>"
    . html_h (2, html_anchor (_("Registered Transitions"), "registered"));
  print html_build_list_table_top ([
    _("From"), _("To"), _("Is Allowed"),
    _("Other Field Update"), _("Carbon-Copy List"), _("Delete")
  ]);
  $z = 0;
  while ($transition = db_fetch_array ($result))
    {
      print '<tr class="' . utils_altrow ($z++) . '">';
      print_transition_label ($transition, $val_label);
      print_transition_allowed ($transition, $val_label);
      print_transition_update ($transition);
      print_delete_transition ($transition, $field);
      print "</tr>\n";
    }
  print "</table>\n";
}

function get_transition_for_field ($field)
{
  global $group_id;
  $res = db_execute ("
     SELECT transition_default_auth
     FROM " . ARTIFACT . "_field_usage
     WHERE group_id = ? AND bug_field_id = ?",
     [$group_id, trackers_data_get_field_id ($field)]
  );
  if (db_numrows ($res) > 0
      && db_result ($res, 0, 'transition_default_auth') == TRANSITION_FORBIDDEN)
    return _("By default, for this field, the\n"
      . "transitions not registered are forbidden. This setting "
      . "can be changed when\nmanaging this field usage.");
 return _("By default, for this field, the\n"
   . "transitions not registered are allowed. This setting can "
   . "be changed when\nmanaging this field usage.");
}

function print_create_transition_caption ($field)
{
  global $group_id;

  print form_tag ([], "#registered");
  print form_hidden (
    ["list_value" => "y", "field" => $field, "group_id" => $group_id]
  );
  print "\n\n<p>&nbsp;</p>" . html_h (2, _("Create a transition")) . "\n";
}

function print_create_transition_table ($field)
{
  global $group_id;
  $title_arr = [_("From"), _("To"), _("Is Allowed"), _("Carbon-Copy List")];
  $auth_label = ['allowed', 'forbidden'];
  $auth_val = $GLOBALS['TRANSITION_STATUS_LIST'];

  $from = '<td>'
    . trackers_field_box (
        $field, 'from', $group_id, false, false, false, 1, _("* - Any")
      )
    . "</td>\n";
  $to = '<td>'
    . trackers_field_box ($field, 'to', $group_id, false, false) . "</td>\n";
  print html_build_list_table_top ($title_arr) . "<tr>$from$to";
  print '<td>'
    . html_build_select_box_from_arrays (
        $auth_val, $auth_label, 'allowed', 'allowed', false, 'None',
        false, 'Any', false, _("allowed or not")
      )
    . "</td>\n";
  $mlist = form_input ('text', 'mail_list', '',
      "title=\"" . _("Carbon-Copy List") . '" size="30" maxlength="60"'
    );
  print "<td>\n$mlist</td>\n</tr>\n</table>\n";
}

function print_create_transition ($field)
{
  print_create_transition_caption ($field);
  print "<p> " . get_transition_for_field ($field) . "</p>\n";
  print '<p>'
    . _("Once a transition created, it will be possible to set "
    . "&ldquo;Other Field\nUpdate&rdquo; for this transition.")
    . "</p>\n";
  print_create_transition_table ($field);
  print form_footer (_("Update Transition"), 'submit');
}

function exit_unless_admin ($end_str = '')
{
  global $is_admin;
  if ($is_admin)
    return;
  print $end_str;
  trackers_footer ();
  exit (0);
}

# Display the list of values for a given bug field.
function show_values ($field, $title, $group)
{
  global $group_id, $by_field_id;
  td_select_box ($field);
  print_field_header ($field, $title, $group);
  $is_group_scope = trackers_data_field_is_group_scope ($field);
  $have_hidden = show_existing_fields ($field, $is_group_scope);
  exit_unless_admin ();

  if ($is_group_scope)
    show_create_field_value ($field, $have_hidden);

  # If the group use custom values, propose to reset to the default.
  if (trackers_data_use_field_custom_values ($field, $group_id))
    show_reset_field_values ($field);

  list ($field_id, $val_label) = get_val_label ($field, $by_field_id);
  show_transitions ($field, $field_id, $val_label);
  print_create_transition ($field);
  trackers_footer ();
}

function print_value_rank ($row, $title)
{
  print '<span class="preinput">' . html_label ('title', $title) . ' </span>';
  print form_input (
    "text", "title", utils_specialchars_decode ($row['value'], ENT_QUOTES),
    'size="40" maxlength="60"'
  );
  print "\n&nbsp;&nbsp;\n<span class='preinput'>"
    . html_label ('order_id', _("Rank:")) . '</span>&nbsp;';
  print form_input (
     "text", "order_id", $row['order_id'], 'size="6" maxlength="6"'
   );
}

function print_update_value_header ($fv_id, $field, $group_id)
{
  trackers_header_admin (['title' => _("Field values")]);
  print form_tag ()
    . form_hidden (
        [
          "post_changes" => "y", "update_value" => "y", "list_value" => "y",
          "fv_id" => $fv_id, "field" => $field, "group_id" => $group_id,
        ]
      );
}

function field_status_option ($option, $status)
{
  global $FIELD_STATUS_LABELS;
  return form_option ($option, $status, $FIELD_STATUS_LABELS[$option]);
}

function print_update_value_status ($row)
{
  print "\n&nbsp;&nbsp;\n<span class='preinput'>"
    . html_label ('status', _("Status:")) . "</span>\n"
    . "<select name='status' id='status'>\n"
    . field_status_option (FIELD_STATUS_ACTIVE, $row['status'])
    . field_status_option (FIELD_STATUS_HIDDEN, $row['status'])
    . "</select>\n<p>\n<span class='preinput'>"
    . html_label ('description', _("Description (optional):"))
    . "</span><br />\n"
    . form_textarea ('description', $row['description'],
        'rows="4" cols="65" wrap="soft"'
      )
    . "</p>\n";
}

function print_value_usage_counter ($field, $group_id, $row)
{
  $count = trackers_data_count_field_value_usage (
    $group_id, $field, $row['value_id']
  );
  if ($count <= 0)
    return;
  print '<p class="warn">';
  printf (
    ngettext (
      "This field value applies to %s item of your tracker.",
      "This field value applies to %s items of your tracker.", $count
    ),
    $count
  );
  print ' ';
  printf (
    _("If you hide this field value, the related items will have no "
      . "value in the\nfield '%s'."),
    $field
  );
  print "</p>\n";
}

function print_update_value ($fv_id, $field, $group_id)
{
  $row = db_fetch_array (trackers_data_get_field_value ($fv_id));
  print_update_value_header ($fv_id, $field, $group_id);
  print_value_rank ($row, _("Value:"));
  print_update_value_status ($row);
  print_value_usage_counter ($field, $group_id, $row);
  print "\n<div class='center'>\n"
    . form_submit (_("Submit"), 'submit') . "</div>\n";
  trackers_footer ();
}

if ($list_value)
  {
    show_values ($field, $title, $group);
    exit (0);
  }

if ($update_value)
  {
    print_update_value ($fv_id, $field, $group_id);
    exit (0);
  }
function canned_hidden ($create)
{
  global $group_id, $item_canned_id;
  $hidden = ['post_changes' => 'y', 'group_id' => $group_id];
  if ($create)
    {
      $hidden['create_canned'] = 'y';
      return $hidden;
    }
  $hidden['update_canned'] = 'y';
  $hidden['item_canned_id'] = $item_canned_id;
  return $hidden;
}

function print_form_canned ($row = null)
{
  $hidden = canned_hidden ($row === null);
  if ($row === null)
    $row = ['body' => '', 'order_id' => '', 'title' => ''];
  print "<p>" . form_tag () . form_hidden ($hidden);
  $row['value'] = $row['title'];
  print_value_rank ($row, _("Title:"));
  print "<br />\n<span class='preinput'>"
    . html_label ("body", _("Message Body:")) . "</span><br />\n&nbsp;&nbsp;"
    . form_textarea ('body', $row['body'], "rows='20' cols='65' wrap='hard'")
    . form_footer (_("Submit"), 'submit');
}

function print_canned_row ($row, $group_id, $i)
{
  global $php_self;
  $id = $row['bug_canned_id'];
  $s_body = utils_cutstring ($row['body'], 360);
  print '<tr class="' . utils_altrow ($i) . '">'
    . "<td><a href=\"$php_self?update_canned=1&amp;"
    . "item_canned_id=$id&amp;group_id=$group_id\">{$row['title']}</a></td>\n"
    . "<td>$s_body</td>\n<td>{$row['order_id']}</td>\n"
    . "<td class='center'>";
  print form_tag ()
    . form_hidden (['func' => 'delcanned', 'item_canned_id' => $id,
        'group_id' => $group_id]
      )
    . form_image_trash ('submit') . "</form>\n";
  print "</td></tr>\n";
}

if ($create_canned || $delete_canned)
  {
    # Show existing responses and UI form.
    trackers_header_admin (['title' => _("Modify Canned Responses")]);
    $result = db_execute ('
      SELECT * FROM ' . ARTIFACT . '_canned_responses
      WHERE group_id = ? ORDER BY order_id ASC',
      [$group_id]
    );
    $rows = db_numrows ($result);

    if ($result && $rows > 0)
      {
        print html_h (2, _("Existing Responses:")) . "<p>\n";
        $title_arr = [
          _("Title"), _("Body (abstract)"), _("Rank"), _("Delete")
        ];
        print html_build_list_table_top ($title_arr);
        $i = 0;
        while ($row = db_fetch_array ($result))
          print_canned_row ($row, $group_id, $i++);
        print "</table>\n";
      }
    else
      print html_h (2, _("No canned bug responses set up yet"));
    print html_h (2,  _("Create a new response")) . "<p>"
      . _("Creating generic quick responses can save a lot of time when "
          . "giving common\nresponses.")
      . "</p>\n";
    print_form_canned ();
    trackers_footer ();
    exit (0);
  }
if ($update_canned)
  {
    #  Allow change of canned responses.
    trackers_header_admin (['title' => _("Modify Canned Response")]);

    $result = db_execute ('
      SELECT bug_canned_id, title, body, order_id
      FROM ' . ARTIFACT . '_canned_responses
      WHERE group_id = ? AND bug_canned_id = ?',
      [$group_id, $item_canned_id]
    );

    if (db_numrows ($result) < 1)
      fb (_("No such response!"), 1);
    else
      {
        print '<p>'
	  . _("Creating generic messages can save you a lot of time when giving\n"
              . "common responses.");
        print "</p>\n";
        print_form_canned (db_fetch_array ($result));
      }
    trackers_footer ();
    exit (0);
  }

function print_field_scope ($field, $is_admin)
{
  if (!$is_admin)
    return;
  $scope_label  = _("System");
  if (trackers_data_field_is_group_scope ($field))
    $scope_label  = _("Group");
  print "<td>$scope_label</td>\n";
}

trackers_header_admin (['title' => _("Field values")]);
print "<br />\n";

# Loop through the list of all used fields that are group-manageable.
$i = 0;
$title_arr = [_("Field Label"), _("Description")];
if ($is_admin)
  $title_arr[] = _("Scope");
print html_build_list_table_top ($title_arr);
while ($field = trackers_list_all_fields ())
  {
    if (!trackers_data_is_used ($field))
      continue;
    if (in_array ($field, ['bug_id', 'group_id']))
      continue;
    $desc = trackers_data_get_description ($field);
    $link = trackers_data_description_link (
      $field, trackers_data_get_label ($field)
    );
    print '<tr class="' . utils_altrow ($i++) . '">'
      . "<td>$link</td>\n<td>$desc</td>\n";
    print_field_scope ($field, $is_admin);
    print "</tr>\n";
  }

exit_unless_admin ("</table>\n");

print '<tr class="' . utils_altrow ($i) . '"><td>';
print "<a href=\"$php_self?group_id=$group_id&amp;create_canned=1\">"
  . _("Canned Responses") . "</a></td>\n";
print "\n<td>"
  . _("Create or change generic quick response messages for this issue "
      . "tracker.\nThese pre-written messages can then be used to quickly "
      . "reply to item\nsubmissions.")
  . " </td>\n";
print "\n<td>" . _("Group") . "</td></tr>\n";
print "</table>\n";

trackers_footer ();
?>
