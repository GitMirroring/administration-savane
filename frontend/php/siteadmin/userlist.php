<?php
# List users.
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

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
require_once ('../include/init.php');
require_once ('../include/form.php');

site_admin_header (['title' => no_i18n("User List"), 'context' => 'admuser']);

extract (sane_import ('get',
  [
    'digits' => 'user_id', 'specialchars' => 'text_search',
    'name' => 'user_name_search'
  ]
));
extract (sane_import ('post',
  ['digits' => 'user_id_to_assign', 'true' => 'assign_uid']
));
form_check (['assign_uid']);
extract (sane_import ('request', ['pass' => 'search']));
html_nextprev_extract_params (100);

$abc_array = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
  'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1',
  '2', '3', '4', '5', '6', '7', '8', '9', '_'
];

if (!empty ($assign_uid) && !empty ($user_id_to_assign))
  member_assign_uidNumber ($user_id_to_assign);

print html_h (2, no_i18n ("User search"))
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

$offset = intval ($offset);
$fields = ['user_id', 'user_name', 'status', 'people_view_skills', 'uidNumber'];
$columns = [];
foreach ($fields as $f)
  $columns[] = "`u`.`$f`";
$sql_fields = join (', ', $columns);
$sql_order = "\nORDER BY `u`.`user_name` LIMIT ?, ?";
$sql = "\nFROM `user` `u`";
$sql_params = $where = [];

$group_listed = no_i18n ("all groups");
if ($group_id)
  {
    $group_listed = "<b>" . group_getname ($group_id) . "</b>";
    $sql_params = [$group_id];
    $sql .= ' JOIN `user_group` `ug` ON `u`.`user_id` = `ug`.`user_id`';
    $where[] = '`ug`.`group_id` = ?';
  }

if ($user_name_search)
  {
    $where[] = ' `user_name` LIKE ?';
    $sql_params[] = str_replace ('_', '\_', $user_name_search) . '%';
  }
elseif ($text_search)
  {
    $where[] = '(`user_name` LIKE ? OR `u`.`user_id` LIKE ? '
      . 'OR `realname` LIKE ? OR `email` LIKE ?)';
    $term = utils_specialchars_decode ($text_search);
    $sql_params = array_merge ($sql_params, [$term, $term, $term, $term]);
  }
if (!empty ($where))
  $sql .= "\nWHERE " . join ("\n  AND ", $where);

$result = db_execute (
  "SELECT COUNT(DISTINCT(`u`.`user_id`)) AS `cnt` $sql", $sql_params
);
$total_rows = db_fetch_array ($result)['cnt'];
array_push ($sql_params, $offset, $max_rows + 1);
$result = db_execute ("SELECT $sql_fields $sql $sql_order", $sql_params);

print html_h (2, sprintf (no_i18n ("User list for %s"), $group_listed));

$rows = db_numrows ($result);

print html_build_list_table_top ([
  no_i18n ("Id"), no_i18n ("User"), no_i18n ("uidNumber"), no_i18n ("Status"),
  no_i18n ("Profile")
]);

function finish_page ()
{
  global $user_name_search, $search, $text_search;
  global $offset, $max_rows, $total_rows, $HTML, $php_self;
  print "</table>\n";
  html_nextprev (
    "$php_self?user_name_search=$user_name_search"
    . '&amp;usersearch=1&amp;search=' . utils_urlencode ($search)
    . "&amp;text_search=$text_search",
    $offset, $max_rows, $total_rows
  );
  $HTML->footer ([]);
  exit (0);
}

function uidNumber_label ($usr)
{
  if ($usr['uidNumber'] !== null)
    return $usr['uidNumber'];
  $ret = "<b>NULL</b>";
  if ($usr['status'] != USER_STATUS_ACTIVE)
    return $ret;
  return "$ret<br />\n" . form_tag ()
    . form_hidden (['user_id_to_assign' => $usr['user_id']])
    . form_submit (no_i18n ("Assign"), 'assign_uid') . "</form>\n";
}

function status_label ($stat)
{
  switch ($stat)
    {
    case USER_STATUS_ACTIVE: return no_i18n ("Active");
    case USER_STATUS_DELETED: # Fall through.
    case USER_STATUS_SUSPENDED: return no_i18n ("Deleted");
    case USER_STATUS_SQUAD: return no_i18n ("Active (Squad)");
    case USER_STATUS_PENDING: return no_i18n ("Pending");
    }
  return no_i18n ("Unknown status") . ": $stat";
}

function output_user ($usr, $class)
{
  $usr_id = $usr['user_id'];
  print "<tr class=\"$class\">\n<td>$usr_id</td>\n"
    . "<td><a href=\"usergroup.php?user_id=$usr_id\">"
    . "$usr[user_name]</a></td>\n";
  print "<td>" . uidNumber_label ($usr) . "</td>\n";
  print "<td>" . status_label ($usr['status']) . "</td>\n";
  if ($usr['people_view_skills'] == 1)
    print '<td><a href="' . $GLOBALS['sys_home']
      . "people/resume.php?user_id=$usr_id\">["
      . no_i18n ("View") . "]</a></td>\n";
  else
    print '<td>(' . no_i18n ("Private") . ")</td>\n";
  print "\n</tr>\n";
}

$inc = 0;
if ($rows < 1)
{
  print '<tr class="' . utils_altrow ($inc++)
    . '"><td colspan="7">'. no_i18n ("No matches") . ".</td></tr>\n";
  finish_page ();
}

if ($rows > $max_rows)
  $rows = $max_rows;

for ($i = 0; $i < $rows; $i++)
  output_user (db_fetch_array ($result), utils_altrow ($inc++));
finish_page ()
?>
