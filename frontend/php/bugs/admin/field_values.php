<?php
# Edit field values.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2001, 2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
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

require_once ('../../include/init.php');
require_once ('../../include/trackers/general.php');

$is_admin_page = 'y';

extract (sane_import ('request',
  [
    'strings' => [['func', ['deltransition', 'delcanned']]],
    'true' => ['update_value', 'create_canned', 'update_canned'],
    'digits' => ['fv_id', 'item_canned_id'],
    'name' => 'field'
  ]
));
extract (sane_import ('get',
  [
    'true' => ['list_value'],
    'digits' => 'transition_id'
  ]
));
extract (sane_import ('post',
  [
    'true' => ['post_changes', 'create_value', 'by_field_id'],
    'specialchars' => ['title', 'description', 'body'],
    'digits' => ['order_id', 'from', 'to'],
    'strings' =>
      [
        ['allowed', ['A', 'F']],
        ['status', ['A', 'P', 'H']]
      ],
    'preg' => [['mail_list', '/^[-+_@.,\s\da-zA-Z]*$/']]
  ]
));

if (!$group_id)
  exit_no_group ();
if (!user_ismember ($group_id, 'A'))
  exit_permission_denied ();
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
    # A form of some sort was posted to update or create
    # an existing value.
    # Deleted Canned doesn't need a form, so let switch
    # into this code.

    if ($create_value)
      {
        # A form was posted to update a field value.
        if ($title)
          trackers_data_create_value (
            $field, $group_id, $title, $description, $order_id, 'A'
          );
        else
          fb (_("Empty field value is not allowed"), 1);
      }
    elseif ($update_value)
      {
        # A form was posted to update a field value.
        if ($title)
          trackers_data_update_value (
            $fv_id, $field, $group_id, $title, $description, $order_id, $status
          );
        else
          fb (_("Empty field value is not allowed"), 1);
      }
    elseif ($create_canned)
      {
        # A form was posted to create a canned response.
        $result = db_autoexecute (
          ARTIFACT . '_canned_responses',
          [
            'group_id' => $group_id, 'title' => $title, 'body' => $body,
            'order_id' => $order_id,
          ],
          DB_AUTOQUERY_INSERT
        );
        if ($result)
          fb (_("Canned bug response inserted"));
        else
          fb (_("Error inserting canned bug response"), 1);
      }
    elseif ($update_canned)
      {
        # A form was posted to update a canned response.
        $result = db_autoexecute (
          ARTIFACT . '_canned_responses',
          ['title' => $title, 'body' => $body, 'order_id' => $order_id],
          DB_AUTOQUERY_UPDATE, 'group_id = ? AND bug_canned_id = ?',
          [$group_id,  $item_canned_id]
        );
        if (!$result)
          fb (_("Error updating canned bug response"), 1);
        else
          fb (_("Canned bug response updated"));
      }
  }

$field_id = $by_field_id? $field: trackers_data_get_field_id ($field);

if ($to != $from)
  {
    # A form was posted to update or create a transition.
    $res_value = db_execute (
     "SELECT from_value_id, to_value_id, is_allowed, notification_list
      FROM trackers_field_transition
      WHERE
        group_id = ? AND artifact = ? AND field_id = ?
        AND from_value_id = ? AND to_value_id = ?",
     [$group_id, ARTIFACT, $field_id, $from, $to]
    );
    $rows = db_numrows ($res_value);

    # If no entry for this transition, create one.
    if ($rows == 0)
      {
        $result = db_autoexecute (
          'trackers_field_transition',
          [
            'group_id' => $group_id, 'artifact' => ARTIFACT,
            'field_id' => $field_id, 'from_value_id' => $from,
            'to_value_id' => $to, 'is_allowed' => $allowed,
            'notification_list' => $mail_list,
          ],
          DB_AUTOQUERY_INSERT
        );

        if (db_affected_rows ($result) < 1)
          fb (_("Insert failed"), 1);
        else
          fb (_("New transition inserted"));
      }
    else
      {
        # Update the existing entry for this transition.
        $result = db_autoexecute (
          'trackers_field_transition',
           ['is_allowed' => $allowed, 'notification_list' => $mail_list],
           DB_AUTOQUERY_UPDATE,
           'group_id = ? AND artifact = ? AND field_id = ?
            AND from_value_id = ? AND to_value_id = ?',
           [$group_id, ARTIFACT, $field_id, $from, $to]
        );

        if (db_affected_rows ($result) < 1)
          fb (_("Update of transition failed"), 1);
        else
          fb (_("Transition updated"));
      }
  } # ($to != $from)

