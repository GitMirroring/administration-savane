<?php
# Various boxes.
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

function show_altrow ($i)
{
  return '<div class="' . utils_altrow ($i) . '"><span class="smaller">';
}

function show_features_boxes ()
{
  global $HTML;
  $return = '';

  # General stats.
  $return .= $HTML->box_top (
    utils_link (
      $GLOBALS['sys_home'] . "stats/",
      # TRANSLATORS: the argument is site name (like Savannah).
      sprintf(_("%s Statistics"), $GLOBALS['sys_name']), "sortbutton"
    )
  );
  $return .= show_sitestats ();
  $return .= $HTML->box_bottom ();

  # Job offers stats.
  $jobs = people_show_category_list ();

  if ($jobs)
    {
      $return .= "<br />\n";
      $return .= $HTML->box_top (_("Help Wanted"),'',1);
      $return .= $jobs;
      $return .= $HTML->box_bottom (1);
    }

  # Popular items.
  $votes = show_votes ();

  if ($votes)
    {
      $return .= "<br />\n";
      $return .= $HTML->box_top (_("Most Popular Items"), '', 1);
      $return .= $votes;
      $return .= $HTML->box_bottom (1);
    }

  # Group type stats.
  $result = db_query ("SELECT type_id, name FROM group_type ORDER BY name");
  $limit = 5;

  while ($eachtype = db_fetch_array ($result))
    {
      $groupdata = show_newest_groups ($eachtype['type_id'], $limit);
      if (!$groupdata)
        continue;
      # TRANSLATORS: the argument is group type like Official GNU software
      # or www.gnu.org translation teams; for the full list, check
      # frontend/site-specific/gnu/admin/groupedit_grouptype.php.
      $lname = gettext ($eachtype['name']);
      $return .= "<br />\n";
      $return .= $HTML->box_top (sprintf (_("Newest %s"), $lname), '', 1);
      $return .= $groupdata;
      global $j, $sys_home;
      $return .= show_altrow ($j) . '<a href="'
        . "{$sys_home}search/?type_of_search=soft&amp;words=%%%&amp;type="
        . $eachtype['type_id'] . '">[';
      # TRANSLATORS: the argument is group type like Official GNU software
      # or www.gnu.org translation teams.
      $return .= sprintf (_("all %s"), $lname) . "]</a></span></div>\n";
      $return .= $HTML->box_bottom (1);
    }
  return $return;
}

function show_sitestats ()
{
  global $sys_home;

  $return = '<span class="smaller">';
  $users = stats_getusers ();
  $return .= sprintf (
    ngettext ("%s registered user", "%s registered users", $users),
    "<strong>$users</strong>"
  );
  $return .= "</span></div>\n";
  $i = 0;
  $return .= show_altrow ($i++);
  $groups = stats_getprojects_active ();
  $return .= sprintf (
    ngettext ("%s hosted group", "%s hosted groups", $groups),
    "<strong>$groups</strong>"
  );
  $return .= "</span></div>\n";
  $result = db_query ("SELECT type_id, name FROM group_type ORDER BY name");
  while ($eachtype = db_fetch_array ($result))
    {
      $n = stats_getprojects_bytype_active ($eachtype['type_id']);
      if ($n < 1)
        continue;
      $return .= show_altrow ($i++);
      $return .= "&nbsp;&nbsp;- <a href=\"{$sys_home}search/"
        . '?type_of_search=soft&amp;words=%%%&amp;type='
        . $eachtype['type_id'] . '" class="center">';
      $return .= ' ' . gettext ($eachtype['name']) . ": $n</a></span></div>\n";
    }
  $pending = stats_getprojects_pending ();
  $return .= show_altrow ($i++) . '&nbsp;&nbsp;';
  $return .= sprintf (
    ngettext (
     "+ %s registration pending", "+ %s registrations pending", $pending
    ),
    $pending
  );
  return $return . '</span>';
}

# Show groups that were added less than 2 months ago.
function show_newest_groups ($group_type, $limit)
{
  global $j, $sys_home;

  $since = time () - 2 * 30 * 24 * 3600;
  $res_newgrp = db_execute ("
    SELECT group_id, unix_group_name, group_name, register_time FROM groups
    WHERE is_public = 1 AND status = 'A' AND type = ? AND register_time >= ?
    ORDER BY register_time DESC LIMIT ?", [$group_type, $since, $limit]
  );
  if (!db_numrows ($res_newgrp))
    return false;

  $base_url = '';
  $res_newgrp_type = db_execute (
    "SELECT type_id, base_host FROM group_type WHERE type_id = ?",
    [$group_type]
  );
  $row_newgrp_type = db_fetch_array ($res_newgrp_type);
  if ($row_newgrp_type['base_host'])
    $base_url = 'http' . (session_issecure () ? 's' : '') . '://'
      . $row_newgrp_type['base_host'];

  $return = '';
  while ($row = db_fetch_array ($res_newgrp))
    if ($row['register_time'])
      {
        $return .= show_altrow ($j++) . '&nbsp;&nbsp;- <a href="'
          . "$base_url{$sys_home}projects/$row[unix_group_name]/\">"
          . $row['group_name'] . '</a>, '
          . utils_format_date ($row['register_time'], 'minimal')
          . '</span></div>';
      }
  return $return;
}

function get_top_votes ($limit)
{
  $tables = [];
  foreach (["bugs", "task", "support", "patch"] as $tracker)
    {
      $tables[] = "
        (SELECT '$tracker' AS tracker, bug_id, group_id, summary, vote
        FROM $tracker
        WHERE vote >= 35 AND privacy = 1 AND status_id = 1 AND spamscore < 5)
      ";
    }
  $sql = join ("UNION ALL", $tables) . " ORDER BY vote DESC LIMIT ?";
  return db_execute ($sql, [$limit]);
}

# If the summary of the item is large, only show the first 30 characters.
function trim_summary ($summary)
{
  if (strlen ($summary <= 30))
    return $summary;
  $summary = substr ($summary, 0, 30);
  $summary = substr ($summary, 0, strrpos ($summary, ' '));
  return "$summary...";
}

function format_vote_item ($count, $v)
{
  global $sys_home;
  $tracker = $v['tracker'];
  $item_id = $v['bug_id'];
  $prefix = utils_get_tracker_prefix ($tracker);
  $summary = trim_summary ($v['summary']);

  $url = "$sys_home$tracker/?$item_id";
  return show_altrow ($count) . '&nbsp;&nbsp;- '
    . "<a href=\"$url\">$prefix #$item_id</a>: &nbsp;"
    . "<a href=\"$url\">$summary</a>,&nbsp;"
    . sprintf (ngettext ("%s vote", "%s votes", $v['vote']), $v['vote'])
    . "</span></div>\n";
}

# Find out most popular items and return a string listing them.
# Closed, private and spam items are ignored.
function show_votes ($limit = 10)
{
  $votes = get_top_votes ($limit);
  $return = '';
  $count = 0;
  while ($v = db_fetch_array ($votes))
    $return .= format_vote_item (++$count, $v);
  return $return;
}
?>
