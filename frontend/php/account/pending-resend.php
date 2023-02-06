<?php
# Resend the confirmation hash to a pending (not yet validated) user.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017, 2022  Ineiev
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
require_once ('../include/database.php');
require_once ('../include/dnsbl.php');
require_once ('../include/spam.php');
require_once ('../include/sane.php');
require_once ('../include/sendmail.php');

# Block here potential robots.
dnsbl_check ();

extract (sane_import ('get', ['name' => 'form_user']));
$res_user = db_execute ("SELECT * FROM user WHERE user_name = ?", [$form_user]);
$row_user = NULL;
if (db_numrows ($res_user) > 0)
  $row_user = db_fetch_array ($res_user);

# Only mail if pending.
if (empty ($row_user) || $row_user['status'] != 'P')
  exit_error (_("Error"), _("This account is not pending verification."));

$message =
  sprintf (_("Thank you for registering on the %s web site."), $sys_name)
  . "\n"
  . _("In order to complete your registration, visit the following URL:")
  . "\n\n$sys_https_url$sys_home"
  . "account/verify.php?confirm_hash=$row_user[confirm_hash]\n\n"
  . _("Enjoy the site.") . "\n\n";
# TRANSLATORS: the argument is the name of the system (like "Savannah").
$message .= sprintf (_("-- the %s team."), $sys_name) . "\n";
sendmail_mail (
  ['from' => "$sys_mail_replyto@$sys_mail_domain", 'to' => $row_user['email']],
  ['subject' => "$sys_name " . _("Account Registration"), 'body' => $message]
);

$HTML->header (['title' => _("Account Pending Verification")]);

print '<h2>' . _("Pending Account") . "</h2>\n";
print '<p>'
  . _("Your email confirmation has been resent. Visit the link in this\n"
      . "email to complete the registration process.")
  . "</p>\n";
print '<p><a href="'
  . $GLOBALS['sys_home'] . '">[' . _("Return to Home Page") . "]</a></p>\n";
$HTML->footer ([]);
?>
