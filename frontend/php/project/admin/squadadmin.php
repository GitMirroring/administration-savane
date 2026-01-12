<?php
# Manage squads.
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

require_once ('../../include/init.php');
require_once ('../../include/account.php');

$submit_buttons = [
  'update', 'update_general', 'update_delete_step1', 'update_delete_step2',
  'add_to_squad', 'remove_from_squad'
];
extract (sane_import ('request', ['digits' => 'squad_id']));
extract (sane_import ('post',
  [
    'true' => array_merge ($submit_buttons, ['deletionconfirmed']),
    'array' => [['user_ids', ['digits', 'digits']]],
    'digits' => ['squad_id_to_delete'],
     # form_realname is sanitized further.
    'pass' => 'form_realname',
    'name' => 'form_loginname',
  ]
));

session_require (['group' => $group_id, 'admin_flags' => MEMBER_FLAGS_ADMIN]);
exit_if_no_group ();
form_check ($submit_buttons);

function finish_page ()
{
  site_project_footer ([]);
  exit (0);
}

$form_realname = account_sanitize_realname ($form_realname);
if (!account_realname_valid ($form_realname))
  $form_realname = '';

function update_squad_name ($new_name, $squad_id, $group_id, &$current_name)
{
  if (empty ($new_name))
    {
      fb (_("You must supply a non-empty real name."), 1);
      return;
    }
  $result = db_execute (
    "UPDATE user SET realname = ? WHERE user_id = ?",
    [$new_name, $squad_id]
  );
  if (db_affected_rows ($result) <= 0)
    return;

  fb (_("Squad name updated"));
  group_add_history ('Squad name update', $current_name, $group_id);
  $current_name = $new_name;
}

function confirm_squad_deletion ($group_id, $squad_id, $realname, $squad_name)
{
  site_project_header (
    ['title' => _("Manage Squads"), 'group' => $group_id, 'context' => 'ahome']
  );
  print '<p>' . _('This action cannot be undone.') . "</p>\n";

  print form_header ();
  print form_hidden (["group_id" => $group_id]);
  print form_hidden (["squad_id_to_delete" => $squad_id]);
  print '<p><span class="preinput">'
    . _("Do you really want to delete this squad account?")
    . "</span></p>\n";
  print "<p>$realname &lt;$squad_name&gt;&nbsp;&nbsp;";
  print form_checkbox ("deletionconfirmed", 0, ['value' => "yes"])
    . ' ' . _("Yes, I really do") . "</p>\n";
  print form_submit (_("Update"), "update_delete_step2");
  finish_page ();
}

