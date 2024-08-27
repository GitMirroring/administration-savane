<?php
# Manage group members.
#
#  Copyright (C) 1999, 2000 The SourceForge Crew
#  Copyright (C) 2000-2006 Derek Feichtinger <derek.feichtinger--cern.ch>
#  Copyright (C) 2003-2006 Frederik Orellana <frederik.orellana--cern.ch>
#  Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
#  Copyright (C) 2014, 2016, 2017 Assaf Gordon
#  Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
#  Copyright (C) 2013, 2014, 2017-2024 Ineiev
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
require_once ('../../include/form.php');
require_once ('../../include/sendmail.php');

extract (sane_import ('post',
  [
    'true' => 'update',
    'array' => [['user_ids', [null, 'digits']]],
    'pass' => 'words',
    'strings' =>
      [
        [
          'action',
          [
            'approve_for_group', 'remove_from_group', 'add_to_group_list',
            'add_to_group',
          ]
        ]
      ],
  ]
));
form_check ('update');
session_require (['group' => $group_id, 'admin_flags' => 'A']);
if (!$update)
  $action = '';

if (!$group_id)
  exit_no_group();

function usr_string ($usr)
{
  return "{$usr['realname']} &lt;{$usr['user_name']}&gt;";
}

function show_pending_users_list ($result, $group_id)
{
  print html_h (2, _("Users Pending for Group")) . "<p>";
  print html_label ('pending_user_list',
    _("Users that have requested to be member of the group are listed\n"
      . "here. To approve their requests, select their names and push "
      . "the button\nbelow. To discard requests, go to the next section "
      . "called &ldquo;Removing users\nfrom group.&rdquo;")
  );
  print "</p>\n";

  $select = form_tag () . form_hidden (['action' => "approve_for_group"])
    . "<select id='pending_user_list' name='user_ids[]' size='10' "
    . "multiple='multiple'>\n";

  $options = '';
  while ($usr = db_fetch_array ($result))
    $options .= form_option ($usr['user_id'], null, usr_string ($usr));

  if (empty ($options))
    {
      print '<p id="pending_user_list">' . _("None found") . "</p>\n";
      return;
    }

  print "$select$options</select>\n" . form_hidden (['group_id' => $group_id])
    . form_footer (_("Approve users for group"));
}

function show_all_users_remove_list ($result, $result2, $group_id)
{
  $exists = false;
  print html_h (2, _("Removing users from group")) . "<p>";
  print html_label ('rm_user_list',
    _("To remove users, select their names and push the button\nbelow. "
      . "The administrators of a project cannot be removed unless they "
      . "quit.\nPending users are at the bottom of the list.")
  );
  print "</p>\n";
  $select = form_tag () . form_hidden (['action' => "remove_from_group"])
    . "<select id='rm_user_list' name='user_ids[]' size='10'"
    . " multiple='multiple'>\n";

  $options = '';
  while ($usr = db_fetch_array ($result))
    $options .= form_option ($usr['user_id'], null, usr_string ($usr));

  while ($usr = db_fetch_array ($result2))
    $options .= form_option ($usr['user_id'], null,
      _("Pending:") . " " . usr_string ($usr)
    );

  if (empty ($options))
    {
      print '<p id="rm_user_list">' . _("None found") . "</p>\n";
      return;
    }
  print "$select$options</select>\n<br />\n";
  print form_hidden (['group_id' => $group_id])
    . form_footer (_("Remove users from group"));
}

function show_all_users_add_searchbox ($group_id, $previous_search)
{
  print html_h (2, _("Adding users to group"), "searchuser") . "\n<p>";
  print html_label ('words',
    _("You can search one or several users to add in the whole users\n"
      . "database with the following search tool. A list of users, "
      . "depending on the\nnames you'll type in this form, will be "
      . "generated.")
  );
  print "</p>\n" . form_tag ([], "#searchuser")
    . form_hidden (['action' => 'add_to_group_list', 'group_id' => $group_id])
    . form_input ('text', 'words', $previous_search, "size='35'")
    . "<br />\n" . form_footer (_("Search users"));
}

