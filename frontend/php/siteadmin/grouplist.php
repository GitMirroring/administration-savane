<?php
# List groups by given criteria.
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

require_once ('../include/init.php');

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.

site_admin_header (
  ['title' => no_i18n ("Group List"), 'context' => 'admgroup']
);

extract (sane_import ('get',
  [
    'name' => 'group_name_search',
    'preg' => [['status', '/^[A-Z]$/']],
    'pass' => 'search',
    'true' => 'groupsearch'
  ]
));

extract (sane_import ('post',
  ['digits' => 'group_id_to_assign', 'true' => 'assign_gid']
));

form_check (['assign_gid']);
html_nextprev_extract_params (100);

function fetch_member_data ($data)
{
  $ret = $gids = [];
  foreach ($data as $row)
    {
      $gids[] = $row['group_id'];
      $ret[$row['group_id']] = [];
    }
  $res = db_execute ("
    SELECT `user_id`, `group_id`, `admin_flags` FROM `user_group`
    WHERE `group_id` " . utils_in_placeholders ($gids), $gids
  );
  while ($row = db_fetch_array ($res))
    {
      $gid = $row['group_id'];
      if (!array_key_exists ($gid, $ret))
        continue;
      if (empty ($ret[$gid][$row['admin_flags']]))
        $ret[$gid][$row['admin_flags']] = 1;
      else
        $ret[$gid][$row['admin_flags']]++;
    }
  return $ret;
}

function count_members ($data)
{
  $ret = [];
  foreach (fetch_member_data ($data) as $group_id => $row)
    {
      $out = [];
      foreach ($row as $k => $v)
        $out[] = "[$k] => $v";
      if (empty ($out))
        $out = ['<b>0</b>'];
      $ret[$group_id] = join ('<br />', $out);
    }
  return $ret;
}

function group_gidN_label ($grp)
{
  if ($grp['gidNumber'] !== null)
    return $grp['gidNumber'];
  $ret = "<b>NULL</b>";
  if (empty ($GLOBALS['GROUP_STATUS_EDITABLE'][$grp['status']]))
    return $ret;
  return "$ret<br />\n" . form_tag ()
    . form_hidden (['group_id_to_assign' => $grp['group_id']])
    . form_submit (no_i18n ("Assign"), 'assign_gid') . "</form>\n";
}

function output_group ($grp, $members, $inc)
{
  global $status_arr;
  $gid = group_gidN_label ($grp);
  $name = $grp['group_name'];
  $status = $grp['status'];
  if ($status != GROUP_STATUS_SPECIAL)
    $name = "<a href=\"groupedit.php?group_id={$grp['group_id']}\">$name</a>";
  print '<tr class="' . utils_altrow ($inc) . '">';
  print "<td>$name</td>\n<td>{$grp['unix_group_name']}</td>\n<td>$gid</td>\n";
  print "<td>{$status_arr[$status]}</td>\n";
  print '<td>' . ($grp['is_public']? no_i18n ("public"): no_i18n ("private"))
    . "</td>\n";
  print "<td>$grp[license]</td>\n<td>$members</td>\n";
  print "</tr>\n";
}

if (!empty ($assign_gid) && !empty ($group_id_to_assign))
  group_assign_gidNumber ($group_id_to_assign);

print html_h (2, no_i18n ("Group List Filter"));

$title_arr = [no_i18n ("Status"), no_i18n ("Number")];
$inc = 0;
print html_build_list_table_top ($title_arr);
print '<tr class="' . utils_altrow ($inc++) . '">';
$res = db_execute ("SELECT count(*) AS count FROM groups");
$row = db_fetch_array ();
print '<td><a href="grouplist.php">' . no_i18n ("Any") . "</a></td>\n";
print '<td>' . $row['count'] . "</td\n";
print "</tr>\n";

print '<tr class="' . utils_altrow ($inc++) . '">';
$res = db_execute (
  "SELECT count(*) AS count FROM groups WHERE status = ?",
  [GROUP_STATUS_PENDING]
);
$row = db_fetch_array ();
print '<td><a href="grouplist.php?status=P">'
  . no_i18n ("Pending groups (an open task should exist about them)")
  . "</a></td>\n<td>" . $row['count'] . "</td>\n</tr>\n";

