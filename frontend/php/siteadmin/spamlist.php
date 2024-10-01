<?php
# List spam items.
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
require_once ('../include/form.php');
session_require (['group' => '1', 'admin_flags' => 'A']);

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.

extract (sane_import ('get', ['digits' => ['max_rows', 'offset']]));

function wash_user ()
{
  extract (sane_import ('get', ['digits' => ['wash_user_id']]));
  if (empty ($wash_user_id))
    return;
  if (!user_exists ($wash_user_id))
    {
      fb (sprintf (no_i18n ("User #%s not found."), $wash_user_id), 1);
      return;
    }
  db_execute (
    "UPDATE user SET spamscore = '0' WHERE user_id = ?", [$wash_user_id]
  );
  # The comment flagged as spam will stay as such.  We assume that the messages
  # flagged as spam should stay hidden, we just change the record of the user
  # (for example, the admins of the tracker may hide duplicate posts as spam).
  db_execute ("
    UPDATE trackers_spamscore SET affected_user_id = '100'
    WHERE affected_user_id = ?", [$wash_user_id]
  );
}

function format_user_data ($entry, $i)
{
  global $php_self;

  return '<td width="25%">'
    . utils_user_link ($entry['user_name'], $entry['realname'])
    . "</td>\n<td width='5%' class='center'>{$entry['spamscore']}</td>\n"
    . "</td>\n<td width='5%' class='center'>"
    . utils_link ("$php_self?wash_user_id={$entry['user_id']}#users_results",
        html_image ('bool/ok.png', ['alt=' => no_i18n ("Wash score")])
      )
    . "</td>\n";
}

function fetch_incriminated_posts ($user_id)
{
  return db_execute ("
    SELECT t.artifact, t.item_id, t.comment_id, u.user_name
    FROM trackers_spamscore t, user u
    WHERE t.affected_user_id = ? AND u.user_id = t.reporter_user_id
    LIMIT 50", [$user_id]
  );
}

function get_incriminated_posts ($user_id)
{
  global $sys_home;
  $flagged_by = $incriminated_posts = [];
  $res = fetch_incriminated_posts ($user_id);
  while ($ent = db_fetch_array ($res))
    {
      $flagged_by[$ent['user_name']] = true;
      $idx = $ent['artifact'] . $ent['item_id'] . 'C' . $ent['comment_id'];
      $incriminated_posts[$idx] = utils_link (
        $sys_home . $ent['artifact'] . '/?item_id=' . $ent['item_id']
        . '&amp;func=viewspam&amp;comment_internal_id='
        . $ent['comment_id'] . '#spam' . $ent['comment_id'],
        utils_get_tracker_prefix ($ent['artifact']) . " #" . $ent['item_id']
        . "(" . $ent['comment_id'] . ")"
      );
    }
  return [array_keys ($flagged_by), $incriminated_posts];
}

function format_flagged_by ($flagged_by)
{
  $ret = [];
  foreach ($flagged_by as $user)
    $ret[] = utils_user_link ($user);
  return join (', ', $ret);
}

wash_user ();

site_admin_header (
  ['title' => no_i18n ("Monitor Spam"), 'context' => 'admhome']
);

print html_h (2, html_anchor (no_i18n ("Suspected users"), "users_results"))
  . "<p>"
  . no_i18n (
      "Follow the list of users that post comments that as been flagged\n"
      . "as spam."
    )
  . "</p>\n";

$title_arr = [
  no_i18n ("User"), no_i18n ("Score"), no_i18n ("Wash score"),
  no_i18n ("Incriminated posts"), no_i18n ("Flagged by")
];

if (empty ($max_rows))
  $max_rows = 50;

if (empty ($offset))
  $offset = 0;
$offset = intval ($offset);

$result = db_execute ("
  SELECT user_name, realname, user_id, spamscore FROM user
  WHERE status = 'A' AND spamscore > 0
  ORDER BY spamscore DESC LIMIT ?, ?", [$offset, $max_rows + 1]
);
if (!db_numrows ($result))
  {
    print '<p>' . no_i18n ("No suspects found.") . "</p>\n";
    $HTML->footer ([]);
    exit (0);
  }

print html_build_list_table_top ($title_arr);

$i = 0;
while ($entry = db_fetch_array ($result))
  {
    $i++;
    # The SQL was artificially asked to search more result than the number
    # we print. If $i > $max, it means that there were more results than
    # the max, we shan't print these more, but below we will add next/prev
    # links.
    if ($i > $max_rows)
      break;

    list ($flagged_by, $incriminated_posts) =
      get_incriminated_posts ($entry['user_id']);
    print '<tr class="' . utils_altrow ($i) . '">';
    print format_user_data ($entry, $i);
    print "<td width='30%'>" . join (', ', $incriminated_posts) . "</td>\n"
      . "<td width='30%'>" . format_flagged_by ($flagged_by) . "</td>\n</tr>\n";
  }
print "</table>\n";

html_nextprev ("$php_self?", $max_rows, $i, "users");
$HTML->footer ([]);
?>
