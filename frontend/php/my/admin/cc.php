<?php
# Cancelling notifications.
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

require_once ('../../include/init.php');
session_require (['isloggedin' => '1']);

$trackers = utils_get_tracker_list ();
$user_id = user_getid ();
$user_email = user_getemail ();
$user_name = user_getname ();

extract (sane_import ('request',
  ['preg' => [['cancel', '/^(\d+|any)$/']]]
));

function cancel_all ($tr_cc, $cc_set)
{
  return
    db_execute ("DELETE FROM $tr_cc WHERE $tr_cc.email IN (?, ?, ?)", $cc_set);
}

function list_bugs_ccing ($tracker, $tr_cc, $group, $cc_set)
{
  $result = db_execute ("
     SELECT DISTINCT t.bug_id
     FROM $tracker t JOIN $tr_cc c ON t.bug_id = c.bug_id
     WHERE t.group_id = ? AND c.email IN (?, ?, ?)",
     array_merge ([$group], $cc_set)
  );
  $bug_ids = [];
  while ($entry = db_fetch_array ($result))
    $bug_ids[] = $entry['bug_id'];
  return $bug_ids;
}

function cancel_in_tracker ($tracker, $group, $cc_set)
{
  $tr_cc = "{$tracker}_cc";
  if ($group == 'any')
    return cancell_all ($tr_cc, $cc_set);
  $bug_ids = list_bugs_ccing ($tracker, $tr_cc, $group, $cc_set);
  if (empty ($bug_ids))
    return true;
  return db_execute ("
    DELETE FROM $tr_cc
    WHERE email IN (?, ?, ?) AND bug_id " . utils_in_placeholders ($bug_ids),
    array_merge ($cc_set, $bug_ids)
  );
}

if (!empty ($cancel))
  foreach ($trackers as $tracker)
    cancel_in_tracker (
      $tracker, $cancel, [$user_id, $user_email, $user_name]
    );

# Actually prints the HTML page.
site_user_header (
  ['title' => _("Cancel Mail Notifications"), 'context' => 'account']
);
# The following text is in two gettext string, because the first part is also
# shown in My Admin index.
print '<p>'
  . _("Here, you can cancel all mail notifications. Beware: this\nprocess "
      . "cannot be undone, you will be definitely removed from carbon-copy "
      . "lists\nof any items of the selected groups.")
  . "<p>\n";

# Find all CC the user is registered to receive, list them per groups.
$groups_with_cc = $groups_with_cc_gid = [];
foreach ($trackers as $tr)
  {
    $result = db_execute ("
      SELECT g.unix_group_name, g.group_name, g.group_id
      FROM
        `groups` g JOIN $tr t ON g.group_id = t.group_id
        JOIN {$tr}_cc cc ON t.bug_id = cc.bug_id
      WHERE cc.email IN (?, ?, ?)", [$user_id, $user_email, $user_name]
    );
    while ($entry = db_fetch_array ($result))
      {
        $groups_with_cc[$entry['unix_group_name']] = $entry['group_name'];
        $groups_with_cc_gid[$entry['unix_group_name']] = $entry['group_id'];
      }
  }

if (!count ($groups_with_cc))
  {
    print '<p class="warn">'
      . _("You are not registered on any Carbon-Copy list.") . "</p>\n";
    site_user_footer ([]);
    exit;
  }

print $HTML->box_top (_("Groups with items you are in Carbon Copy for"));
ksort ($groups_with_cc);
$i = 0;
foreach ($groups_with_cc as $thisunixname => $thisname)
  {
    $i++;
    if ($i > 1)
      print $HTML->box_nextitem (utils_altrow ($i));

    print '<span class="trash">';
    print utils_link (
      "$php_self?cancel=" . $groups_with_cc_gid[$thisunixname],
      html_image_trash (['alt' => _("Cancel CC for this group")])
    );
    print '</span>';
    print
      "<a href=\"{$sys_home}projects/$thisunixname/\">$thisname</a><br />\n";
  }

# Allow to kill sessions apart the current one,
# if more than 3 sessions were counted (otherwise, it looks overkill).
if ($i > 3)
  {
    $i++;
    print $HTML->box_nextitem (utils_altrow ($i));
    print '<span class="trash">';
    print utils_link (
      "$php_self?cancel=any", html_image_trash (['alt' => _("Cancel All CC")])
    );
    print '</span><em>';
    # TRANSLATORS: the argument is site name (like Savannah).
    printf (_("All Carbon-Copies over %s"), $sys_name);
    print "</em><br />&nbsp;\n";
  }
print $HTML->box_bottom ();
site_user_footer ([]);
?>
