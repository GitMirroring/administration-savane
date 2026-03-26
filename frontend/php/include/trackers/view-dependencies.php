<?php
# Tracker functions for displaying item dependencies.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

$have_hidden_something = 0;

function trackers_list_group_items ()
{
  global $group_id;
  $artifact = ARTIFACT;
  $res = db_execute ("
     SELECT
       '$artifact' AS `tracker`, `bug_id`, `summary`, `privacy`, `group_id`,
       `status_id`, `priority`
     FROM `$artifact` WHERE `group_id` = ? ORDER BY `bug_id`", [$group_id]
  );
  $items_for_digest = $ret = [];
  if (!db_numrows ($res))
    return [$items_for_digest, $ret];
  while ($row = db_fetch_array ($res))
    {
      $items_for_digest[] = $row['bug_id'];
      $ret[$row['bug_id']] = $row;
    }
  return [$items_for_digest, $ret];
}

function trackers_fetch_summaries ($art)
{
  $tables = $args = $ret = [];
  foreach ($art as $a => $l)
    {
      $tables[] = "
        SELECT
          '$a' AS `tracker`, `bug_id`, `summary`, `privacy`, `group_id`,
          `status_id`, `priority`
        FROM `$a` WHERE `spamscore` < 5 AND `bug_id` "
        . utils_in_placeholders ($l);
      $args = array_merge ($args, $l);
    }
  if (empty ($tables))
    return $ret;
  $sql = join ("UNION", $tables);
  $res = db_execute ($sql, $args);
  while ($row = db_fetch_array ($res))
    if (!trackers_item_access_denied ($row))
      $ret[$row['tracker']][$row['bug_id']] = $row;
  return $ret;
}

function trackers_fetch_dependencies ($items)
{
  if (empty ($items))
    return [[], []];
  $sql = "
    SELECT
        `item_id`, `is_dependent_on_item_id` AS `dep_id`,
        `is_dependent_on_item_id_artifact` AS `dep_art`
      FROM `" . ARTIFACT . "_dependencies`
      WHERE `item_id` " . utils_in_placeholders ($items);
  $res = db_execute ($sql, $items);
  if (!db_numrows ($res))
    return [[], []];
  $items = $art = [];
  while ($l = db_fetch_array ($res))
    {
      $items[$l['item_id']][$l['dep_art']][] = $l['dep_id'];
      $art[$l['dep_art']][] = $l['dep_id'];
    }
  return [$items, $art];
}

function trackers_list_dependencies ($items)
{
  list ($items, $art) = trackers_fetch_dependencies ($items);
  $summaries = trackers_fetch_summaries ($art);
  $ret = [];
  foreach ($items as $it => $v)
    foreach ($v as $tracker => $ids)
      {
        if (!array_key_exists ($tracker, $summaries))
          continue;
        $sum = $summaries[$tracker];
        foreach ($ids as $i)
          if (array_key_exists ($i, $sum))
            $ret[$it][] = $sum[$i];
      }
  return $ret;
}

function trackers_item_access_denied ($row)
{
  $ret = $row['privacy'] == '2' && !member_check_private (0, $row['group_id']);
  if ($ret)
    $GLOBALS['have_hidden_something'] = 1;
  return $ret;
}

function trackers_warn_about_hidden ($html_format = true)
{
  $ret = '';
  if (!$GLOBALS['have_hidden_something'])
    return $ret;
  if ($html_format)
    $ret .= "<p><strong>";
  $ret .= _('Note: private items are not shown.');
  if ($html_format)
    $ret .= "</strong></p>\n";
  else
    $ret .= '\l\l';
  return $ret;
}

# Drop unaccessible, unlinked and other unappropriate items.
function trackers_filter_out_items (&$items_for_digest, $items, $dependencies)
{
  global $include_closed, $max_rows, $offset;
  $filtered = [];
  $i = $total = 0;
  foreach ($items_for_digest as $it)
    {
      $item = $items[$it];
      if (trackers_item_access_denied ($item))
        continue;
      if (!array_key_exists ($it, $dependencies))
        continue;
      if (empty ($include_closed) && $item['status_id'] == 3)
        continue;
      $total++;
      if ($i++ < $offset)
        continue;
      if ($i > $offset + $max_rows)
        continue;
      $filtered[] = $it;
    }
  $items_for_digest = $filtered;
  return [$i, $total];
}

