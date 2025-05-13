<?php
# User public page.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Free Software Foundation, Inc.
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

# The context of this page cannot be guessed later, we have to hardcode it
define ('CONTEXT', 'people');
require_once ('include/init.php');
require_once ('include/sendmail.php');

$sanitized = sane_import_name (_("User *%s* not found."));
$user_arr = user_get_array ($sanitized);
if (empty ($user_arr))
  exit_user_not_found ($sanitized);

$extra_script_name = "/$sanitized";
$user_id = $user_arr['user_id'];

require_directory ("my");

$realname = $user_arr['realname'];
$account_status = $user_arr['status'];

# For deleted account, we will print only very basic info:
# account id, login + description as deleted account.
$is_suspended = user_status_is_removed ($account_status);

$is_squad = $user_arr['status'] == USER_STATUS_SQUAD;

function print_account_items ($user_id, $realname, $is_squad)
{
  global $usersquads, $group_data, $usergroups_groupid, $usergroups, $HTML;
  $usersquads = $group_data = [];
  list ($usergroups_groupid, $usergroups) = user_group_names ($user_id);
  # TRANSLATORS: the argument is user's name (like Assaf Gordon).
  print $HTML->box_top (
    html_h (2, sprintf (_("Open items assigned to %s"), $realname)), '', 1
  );
  my_item_list ("assignee", "0", "open", $user_id, true);
  print $HTML->box_bottom (1);
  if ($is_squad)
    return;
  print "<br />\n";
  # Meaningless for squads.
  # TRANSLATORS: the argument is user's name (like Assaf Gordon).
  print $HTML->box_top (
    html_h (2, sprintf (_("Open items submitted by %s"), $realname)), '', 1
  );
  my_item_list ("submitter", "0", "open", $user_id, true);
  print $HTML->box_bottom (1);
}

function print_resume_cell ($user_arr, $is_squad)
{
  if ($is_squad)
    return;
  if ($user_arr['people_view_skills'] != 1)
    {
      print _("This user did not enable Resume & Skills.");
      return;
    }
  print '<a href="' . $GLOBALS['sys_home']
    . 'people/resume.php?user_id=' . $user_arr['user_id'] . '">'
    . _("View Resume & Skills") . "</a>";
}

function print_email_cell ($user_arr, $is_squad)
{
  print "<a href=\"{$GLOBALS['sys_home']}sendmessage.php?touser="
    . $user_arr['user_id'] . '&cc_me=cc_me">';
  if ($is_squad || ($user_arr['email_hide'] == "1" && !user_is_super_user ()))
    print _("Send this user a mail");
  else
    print utils_email_basic ($user_arr['email'], 1);
  print '</a>';
}

function print_gpg_row ($user_arr, $user_id)
{
  global $tr_head;
  if (empty (user_get_gpg_key ($user_id)))
    return;
  print "$tr_head<td></td><td>";
  print "<a href=\"{$GLOBALS['sys_home']}people/viewgpg.php?user_id="
    . $user_arr['user_id'] . '">' . _("Download GPG Key") . '</a>';
  print "</td>\n</tr>\n";
}

function print_edit_user_link ($user_id)
{
  if (!user_is_super_user ())
    return;
  # We don't translate the text of this link because it's for
  # sysadmins only.
  $admin_url = "/siteadmin/usergroup.php?user_id=$user_id";
  print "<a href=\"$admin_url\">[Edit user]</a>";
}

function output_group_info ($user_id)
{
  global $HTML;
  $group_list = user_list_groups ($user_id, true);
  if (empty ($group_list))
    return;
  print "<br />\n";
  print $HTML->box_top (html_h (2, _("Group membership")), '', 1);
  print "<ul class='boxli'>";
  $j = 1;
  foreach ($group_list as $gid => $v)
    {
      if ($v['admin_flags'] == MEMBER_FLAGS_ADMIN)
        $color = "boxhighlight";
      else
        $color = utils_altrow ($j++);
      print '<li class="' . $color . '">';
      print "<a href=\"{$GLOBALS['sys_home']}projects/"
        . $v['unix_group_name'] . '/">' . $v['group_name'] . "</a><br />\n";
      print user_format_member_since ($v['date']);
      print "</li>\n";
    }
  print "</ul>\n";
  print $HTML->box_bottom (1);
}

