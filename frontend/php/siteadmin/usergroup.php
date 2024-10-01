<?php
# Edit user's groups, email &c.
#
# This file is part of the Savane project
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.

$incs =
  ['init', 'form', 'account', 'trackers/general', 'markup', 'trackers/data'];
foreach ($incs as $i)
  require_once ("../include/$i.php");

session_require (['group' => '1','admin_flags' => MEMBER_FLAGS_ADMIN]);

$actions = ['remove_user_from_group', 'update_user_group',
  'update_user', 'add_user_to_group', 'rename', 'delete', 'activate'
];

extract (sane_import ('request',
  [
    'digits' => ['user_id', 'comment_max_rows', 'comment_offset']
  ]
));
extract (sane_import ('post',
  [
    'strings' => [['action', $actions]],
    'name' => 'new_name',
    'true' => 'update',
    'preg' => [
      ['email', '/^[a-zA-Z\d_.+-]+@(([a-zA-Z\d-])+\.)+[a-zA-Z\d]+$/'],
      ['admin_flags', '/^[A-Z\d]+$/'],
    ]
  ]
));
form_check ('update');
if (empty ($update))
  $action = null;

if (empty ($comment_max_rows))
  $comment_max_rows = 50;

$max_rows = intval ($comment_max_rows);
$offset = intval ($comment_offset);

function contribution_nextprev ($user_id, $max_rows, $result)
{
  global $php_self;
  html_nextprev (
    "$php_self?user_id=$user_id", $max_rows, db_numrows ($result), 'comment'
  );
}

function print_contribution_heading ($user_id)
{
  if ($user_id == 100)
    return;
  print html_h (2, no_i18n ("Contributions"));
}

function tracker_query ($user_id, $tracker)
{
  $link = "<a href='/$tracker/\?\", bug_id, \"'>";
  return "
    SELECT
      CONCAT(\"$link\", 'New Item in ', '$tracker #', bug_id, ': ',
        summary, '</a>')
      as summary,
      details, spamscore, 0 as comment_id, date
    FROM $tracker
    WHERE submitted_by = $user_id
    UNION
    SELECT
      CONCAT(\"$link\", 'Comment #', bug_history_id, ' in ',
        '$tracker #', bug_id, ' (', field_name, ')</a>')
      as summary,
      old_value as details, spamscore, bug_history_id as comment_id, date
    FROM {$tracker}_history
    WHERE mod_by = $user_id";
}

