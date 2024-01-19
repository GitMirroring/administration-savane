<?php
# Edit field usage.
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
  ['name' => 'field', 'true' => 'update_field']
));
$submits = ['post_changes', 'submit', 'reset'];
extract (sane_import ('post',
  [
    'true' => $submits,
    'specialchars' => ['label', 'description'],
    'digits' =>
      [
        ['status', 'keep_history', [0, 1]],
        ['mandatory_flag', [0, 3]],
        'place', 'n1', 'n2'
      ],
     'strings' =>
       [
         ['form_transition_default_auth', ['A', 'F']],
         ['show_on_add_logged','show_on_add_mem',  ['1']],
         ['show_on_add_anon', ['2']]
       ]
  ]
));
form_check ($submits);

if (!$group_id)
  exit_no_group ();
if (!user_ismember ($group_id, 'A'))
  exit_permission_denied ();

trackers_init ($group_id);

if ($post_changes)
  {
    # A form was posted to update a field.
    if ($submit)
      {
        $display_size = null;
        if (isset ($n1) && isset ($n2))
          $display_size = "$n1/$n2";

        if (trackers_data_is_required ($field))
          {
            # Do not let the user change these field settings
            # if the field is required.
            $show_on_add_mem =
              trackers_data_is_showed_on_add_members ($field);
            $show_on_add_logged = trackers_data_is_showed_on_add ($field);
            $show_on_add_anon =
              trackers_data_is_showed_on_add_nologin ($field);
          }
        elseif ($field == "vote")
          {
            # Members always may vote.
            $show_on_add_mem = 1;
            # Anonymous visitors can't vote.
            $show_on_add_anon = 0;
          }

        # The additional possibility of differently treating non-group
        # members who have accounts and anonymous visitors demanded
        # a new handling of the values of the show_on_add_logged field:
        # bit (1 << 0) set: show for logged-in non-group members
        # bit (1 << 1) set: show for anonymous users.
        $show_on_add_logged = $show_on_add_logged | $show_on_add_anon;

        trackers_data_update_usage (
          $field, $group_id, $label, $description, $status, $place,
          $display_size, $mandatory_flag, $keep_history, $show_on_add_mem,
          $show_on_add_logged, $form_transition_default_auth
        );
      }
    elseif ($reset)
      trackers_data_reset_usage ($field, $group_id);
    # Force a re-initialization of the global structure after
    # the update and before we redisplay the field list.
    trackers_init ($group_id);
  } # if ($post_changes)

