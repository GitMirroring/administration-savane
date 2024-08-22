<?php
# Show hidden or visible list of items depending on user prefs.
# Set prefs if a change was asked.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2001, 2002 Laurent Julliard, CodeX Team, Xerox
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
require_once (dirname (__FILE__) . '/../utils.php');

function my_hide_link ($role, $group_id, $text, $hide)
{
  global $php_self;
  # Determine the relevant content (title with a + or a -).
  $mp = '-'; $role_val = '0';
  if ($hide)
    {
      $mp = '+'; $role_val = '1';
    }
  return "<a name=\"$role$group_id\" href=\"$php_self"
    . "?hide_$role=$role_val&amp;hide_group_id=$group_id#"
    . "$role$group_id\"><span class='minusorplus'>($mp)</span>$text</a>";
}

#  Function that generates hide and show URLs to expand and collapse
#  sections of the personal page.
#
# Input:
#  $hide = hide param as given in the script URL (-1 means no param was given)
#
# Output:
#  $hide_url: URL to use in the page to switch from hide to show or vice versa
#  $count_diff: difference between the number of items in the list between now
#    and the previous last time the section was open (can be negative if items
#    were removed).
#  $hide_flag: true if the section must be hidden, false otherwise.
function my_hide_url ($role, $group_id, $count, $link = "")
{
  # Determine if we should hide or not.
  $hide = my_is_hidden ($role, $group_id);

  # Compare with preferences, update preference if not equal.
  $pref_name = "my_hide_$role$group_id";
  $old_pref_value = user_get_preference ($pref_name);
  $old_count = 0;
  $arr = explode ('|', $old_pref_value);
  if (!empty ($arr[1]))
    $old_count = $arr[1];
  $pref_value = "$hide|$count";
  if ($old_pref_value != $pref_value)
    user_set_preference ($pref_name, $pref_value);

  $hide_url = my_hide_link ($role, $group_id, $link, $hide);
  return [$hide, $count - $old_count, $hide_url];
}

# Determine whether a given group items of a given role should be hidden or not.
function my_is_hidden ($role, $group_id)
{
  # Extract user prefs.
  # No pref? Then assume we do not want to hide.
  $old_hide = 0;
  $pref_name = "my_hide_$role$group_id";
  $old_pref_value = user_get_preference ($pref_name);
  if ($old_pref_value)
    list ($old_hide,) = explode ('|', $old_pref_value);

  # Extract URL arguments.
  $args = sane_import ('get',
    [
      "digits" => ["hide_group_id", ["hide_$role", [0, 1]]]
    ]);
  $asked_to_hide_group = $args["hide_group_id"];
  $asked_to_hide_role = isset ($args["hide_$role"]);

  # The user asked to change something for this role and this group,
  # return exactly what was asked for.
  if ($asked_to_hide_group == $group_id && $asked_to_hide_role)
    return $args["hide_$role"];

  # No related change, return the pref.
  return $old_hide;
}

function my_item_count ($total, $new)
{
  return ' '
    . sprintf (_('(new items: %1$s, total: %2$s)'), $total, $new) . "\n";
}

# Function that expect item_data and $group_data to exist as globals,
# so we can avoid doing hundred of time the same SQL requests.
function my_item_list (
  $role = "assignee", $threshold = "5", $openclosed = "open", $uid = 0,
  $condensed = 0
)
{
  global $item_data, $group_data, $items_per_groups, $maybe_missed_rows;

  $items_per_groups = [];
  $maybe_missed_rows = 0;

  foreach (array_diff (utils_get_tracker_list (), ['cookbook']) as $tracker)
    {
      # Create the SQL request.
      $sql_result = my_item_list_buildsql (
        $tracker, $role, $threshold, $openclosed, $uid
      );

      # Ignore if not able to produce a SQL (maybe because the user
      # have no relevant rights, whatever).
      if (!$sql_result)
        continue;

      # Feed the hashes that contains data.
      my_item_list_extractdata ($sql_result, $tracker);
    }
  my_item_list_print ($role, $openclosed, $condensed);
}