function look_for_non_members ($group_id, $words)
{
  $keywords = explode (' ', $words);
  list ($kw_sql, $sql_params) =
    search_keywords_in_fields (
      $keywords, ['user_name', 'realname', 'u.user_id'], 'OR'
    );
  array_unshift ($sql_params, $group_id);
  return db_execute ("
    SELECT u.user_id, user_name, realname
    FROM
      user u LEFT JOIN
        (select * from user_group ug WHERE group_id = ?) ug
      ON u.user_id = ug.user_id
    WHERE $kw_sql AND status = 'A' AND ug.onduty IS NULL
    GROUP BY u.user_id ORDER BY user_name LIMIT 26", $sql_params
    );
}

function found_user_select_box ($result, $group_id)
{
  $options = '';
  while ($usr = db_fetch_array ($result))
    $options .= form_option ($usr['user_id'], null, usr_string ($usr));
  if (empty ($options))
    return '<p id="user_add_list">' . _("None found") . "</p>\n</form>";
  return form_tag () . form_hidden (['action' => 'add_to_group'])
    . "<select id='user_add_list' name=\"user_ids[]\" size='10' "
    . "multiple='multiple'>\n$options</select>\n"
    . form_hidden (['group_id' => $group_id])
    . form_footer (_("Add users to group"));
}

function show_all_users_add_list ($group_id, $words)
{
  if (!$words)
    return;
  print "<p>"
    . html_label ('user_add_list', _("Below is the result of your search."))
    . "</p>\n";
  $result = look_for_non_members ($group_id, $words);
  print found_user_select_box ($result, $group_id);
}

if ($action == 'add_to_group' && $user_ids)
  foreach ($user_ids as $user)
    {
      $res = member_add ($user, $group_id);
      $msg = sprintf (_("User %s added to the group."), user_getname ($user));
      if ($res)
        fb ($msg);
    }

if ($action == 'remove_from_group' && $user_ids)
  foreach ($user_ids as $user)
    {
      if (member_check ($user, $group_id, "A")) # Don't remove admins.
        continue;
      member_remove ($user, $group_id);
      fb (
        sprintf (_("User %s deleted from the group."), user_getname ($user))
      );
    }

if ($action == 'approve_for_group' && $user_ids)
  {
    foreach ($user_ids as $user)
      {
        member_approve ($user, $group_id);
        if (!($email = user_get_email ($user)))
          continue;
        # As mail content sent to a user different from the one browsing the
        # page, this cannot be translated.
         $title = "Group membership approved";
         $message =
           sprintf (
             "You've been approved as a member of the group %s on %s,\n"
             . "where you are registered as %s.",
             group_getname ($group_id), $sys_name, user_getname ($user)
           )
         . "\n\n" . utils_team_signature ();

        sendmail_mail (
          ['to' => $email], ['subject' => $title, 'body' => $message]
        );
      }
  }

site_project_header (
  ['title' => _("Manage Members"), 'group' => $group_id, 'context' => 'ahome']
);

$res_pend = member_admin_flags_query ($group_id, "= ?", MEMBER_FLAGS_PENDING);

show_pending_users_list ($res_pend, $group_id);
db_data_seek ($res_pend);
print "<br />\n";
$flags = [MEMBER_FLAGS_ADMIN, MEMBER_FLAGS_PENDING, MEMBER_FLAGS_SQUAD];
$res_act = member_admin_flags_query (
  $group_id, "NOT " . utils_in_placeholders ($flags), $flags
);
show_all_users_remove_list ($res_act, $res_pend, $group_id);
print "<br />\n";
show_all_users_add_searchbox ($group_id, $words);
show_all_users_add_list ($group_id, $words);
site_project_footer ([]);
?>
