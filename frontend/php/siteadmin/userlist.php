<?php
# List users.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
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

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
require_once ('../include/init.php');
require_once ('../include/form.php');

site_admin_header (['title' => no_i18n("User List"), 'context' => 'admuser']);

extract (sane_import ('get',
  [
    'digits' => ['offset', 'user_id'],
    'specialchars' => 'text_search',
    'name' => 'user_name_search',
  ]
));
extract (sane_import ('request', ['pass' => 'search']));

$abc_array = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
  'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1',
  '2', '3', '4', '5', '6', '7', '8', '9', '_'
];

print html_h (2, no_i18n ("User Search"))
  . no_i18n ("Display users beginning with:") . ' ';

for ($i = 0; $i < count ($abc_array); $i++)
  print '<a href="'
    . "$php_self?user_name_search=$user_name_search{$abc_array[$i]}\">"
    . "$user_name_search{$abc_array[$i]}</a>\n";

print "<br />\n"
  . no_i18n ("Search by email, username, realname or userid:") . "\n";
print form_tag (['method' => 'get', 'name' => 'usersrch'])
  . form_input ('text', 'text_search',  $text_search)
  . form_hidden (['usersearch' => '1'])
  . form_submit (no_i18n ("Search")) . "\n</form>\n</p>\n";

$MAX_ROW = 100;
$offset = intval($offset);

$sql_fields =
  "user.user_id, user.user_name, user.status, user.people_view_skills";
$sql_order = 'ORDER BY user.user_name LIMIT ?, ?';

if ($group_id)
  {
    # Show list for one group.
    $group_listed = group_getname ($group_id);

    $result = db_execute ("
      SELECT $sql_fields FROM user, user_group
      WHERE user.user_id = user_group.user_id AND user_group.group_id = ?
      $sql_order",
      [$group_id, $offset, $MAX_ROW + 1]
    );
  }
else
  {
    $group_listed = no_i18n("All Groups");

    if ($user_name_search)
      {
        $result = db_execute("
          SELECT $sql_fields FROM user
          WHERE user_name LIKE ?
          $sql_order",
          [
            str_replace ('_', '\_', $user_name_search) . '%',
            $offset, $MAX_ROW + 1
          ]
        );
      }
    elseif ($text_search)
      {
        $term = utils_specialchars_decode ($text_search);
        $result = db_execute ("
          SELECT $sql_fields FROM user
          WHERE
            user_name LIKE ? OR user_id LIKE ?
            OR realname LIKE ? OR email LIKE ?
          $sql_order",
          [$term, $term, $term, $term, $offset, $MAX_ROW + 1]
        );
      }
    else
      {
        $result = db_execute ("
          SELECT $sql_fields FROM user $sql_order",
          [$offset, $MAX_ROW + 1]
        );
      }
  }

print '<h2>';
printf (no_i18n ("User List for %s"), "<strong>$group_listed</strong>");
print "</h2>\n";

$rows = $rows_returned = db_numrows ($result);

print html_build_list_table_top (
  [no_i18n ("Id"), no_i18n ("User"), no_i18n ("Status"), no_i18n ("Profile")]
);

function finish_page ()
{
  global $user_name_search, $search, $text_search, $rows, $rows_returned;
  global $HTML, $php_self;
  print "</table>\n";
  html_nextprev (
    "$php_self?user_name_search=$user_name_search"
    . '&amp;usersearch=1&amp;search=' . utils_urlencode ($search)
    . "&amp;text_search=$text_search",
    $rows, $rows_returned
  );
  $HTML->footer ([]);
  exit (0);
}

$inc = 0;
if ($rows_returned < 1)
{
  print '<tr class="' . utils_altrow ($inc++)
    . '"><td colspan="7">'. no_i18n ("No matches") . ".</td></tr>\n";
  finish_page ();
}

if ($rows_returned > $MAX_ROW)
  $rows = $MAX_ROW;

for ($i = 0; $i < $rows; $i++)
  {
    $usr = db_fetch_array ($result);
    $stat = $usr['status'];
    $usr_id = $usr['user_id'];
    print '<tr class="' . utils_altrow ($inc++)
      . "\">\n<td>$usr_id</td>\n"
      . "<td><a href=\"usergroup.php?user_id=$usr_id\">"
      . "$usr[user_name]</a></td>\n<td>\n";

    switch ($stat)
      {
      case USER_STATUS_ACTIVE: print no_i18n ("Active"); break;
      case USER_STATUS_DELETED: # Fall through.
      case USER_STATUS_SUSPENDED: print no_i18n ("Deleted"); break;
      case USER_STATUS_SQUAD: print no_i18n ("Active (Squad)"); break;
      case USER_STATUS_PENDING: print no_i18n ("Pending"); break;
      default: print no_i18n ("Unknown status") . ": $stat"; break;
      }
    if ($usr['people_view_skills'] == 1)
      print '<td><a href="' . $GLOBALS['sys_home']
        . "people/resume.php?user_id=$usr_id\">["
        . no_i18n ("View") . "]</a></td>\n";
    else
      print '<td>(' . no_i18n ("Private") . ")</td>\n";
    print "\n</tr>\n";
  }
finish_page ()
?>
