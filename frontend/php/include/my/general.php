<?php
# Show hidden or visible list of items depending on user prefs.
# Set prefs if a change was asked.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2001, 2002 Laurent Julliard, CodeX Team, Xerox
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
require_once (dirname (__FILE__) . '/../utils.php');

function my_hide_link ($role, $group_id, $text, $hide)
{
  global $php_self;
  $mp = '-'; $role_val = 1;
  if ($hide)
    {
      $mp = '+'; $role_val = 0;
    }
  return "<a id=\"$role$group_id\" href=\"$php_self"
    . "?hide_$role=$role_val&amp;hide_group_id=$group_id#"
    . "$role$group_id\"><span class='minusorplus'>($mp)</span>$text</a>";
}

function my_hide_pref_name ($role, $group_id)
{
  return "my_hide_$role$group_id";
}

function my_get_hide_pref ($role, $group_id)
{
  return user_get_preference (my_hide_pref_name ($role, $group_id));
}

function my_set_hide_pref ($role, $group_id, $val)
{
  user_set_preference (my_hide_pref_name ($role, $group_id), $val);
}

# Generate URLs to expand and collapse sections of the personal page.
#
# Output:
#  $hide_flag: true if the section must be hidden, false otherwise.
#  $count_diff: difference between the number of items in the list between now
#    and the previous last time the section was open (can be negative if items
#    were removed).
#  $hide_url: URL to use in the page to switch from hide to show or vice versa
function my_hide_url ($role, $group_id, $count, $link)
{
  # Determine if we should hide or not.
  $hide = my_is_hidden ($role, $group_id);

  # Compare with preferences, update preference if not equal.
  $old_pref_value = my_get_hide_pref ($role, $group_id);
  $arr = explode ('|', $old_pref_value);
  $pref_value = "$hide|$count";
  if ($old_pref_value != $pref_value)
    my_set_hide_pref ($role, $group_id, $pref_value);
  $hide_url = my_hide_link ($role, $group_id, $link, $hide);
  return [$hide, $hide_url];
}

# Determine whether a given group items of a given role should be hidden or not.
function my_is_hidden ($role, $group_id)
{
  if ($GLOBALS['my_never_hide_items'])
    return 0;
  $args = sane_import ('get',
    ["digits" => ["hide_group_id", ["hide_$role", [0, 1]]]]
  );
  $asked_to_hide_group = $args["hide_group_id"];
  $asked_to_hide_role = isset ($args["hide_$role"]);

  # The user asked to change something for this role and this group,
  # return exactly what was asked for.
  if ($asked_to_hide_group == $group_id && $asked_to_hide_role)
    return $args["hide_$role"];

  # No related change, return the pref.
  $old_pref_value = my_get_hide_pref ($role, $group_id);
  if (!$old_pref_value)
    return 0; # No pref? Then assume we do not want to hide.
  list ($old_hide, ) = explode ('|', $old_pref_value);
  return $old_hide;
}

function my_item_count ($total)
{
  return sprintf (ngettext ("(%s item)", "(%s items)", $total), $total) . "\n";
}

# Function that expect item_data and $group_data to exist as globals,
# so we can avoid doing hundred of time the same SQL requests.
function my_item_list (
  $role, $threshold = "5", $openclosed = "open", $uid = 0, $condensed = 0
)
{
  global $item_data, $group_data, $items_per_groups, $maybe_missed_rows;
  $GLOBALS['my_never_hide_items'] = $condensed;

  $items_per_groups = []; $maybe_missed_rows = 0;
  $openclosed =  $openclosed == "open"? 1: 3; # Status: 1 = open, 3 = closed.

  foreach (utils_get_dependable_trackers () as $tracker)
    {
      # Create the SQL request.
      $sql_result = my_item_list_buildsql (
        $tracker, $role, $threshold, $openclosed, $uid
      );
      my_item_list_extractdata ($sql_result, $tracker);
    }
  ksort ($items_per_groups);
  reset ($items_per_groups);
  my_item_list_print ($role, $openclosed, $condensed);
}

