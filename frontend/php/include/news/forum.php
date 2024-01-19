<?php
# Forum functions.
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

# Accepts a database result handle to display a single message
# in the format appropriate for the nested messages
# second param is which row in that result set to use
require_once (dirname (__FILE__) . '/../html.php');
require_once (dirname (__FILE__) . '/../form.php');
function forum_show_a_nested_message ($result, $row = 0)
{
  $g_id =  db_result ($result, $row, 'group_id');
  # If the forum is a piece of news then get the real group_id from the
  # news_byte table.
  if ($g_id == $GLOBALS['sys_group_id'])
    {
      $f_id =  db_result ($result, $row, 'group_forum_id');
      $gr = db_execute ("
        SELECT group_id FROM news_bytes WHERE forum_id = ?", [$f_id]
      );
      $g_id = db_result ($gr, 0, 'group_id');
    }

  $ret_val = "\n<table border='0' width='100%'>\n<tr>\n<td class='boxitem'>"
    . '<strong>' . db_result ($result, $row, 'subject') . '</strong>'
    . ' (' . _("posted by") . ' <a href="' . $GLOBALS['sys_home'] . 'users/'
    . db_result ($result, $row, 'user_name') . '/">'
    . db_result ($result, $row, 'realname') . '</a>, '
    .  utils_format_date (db_result ($result, $row, 'date')) . ')'
    .  "</td>\n</tr>\n<tr>\n<td><p>"
    . markup_rich (db_result ($result, $row, 'body'));

  if ($GLOBALS['sys_enable_forum_comments'])
    $ret_val .= '</p><p><a href="' . $GLOBALS['sys_home']
      . 'forum/message.php?msg_id=' . db_result ($result, $row, 'msg_id')
      . '#followup">[ ' . _("Reply") . ' ]</a>';

  $ret_val .= "</p>\n</td>\n</tr>\n</table>\n";
  return $ret_val;
}

function forum_show_nested_messages ($thread_id, $msg_id)
{
  global $total_rows;

  $result = db_execute ("
    SELECT
      u.user_name, f.has_followups, u.realname, u.user_id, f.msg_id,
      f.group_forum_id, f.subject, f.thread_id, f.body, f.date,
      f.is_followup_to, g.group_id
    FROM forum f, user u, forum_group_list g
    WHERE
      f.thread_id = ? AND u.user_id = f.posted_by AND f.is_followup_to = ?
      AND g.group_forum_id = f.group_forum_id
    ORDER BY f.date ASC", [$thread_id, $msg_id]
  );
  $rows = db_numrows ($result);

  if (!$result || $rows <= 0)
    return '';
  $ret_val = '<ul>';
  # Iterate and show the messages in this result
  # for each message, recurse to show any submessages.
  for ($i = 0; $i < $rows; $i++)
    {
      # Increment the global total count.
      $total_rows++;
      # Show the actual nested message.
      $ret_val .= forum_show_a_nested_message ($result, $i);
      if (db_result ($result, $i, 'has_followups') > 0)
        # Call yourself if there are followups.
        $ret_val .= forum_show_nested_messages (
          $thread_id, db_result ($result, $i, 'msg_id')
        );
    }
  $ret_val .= "\n</ul>\n";
  return $ret_val;
}

# FIXME: site_project_header function should be used instead
function forum_header ($params)
{
  global $DOCUMENT_ROOT, $HTML, $group_id, $forum_name, $thread_id, $msg_id;
  global $forum_id, $REQUEST_URI, $et, $et_cookie;

  $params['group'] = $group_id;
  $params['toptab'] = 'forum';

  # NEWS ADMIN
  # This is a news item for the whole system or a for a project,
  # not a regular forum: forum are deactivated in Savannah.
  if (!$forum_id)
    return;
  # Show this news item at the top of the page.
  $result = db_execute (
    "SELECT * FROM news_bytes WHERE forum_id = ?", [$forum_id]
  );

  # If the result is empty, this is not a news item, but a forum.
  if (db_numrows ($result) < 1)
    {
      $is_news = 0;
      site_project_header ($params);
      return;
    }
  $is_news = 1;
  # Backwards shim for all "generic news" that used to be submitted
  # as of may, "generic news" is not permitted - only project-specific
  # news.

  $params['group'] = db_result ($result, 0, 'group_id');

  $group_id = db_result ($result, 0, 'group_id');
  $params['toptab'] = 'news';
  site_project_header ($params);

  print "\n<div class='indexright'>\n";
  print $HTML->box_top (_("Latest News"));
  print news_show_latest (db_result ($result, 0, 'group_id'), 5, false);
  print $HTML->box_bottom ();
  print "</div>\n<div class='indexcenter'>\n";
  print "<h2><a href='forum.php?forum_id=$forum_id'>"
    . db_result ($result, 0, 'summary') . "</a></h2>\n";
  print '<p><em>';
  $sub_by = db_result ($result, 0, 'submitted_by');
  $posted_by = utils_user_link (
    user_getname ($sub_by), user_getrealname ($sub_by)
  );
  # TRANSLATORS: the first argument is user's name, the second
  # argument is date.
  printf (_('Item posted by %1$s on %2$s.'),
      $posted_by, utils_format_date (db_result ($result, 0, 'date'))
  );
  print "</em></p>\n";
  print markup_full (db_result ($result, 0, 'details'));
  $forum_name = db_result ($result, 0, 'summary');
  print "</div>\n";
}

