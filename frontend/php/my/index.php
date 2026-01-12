<?php
# User's start page.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2026 Ineiev
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

list ($usergroups_groupid, $usergroups) = user_group_names (user_getid ());
# Get the list of squads the user is member of.
list ($usersquads, $nosquads) = user_get_squads ();

# Get a timestamp to get new items (15 days).
$date_limit = time () - 15 * 24 * 3600;

# Right part.
print html_splitpage (1);

function fetch_news ($date_limit, $gids, $cond)
{
  if (empty ($gids))
    return false;
  $gid_sql = "group_id " . utils_in_placeholders ($gids);
  $res = db_execute ("
    SELECT group_id, date, id, summary FROM news_bytes
    WHERE date > ? AND $cond AND $gid_sql
    ORDER BY date DESC", array_merge ([$date_limit], $gids)
  );
  return $res;
}

function output_news_entry ($row, $j)
{
  print '<div class="' . utils_altrow ($j) . '">';
  print "<a href=\"/news/approve.php?approve=1&amp;id="
    . $row['id'] . '&amp;group=' . group_getunixname ($row['group_id']) . '">'
    . $row['summary'] . "</a><br />\n";
  print '<span class="smaller">';
  # TRANSLATORS: the first argument is group name, the second is date.
  printf (_('Group %1$s, %2$s'),
    group_getname ($row['group_id']), utils_format_date ($row['date'])
  );
  print "</span>\n</div>\n";
}

# News to approve.
# Show only if the user is a news manager somewhere and if any item found.
reset ($usergroups);
reset ($usergroups_groupid);
unset ($rows);
$gids = [];
$member_flags = member_create_tracker_flag ('news') . MEMBER_ROLE_MANAGER;
foreach ($usergroups as $group => $groupname)
  {
    if (!member_check (0, $usergroups_groupid[$group], $member_flags))
      continue;
    $gids[] = $usergroups_groupid[$group];
  }
$res = fetch_news ($date_limit, $gids, "is_approved = '5'");
if (db_numrows ($res))
  {
    print "<br />\n<div class='box'><div class='boxtitle'>"
      . _("News Waiting for Approval") . "</div>\n";
    $j = 0;
    while ($row = db_fetch_array ($res))
      output_news_entry ($row, $j++);
    print "</div>\n";
  }

print "<br />\n<div class='box'><div class='boxtitle'>"
  . _("News") . "</div>\n";
reset ($usergroups);
reset ($usergroups_groupid);
$gids = [$sys_group_id];
foreach ($usergroups as $group => $groupname)
  $gids[] = $usergroups_groupid[$group];

$res = fetch_news ($date_limit, $gids, "is_approved IN ('0', '1')");
$j = 0;
while ($row = db_fetch_array ($res))
  output_news_entry ($row, $j++);
if (!$j)
  # TRANSLATORS: it means, no approved news.
  print _("None found");
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