function my_item_list_assigned_arg ($role, $uid)
{
  if (!in_array ($role, ['assignee', 'newlyassigned']))
    return [[$uid, $uid], 't.assigned_to <> ? AND t.submitted_by = ?'];
  $arg = $GLOBALS['usersquads'];
  array_unshift ($arg, $uid);
  return [$arg, "t.assigned_to " . utils_in_placeholders ($arg)];
}

function my_fill_items_per_groups_entry ($from, $where, $params, $gid)
{
  global $items_per_groups;
  array_pop ($params);
  $res = db_execute (
    "SELECT count(t.bug_id) AS cnt $from $where AND t.group_id = ?", $params
  );
  if (!array_key_exists ($gid, $items_per_groups))
    $items_per_groups[$gid] = [];
  while ($row = db_fetch_array ($res))
    for ($i = 0; $i < $row['cnt']; $i++)
      $items_per_groups[$gid][] = true;
}

function my_group_condition ($groups, $role)
{
  $ret = '';
  if (!empty ($groups))
    $ret = 't.group_id ' . utils_in_placeholders ($groups);
  if ($ret && $role == 'submitter')
    $ret = "NOT $ret";
  if ($ret)
    $ret = "AND ($ret)";
  return $ret;
}

# Run SQL request depending on what we are looking for.
function my_item_list_buildsql ($tracker, $role, $threshold, $openclosed, $uid)
{
  global $item_data, $group_data, $sql_limit, $usergroups, $usergroups_groupid;
  global $items_per_groups, $usersquads;

  if (!ctype_alnum (strval ($tracker)))
    util_die (_("Invalid tracker name:") . " $tracker");

  # Max items: defines to 50 by default
  # (meaning 50 x trackers for each list = 200 items).
  # This is important to save CPU resources.
  # This variable is set as global to able to afterwards check if we hit
  # max results or not.
  $sql_limit = 50;

  # threshold: based on priority
  # by default, consider we are printing items of the current user
  # if not, we want to ignore private items.
  $no_private = '';
  if (!$uid)
    $uid = user_getid ();
  else
    $no_private = ' AND privacy <> 2 ';
  # Get a timestamp to get new items (15 days).
  $new_date_limit = time () - 15 * 24 * 3600;

  $select = "
    SELECT
      t.bug_id, t.date, t.priority, t.resolution_id, t.summary,
      g.group_id, g.group_name, g.unix_group_name";
  $from = "FROM $tracker t JOIN groups g ON t.group_id = g.group_id ";
  $select_params = $from_params = [];
  if ($role == "assignee" || $role == "submitter")
    {
      # Items listing in My Items: assigned to and posted by.
      $where = 'WHERE t.status_id = ? AND (t.priority >= ? OR t.date > ?) '
        . $no_private;
      $where_params = [$openclosed, $threshold, $new_date_limit];

      # If we are dealing with tasks, check if the group has task
      # tracker enabled.
      if ($tracker == "task")
        $where .= 'AND g.use_task = 1 ';

      list ($par, $wh) = my_item_list_assigned_arg ($role, $uid);
      $where .= "AND ($wh) ";
      $where_params = array_merge ($where_params, $par);

      # 1. Restrict to groups the users belongs to.
      # 2. Do a simple SQL count if the group is supposed to be hidden.
      $restrict_to_groups_params = [];
      foreach ($usergroups_groupid as $gid)
        {
          if (my_is_hidden ($role, $gid))
            {
              # No restriction if we are not listing the items of the logged
              # in user: we are not in page where items can be hidden.
              if ($uid != user_getid ())
                continue;

              $params = array_merge (
                $from_params, $where_params, [$gid, $sql_limit]
              );
              my_fill_items_per_groups_entry ($from, $where, $params, $gid);

              # When we look for items the user submitted, we do not restrict
              # groups, if this one is supposed to be hidden, we have to
              # explicitly ignore it.
              if ($role == "submitter")
                $restrict_to_groups_params[] = $gid;
              continue;
            } # if (my_is_hidden ($role, $gid))
          if ($role == "submitter")
            continue;
          $restrict_to_groups_params[] = $gid;
        }
    } # if ($role == "assignee" || $role == "submitter")
  else
    {
      # Items listing in My incoming items:
      # recent unassigned items or recently assigned items.
      if ($role == "unassigned")
        {
          $where = '
            WHERE t.status_id = 1 AND t.date > ?  AND t.assigned_to = 100 ';
          $where_params = [$new_date_limit];
        }
      elseif ($role == "newlyassigned")
        {
          # Incoming assigned items is a bit complex: we want newly assigned
          # item that are in fact completely new items, with no history,
          # and assigned item that may be very very old but
          # that were assigned recently to the user.
          $where = "
            WHERE
              g.group_id = t.group_id AND t.status_id = ?\n    ";
          $where_params = [$openclosed];
          list ($par, $wh) = my_item_list_assigned_arg ($role, $uid);
          $where .= "AND ($wh)\n    ";
          $where_params = array_merge ($where_params, $par);
          $where .= 'AND (t.date > ? AND t.submitted_by <> ?) ';
          $where_params[] = $new_date_limit;
          $where_params[] = $uid;
        }

      # Go through the list of groups the user belongs to
      # to find out if any is relevant.
      foreach ($usergroups_groupid as $gid)
        {
          if ($role == "unassigned")
            {
              # For unassigned items, we must ignore all trackers the user
              # is not a manager of.
              $flag = member_create_tracker_flag ($tracker) . '3';
              if (!member_check (0, $gid, $flag))
                continue;
            }

          if (my_is_hidden ($role, $gid))
            {
              $params = array_merge (
                $from_params, $where_params, [$gid, $sql_limit]
              );
              my_fill_items_per_groups_entry ($from, $where, $params, $gid);
              continue;
            }
          # This group will be shown.
          $restrict_to_groups_params[] = $gid;
        }
    } # ! ($role == "assignee" || $role == "submitter")

  # No SQL if not at least one group is not in hidden mode.
  if (empty ($restrict_to_groups_params) && $role != 'submitter')
    return false;
  $group_cond = my_group_condition ($restrict_to_groups_params, $role);
  $sql = "$select\n$from\n$where\n$group_cond ORDER BY t.date DESC";
  $sql_params = array_merge (
    $select_params, $from_params, $where_params, $restrict_to_groups_params
  );
  $sql .= " LIMIT ?";
  $sql_params[] = $sql_limit;
  return db_execute ($sql, $sql_params);
}