if ($squad_id)
  {
    # A squad passed? Allow to add and remove member, to
    # change the squad name or to delete it.
    $result = member_admin_flags_query ($group_id,
      '= ? AND u.user_id = ?', [MEMBER_FLAGS_SQUAD, $squad_id]
    );
    if (!db_numrows ($result))
      exit_error (_("Squad not found"));

    $realname = db_result ($result, 0, 'realname');
    $squad_name = db_result ($result, 0, 'user_name');

    if ($update_general)
      update_squad_name ($form_realname, $squad_id, $group_id, $realname);

    if ($update_delete_step1)
      confirm_squad_deletion ($group_id, $squad_id, $realname, $squad_name);

    if ($add_to_squad && !empty ($user_ids))
      foreach ($user_ids as $user)
        {
          $ok = member_add_to_squad ($user, $squad_id, $group_id);
          if ($ok)
            # TRANSLATORS: the argument is user's name.
            $str = _("User %s added to the squad.");
          else
            # TRANSLATORS: the argument is user's name.
            $str = _("User %s is already part of the squad.");
          fb (sprintf ($str, user_getname ($user)), !$ok);
        }

    if ($remove_from_squad && !empty ($user_ids))
      foreach ($user_ids as $user)
        {
          $ok = member_remove_from_squad ($user, $squad_id, $group_id);
          if ($ok)
            # TRANSLATORS: the argument is user's name.
            $str = _("User %s removed from the squad.");
          else
            # TRANSLATORS: the argument is user's name.
            $str = _("User %s is not part of the squad.");
          fb (sprintf ($str, user_getname ($user)), !$ok);
        }

    site_project_header (
      [ 'title' => _("Manage Squads"), 'group' => $group_id,
        'context' => 'ahome']
    );

    print form_header ();
    print form_hidden (["group_id" => $group_id, "squad_id" => $squad_id]);
    print '<p><span class="preinput">'
      . html_label ("form_realname", _("Display name:"))
      . "</span><br />\n&nbsp;&nbsp;";
    print form_input ("text", "form_realname", $realname)
      . " &lt;$squad_name&gt;</p>\n";
    print form_submit (_("Update"), "update_general") . ' '
      . form_submit (_("Delete squad"), "update_delete_step1") . "</form>\n";

    print html_h (2, _("Removing members"));

    $result_delusers = db_execute ("
      SELECT u.user_id, user_name, realname
      FROM user u JOIN user_squad s ON u.user_id = s.user_id
      WHERE s.squad_id = ?  ORDER BY user_name", [$squad_id]
   );

    print "<p>"
      . _("To remove members from the squad, select their names and push the "
          . "button\nbelow.")
      . "</p>\n";
    print form_header ();
    print form_hidden (["group_id" => $group_id, "squad_id" => $squad_id]);
    $sel_head = '&nbsp;&nbsp;<select title="' . _("Users")
      . '" name="user_ids[]" size="10" multiple="multiple">';
    $select = $sel_head;
    $exists = false;
    $already_in_squad = [];
    while ($thisuser = db_fetch_array ($result_delusers))
      {
        $select .= form_option ($thisuser['user_id'], null,
          $thisuser['realname'] . ' &lt;' . $thisuser['user_name'] . "&gt;"
        );
        $already_in_squad[$thisuser['user_id']] = true;
        $exists = true;
      }
    $users_none_found =
      html_h (3, _("Users")) . "\n<p>" . _("None found") . "</p>\n";

    if ($exists)
      print "$select</select><br />\n";
    else
      print $users_none_found;
    print form_submit (_("Remove Members"), "remove_from_squad") . "</form>\n";
    print html_h (2, _("Adding members"));
    $result_addusers =  member_admin_flags_query ($group_id,
      'NOT IN (?, ?)', [MEMBER_FLAGS_PENDING, MEMBER_FLAGS_SQUAD]
    );
    print "<p>"
      . _("To add members to the squad, select their name and push the button "
          . "below.")
      . "</p>\n";
    print form_header ();
    print form_hidden (["group_id" => $group_id, "squad_id" => $squad_id]);
    $select = $sel_head;
    $exists = false;
    while ($thisuser = db_fetch_array ($result_addusers))
      {
        # Ignore if previously found as member.
        if (array_key_exists ($thisuser['user_id'], $already_in_squad))
          continue;
        $select .= form_option ($thisuser['user_id'], null,
          "{$thisuser['realname']}" . " &lt;{$thisuser['user_name']}&gt;"
        );
        $exists = true;
      }

    if ($exists)
      print "$select</select><br />\n";
    else
      print $users_none_found;
    print form_submit (_("Add Members"), "add_to_squad") . "</form>\n";
    print html_h (2, _("Setting permissions"));
    print "<p><a href=\"userperms.php?group=$group#$squad_name\">"
      . _("Set Permissions") . "</a></p>\n";
    finish_page ();
  } # if ($squad_id)

# No $squad_id.  List existing squads, allow to create one.
function validate_loginname ($form_loginname, $group)
{
  if (!account_namevalid ($form_loginname))
    return false; # Feedback included by the check function.

  $res = db_execute (
    "SELECT user_id FROM user WHERE user_name LIKE ?",
     ["$group-$form_loginname"]
  );
  if (db_numrows ($res) > 0)
    {
      fb (_("That username already exists."), 1);
      return false;
    }

  $res = db_execute (
    "SELECT group_list_id FROM mail_group_list WHERE list_name LIKE ?",
    ["$group-$form_loginname"]
  );
  if (db_numrows ($res) <= 0)
    return true;
  fb (_("That username may conflict with mailing list addresses."), 1);
  return false;
}

