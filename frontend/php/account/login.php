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
extract (sane_import ('request', ['true' => 'from_brother']));

# Logged users have no business here.
if (user_isloggedin () && !$from_brother)
  session_redirect ("{$sys_home}my/");

# Input checks.
extract (sane_import('request',
  [
    'true' => ['brotherhood', 'cookie_for_a_year', 'login', 'cookie_test'],
    'name' => 'form_loginname',
    'pass' => 'form_pw',
    'internal_uri' => 'uri'
  ]
));
if (!$from_brother)
  form_check ('login');

$stay_in_ssl = session_stay_in_ssl ();
$uri_enc = utils_urlencode ($uri);

# Check cookie support.
if (!$from_brother && !isset ($_COOKIE["cookie_probe"]))
  {
    if ($cookie_test)
      {
        # TRANSLATORS: the first argument is a domain (like
        # "savannah.gnu.org" vs. "savannah.nongnu.org");
        # the second argument is a URL ("[URL label]" transforms to a link).
        $msg =
          sprintf (
            _("Savane thinks your cookies are not activated for %s.\nPlease "
              . "activate cookies in your web browser for this website\n"
              . "and [%s try to login again]."),
            $sys_default_domain,
            "$sys_https_url{$sys_home}account/login.php?uri=$uri"
          );
        fb ($msg, 1);
      }
    else
      {
        # Attempt to set a cookie to go to a new page to see
        # if the client will indeed send that cookie.
        session_cookie ('cookie_probe', 1);
        # $uri used to be not url-encoded, it caused login problems,
        # see Savannah sr #108277.
        header ("Location: login.php?uri=$uri_enc&cookie_test=1");
      }
  }

if (!empty ($login))
  {
    if ($from_brother)
      {
        extract (sane_import ('get',
          ['digits' => 'session_uid', 'xdigits' => 'session_hash']
        ));
      }

    if (isset ($session_uid) && session_exists ($session_uid, $session_hash))
      {
        session_set_new_cookies ($session_uid, $cookie_for_a_year);
        $success = 1;
      }
    else
      $success =
        session_login_valid ($form_loginname, $form_pw, $cookie_for_a_year);
    if ($success)
      {
        # Set up the theme, if the user has selected any in the user
        # preferences -- but give priority to a cookie, if set.
        if (!isset($_COOKIE['SV_THEME']))
          {
            $theme_result = user_get_result_set (user_getid ());
            $theme = db_result ($theme_result, 0, 'theme');
            if (strlen ($theme) > 0)
              utils_setcookie ('SV_THEME', $theme, time () + 60 * 60 * 24);
          }
        # We return to our brother 'my', where we login originally,
        # unless we are request to go to an uri.
        if (!$uri)
          {
            $uri = "{$sys_home}my/";
            $uri_enc = utils_urlencode ($uri);
          }
        # If a brother server exists, login there too, if we are not
        # already coming from there.
        if (!empty ($sys_brother_domain) && $brotherhood)
          {
            $root_url = session_protocol () . "://$sys_brother_domain";

            if (!$from_brother)
              {
                # Go there saying hello to your brother.
                header (
                  "Location: $root_url{$sys_home}"
                  . "account/login.php?session_uid=" . user_getid ()
                  . "&session_hash={$session_hash}&login=1"
                  . "&cookie_for_a_year=$cookie_for_a_year&from_brother=1"
                  . "&stay_in_ssl=$stay_in_ssl&brotherhood=1&uri=$uri_enc"
                );
                exit;
              }
            else
              {
                header ("Location: $root_url$uri");
                exit;
              }
          }
        else
          {
            # If No brother server exists, just go to 'my' page
            # unless we are request to go to an uri.
            $url = $uri;
            if ($stay_in_ssl) # Enforce HTTPS mode.
              $url = "$sys_https_url$url";
            header ("Location: $url");
            exit;
          }
      } # $success
  } # !empty ($login)

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
if (!empty ($login) && !$success)
  {
    if (isset ($signal_pending_account) && $signal_pending_account == 1)
      {
        print '<h2>' . _("Pending Account") . "</h2>\n";
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
print '<p><span class="preinput">' . _("Login Name:")
  . "</span><br />&nbsp;&nbsp;\n";
print "<input type='text' name='form_loginname' value=\"$form_loginname"
  . '" tabindex="1" /> <a class="smaller" href="register.php" tabindex="2">['
  . _("No account yet?") . "]</a></p>\n";

print '<p><span class="preinput">' . _("Password:")
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

print '<p>'
  . form_checkbox ('cookie_for_a_year', $cookie_for_a_year, $attr_list)
  . '<span class="preinput">' . _("Remember me") . "</span><br />\n";
print '<span class="text">'
  . _("For a year, your login information will be stored in a cookie. Use\n"
      . "this only if you are using your own computer.")
  . '</span>';

if (!empty ($sys_brother_domain))
  {
    print '<p>'
      .  form_checkbox ('brotherhood', $brotherhood || !$login, $attr_list)
      . '<span class="preinput">';
    # TRANSLATORS: the argument is a domain (like "savannah.gnu.org"
    # vs. "savannah.nongnu.org").
    printf (_("Login also in %s"), $sys_brother_domain);
    print  "</span><br />\n";
  }
print form_footer (_("Login"), 'login');
$HTML->footer ([]);
?>
