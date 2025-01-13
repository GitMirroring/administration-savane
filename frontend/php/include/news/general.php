<?php
# News-related functions.
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

# is_approved values:
# 5 - newly submitted
# 4 - deleted
# 2 - "refused" news in sys_group (obsolete, should be 4)
# 1 - approved for forge front page (obsolete, should be 0)
# 0 - approved for group main page

function news_format_atom_entry ($row)
{
  global $sys_default_domain, $sys_home;
  $id = "https://$sys_default_domain{$sys_home}news/?id={$row['id']}";
  $title = $row['summary'];
  $updated = date ('c', $row['date']);
  $author = $row['realname'];
  $text = str_replace ('&nbsp;', ' ', markup_full (trim ($row['details'])));
  return "
  <entry>
    <id>$id</id>
    <link rel='alternate' href='$id'/>
    <title>$title</title>
    <updated>$updated</updated>
    <author>
      <name>$author</name>
    </author>
    <content type='xhtml' xml:base='$id'>
      <div xmlns='http://www.w3.org/1999/xhtml'>$text</div>
    </content>
  </entry>\n";
}

function news_fetch_item ($news_id)
{
  $result = db_execute ("SELECT * from news_bytes WHERE id = ?", [$news_id]);
  if (!db_numrows ($result))
    return null;
  $row = db_fetch_array ($result);
  $group = project_get_object ($row['group_id']);
  if ($group->isError ())
    return null;
  return $row;
}

function news_show_news_item ($item, $level = 2)
{
  if (empty ($item))
    {
      fb (_("No news item found"), 1);
      return;
    }
  print html_h ($level, $item['summary']);
  print "<p><i>";
  # TRANSLATORS: the first argument is user's name, the second
  # argument is date.
  printf (_('Item posted by %1$s on %2$s.'),
    news_submitted_by_link ($item['submitted_by']),
    utils_format_date ($item['date'])
  );
  print "</i></p>\n";
  print markup_full ($item['details']);
}

function news_query_news ($group_id, $set)
{
  $timeout = time () - 86400 * 15;
  return db_execute ("
      SELECT * FROM news_bytes
      WHERE is_approved IN ($set) AND date > ? AND group_id = ?",
      [$timeout, $group_id]
    );
}

function news_print_news_list ($result, $group_id, $group)
{
  global $php_self;
  print "<ul>\n";
  while ($row = db_fetch_array ($result))
    {
      print "<li>";
      print "<a href=\"$php_self?show=1&amp;group=$group&amp;";
      print "id={$row['id']}\">{$row['summary']}</a></li>\n";
    }
  print "</ul>\n";
}

function news_list_news_to_manage ($group_id, $group)
{
  $news_sets = [
    '5' => [_("News waiting for approval"), _("No news waiting for approval")],
    '4' => [_("Recently deleted news"), _("No recently deleted news")],
    '0, 1' => [_("Recently approved news"), _("No recently approved news")]
  ];
  foreach ($news_sets as $set => $t)
    {
      $result = news_query_news ($group_id, $set);
      if (!db_numrows ($result))
        {
          print html_h (2, $t[1]);
          continue;
        }
      print html_h (2, $t[0]);
      news_print_news_list ($result, $group_id, $group);
    }
}

function news_send_notification ($group, $item)
{
  global $sys_mail_replyto, $sys_mail_domain;
  $res = db_execute ("
    SELECT new_news_address FROM groups WHERE group_id = ?",
    [$group['id']]
  );
  $to = db_result ($res, 0, 'new_news_address');
  $from = "<$sys_mail_replyto@$sys_mail_domain>";
  $res = db_execute ("
    SELECT submitted_by FROM news_bytes WHERE id = ? AND group_id = ?",
    [$item['id'], $group['id']]
  );
  if (db_numrows ($res) > 0)
    $from = user_getrealname (db_result ($res, 0, 'submitted_by'), 1)
      . " $from";
  sendmail_mail (
    ['from' => $from, 'to' => $to],
    ['subject' => $item['summary'], 'body' => markup_ascii ($item['details'])],
    ['group' => $group['name'], 'tracker' => 'news']
  );
}

function news_update_news_item ($group, $item)
{
  $new_status = $item['status'];
  if (!in_array ($new_status, [4, 5]))
    $new_status = 0;
  $fields = ['is_approved' => $new_status, 'date_last_edit' => time (),
    'summary' => $item['summary'], 'details' => $item['details']
  ];
  $result = db_autoexecute ('news_bytes', $fields, DB_AUTOQUERY_UPDATE,
    "id = ? AND group_id = ?", [$item['id'], $group['id']]
  );
  if ($result)
    fb (_("Group news item updated"));
  else
    fb (_("Failed to update"), 1);
  if ($new_status == 0)
    # Send mails; don't care if it was already approved.
    news_send_notification ($group, $item);
}

