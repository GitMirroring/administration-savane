<?php
# Vote-related functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
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

# Return the remaining amount of votes for the current user.
function trackers_votes_remaining ()
{
  $total = 100;
  $result = db_execute (
    "SELECT SUM(howmuch) AS sum FROM user_votes WHERE user_id = ?",
    [user_getid ()]
  );

  if (db_numrows ($result))
    $total -= db_result ($result, 0, 'sum');

  if ($total < 0)
    return 0;
  return $total;
}

# Return the votes of the current user given to the item.
function trackers_get_user_votes ($tracker, $item_id)
{
  $result = db_execute ("
    SELECT howmuch FROM user_votes
    WHERE user_id = ? AND tracker = ? AND item_id = ? LIMIT 1",
    [user_getid (), $tracker, $item_id]
  );

  if (!db_numrows ($result))
    return 0;
  return db_result ($result, 0, 'howmuch');
}

# Remove a vote.
function trackers_votes_erase_vote ($tracker, $item_id, $vote)
{
  db_execute ("
    DELETE FROM user_votes
    WHERE user_id = ?  AND tracker = ? AND item_id = ? LIMIT 1",
    [user_getid (), $tracker, $item_id]
  );
  $res = db_execute ("SELECT vote FROM $tracker WHERE bug_id = ?", [$item_id]);
  $new_vote = db_result ($res, 0, 'vote') - $vote;
  db_execute (
    "UPDATE $tracker SET vote = ? WHERE bug_id = ?", [$new_vote, $item_id]
  );
  fb (_("Vote erased"));
}

# Make sure the new vote count doesn't overflow the total allowed
# vote count for the user; return the resulting increment.
function trackers_votes_diff ($old_vote, &$new_vote)
{
  # Check the diff between the registered vote and the new vote.
  $diff_vote = $new_vote - $old_vote;

  # If new vote equal to the current vote, nothing to do.
  if (!$diff_vote)
    return $diff_vote;

  # Check whether the user have not specified more votes than he actually
  # got available.
  $remains = trackers_votes_remaining ();
  if ($remains < $diff_vote)
    {
      # If so, set the diff_vote and new_vote as the maximum possible.
      $diff_vote = $remains;
      $new_vote = $diff_vote + $old_vote;
    }
  return $diff_vote;
}

# Update user_votes table, add CC to tracker item if needed.
# Return true on failure.
function trackers_votes_insert_vote ($old_vote, $new_vote, $tracker, $item_id)
{
  if ($old_vote)
    $res = db_execute ("
      UPDATE user_votes SET howmuch = ?
      WHERE user_id = ? AND tracker = ? AND item_id = ?",
      [$new_vote, user_getid (), $tracker, $item_id]
    );
  else
    {
      $res = db_autoexecute (
        'user_votes',
        [
          'user_id' => user_getid (), 'tracker' => $tracker,
          'item_id' => $item_id, 'howmuch' => $new_vote
        ], DB_AUTOQUERY_INSERT
      );
      trackers_add_cc ($item_id, user_getname (), TRACKERS_CC_VOTED);
    }
  return db_affected_rows ($res) < 1;
}

# Update vote count in tracker table.
function trackers_votes_inc_item_vote ($tracker, $item_id, $diff_vote)
{
  # Add the new vote to the item proper info table.
  $res = db_execute (
    "SELECT vote FROM $tracker WHERE bug_id = ?", [$item_id]
  );
  $item_vote = db_result ($res, 0, 'vote') + $diff_vote;
  $res = db_execute ("
    UPDATE $tracker SET vote = ? WHERE bug_id = ?", [$item_vote, $item_id]
  );
  if (db_affected_rows ($res) < 1)
    {
      # In case of problem, kept unmodified the item proper info.
      fb (_("Unable to finally record the vote, please report to admins"), 1);
      return;
    }
  # If we arrive here, everything went properly.
  if ($diff_vote > 0)
    $diff_vote = "+$diff_vote";
  fb (_("Vote recorded") . " ($diff_vote)");
}

# Update update votes in the database.
function trackers_votes_update ($item_id, $new_vote, $tracker = null)
{
  if (!user_isloggedin ())
    return;
  if (!$tracker)
    $tracker = ARTIFACT;

  $old_vote = trackers_get_user_votes ($tracker, $item_id);
  if ($new_vote < 1)
    {
      if ($old_vote)
        trackers_votes_erase_vote ($tracker, $item_id, $old_vote);
      return;
    }
  $diff_vote = trackers_votes_diff ($old_vote, $new_vote);
  if (!$diff_vote)
    return;

  if (trackers_votes_insert_vote ($old_vote, $new_vote, $tracker, $item_id))
    {
      fb (_("Unable to record the vote, please report to admins"), 1);
      return;
    }
  trackers_votes_inc_item_vote ($tracker, $item_id, $diff_vote);
}
?>