function my_assign_item ($row, $tracker)
{
  global $item_data, $items_per_groups;
  # Create unique item name beginning with the date to ease sorting.
  $item = $row['date'] . ".$tracker#" . $row['bug_id'];
  $group = $row['group_id'];

  # Associate to the group (ignore if it was already done).
  if (array_key_exists ($group, $items_per_groups)
      && is_array ($items_per_groups[$group])
      && array_key_exists ($item, $items_per_groups[$group]))
    return;
  $items_per_groups[$group][$item] = true;

  # Store data (ignore if already found).
  if (is_array ($item_data) && array_key_exists ($item, $item_data))
    return;
  $row['tracker'] = $tracker; $row['item_id'] = $row['bug_id'];
  $row['status'] = $row['resolution_id'];
  foreach (['item_id', 'tracker', 'date', 'priority', 'status', 'summary']
    as $key)
    $item_data[$item][$key] = $row[$key];
}

function my_fetch_item_status_fv ($group_id, $item_status, $tracker)
{
  $params = [GROUP_NONE, $group_id, $item_status];
  $res = db_execute ("
    SELECT group_id, value FROM {$tracker}_field_value
    WHERE bug_field_id = '108' AND group_id IN (?, ?) AND value_id = ?",
    $params
  );
  array_pop ($params);
  foreach ($params as $p)
    {
      db_data_seek ($res);
      while ($row = db_fetch_array ($res))
        if ($row['group_id'] == $p)
          $ret = $row['value'];
    }
  return $ret;
}

