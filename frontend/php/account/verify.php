<?php
# Verify registration hash.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
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

foreach (['init', 'spam', 'html', 'form', 'exit', 'account'] as $i)
  require_once ("../include/$i.php");

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

function run_update ()
{
  global $update, $form_loginname, $sys_home, $form_pw, $confirm_hash;
  if (empty ($update))
    return;
  $uid = user_getid ($form_loginname);
  if (empty ($uid) || user_squad_exists ($uid))
    exit_error (_("Invalid username."));
  account_validate_confirm_hash ($confirm_hash, HASH_NEW_ACCOUNT, $uid);
  if (!session_login_valid ($form_loginname, $form_pw, 0, 1))
    return;
  db_autoexecute ('user', ['status' => 'A', 'confirm_hash' => null],
    DB_AUTOQUERY_UPDATE, 'user_name = ?', [$form_loginname]
  );
  account_clear_confirm_hash ($form_loginname);
  session_redirect ("{$sys_home}account/first.php");
}

run_update ();
site_header (['title' => _("Login")]);
# TRANSLATORS: the argument is the name of the system (like "Savannah").
print html_h (2, sprintf (_("%s Account Verification"), $sys_name));
print '<p>'
 . _("In order to complete your registration, login now. Your account\n"
     . "will then be activated for normal logins.")
 . "</p>\n";

print form_header ();
print '<p><span class="preinput">'
  . html_label ('form_loginname', _("Login name:"))
  . "</span><br />\n&nbsp;&nbsp;";
print form_input ("text", "form_loginname");
print "</p>\n";

print '<p><span class="preinput">'
  . html_label ('form_pw', _("Password:")) . "</span><br />\n&nbsp;&nbsp;";
print form_input ("password", "form_pw");
print "</p>\n";

print form_hidden (["confirm_hash" => $confirm_hash]);
print form_footer (_("Login"));

site_footer ([]);
?>
