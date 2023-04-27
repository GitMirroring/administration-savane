<?php
# Resend the confirmation hash to a pending (not yet validated) user.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2023 Ineiev
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
require_once ('../include/database.php');
require_once ('../include/spam.php');
require_once ('../include/sane.php');
require_once ('../include/sendmail.php');

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
