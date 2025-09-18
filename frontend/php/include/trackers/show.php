<?php
# Show items.
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

require_once (dirname (__FILE__) . '/cookbook.php');
require_once (dirname (__FILE__) . '/../utils.php');

function show_item_field_try_date ($field, $value)
{
  if (!trackers_data_is_date_field ($field))
    return null;
  if (!$value)
    return "align=\"middle\">-";
  $highlight_date = '';
  if ($field == 'planned_close_date' && $value < time ())
    $highlight_date = ' class="highlight"';
  return "$highlight_date>" . utils_format_date ($value, 'natural');
}

function show_item_field_try_username ($field, $value)
{
  if (!trackers_data_is_username_field ($field))
    return null;
  if ($value == 'None')
    $value = '';
  if ($value === '')
    return ">$value";
  return ">" . utils_user_link ($value);
}

function show_item_field_try_bug_id ($field, $value)
{
  if ($field !== 'bug_id')
    return null;
  return "><a href=\"?$value\">#$value</a>";
}

function show_item_field_try_select_box ($field, $value)
{
  global $group_id;
  if (!trackers_data_is_select_box ($field))
    return null;
  $val = trackers_data_get_cached_field_value ($field, $group_id, $value);
  if ($val == 'None')
    $val = '';
  return ">$val";
}

function show_item_field_in_list ($row, $field, $width)
{
  if (empty ($width))
    $width = '';
  else
    $width = " width=\"$width%\"";
  $value = $row[$field];

  foreach (['date', 'bug_id', 'username', 'select_box'] as $f)
    {
      $f = "show_item_field_try_$f";
      $text = $f ($field, $value);
      if ($text === null)
        continue;
      print "<td$width$text</td>\n";
      return;
    }
  print "<td$width><a href=\"?{$row['bug_id']}\">$value</a></td>\n";
}

function show_item_in_list ($row, $fields, $widths, $field_num)
{
  print '<tr class="'
    . utils_get_priority_color ($row["priority"], $row["status_id"])
    . "\">\n";

  for ($j = 0; $j < $field_num; $j++)
    {
      if ($fields[$j] != "digest")
        {
          show_item_field_in_list ($row, $fields[$j], $widths[$j]);
          continue;
        }
      print '<td class="center">'
        . form_checkbox ("items_for_digest[]", 1, ['value' => $row['bug_id']])
        . "</td>\n";
    } # for ($j = 0; $j < $field_num; $j++)
  print "</tr>\n";
}

function show_item_list ($items, $fields, $titles, $widths, $url)
{
  $links = [];
  foreach ($fields as $field)
    $links[] = "$url&amp;order=$field#results";
  print html_build_list_table_top ($titles, $links);

  $field_num = count ($fields);
  foreach ($items as $row)
    show_item_in_list ($row, $fields, $widths, $field_num);
  print "</table>\n";
}

function show_history_previous_value ($field, $group_id, $value_id)
{
  if (trackers_data_is_select_box ($field))
    {
      # Its a select box look for value in clear.
      # (If we hit case of transition automatique update, show it in
      # specific way).
      if ($value_id != "transition-other-field-update")
        {
          print trackers_data_get_value ($field, $group_id, $value_id);
          return;
        }
      print "-" . _("Automatic update due to transitions settings") . "-";
      return;
    }
  if (trackers_data_is_date_field ($field))
    {
      # For date fields do some special processing.
      print utils_format_date ($value_id, 'natural');
      return;
    }
  print markup_basic (utils_specialchars ($value_id));
}

function show_history_current_value ($field, $group_id, $new_value_id)
{
  if (trackers_data_is_select_box ($field))
    print trackers_data_get_value ($field, $group_id, $new_value_id);
  elseif (trackers_data_is_date_field ($field))
    print utils_format_date ($new_value_id, 'natural');
  else
    print markup_basic (utils_specialchars ($new_value_id));
}

function show_history_check_rows ($rows)
{
  if ($rows > 0)
    return false;
  print "\n<span class='warn'>"
    . _("No changes have been made to this item") . '</span>';
  return true;
}

