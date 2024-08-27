<?php
# Resend the confirmation hash to a pending (not yet validated) user.
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

foreach (['init', 'database', 'spam', 'sane', 'sendmail', 'markup'] as $i)
  require_once ("../include/$i.php");

extract (sane_import ('get', ['name' => 'form_user']));
exit_if_missing ('form_user');
$user_id = user_getid ($form_user);
if (empty ($user_id))
  exit_error (markup_rich (sprintf (_('User *%s* not found.'), $form_user)));
$row_user = user_get_fields (['status', 'email'], $user_id);
# Only mail if pending.
if ($row_user['status'] != USER_STATUS_PENDING)
  exit_error (_("Error"), _("This account is not pending verification."));

# No way to get the old hash, so just generate a new one.
$confirm_hash = account_generate_confirm_hash (HASH_NEW_ACCOUNT, [], $user_id);

$message =
  sprintf (_("Thank you for registering on the %s web site."), $sys_name)
  . "\n"
  . _("In order to complete your registration, visit the following URL:")
  . "\n\n$sys_https_url$sys_home"
  . "account/verify.php?confirm_hash=$confirm_hash\n\n"
  . _("Enjoy the site.") . "\n\n";
$message .= utils_team_signature ();
sendmail_mail (
  ['to' => $row_user['email']],
  ['subject' => "$sys_name " . _("Account Registration"), 'body' => $message]
);

$HTML->header (['title' => _("Account Pending Verification")]);

print html_h (2, _("Pending Account"));
print '<p>'
  . _("Your email confirmation has been resent. Visit the link in this\n"
      . "email to complete the registration process.")
  . "</p>\n";
print '<p><a href="'
  . $GLOBALS['sys_home'] . '">[' . _("Return to Home Page") . "]</a></p>\n";
$HTML->footer ([]);
?>