function news_submitted_by_link ($submitted_by)
{
  $s_by = "None";
  if ($submitted_by)
    $s_by = user_getname ($submitted_by);
   return utils_user_link ($s_by, user_getrealname ($submitted_by));
}

function news_print_submitter_link ($submitted_by)
{
  print '<p>' . _("Submitter:") . ' '
    . news_submitted_by_link ($submitted_by) . "</p>\n";
}

function news_print_status_button ($label, $name, $value, $checked)
{
  print "\n&nbsp;&nbsp;";
  print form_radio ('status', $value,
    ["id" => "status_$name", 'label' => $label, 'checked' => $checked]
  );
}
function news_print_status_selector ($status)
{
  $defer = $status == 5;
  print "<p>";
  news_print_status_button (_("Display"), 'display', '0', !$defer);
  print "<br />\n";
  news_print_status_button (_("Delete"), 'delete', '4', false);
  if ($defer)
    {
      print "<br />\n";
      news_print_status_button (_("Approve later"), 'defer', '5', true);
    }
  print "</p>";
}

function news_print_news_fields ($summary, $details)
{
  print "<span class='preinput'>" . html_label ('summary', _("Subject:"))
    . "</span><br />\n&nbsp;&nbsp;\n"
    . '<input type="text" name="summary" id="summary" value="'
    . "$summary\" size='65' maxlength='80' /><br />\n"
    . '<span class="preinput">' . html_label ('details', _("Details")) . "\n"
    . markup_info ("full") . "</span><br />\n&nbsp;&nbsp;\n"
    . '<textarea name="details" id="details" rows="20" cols="65" wrap="soft">'
    . "$details</textarea>\n";
}

function news_print_approve_form ($row)
{
  news_print_submitter_link ($row['submitted_by']);
  print form_tag ();
  print form_hidden (
    [ 'id' =>  $row['id'], 'group_id' => $row['group_id'],
      'approve' => 'y', 'post_changes' => 'y']
  );
  news_print_status_selector ($row['is_approved']);
  news_print_news_fields ($row['summary'], $row['details']);
  print '<p>' . form_submit (_("Submit"), "submit") . "</p>\n</form>\n";
  print html_h (2, _("Preview"));
  news_show_news_item ($row, 3);
}

function news_new_subbox ($row)
{
  if ($row <= 0)
    return '';
  return '</div><div class="' . utils_altrow ($row + 1) . '">';
}

# Request either first $limit approved news items (when $start_from <= 0),
# or all approved items starting from $start_from.
function news_get_news ($group_id, $start_from, $limit, $news_n)
{
  $sql = "
    SELECT user_name, id, forum_id, summary, details, date
    FROM user, news_bytes
    WHERE
      is_approved NOT IN (4, 5) AND group_id = ? AND user_id = submitted_by
    ORDER BY date DESC LIMIT ";
  $params = [$group_id];
  if ($start_from > 0)
    {
      $sql .= "?, ?";
      $params[] = $start_from;
      $params[] = intval ($news_n);
    }
  else
    {
      $sql .= "?";
      $params[] = $limit;
    }
  return db_execute ($sql, $params);
}

# Return the position to truncate the string at,
# or -1 when the string is short enough.
function news_break_details ($story)
{
  if (strlen ($story) < 500)
    return -1;
  # If there is a linebreak close to the 250 character mark, we use it
  # to truncate the news item, so that the markup will not be confused.
  # We accept the range from 240 to 350 characters, else
  # the news item will be split on whitespace.
  $linebreak = strpos ($story, "\n", 240);
  if ($linebreak !== false && $linebreak < 350)
    return $linebreak;
  $truncate = strrpos (substr ($story, 0, 350), ' ');
  if ($truncate === false)
    $truncate = 300;
  return $truncate;
}

# Markup the string without trailing line breaks.
function news_markup_details ($story)
{
  $ret = markup_full ($story);
  return preg_replace ("/(<br \\/>\s*)*((<\\/p>\s*)?)$/", '$2', $ret);
}

function news_item_url ($rows, $replies)
{
  $ret = [];
  foreach ($rows as $row)
    {
      $reply = $replies[$row['forum_id']];
      if ($reply === '')
        $url = "news/?id={$row['id']}";
      else
        $url = "forum/forum.php?forum_id={$row['forum_id']}";
      $ret[] = $GLOBALS['sys_home'] . $url;
    }
  return $ret;
}

function news_link ($row, $reply)
{
  list ($url) = news_item_url ([$row], $reply);
  return "<a href=\"$url\">[...]</a>";
}

