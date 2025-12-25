<?php
# Functions used by /search/index.php
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2000-2006 Stéphane Urbanovski <s.urbanovski--ac-nancy-metz.fr>
# Copyright (C) 2008 Nicodemo Alvaro
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

require_once (dirname (__FILE__) . '/../utils.php');
# Check if the group uses any trackers to search in.
function search_has_group_anything_to_search ($gid)
{
  $cases = utils_get_tracker_list ();
  $group = project_get_object ($gid);
  foreach ($cases as $tracker)
    if ($group->Uses ($tracker))
      return true;
  return false;
}

# List tracker search options for given group and kind of search;
# used in search_box ().
function search_list_tracker_options ($gid, $type_of_search, $is_small)
{
   $sel = '';
   if (!empty ($gid))
     {
       $group_realname = substr (group_getname ($gid), 0, 10) . "...";
       $group = project_get_object ($gid);
     }
   $cases = [
     'support' => [
       # TRANSLATORS: this string is used in the context
       # of "Search [...] in Support"; the HTML comment is used
       # to differentiate the usages of the same English string.
       _("<!-- Search... in -->Support"),
       # TRANSLATORS: this string is used in the context
       # of "Search [...] in %s Support"; the argument is group name
       # (like GNU Coreutils).
       _("%s Support"),
     ],
     'bugs'  => [
       # TRANSLATORS: this string is used in the context
       # of "Search [...] in Bugs"; the HTML comment is used
       # to differentiate the usages of the same English string.
       _("<!-- Search... in -->Bugs"),
       # TRANSLATORS: this string is used in the context of
       # "Search [...] in %s Bugs"; the argument is group name
       # (like GNU Coreutils).
       _("%s Bugs"),
     ],
     'task' => [
       # TRANSLATORS: this string is used in the context of
       # "Search [...] in Tasks"; the HTML comment is used to differentiate
       # the usages of the same English string.
       _("<!-- Search... in -->Tasks"),
       # TRANSLATORS: this string is used in the context
       # of "Search [...] in %s Tasks"; the argument is group name
       # (like GNU Coreutils).
       _("%s Tasks"),
     ],
     'patch' => [
       # TRANSLATORS: this string is used in the context
       # of "Search [...] in Patches"; the HTML comment is used
       # to differentiate the usages of the same English string.
       _("<!-- Search... in -->Patches"),
       # TRANSLATORS: this string is used in the context
       # of "Search [...] in %s Patches"; the argument is group name
       # (like GNU Coreutils).
       _("%s Patches"),
     ],
   ];

   foreach ($cases as $key => $msg)
     {
       $text = '';
       if (empty ($gid) || ($is_small && $group->Uses ($key)))
         $text = $msg[0];
       elseif ($group->Uses ($key))
         $text = sprintf ($msg[1], $group_realname);
       if (!$text)
         continue;
       $sel .= form_option ($key, $type_of_search, $text);
     }
  return $sel;
}