function show_history_title ($rows, $no_limit)
{
  # If no limit is not set, print only 25 latest news items
  # yeupou--gnu.org 2004-09-17: currently we provide no way to get the
  # full history. We will see if users request it.
  if ($no_limit)
    return;

  $title = sprintf (ngettext (
    "Follows %s latest change.", "Follow %s latest changes.", $rows), $rows
  );
  print "\n<p>$title</p>\n";
}

function show_history_top ($rows, $no_limit)
{
  if (show_history_check_rows ($rows))
    return -1;
  show_history_title ($rows, $no_limit);
  if (!$no_limit && $rows > 25)
    $rows = 25;
  print html_build_list_table_top (
    [
      _("Date"), _("Changed by"), _("Updated Field"), _("Previous Value"),
      "=>", _("Replaced by")
    ]
  );
  return $rows;
}

function show_history_date_and_user ($date, $user, $j)
{
  print "\n<tr class=\"" . utils_altrow ($j) . '">';
  print '<td align="center" class="smaller">'
    . utils_format_date ($date, 'natural') . "</td>\n";
  print '<td align="center" class="smaller">'
    . utils_user_link ($user) . "</td>\n";
}

function show_history_values ($field, $group_id, $value_id, $new_value_id)
{
  print '<td class="smaller" align="right">';
  show_history_previous_value ($field, $group_id, $value_id);
  print "</td>\n<td class='smaller' align='center'>"
    . html_image ('arrows/next.png') . "</td>\n"
    . '<td class="smaller" align="left">';
  show_history_current_value ($field, $group_id, $new_value_id);
  print "</td>\n";
}

function show_history_extract_field ($row)
{
  $vars = [
    'field_name' => 'field', 'date' => 'date', 'mod_by' => 'user',
    'old_value' => 'value_id', 'new_value' => 'new_value_id'
  ];
  $ret = [];
  foreach ($vars as $field => $var_name)
    $ret[$var_name] = $row[$field];
  $ret['user'] = user_getname ($ret['user']);
  # If the stored label is "realdetails", it means it is the details
  # field (realdetails is used because someone had the nasty idea to
  # use "details" to mean "comment").
  if ($ret['field'] == "realdetails")
    $ret['field'] = "details";
  $label = trackers_data_get_label ($ret['field']);
  if (!$label)
    $label = $ret['field'];
  $ret['field_label'] = $label;
  return $ret;
}

# Show the changes of the tracker data we have for this item, excluding details.
function show_item_history ($item_id, $group_id, $no_limit = false)
{
  $result = trackers_data_get_history ($item_id);
  $rows = show_history_top (db_numrows ($result), $no_limit);
  if ($rows < 0)
    return;
  $previous_date = $previous_user = null;
  for ($i = $j = 0; ($row = db_fetch_array ($result)) && $i < $rows; $i++)
    {
      extract (show_history_extract_field ($row));
      if ($date == $previous_date && $user == $previous_user)
        print "\n<tr class=\"" . utils_altrow ($j)
          . "\"><td>&nbsp;</td>\n<td>&nbsp;</td>\n";
      else
        show_history_date_and_user ($date, $user, ++$j);
      $previous_date = $date; $previous_user = $user;
      print "<td class='smaller' align='center'>$field_label</td>";
      show_history_values ($field, $group_id, $value_id, $new_value_id);
      print "</tr>\n";
    } # for ($i = $j = 0; ($row = db_fetch_array ($result)) && $i < $rows; $i++)
  print "</table>\n";
}

function show_item_details (
  $item_id, $group_id, $item_assigned_to = false,
  $new_comment = false, $allow_quote = true
)
{
  list ($text, $item_no, $comment_order) = format_details (
    $item_id, $group_id, false, $item_assigned_to, $new_comment, $allow_quote
  );
  if ($item_no < 1)
    return $text;
  $ctl = form_submit (_('Reverse comment order'), 'reverse_bis');
  if ($item_no > 5 && !$comment_order)
    {
      $jumpto_text = _("Jump to the original submission");
      if (ARTIFACT == "cookbook")
        $jumpto_text = _("Jump to the recipe preview");

      $ctl .= " <a href='#comment0'>"
        . html_image ("arrows/bottom.png", ['class' => 'icon'])
        . " $jumpto_text</a>";
    }
  return "<p>$ctl</p>\n$text";
}

