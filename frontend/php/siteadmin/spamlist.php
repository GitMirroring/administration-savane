<?php
# List spam items.
#
# Copyright (C) 2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2019, 2023 Ineiev
#
# This file is part of Savane.
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

require_once ('../include/init.php');
require_once ('../include/form.php');

register_globals_off ();
session_require (['group' => '1', 'admin_flags' => 'A']);

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.
function no_i18n ($string)
{
  return $string;
}

extract (sane_import ('get',
  ['digits' => ['ban_user_id', 'wash_user_id', 'max_rows', 'offset']]
));

if ($ban_user_id)
  {
    if (!user_exists ($ban_user_id))
      fb (no_i18n ("User not found"), 1);
    else
      user_delete ($ban_user_id);
  }

if ($wash_user_id)
  {
    if (!user_exists ($wash_user_id))
      fb (no_i18n ("User not found"), 1);
    else
      {
        # Update the user spamscore field.
        db_execute (
          "UPDATE user SET spamscore = '0' WHERE user_id = ?", [$wash_user_id]
        );
        # Previous comment flagged as spam will stay as such.
        # We just changed the affected user id that it won't affect this guy
        # any more.
        # We assume that message flagged as spam really were.
        # (we may change that in the future, depending on user experience).
        db_execute ("
          UPDATE trackers_spamscore SET affected_user_id = '100'
          WHERE affected_user_id = ?", [$wash_user_id]
        );
      }
  }

site_admin_header (
  ['title' => no_i18n ("Monitor Spam"), 'context' => 'admhome']
);

print '<h2>' . html_anchor (no_i18n ("Suspected users"), "users_results")
  . "</h2>\n<p>"
  . no_i18n (
      "Follow the list of users that post comments that as been flagged\n"
      . "as spam.  You can remove accounts if their users are obvious\n"
      . "spammers.  If the comment was flagged by mistake,\n"
      . "you can wash user's reputation."
    )
  . ' <span class="warn">'
  . no_i18n (
      "Removed accounts can't be undeleted.  Be careful.\n"
      . "For efficiency purpose, there won't be any warnings."
    )
  . "</span></p>\n";

$title_arr = [
  no_i18n ("User"), no_i18n ("Score"), no_i18n ("Remove"),
  no_i18n ("Wash score"), no_i18n ("Incriminated posts"),
  no_i18n ("Flagged by")
];

if (empty ($max_rows))
  $max_rows = 50;

if (empty ($offset))
  $offset = 0;
$offset = intval ($offset);

$result = db_execute ("
  SELECT user_name, realname, user_id, spamscore FROM user
  WHERE status='A' AND spamscore > 0
  ORDER BY spamscore DESC LIMIT ?, ?", [$offset, $max_rows + 1]
);
if (!db_numrows ($result))
  {
    print '<p>' . no_i18n ("No suspects found") . "</p\n";
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

    $res_score = db_execute ("
      SELECT t.artifact, t.item_id, t.comment_id, u.user_name
      FROM trackers_spamscore t, user u
      WHERE t.affected_user_id = ?  AND u.user_id = t.reporter_user_id
      LIMIT 50", [$entry['user_id']]
    );
    $flagged_by = '';
    $incriminated_posts = '';
    $seen_before = [];
    while ($ent = db_fetch_array ($res_score))
      {
        if (!isset ($seen_before[$ent['user_name']]))
          {
            $flagged_by .= utils_user_link ($ent['user_name']) . ', ';
            $seen_before[$ent['user_name']] = true;
          }
        $idx = $ent['artifact'] . $ent['item_id'] . 'C' . $ent['comment_id'];
        if (!isset ($seen_before[$idx]))
          {
            # Only put the string "here" for each item, otherwise it gets
            # overlong when we have to tell comment #nnn of item #nnnn.
            $incriminated_posts .= utils_link (
              $sys_home . $ent['artifact'] . '/?item_id=' . $ent['item_id']
              . '&amp;func=viewspam&amp;comment_internal_id='
              . $ent['comment_id'] . '#spam' . $ent['comment_id'],
              no_i18n ("here")
            );
            $incriminated_posts .= ', ';
            $seen_before[$idx] = true;
          }
      }
    $flagged_by = rtrim ($flagged_by, ', ');
    $incriminated_posts = rtrim ($incriminated_posts, ', ');

    print '<tr class="' . utils_altrow($i) . '">';
    print '<td width="25%">'
      . utils_user_link ($entry['user_name'], $entry['realname'])
      . "</td>\n<td width='5%' class='center'>{$entry['spamscore']}</td>\n"
      . '<td width="5%" class="center">'
      . utils_link (
         "$php_self?ban_user_id=" . $entry['user_id'] . '#users_results',
         html_image_trash (['alt' =>  no_i18n("Remove")])
        )
      . "</td>\n<td width='5%' class='center'>"
      . utils_link ("$php_self?wash_user_id={$entry['user_id']}#users_results",
          html_image ('bool/ok.png', ['alt=' => no_i18n("Wash score")])
        )
      . "</td>\n<td width='30%'>$incriminated_posts</td>\n"
      . "<td width='30%'>$flagged_by</td>\n</tr>\n";
  }
print "</table>\n";

html_nextprev ("$php_self?", $max_rows, $i, "users");
$HTML->footer ([]);
?>