# Backward compatibility.
function forum_footer ($params)
{
  site_project_footer ($params);
}

# Add forums to this group.
function forum_create_forum (
  $group_id, $forum_name, $is_public = 1, $create_default_message = 1,
  $description = ''
)
{
  global $feedback;
  $fields = [
    'group_id' => $group_id, 'forum_name' => utils_specialchars ($forum_name),
    'is_public' => $is_public,
    'description' => utils_specialchars ($description)
  ];
  $result = db_autoexecute ('forum_group_list', $fields, DB_AUTOQUERY_INSERT);
  $forum_id = db_insertid ($result);

  if ($create_default_message)
    # Set up a cheap default message.
    $result2 = db_autoexecute ('forum',
      [ 'group_forum_id' => $forum_id, 'posted_by' => 100,
        'subject' => 'Welcome to $forum_name',
        'body' => 'Welcome to $forum_name', 'date' => time (),
        'is_followup_to' => 0, 'thread_id' => get_next_thread_id ()
      ],
      DB_AUTOQUERY_INSERT
    );
  return $forum_id;
}

# Take a thread_id and fetches it, then invoke show_submessages to nest
# the threads.  $et is whether or not the forum is "expanded" or in flat mode.
function show_thread ($thread_id, $et = 0)
{
  global $total_rows, $is_followup_to, $subject, $forum_id, $current_message;

  $result = db_execute ("
    SELECT
      user.user_name, forum.has_followups, forum.msg_id, forum.subject,
      forum.thread_id, forum.body, forum.date, forum.is_followup_to
    FROM forum, user
    WHERE
      forum.thread_id = ?  AND user.user_id = forum.posted_by
      AND forum.is_followup_to = 0
    ORDER BY forum.msg_id DESC", [$thread_id]
  );
  $total_rows = 0;
  $ret_val = '';
  if (db_numrows ($result) < 1)
    return 'Broken Thread';
  $title_arr = [_('Thread'), _('Author'), _('Date')];
  $ret_val .= html_build_list_table_top ($title_arr);

  $rows = db_numrows ($result);
  $is_followup_to = db_result ($result, $rows - 1, 'msg_id');
  $subject = db_result ($result, $rows - 1, 'subject');
  # Short - term compatibility fix. Leaving the iteration in for now -
  # will remove in the future. If we remove now, some messages will become
  # hidden.
  #
  # No longer iterating here. There should only be one root message per thread
  # now.  Messages posted at the thread level are shown as followups
  # to the first message.
  for ($i = 0; $i < $rows; $i++)
    {
      $total_rows++;
      $msg = '';
      if ($current_message != db_result ($result, $i, 'msg_id')) 
        $msg = '<a href="' . $GLOBALS['sys_home'] . 'forum/message.php?msg_id='
        . db_result ($result, $i, 'msg_id') . '">';
      $ret_val .= '<tr class="'. utils_altrow ($total_rows) . '"><td>' . $msg
        . html_image ("contexts/mail.png", ['height' => 12, 'width' => 12]);
      # See if this message is new or not.
      if (get_forum_saved_date ($forum_id) < db_result ($result, $i, 'date'))
        $ret_val .= '<strong>';

      $ret_val .= db_result ($result, $i, 'subject')  . "</a></td>\n"
        . '<td>' . db_result ($result, $i, 'user_name') . "</td>\n"
        . '<td>' . utils_format_date (db_result ($result, $i, 'date'))
        . "</td></tr>\n";
      # Show the body/message if requested.
      if ($et == 1)
        $ret_val .= "\n<tr class=\"" . utils_altrow ($total_rows)
          . "\"><td>&nbsp;</td>\n<td colspan='2'>"
          . nl2br (db_result ($result, $i, 'body')) . "</td><tr>\n";

      if (db_result ($result, $i, 'has_followups') > 0)
        $ret_val .= show_submessages (
          $thread_id, db_result ($result, $i, 'msg_id'), 1, $et
        );
    }
  return "$ret_val</table>\n";
}

