<?php
# Functions for digest mode.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
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

extract (sane_import ('request',
  [
    'funcs' => 'func',
    'digits' =>  ['dependencies_of_item'],
    'artifact' => 'dependencies_of_tracker',
    'array' =>
      [
        ['items_for_digest', ['digits', 'digits']],
        ['field_used', ['name', ['digits', [0, 1]]]]
      ]
  ]
));

require_once ('../include/trackers/view-dependencies.php');

if ($func == "digest")
  {
    $browse_preamble = '<p>'
      . _("Select the items you wish to digest with the checkbox "
          . "shown next to the\n&ldquo;Item Id&rdquo; field, on the table "
          . "below. You will be able to select the\nfields you wish "
          . "to include in your digest at the next step."
        )
      . "</p>\n<p class='warn'>"
      . _("Once your selection is made, push the button "
          . "&ldquo;Proceed to Digest next\nstep&rdquo; at the bottom "
          . "of this page."
        )
      . "</p>\n";
    include '../include/trackers_run/browse.php';
    exit (0);
  }

# Select items to digest, if we are supposed to digest dependencies.
function select_items_by_deps ($deps_of_item, $deps_of_tracker)
{
  global $items_for_digest;
  if (!($deps_of_item && $deps_of_tracker))
    return;
  $res_deps =
    db_execute ("
      SELECT is_dependent_on_item_id FROM {$deps_of_tracker}_dependencies
      WHERE item_id = ? AND is_dependent_on_item_id_artifact = ?
      ORDER by is_dependent_on_item_id", [$deps_of_item, ARTIFACT]
    );
  $items_for_digest = [];
  while ($deps = db_fetch_array ($res_deps))
    $items_for_digest[] = $deps['is_dependent_on_item_id'];
}
function print_field_selection_head ($group)
{
  global $items_for_digest;
  trackers_header (['title' => _("Digest items: field selection")]);
  print form_tag (['method' => 'get'])
    . form_hidden (['group' => $group, 'func' => 'digestget']);

  # Keep track of the selected items.
  $count = 0;
  foreach ($items_for_digest as $item)
    {
      print form_hidden (["items_for_digest[]" => $item]);
      $count++;
    }

  print "\n\n<p>";
  printf (
    ngettext (
      "You selected %s item for this digest.",
      "You selected %s items for this digest.", $count),
    $count
  );
  print ' '
   . _("Now you must unselect fields you do not want to be included "
       . "in the digest.")
   . "</p>\n";
}
function print_altrow ($i)
{
  print '<div class="' . utils_altrow ($i) . '">';
}
function print_field_selection ($field_name, $i)
{
  # Open/Close and group id are meaningless in this context:
  # they'll be on the output page in any cases.
  if (in_array ($field_name, ['group_id', 'status_id']))
    return 0;
  if (!trackers_data_is_used ($field_name))
    return 0;

  # Item ID is mandatory.
  if ($field_name == "bug_id")
      {
        print form_hidden (["field_used[$field_name]" => "1"])
          . "\n";
        return 0;
      }
  print_altrow ($i);
  print form_checkbox ("field_used[$field_name]", 1)
    . '&nbsp;&nbsp;' . trackers_data_get_label ($field_name)
    . ' <span class="smaller"><em>- '
    . trackers_data_get_description ($field_name)
    . "</em></span></div>\n";
  return 1;
}
function print_all_fields_selection ()
{
  $i = 0;
  while ($field_name = trackers_list_all_fields ())
    $i += print_field_selection ($field_name, $i);
  return $i;
}
function print_select_latest_comment ($i)
{
  print_altrow ($i);
  print form_checkbox ("field_used[latestcomment]", 1) . '&nbsp;&nbsp;'
    . _("Latest Comment") . ' <span class="smaller"><em>- '
    . _("Latest comment posted about the item.") . "</em></span></div>\n";
}
function print_select_dependencies ($i)
{
  print_altrow ($i);
  print form_checkbox ("field_used[dependencies]", 1) . '&nbsp;&nbsp;'
    . _("Dependencies") . ' <span class="smaller"><em>- '
    . _("List of dependencies.") . "</em></span></div>\n";
}
if ($func == "digestselectfield")
  {
    select_items_by_deps ($dependencies_of_item, $dependencies_of_tracker);

    if (!is_array ($items_for_digest))
      exit_error (_("No items selected for digest"));

    print_field_selection_head ($group);
    $i = print_all_fields_selection ();
    # The rest are not fields, but could be useful.
    print_select_latest_comment ($i++);
    print_select_dependencies ($i);

    print form_footer (_("Submit"));
    trackers_footer ();
    exit (0);
  } # if ($func == "digestselectfield")

if ($func != "digestget")
  exit (0);

if (!is_array ($items_for_digest))
  exit_error (_("No items selected for digest"));

if (!is_array ($field_used))
  exit_error (_("No fields selected for digest"));

