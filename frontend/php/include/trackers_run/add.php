<?php
# Add item to trackers.
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
require_once (dirname (__FILE__) . '/../trackers/show.php');

extract (sane_import ('request',
  ['array' => [['prefill', [null, 'specialchars']]]]
));

if (!group_restrictions_check ($group_id, ARTIFACT))
  {
    $help = group_getrestrictions_explained ($group_id, ARTIFACT);
    # TRANSLATORS: the argument is a string that explains why the action is
    # unavailable.
    exit_error (sprintf (_("Action Unavailable: %s"), $help));
  }

trackers_header (['title' => _("Submit Item")]);
$fields_per_line = 2;
$max_size = 40;

function show_preamble ($group_id)
{
  $field = ARTIFACT . "_preamble";
  $res_preamble = db_execute (
     "SELECT `$field` FROM `groups` WHERE `group_id` = ?", [$group_id]
  );

  $preamble = db_result ($res_preamble, 0, $field);
  if ($preamble)
    print html_h (2, _("Preamble")) . markup_rich ($preamble);
}

function show_form_caption ($group_id)
{
  print html_h (2, _("Details"));
  print form_header (
    null, "post", 'enctype="multipart/form-data" name="trackers_form"'
  );
  print form_hidden (["func" => "postadditem", 'group_id' => $group_id]);
  print "\n<table cellpadding='0' width='100%'>";
}

function filter_field ($field_name, $is_admin)
{
  if (!trackers_data_is_used ($field_name))
    return true;
  # If the field is a special field (except summary and original description),
  # skip it.
  if (trackers_data_is_special ($field_name)
    && !in_array ($field_name, ['summary', 'details'])
  )
    return true;
  # Plus only show fields allowed on the bug submit_form.
  if (!user_isloggedin ()
    && trackers_data_is_showed_on_add_nologin ($field_name)
  )
    return false;
  if (!$is_admin && trackers_data_is_showed_on_add ($field_name))
    return false;
  if ($is_admin && trackers_data_is_showed_on_add_members ($field_name))
    return false;
  return true;
}

function get_field_value ($field_name)
{
  global $prefill, $form_check_submit;
  if (!empty ($GLOBALS[$field_name]))
    return utils_specialchars ($GLOBALS[$field_name]);
  # We let people make URLs with predefined values;
  # if the value is in the URL, we override the default one.
  if (isset ($prefill[$field_name]))
    return $prefill[$field_name];
  if ($form_check_submit && array_key_exists ($field_name, $GLOBALS))
    return $GLOBALS[$field_name];
  return trackers_data_get_default_value ($field_name);
}

function get_field_label ($field, $group_id)
{
  $label = trackers_field_label_display ($field, $group_id, false, false);
  if ($field != 'details')
    return $label;
  $GLOBALS['int_trapisset'] = true;
  $label .= ' <span class="preinput">' . markup_info ("full")
    . "</span>&nbsp;\n&nbsp;" . form_submit (_('Preview'), 'preview');
  return $label;
}

function field_star_value ($field, $group_id, $field_value)
{
  $star = '';
  $mandatory_flag = trackers_data_mandatory_flag ($field);
  if ($mandatory_flag == 3 || $mandatory_flag == 0)
    {
      $star = '<span class="warn"> *</span>';
      $mandatory_flag = 0;
    }
  # Field display with special Unknown option, only for fields that
  # are not mandatory.
  $value = trackers_field_display (
    $field, $group_id, $field_value, false, false, false,
    false, false, false, false, false, true, $mandatory_flag
  );
  return [$star, $value];
}

show_preamble ($group_id);
show_form_caption ($group_id);
# Display the variable part of the field list (depending on the group).
$i = 0;
$is_trackeradmin = member_check (0, $group_id, 2);

