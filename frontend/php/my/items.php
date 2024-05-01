<?php
# List user's items.
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
require_once ('../include/init.php');
require_once ('../include/my/general.php');
require_directory ("trackers");

global $item_data, $group_data;
$item_data = $group_data = [];

if (!user_isloggedin ())
  exit_not_logged_in ();

extract (sane_import ('get',
  [
    'digits' => [['form_threshold', [1, 9]]],
    'strings' => [['form_open', ['open', 'closed']]],
    'true' => 'boxoptionwanted'
  ]
));

$user_arr = [user_getid ()];

# Get the list of groups the user is member of.
$result = db_execute ("
  SELECT
    g.group_name, g.group_id, g.unix_group_name, g.status
  FROM groups g, user_group u
  WHERE g.group_id = u.group_id AND u.user_id = ?  AND g.status = 'A'
  GROUP BY g.unix_group_name ORDER BY g.unix_group_name", $user_arr
);
$rows = db_numrows ($result);
$usergroups = $usergroups_groupid = [];
$nogroups = 1;
if ($result && $rows > 0)
  {
    for ($j = 0; $j < $rows; $j++)
      {
        unset ($nogroups);
        $unixname = db_result ($result, $j, 'unix_group_name');
        $usergroups[$unixname] = db_result ($result, $j, 'group_name');
        $usergroups_groupid[$unixname] = db_result ($result, $j, 'group_id');
      }
  }

# Get the list of squads the user is member of.
$result = db_execute (
  "SELECT squad_id FROM user_squad WHERE user_id = ?", $user_arr
);
$rows = db_numrows ($result);
$usersquads = [];
$nosquads = 1;
if ($result && $rows > 0)
  {
    unset ($nosquads);
    for ($j = 0; $j < $rows; $j++)
      $usersquads[] = db_result ($result, $j, 'squad_id');
  }

$threshold = $form_threshold;
if ($threshold)
  user_set_preference ("my_items_threshold", $threshold);

$open = $form_open;
if ($open)
  user_set_preference ("my_items_open", $open);

# Extract configuration if needed.
if (!$threshold)
  $threshold = user_get_preference ("my_items_threshold");
if (!$open)
  $open = user_get_preference ("my_items_open");

# Still nothing? Set the default settings.
if (!$threshold)
  $threshold = 5;
if (!$open)
  $open = "open";

site_user_header (['context' => 'myitems']);
print '<p>'
  . _("This page contains lists of items assigned to or submitted by you.")
  . "</p>\n";
utils_get_content ("my/items");

$fopen = '<select title="' . _("open or closed") . "\" name='form_open'>\n"
  # TRANSLATORS: This is used later as argument of "Show [%s] new items..."
  . form_option ('open', $open, _("open<!-- items -->"));
$fopen .=
  # TRANSLATORS: This is used later as argument of "Show [%s] new items..."
  form_option ('closed', $open, _("closed<!-- items -->"))
  . "</select>\n";

$fthreshold = '<select title="' . _("priority") . "\" name='form_threshold'>\n";
$priorities = [
# TRANSLATORS: This is used later as argument of
# "...new items or of [%s] priority"
 1 => _("lowest"), 3 => _("low"), 5 => _("normal"), 7 => _("high"),
 9 => _("immediate")
];
foreach ($priorities as $k => $v)
  $fthreshold .= form_option ($k, $threshold, $v);
$fthreshold .= "</select>\n";

$form_opening = form_tag (['method' => 'get'], "#options");
$form_submit = '<input class="bold"  type="submit" value="'._("Apply").'" />';
# TRANSLATORS: the first argument is either 'open' or 'closed',
# the second argument is priority ('lowest', 'normal' &c.).
$msg_text = sprintf (_('Show %1$s new items of %2$s priority at least.'),
  $fopen, $fthreshold
);
print html_show_displayoptions ($msg_text, $form_opening, $form_submit);

foreach (
 [[1, 'assignee', _("Assigned to me")], [2, 'submitter', _("Submitted by me")]]
 as $v
)
 {
   print html_splitpage ($v[0]);
   print "<br />\n<div class='box'><div class='boxtitle'>{$v[2]}</div>\n";
   print my_item_list ($v[1], $threshold, $open);
   print "</div>\n";
 }
print html_splitpage (3);
print "\n\n". show_priority_colors_key ();
$HTML->footer ([]);
?>
