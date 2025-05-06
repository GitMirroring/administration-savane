<?php
# Cookbook functions
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
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

# This will return an array of possible values, like
#      anonymous, logged-in...
function cookbook_audience_possiblevalues ()
{
  return [
    "anonymous" => _("Anonymous Users"),
    "loggedin" => _("Logged-in Users"),
    "members" => _("All Group Members"),
    "technicians" => _("Group Members who are technicians"),
    "managers" => _("Group Members who are managers")
  ];
}

# Same for context.
# Guess all the possible values in theory on the site for a project.
function cookbook_context_project_possiblevalues ()
{
  return [
    "project" => _("Group Main Pages"), "homepage" => _("Group Homepage"),
    "cookbook" => _("Cookbook"), "download" => _("Download Area"),
    "support" => _("Support Tracker"), "bugs" => _("Bug Tracker"),
    "task" => _("Task Manager"), "patch" => _("Patch Tracker"),
    "news" => _("News Manager"), "mail" => _("Mailing Lists"),
    "cvs" => _("Source Code Manager: CVS Repositories"),
    "arch" => _("Source Code Manager: GNU Arch Repositories"),
    "svn" => _("Source Code Manager: Subversion Repositories")
  ];
}

# Guess all the possible values in theory on the site for the site admin.
function cookbook_context_site_possiblevalues ()
{
  return [
    "my" => _("User Personal Area"), "stats" => _("Site Statistics"),
    "siteadmin" => _("Site Administration")
  ];
}

function cookbook_context_possiblevalue_set ()
{
  $pr = array_keys (cookbook_context_project_possiblevalues ());
  $si = array_keys (cookbook_context_site_possiblevalues ());
  return array_merge ($pr, $si);
}

# Guess all the impossible values in reality for a project.
function cookbook_context_project_impossiblevalues ()
{
  global $group_id;

  # Site values are per definition impossible for a normal project.
  $array_impossible = cookbook_context_site_possiblevalues ();

  # Impossible values are values of unactivated features for the project.
  $array_possible = cookbook_context_project_possiblevalues ();
  $group = project_get_object ($group_id);

  foreach ($array_possible as $feature => $value)
    {
      # Cookbook cannot be deactivated.
      if ($feature == 'cookbook')
        continue;

      # Group main pages cannot be deactivated.
      if ($feature == 'project')
        continue;

      if (!$group->Uses ($feature))
        $array_impossible[$feature] = 1;
    }
  return $array_impossible;
}

# Find out the really possible value in the current context.
function cookbook_context_possiblevalues ()
{
  global $group_id, $sys_group_id;

  # All project-wide possible values, ordered in a clean way.
  $array_possible = cookbook_context_project_possiblevalues ();

  if ($group_id != $sys_group_id)
    {
      # If we are in a normal group, remove impossible values.
      # For instance, remove all unused features.
      $array_impossible = cookbook_context_project_impossiblevalues ();

      foreach ($array_impossible as $key => $value)
        unset ($array_possible[$key]);
    }
  else
    # For the site admin group, present all possible values.
    $array_possible = array_merge (
      cookbook_context_site_possiblevalues (), $array_possible
    );
  reset ($array_possible);
  return $array_possible;
}

# Same for subcontext.
function cookbook_subcontext_possiblevalues ()
{
  return [
    "browsing" => _("Browsing"),
    "postitem" => _("Posting New Items"),
    "edititem" => _("Editing Items, Posting Comments"),
    "search" => _("Doing Searches"),
    "configure" => _("Configuring Features")
  ];
}

function cookbook_get_possiblevalues ($which)
{
  if ($which == "audience")
    return cookbook_audience_possiblevalues ();
  if ($which == "context")
    return cookbook_context_possiblevalues ();
  if ($which == "subcontext")
    return cookbook_subcontext_possiblevalues ();
  return [];
}

# Return the bit of form that should be used inside the item edit forms.
function cookbook_build_form ($which = "audience")
{
  global $item_id, $group_id, $previous_form_bad_fields;

  if (empty ($item_id))
    return '';
  # If there is an item id available, it means we are editing an item
  # and we want to previous results from the database.
  $result = db_execute ("
    SELECT * FROM cookbook_context2recipe
    WHERE recipe_id = ? AND group_id = ? LIMIT 1",
    [$item_id, $group_id]
  );

  $text = [];
  foreach (cookbook_get_possiblevalues ($which) as $field => $label)
    {
      $checked = false;
      $field_name = "{$which}_$field";
      $cb_name = "recipe_$field_name";

      if ($item_id && db_result ($result, 0, $field_name) == 1)
        $checked = true;

      # Ultimately take into account what was posted, in the case the form
      # was reprovided to the user because he forgot mandatory fields.
      if ($previous_form_bad_fields)
        $checked = !empty ($_POST[$cb_name]);
      $text[] = form_checkbox ($cb_name, $checked, ['label' => $label]);
    }
  return join ("<br />\n", $text);
}