function show_item_attached_files ($item_id, $group_id, $public)
{
  print format_item_attached_files ($item_id, $group_id, false, $public);
}

# Look for items that $item_id depends on in all artifact.
function show_item_dependency ($item_id)
{
  return show_dependent_item ($item_id, 1);
}

function show_depend_link_title ($tracker)
{
  $titles = [
    'support' => _('support dependencies'), 'bugs' => _('bug dependencies'),
    'task' => _('task dependencies'), 'patch' => _('patch dependencies')
  ];
  if (array_key_exists ($tracker, $titles))
    return $titles[$tracker];
  return
    # TRANSLATORS: the argument is tracker name, unlocalized
    # (this string is a fallback that should never actually be used).
    sprintf (_("%s dependencies"), $tracker);
}

function show_depend_output_bottom ($item_id, $group_id, $tracker_set)
{
  print $GLOBALS['HTML']->box_bottom (1);
  print '<p class="noprint"><span class="preinput">' . _("Digest:")
    . "</span>\n<br />&nbsp;&nbsp;&nbsp;";
  $artifacts = utils_get_dependable_trackers ();
  $links = [];
  foreach ($artifacts as $tracker)
    {
      if (empty ($tracker_set[$tracker]))
        continue;
      $linktitle = show_depend_link_title ($tracker);
      $links[] .= utils_link (
        $GLOBALS['sys_home'] . "$tracker/?group_id=$group_id"
          . '&amp;func=digestselectfield&amp;dependencies_of_item='
          . $item_id . '&amp;dependencies_of_tracker=' . ARTIFACT,
        $linktitle, 'noprint'
      );
    }
  print join (', ', $links) . ".</p>\n";
}

function show_dependent_art_params ($dependson, $art)
{
  if ($dependson)
    return
      [
        ARTIFACT . '_dependencies', $art,
        "art.bug_id = art_dep.is_dependent_on_item_id AND art_dep.item_id = ?"
      ];
  return
    [
      $art . '_dependencies', ARTIFACT,
      "art.bug_id = art_dep.item_id AND art_dep.is_dependent_on_item_id = ? "
    ];
}

function show_dependent_run_query ($sql_params, $art, $art_dep, $where)
{
  $sql = "
   SELECT
     art.bug_id, art.date, art.summary, art.status_id, art.resolution_id,
     art.group_id, art.priority, art.privacy, art.submitted_by
   FROM $art art, $art_dep art_dep
   WHERE
     $where
     AND art_dep.is_dependent_on_item_id_artifact = ?
   ORDER by art.bug_id";
  return db_execute ($sql, $sql_params);
}

function show_dependent_fetch_items ($result, $art, &$tracker_set, &$items)
{
  $fields = [
    'date', 'summary', 'status_id', 'resolution_id', 'group_id',
    'priority', 'privacy', 'submitted_by'
  ];
  while ($row = db_fetch_array ($result))
    {
      $tracker_set[$art] = 1;
      $key = $row['date'] . ".$art#" . $row['bug_id'];
      $items[$key]['item_id'] = $row['bug_id'];
      $items[$key]['tracker'] = $art;
      foreach ($fields as $k)
        $items[$key][$k] = $row[$k];
    }
}

function show_dependent_get_items ($item_id, $dependson)
{
  # Create hash that will contain every relevant info
  # with keys like $date.$item_id so it will be sorted by date (first)
  # and item_id (second).
  $items = $tracker_set = [];
  foreach (utils_get_dependable_trackers () as $art)
    {
      $sql_params = [$item_id];
      list ($art_dep, $param, $where) =
        show_dependent_art_params ($dependson, $art);
      $sql_params[] = $param;
      $result = show_dependent_run_query ($sql_params, $art, $art_dep, $where);
      show_dependent_fetch_items ($result, $art, $tracker_set, $items);
    } # foreach ($artifacts as $art)
  # Sort the items by key, which contain the date as first field
  # (so order by date).
  ksort ($items);
  return [$items, $tracker_set];
}