function news_format_details ($row, $reply)
{
  global $sys_home;
  $story = trim ($row['details']);
  $truncate = news_break_details ($story);
  if ($truncate < 0)
    return news_markup_details ($story);
  $sub = substr ($story, 0, $truncate);
  $story = news_markup_details (substr ($story, 0, $truncate));
  # Put the "Read more" link into the last <p>.
  if (!preg_match ("/<\\/p>\s*$/", $story, $matches, PREG_OFFSET_CAPTURE))
    $matches = [0 => ['', strlen ($story)]];
  $head = substr ($story, 0, $matches[0][1]);
  $tail = $matches[0][0];
  $link = news_link ($row, [$row['forum_id'] => $reply]);
  return "$head\n$link$tail\n";
}

function news_reply_count ($forum_ids)
{
  $ret = [];
  if (empty ($forum_ids))
    return $ret;
  $result = db_execute ("
    SELECT group_forum_id AS forum_id, count(msg_id) AS cnt FROM forum
    WHERE group_forum_id " . utils_in_placeholders ($forum_ids) . "
    GROUP BY group_forum_id", $forum_ids
  );
  $cnt = [];
  while ($row = db_fetch_array ($result))
    $cnt[$row['forum_id']] = $row['cnt'];
  foreach ($forum_ids as $id)
    {
      $ret[$id] = '';
      if (!array_key_exists ($id, $cnt))
        continue;
      $c = $cnt[$id];
      $ret[$id] = ' - ' . sprintf (ngettext ("%s reply", "%s replies", $c), $c);
    }
  return $ret;
}

function news_list_forum_ids ($rows)
{
  $ret = [];
  foreach ($rows as $r)
    $ret[] = $r['forum_id'];
  return $ret;
}

function news_format_item ($row, $link, $reply, $show_details)
{
  global $sys_home;
  $ret = "<a href=\"$link\"><b>{$row['summary']}</b></a>";
  $det = '';
  if ($show_details)
    {
      $ret .= "<br />\n&nbsp;&nbsp;&nbsp;&nbsp;";
      $det = news_format_details ($row, $reply);
    }
  $uname = $row['user_name'];
  $ret .= ' <span class="smaller"><em>' . _("posted by")
    . " <a href=\"{$sys_home}users/$uname\">$uname</a>, "
    . utils_format_date ($row['date']) . "$reply</em></span>\n$det";
  return $ret;
}

function news_list_items ($result, $show_details)
{
  if (!db_numrows ($result))
    return ['<p><strong>' . _("No news found") . "</strong></p>\n", -1];
  $rows = [];
  for ($n = 0; $row = db_fetch_array ($result); $n++)
    $rows[] = $row;
  $ret = '';
  $reply = news_reply_count (news_list_forum_ids ($rows));
  $link = news_item_url ($rows, $reply);
  for ($i = 0; $i < $n; $i++)
    $ret .= news_new_subbox ($i)
      . news_format_item (
          $rows[$i], $link[$i], $reply[$rows[$i]['forum_id']], $show_details
        );
  return [$ret, $n];
}

function news_bottom_link ($group_id, $news_n)
{
  global $sys_home;
  return "<a href=\"{$sys_home}news/?group_id=$group_id"
    . '"><span class="smaller">['
    . sprintf (
        ngettext ("%d news in archive", "%d news in archive", $news_n),
        $news_n
      )
    . ']</span></a>';
}

# Show a simple list of the latest news items with a link to the forum.
# When $start_from is negative, the bottom link is skipped.
function news_show_latest (
  $group_id, $limit = 10, $show_details = true, $start_from = 0
)
{
  global $sys_group_id;
  if (empty ($group_id))
    $group_id = $sys_group_id;

  $news_n = news_total_number ($group_id);
  $result = news_get_news ($group_id, $start_from, $limit, $news_n);
  list ($return, $n) = news_list_items ($result, $show_details);

  if ($start_from < 0 || $n < 0)
    return $return;
  $return .= news_new_subbox ($n);
  return $return . news_bottom_link ($group_id, $news_n);
}

function news_total_number ($group_id)
{
  $sql = "
    SELECT count(id) FROM news_bytes n, groups g
    WHERE
      is_approved NOT IN (4, 5)
      AND n.group_id = ? AND n.group_id = g.group_id";
  return db_result (db_execute ($sql, [$group_id]), 0, 0);
}

# Take an ID and returns the corresponding forum name.
function get_news_name ($id)
{
  $result = db_execute ("SELECT summary FROM news_bytes WHERE id = ?", [$id]);
  if (db_numrows ($result) < 1)
    return _("Not found");
  return db_result ($result, 0, 'summary');
}
?>
