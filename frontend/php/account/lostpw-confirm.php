<?php
# Send confirmation message for password recovery.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2004, 2005 Joxean Koret <joxeankoret--yahoo.es>
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2023 Ineiev <ineiev@gnu.org>
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

require_once ('../include/init.php');
require_once ('../include/sane.php');
require_once ('../include/session.php');
require_once ('../include/sendmail.php');
require_once ('../include/database.php');

extract (sane_import ('post', ['name' => 'form_loginname']));

# Logged users have no business here.
if (user_isloggedin ())
  session_redirect ($GLOBALS['sys_home'] . "my/");

$confirm_hash = md5 (strval (time ()) . strval (rand ()));
# Account check.
$res_user = db_execute ("
  SELECT * FROM user WHERE user_name = ? AND status = 'A'", [$form_loginname]
);
if (db_numrows ($res_user) < 1)
  {
    $res_user = db_execute ("
      SELECT status FROM user WHERE user_name = ? AND status = 'P'",
      [$form_loginname]
    );
    $msg = _("This account does not exist");
    if (db_numrows  ($res_user) > 0)
      $msg =
        _("This account hasn't been activated, please contact website "
          . "administration");

    exit_error (_("Invalid User"), $msg);
  }
$row_user = db_fetch_array ($res_user);

# Notification count check:
# This code would allow to define the number of request that can be made
# per hour.
# By default, we set it to one.
$notifications_max = 1;
$email_notifications = 0;

$res_emails = db_execute ("
  SELECT count FROM user_lostpw
  WHERE
    user_id = ? AND DAYOFYEAR(date) = DAYOFYEAR(CURRENT_DATE)
    AND HOUR(DATE) = HOUR(NOW())", [$row_user['user_id']]
);
if (db_numrows ($res_emails) < 1)
  $email_notifications = 0;
else
  {
    $row_emails = db_fetch_array ($res_emails);
    $email_notifications = strval ($row_emails[0]);
  }

if ($email_notifications == 0)
  # This would be made empty by itself. We could have the login form
  # to remove old request.
  # But sv_cleaner will take care of it.
  db_execute ("
    INSERT INTO user_lostpw VALUES (?, CURRENT_TIMESTAMP, 1)",
    [$row_user['user_id']]
  );
else
  {
    if ($email_notifications >= $notifications_max)
      exit_error (
        _("An email for your lost password has already been sent.\n"
          . "Please wait one hour and try again.")
      );
    db_execute ("
      UPDATE user_lostpw SET count = count + 1
      WHERE
        user_id = ? AND DAYOFYEAR(DATE) = DAYOFYEAR(CURRENT_DATE)
        AND HOUR(DATE) = HOUR(NOW())",
      [$row_user['user_id']]
    );
  }

db_execute ("
  UPDATE user SET confirm_hash = ? WHERE user_id = ?",
  [$confirm_hash, $row_user['user_id']]
);

# TRANSLATORS: the argument is a domain (like "savannah.gnu.org"
# vs. "savannah.nongnu.org").
$message = sprintf (
  _("Someone (presumably you) on the %s site requested\n"
    . "a password change through email verification."),
  $GLOBALS['sys_default_domain']
);
$message .= ' '
  . _("If this was not you, this could pose a security risk for the system.")
  . "\n\n";
$message .= "\n\n"
  . _("If you requested this verification, visit this URL to change your "
      . "password:")
  . "\n\n";
$message .= "$sys_https_url$sys_home"
  . "account/lostlogin.php?confirm_hash=$confirm_hash\n\n";
# FIXME: There should be a discard procedure.
$message .=
  _("In any case make sure that you do not disclose this URL to\n"
    . "somebody else, e.g. do not mail this to a public mailinglist!\n\n"
);
$message .= sprintf (_("-- the %s team."), $GLOBALS['sys_name'])
  . "\n" . sendmail_signature ();

# We should not add i18n to admin messages.
$message_for_admin =
  sprintf (
    ("Someone attempted to change a password via email verification\n"
     . "on %s\n\nSomeone is maybe trying to steal a user account.\n\n"
     . "The user affected is %s\n\n"
     . "Date: %s\n"),
    $sys_default_domain, $form_loginname, gmdate ('D, d M Y H:i:s \G\M\T')
  );

list ($fail, $gpg_error) =
  sendmail_encrypt_message ($row_user['user_id'], $message);

sendmail_mail (
  ['from' => "$sys_mail_replyto@$sys_mail_domain", 'to' => $row_user['email']],
  ['subject' => "$sys_default_domain Verification", 'body' => $message]
);

sendmail_mail (
  [ 'from' => "$sys_mail_replyto@$sys_mail_domain",
    'to' =>  "$sys_mail_admin@$sys_mail_domain"],
  [ 'subject' => "password change - $sys_default_domain",
    'body' => $message_for_admin],
  ['tracker' => "lostpw", 'skip_format_body' => true]
);

fb (_("Confirmation mailed"));

$HTML->header (['title' => _("Lost Password Confirmation")]);
print '<p>'
  . _("An email has been sent to the address you have on file.")
  . "</p>\n";
print '<p>'
  . _("Follow the instructions in the email to change your account password.")
  . "</p>\n";

if ($fail)
  {
    if (user_get_preference ("email_encrypted", $row_user['user_id']))
      print "<p><strong>$gpg_error<strong></p>";
    print '<blockquote><p>'
      . _("Note that the message was sent unencrypted.\nIn order to use "
          . "encryption, register an encryption-capable GPG key\nand set "
          . "the <b>Encrypt emails when resetting password</b> checkbox\n"
          . "in your account settings.")
      . "</p></blockquote>\n";
  }
else
  print '<p>' . _("Note that it was encrypted with your registered GPG key.")
    . "</p>\n";

$HTML->footer ([]);
?>
