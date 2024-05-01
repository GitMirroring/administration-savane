<?php
# User's start page.
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
require_once ('../include/init.php');
require_once ('../include/my/general.php');
require_directory ("trackers");

global $item_data, $group_data;
$item_data = $group_data = [];

if (!user_isloggedin ())
  exit_not_logged_in ();

site_user_header (['context' => 'my']);

print '<p>'
  . _("Here's a list of recent items (less than 16 days) we think you should\n"
      . "have a look at.  These are items recently posted on trackers you\n"
      . "manage that are still unassigned or assigned to you and news posted\n"
      . "in groups you are a member of.")
  . "</p>\n";

# Get the list of projects the user is member of.
$result = db_execute ("
  SELECT g.group_name, g.group_id, g.unix_group_name, g.status
  FROM groups g, user_group u
  WHERE g.group_id = u.group_id AND u.user_id = ? AND g.status = 'A'
  GROUP BY g.unix_group_name ORDER BY g.unix_group_name", [user_getid ()]
);
$rows = db_numrows ($result);
$usergroups = $usergroups_groupid = [];
if ($result && $rows > 0)
  {
    unset ($nogroups);
    for ($j = 0; $j < $rows; $j++)
      {
        $unixname = db_result ($result, $j, 'unix_group_name');
        $usergroups[$unixname] = db_result ($result, $j, 'group_name');
        $usergroups_groupid[$unixname] = db_result ($result, $j, 'group_id');
      }
  }
else
  $nogroups = 1;

# Get the list of squads the user is member of.
$result = db_execute (
  "SELECT squad_id FROM user_squad WHERE user_id = ?", [user_getid ()]
);
$rows = db_numrows ($result);
$usersquads = [];
if ($result && $rows > 0)
  {
    unset ($nosquads);
    for ($j = 0; $j < $rows; $j++)
      $usersquads[] = db_result ($result, $j, 'squad_id');
  }
else
  $nosquads = 1;

# Get a timestamp to get new items (15 days).
$new_date_limit = time () - 15 * 24 * 3600;

# Right part.
print html_splitpage (1);

# News to approve.
# Shown only if the user is news manager somewhere and if any item found.
reset ($usergroups);
reset ($usergroups_groupid);
unset ($result);
unset ($rows);
# Build an sql request that will fetch any relevant news.
$sql = "
  SELECT group_id, date, id, summary FROM news_bytes
  WHERE date > ? AND is_approved = '5' AND (";
$params = [$new_date_limit];
$link = '';

foreach ($usergroups as $group => $groupname)
  {
    if (!member_check (0, $usergroups_groupid[$group], 'N3'))
      continue;
    $sql .= "{$link}group_id = ? ";
    $link = "OR ";
    $params[] = $usergroups_groupid[$group];
  }
$sql .= ") ORDER BY date DESC";

# If there is no relevant group, it is not even necessary
# to run the sql command.
$result = NULL;
if ($link !== '')
  {
    $result = db_execute ($sql, $params);
    $rows = db_numrows ($result);
  }

if ($result && $rows > 0)
  {
    print "<br />\n<div class='box'><div class='boxtitle'>"
      . _("News Waiting for Approval") . "</div>\n";
    for ($j = 0; $j < $rows; $j++)
      {
        print '<div class="' . utils_altrow ($j) . '">';
        print '<a href="' . $sys_home . 'news/approve.php?approve=1&amp;id='
          . db_result ($result, $j, 'id') . '&amp;group='
          . group_getunixname (db_result ($result, $j, 'group_id')) . '">'
          . db_result($result, $j, 'summary') . "</a><br />\n";
        print '<span class="smaller">';
        # TRANSLATORS: the first argument is project name, the second is date.
        printf (_('Project %1$s, %2$s'),
          group_getname (db_result ($result, $j, 'group_id')),
          utils_format_date (db_result ($result, $j, 'date'))
        );
        print "</span>\n</div>\n";
      }
    print "</div>\n";
  }

# Latest Approved News.
print "<br />\n<div class='box'><div class='boxtitle'>"
  . _("News") . "</div>\n";
reset ($usergroups);
reset ($usergroups_groupid);
# Build an sql request that will fetch any relevant news.
$sql = "
SELECT group_id, date, forum_id, summary FROM news_bytes
WHERE
  date > ? AND is_approved in ('0', '1') AND (group_id = ? ";
$params = [$new_date_limit, $sys_group_id];

foreach ($usergroups as $group => $groupname)
  {
    $sql .= "OR group_id = ? ";
    $params[] = $usergroups_groupid[$group];
  }
$sql .= ") ORDER BY date DESC";

$result = db_execute ($sql, $params);
$rows = db_numrows ($result);
if ($result && $rows > 0)
  for ($j = 0; $j < $rows; $j++)
    {
      print '<div class="' . utils_altrow ($j) . '">';
      print '<a href="' . $sys_home . 'forum/forum.php?forum_id='
        . db_result ($result, $j, 'forum_id') . '">'
        . db_result ($result, $j, 'summary') . "</a><br />\n";
      print '<span class="smaller">';
      # TRANSLATORS: the first argument is project name, the second is date.
      printf (_('Project %1$s, %2$s'),
        group_getname (db_result ($result, $j, 'group_id')),
        utils_format_date (db_result ($result, $j, 'date'))
      );
      print "</span></div>\n";
    }
else
  {
    # TRANSLATORS: it means, no approved news.
    print _("None found");
  }
print "</div>\n";

# Left part.
print html_splitpage (2);

# New items to assign.
# Shown only if the user is tracker manager somewhere and if any item found
# (so the title is included in the function called).

print '<br /><div class="box"><div class="boxtitle">'
  . _("New and Unassigned Items") . "</div>\n";
print my_item_list ("unassigned");
print "</div>\n";

# Items newly assigned (not necessarily new items).
print '<br /><div class="box"><div class="boxtitle">'
  . _("New and Assigned Items") . "</div>\n";
print my_item_list ("newlyassigned");
print "</div>\n";
print html_splitpage (3);
print "\n\n" . show_priority_colors_key ();
$HTML->footer ([]);
?>