function inclusion_requests_query ($user_name)
{
  return "
    SELECT
      CONCAT(\"<a href=\\\"/project/admin/history.php?group=\",
        g.unix_group_name, \"\\\">Request for inclusion in \",
        g.group_name, \"</a>\")
      as summary,
      \" \" as details, -1 as spamscore, group_history_id as comment_id,
      h.date as date
    FROM group_history h, groups g
    WHERE
      h.old_value = \"$user_name\" AND g.group_id = h.group_id
      AND h.field_name = \"User Requested Membership\"";
}

function contribution_query ($user_id, $user_name, $offset, $max_rows)
{
  $max_plus = $max_rows + 1;
  $trackers = utils_get_tracker_list ();
  $queries = [];
  foreach ($trackers as $tracker)
    $queries[] = tracker_query ($user_id, $tracker);
  if ($user_id != 100)
    $queries[] = inclusion_requests_query ($user_name);
  $query = join (" UNION ", $queries);
  return $query . " ORDER BY date DESC LIMIT $offset, $max_plus";
}

function entry_text ($entry)
{
  $comment_div = '<div class="tracker_comment">';
  $summary = $entry['summary'];
  $details = null;
  if (isset ($entry['details']))
    $details = trackers_decode_value ($entry['details']);
  if (preg_match ('/\'>New Item in/', $summary))
    $details = $comment_div . markup_full ($details) . "</div>\n";
  elseif (preg_match ('/ \(details\)<\/a>$/', $summary))
    $details = $comment_div . markup_rich ($details) . "</div>\n";
  elseif ($entry['spamscore'] < 0)
    $details = markup_rich ($details);
  elseif ($details !== null)
    $details = utils_specialchars ($details);
  return $details;
}

function output_contributions ($result, $offset, $max_rows)
{
  $i = 0;
  $defs = [];
  while ($entry = db_fetch_array ($result))
    {
      if (++$i > $max_rows)
        return $defs;
      $spam = no_i18n ('Spam score') . ' ' . $entry['spamscore'] . '; ';
      $date = utils_format_date ($entry['date'], 'natural');
      $line = "$spam$date {$entry['summary']}";
      if ($entry['spamscore'] > 4)
        $line = "<b>$line</b>";
      $entry_num = $i + $offset;
      $defs["<b>$entry_num</b>: $line"] = entry_text ($entry);
    }
  return $defs;
}

function list_user_contributions ($user_id, $user_name, $offset, $max_rows)
{
  print_contribution_heading ($user_id);
  $query = contribution_query ($user_id, $user_name, $offset, $max_rows);
  $result = db_execute ($query);
  if (db_numrows ($result) < 1)
    {
      print '<p>' . no_i18n ('No contributions found.') . "</p>\n";
      return;
    }
  contribution_nextprev ($user_id, $max_rows, $result);
  print html_dl (
    output_contributions ($result, $offset, $max_rows), 'comment_results'
  );
  contribution_nextprev ($user_id, $max_rows, $result);
}

function report_db_result ($result, $msg_err, $msg_ok)
{
  if (!$result)
    fb ($msg_err . ' ' . db_error (), 1);
  else
    fb ($msg_ok);
}

function action_activate ()
{
  global $user_id;
  db_execute ("UPDATE user SET status='A' WHERE user_id = ?", [$user_id]);
}

function action_delete ()
{
  user_delete ($GLOBALS['user_id']);
}

function action_remove_user_from_group ()
{
  global $user_id, $group_id;

  $result = member_remove ($user_id, $group_id);
  report_db_result ($result,
    no_i18n ('Error removing user:'), no_i18n ('Successfully removed user')
  );
}
function action_update_user_group ()
{
  global $admin_flags, $user_id, $group_id;

  $result = db_execute ("
    UPDATE user_group SET admin_flags = ?
    WHERE user_id = ? AND group_id = ?", [$admin_flags, $user_id, $group_id]
  );
  report_db_result (
    $result, no_i18n ('Error updating user admin flags:'),
    no_i18n ('Successfully updated user admin flags')
  );
}

function action_update_user ()
{
  global $user_id, $email;

  $result = db_execute (
    "UPDATE user SET email = ? WHERE user_id = ?",
    [preg_replace ('/\s/', "", $email), $user_id]
  );
  report_db_result ($result,
    no_i18n ('Error updating email:'), no_i18n ('Successfully updated email')
  );
}

function action_add_user_to_group ()
{
  global $user_id, $group_id;
  if (empty ($group_id))
    exit_no_group ();
  $result = member_add ($user_id, $group_id);
  report_db_result (
    $result, no_i18n ('Error adding user to group:'),
    no_i18n ('Successfully added user to group')
  );
}

function action_rename ()
{
  global $user_id, $new_name;

  if (!account_namevalid ($new_name))
    {
      fb (sprintf (no_i18n ('New account name <%s> is invalid'), $new_name, 1));
      return;
    }
  $res = user_rename ($user_id, $new_name);
  if ('' == $res)
    fb (no_i18n ('Successfully renamed account to ') . $new_name);
  else
    fb (no_i18n ("Error renaming account to <$new_name>: $res"), 1);
}

function rename_form ($user_id, $user_name)
{
  return form_tag ()
    . form_hidden (['action' => 'rename', 'user_id' => $user_id])
    . "<p>Account:\n<input type='text' title=\"" . no_i18n ("New name")
    . '" name="new_name" size="22"  value="' . $user_name
    . '" maxlength="55">' . "&nbsp;\n"
    . form_submit (no_i18n ('Rename')) . "</p>\n</form>\n";
}

function email_form ($user_id, $email)
{
  return form_tag ()
    . form_hidden (['action' => 'update_user', 'user_id' => $user_id])
    . "<p>Email:\n<input type='text' size='25' title=\"" . no_i18n ("Email")
    . "\" name='email' value=\"$email\" maxlength='55'>"
    . "&nbsp;\n" . form_submit (no_i18n ('Update')) . "</p>\n</form>\n";
}

function add_to_group_form ($user_id)
{
  return form_tag ()
    . form_hidden (["action" => "add_user_to_group", "user_id" => $user_id])
    . html_label ('group_id', no_i18n ('Add to group (group_id):'))
    . "\n&nbsp;\n" . form_input ('text', "group_id", '',  'size="17"')
    . "&nbsp;\n" . form_submit (no_i18n ('Update')) . "</p>\n</form>\n";
}

function change_passwd_link ($user_id)
{
  return "<a href=\"user_changepw.php?user_id=$user_id\">"
    . '[' . no_i18n ('Change password') . "]</a>\n";
}

function delete_account_link ($user_id)
{
  return form_tag ()
    . form_hidden (["action" => "delete", "user_id" => $user_id])
    . form_submit (no_i18n ('Delete account')) . "\n</form>\n";
}

function show_status ($user_id, $status)
{
  $labels = [
    USER_STATUS_ACTIVE => no_i18n ('Active'),
    USER_STATUS_PENDING => no_i18n ('Pending')
  ];
  $ret = "<p>Status: ";
  if (array_key_exists ($status, $labels))
    $ret .= $labels[$status];
  else
    $ret .= "[$status]";
  if ($status == USER_STATUS_PENDING)
    $ret .= ' ' . form_tag ()
      . form_hidden (['action' => 'activate', 'user_id' => $user_id])
      . form_submit (no_i18n ('Activate')) . "\n</form>\n";
  $ret .= "</p>\n";
  return $ret;
}

function account_title ($user_id)
{
  return html_h (2,  no_i18n ('Account info:')
    . " #$user_id &lt;" . user_getname ($user_id) . "&gt;");
}

function account_form ($user_id, $row_user)
{
  $ret = account_title ($user_id);
  if ($row_user['status'] == USER_STATUS_SQUAD)
    return $ret . '<p>' . no_i18n ('This is a squad.') . "</p>\n";
  $ret .= rename_form ($user_id, $row_user['user_name']);
  $ret .= show_status ($user_id, $row_user['status']);
  $ret .= email_form ($user_id, $row_user['email']);
  $ret .= '<p>' . change_passwd_link ($user_id) . '&nbsp;';
  $ret .= delete_account_link ($user_id) . "</p>\n";
  $ret .= add_to_group_form ($user_id);
  return $ret;
}

function user_group_form ($user_id, $grp_id, $admin_flags)
{
  return form_tag ()
    . form_hidden (
        [ 'action' => 'update_user_group', 'user_id' => $user_id,
          'group_id' => $grp_id
        ]
      )
    . "<br />\n" . html_label ('admin_flags', no_i18n ('Admin flags:'))
    . "\n&nbsp;\n"
    . '<input type="text" name="admin_flags" id="admin_flags" '
    . "value=\"$admin_flags\">\n&nbsp;\n"
    . form_submit (no_i18n ('Update')) . "</form>\n"
    . form_tag ()
    . form_hidden (['user_id' => $user_id, 'group_id' => $grp_id,
        'action' => 'remove_user_from_group'])
    . "<br />\n" . form_submit (no_i18n ('Remove user from group'))
    . "</form>\n";
}

function group_entry ($user_id, $status, $row_cat)
{
  $grp_id = $row_cat['group_id'];
  $grp_name = group_getname ($grp_id);
  if ($status == USER_STATUS_SQUAD)
    return "<p>\n<a href=\"/project/admin/squadadmin.php?"
      . "squad_id=$user_id&amp;group_id=$grp_id\">$grp_name</a></p>\n";
  $ret = html_h (3, $grp_name);
  return $ret . user_group_form ($user_id, $grp_id, $row_cat['admin_flags']);
}

function list_groups ($user_id, $status)
{
  print html_h (2, no_i18n ('Current Groups'));
  $res_cat = db_execute ("
    SELECT g.group_name, g.group_id, u.admin_flags FROM groups g, user_group u
    WHERE u.user_id = ? AND g.group_id = u.group_id", [$user_id]
  );

  while ($row_cat = db_fetch_array ($res_cat))
    print group_entry ($user_id, $status, $row_cat);
}

if ($user_id == 100)
  {
    $HTML->header (['title' => no_i18n ('Anonymous posts')]);
    list_user_contributions ($user_id, null, $offset, $max_rows);
    html_feedback_bottom ();
    $HTML->footer ([]);
    exit;
  }

if (in_array ($action, $actions))
  {
    $f = "action_$action";
    $f ();
  }

$HTML->header (['title' => no_i18n ('Admin: Manage user')]);
$res_user = db_execute ("SELECT * FROM user WHERE user_id = ?", [$user_id]);
if (!db_numrows ($res_user))
  {
    print '<p>';
    printf (no_i18n ('User #%s not found.'), $user_id);
    print "</p>\n";
    $HTML->footer ([]);
    exit;
  }
$row_user = db_fetch_array ($res_user);

print account_form ($user_id, $row_user);
list_groups ($user_id, $row_user['status']);

if ($row_user['status'] != USER_STATUS_SQUAD)
  list_user_contributions (
    $user_id, $row_user['user_name'], $offset, $max_rows
  );

html_feedback_bottom ();
$HTML->footer ([]);
?>
