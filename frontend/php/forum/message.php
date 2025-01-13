<?php
# Display forum message.
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

require_once ('../include/init.php');
require_once ('../include/sane.php');
require_once ('../include/news/forum.php');
require_once ('../include/news/general.php');

extract (sane_import ('request', ['digits' => 'msg_id']));


if (empty ($msg_id))
  {
    forum_header (['title' => _('Choose a message first')]);
    print '<p>' . _('You must choose a message first') . "</p>\n";
    forum_footer ([]);
    exit;
  }
# Figure out which group this message is in, for the sake of the admin links.
$result = db_execute ("
  SELECT
    l.group_id, l.forum_name, f.group_forum_id, f.thread_id
  FROM forum_group_list l, forum f
  WHERE l.group_forum_id = f.group_forum_id AND f.msg_id = ?",
  [$msg_id]
);
if (!db_numrows ($result))
  exit_error ();
$row = db_fetch_array ($result);
$forum_id = $row['group_forum_id'];
$thread_id = $row['thread_id'];
$forum_name = $row['forum_name'];
forum_header (['title' => $row['forum_name']]);
print "<p>";

$sql = "
  SELECT
    u.user_name, f.group_forum_id, f.thread_id, f.subject, f.date, f.body
  FROM forum f, user u WHERE u.user_id = f.posted_by AND f.msg_id = ?";

$result = db_execute ($sql, [$msg_id]);
if (!db_numrows ($result))
  exit_error (_('Message not found.'));
$row = db_fetch_array ($result);

# TRANSLATORS: the argument is message id.
print html_build_list_table_top ([sprintf (_('Message %s'), $msg_id)]);

print "<tr>\n<td>\n";
# TRANSLATORS: the first argument is subject, the second is user's name,
# the third is date.
printf (_('%1$s (posted by %2$s, %3$s)'), '<b>' . $row["subject"] . '</b>',
  utils_user_link ($row["user_name"]), utils_format_date ($row["date"])
);
print '<p>';
print markup_rich ($row['body']);
print "</p>\n</td>\n</tr>\n</table>\n";
# Show entire thread.
# Highlight the current message in the thread list.
$current_message = $msg_id;
print show_thread ($row['thread_id']);
print '<p>&nbsp;<p>';
if ($GLOBALS['sys_enable_forum_comments'])
  {
    print '<p id="followup">' . _("Post a followup to this message") . "</p>\n";
    show_post_form (
      $row['group_forum_id'], $row['thread_id'], $msg_id, $row['subject']
    );
  }
forum_footer ([]);
?>