if ($update_field)
  {
    # Show the form to change a field setting.
    # - "required" means the field must be used, no matter what
    # - "special" means the field is not entered by the user but by the system
    trackers_header_admin (['title' => _("Modify Field Usage")]);

    print form_tag ();
    print form_hidden (
      ['post_changes' => 'y', 'field' => $field, 'group_id' => $group_id]
    );
    print "\n<h1>" . _("Field Label:") . ' ';
    $closetag = "</h1>\n";
    if (trackers_data_is_select_box ($field))
      {
        # Only selectboxes can have values configured.
        $closetag .= '<p><span class="smaller">'
          . utils_link (
             $sys_home . ARTIFACT
             . "/admin/field_values.php?group=$group"
             . "&amp;list_value=1&amp;field=$field",
             _("Jump to this field values")
          )
          . "</span></p>\n";
      }
    # If it is a custom field let the user change the label and description.
    if (trackers_data_is_custom ($field))
      {
        print '<input type="text" title="' . _("Field Label")
          . '" name="label" value="'
          . trackers_data_get_label ($field) . '" size="20" maxlength="85">'
          . $closetag;
        print '<span class="preinput">'
          . html_label ("description", _("Description:")) . ' </span>';
        print "<br />\n&nbsp;&nbsp;&nbsp;<input type='text' id='description' "
          . "name='description' value=\""
          . trackers_data_get_description ($field)
          . "\" size='70' maxlength='255' /><br />\n";
      }
    else
      print trackers_data_get_label ($field) . $closetag;

    print "<dl>";
    print '<dt><span class="preinput">' . html_label ('status', _("Status:"))
      . "</span></dt>\n<dd>";

    # Display the Usage box (Used, Unused select box  or hardcoded
    # "required").
    if (trackers_data_is_required ($field))
      print _("Required") . form_hidden (["status" => "1"]);
    else
      print form_checkbox (
        'status', trackers_data_is_used ($field), ['label'=> _("Used")]
      );
    print "</dd>\n";

    # Ask they want to save the history of the item.
    if (!trackers_data_is_special ($field))
      {
        print "<dt>\n<span class='preinput'>" . _("Item History:")
          . "</span>\n<dt>\n<dd>"
          . "<select title=\"" . _("whether to keep in history")
          . "\" name='keep_history'>\n";
        $cur = trackers_data_do_keep_history ($field);
        print form_option ('1', $cur, _("Keep field value changes in history"));
        print form_option (
         '0', $cur, _("Ignore field value changes in history")
        );
        print "</select>\n";
      }
    print "</dd>\n</dl>\n";

    print "\n\n<h2>" . _("Access:") . "</h2>\n";

    print "<dl>\n";
    # Set mandatory bit: if the field is special, meaning it is entered
    # by the system, or if it is "priority", assume the
    # admin is not entitled to modify this behavior.
    if (!trackers_data_is_special ($field))
      {
        # "Mandatory" is not really 100% mandatory, only if it is possible
        # for a user to fill the entry.
        # It is "Mandatory whenever possible".
        $cur = trackers_data_mandatory_flag ($field);
        print '<dt><span class="preinput">' . _("This field is:")
          . "</span></dt>\n<dd>\n"
          . "<select title=\"" . _("whether the field is mandatory")
          . "\" name='mandatory_flag'>\n";
        print form_option ('1', $cur,
          _("Optional (empty values are accepted)")
        );
        print form_option ('3', $cur, _("Mandatory"));
        print form_option ('0', $cur,
          _("Mandatory only if it was presented to the original submitter")
        );
        print "</select>\n";
     }

    print "</dd>\n<dt>";
    print '<span class="preinput">' . _("On new item submission:") . '</span>';
    print "</dt>\n";
    $sh_add_mem = trackers_data_is_showed_on_add_members ($field);
    $sh_add = trackers_data_is_showed_on_add ($field);
    $sh_anon = trackers_data_is_showed_on_add_nologin ($field);
    $anon_label = _("Show field to anonymous users");
    $logged_label = _("Show field to logged-in users");
    $member_label = _("Show field to members");
    $checkboxes = [];
    if (trackers_data_is_required ($field))
      {
        # Do not let the user change field settings.
        $checkboxes[] = form_checkbox ('show_on_add_mem', $sh_add_mem,
          ['label' => $member_label, 'disabled' => 'disabled']
        );
        $checkboxes[] = form_checkbox ('show_on_add_logged', $sh_add,
          ['label' => $logged_label, 'disabled' => 'disabled']
        );
        $checkboxes[] = form_checkbox ('show_on_add_anon', $sh_anon,
          ['value' => 2, 'label' => $anon_label, 'disabled' => 'disabled']
        );
        foreach (array_keys ($checkboxes) as $k)
          $checkboxes[$k] = "<del class='preinput'>{$checkboxes[$k]}</del>";
      }
    else
      {
        # Some fields require specific treatment.
        if ($field == "vote")
          {
            # Vote is always available for members.
            # Vote is impossible unless logged in.
            $checkboxes[] =
              '<del class="preinput">'
              . form_checkbox ('show_on_add_mem',
                  1, ['label' => $member_label, 'disabled' => 'disabled']
                )
              . '</del>';
            $checkboxes[] = form_checkbox ("show_on_add_logged",
              $sh_add, ['label' => $logged_label]
            );
          }
        elseif ($field == "originator_email")
          {
            # Originator email is, by the code, available only to anonymous.
            $checkboxes[] =
              form_checkbox ("show_on_add_anon", $sh_anon,
                ['value' => "2", 'label' => $anon_label]
              );
          }
        else
          {
            $checkboxes[] = form_checkbox (
              "show_on_add_mem", $sh_add_mem, ['label' => $member_label]
            );
            $checkboxes[] = form_checkbox (
              "show_on_add_logged", $sh_add, ['label' => $logged_label]
            );
            $checkboxes[] = form_checkbox ("show_on_add_anon", $sh_anon,
              ['label' => $anon_label, 'value' => "2"]
            );
          }
      } # !trackers_data_is_required ($field)

    print "<dd>" . join ("<br />", $checkboxes) . "</dd>\n</dl>\n";

    if (trackers_data_is_special ($field))
      print form_hidden (['place' => trackers_data_get_place ($field)]);
    else
      {
        print "\n\n<h2>" . _("Display:") . "</h2>\n";

        print "<dl>\n<dt><span class='preinput'>"
          . html_label ("place", _("Rank on page:"))
          . "</span></dt>\n<dd>";
        print '<input type="text" id="place" name="place" value="'
          . trackers_data_get_place ($field)
          . "\" size='6' maxlength='6' /></dd>\n</dl>\n";
      }

    # Customize field size only for text fields and text areas.
    if (trackers_data_is_text_field ($field))
      {
        list ($size, $maxlength) = trackers_data_get_display_size ($field);

        print "<dl>\n<dt><span class='preinput'>"
          . html_label ("n1", _("Visible size of the field:"))
          . "</span></dt>\n<dd>";
        print '<input type="text" id="n1" name="n1" value="' . $size
          . "\" size='3' maxlength='3' /></dd>\n";
        print "<dt><span class='preinput'>"
          . html_label ("n2", _("Maximum size of field text (up to 255):"))
          . "</span></dt>\n<dd>";
        print '<input type="text" id="n2" name="n2" value="' . $maxlength
          . "\" size='3' maxlength='3' /></dd>\n</dl>\n";
      }
    elseif (trackers_data_is_text_area ($field))
      {
        list ($rows, $cols) = trackers_data_get_display_size ($field);

        print "<dl>\n<dt><span class='preinput'>"
          . html_label ("n1", _("Number of columns of the field:"))
          . "</span></dt>\n><dd>";
        print '<input type="text" id="n1" name="n1" value="' . $rows
          . "\" size='3' maxlength='3' /></dd>\n";
        print "<dt><span class='preinput'>"
          . html_label ("n2", _("Number of rows  of the field:"))
          . "</span></dt>\n<dd>";
        print '<input type="text" id="n2" name="n2" value="' . $cols
          . "\" size='3' maxlength='3' /></dd>\n</dl>\n";
      }

    # Transitions.

    # Only select boxes have transition management.
    if (trackers_data_is_select_box ($field))
      {
        $transition_default_auth = '';
        $result = db_execute ("
          SELECT transition_default_auth
          FROM " . ARTIFACT . "_field_usage
          WHERE group_id = ? AND bug_field_id = ?",
          [$group_id, trackers_data_get_field_id ($field)]
        );
        if (db_numrows ($result) > 0)
          $transition_default_auth =
            db_result ($result, 0, 'transition_default_auth');
        $ck = $transition_default_auth !='F';
        print "\n\n<p>&nbsp;</p>\n<h2>"
          . _("By default, transitions (from one value to another) are:")
          . "</h2>\n";
        print
          form_radio ('form_transition_default_auth', 'A',
            [ 'checked' => $ck, 'label' => _("Allowed"),
              'id' => 'form_transition_default_auth_allowed']
          )
          . "<br />\n"
          . form_radio ('form_transition_default_auth', 'F',
              [ 'checked' => !$ck, 'label' => _("Forbidden"),
                'id' => 'form_transition_default_auth_forbidden']
            )
          . "\n";
      }
    print "\n<p align='center'>"
      . form_submit (_("Update"), 'submit') . "\n&nbsp;&nbsp;\n"
      . form_submit (_("Reset to defaults"), 'reset')
      . "</p>\n</form>\n";
    trackers_footer ();
    exit (0);
  } # if ($update_field)