# Build a search box.
# $search_words: terms to look for.
# $scope defines the search area:
#   empty      - all trackers
#   'sitewide' - trackers of all groups (used in the left menu)
#   artifact   - given tracker
# $size: width of the text field used for $search_words.
function search_box ($searched_words = '', $scope = null, $size = 15)
{
  global $words, $group_id, $exact, $type_of_search, $type, $max_rows;
  global $only_group_id;
  $submit = form_input ("submit", 'search', _("Search"));

  if (!is_scalar ($searched_words))
    $searched_words = '';

  $gid = null;
  if (!empty ($group_id))
    $gid = $group_id;

  if ($only_group_id)
    $gid = $only_group_id;

  if ($size > 15)
    $is_small = 0;
  else
    $is_small = 1;

  # If it is the left menu, small box, then make sure any group_id info
  # is ignored, because we want to keep the left menu site-wide.
  if ($scope == "sitewide")
    $gid = $scope = null;

  # If there is no search currently, set the default.
  if (!isset ($type_of_search))
    $exact = 1;

  # If the wildcard '%%%' is searched, replace it with the more usual '*'.
  if ($words == "%%%")
    $words = "*";

  $ret = form_header ("/search/#options", 'get');
  if (!$is_small)
    # If it's a big form, we want the submit button on the right.
    $ret .= "<span class='boxoptionssubmit'>$submit&nbsp;</span>\n";

  $title = false;
  if ($is_small)
    $title = "title=\"" . utils_specialchars (_("Terms to look for")) . '"';
  else
    $ret .= "<br />\n"
      . html_label ("words$is_small", _("Terms to look for")) . " ";
  $ret .= form_input ('text', "words$is_small", $searched_words, $title);

  if ($is_small)
    $ret .= "<br />\n";
  else
    $ret .= "&nbsp;&nbsp;\n";

  if (empty ($scope))
    {
      $sel = html_label ("type_of_search$is_small", _("Area to search in"));
      $sel .= "\n<select name='type_of_search' id='type_of_search$is_small'>\n";

      # If the search is restricted to a given group, remove the possibility
      # to search another group, unless we're showing the left box.
      if (empty ($gid))
        {
          $ck = in_array ($type_of_search, ["soft", ""])? 'soft': 'hard';
          # TRANSLATORS: this is search area.
          $sel .= form_option ('soft', $ck, _("Groups"));
          # TRANSLATORS: this is search area.
          $sel .= form_option ("people", $type_of_search, _("People"));
        }
      $sel .=
        search_list_tracker_options ($gid, $type_of_search, $is_small)
        . "</select>\n";

      $ret .= " $sel";
    }
  else # !empty ($scope)
    $ret .= form_hidden (['type_of_search' => $scope]);

  if (isset ($gid))
    $ret .= form_hidden (['only_group_id' => $gid]);

  if ($is_small)
    # If it's a small form, the submit button has not already been inserted.
    return $ret . form_hidden (['exact' => 1]) . "<br />\n"
      . "$submit&nbsp;\n</form>\n";

  $ret .= "<br />\n&nbsp;";
  $ret .= form_radio ('exact', 0,
    ['checked' => !$exact, 'label' => _("with at least one of the words")]
  );
  $ret .= "<br />\n&nbsp;";
  $ret .= form_radio ('exact', 1,
    ['checked' => $exact, 'label' => _("with all of the words")]
  );
  $ret .= "<br />\n&nbsp;";
  $ret .= html_label ('max_rows', _("Number of items to show per page"))
    . "\n";
  $ret .= form_input ('text', 'max_rows', $max_rows, 'size="4"') . "\n";
  if (!isset ($gid))
    {
      # Add the functionality to restrict the search to a group type.
      $ret .= "<br />\n&nbsp;";

      $ret .= html_label ('type',
        _("Group type to search in, when searching for a group")
      );
      $ret .= "\n<select name='type'>" . form_option ('', NULL, _("any"));
      $result =
        db_execute ("SELECT type_id, name FROM group_type ORDER BY type_id");
      while ($eachtype = db_fetch_array ($result))
        $ret .= form_option ($eachtype['type_id'], $type,
          gettext ($eachtype['name'])
        );
      $ret .= "</select>\n";
    } # !isset ($gid)
  $ret .= '<p>'
    . _("Notes: You can use the wildcard *, standing for everything. "
        . "You can also\nsearch items by number.")
    . "</p>\n";
  return "$ret</form>\n";
}

function search_send_header ()
{
  global $words, $type_of_search, $only_group_id;

  if ($type_of_search == "soft" || $type_of_search == "people")
    {
      # There cannot be a group id specific if we are looking for a group
      # group id is meaningless when looking for someone.
      $group_id = 0;
    }
  site_header (['title' => _("Search"), 'context' => 'search']);
  # Print the form.
  if (!$only_group_id)
    $title = _("Search Criteria:");
  else
    # TRANSLATORS: the argument is group name (like GNU Coreutils).
    $title = sprintf (_("New search criteria for the Group %s:"),
      group_getname ($only_group_id));

  print html_show_boxoptions ($title, search_box ($words, '', 45));
}

# Search results for XXX (in YYY):
# e.g.: Search results for emacs (in groups):
function print_search_heading ()
{
  global $words, $type_of_search, $only_group_id;
  print html_h (2, _('Search results'), 'results');
  if (!($words && $type_of_search))
    return;
  print "<p>";
  # Print real words describing the type of search.
  if ($type_of_search == "soft")
    # TRANSLATORS: this string is the section to look in; it is used as
    # the second argument in 'Search results for %1$s (in %2$s)'.
    $type_of_search_real = _("Groups");
  elseif ($type_of_search == "support")
    # TRANSLATORS: this string is the section to look in; it is used as
    # the second argument in 'Search results for %1$s (in %2$s)'. The HTML
    # comment is used to differentiate the usages of the same English string.
    $type_of_search_real = _("<!-- Search... in -->Support");
  elseif ($type_of_search == "bugs")
    $type_of_search_real = _("<!-- Search... in -->Bugs");
  elseif ($type_of_search == "task")
    $type_of_search_real = _("<!-- Search... in -->Tasks");
  elseif ($type_of_search == "patch")
    $type_of_search_real = _("<!-- Search... in -->Patches");
  elseif ($type_of_search == "people")
    $type_of_search_real = _("<!-- Search... in -->People");

  if (!$only_group_id)
    # TRANSLATORS: the first argument is string to look for,
    # the second argument is section (Group|Support|Bugs|Task|Patch|People).
    printf (_('Search results for %1$s in %2$s:'),
      '<b>' . utils_specialchars ($words) . '</b>',
      $type_of_search_real
    );
  else
    # TRANSLATORS: the first argument is string to look for, the second
    # argument is section (Support|Bugs|Task|Patch|People), the third argument
    # is group name (like GNU Coreutils).
    printf (_('Search results for %1$s in %2$s, for the Group %3$s:'),
      '<b>' . utils_specialchars ($words) . '</b>',
      $type_of_search_real, group_getname ($only_group_id)
    );
  print "</p>\n";
}