trackers_header (
  ['title' => _("Digest") . ' - ' . utils_format_date (time ())]
);

function show_dependencies ($item)
{
  global $dependencies, $field_used;
  if (!isset ($field_used["dependencies"]))
    return;
  if ($field_used["dependencies"] != 1)
    return;
  print '<p class="clearr"><span class="preinput">'
    . _("Dependencies") . "</span></p>\n";
  if (empty ($dependencies[$item]))
    return;
  print "<ul>\n";
  foreach ($dependencies[$item] as $d)
    trackers_show_dep ($d);
  print "</ul>\n";
}

if (isset ($field_used["dependencies"]) && $field_used["dependencies"] == 1)
  $dependencies = trackers_list_dependencies ($items_for_digest);

# Browse the list of selected item.
$i = 0;
foreach ($items_for_digest as $item)
  {
    $result =
      db_execute ("SELECT * FROM " . ARTIFACT . " WHERE bug_id = ?", [$item]);

    if (db_numrows ($result) < 1)
      continue;
    $res_arr = db_fetch_array ($result);
    # Skip it is it is private but the user got no privilege.
    # Normally, the user should not even been able to select this item.
    # But someone nasty could forge the arguments of the script... So its
    # better to check everytime.
    if (trackers_item_access_denied ($res_arr))
      continue;

    # Show summary if requested.
    if (!(isset ($field_used['summary']) && $field_used['summary'] == 1))
      $res_arr['summary'] = '';
    $res_arr['tracker'] = ARTIFACT;
    print '<div class="' . utils_altrow ($i++) . '"><span class="large">';
    trackers_print_item_link ($res_arr);
    print "</span><br /><br />\n";

    $field_count = 0;
    $halves = ['', ''];
    while ($field_name = trackers_list_all_fields ())
      {
        # Some fields can be ignored in any cases.
        $ignore_fields =
          ["status_id", "summary", "bug_id", "details", "comment_type_id"];
        if (in_array ($field_name, $ignore_fields))
          continue;

        # Check the fields.
        if (!isset ($field_used[$field_name]) || $field_used[$field_name] != 1)
          continue;

        if ($field_name == 'updated')
          {
            $res_arr['updated'] = $res_arr['date'];
            $result = db_execute ("
              SELECT date FROM " . ARTIFACT . "_history WHERE bug_id = ?
              ORDER BY date DESC LIMIT 1", [$item]
            );
            if (db_numrows ($result) >= 1)
              $res_arr['updated'] = db_result ($result, 0, 'date');
          }

        $field_count++;
        if ($field_count == 2)
          $field_count = 0;

        $value =
          trackers_field_display (
            $field_name, $res_arr['group_id'], $res_arr[$field_name],
            false, false, true
          );
        # If it is an user name field, show full user info.
        if ($field_name == "assigned_to" || $field_name == "submitted_by")
          $value = utils_user_link (
            $value, user_getrealname (user_getid ($value))
          );
        $halves[$field_count] .=
          trackers_field_label_display (
            $field_name, $res_arr['group_id'], false, false
          )
         . " $value<br />\n";
      }
    print "<div class='splitright'>{$halves[1]}</div>\n";
    print "<div class='splitleft'>{$halves[0]}</div>\n";

    # Finally include details + last comment, if asked.
    if (isset ($field_used['details']) && $field_used["details"] == 1)
      print '<hr class="clearr" /><div class="smaller">'
        . trackers_field_display (
            "details", $res_arr['group_id'], $res_arr["details"],
            false, true, true
          )
        . "</div>\n";
    if (isset ($field_used["latestcomment"])
        && $field_used["latestcomment"] == 1)
      {
        $result =
          db_execute ("
            SELECT old_value, mod_by, realname, user_name
            FROM " . ARTIFACT . "_history, user
            WHERE
              bug_id = ? AND field_name = 'details' AND user_id = mod_by
            ORDER BY bug_history_id DESC LIMIT 1",
            [$item]
          );
        $last_comment = null;
        if (db_numrows ($result) > 0)
          {
            $res_arr = db_fetch_array ($result);
            $last_comment = $res_arr['old_value'];
            $mod_by = $res_arr['mod_by'];
            if ($mod_by != 100)
              {
                $realname = $res_arr['realname'];
                $user_name = '&lt;' . $res_arr['user_name'] . '&gt;';
              }
            else
              {
                $realname = _("Anonymous");
                $user_name = "";
              }
          }
        if ($last_comment)
          {
            print '<hr class="clearr" /><div class="smaller">'
              . '<span class="preinput">';
            printf (
              _("Latest comment posted (by %s):"), "$realname $user_name"
            );
            print '</span> ' . markup_rich ($last_comment) . "</div>\n";
          }
      }
    show_dependencies ($item);
    print "<p class='clearr'>&nbsp;</p>\n</div>\n\n";
  } # foreach ($items_for_digest as $item)

print trackers_warn_about_hidden ();
trackers_footer ();
?>