# Recursive. Selects this message's id in this thread,
# then checks if any messages are nested underneath it.
# If there are, it calls itself, incrementing $level
# $level is used for indentation of the threads.
function show_submessages ($thread_id, $msg_id, $level,$et=0)
{
  global $total_rows, $forum_id, $current_message;

  $result = db_execute ("
    SELECT
      user.user_name, forum.has_followups, forum.msg_id, forum.subject,
      forum.thread_id, forum.body, forum.date, forum.is_followup_to
    FROM forum, user
    WHERE
      forum.thread_id = ? AND user.user_id = forum.posted_by
      AND forum.is_followup_to = ?
    ORDER BY forum.msg_id ASC",
    [$thread_id, $msg_id]
  );
  $rows = db_numrows ($result);

  if (!$result || $rows <= 0)
    return '';
  $ret_val = '';
  # Messages belonging to the same thread get the same background
  # color as the first message of that thread.
  for ($i = 0; $i < $rows; $i++)
    {
      $total_rows++;
      $ret_val .= '<tr class="'
        . utils_altrow($total_rows) . '"><td nowrap>';
      # How far should it indent?
      for ($i2 = 0; $i2 < $level; $i2++)
        $ret_val .= ' &nbsp; &nbsp; &nbsp; ';

      $ret_val .= html_image (
        "contexts/mail.png", ['height' => 12, 'width' => 12]
      );
      # If this is the message being displayed, don't show a link to it.
      if ($current_message != db_result ($result, $i, 'msg_id'))
        $ret_val .= '<a href="' . $GLOBALS['sys_home']
          . 'forum/message.php?msg_id=' . db_result ($result, $i, 'msg_id')
          . '">';
      if (
        get_forum_saved_date ($forum_id) < db_result ($result, $i, 'date')
      )
        $ret_val .= '<strong>';

      $ret_val .= db_result ($result, $i, 'subject') . "</a></td>\n"
        . '<td>' . db_result ($result, $i, 'user_name') . "</td>\n"
        . '<td>' . utils_format_date (db_result ($result, $i, 'date'))
        . "</td></tr>\n";
      # Show the body/message if requested.
      if ($et == 1)
        $ret_val .= "\n<tr class=\"" . utils_altrow ($total_rows)
          . "\"><td>&nbsp;</td>\n<td colspan='2'>"
          . nl2br (db_result ($result, $i, 'body')) . "</td><tr>\n";

      if (db_result ($result, $i, 'has_followups') > 0)
        # Call yourself, incrementing the level.
        $ret_val .= show_submessages (
          $thread_id, db_result ($result, $i, 'msg_id'), $level + 1, $et
        );
    }
  return $ret_val;
}

# Get around limitation in MySQL - Must use a separate table
# with an auto-increment.
function get_next_thread_id ()
{
  $result = db_query ("INSERT INTO forum_thread_id VALUES ('')");
  if ($result)
    return db_insertid ($result);
  print '<h1>' . _('Error') . "</h1>\n";
  print db_error ();
  exit;
}

# Return the save_date for this user.
function get_forum_saved_date ($forum_id)
{
  global $forum_saved_date;

  if ($forum_saved_date)
    return $forum_saved_date;
  $result = db_execute ("
    SELECT save_date FROM forum_saved_place
    WHERE user_id = ? AND forum_id = ?",
    [user_getid (), $forum_id]
  );
  if (db_numrows ($result) > 0)
    {
      $forum_saved_dat = db_result ($result, 0, 'save_date');
      return $forum_saved_date;
    }
  # Highlight new messages from the past week only.
  $forum_saved_date = time () - 604800;
  return $forum_saved_date;
}

