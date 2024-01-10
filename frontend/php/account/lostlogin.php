<?php
# URL sent by mail to recover a passphrase (not a login, despite the name).
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
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
require_once ('../include/account.php');
require_once ('../include/form.php');

extract (sane_import ('request', ['hash' => 'confirm_hash']));
extract (sane_import ('post',
  [
    'true' => 'update',
    'pass' => ['form_pw', 'form_pw2']
  ]
));

if ($update)
  form_check ();

$res_lostuser = db_execute (
  "SELECT * FROM user WHERE confirm_hash = ?", [$confirm_hash]
);
if (db_numrows ($res_lostuser) > 1)
  exit_error (_("Error"),
    # TRANSLATORS: confirmation hash is a secret code mailed to the user.
    _("This confirmation hash exists more than once.")
  );
if (db_numrows ($res_lostuser) < 1)
  exit_error (_("Error"), _("Invalid confirmation hash."));
$row_lostuser = db_fetch_array($res_lostuser);

if ($update && $form_pw)
  {
    if (strcmp ($form_pw, $form_pw2))
      fb (_("Passphrases do not match."), 1);
    elseif (account_pwvalid ($form_pw))
      {
        db_autoexecute ('user',
          ['user_pw' => account_encryptpw ($form_pw), 'confirm_hash' => ''],
          DB_AUTOQUERY_UPDATE, "confirm_hash = ?", [$confirm_hash]
        );
        session_redirect ($sys_home);
      }
  }

site_header (['title' => _("Lost Passphrase Login")]);

print '<h2>' . _("Lost Passphrase Login") . "</h2>\n";
print '<p>';
printf (_("Welcome, %s."), $row_lostuser['user_name']);
print ' ' . _("You may now change your passphrase.") . "</p>\n";
print form_header ();
print '<div>' . account_password_help () . "</div>\n";
print '<div class="inputfield"><strong><label for="form_pw">'
  . _("New passphrase:")
  . '</label></strong>';
print form_input ("password", "form_pw") . "</div>\n";

print '<div class="inputfield"><strong><label for="form_pw2">'
  . _("New passphrase (repeat):") . '</label></strong>';
print form_input ("password", "form_pw2") . "</div>\n";

print form_hidden (["confirm_hash" => $confirm_hash]);
print form_footer ();

$HTML->footer ([]);
?>
