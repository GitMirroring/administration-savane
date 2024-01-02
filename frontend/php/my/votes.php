<?php
# Handle votes.
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
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
require_once ('../include/init.php');
require_once ('../include/html.php');
require_once ('../include/trackers/general.php');
require_once ('../include/trackers/votes.php');

extract (sane_import ('post',
  [
    'true' => 'submit',
    'array' => [['new_votes', ['digits', 'digits']]],
  ]
));

if (!user_isloggedin ())
  exit_not_logged_in ();
$remaining_votes = trackers_votes_remaining ();

if ($submit)
  {
    $result = db_execute ("
       SELECT vote_id, tracker, item_id FROM user_votes
       WHERE user_id = ? ORDER BY howmuch DESC, item_id ASC LIMIT 100",
       [user_getid ()]
    );
    unset($count);
    # Build a list of votes to update: we must proceed in two step because
    # we must check that the vote count does not exceed the limit (100).
    $new_votes_list = $new_votes_list_item_id = $new_votes_list_tracker = [];

    $count = 0;
    while ($row = db_fetch_array ($result))
      {
        if(!isset ($new_votes[$row['vote_id']]))
          continue;
        $new_vote = $new_votes[$row['vote_id']];
        $count = $count + $new_vote;
        $new_votes_list[$row['vote_id']] = $new_vote;
        $new_votes_list_item_id[$row['vote_id']] = $row['item_id'];
        $new_votes_list_tracker[$row['vote_id']] = $row['tracker'];
      }

    if ($count > 100)
      fb (_("Vote count exceed limits, your changes have been discarded"), 1);
    else
      foreach ($new_votes_list as $vote_id => $new_vote)
        trackers_votes_update ($new_votes_list_item_id[$vote_id],
          $new_vote, $new_votes_list_tracker[$vote_id]
        );
    $remaining_votes = trackers_votes_remaining ();
  }
site_user_header (['context' => 'votes']);
# Simple listing. No need of anything really fancy, there will be no more
# than hundred entries.

# The SQL is not exactly designed to save requests, just simple stuff.
print '<p>' . _("Here is the list of your votes.") . ' ';
printf (
  ngettext (
    "%s vote remains at your disposal.", "%s votes remain at your disposal.",
    $remaining_votes
  ),
  $remaining_votes
);
print "</p>\n";

if ($remaining_votes < 100)
  {
    print '<p>'
      . _("To change your votes, type in new numbers (using zero removes "
          . "the entry\nfrom your votes list).")
      . "</p>\n";

    print form_tag ();

    $result = db_execute ("
      SELECT * FROM user_votes WHERE user_id = ?
      ORDER BY howmuch DESC, item_id ASC LIMIT 100",
      [user_getid ()]
    );

    while ($row = db_fetch_array ($result))
      {
        $tr = $row['tracker'];
        $msg = sprintf (_("Invalid tracker name: %s"), "<em>$tr/em>");
        if (!ctype_alnum (strval ($tr)))
          util_die ($msg);
        $res_item = db_execute ("
          SELECT summary, vote, status_id, priority, group_id
          FROM $tr WHERE bug_id = ? LIMIT 1", [$row['item_id']]
        );
        $res_row = db_fetch_array ($res_item);

        $prefix = utils_get_tracker_prefix ($tr);
        $icon = utils_get_tracker_icon ($tr);
        $vote = $res_row['vote'];
        $color = utils_get_priority_color (
          $res_row['priority'], $res_row['status_id']
        );

        print "<div class=\"$color\">\n"
          . '<input type="text" title="' . _("Vote number")
          . "\" name=\"new_votes[{$row['vote_id']}]\" "
          . 'size="3" maxlength="3" value="' . "{$row['howmuch']}\" />\n/ "
          . ($row['howmuch'] + $remaining_votes) . '&nbsp;&nbsp;&nbsp;&nbsp;'
          . "<a href=\"{$GLOBALS['sys_home']}$tr"
          . "/?func=detailitem&amp;item_id={$row['item_id']}\">\n"
          . html_image ("contexts/$icon.png",
              ['class' => "icon", 'alt' => $tr]
            )
          . $res_row['summary'] . ', '
          . sprintf (ngettext ("%s vote", "%s votes", $vote), $vote)
          . "&nbsp;<span class=\"xsmall\">($prefix #{$row['item_id']}, "
          . group_getname ($res_row['group_id']) . ")</span></a></div>\n";
      }

    print "<br />\n<div align=\"center\" class=\"noprint\">"
      . '<input type="submit" name="submit" class="bold" value="'
      . _("Submit Changes") . "\" /></div>\n</form>\n";
    print "\n\n" . show_priority_colors_key ();
  }
$HTML->footer ([]);
?>
