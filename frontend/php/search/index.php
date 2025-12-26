<?php
# Display search form and search results
# Copyright (C) 1999, 2000 The SourceForge Crew
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

require_once ('../include/init.php');

$tos_values = array_merge (['soft', 'people'], utils_get_tracker_list ());
extract (sane_import ('request',
  [
    'digits' => ['type', 'only_group_id', ['exact', [0, 1]]],
    'strings' => [['type_of_search', $tos_values]],
    'pass' => ['words0', 'words1', 'words'],
  ]
));

html_nextprev_extract_params (25);
foreach ([0, 1] as $n)
  if (!empty (${"words$n"}))
    $words = ${"words$n"};
if (empty ($words))
  $words = NULL;

if (!$words || !is_scalar ($words))
  {
    search_send_header ();
    print '<p>' . _("Enter your search words above.") . "</p>\n";
    $HTML->footer ([]);
    exit;
  }

function finish_page ()
{
  global $only_group_id, $words, $type_of_search;
  global $type, $exact, $search_total_rows, $offset, $max_rows;

  $nextprev_url = "/search/?type_of_search=$type_of_search&amp;words="
    . utils_urlencode ($words);
  if (isset ($type))
    $nextprev_url .= "&amp;type=$type";
  if (isset ($only_group_id))
    $nextprev_url .= "&amp;only_group_id=$only_group_id";
  if (isset ($exact))
    $nextprev_url .= "&amp;exact=$exact";

  html_nextprev ($nextprev_url, $offset, $max_rows, $search_total_rows);
  site_footer ([]);
  exit (0);
}

function check_search_fail ($result, $max_rows)
{
  $rows = db_numrows ($result);
  if ($rows)
    {
      if ($rows > $max_rows)
        $rows = $max_rows;
      return $rows;
    }
  search_failed ();
  finish_page ();
  return 0;
}

$result = search_run ($words, $type_of_search);
$rows = check_search_fail ($result, $max_rows);

if ($type_of_search == 'soft')
  {
    search_send_header ();
    search_exact ($words);
    print_search_heading ();
    $title_arr = [_("Group"), _("Description"), _("Type")];

    print html_build_list_table_top ($title_arr);
    print "\n";

    for ($i = 0; $i < $rows; $i++)
      {
        $row = db_fetch_array ($result);
        if (empty ($row))
          break;
        $res_type = db_execute (
          "SELECT name FROM group_type WHERE type_id = ?", [$row['type']]
        );
        $name = db_fetch_array ($res_type)['name'];

        print '<tr class="' . html_get_alt_row_color ($i)
          . '"><td><a href="../projects/' . $row['unix_group_name']
          . '">' . "<b>[{$row['unix_group_name']}]</b> "
          . $row['group_name'] . "</a></td>\n<td>"
          . $row['short_description'] . "</td>\n<td>"
          . gettext ($name) . "</td>\n</tr>\n";
      }
    print "</table>\n";
    print '<p>'
      . _("Note that <strong>private</strong> groups are not shown "
          . "on this page.")
      . "</p>\n";
    finish_page ();
  }
if ($type_of_search == "people")
  {
    if ($rows == 1 && $offset == 0)
      {
        $user = db_result ($result, 0, 'user_name');
        Header ("Location: /users/$user");
      }
    else
      {
        search_send_header ();
        print_search_heading ();

        print html_build_list_table_top ([_("Login"), _("Name")]);
        print "\n";

        for ($i = 0; $i < $rows; $i++)
          {
            $row = db_fetch_array ($result);
            $namequery = preg_replace ('/[^a-z]+/i', '+', $row['realname']);
            print "<tr class=\"" . html_get_alt_row_color ($i) . "\"><td>"
              . utils_user_link ($row['user_name'])
              . "</td>\n<td>" . $row['realname'] . "</td>\n</tr>\n";
          }
        print "</table>\n";
      }
    finish_page ();
  }
if (!in_array ($type_of_search, utils_get_tracker_list ()))
  {
    search_send_header ();
    print '<p class="error">' . _("Error") . ' - ' . _("Invalid Search!!")
      . "</p>\n";
    finish_page ();
  }

if ($rows == 1 && $offset == 0 && db_result ($result, 0, 'privacy') != "2")
  {
    # No automatic redirection for private item, use the usual listing.
    $bug = db_result ($result, 0, 'bug_id');
    Header (
      "Location: /$type_of_search/?func=detailitem&item_id=$bug"
    );
    finish_page ();
  }

search_send_header ();
print_search_heading ();

$titles = [_("Item Id"), _("Item Summary")];
if (empty ($only_group_id))
  $titles[] = _("Group");
$titles = array_merge ($titles, [_("Submitter"), _("Date")]);

print html_build_list_table_top ($titles);
print "\n";

$i = 0;
db_data_seek ($result);
while ($i < $rows && $row = db_fetch_array ($result))
  {
    if ($row['privacy'] == "2" && !member_check_private (0, $row['group_id'])
        && $row['user_name'] != user_getname ()
    )
      continue;
    $url = "/$type_of_search/?func=detailitem&amp;item_id="
      . $row["bug_id"];

    print '<tr class="' . html_get_alt_row_color ($i) . '">'
      . "<td><a href=\"$url\">#" . $row["bug_id"]
      . "</a></td>\n<td><a href=\"$url\">" . $row["summary"]
      . "</a></td>\n";
    if (empty ($only_group_id))
      print "<td><a href=\"$url\">" . group_getname ($row["group_id"])
        . "</a></td>\n";
    print  "<td>" . utils_user_link ($row["user_name"]) . "</td>\n<td>"
      . utils_format_date ($row["date"], 'natural') . "</td>\n</tr>\n";
     $i++;
  }
print "</table>\n";
finish_page ();
?>