# Describe a field type.
function cookbook_describe ($which = "audience")
{
  if ($which == "audience")
    return _("Defines which users will actually get such recipe showing up\n"
      . "as related recipe while browsing the site. It will not prevent "
      . "other users to\nsee the recipe in the big list inside the Cookbook."
    );
  if ($which == "context")
    return _("Defines on which pages such recipe will show up as related\n"
      . "recipe. It will not prevent other users to see the recipe in the big "
      . "list\ninside the Cookbook."
    );
  if ($which == "subcontext")
    return _("Defines while doing which actions such recipe will show up "
      . "as\nrelated recipe. It will not prevent other users to see "
      . "the recipe in the big\nlist inside the Cookbook."
    );
  return null;
}

function cookbook_set_row_class (&$j, &$row_class)
{
  $j++;
  $row_class = '';
  if ($j % 2)
    $row_class = ' class="' . utils_altrow ($j + 1) . '"';
}

# Use cookbook_build_form to return a nice form that can be included in
# mod and post forms.
function cookbook_print_form ()
{
  global $j, $fields_per_line, $i, $row_class, $field_class;

  # Field getting one line for itself
  #  |            Audience                     |

  $td = '<td valign="middle" ' . $field_class;

  print "<tr$row_class>$td"
    . ' width="15%"><span class="preinput"><span class="help" title="'
    . cookbook_describe ("audience") . '">' . _("Audience:")
    . "</span></span></td>\n"
    . $td . ' colspan="' . (2 * $fields_per_line - 1)
    . '" width="75%">' . cookbook_build_form ("audience") . "</td>\n</tr>\n";

  $i = 0;

  # Field getting half of a line for itself
  #  | context, kind of pages | context, kind of action
  #       (CONTEXT)                   (SUBCONTEXT)

  cookbook_set_row_class ($j, $row_class);

  print ($i % $fields_per_line? '': "<tr$row_class>");
  print $td
    . ' width="15%"><span class="preinput"><span class="help" title="'
    . cookbook_describe("context").'">' . _("Feature:")
    . '</span></span></td>' . $td . ' width="35%">'
    . cookbook_build_form ("context") . "</td>\n";
  $i++;
  print ($i % $fields_per_line? '': "\n</tr>\n");
  print ($i % $fields_per_line? '': "<tr$row_class>");
  print $td
    . ' width="15%"><span class="preinput"><span class="help" title="'
    . cookbook_describe("subcontext").'">' . _("Action:")
    . '</span></span></td>' . $td . ' width="35%">'
    . cookbook_build_form ("subcontext") . "</td>\n";
  $i++;
  print ($i % $fields_per_line? '': "</tr>\n");

  $i = 0;
  cookbook_set_row_class ($j, $row_class);
}

function cookbook_sane_import ()
{
  $fields = [];
  foreach (array_keys (cookbook_audience_possiblevalues ()) as $a)
    $fields[] = "recipe_audience_$a";
  foreach (cookbook_context_possiblevalue_set () as $c)
    $fields[] = "recipe_context_$c";
  foreach (array_keys (cookbook_subcontext_possiblevalues ()) as $s)
    $fields[] = "recipe_subcontext_$s";
  return sane_import ('post', ['true' => $fields]);
}

function cookbook_get_update_list ()
{
  $in = cookbook_sane_import ();
  $ret = [];
  foreach (['audience', 'context', 'subcontext'] as $which)
    foreach (array_keys (cookbook_get_possiblevalues ($which)) as $field)
      {
        $value = 0;
        if ($in["recipe_{$which}_$field"])
          $value = 1;
        $ret["{$which}_$field"] = $value;
      }
  return $ret;
}

function cookbook_add_context2recipe ($item_id, $group_id)
{
  $res = db_execute ("
    SELECT context_id FROM cookbook_context2recipe
    WHERE recipe_id = ? AND group_id = ? LIMIT 1", [$item_id, $group_id]
  );
  if (db_numrows ($res))
    return;
  db_autoexecute ('cookbook_context2recipe',
    ['recipe_id' => $item_id, 'group_id' => $group_id], DB_AUTOQUERY_INSERT
  );
}

# Handle update or create of cookbook-specific things in items.
function cookbook_handle_update ($item_id, $group_id)
{
  global $change_exists;
  if (ARTIFACT != 'cookbook')
    return;
  $cookbook_upd_list = cookbook_get_update_list ();
  cookbook_add_context2recipe ($item_id, $group_id);
  $res = db_autoexecute ('cookbook_context2recipe',
    $cookbook_upd_list, DB_AUTOQUERY_UPDATE, "recipe_id = ? AND group_id = ?",
    [$item_id, $group_id]
  );
  $result = db_affected_rows ($res);

  # If there was an affected row, it means we did an update
  # (ignoring the very unusual case where the SQL would fail).
  if ($result)
    {
      $change_exists = 1;
      fb (_("Audience/Feature/Action updated"));
      trackers_data_add_history ("Audience/Feature/Action", '', '', $item_id);
    }
}
?>