function post_message (
  $thread_id, $is_followup_to, $subject, $body, $group_forum_id
)
{
  global $feedback;

  if (!$GLOBALS['sys_enable_forum_comments'])
    exit_error(_("Posting has been disabled."));
  if  (!user_isloggedin())
    {
      print '<p>' . _("You could post if you were logged in") . "</p>\n";
      return;
    }
  if (!$group_forum_id)
    exit_error (_("Trying to post without a forum ID"));
  if (!$body || !$subject)
    exit_error (_("Must include a message body and subject"));

  # See if that message has been posted already.
  $res3 = db_execute ("
    SELECT * FROM forum
    WHERE
      is_followup_to = ? AND subject = ?
      AND group_forum_id = ? AND posted_by = ?",
    [$is_followup_to, $subject, $group_forum_id, user_getid ()]
  );

  if (db_numrows ($res3) > 0)
    exit_error (
      _("You appear to be double-posting this message, since it has "
        . "the same subject\nand followup information as a prior post."));
  print db_error ();

  if ($thread_id)
    {
      if ($is_followup_to)
        {
          # Increment the parent's followup count if necessary.
          $res2 = db_execute ("
            SELECT * FROM forum
            WHERE msg_id = ?  AND thread_id = ? AND group_forum_id = ?",
            [$is_followup_to, $thread_id, $group_forum_id]
          );
          if (db_numrows ($res2) > 0)
            {
              if (db_result ($result, 0, 'has_followups') > 0)
                {
                  # Parent already is marked with followups.
                }
              else
                # Mark the parent with followups as an optimization later.
                db_execute ("
                  UPDATE forum SET has_followups = '1' WHERE msg_id = ?
                  AND thread_id = ? AND group_forum_id = ?",
                  [$is_followup_to, $thread_id, $group_forum_id]
                );
            }
          else
            exit_error (
              _("Trying to followup to a message that doesn't exist.")
            );
        }
      else
        # Should never happen except with shoddy browsers or mucking
        # with the HTML form.
        exit_error (
          _("No followup ID present when trying to post to an existing "
            . "thread.")
        );
    } # $thread_id
  else
    {
      $thread_id = get_next_thread_id ();
      $is_followup_to = 0;
    }

  $result = db_autoexecute ('forum',
    [
      'group_forum_id' => $group_forum_id, 'posted_by' => user_getid (),
      'subject' => $subject, 'body' => $body, 'date' => time (),
      'is_followup_to' => $is_followup_to, 'thread_id' => $thread_id
    ], DB_AUTOQUERY_INSERT
  );

  if (!$result)
    {
      print "INSERT FAILED" . db_error ();
      # ' '.("Posting Failed").' ';
    }
  else
    {
      #' '.("Message Posted").' ';
    }

  $msg_id = db_insertid ($result);
}

function show_post_form (
  $forum_id, $thread_id = 0, $is_followup_to = 0, $subject = ""
)
{
  global $sys_home;
  print "<center>";
  if (!user_isloggedin ())
    {
      print "\n\n<span class=\"error\">"
        . _("You could post if you were logged in") . '</span>';
      print "</center>";
      return;
    }
      if ($subject)
        {
          # If this is a followup, put a RE: before it if needed.
          if (!preg_match ('/RE:/i', $subject))
            $subject = 'RE: ' . $subject;
        }
  print form_tag (['action' => "{$sys_home}forum/forum.php"])
    . form_hidden (
        [ 'post_message' => 'y', 'forum_id' => $forum_id,
          'thread_id' => $thread_id, 'is_followup_to' => $is_followup_to]
      )
    . '<table><tr><td><strong>' . _("Subject") . ":</td><td>\n"
    . '<input type="text" name="subject" value="' . $subject
    . '" size="60" maxlength="45" />' . "\n</td></tr>\n"
    . '<tr><td><strong>' . _("Message:") . "</td><td>\n"
    . '<textarea name="body" value="" rows="25" cols="60" '
    . "wrap='SOFT'></textarea>\n</td></tr>\n"
    . "<tr><td colspan='2' align='middle'>\n<span class='warn'>"
    . "HTML tags will display in your post as text</span>\n<br />\n"
    . form_input ('submit', 'submit', _("Post Comment"))
    . "\n</td></tr></table>\n</form>\n</center>\n";
}

# Take a message id and recurse, deleting all followups.
function recursive_delete ($msg_id, $forum_id)
{
  if ($msg_id == '' || $msg_id == '0' || (strlen ($msg_id) < 1))
    return 0;
  $result = db_execute ("
    SELECT msg_id FROM forum WHERE is_followup_to = ?  AND group_forum_id = ?",
    [$msg_id, $forum_id]
   );
  $rows = db_numrows ($result);
  $count = 1;

  for ($i = 0; $i < $rows; $i++)
    $count += recursive_delete (
      db_result ($result, $i, 'msg_id'), $forum_id
    );
  $toss = db_execute (
    "DELETE FROM forum WHERE msg_id = ? AND group_forum_id = ?",
    [$msg_id, $forum_id]
  );
  return $count;
}

# Validate forum.
function validate_forum_name ($forum_name)
{
  return preg_match ('/^[a-zA-Z0-9\-]+$/', $forum_name);
}
?>