# Find out the status full text name: this is group-specific.
# If there is no group setup for this, then go to the default for the site.
function my_get_item_status_field_value ($gid, $item_status, $tracker)
{
  global $group_data;
  $idx = "$gid$tracker$item_status";
  if (is_array ($group_data) && array_key_exists ($idx, $group_data))
    return $group_data[$idx];
  $group_data[$idx] = my_fetch_item_status_fv ($gid,  $item_status, $tracker);
  return $group_data[$idx];
}

function my_item_group_header ($gid, $idx, $condensed, $role)
{
  global $group_data, $items_per_groups;
  $hide_now = false;
  if (!isset ($group_data[$idx]))
    $group_data[$idx] = group_getname ($gid);

  print '<div class="' . utils_altrow (1) . '"> ';
  if ($condensed)
    # In condensed mode, there is no hide URL.
    print $group_data[$idx] . ": ";
  else
    {
      $count = count ($items_per_groups[$gid]);
      list ($hide_now, $hide_url) =
        my_hide_url ($role, $gid, $count, '<b>' . $group_data[$idx] . '</b>');
      print $hide_url . ' <span class="smaller">'
        . my_item_count ($count) . "</span>";
    }
  print "</div>\n";
  return $hide_now;
}

# Extract items data from database, put in hashes.
function my_item_list_extractdata ($sql_result, $tracker)
{
  global $sql_limit, $maybe_missed_rows;
  if (!$sql_result)
    return;

  $rows = db_numrows ($sql_result);
  # Record for later if we maybe missed items.
  if ($sql_limit <= $rows)
    $maybe_missed_rows = 1;

  while ($row = db_fetch_array ($sql_result))
    my_assign_item ($row, $tracker);
}

function my_item_print_item ($item, $gid, $openclosed)
{
  global $group_data, $sys_home, $item_data;
  if (!isset ($item_data[$item]))
    return;
  $it = $item_data[$item]; $it_id = $it['item_id']; $tracker = $it['tracker'];
  $prefix = utils_get_tracker_prefix ($tracker);
  $icon = utils_get_tracker_icon ($tracker);

  # Find out the status full text name: this is group-specific.
  # If there is no group setup for this, then go to the default for the site.
  $status = my_get_item_status_field_value ($gid, $it['status'], $tracker);

  # Print directly, to avoid putting too much things in memory
  print '<div class="'
    . utils_get_priority_color ($it['priority'], $openclosed) . '">'
    . "<a href=\"$sys_home$tracker/?$it_id\" class='block'>"
    . html_image ("contexts/$icon.png",
        ['class' => 'icon', 'alt' => $tracker]
      )
    . $it['summary'] . "&nbsp;<span class='xsmall'>"
    . "($prefix #$it_id, $status)</span></a></div>\n";
}

function my_item_list_header ($condensed)
{
  global $items_per_groups, $maybe_missed_rows;
  # Break here if we have no results.
  if (count ($items_per_groups) < 1)
    {
      print _("None found");
      return true;
    }

  # If when doing the SQL, we found as many result as possible with the
  # SQL limits, we may have missed others items because they are too many.
  if (!$maybe_missed_rows)
    return false;
  print '<div class="boxitem"><span class="xsmall"><span class="warn">'
    . _("We found many items that match the current criteria. We had "
        . "to set a limit\nat some point, some items that match "
        . "the criteria may be missing for this\nlist.")
    . "</span></span></div>\n";
  if (!$condensed)
    print "<br />\n";
  return false;
}

# Print a list of data from what was in the hash.
function my_item_list_print ($role, $openclosed, $condensed)
{
  global $item_data, $items_per_groups;
  if (my_item_list_header ($condensed))
    return false;

  foreach ($items_per_groups as $gid => $group_items)
    {
      $hide_now = my_item_group_header ($gid, "group$gid", $condensed, $role);
      # Go through the item list unless asked to hide.
      if (!$hide_now)
        {
          krsort ($group_items);
          reset ($group_items);
          foreach (array_keys ($group_items) as $thisitem)
            my_item_print_item ($thisitem, $gid, $openclosed);
        }
      if (!$condensed) # Add extra space to make the page easier to read.
        print "<br />\n";
    }
}
?>
