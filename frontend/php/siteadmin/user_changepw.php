<?php
# Change user's password.
#
# This file is part of the Savane project
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.

require_once ('../include/init.php');
require_once ('../include/account.php');
session_require (['group' => '1','admin_flags' => 'A']);

extract (sane_import ('request', ['digits' => 'user_id']));
extract (sane_import ('post',
  ['true' => 'update', 'pass' => ['form_pw', 'form_pw2']]
));
form_check ('update');

$error = '';

# Return 1 if inputs are valid, else set $error and return 0.
function validate_inputs ()
{
  global $update, $error, $form_pw, $form_pw2;

  if (!$update)
    return 0;
  if (!$form_pw)
    {
      $error = no_i18n ('no password provided');
      return 0;
    }
  if ($form_pw != $form_pw2)
    {
      $error = no_i18n ('passwords don\'t match');
      return 0;
    }
  if (!account_pwvalid ($form_pw))
    {
      $error = no_i18n ('provided password is considered weak');
      return 0;
    }
  return 1;
}

$title = sprintf (no_i18n ('Change password for %s'), user_getname ($user_id));

$HTML->header (['title' => $title]);
print html_h (2, no_i18n ('Savannah Password Change'));
# Check for valid login, if so, congratulate.
if (validate_inputs ())
  {
    # If we got this far, it must be good.
    account_set_pw ($user_id, $form_pw);
    print "<p>"
      . no_i18n ("Congratulations. You have managed to change this user's\n"
          . "password.")
      . "</p>\n<p>";
    printf (
      no_i18n ('<a href="%s">Return to user list</a>.'), '/admin/userlist.php'
    );
    print "</p>\n";
  }
else
  {
    if ($error)
      $error = '<p><b>' . no_i18n ('Password change failed')
        . ": $error</b></p>\n";

    print "$error" . form_header () . '<p>'
      . html_label ('form_pw', no_i18n ('New Password:'))
      . "<br />\n" . form_input ('password', 'form_pw') . "\n</p>\n<p>"
      . html_label ('form_pw2', no_i18n ('New Password (repeat):'))
      . "<br />\n" . form_input ('password', 'form_pw2')
      . form_hidden (['user_id' => $user_id]) . "</p>\n<p>"
      . form_footer (no_i18n ('Update'));
  }
$HTML->footer ([]);
?>