function create_squad (&$form_loginname, &$form_realname, $group)
{
  global $group_id, $sys_mail_replyto, $sys_mail_domain;

  $result = db_autoexecute (
    'user',
    [
      'user_name' => strtolower ($group . "-" . $form_loginname),
      'user_pw' => '!', 'realname' => $form_realname, 'people_resume' => '',
      'email' => "{$sys_mail_replyto}@{$sys_mail_domain}",
      'add_date' => time (), 'status' => USER_STATUS_SQUAD, 'email_hide' => 1,
    ],
    DB_AUTOQUERY_INSERT
  );
  if (db_affected_rows ($result) <= 0)
    {
      fb (_("Error during squad creation"));
      return;
    }
  fb (_("Squad created"));
  $created_squad_id = db_insertid ($result);
  member_add ($created_squad_id, $group_id, MEMBER_FLAGS_SQUAD);

  # Clear variables so the form below will be empty.
  $form_loginname = $form_realname = null;
}

if ($update)
  {
    if (!$form_loginname)
      fb (_("You must supply a username."), 1);
    if (!$form_realname)
      fb (_("You must supply a non-empty real name."), 1);

    if (
      $form_loginname && $form_realname
      && validate_loginname ($form_loginname, $group)
    )
      create_squad ($form_loginname, $form_realname, $group);
  }

if ($update_delete_step2 && $deletionconfirmed == "yes")
  {
    $squad_id_to_delete = $squad_id_to_delete;
    $delete_result = member_admin_flags_query ($group_id,
       '= ? AND u.user_id = ?', [MEMBER_FLAGS_SQUAD, $squad_id_to_delete]
    );
    if (!db_numrows ($delete_result))
      exit_error (_("Squad not found"));

    fb (_("Squad deleted"));
    member_remove ($squad_id_to_delete, $group_id);
  }

$result = member_admin_flags_query ($group_id, '= ?', MEMBER_FLAGS_SQUAD);
$rows = db_numrows ($result);

site_project_header (
  ['title' => _("Manage Squads"), 'group' => $group_id, 'context' => 'ahome']
);

print '<p>'
  . _("Squads can be assigned items, share permissions. Creating squads is "
    . "useful\nif you want to assign some items to several members at once.")
  . "</p>\n";

print html_h (2, _("Squad List"), ['id' => 'form']);

if ($rows < 1)
  print '<p class="warn">' . _("None found") . "</p>\n";
else
  {
    print "<ul>\n";
    while ($squad = db_fetch_array ($result))
      print "<li><a href=\"?squad_id={$squad['user_id']}&amp;"
        . "group_id=$group_id\">{$squad['realname']} "
        . "&lt;{$squad['user_name']}&gt;</a></li>\n";
    print "</ul>\n";
  }

# Limit squad creation to the group size (yes, one can easily override this
# restriction by creating fake users, but the point is only to incitate
# to create squads only if necessary, not to really enforce something
# important).
print html_h (2, _("Create a New Squad"));

$result = member_admin_flags_query (
  $group_id, 'NOT IN (?, ?)', [MEMBER_FLAGS_PENDING, MEMBER_FLAGS_SQUAD]
);
if ($rows < db_numrows ($result))
  {
    print form_header ("$php_self#form");
    print form_hidden (["group_id" => $group_id]);
    print '<p><span class="preinput">'
      . html_label ("form_loginname", _("Squad login name:"))
      . "</span>\n<br />&nbsp;&nbsp;";
    print "$group-" . form_input ("text", "form_loginname", $form_loginname)
      . "</p>\n";
    print '<p><span class="preinput">'
      . html_label ("form_realname", _("Squad full name:"))
      . "</span>\n<br />&nbsp;&nbsp;";
    print form_input ("text", "form_realname", $form_realname) . "</p>\n";
    print form_footer ();
  }
else
  print '<p class="warn">'
    . _("You cannot have more squads than members") . "</p>\n";
finish_page ();
?>