trackers_header_admin (['title' => _("Select Fields")]);

print "<br />\n";

# Show all the fields currently available in the system.
$i = 0;
$title_arr = [
  _("Field Label"), _("Type"), _("Description"), _("Rank on page"),
  _("Scope"), _("Status")
];

$hdr = html_build_list_table_top ($title_arr);

# Build HTML for used fields, then unused fields.
$iu = $in = $inc = 0;
$hu = $hn = $hnc = '';
while ($field_name = trackers_list_all_fields ())
  {
    # Do not show some special fields any way in the list,
    # because there is nothing to customize in them.
    if (
        in_array (
          $field_name,
          [
            'group_id', 'comment_type_id', 'bug_id', 'date', 'close_date',
            'submitted_by', 'updated'
          ]
        )
    )
      continue;

    # Show used, unused and required fields on separate lists.
    # Show unused custom fields in a separate list at the very end.
    $is_required = trackers_data_is_required ($field_name);
    $is_custom = trackers_data_is_custom ($field_name);

    $is_used = trackers_data_is_used ($field_name);
    $status_label =
      $is_required? _("Required"): ($is_used? _("Used"): _("Unused"));

    $scope_label  =
      trackers_data_get_scope ($field_name) == 'S'? _("System"): _("Group");
    $place_label = $is_used? trackers_data_get_place ($field_name): '-';

    $html = "<td><a href=\"$php_self?group_id=$group_id"
      . '&update_field=1&field=' . utils_urlencode ($field_name) . '">'
      . trackers_data_get_label ($field_name) . "</a></td>\n"
      . "\n<td>" . trackers_data_get_display_type_in_clear ($field_name)
      . "</td>\n<td>" . trackers_data_get_description ($field_name)
      . (($is_custom && $is_used)? ' - <b>[' . _("Custom Field") . ']</b>': '')
      . "</td>\n"
      . "\n<td align =\"center\">$place_label</td>\n"
      . "\n<td align =\"center\">$scope_label</td>\n"
      . "\n<td align =\"center\">$status_label</td>\n";

    if ($is_used)
      $hu .= '<tr class="' . utils_altrow ($iu++) . "\">$html</tr>\n";
    elseif ($is_custom)
      $hnc .= '<tr class="' . utils_altrow ($inc++) . "\">$html</tr>\n";
    else
      $hn .= '<tr class="' . utils_altrow ($in++) . "\">$html</tr>\n";
  } #  while ($field_name = trackers_list_all_fields())

$rule0 = '<tr><td colspan="5"><center><b>---- ';
$rule1 = " ----</b></center></tr>\n";
$tr = "<tr><td colspan='5'> &nbsp;</td></tr>\n$rule0";
$hu =  $rule0 . _("USED FIELDS") . "$rule1$hu";
if ($in)
  $hn = "$tr" . _("UNUSED STANDARD FIELDS") . "$rule1$hn";

if ($inc)
  $hnc = "$tr" . _("UNUSED CUSTOM FIELDS") . "$rule1$hnc";
print "$hdr$hu$hn$hnc</table>\n";

trackers_footer ();
?>