function result_no_match ()
{
  return search_failed ();
}

function search_failed ()
{
  global $no_rows, $words;
  $no_rows = 1 ;
  search_send_header ();
  print '<span class="warn">';
  print _("None found. Please note that only search words of more than two\n"
          . "characters are valid.");
  print '</span>';
  print db_error ();
}

function search_failed_exit ()
{
  search_failed ();
  exit;
}

function search_keywords_field_sql ($keywords, $field, $and_or)
{
  $sql_bits = $sql_params = [];
  foreach($keywords as $keyword)
    {
      $sql_bits[] = "$field LIKE ?";
      if (preg_match('/_id$/', $field))
        # Strip "#" from, eg, "#153".
        $keyword = str_replace ('#', '', $keyword);
      $sql_params[] = "%$keyword%";
    }
  $bits = '(' . join (" $and_or ", $sql_bits) . ')';
  return [$bits, $sql_params];
}

# Build
# "((field1 LIKE '%kw1%' and/or field1 LIKE '%kw2%' ...)
#   or (field2 LIKE '%kw1%' and/or field2 LIKE '%kw2%' ...)
#   ...)"
# + matching parameters array, suitable for db_execute()
# $and_or <=> 'AND'/'OR' <=> all/any word
function search_keywords_in_fields ($keywords, $fields, $and_or = 'OR')
{
  $sql_bits = $sql_params = [];
  foreach ($fields as $field)
    {
      list ($bits, $params) =
        search_keywords_field_sql ($keywords, $field, $and_or);
      $sql_bits[] = $bits;
      $sql_params = array_merge ($sql_params, $params);
    }
  $allfields_sql = '(' . join (' OR ', $sql_bits) . ')';
  return [$allfields_sql, $sql_params];
}

function search_convert_keywords_to_sql ($keywords)
{
  # Remove useless blank spaces.
  $keywords = trim ($keywords);

  # Convert the wildcard * to the similar SQL one, when it is alone.
  if ($keywords == "*")
    $keywords = "%%%";

  # Replace the wildcard * to the similar SQL one, when included in a word.
  $keywords = strtr ($keywords, "*", "%");
  return $keywords;
}

function search_sanitize ($keywords, $exact, $exit_on_error)
{
  if (!is_scalar ($keywords))
    search_failed_exit ();

  $keywords = search_convert_keywords_to_sql ($keywords);
  $and_or = $exact? 'AND': 'OR';

  # Accept only to do a search for more than 2 characters.
  # Exit only if we were not told to avoid returning error messages.
  # Note: we tell user we want more than 3 characters, to incitate to
  # do clever searches.  But it will be ok for only 2 characters (limit
  # that conveniently allow us to search by items numbers).
  if ($keywords && (strlen ($keywords) < 3) && $exit_on_error)
    search_failed_exit ();
  return [explode (" ", $keywords), $and_or];
}

function search_soft_params ($keywords, $and_or, $type)
{
  $fields = [
    'group_id', 'group_name', 'short_description', 'unix_group_name'
  ];
  $sql = "groups WHERE status = 'A' AND is_public = '1'";
  $sql_params = [];
  if ($type)
    {
      $sql .= " AND type = ?";
      $sql_params[] = $type;
      $fields[] = 'type';
    }
  list ($kw_sql, $kw_params) =
    search_keywords_in_fields ($keywords, $fields, $and_or);
  if (!in_array ('type', $fields))
    $fields[] = 'type';
  $sql .= " AND $kw_sql";
  $sql_params = array_merge ($sql_params, $kw_params);
  return [$fields, $sql, $sql_params, 'group_name, unix_group_name'];
}

