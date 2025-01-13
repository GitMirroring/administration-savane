<?php
# Various boxes.
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

function show_altrow ($i)
{
  return '<div class="' . utils_altrow ($i) . '"><span class="smaller">';
}

function show_general_stats ()
{
  global $HTML;
  $ret = $HTML->box_top (
    utils_link (
      $GLOBALS['sys_home'] . "stats/",
      # TRANSLATORS: the argument is site name (like Savannah).
      sprintf(_("%s Statistics"), $GLOBALS['sys_name']), "sortbutton"
    )
  );
  $ret .= show_sitestats ();
  return $ret . $HTML->box_bottom ();
}

function show_help_wanted ()
{
  global $HTML;
  $jobs = people_show_category_list ();
  if (!$jobs)
    return '';
  return "<br />\n" . $HTML->box_top (_("Help Wanted"), '', 1)
    . $jobs . $HTML->box_bottom (1);
}

function show_popular_items ()
{
  global $HTML;
  $votes = show_votes ();
  if (!$votes)
    return '';
  return "<br />\n" . $HTML->box_top (_("Most Popular Items"), '', 1)
    . $votes . $HTML->box_bottom (1);
}

function show_group_type ($type, $groupdata)
{
  global $HTML, $j, $sys_home;
  # TRANSLATORS: the argument is group type like Official GNU software
  # or www.gnu.org translation teams; for the full list, check
  # frontend/site-specific/gnu/admin/groupedit_grouptype.php.
  $lname = gettext ($type['name']);
  $ret = "<br />\n";
  $ret .= $HTML->box_top (sprintf (_("Newest %s"), $lname), '', 1);
  $ret .= $groupdata;
  $ret .= show_altrow ($j) . '<a href="'
    . "{$sys_home}search/?type_of_search=soft&amp;words=%%%&amp;type="
    . $type['type_id'] . '">[';
  # TRANSLATORS: the argument is group type like Official GNU software
  # or www.gnu.org translation teams.
  $ret .= sprintf (_("all %s"), $lname) . "]</a></span></div>\n";
  $ret .= $HTML->box_bottom (1);
  return $ret;
}

function show_group_type_stats ()
{
  $ret = '';
  $types = fetch_group_types ();
  foreach ($types as $eachtype)
    {
      $groupdata = show_newest_groups ($eachtype['type_id']);
      if (!$groupdata)
        continue;
      $ret .= show_group_type ($eachtype, $groupdata);
    }
  return $ret;
}

function show_features_boxes ()
{
  return show_general_stats ()
    . show_help_wanted ()
    . show_popular_items ()
    . show_group_type_stats ();
}

function show_user_stats ()
{
  $ret = '<span class="smaller">';
  $users = stats_getusers ();
  $ret .= sprintf (
    ngettext ("%s registered user", "%s registered users", $users),
    "<b>$users</b>"
  );
  $ret .= "</span></div>\n";
  return $ret;
}

function show_active_groups ()
{
  $groups = stats_get_active_groups ();
  $ret = sprintf (
    ngettext ("%s hosted group", "%s hosted groups", $groups),
    "<strong>$groups</strong>"
  );
  $ret .= "</span></div>\n";
  return $ret;
}

function show_active_groups_per_type ($eachtype)
{
  global $sys_home;
  $n = stats_get_active_groups ($eachtype['type_id']);
  if ($n < 1)
    return null;
  $ret = "&nbsp;&nbsp;- <a href=\"{$sys_home}search/"
    . '?type_of_search=soft&amp;words=%%%&amp;type='
    . $eachtype['type_id'] . '" class="center">';
  $ret .= ' ' . gettext ($eachtype['name']) . ": $n</a></span></div>\n";
  return $ret;
}

function show_pending_groups ()
{
  $pending = stats_get_pending_groups ();
  $msg = ngettext (
   "+ %s registration pending", "+ %s registrations pending", $pending
  );
  $ret = sprintf ($msg, $pending);
  return $ret . '</span>';
}

function show_sitestats ()
{
  $i = 0;
  $ret = show_user_stats ();
  $ret .= show_altrow ($i++);
  $ret .= show_active_groups ();
  $result = db_execute ("SELECT type_id, name FROM group_type ORDER BY name");
  while ($eachtype = db_fetch_array ($result))
    {
      $groups = show_active_groups_per_type ($eachtype);
      if ($groups === null)
        continue;
      $ret .= show_altrow ($i++) . $groups;
    }
  $ret .= show_altrow ($i++) . '&nbsp;&nbsp;';
  $ret .= show_pending_groups ();
  return $ret;
}

function fetch_group_types ()
{
  $result = db_execute ("SELECT type_id, name FROM group_type ORDER BY name");
  $ret = [];
  while ($row = db_fetch_array ($result))
    $ret[] = $row;
  return $ret;
}

function fetch_newest_groups ()
{
  static $ret = null;
  if ($ret !== null)
    return $ret;
  $limit = 5;
  $result = db_execute ("
    SELECT group_id, type, unix_group_name, group_name, register_time FROM groups
    WHERE is_public = 1 AND status = 'A' AND register_time >= ?
    ORDER BY register_time DESC", [time () - 2 * 30 * 24 * 3600]
  );
  $ret = [];
  while ($row = db_fetch_array ($result))
    {
      $type = $row['type'];
      if (empty ($ret[$type]))
        $ret[$type] = [];
      if (count($ret[$type]) <= $limit)
        $ret[$type][] = $row;
    }
  return $ret;
}

function fetch_base_hosts ()
{
  static $ret = null;
  if ($ret !== null)
    return $ret;
  $result = db_execute ("SELECT type_id, base_host FROM group_type");
  $ret = [];
  while ($row = db_fetch_array ($result))
    $ret[$row['type_id']] = $row['base_host'];
  return $ret;
}

# Show groups that were added less than 2 months ago.
function show_newest_groups ($group_type)
{
  global $j, $sys_home;
  $newest_groups = fetch_newest_groups ();
  if (empty ($newest_groups[$group_type]))
    return false;
  $base_hosts = fetch_base_hosts ();
  $base_url = '';
  if ($base_hosts[$group_type])
    $base_url = session_protocol () . '://' . $base_hosts[$group_type];
  $ret = '';
  foreach ($newest_groups[$group_type] as $row)
    if ($row['register_time'])
      $ret .= show_altrow ($j++) . '&nbsp;&nbsp;- <a href="'
        . "$base_url{$sys_home}projects/$row[unix_group_name]/\">"
        . $row['group_name'] . '</a>, '
        . utils_format_date ($row['register_time'], 'minimal')
        . '</span></div>';
  return $ret;
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