$td_select_box = function ($field)
{
  return trackers_data_get_field_id ($field)
    && trackers_data_is_select_box ($field);
};

if ($list_value)
  {
    # Display the list of values for a given bug field.
    # TRANSLATORS: the argument is field label.
    $hdr = sprintf (
      _("Edit Field Values for '%s'"), trackers_data_get_label ($field)
    );

    if ($td_select_box ($field))
      {
        # First check that this field is used by the group and
        # it is in the group scope.
        $is_group_scope = trackers_data_is_project_scope ($field);
        trackers_header_admin (['title' => $hdr]);
        print
          html_h (1,
            _("Field Label:") . ' ' . trackers_data_get_label ($field)
          )
          . '<p><span class="smaller">('
          . utils_link (
              $sys_home . ARTIFACT . "/admin/field_usage.php?group=$group"
              . "&amp;update_field=1&amp;field=$field",
              _("Jump to this field usage")
            )
          . ")</span></p>\n";

        $result = trackers_data_get_field_predefined_values (
          $field, $group_id, false, false, false
        );
        $rows = db_numrows ($result);

        if (!$result || $rows <= 0)
          # TRANSLATORS: the  argument is field label.
          printf (html_h (1, _("No values defined yet for %s")),
            trackers_data_get_label ($field)
          );
        else
          {
            print html_h (2, _("Existing Values"));
            $title_arr =  [_("Value label"), _("Description"), _("Rank"),
              _("Status"), _("Occurrences")];
            if (!$is_group_scope)
              $title_arr = array_merge ([_('ID')], $title_arr);

            $hdr = html_build_list_table_top ($title_arr);

            # TRANSLATORS: this is field status.
            $status_stg = [
             'A' => _("Active"), 'P' => _("Permanent"), 'H' => _("Hidden")
            ];

            # Display the list of values in 2 blocks: active first,
            # hidden second.
            $ia = $ih = 0;
            $ha = $hh = '';
            while ( $fld_val = db_fetch_array ($result) )
              {
                $item_fv_id = $fld_val['bug_fv_id'];
                $status = $fld_val['status'];
                $value_id = $fld_val['value_id'];
                $value = $fld_val['value'];
                $description = $fld_val['description'];
                $order_id = $fld_val['order_id'];
                $usage = trackers_data_count_field_value_usage (
                  $group_id, $field, $value_id
                );
                $html = '';
                # Keep the rank of the 'None' value in mind if any.
                if ($value == 100)
                  $none_rk = $order_id;

                # Show the value ID only for system wide fields which
                # value id are fixed and serve as a guide.
                if (!$is_group_scope)
                  $html .= "<td>$value_id</td>\n";

                # The permanent values cant be modified (No link).
                $txt_val = $value;
                if ($status != 'P')
                  $txt_val = "<a href=\"$php_self?update_value=1"
                    . "&fv_id=$item_fv_id&field=$field&group_id=$group_id"
                    . "\">$value</a>";
                $html .= "<td>$txt_val</td>\n";

                $html .= "<td>$description&nbsp;</td>\n"
                  . "<td align='center'>$order_id</td>\n"
                  . "<td align='center'>{$status_stg[$status]}</td>\n";

                $us_str = $usage;
                if ($status == 'H' && $usage > 0)
                  $us_str = "<strong class='warn'>$usage</strong>";
                $html .= "<td align='center'>$us_str</td>\n";

                $suff = 'h';
                if ($status == 'A' || $status == 'P')
                  $suff = 'a';
                $class = utils_altrow (${"i$suff"});
                $html = "<tr class=\"$class\">$html</tr>\n";
                ${"i$suff"}++;
                ${"h$suff"} .= $html;
              }

            # Display the list of values now.
            if ($ia)
              $ha = '<tr><td colspan="4" class="center"><strong>'
                . _("---- ACTIVE VALUES ----") . "</strong></tr>\n$ha";
            else
              $hdr = '<p>'
                . _("No active value for this field. Create one or "
                    . "reactivate a hidden value (if\nany)")
                . "</p>\n$hdr";
            if ($ih)
              $hh = "<tr><td colspan=\"4\"> &nbsp;</td></tr>\n"
                .' <tr><td colspan="4"><center><strong>'
                . _("---- HIDDEN VALUES ----")
                . "</strong></center></tr>\n$hh";
            print "$hdr$ha$hh</table>\n";
          } # !(!$result || $rows <= 0)

        # Only show the add value form if this is a group scope field.
        if ($is_group_scope)
          {
            print html_h (2, _("Create a new field value"));
            if ($ih)
              print '<p>'
                . _("Before you create a new value make sure there isn't one "
                . "in the hidden list\nthat suits your needs.")
                . "</p>\n";

            print form_tag ();
            print form_hidden (
                [
                  'post_changes' => 'y', 'create_value' => 'y',
                  'list_value' => 'y', 'field' => $field,
                  'group_id' => $group_id
                ]
              );
            print '<span class="preinput">'
              . html_label ('title', _("Value:")) . '</span>&nbsp;'
              . form_input ("text", "title", "", 'size="30" maxlength="60"')
              . "\n&nbsp;&nbsp;<span class='preinput'>"
              . html_label ('order_id', _("Rank:")) . '</span>&nbsp;'
              . form_input ("text", "order_id", "", 'size="6" maxlength="6"');

            if (isset ($none_rk))
              {
                print "&nbsp;&nbsp;<strong> ";
                # TRANSLATORS: the argument is minimum rank value;
                # the string is used like "Rank: (must be > %s)".
                printf (_("(must be &gt; %s)"), $none_rk);
                print "</strong></p>\n";
              }

            print "<p><span class='preinput'>"
              . html_label ('description', _("Description (optional):"))
              . "</span><br />\n"
              . form_textarea ('description', '',
                 "rows='4' cols='65' wrap='hard'")
              . "</p>\n" . form_footer (_("Update"), 'submit');
          } # $is_group_scope

        # If the group use custom values, propose to reset to the default.
        if (trackers_data_use_field_predefined_values ($field, $group_id))
          {
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

            $default_result = trackers_data_get_field_predefined_values (
              $field, '100', false, false, false
            );
            $default_rows = db_numrows ($default_result);
            $previous = false;
            if ($default_result && $default_rows > 0)
              {
                while ($fld_val = db_fetch_array ($default_result))
                  {
                    $status = $fld_val['status'];
                    $value = $fld_val['value'];
                    $description = $fld_val['description'];
                    $order = $fld_val['order_id'];

                    # Non-active value are not important here.
                    if ($status != "A")
                      continue;

                    if ($previous)
                      print ", ";

                    print "<b>$value</b> <span class='smaller'>"
                      . "($order, \"$description\")</span>";
                    $previous = true;
                  }
              }
            else
              fb (
                _("No default values found. You should report this problem "
                  . "to\nadministrators."),
                1
              );
          }
      }
    else # ! $td_select_box ($field)
      {
        # TRANSLATORS: the argument is field.
        $msg = sprintf (
          _("The field you requested '%s' is not used by your group "
            . "or you are not\nallowed to customize it"),
          $field
        );
        exit_error ($msg);
      }

    $field_id = $by_field_id ? $field: trackers_data_get_field_id ($field);
    if ($td_select_box ($field))
      {
        $sql = '
          SELECT value_id, value FROM ' . ARTIFACT . '_field_value
          WHERE group_id = ? AND bug_field_id = ?';
        # Get all the value_id - value pairs.
        $res_value = db_execute ($sql, [$group_id, $field_id]);

        $rows = db_numrows ($res_value);
        if ($rows <= 0)
          $res_value = db_execute ($sql, [100, $field_id]);

        if ($rows > 0)
          {
            $val_label = [];
            while ($val_row = db_fetch_array ($res_value))
              {
                $value_id = $val_row['value_id'];
                $value = $val_row['value'];
                $val_label[$value_id] = $value;
              }
          }
        $result = db_execute ('
          SELECT
            transition_id, from_value_id, to_value_id, is_allowed,
            notification_list
          FROM trackers_field_transition
          WHERE group_id = ? AND artifact = ?  AND field_id = ?',
          [$group_id, ARTIFACT, $field_id]
        );
        $rows = db_numrows ($result);

        if ($result && $rows > 0)
          {
            print "\n\n<p>&nbsp;</p><h2>"
              . html_anchor (_("Registered Transitions"), "registered")
              . "</h2>\n";

            $title_arr = [
              _("From"), _("To"), _("Is Allowed"),
              _("Other Field Update"), _("Carbon-Copy List"), _("Delete")
            ];

            print html_build_list_table_top ($title_arr);

            $reg_default_auth = '';
            $z = 1;
            while ($transition = db_fetch_array ($result))
              {
                $z++;
                if ($transition['is_allowed'] == 'A')
                  $allowed = _("Yes");
                else
                  $allowed = _("No");

                print '<tr class="' . utils_altrow ($z) . '">';
                if (empty ($val_label[$transition['from_value_id']]))
                  # TRANSLATORS: this refers to transitions.
                  $txt = _("* - Any");
                else
                  $txt = $val_label[$transition['from_value_id']];
                print "<td align='center'>$txt</td>\n";

                print '<td align="center">'
                  . $val_label[$transition['to_value_id']] . "</td>\n"
                  . "<td align='center'>$allowed</td>\n";

                if ($transition['is_allowed'] == 'A')
                  {
                    print '<td align="center">';
                    $registered =
                      trackers_transition_get_other_field_update (
                        $transition['transition_id']
                      );
                    $fields = '';
                    if ($registered)
                      {
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
                        $fields = trim ($fields, ", ");
                      }
                    else
                      $fields = _("Edit other fields update");

                    print utils_link (
                      $sys_home . ARTIFACT
                      . "/admin/field_values_transition-ofields-update.php?"
                      . "group=$group&amp;transition_id="
                      . $transition['transition_id'],
                      $fields
                    );
                    print "</td>\n<td align='center'>"
                      . $transition['notification_list'] . "</td>\n";
                  }
                else
                  print "<td align='center'>---------</td>\n"
                    .  "<td align='center'>--------</td>\n";
                print '<td align="center">';
                print utils_link (
                  "$php_self?group=$group&amp;transition_id="
                  . $transition['transition_id'] . '&amp;list_value=1&amp;'
                  . "func=deltransition&amp;field=$field",
                  html_image_trash (['alt' => _("Delete this transition")])
                );
                print "</td>\n</tr>\n";
              } # while ($transition = db_fetch_array ($result))
            print "</table>\n";
          } # $result && $rows > 0
        else
          {
            $reg_default_auth = '';
            printf (
              "\n\n<p>&nbsp;</p><h2>"
              # TRANSLATORS: the argument is field.
              . _("No transition defined yet for %s") . "</h2>\n",
              trackers_data_get_label ($field)
            );
          }

        print form_tag ([], "#registered");
        print form_hidden (
          [
            "post_transition_changes" => "y", "list_value" => "y",
            "field" => $field, "group_id" => $group_id
          ]
        );

        $result = db_execute ("
           SELECT transition_default_auth
           FROM " . ARTIFACT . "_field_usage
           WHERE group_id = ? AND bug_field_id = ?",
           [$group_id, trackers_data_get_field_id ($field)]
        );
        if (db_numrows ($result) > 0
            && db_result ($result, 0, 'transition_default_auth') == "F")
	  $transition_for_field = _("By default, for this field, the\n"
           . "transitions not registered are forbidden. This setting "
           . "can be changed when\nmanaging this field usage.");
        else
	  $transition_for_field = _("By default, for this field, the\n"
           . "transitions not registered are allowed. This setting can "
           . "be changed when\nmanaging this field usage.");
        print "\n\n<p>&nbsp;</p><h2>" . _("Create a transition") . "</h2>\n";
        print "<p>$transition_for_field</p>\n";
        print '<p>'
          . _("Once a transition created, it will be possible to set "
          . "&ldquo;Other Field\nUpdate&rdquo; for this transition.")
          . "</p>\n";

        $title_arr = [
          _("From"), _("To"), _("Is Allowed"), _("Carbon-Copy List")
        ];

        $auth_label = ['allowed', 'forbidden']; $auth_val = ['A', 'F'];

        $hdr = html_build_list_table_top ($title_arr);
        $from = '<td>'
          . trackers_field_box (
              $field, 'from', $group_id, false, false, false, 1, _("* - Any")
            )
          . "</td>\n";
        $to = '<td>'
          . trackers_field_box ($field, 'to', $group_id, false, false)
          . "</td>\n";
        print "$hdr<tr>$from$to";
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
        print form_footer (_("Update Transition"), 'submit');
      }
    else # !$td_select_box ($field)
      {
        print "\n\n<p><b>";
        # TRANSLATORS: the argument is field.
        printf (
          _("The field you requested '%s' is not used by your group "
            . "or you are not\nallowed to customize it"),
          $field
        );
        print "</b></p>\n";
      }
    trackers_footer ();
    exit (0);
  } # if ($list_value)

