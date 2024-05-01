<?php
# Verify registration hash.
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

require_once ('../include/init.php');
require_once ('../include/spam.php');
require_once ('../include/html.php');
require_once ('../include/form.php');
require_once ('../include/exit.php');

extract (sane_import ('post',
  ['true' => 'update', 'name' => 'form_loginname', 'pass' => 'form_pw']
));
# Must accept all ways of providing confirm_hash because in the mail it is a GET
# but if the form fails (wrong password, etc), it will be a POST.
extract (sane_import ('request', ['hash' => 'confirm_hash']));
form_check ('update');

# Logged users have no business here.
if (user_isloggedin ())
  session_redirect ("{$sys_home}my/");

if (!empty ($update))
  {
    # First check just confirmation hash.
    $res = db_execute ("
      SELECT confirm_hash, status FROM user
      WHERE user_name = ? AND status <> 'SQD'",
      [$form_loginname]
    );
    if (db_numrows ($res) < 1)
      exit_error (_("Invalid username."));

    $usr = db_fetch_array ($res);
    if ($confirm_hash != $usr['confirm_hash'])
      # TRANSLATORS: confirmation hash is a secret code sent to the user.
      exit_error (_("Invalid confirmation hash"));

    # Then check valid login.
    if (session_login_valid ($form_loginname, $form_pw, 0, 1))
      {
        $res = db_execute (
          "UPDATE user SET status = 'A' WHERE user_name = ?",
          [$form_loginname]
        );
        session_redirect ("{$sys_home}account/first.php");
      }
  }
site_header (['title' => _("Login")]);
print '<h2> ';
# TRANSLATORS: the argument is the name of the system (like "Savannah").
printf (_("%s Account Verification"), $sys_name);
print "</h2>\n";
print '<p>'
 . _("In order to complete your registration, login now. Your account\n"
     . "will then be activated for normal logins.")
 . "</p>\n";

print form_header ();
print '<p><span class="preinput">'
  . _("Login Name") . ":</span><br />\n&nbsp;&nbsp;";
print form_input ("text", "form_loginname");
print "</p>\n";

print '<p><span class="preinput">'
  . _("Password") . ":</span><br />\n&nbsp;&nbsp;";
print form_input ("password", "form_pw");
print "</p>\n";

print form_hidden (["confirm_hash" => $confirm_hash]);
print form_footer (_("Login"));

site_footer ([]);
?>