function trackers_view_dependencies ($list_format)
{
  global $item_no;
  list ($items_for_digest, $group_items) = trackers_list_group_items ();
  $deps = trackers_list_dependencies ($items_for_digest);
  list ($item_no, $total) =
    trackers_filter_out_items ($items_for_digest, $group_items, $deps);
  $f = "trackers_output_list_$list_format";
  $f ($items_for_digest, $group_items, $deps, $total);
  return $items_for_digest;
}

function trackers_output_list_file ($text, $type, $extension, $total = 0)
{
  global $group;
  $name = "$group-" . ARTIFACT . ".$extension";
  header ('Last-Modified: ' . date ('r'));
  header ("Content-Type: $type");
  header ('Content-Length: ' . strlen ($text));
  header ("Content-Disposition: attachment; filename=$name");
  print $text;
}
function trackers_label_items ($listed, $group_items, $deps)
{
  $ret = "  node [ style = filled, fontcolor = white, fillcolor = black ]\n";
  foreach ($listed as $tr => $items)
    foreach ($items as $i => $ignored)
      {
        $status = null;
        if (ARTIFACT == $tr && !empty ($group_items[$i]))
          $status = $group_items[$i]['status_id'];
        if ($status === null && !empty ($deps))
          {
            foreach ($deps as $l)
              foreach ($l as $d)
                if ($d['tracker'] == $tr && $d['bug_id'] == $i)
                  $status = $d['status_id'];
          }
        $ret .= tracker_item_label ($tr, $i, $status);
      }
  return $ret;
}
function trackers_list_text_head ()
{
  global $group;
  $head = "digraph {$group}_" . ARTIFACT . "\n{\n  label = \"\n$group ";
  $head .= ARTIFACT . "\n\n" . trackers_warn_about_hidden (false);
  $notice = git_agpl_notice ("This graph was generated with Savane.");
  $notice = preg_replace ('/\n/', '\l', $notice);
  $head .= "$notice\"\n\n";
  return $head;
}
function tracker_dep_node_name ($tr, $i)
{
  return "{$tr}_$i";
}
function trackers_print_item_list_img ($items, $dependencies)
{
  global $sys_graphviz, $php_self;
  if (empty ($sys_graphviz))
    return;
  $url = "/" . ARTIFACT . "/dependencies.php?";
  $args = ['list_format=svg'];
  foreach (['group', 'include_closed', 'offset', 'max_rows'] as $var)
    {
      if (empty ($GLOBALS[$var]))
        continue;
      $args[] = "$var={$GLOBALS[$var]}";
    }
  $url .= join ('&', $args);
  print "<img src=\"$url\" alt=\"" . _('Dependency graph')
    . " width='100%'\" />\n";
}
function trackers_print_view_deps_controls ()
{
  global $include_closed, $group;
  print form_tag (['method' => 'get']);
  print form_hidden (['func' => 'view-dependencies', 'group' => $group]);
  print trackers_max_rows_control ('max_rows');
  print "&nbsp; &nbsp;\n";
  print form_checkbox ('include_closed', !empty ($include_closed),
    ['label' => _('Include closed items')]);
  print " &nbsp; ";
  print form_input ('submit', 'apply', _('Apply')) . "</form>\n";
}
function trackers_show_dep ($d)
{
  print "<li>";
  print "<a href=\"/{$d['tracker']}/?{$d['bug_id']}\">";
  print "{$d['tracker']} #{$d['bug_id']}</a>: ";
  print '<span class="'
    . utils_get_priority_color ($d['priority'], $d['status_id']) . '">';
  print "{$d['summary']}</span></li>\n";
}