function print_value_rank ($row, $title)
{
  print '<span class="preinput">' . html_label ('title', $title);
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

if ($update_value)
  {
    # Show the form to update an existing field_value.
    # Display the List of values for a given bug field.
    trackers_header_admin (['title' => _("Edit Field Values")]);

    # Get all attributes of this value.
    $res = trackers_data_get_field_value ($fv_id);
    $row = db_fetch_array ($res);

    print form_tag ()
      . form_hidden (
          [
            "post_changes" => "y", "update_value" => "y", "list_value" => "y",
            "fv_id" => $fv_id, "field" => $field, "group_id" => $group_id,
          ]
        );
    print_value_rank ($row, _("Value:"));
    print "\n&nbsp;&nbsp;\n<span class='preinput'>"
      . html_label ('status', _("Status:")) . "</span>\n"
      . "<select name='status' id='status'>\n"
      # TRANSLATORS: this is field status.
      . form_option ('A', null, _("Active"))
      . form_option ('H', $row['status'], _("Hidden"))
      . "</select>\n<p>\n<span class='preinput'>"
      . html_label ('description', _("Description (optional):"))
      . "</span><br />\n"
      . form_textarea ('description', $row['description'],
          'rows="4" cols="65" wrap="soft"')
      . "</p>\n";
    $count = trackers_data_count_field_value_usage (
      $group_id, $field, $row['value_id']
    );
    if ($count > 0)
      {
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
    print "\n<div class='center'>\n"
      . form_submit (_("Submit"), 'submit') . "</div>\n";

    trackers_footer ();
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
          {
            $s_body = substr ($row['body'], 0, 360);
            print '<tr class="' . utils_altrow ($i++) . '">'
              . "<td><a href=\"$php_self"
              . "?update_canned=1&amp;item_canned_id={$row['bug_canned_id']}"
              . "&amp;group_id=$group_id\">{$row['title']}</a></td>\n"
              . "<td>$s_body...</td>\n<td>{$row['order_id']}</td>\n"
              . "<td class='center'><a href=\"$php_self"
              . "?func=delcanned&amp;item_canned_id={$row['bug_canned_id']}"
              . "&amp;group_id=$group_id\">"
              . html_image_trash (['alt' => _("Delete this canned response")])
              . "</a></td></tr>\n";
          }
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

trackers_header_admin (['title' => _("Edit Field Values")]);
print "<br />\n";

# Loop through the list of all used fields that are group manageable.
$i = 0;
$title_arr = [_("Field Label"), _("Description"), _("Scope")];
print html_build_list_table_top ($title_arr);
while ($field_name = trackers_list_all_fields ())
  {
    if (
      !(trackers_data_is_select_box ($field_name)
        && ($field_name != 'submitted_by')
        && ($field_name != 'assigned_to')
        && trackers_data_is_used ($field_name))
    )
      continue;
    $scope_label  = _("System");
    if (trackers_data_is_project_scope ($field_name))
      $scope_label  = _("Group");
    $desc = trackers_data_get_description ($field_name);
    print '<tr class="' . utils_altrow ($i) . '">'
      . "<td><a href=\"$php_self?group_id=$group_id"
      . "&list_value=1&field=$field_name\">"
      . trackers_data_get_label ($field_name) . "</a></td>\n"
      . "<td>$desc</td>\n<td>$scope_label</td>\n</tr>\n";
    $i++;
  }

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
