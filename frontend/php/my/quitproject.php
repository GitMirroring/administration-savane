<?php
# Leaving a group.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 1999-2000 Wallace Lee
# Copyright (C) 2004-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2023 Ineiev
#
# This file is part of Savane.
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
require_once ('../include/init.php');
require_once ('../include/session.php');
require_once ('../include/sane.php');
require_once ('../include/form.php');
require_once ('../include/sendmail.php');

session_require (['isloggedin'=>'1']);

extract (sane_import ('request', ['digits' => 'quitting_group_id']));
extract (sane_import ('post', ['true' => ['confirm', 'cancel']]));

$pending = member_check_pending (0, $quitting_group_id);

# Make sure the user is actually member of the group.
if (!$pending && !member_check (0, $quitting_group_id))
  exit_error (_("You are not member of this group."));

if ($cancel)
  session_redirect ($GLOBALS['sys_home'] . "my/groups.php");

# Mail the changes so the admins know what happened.
function notify_admins ($quitting_group_id)
{
  global $sys_mail_replyto, $sys_mail_domain, $sys_mail_admin;
  $user_name = user_getname (0, 1);
  $login = user_getname ();
  $group_name = group_getname ($quitting_group_id);
  $res_admin = db_execute ("
    SELECT u.email AS email FROM user u, user_group g
    WHERE
      g.user_id = u.user_id AND g.group_id = ? AND g.admin_flags = 'A'",
    [$quitting_group_id]
  );
  $to = '';
  while ($row_admin = db_fetch_array ($res_admin))
    $to .= "$row_admin[email],";
  # The next messages are not localized because they are not sent
  # to the current user (whose preferences we hopefully know).
  if ($to != '')
    {
      $to = substr ($to, 0, -1);
      $message =
        "This message is being sent to notify the administrators of"
        . "\ngroup $group_name that $user_name <$login>\n"
        . "has chosen to quit the group.\n";
    }
  else
    {
      # No admin; the group is orphan, it will require the assistance
      # of site admins.
      $to = "$sys_mail_admin@$sys_mail_domain";
      $message =
        "This message is being sent to notify the site administrators\n"
        . "that the last administrator of the group $group_name "
        . "($user_name <$login>)\nhas chosen to quit the group.\n\n"
        . "As result, the group is orphan.\n";
    }
  $from = "$sys_mail_replyto@$sys_mail_domain";
  $subject = "$user_name has quit the group $group_name";
  sendmail_mail (
    ['from' => $from, 'to' => $to],
    ['subject' => $subject, 'body' => $message]
  );
}

# If we get here, the user is actually member of the group.
# Ask the user to confirm quit.
# If it is just a removal from a pending for inclusion request, no need
# to confirm, the loss is not worth it.
if ($confirm || $pending)
  {
    member_remove (user_getid (), $quitting_group_id);
    if (!$pending)
      notify_admins ($quitting_group_id);
    session_redirect ($GLOBALS['sys_home'] . "my/groups.php");
  }
else
  {
    site_user_header (['title' => _("Quit a group"), 'context' => 'mygroups']);
    print form_tag ();
    print form_hidden (["quitting_group_id" => $quitting_group_id]);
    print '<span class="preinput">';
    printf (_("You are about to leave the group %s, please confirm:"),
      group_getname ($quitting_group_id)
    );
    print '</span>';
    print '<div class="center"><input type="submit" name="confirm" value="'
      . _("Confirm") . '" /> <input type="submit" name="cancel" value="'
      . _("Cancel") . "\" /></div>\n</form>\n";
  }
?>
