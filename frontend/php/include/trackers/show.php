<?php
# Show items.
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

function show_item_cc_list ($item_id, $group_id)
{
  print format_item_cc_list ($item_id, $group_id);
}

# Look for items that $item_id depends on in all artifact.
function show_item_dependency ($item_id)
{
  return show_dependent_item ($item_id, 1);
}

# Look for items that depends on $item_id in all artifacts (default)
# or look for items that $item_id depends on in all artifact.
function show_dependent_item ($item_id, $dependson = 0)
{
  global $group_id;

  $artifacts = ["support", "bugs", "task", "patch"];
  $is_manager = member_check (0, $group_id, 1);
  if (!$dependson)
    $title = _("Items that depend on this one");
  else
    $title = _("Depends on the following items");

  # Create hash that will contain every relevant info
  # with keys like $date.$item_id so it will be sorted by date (first)
  # and item_id (second).
  $content = [];

  # Slurps the database.
  $item_exists = false;
  $item_exists_tracker = [];
  foreach ($artifacts as $art)
    {
      $sql_params = [$item_id];
      if ($dependson)
        {
          $art_dep = ARTIFACT;
          $sql_params[] = $art;
          $where = "art.bug_id = art_dep.is_dependent_on_item_id
            AND art_dep.item_id = ?";
        }
      else
        {
          $art_dep = $art;
          $sql_params[] = ARTIFACT;
          $where = "art.bug_id = art_dep.item_id
             AND art_dep.is_dependent_on_item_id = ? ";
        }
     $art_dep .= '_dependencies';
     $sql = "
       SELECT
         art.bug_id, art.date, art.summary, art.status_id, art.resolution_id,
         art.group_id, art.priority, art.privacy, art.submitted_by
       FROM $art art, $art_dep art_dep
       WHERE
         $where
         AND art_dep.is_dependent_on_item_id_artifact = ?
       ORDER by art.bug_id";
      $res_all = db_execute ($sql, $sql_params);

      while ($res_arr = db_fetch_array ($res_all))
        {
          # Note for later that at least one item was found.
          $item_exists = true;
          $item_exists_tracker[$art] = 1;

          # Generate unique key date.tracker#nnn.
          $key = $res_arr['date'] . ".$art#" . $res_arr['bug_id'];

          # Store relevant data.
          $content[$key]['item_id'] = $res_arr['bug_id'];
          $content[$key]['tracker'] = $art;
          foreach (
            [
              'date', 'summary', 'status_id', 'resolution_id', 'group_id',
              'priority', 'privacy', 'submitted_by'
            ] as $k
          )
            $content[$key][$k] = $res_arr[$k];
        }
    } # foreach ($artifacts as $art)

  # No item found? Exit here.
  if (!$item_exists)
    {
      print '<p class="warn">' . sprintf (("%s: %s"), $title, _("None found"))
        . "</p>\n";
      return;
    }

  # Give back the HTML output, if we have some data.
  global $HTML, $php_self;
  print $HTML->box_top ($title, '', 1);

  # Create a hash to avoid looking several times for the same info.
  $dstatus = $allowed_to_see = $group_getname = [];

  # Sort the content by key, which contain the date as first field
  # (so order by date).
  ksort ($content);
  $i = 0;

  foreach ($content as $key => $val)
    {
      $current_item_id = $content[$key]['item_id'];
      $tracker = $content[$key]['tracker'];
      $current_group_id = $content[$key]['group_id'];
      $link_to_item = $GLOBALS['sys_home'] . "$tracker/?$current_item_id";

      # Found out the status full text name:
      # this is project specific. If there is no project setup for this
      # then go to the default for the site.
      $st_key = $current_group_id . $tracker . $content[$key]['resolution_id'];
      if (!array_key_exists ($st_key, $dstatus))
        {
          $dstatus[$st_key] =
            db_result (db_execute ("
              SELECT value FROM {$tracker}_field_value
              WHERE
                bug_field_id = '108' AND (group_id = ? OR group_id = 100)
                AND value_id = ?
              ORDER BY bug_fv_id DESC LIMIT 1",
              [$group_id, $content[$key]['resolution_id']]), 0, 'value'
           );
        }
      $status = $dstatus[$st_key];

      print '<div class=\''
        . utils_get_priority_color (
            $content[$key]['priority'], $content[$key]['status_id']
          )
        . "'>\n";

      # Ability to remove a dependency is only given to technician
      # level members of a project.
      if ($dependson && $is_manager)
        print '<span class="trash"><a href="'
          . "$php_self?func=delete_dependency&amp;item_id=$item_id"
          . "&amp;item_depends_on=$current_item_id"
          . "&amp;item_depends_on_artifact=$tracker\">"
          . html_image_trash (
              ['class' => 'icon', 'alt' => _("Delete this dependency")]
            )
          . '</a></span>';

      print "<a href=\"$link_to_item\" class='block'>";

      print html_image (
        'contexts/' . utils_get_tracker_icon ($tracker) . '.png',
         ['class' => 'icon', 'alt' => $tracker]);

      # Print summary only if the item is not private.
      # Check privacy right (dont care about the tracker specific
      # rights, being project member is enough).
      if (!array_key_exists ($current_group_id, $allowed_to_see))
        $allowed_to_see[$current_group_id] =
          member_check (0, $current_group_id, 2);

      if ($content[$key]['privacy'] == "2"
          && !$allowed_to_see[$current_group_id]
          && $content[$key]['submitted_by'] != user_getid ())
        print _("---- Private ----");
      else
        print $content[$key]['summary'];

      # Print group info if the item is from another group.
      $fromgroup = null;
      if ($current_group_id != $group_id)
        {
          if (!array_key_exists ($current_group_id, $group_getname))
            $group_getname[$current_group_id] =
              group_getname ($content[$key]['group_id']) . ', ';

          $fromgroup = $group_getname[$current_group_id];
        }

      # Mention the status.
      print '&nbsp;<span class="xsmall">('
        . utils_get_tracker_prefix ($tracker)
        . " #$current_item_id, $fromgroup$status)</span></a>";
      print "</div>\n";
      $i++;
    }
  print $HTML->box_bottom (1);

  # Add links to make digests.
  reset ($artifacts);
  print '<p class="noprint"><span class="preinput">' . _("Digest:")
    . "</span>\n<br />&nbsp;&nbsp;&nbsp;";
  $content = '';
  foreach ($artifacts as $tracker)
    {
      if (empty ($item_exists_tracker[$tracker]))
        continue;
      switch ($tracker)
        {
        case "support":
          $linktitle = _("support dependencies");
          break;
        case "bugs":
          $linktitle = _("bug dependencies");
          break;
        case "task":
          $linktitle = _("task dependencies");
          break;
        case "patch":
          $linktitle = _("patch dependencies");
          break;
        default:
          # TRANSLATORS: the argument is tracker name, unlocalized
          # (this string is a fallback that should never actually be used).
          $linktitle = sprintf (_("%s dependencies"), $tracker);
        }
      $content .= utils_link (
        $GLOBALS['sys_home'] . "$tracker/?group_id=$group_id"
          . '&amp;func=digestselectfield&amp;dependencies_of_item='
          . $item_id . '&amp;dependencies_of_tracker=' . ARTIFACT,
        $linktitle, 'noprint'
      );
      $content .= ', ';
    }
  print rtrim ($content, ', ') . ".</p>\n";
}
?>
