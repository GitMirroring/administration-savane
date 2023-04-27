<?php
# List groups by given criteria.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2023 Ineiev
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

require_once ('../include/init.php');

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.

site_admin_header (
  ['title' => no_i18n ("Group List"), 'context' => 'admgroup']
);

extract (sane_import ('post', ['pass' => 'search', 'true' => 'groupsearch']));
extract (sane_import ('get',
  [
    'digits' => ['offset', 'max_rows'],
    'name' => 'group_name_search',
    'preg' => [['status', '/^[A-Z]$/']],
  ]
));

print '<h2>' . no_i18n ("Group List Filter") . "</h2>\n";

$title_arr = [no_i18n ("Status"), no_i18n ("Number")];
$inc = 0;
print html_build_list_table_top ($title_arr);
print '<tr class="' . utils_altrow ($inc++) . '">';
$res = db_query ("SELECT count(*) AS count FROM groups");
$row = db_fetch_array ();
print '<td><a href="grouplist.php">' . no_i18n ("Any") . "</a></td>\n";
print '<td>' . $row['count'] . "</td\n";
print "</tr>\n";

print '<tr class="' . utils_altrow ($inc++) . '">';
$res = db_query ("SELECT count(*) AS count FROM groups WHERE status = 'P' ");
$row = db_fetch_array ();
print '<td><a href="grouplist.php?status=P">'
  . no_i18n (
      "Pending groups (normally, an opened task should exist about them)"
    )
  . "</a></td>\n<td>" . $row['count'] . "</td>\n</tr>\n";

print '<tr class="' . utils_altrow ($inc++) . '">';
$res = db_query ("SELECT count(*) AS count FROM groups WHERE status = 'D' ");
$row = db_fetch_array ();
print '<td><a href="grouplist.php?status=D">'
  . no_i18n ("Deleted groups (the backend will remove the record soon)")
  . "</a></td>\n<td>" . $row['count'] . "</td>\n";
print "</tr>\n";
print "</table>\n";

$MAX_ROW = !empty ($max_rows)? $max_rows: 100;

$abc_array = [
  'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
  'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3',
  '4', '5',  '6', '7', '8', '9'
];

$status_arr = [
  'A' => no_i18n ("Active"), 'P' => no_i18n ("Pending"),
  'I' => no_i18n ("Incomplete"), 'D' => no_i18n ("Deleted"),
  'M' => no_i18n ("Maintenance"), 'X' => no_i18n ("System internal")
];
$status_proj_arr = [
  'A' => no_i18n ("Active Groups"), 'P' => no_i18n ("Pending Groups"),
  'I' => no_i18n ("Incomplete Groups"), 'D' => no_i18n ("Deleted Groups"),
  'M' => no_i18n ("Maintenance Groups"),
  'X' => no_i18n ("System internal Groups")
];

print '<h2>' . no_i18n ("Group Search") . "</h2>\n<p>"
  . no_i18n ("Display Groups beginning with:") . ' ';

for ($i = 0; $i < count ($abc_array); $i++)
  print "<a href=\"grouplist.php?group_name_search="
    . "$abc_array[$i]\">$abc_array[$i]</a> ";

print "<br />\n"
  . no_i18n ("or search by group_id, group_unix_name or group_name:");

print "\n"
  . "<form name='gpsrch' action='grouplist.php' method='POST'>\n"
  . "<input type='text' title=\"" . no_i18n ("Group name")
  . "\" name='search' value =\"" . utils_specialchars ($search) . "\" />\n"
  . form_hidden (['groupsearch' => '1'])
  . "<input type='submit' value=\"" . no_i18n ("Search") . "\" />\n"
  . "</form>\n</p>\n";

print '<h2>' . no_i18n ("Group List") . "</h2>\n";

if (!$offset or !ctype_digit (strval ($offset)) or $offset < 0)
  $offset = 0;
else
  $offset = intval ($offset);

$where = "1";
$msg = '';
if (isset ($group_name_search))
  {
    $msg = sprintf (no_i18n ("Groups that begin with %s"), $group_name_search);
    $where = "group_name LIKE '$group_name_search%' ";
    $search_url = "&group_name_search=$group_name_search";
  }
elseif (!empty ($status_arr[$status]))
  {
    $msg = $status_proj_arr[$status];
    $where = "status='$status'";
    $search_url = "&status=$status";
  }
elseif ($groupsearch)
{
  $msg = no_i18n ("Groups that match") . " <strong>'"
    . utils_specialchars ($search) . "'</strong>\n";
  $where = "group_id LIKE '%$search%' OR unix_group_name "
    . "LIKE '%$search%' OR group_name LIKE '%$search%'";
  $search_url = "&groupsearch=1&search=" . utils_urlencode ($search);
}

$res = db_execute ("
  SELECT DISTINCTROW
    group_name, unix_group_name, group_id, is_public, status, license
  FROM groups WHERE $where ORDER BY group_name LIMIT ?, ?",
  [$offset, $MAX_ROW + 1]
);
if (!$res)
  $feedback = db_error ();
print "<p><strong>$msg</strong></p>\n";

$rows = $rows_returned = db_numrows ($res);

$title_arr = [
  no_i18n ("Group Name"), no_i18n ("System Name"), no_i18n ("Status"),
  no_i18n ("Public?"), no_i18n ("License"), no_i18n ("Members")
];

print html_build_list_table_top ($title_arr);

if ($rows_returned < 1)
  {
    print '<tr class="' . utils_altrow ($inc++) . '"><td colspan="7">';
    print no_i18n ("No matches.");
    print "</td></tr>\n";
  }
else
  {
    if ($rows_returned > $MAX_ROW)
      $rows = $MAX_ROW;
    for ($i = 0; $i < $rows; $i++)
      {
        $grp = db_fetch_array ($res);
        print '<tr class="' . utils_altrow ($inc++) . '">';
        print "<td><a href=\"groupedit.php?group_id=$grp[group_id]\">"
          . "$grp[group_name]</a></td>\n";
        print "<td>$grp[unix_group_name]</td>\n";
        print '<td>' . $status_arr[$grp['status']] . "</td>\n";
        print '<td>' . ($grp['is_public']? no_i18n ("yes"): no_i18n ("no"))
          . "</td>\n";
        print "<td>$grp[license]</td>\n";

        $res_count = db_execute (
          "SELECT user_id FROM user_group WHERE group_id = ?",
          [$grp['group_id']]
        );
        print "<td>" . db_numrows ($res_count) . "</td>\n";
        print "</tr>\n";
      }
  }
print "</table>\n";

html_nextprev (
 '?groupsearch=1&amp;group_name_search=' . utils_urlencode ($group_name_search)
 . '&amp;search=' . utils_urlencode ($search),
 $rows, $rows_returned
);

site_admin_footer ([]);
?>
