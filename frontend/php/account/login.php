<?php
# Front page - news, latests projects, etc.
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
require_once ('../include/account.php');
require_once ('../include/sane.php');

utils_disable_cache ();
extract (sane_import ('get', ['true' => 'from_brother']));

# Logged users have no business here.
if (user_isloggedin () && !$from_brother)
  session_redirect ("{$sys_home}my/");

# Input checks.
extract (sane_import ('request',
  [
    'true' => ['brotherhood', 'login'],
    'digits' => 'cookie_for_a_year',
    'name' => 'form_loginname',
    'pass' => 'form_pw',
    'internal_uri' => 'uri'
  ]
));
if (empty ($cookie_for_a_year))
  $cookie_for_a_year = 0;
if (!$from_brother)
  form_check ('login');

$stay_in_ssl = session_stay_in_ssl ();
$uri_enc = utils_urlencode ($uri);

if (!$from_brother)
  session_check_cookies ($uri, $uri_enc);

function validate_login ($from_brother, $form_loginname, $form_pw)
{
  global $sesion_uid, $session_hash, $cookie_for_a_year;
  if ($from_brother)
    extract (sane_import ('get',
      ['digits' => 'session_uid', 'hash' => 'session_hash']
    ));
  if (isset ($session_uid) && session_exists ($session_uid, $session_hash))
    {
      session_set_new_cookies ($session_uid, $cookie_for_a_year);
      return true;
    }
  return session_login_valid ($form_loginname, $form_pw, $cookie_for_a_year);
}

function arrange_session ($uri, $uri_enc)
{
  global $sys_home, $stay_in_ssl, $sys_https_url;
  session_set_theme ();
  # We return to our brother 'my', where we login originally,
  # unless we are request to go to an uri.
  if (!$uri)
    {
      $uri = "{$sys_home}my/";
      $uri_enc = utils_urlencode ($uri);
    }
  session_login_brother ($uri, $uri_enc);
  # If no brother domain is defined, just return to the page the login
  # was requested from.
  $url = $uri;
  if ($stay_in_ssl)
    $url = "$sys_https_url$url";
  session_redirect ($url);
}

if (!empty ($login))
  {
    $success = validate_login ($from_brother, $form_loginname, $form_pw);
    if ($success)
      arrange_session ($uri, $uri_enc);
  }

if (isset ($session_hash))
  {
    # Nuke their old session securely.
    session_delete_cookie ('session_hash');
    if (isset ($user_id))
      db_execute ("DELETE FROM session WHERE session_hash = ? AND user = ?",
        [$session_hash, $user_id]
      );
  }

site_header (['title' => _("Login")]);
if (!empty ($login) && empty ($success))
  {
    if (isset ($signal_pending_account) && $signal_pending_account == 1)
      {
        print html_h (2, _("Pending Account"));
        print '<p>'
          . _("Your account is currently pending your email confirmation.\n"
              . "Visiting the link sent to you in this email will activate "
              . "your account.")
          . "</p>\n";
        print '<p><a href="pending-resend.php?form_user='
          . "$form_loginname\">["
          . _("Resend Confirmation Email") . "]</a></p>\n";
      }
    else
      {
        # Print helpful error message.
        print '<div class="splitright"><div class="boxitem">';
        print '<div class="warn">' . _("Troubleshooting:")
          . "</div></div>\n<ul class='boxli'><li class='boxitemalt'>"
          . _("Is the &ldquo;Caps Lock&rdquo; or &ldquo;A&rdquo; light on "
              . "your keyboard on?")
          . "<br />\n"
          . _("If so, hit &ldquo;Caps Lock&rdquo; key before trying again.")
          . "</li>\n<li class='boxitem'>"
          . _("Did you forget or misspell your password?")
          . "<br />\n"
          . utils_link (
              'lostpw.php',
               _("You can recover your password using the lost password form.")
            )
          . "</li>\n"
          .'<li class="boxitemalt">' . _("Still having trouble?") . "<br />\n"
          . utils_link (
              "{$sys_home}support/?group=$sys_unix_group_name",
              _("Fill a support request.")
            )
          . "</li>\n";
        print "</ul>\n</div>\n";
      }
  }

if (isset ($sys_https_host))
  utils_get_content ("account/login");
print form_tag (['action' => "$sys_https_url{$sys_home}account/login.php"]);
print form_hidden (['uri' => $uri]);

# Shortcuts to New Account and Lost Password have a tabindex superior to
# the rest of form, so they don't mess with the normal order when you
# press TAB on the keyboard (login -> password -> post).
print '<p><span class="preinput">'
  . html_label ('form_loginname', _("Login name:"))
  . "</span><br />&nbsp;&nbsp;\n";
print "<input type='text' name='form_loginname' value=\"$form_loginname"
  . '" tabindex="1" /> <a class="smaller" href="register.php" tabindex="2">['
  . _("No account yet?") . "]</a></p>\n";

print '<p><span class="preinput">' . html_label ('form_pw', _("Password:"))
  . "</span><br />\n&nbsp;&nbsp;";
print '<input type="password" name="form_pw" tabindex="1" /> '
  . '<a class="smaller" href="lostpw.php" tabindex="2">['
  . _("Lost your password?") . "]</a></p>\n";

$attr_list = ['tabindex' => '1'];

if (!isset ($sys_https_host))
  {
    print '<p class="warn">';
    print
      _("This server does not encrypt data (no https), so the password you\n"
        . "sent may be viewed by other people. Do not use any important\n"
        . "passwords.")
      . "</p>\n";
  }

$attr_list['label'] = '<span class="preinput">' . _("Remember me")
  . "</span><br />\n"
  . _("For a year, your login information will be stored in a cookie. Use\n"
      . "this only if you are using your own computer.");
print '<p>'
  . form_checkbox ('cookie_for_a_year', $cookie_for_a_year, $attr_list)
  . "</p>\n";

if (!empty ($sys_brother_domain))
  {
    print '<p>';
    print form_checkbox ('brotherhood', $brotherhood || !$login,
      # TRANSLATORS: the argument is a domain (like "savannah.gnu.org"
      # vs. "savannah.nongnu.org").
      ['label' => '<span class="preinput">'
        . sprintf (_("Login also in %s"), $sys_brother_domain) . '</span>']
    );
    print  "</p>\n";
  }
print form_footer (_("Login"), 'login');
$HTML->footer ([]);
?>