# Build sql request depending on what we are looking for.
function my_item_list_buildsql (
  $tracker, $role = "assignee", $threshold = "5", $openclosed = "open",
  $uid = false
)
{
  global $item_data, $group_data, $sql_limit, $usergroups, $usergroups_groupid;
  global $items_per_groups, $usersquads;

  if (!ctype_alnum (strval ($tracker)))
    util_die (_("Invalid tracker name:") . " $tracker");

  # status: 1 = open, 3 = closed
  if ($openclosed == "open")
    $openclosed = 1;
  if ($openclosed == "closed")
    $openclosed = 3;

  # Max items: defines to 50 by default
  # (meaning 50 x trackers for each list = 200 items).
  # This is important to save CPU resources.
  # This variable is set as global to able to afterwards check if we hit
  # max results or not.
  $sql_limit = 50;

  # threshold: based on priority
  # by default, consider we are printing items of the current user
  # if not, we want to ignore private items.
  $showprivate = '';
  if (!$uid)
    $uid = user_getid ();
  else
    $showprivate = ' AND privacy <> 2 ';
  # Get a timestamp to get new items (15 days).
  $new_date_limit = time () - 15 * 24 * 3600;

  $select = "
    SELECT
      t.bug_id, t.date, t.priority, t.resolution_id, t.summary,
      g.group_id, g.group_name, g.unix_group_name";
  $from = "FROM $tracker t, groups g ";
  $select_params = $from_params = [];
  # FIXME: should we put a SQL LIMIT, to avoid cases of users that would
  # have tons of items, with a meaningful error message?
  if ($role == "assignee" || $role == "submitter")
    {
      # Items listing in My Items: assigned to and posted by.
      $where = 'WHERE g.group_id = t.group_id ';

      # If we are dealing with tasks, check if the group has task
      # tracker enabled.
      if ($tracker == "task")
        $where .= 'AND g.use_task = 1 ';

      $where .= 'AND t.status_id = ? AND (t.priority >= ? OR  t.date > ?) '
        . $showprivate;
      $where_params = [$openclosed, $threshold, $new_date_limit];

      if ($role == "assignee")
        {
          $where .= 'AND (t.assigned_to = ?';
          $where_params[] = $uid;

          # If the user is member of squads, add them now.
          reset ($usersquads);
          foreach ($usersquads as $squad_id)
            {
              $where .= ' OR t.assigned_to = ?';
              $where_params[] = $squad_id;
            }
          $where .= ') ';
        }
      else
        {
          # If the submitter is also the owner, we'll show it in the assigned
          # list, which matters more than submitting.
          $where .= 'AND t.assigned_to <> ? AND t.submitted_by = ? ';
          $where_params[] = $uid;
          $where_params[] = $uid;
        }

      # 1. Restrict to groups the users belongs to.
      # 2. Do a simple SQL count if the group is supposed to be hidden.
      $restrict_to_groups = '';
      $restrict_to_groups_params = [];
      foreach ($usergroups_groupid as $current_group_id)
        {
          if (my_is_hidden ($role, $current_group_id))
            {
              # No restriction if we are not listing the items of the logged
              # in user: we are not in page where items can be hidden.
              if ($uid != user_getid ())
                continue;

              # This group is supposed to be hidden, just do a count; do it
              # now.
              $res = db_execute ("
                SELECT count(t.bug_id) AS count $from
                $where AND t.group_id = ? GROUP BY bug_id LIMIT ?",
                array_merge (
                  $from_params, $where_params, [$current_group_id, $sql_limit]
                )
              );
              $rows = db_numrows ($res);
              # Feed the array so it knows exactly how many items we have
              # (array_fill exists only in PHP 4.2).
              for ($k = 0; $k < $rows; $k++)
                $items_per_groups[$current_group_id][] = true;

              # When we look for items the user submitted, we do not restrict
              # groups, if this one is supposed to be hidden, we have to
              # explicitly ignores it.
              if ($role == "submitter")
                {
                  if ($restrict_to_groups)
                    $restrict_to_groups .= ' AND ';
                  $restrict_to_groups .= ' t.group_id <> ? ';
                  $restrict_to_groups_params[] = $current_group_id;
                }
              continue;
            } # if (my_is_hidden ($role, $current_group_id))
          # When we look for items the user submitted,
          # we do not restrict groups.
          if ($role == "submitter")
            continue;

          if ($restrict_to_groups)
            $restrict_to_groups .= ' OR ';
          # Group is not supposed to be hidden.
          $restrict_to_groups .= ' t.group_id = ? ';
          $restrict_to_groups_params[] = $current_group_id;
        }

      # No SQL if not at least one group is not in hidden mode.
      if (!$restrict_to_groups && $role == "assignee")
        return false;

    } # if ($role == "assignee" || $role == "submitter")
  else
    {
      # Items listing in My Incoming Items:
      #   recent unassigned items or recently assigned items.
      if ($role == "unassigned")
        {
          $where = '
            WHERE
              g.group_id = t.group_id AND t.status_id = 1 AND t.date > ?
              AND t.assigned_to = 100 ';
          $where_params = [$new_date_limit];
        }
      elseif ($role == "newlyassigned")
        {
          # Incoming assigned items is a bit complex: we want newly assigned
          # item that are in fact completely new items, with no history,
          # and assigned item that may be very very old but
          # that were assigned recently to the user.
          $where = '
            WHERE
              g.group_id = t.group_id AND t.status_id = ?
              AND (t.assigned_to = ?';
          $where_params = [$openclosed, $uid];

          # If the user is member of squads, add them now.
          reset ($usersquads);
          foreach ($usersquads as $squad_id)
            {
              $where .= ' OR t.assigned_to = ?';
              $where_params[] = $squad_id;
            }

          $where .= ') AND (t.date > ? AND t.submitted_by <> ?) ';
          $where_params[] = $new_date_limit;
          $where_params[] = $uid;
        }

      # Go thru the list of groups the user belongs to
      # to find out if any is relevant.
      $restrict_to_groups = NULL;
      foreach ($usergroups_groupid as $current_group_id)
        {
          if ($role == "unassigned")
            {
              # For unassigned items, we must ignore all trackers the user
              # is not a manager of.
              $flag = member_create_tracker_flag ($tracker) . '3';
              if (!member_check (0, $current_group_id, $flag))
                continue;
            }

          if (my_is_hidden ($role, $current_group_id))
            {
              # This group is supposed to be hidden, just do a count; do it
              # now.
              $res = db_execute ("
                SELECT
                  count(t.bug_id) AS count $from
                  $where AND t.group_id = ? GROUP BY bug_id LIMIT ?",
                array_merge ($from_params, $where_params,
                            [$current_group_id, $sql_limit])
              );
              $rows = db_numrows ($res);
              # Feed the array so it nows exactly how many items we have
              # (array_fill exists only in PHP 4.2).
              for ($k = 0; $k < $rows; $k++)
                $items_per_groups[$current_group_id][] = true;
              continue;
            }
          # This group will be shown.
          if ($restrict_to_groups)
            $restrict_to_groups .= "OR ";

          $restrict_to_groups .= " t.group_id = ? ";
          $restrict_to_groups_params[] = $current_group_id;
        }
      # No SQL if not at least one group is not in hidden mode.
      if (!$restrict_to_groups)
        return false;
    } # ! ($role == "assignee" || $role == "submitter")

  if ($restrict_to_groups)
    $restrict_to_groups = "AND ($restrict_to_groups)";
  $sql = "$select $from $where $restrict_to_groups
    GROUP BY bug_id ORDER BY t.date DESC ";
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

function my_get_item_status_field_value ($group_id, $item_status, $tracker)
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

# Extract items data from database, put in hashes.
function my_item_list_extractdata ($sql_result, $tracker)
{
  global $sql_limit, $maybe_missed_rows;

  $rows = db_numrows ($sql_result);
  # Record for later if we maybe missed items.
  if ($sql_limit <= $rows)
    $maybe_missed_rows = 1;

  while ($row = db_fetch_array ($sql_result))
    my_assign_item ($row, $tracker);
  return $rows;
}

# Print a list of data from what was in the hash.
function my_item_list_print (
  $role = "assignee", $openclosed = "open", $condensed = false
)
{
  global $item_data, $group_data, $items_per_groups, $maybe_missed_rows;
  global $sys_home;

  if ($openclosed == "closed")
    $openclosed = 3;

  # Break here if we have no results.
  if (count ($items_per_groups) < 1)
    {
      print _("None found");
      return false;
    }

  # If when doing the SQL, we found as many result as possible with the
  # SQL limits, we may have missed others items because they are too many.
  if ($maybe_missed_rows)
    {
      print '<div class="boxitem"><span class="xsmall"><span class="warn">'
        . _("We found many items that match the current criteria. We had "
            . "to set a limit\nat some point, some items that match "
            . "the criteria may be missing for this\nlist.")
        . "</span></span></div>\n";
      if (!$condensed)
        print "<br />\n";
    }

  # Go through the group list.
  ksort ($items_per_groups);
  $hide_now = false;

  reset ($items_per_groups);
  foreach ($items_per_groups as $current_group_id => $current_group_items)
    {
      $idx = "group$current_group_id";
      # Obtain the group fullname.
      if (!isset ($group_data[$idx]))
        $group_data[$idx] = group_getname ($current_group_id);

      # Print subtitle.
      print '<div class="' . utils_altrow (1) . '"> ';
      if ($condensed)
        # In condensed mode, there is no hide URL.
        print $group_data[$idx] . ": ";
      else
        {
          $count = count ($current_group_items);
          list ($hide_now, $count_diff, $hide_url) =
            my_hide_url ($role, $current_group_id, $count,
              '<b>' . $group_data[$idx] . '</b>'
            );
          print $hide_url . ' <span class="smaller">'
            . my_item_count ($count, max (0, $count_diff)) . "</span>";
        }
      print "</div>\n";

      # Go through the item list, unless asked to hide.
      if (!$hide_now)
        {
          krsort ($current_group_items);
          reset ($current_group_items);
          foreach ($current_group_items as $thisitem => $thisvalue)
            {
              if (!isset ($item_data[$thisitem]))
                continue;

              $it = $item_data[$thisitem];
              $it_id = $it['item_id']; $tracker = $it['tracker'];
              $prefix = utils_get_tracker_prefix ($tracker);
              $icon = utils_get_tracker_icon ($tracker);

              # Found out the status full text name:
              # this is group-specific. If there is no group setup for this
              # then go to the default for the site
              $item_status = $it['status'];
              $idx = "$current_group_id$tracker$item_status";
              if (!array_key_exists ($idx, $group_data))
                $group_data[$idx] = my_get_item_status_field_value (
                  $current_group_id,  $item_status, $tracker
                );
              $status = $group_data[$idx];

              # Print directly, to avoid putting too much things in memory
              print '<div class="'
                . utils_get_priority_color ($it['priority'], $openclosed)
                . '">'
                . "<a href=\"$sys_home$tracker/?$it_id\" class='block'>"
                . html_image ("contexts/$icon.png",
                    ['class' => 'icon', 'alt' => $tracker]
                  )
                . $it['summary'] . "&nbsp;<span class='xsmall'>"
                . "($prefix #$it_id, $status)</span></a></div>\n";
            }
        }
      # Add extra space to make the page easier to read.
      if (!$condensed)
        print "<br />\n";
    }
}
?>