function output_squad_info ($user_id)
{
  global $HTML;
  print "<br />\n" . $HTML->box_top (html_h (2, _("Members")), '', 1);
  $result = db_execute ("
    SELECT user_name, realname, user_id FROM user
    WHERE user_id IN (SELECT user_id FROM user_squad WHERE squad_id = ?)",
    [$user_id]
  );
  $j = 1;
  $items = '';
  while ($row = db_fetch_array ($result))
    {
      $items .= '<li class="' . utils_altrow ($j++) . '">';
      $items .= utils_user_link ($row['user_name'], $row['realname']);
      $items .= "</li>\n";
    }

  if ($items === '')
    print _("No member found");
  else
    print "<ul class='boxli'>$items</ul>\n";
  print $HTML->box_bottom (1);
}

function split_page ($how)
{
  return html_splitpage ($how, true);
}

site_header (
  # TRANSLATORS: the argument is user's name (like J. Random Hacker).
  ['title' => sprintf (_("%s profile"), $realname), 'context' => 'people']
);
if ($is_squad)
  print '<p>'
    . _("It is not a normal user account but a squad: it unites several\n"
        . "users as if they were one (notifications, privileges, etc).")
    . "</p>\n";
else
  print "<br />\n";
print split_page ('start');
print $HTML->box_top (html_h (2, _("General information")));

print
  "<br />\n<table width='100%' cellpadding='0' cellspacing='0' border='0'>\n";

$tr_head = "<tr valign='top'>\n";
$active = user_is_super_user () || !$is_suspended;

if ($is_suspended)
  print "$tr_head<td>" . _("Note:") . " </td>\n<td><b>"
    . user_suspended_note ($realname) . "</b></td>\n</tr>\n";
if ($active)
  # TRANSLATORS: user's id (a number) shall follow this message.
  print "$tr_head<td>" . _("Id:") . " </td>\n<td><b>#"
    . $user_arr['user_id'] . "</b></td>\n</tr>\n";

print "$tr_head<td>" . _("Display name:")
  . " </td>\n<td><b>$realname</b></td>\n</tr>\n"
  . "$tr_head<td>" . _("Login name:") . " </td>\n<td><b>"
  . $user_arr['user_name'] . "</b></td>\n</tr>\n";

if ($active)
  {
    print "$tr_head<td>" . _("Email address:") . " </td>\n<td>";
    print_email_cell ($user_arr, $is_squad);
    print "</td>\n</tr>\n";
    print "$tr_head<td>" . _("Site member since:") . "</td>\n<td><b>"
      . utils_format_date ($user_arr['add_date'])
      . "</b>\n</td>\n</tr>\n$tr_head<td>";
    print_edit_user_link ($user_id);
    print "</td>\n<td>";
    print_resume_cell ($user_arr, $is_squad);
    print "</td>\n</tr>\n";
    print_gpg_row ($user_arr, $user_id);
  } # if ($active)
print "</table>\n";
print $HTML->box_bottom ();
output_group_info ($user_id);
if ($is_squad)
  output_squad_info ($user_id);

print split_page ('middle');
if (!$is_suspended)
  print_account_items ($user_id, $realname, $is_squad);
if ($is_suspended)
  {
    $HTML->footer ([]);
    exit (0);
  }
print split_page ('end');
if (user_isloggedin ())
  sendmail_form_message (
    $sys_home . 'sendmessage.php', $user_id, true, 'floatleft'
  );
else
  print '<p class="warn floatleft">'
    . _("You could send a message if you were logged in.") . "</p>\n";
$HTML->footer ([]);
?>