while ($field_name = trackers_list_all_fields ())
  {
    if (filter_field ($field_name, $is_trackeradmin))
      continue;

    $field_value = get_field_value ($field_name);
    $label = get_field_label ($field_name, $group_id);
    list ($star, $value) =
      field_star_value ($field_name, $group_id, $field_value);
    $field_class = '';

    if ($is_trackeradmin)
      cookbook_print_form ($field_name, $field_class);

    # We highlight fields that were not properly/completely
    # filled.
    if (!empty ($previous_form_bad_fields)
        && array_key_exists ($field_name, $previous_form_bad_fields))
      $field_class = ' class="highlight"';

    # If field size is greatest than max_size chars, then force it to
    # appear alone on a new line or it won't fit in the page.
    list ($sz,) = trackers_data_get_display_size ($field_name);
    if ($sz > $max_size)
      {
        $row_class = trackers_get_row_class ($field_name);
        # Field getting one line for itself.
        # Each time prepare the background color change.
        print "\n<tr$row_class>"
          . "<td valign='middle'$field_class width='15%'>$label</td>\n"
          . "<td valign='middle'$field_class colspan=\""
          . (2 * $fields_per_line - 1) . '" width="75%">'
          . "$value$star</td>\n</tr>\n";
        $i = 0;
      }
    else
      {
       print "\n";
        # Field getting half of a line for itself.
        if (!($i % $fields_per_line))
          {
            # Every one out of two, prepare the background color change.
            # We do that at this moment because we cannot be sure
            # there will be another field on this line.
            $row_class = trackers_get_row_class ($field_name);
            print "<tr$row_class>";
          }
        print "<td valign='middle'$field_class width='15%'>$label</td>\n"
          . "<td valign='middle'$field_class width='35%'>$value$star</td>\n";
        print (++$i % $fields_per_line? "\n": "</tr>\n");
      }
  } # while ($field_name = trackers_list_all_fields ())

print "</table>\n";
print
  '<p><span class="warn smaller">* ' . _("Mandatory Fields") . "</span></p>\n";

if ($preview)
  {
    print html_h (2, _('Preview'));
    if (isset ($details))
      print markup_full (utils_specialchars ($details));
  }


print "<p>&nbsp;</p>\n";
print html_h (2, _("Attached Files"));

show_attach_inputs ();

# Cc addresses.
if ($is_trackeradmin)
  {
    print "<p>&nbsp;</p>\n";
    print html_h (2, _("Mail Notification CC"));

    # TRANSLATORS: the argument is site name (like Savannah).
    print '<p>';
    printf (
      _("(Note: for %s users, you can use their login name\n"
        . "rather than their email addresses.)"),
      $sys_name
    );
    print "</p>\n";

    print '<p><span class="preinput">'
      . _("Add Email Addresses (use comma as separator):")
      . "</span><br />\n&nbsp;&nbsp;&nbsp;"
      . form_input ('text', 'add_cc', $add_cc, 'size="40"')
      . "&nbsp;&nbsp;&nbsp;\n"
      . "<br />\n<span class='preinput'>" . _("Comment:")
      . "</span><br />\n&nbsp;&nbsp;&nbsp;"
      . form_input ('text', "cc_comment", $cc_comment,
          'size="40" maxlength="255"');
    print "</p>\n";
  }

if (empty ($fields['check']))
  $check = '';
else
  $check = $fields['check'];
if (!user_isloggedin ())
  print '<p class="noprint">'
    . _("Please enter the title of <a\n"
        . "href=\"https://en.wikipedia.org/wiki/George_Orwell\">George "
        . "Orwell</a>'s famous\ndystopian book (it's a date):")
    . "\n" . form_input ('text', 'check', $check) . "</p>\n";

print '<div align="center">';
$int_trapisset = true;
print form_submit (_('Preview'), 'preview') . '&nbsp;&nbsp;';
print form_submit (false, "submit", 'class="bold"');
print "</div>\n";
print "</form>\n";

trackers_footer ();
?>