function show_dependent_check_for_empty ($tracker_set, $dependson)
{
  if ($dependson)
    $title = _("Depends on the following items");
  else
    $title = _("Items that depend on this one");
  if (!empty ($tracker_set))
    return $title;
  print '<p class="warn">' . sprintf (("%s: %s"), $title, _("None found"))
    . "</p>\n";
  return null;
}

function show_dependent_output_top ($tracker_set, $dependson)
{
  $title = show_dependent_check_for_empty ($tracker_set, $dependson);
  if ($title === null)
    return true;
  print $GLOBALS['HTML']->box_top ($title, '', 1);
  return false;
}

function show_dependent_trash_link ($item_id, $current_item_id, $tracker)
{
  global $php_self;
  return "<span class='trash'><a href=\"$php_self?func=delete_dependency"
    . "&amp;item_id=$item_id&amp;item_depends_on=$current_item_id"
    . "&amp;item_depends_on_artifact=$tracker\">"
    . html_image_trash (
        ['class' => 'icon', 'alt' => _("Delete this dependency")]
      )
    . '</a></span>';
}

function show_dependent_priority_color ($item)
{
  return '<div class=\''
    . utils_get_priority_color ($item['priority'], $item['status_id']) . "'>\n";
}

# Find out the status full text name: this is group-specific.
# If there is no group setup for this, go to the default for the site.
function show_dependent_status_name ($item)
{
  static $names = [];
  $st_key = $item['group_id'] . $item['tracker'] . $item['resolution_id'];
  if (array_key_exists ($st_key, $names))
    return $names[$st_key];
  $res = db_execute ("
    SELECT value FROM {$item['tracker']}_field_value
    WHERE bug_field_id = '108' AND group_id IN (?, 100) AND value_id = ?
    ORDER BY bug_fv_id DESC LIMIT 1",
    [$item['group_id'], $item['resolution_id']]
  );
  $names[$st_key] = db_result ($res, 0, 'value');
  return $names[$st_key];
}

function show_dependent_item_summary ($item, $access)
{
  if ($item['privacy'] == "2" && !$access
      && $item['submitted_by'] != user_getid ())
    print _("---- Private ----");
  else
    print $item['summary'];
}

function show_dependent_from_group ($grp_id, $item_id, $status, $tracker)
{
  global $group_id;
  # Print group info if the item is from another group.
  $from_group = '';
  if ($grp_id != $group_id)
    $from_group = group_getname ($grp_id) . ', ';
  print '&nbsp;<span class="xsmall">(' . utils_get_tracker_prefix ($tracker)
    . " #$item_id, $from_group$status)</span></a>";
}

function show_dependent_single_item ($item_id, $item, $show_trash, &$access)
{
  $cur_item = $item['item_id'];
  $tracker = $item['tracker'];
  $grp_id = $item['group_id'];
  print show_dependent_priority_color ($item);
  if ($show_trash)
    print show_dependent_trash_link ($item_id, $cur_item, $tracker);
  $link_to_item = $GLOBALS['sys_home'] . "$tracker/?$cur_item";
  print "<a href=\"$link_to_item\" class='block'>";
  print html_image (
    'contexts/' . utils_get_tracker_icon ($tracker) . '.png',
     ['class' => 'icon', 'alt' => $tracker]);
  # Print summary only if the item is not private.  Check privacy (don't
  # care about the tracker-specific rights, being group member is enough).
  if (!array_key_exists ($grp_id, $access))
    $access[$grp_id] = member_check (0, $grp_id, 2);
  show_dependent_item_summary ($item, $access[$grp_id]);
  $status = show_dependent_status_name ($item);
  show_dependent_from_group ($grp_id, $cur_item, $status, $tracker);
  print "</div>\n";
}

# Look for items that depend on $item_id in all trackers,
# or look for items that $item_id depends on in all trackers.
function show_dependent_item ($item_id, $dependson = 0)
{
  global $group_id;
  list ($items, $tracker_set) = show_dependent_get_items ($item_id, $dependson);
  if (show_dependent_output_top ($tracker_set, $dependson))
    return;
  $show_trash = $dependson && member_check (0, $group_id, 1);
  $access = [];
  foreach ($items as $item)
    show_dependent_single_item ($item_id, $item, $show_trash, $access);
  show_depend_output_bottom ($item_id, $group_id, $tracker_set);
}
?>