print '<tr class="' . utils_altrow ($inc++) . '">';
$res = db_execute (
  "SELECT count(*) AS count FROM groups WHERE status = ?",
  [GROUP_STATUS_DELETED]
);
$row = db_fetch_array ();
print '<td><a href="grouplist.php?status=D">'
  . no_i18n ("Deleted groups (the backend will remove the record soon)")
  . "</a></td>\n<td>" . $row['count'] . "</td>\n";
print "</tr>\n";
print "</table>\n";

$abc_array = [
  'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
  'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3',
  '4', '5', '6', '7', '8', '9'
];

$status_arr = [
  'A' => no_i18n ("Active"), 'P' => no_i18n ("Pending"),
  'D' => no_i18n ("Deleted"), 'X' => no_i18n ("System internal")
];
$status_proj_arr = [
  'A' => no_i18n ("Active Groups"), 'P' => no_i18n ("Pending Groups"),
  'D' => no_i18n ("Deleted Groups"), 'X' => no_i18n ("System internal Groups")
];

print html_h (2, no_i18n ("Group Search")) . "<p>"
  . no_i18n ("Display Groups beginning with:") . ' ';

for ($i = 0; $i < count ($abc_array); $i++)
  print "<a href=\"grouplist.php?group_name_search="
    . "$abc_array[$i]\">$abc_array[$i]</a> ";

print "<br />\n"
  . no_i18n ("or search by group_id, group_unix_name or group_name:");

print "\n"
  . form_tag (['name' => 'gpsrch', 'method' => 'get'])
  . form_input ('text', 'search', $search) . "\n"
  . form_hidden (['groupsearch' => '1'])
  . form_submit (no_i18n ("Search")) . "</form>\n</p>\n";

print html_h (2, no_i18n ("Group List"));

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
  $msg = no_i18n ("Groups that match") . " <b>'"
    . utils_specialchars ($search) . "'</b>\n";
  $where = "group_id LIKE '%$search%' OR unix_group_name "
    . "LIKE '%$search%' OR group_name LIKE '%$search%'";
  $search_url = "&groupsearch=1&search=" . utils_urlencode ($search);
}

$total_rows = 0;
$res = db_execute (
  "SELECT count(DISTINCT(`group_id`)) AS `cnt` FROM `groups` WHERE $where"
);

if (!$res)
  $feedback = db_error ();
$total_rows = db_fetch_array ($res)['cnt'];

$res = db_execute ("
  SELECT `group_name`, `unix_group_name`, `group_id`, `is_public`, `status`,
    `license`, `gidNumber`
  FROM `groups` WHERE $where ORDER BY `group_name` LIMIT ?, ?",
  [$offset, $max_rows + 1]
);
if (!$res)
  $feedback = db_error ();
print "<p><strong>$msg</strong></p>\n";

$rows = db_numrows ($res);

$title_arr = [
  no_i18n ("Group Name"), no_i18n ("System Name"), no_i18n ('gidNumber'),
  no_i18n ("Status"), no_i18n ("Visibility"), no_i18n ("License"),
  no_i18n ("Members")
];

print html_build_list_table_top ($title_arr);

if ($rows < 1)
  {
    print '<tr class="' . utils_altrow ($inc++) . '"><td colspan="7">';
    print no_i18n ("No matches.");
    print "</td></tr>\n";
  }
else
  {
    if ($rows > $max_rows)
      $rows = $max_rows;
    $data = [];
    for ($i = 0; $i < $rows; $i++)
      $data[$i] = db_fetch_array ($res);
    $members = count_members ($data);
    foreach ($data as $d)
      output_group ($d, $members[$d['group_id']], $inc++);
  }
print "</table>\n";

html_nextprev (
  '?groupsearch=1&amp;group_name_search=' . utils_urlencode ($group_name_search)
  . '&amp;search=' . utils_urlencode ($search),
  $offset, $max_rows, $total_rows
);

site_admin_footer ([]);
?>