function search_people_params ($keywords, $and_or)
{
  $fields = ['user_id', 'user_name', 'realname', 'user_id'];
  $sql = "user WHERE status = 'A'";

  list ($kw_sql, $sql_params) =
    search_keywords_in_fields ($keywords, $fields, $and_or);
  $sql .= " AND $kw_sql";
  return [$fields, $sql, $sql_params, 'user_name'];
}

function search_tracker_params ($keywords, $and_or, $tracker, $only_group_id)
{
  $fields = ['t.bug_id', 't.details', 't.summary'];
  $sql = "$tracker t, user, groups
    WHERE user.user_id = t.submitted_by AND groups.group_id = t.group_id";
  if ($tracker != 'cookbook')
    # Cookbook is enabled in the `group_preferences` table.
    # Allow cookbook lookups even when cookbook isn't used.
    $sql .= " AND groups.use_$tracker = 1";

  list ($kw_sql, $sql_params) =
    search_keywords_in_fields ($keywords, $fields, $and_or);
  $sql .= " AND $kw_sql";
  if ($only_group_id)
    {
      $sql .= " AND t.group_id = ?";
      $sql_params[] = $only_group_id;
    }
  $sql .= " AND t.spamscore < 5";
  array_push ($fields,
    't.date', 't.privacy', 't.submitted_by', 'user.user_name', 't.group_id'
  );
  return [$fields, $sql, $sql_params, 't.bug_id DESC'];
}

function search_get_row_count ($sql, $fields, $sql_params)
{
  $result = db_execute (
    "SELECT count(DISTINCT({$fields[0]})) AS cnt FROM $sql", $sql_params
  );
  if (!db_numrows ($result))
    search_failed_exit ();
  return db_result ($result, 0, 'cnt');
}

function search_run_query ($sql, $sql_params, $fields, $order)
{
  global $max_rows, $offset;
  $fields = join (', ', $fields);
  $sql = "SELECT $fields FROM $sql ORDER BY $order LIMIT ?, ?";
  array_push ($sql_params, intval ($offset), $max_rows + 1);
  return db_execute ($sql, $sql_params);
}

# Run a search in the database, by default in programs.
function search_run ($keywords, $search_area = "soft", $exit_on_error = 1)
{
  global $type, $exact, $offset, $max_rows, $only_group_id, $search_total_rows;
  $search_total_rows = 0;
  list ($keywords, $and_or) =
    search_sanitize ($keywords, $exact, $exit_on_error);
  if ($search_area == "soft")
    list ($fields, $sql, $sql_params, $order) =
      search_soft_params ($keywords, $and_or, $type);
  elseif ($search_area == "people")
    list ($fields, $sql, $sql_params, $order) =
      search_people_params ($keywords, $and_or);
  elseif (!in_array ($search_area, utils_get_tracker_list ()))
    exit_error (_("Invalid search."));
  else
    list ($fields, $sql, $sql_params, $order) =
      search_tracker_params ($keywords, $and_or, $search_area, $only_group_id);
  $search_total_rows = search_get_row_count ($sql, $fields, $sql_params);
  return search_run_query ($sql, $sql_params, $fields, $order);
}

function search_exact ($keywords)
{
  # Find the characters that maybe for a non-precise search.
  # No need to continue if it they are present.
  $non_precise_key1 = strpos ($keywords, '*');
  $non_precise_key2 = strpos ($keywords, '%');

  if (!($non_precise_key1 === false && $non_precise_key2 === false))
    return;
  $arr_keywords = explode (' ', $keywords);
  $ph = utils_in_placeholders ($arr_keywords);
  $sql = "
    SELECT group_name, unix_group_name, short_description, name
    FROM groups, group_type
    WHERE
      type = type_id AND group_name $ph AND status = 'A' AND is_public = '1'";
  $result = db_execute ($sql, $arr_keywords);
  if (db_numrows ($result) != 1)
    return;
  # TRANSLATORS: this is a title for search results when exactly one item is found.
  print html_h (2, _("Unique group search result"));
  print "<p>";
  printf (_("Search string was: %s."),
    '<b>' . utils_specialchars ($keywords) . '</b>'
  );
  print "</p>\n";
  print html_build_list_table_top ([_("Group"), _("Description"), _("Type")]);
  print "\n";
  $row = db_fetch_array ($result);
  $name = gettext ($row['name']);
  print "<tr><td><a href=\"../projects/{$row['unix_group_name']}\">"
    . "{$row['group_name']}</a></td>\n<td>{$row['short_description']}</td>\n"
    . "<td>$name</td></tr>\n</table>\n";
}
?>