function trackers_print_item_deps ($deps)
{
  print "<ul>\n";
  foreach ($deps as $d)
    trackers_show_dep ($d);
  print "</ul>\n";
}
function trackers_print_item_link ($row)
{
  if ($row['status_id'] != 1)
    {
      $img_file = 'ok.png'; $img_alt = _("Closed Item");
    }
  else
    {
      $img_file = 'wrong.png'; $img_alt = _("Open Item");
    }
  $icon = html_image ("bool/$img_file", ['alt' => $img_alt]);
  $item = $row['bug_id'];
  $artifact = $row['tracker'];
  $summary = $row['summary'];

  print '<span class="'
   . utils_get_priority_color ($row['priority'], $row['status_id'])
   . "\">$icon&nbsp; "
   . utils_link ("/$artifact/?$item", "$artifact #$item")
   . ": &nbsp;$summary &nbsp;</span>";

}
function trackers_print_item_list_html (
  $items_for_digest, $items, $dependencies, $total
)
{
  if (empty ($items_for_digest))
    {
      print "<p>" . _("No item found.") . "</p>\n";
      return;
    }
  print "<ul>\n";
  $i = 0;
  foreach ($items_for_digest as $it)
    {
      print '<li class="' . utils_altrow ($i++) . "\">\n<p>";
      trackers_print_item_link ($items[$it]);
      print "</p>\n";
      trackers_print_item_deps ($dependencies[$it], $total);
      print "</li>\n";
    }
  print "</ul>\n";
}
function trackers_viewdep_nextprev ($total)
{
  global $group, $item_no, $include_closed;
  global $max_rows, $offset;
  if (empty ($offset) && $item_no <= $max_rows)
    return;
  $url = '/' . ARTIFACT . "/dependencies.php?group=$group";
  if (!empty ($include_closed))
    $url .= "&amp;include_closed";
  html_nextprev ($url, $offset, $max_rows, $total);
}
function trackers_output_list_html (
  $items_for_digest, $items, $dependencies, $total
)
{
  trackers_header (['title' => _("Dependencies")]);
  trackers_print_view_deps_controls ();
  print "<div id='results'>\n";
  trackers_viewdep_nextprev ($total);
  if (!empty ($items_for_digest))
    trackers_print_item_list_img ($items, $dependencies);
  trackers_print_item_list_html (
    $items_for_digest, $items, $dependencies, $total
  );
  print trackers_warn_about_hidden ();
  trackers_viewdep_nextprev ($total);
  print "</div><!-- id='results' -->\n";
  trackers_footer ();
}
function tracker_item_label ($tr, $i, $status)
{
  if ($status === null)
    return '';
  $ret = "  " . tracker_dep_node_name ($tr, $i) . " [ label = \"$tr $i\", ";
  if ($status == 3)
    $ret .= "fillcolor = \"#006000\"";
  else
    $ret .= "fillcolor = \"#800000\", shape = box";
  return "$ret ]\n";
}
function trackers_gen_list_text ($items_for_digest, $group_items, $deps)
{
  global $group;
  $head =  trackers_list_text_head ();
  $listed = [];
  $links = '';
  foreach ($items_for_digest as $it)
    {
      $listed[ARTIFACT][$it] = 1;
      foreach ($deps[$it] as $d)
        {
          $links .= "  " . tracker_dep_node_name (ARTIFACT, $it) . " -> "
            . tracker_dep_node_name ($d['tracker'], $d['bug_id']) . "\n";
          $listed[$d['tracker']][$d['bug_id']] = 1;
        }
    }
  return $head . trackers_label_items ($listed, $group_items, $deps) . $links . "}\n";
}
function trackers_output_list_text ($items_for_digest, $items, $deps, $total)
{
  trackers_output_list_file (
    trackers_gen_list_text ($items_for_digest, $items, $deps),
    'text/plain', 'txt'
  );
}
function trackers_gen_list_svg ($items_for_digest, $items, $deps, $total = 0)
{
  global $sys_graphviz;
  if (empty ($sys_graphviz))
    return '';
  $list_text = trackers_gen_list_text ($items_for_digest, $items, $deps);
  utils_run_proc ("$sys_graphviz -Tsvg", $out, $err, ['in' => $list_text]);
  return $out;
}
function trackers_output_list_svg ($items_for_digest, $items, $deps, $total)
{
  trackers_output_list_file (
    trackers_gen_list_svg ($items_for_digest, $items, $deps),
   'image/svg+xml', 'svg'
  );
}
?>
