<?php
# Manage group members.
#
#  Copyright (C) 1999, 2000 The SourceForge Crew
#  Copyright (C) 2000-2006 Derek Feichtinger <derek.feichtinger--cern.ch>
#  Copyright (C) 2003-2006 Frederik Orellana <frederik.orellana--cern.ch>
#  Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
#  Copyright (C) 2014, 2016, 2017 Assaf Gordon
#  Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
#  Copyright (C) 2013, 2014, 2017-2023 Ineiev
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
require_once ('../../include/init.php');
require_once ('../../include/form.php');
require_once ('../../include/sendmail.php');

extract (sane_import ('post',
  [
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

session_require (['group' => $group_id, 'admin_flags' => 'A']);

if (!$group_id)
  exit_no_group();

function show_pending_users_list ($result, $group_id)
{
  print "<h2>" . _("Users Pending for Group") . "</h2>\n<p>"
    . _("Users that have requested to be member of the group are listed\n"
        . "here. To approve their requests, select their names and push "
        . "the button\nbelow. To discard requests, go to the next section "
        . "called &ldquo;Removing users\nfrom group.&rdquo;")
    . "</p>\n";

  print form_tag () . form_hidden (['action' => "approve_for_group"])
    . "<select title=\"" . _("Users")
    . "\" name=\"user_ids[]\" size=\"10\" multiple>\n";

  $exists = false;
  while ($usr = db_fetch_array ($result))
    {
      print "<option value='{$usr['user_id']}'>{$usr['realname']}"
        . " &lt;{$usr['user_name']}&gt;</option>\n";
      $exists = true;
    }

  if (!$exists)
    print '<option>' . _("None found") . "</option>\n";

  print "</select>\n"
    . "<input type=\"hidden\" name=\"group_id\" value=\"$group_id\" />\n"
    . "<p>\n<input type=\"submit\" name=\"Submit\" value=\""
    . _("Approve users for group") . "\" />\n</p>\n</form>\n";
}

function show_all_users_remove_list ($result, $result2, $group_id)
{
  $exists = false;
  print "<h2>" . _("Removing users from group") . "</h2>\n<p>"
    . _("To remove users, select their names and push the button\nbelow. "
        . "The administrators of a project cannot be removed unless they "
        . "quit.\nPending users are at the bottom of the list.")
    . "</p>\n";
  print form_tag () . form_hidden (['action' => "remove_from_group"])
    . "<select title=\"" . _("Users")
    . "\" name=\"user_ids[]\" size=\"10\" multiple>\n";

  while ($usr = db_fetch_array($result))
    {
      if (member_check ($usr['user_id'], $group_id, "A"))
        continue;
      print "<option value='{$usr['user_id']}'>{$usr['realname']}"
        . " &lt;{$usr['user_name']}&gt;</option>\n";
      $exists = true;
    }

  while ($usr = db_fetch_array($result2))
    {
      if (member_check($usr['user_id'], $group_id, "A"))
        continue;
      print "<option value='{$usr['user_id']}'>" . _("Pending:") . " "
        . "{$usr['realname']} &lt;{$usr['user_name']}&gt;</option>\n";
      $exists = true;
    }

  if (!$exists)
    print '<option>' . _("None found") . "</option>\n";

  print "</select>\n<br />\n"
    . "<input type=\"hidden\" name=\"group_id\" value=\"$group_id\" />\n"
    . "<p>\n<input type=\"submit\" name=\"Submit\" value=\""
    . _("Remove users from group") . "\" />\n</p></form>\n";
}

function show_all_users_add_searchbox ($group_id, $previous_search)
{
  print '<h2 id="searchuser">' . _("Adding users to group") . "</h2>\n<p>"
    . _("You can search one or several users to add in the whole users\n"
        . "database with the following search tool. A list of users, "
        . "depending on the\nnames you'll type in this form, will be "
        . "generated.")
    . "</p>\n" . form_tag ([], "#searchuser")
    . form_hidden (['action' => 'add_to_group_list'])
    . "<input type='text' size='35' title=\"" . _("Search users")
    . '" name="words" value="' . utils_specialchars ($previous_search)
    . "\" /><br />\n<p>\n<input type='hidden' name='group_id' value='"
    . "$group_id' />\n<input type='submit' name='Submit' value=\""
    . _("Search users") . "\" />\n</p>\n</form>\n";
}

function show_all_users_add_list ($result, $group_id)
{
  print _("Below is the result of your search in the users database.")
    . "\n";
  print form_tag () . form_hidden (['action' => 'add_to_group'])
    . "<select title=\"" . _("Users")
    . "\" name=\"user_ids[]\" size=\"10\" multiple>\n";
  $exists = false;

  while ($usr = db_fetch_array($result))
    {
      print "<option value='{$usr['user_id']}'>{$usr['realname']}"
        . " &lt;{$usr['user_name']}&gt;</option>\n";
      $exists = true;
    }

  if (!$exists)
    print '<option>' . _("None found") . "</option>\n";

  print "</select>\n<br />\n<input type=\"hidden\" name=\"group_id\" value=\""
    . "$group_id\" />\n<p>\n<input type=\"submit\" name=\"Submit\" value=\""
    . _("Add users to group") . "\" />\n</p>\n</form>\n";
}

if ($action == 'add_to_group' && $user_ids)
  foreach ($user_ids as $user)
    {
      member_add($user, $group_id);
      fb(sprintf(_("User %s added to the group."), user_getname($user)));
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
            group_getname ($group_id), $sys_name,
            user_getname ($user)
          )
          . "\n\n" . sprintf ("-- the %s team.", $sys_name) . "\n";

        sendmail_mail (
          ['from' => "$sys_mail_replyto@$sys_mail_domain", 'to' => $email],
          ['subject' => $title, 'body' => $message]
        );
      }
  }

site_project_header (
  ['title' => _("Manage Members"), 'group' => $group_id, 'context' => 'ahome']
);

$result =  db_execute ("
  SELECT user.user_id, user.user_name, user.realname
  FROM user, user_group
  WHERE
    user.user_id = user_group.user_id
    AND user_group.group_id = ? AND admin_flags = 'P'
  ORDER BY user.user_name", array($group_id));

show_pending_users_list ($result, $group_id);
print "<br />\n";

$result =  db_execute ("
  SELECT
    user.user_id, user.user_name, user.realname
  FROM user, user_group
  WHERE
    user.user_id = user_group.user_id
    AND user_group.group_id = ? AND admin_flags <> 'A'
    AND admin_flags <> 'P' AND admin_flags <> 'SQD'
  ORDER BY user.user_name",
  array($group_id)
);

$result2 =  db_execute ("
  SELECT
    user.user_id, user.user_name, user.realname
  FROM user, user_group
  WHERE
    user.user_id = user_group.user_id
    AND user_group.group_id = ? AND admin_flags = 'P'
    AND admin_flags <> 'SQD'
  ORDER BY user.user_name",
  array($group_id)
);

show_all_users_remove_list ($result, $result2, $group_id);
print "<br />\n";

if ($words)
  {
    $keywords = explode(' ',$words);
    list($kw_sql, $kw_sql_params) =
      search_keywords_in_fields (
        $keywords,['user_name', 'realname', 'user_id'], 'OR'
      );
    $result = db_execute ("
      SELECT user_id, user_name, realname
      FROM user
      WHERE $kw_sql AND (status = 'A')
      ORDER BY user_name LIMIT 0,26",
      $kw_sql_params
    );
  }
show_all_users_add_searchbox ($group_id, $words);

if ($words)
  show_all_users_add_list ($result, $group_id);

site_project_footer ([]);
?>
