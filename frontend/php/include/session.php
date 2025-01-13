<?php
# Session functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Derek Feichtinger <derek.feichtinger--cern.ch>
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

# A note on cookies.
#
# A feature is to set the cookies for the domain and subdomains.
# This allows to reuse authentication in subdomains.
#
# Setting the domain is a little bit tricky. Tests:
#
# Domain: .cookies.com
# - request-host=cookies.com (some konqueror versions, firefox)
# - request-host=*.cookies.com (w3m, links, konqueror, firefox)
# This is the cleanest form, but the RFC is ambiguous in this
# particular case.
#
# Domain: cookies.com
# - request-host=cookies.com (w3m, links, konqueror, firefox)
# - request-host=*.cookies.com (w3m, links, konqueror, firefox)
# This form lacks the leading dot, but the RFC says this should be
# accepted. This is what works best.
#
# Domain: localhost
# - All such cookies are rejected because there's no embedded dot.
#
# Domain: .local (rfc2965)
# - Doesn't work because PHP uses v1 cookies and not v2.
#
# Conclusion: we set the domain only for non-local request-hosts, and
# we use the form without the leading dot.
#
# Refs:
# http://wp.netscape.com/newsref/std/cookie_spec.html (?)
# http://www.ietf.org/rfc/rfc2109.txt (obsoleted by 2965)
# http://www.ietf.org/rfc/rfc2965.txt (status: proposed standard)
# https://savannah.gnu.org/task/?6800 (don't use a leading dot)

foreach (['sane', 'account', 'random-bytes'] as $h)
  require_once (dirname (__FILE__) . "/$h.php");

$G_SESSION = $G_USER = [];

function session_stay_in_ssl ()
{
  return isset ($GLOBALS['sys_https_host']);
}

function session_login_is_sane ($name, $password)
{
  if ($password === '')
    {
      fb (_('Missing password'), 1);
      return false;
    }
  if (!empty ($name))
     return true;
  $raw_loginname = null;
  if (array_key_exists ('form_loginname', $_REQUEST))
    $raw_loginname = $_REQUEST['form_loginname'];
  if (null === $raw_loginname)
    fb (_('Missing user name'), 1);
  else
    fb (sprintf (_('Invalid user name *%s*'), $raw_loginname), 1);
  return false;
}

function session_login_is_disallowed ($status, $allow_pending)
{
  $GLOBALS['signal_pending_account'] = 0;
  if ($status == USER_STATUS_ACTIVE)
     return false;
  if ($status == USER_STATUS_SQUAD) # Squad account, silently exit.
    return true;
  if ($status == USER_STATUS_PENDING)
    {
      if ($allow_pending) # If allowpending (in verify.php), then allow.
        return false;
      fb (_('Account pending'), 1);
      # We can't rely on $ffeedback because it's cleared after use.
      $GLOBALS['signal_pending_account'] = 1;
    }
  elseif (user_status_is_removed ($status))
    fb (_('Account deleted'), 1);
  else
    fb (_('Account not active'), 1);
  return true;
}

function session_fetch_login_data ($name)
{
  $ret = user_get_array ($name);
  if (empty ($ret))
    fb (sprintf (_('User *%s* not found.'), $name), 1);
  return $ret;
}

function session_login_valid (
  $name, $password, $cookie_for_a_year = 0, $allowpending = 0
)
{
  if (!session_login_is_sane ($name, $password))
    return false;

  $usr = session_fetch_login_data ($name);
  if (empty ($usr))
    return false;

  if (session_login_is_disallowed ($usr['status'], $allowpending))
    return false;

  if (!account_validpw ($usr['user_pw'], $password))
    {
      fb (_('Invalid password'), 1);
      return false;
    }
  account_upgrade_pw ($usr['user_pw'], $password, $usr['user_id']);
  session_set_new ($usr['user_id'], $cookie_for_a_year);
  return true;
}

function session_issecure ()
{
  return (getenv ('HTTPS') == 'on');
}

function session_protocol ()
{
  if (session_issecure ())
    return 'https';
  return 'http';
}

function session_needsstayinssl ()
{
  $res = db_execute ("SELECT stay_in_ssl FROM session WHERE session_hash = ?",
    [$GLOBALS['session_hash']]
  );
  return db_result ($res, 0, 'stay_in_ssl');
}

# Define a cookie, just for session or for a year, HTTPS-only or not.
function session_cookie ($name, $value, $cookie_for_a_year = 0, $secure = 0)
{
  $expiration = 0; # At the end of session.
  if ($cookie_for_a_year == 1)
    $expiration = time() + 60 * 60 * 24 * 365;

  utils_setcookie ($name, $value, $expiration, $secure);
}

# Remove a cookie. This is an alternative to setting it to an empty
# or irrelevant value, and will just prevent the browser from sending
# it again.
function session_delete_cookie ($n)
{
  $expiration = time () - 3600; # In the past.
  utils_setcookie ($n, '', $expiration);
}

function session_redirect ($loc)
{
  header ("Location: $loc");
  utils_output_debug_footer ();
  exit;
}

function session_require ($req)
{
  if (user_is_super_user ())
    return true;

  if (!empty ($req['group']))
    {
      $query =
        "SELECT user_id FROM user_group WHERE user_id = ? AND group_id = ?";
      $params = [user_getid (), $req['group']];
      if (!empty ($req['admin_flags']))
        {
          $query .= " AND admin_flags = ?";
          $params[] = $req['admin_flags'];
        }

      if (!db_numrows (db_execute ($query, $params)))
        exit_permission_denied ();
      return true;
    }
  if (!empty ($req['user']))
    {
      if (user_getid () != $req['user'])
        exit_permission_denied ();
      return true;
    }
  if (!empty ($req['isloggedin']))
    {
      if (!user_isloggedin ())
        exit_not_logged_in ();
      return true;
    }
  exit_missing_param ();
}

function session_setglobals ($user_id)
{
  global $G_USER;

  $G_USER = [];
  if ($user_id <= 0)
    return;
  $G_USER = user_get_array ($user_id);
}

function session_hash_parts ($hash)
{
  if (empty ($hash))
    return ['', null];
  if (preg_match ('/(.*;)(.*)/', $hash, $m))
    return [$m[2], $m[1]];
  return [$hash, null];
}

function session_fetch_data ($uid, $hash)
{
  list ($clean_hash, $param) = session_hash_parts ($hash);
  if (empty ($param))
    return null;
  $res = db_execute (
    'SELECT * FROM session WHERE user_id = ? AND session_hash LIKE ?',
    [$uid, "$param%"]
  );
  while ($row = db_fetch_array ($res))
    if (session_valid_hash ($row['session_hash'], $clean_hash))
      {
        $row['hash_enc'] = $row['session_hash'];
        $row['session_hash'] = $hash;
        return $row;
      }
  return null;
}

# Try inserting a new session hash in the table.  Return null if the hash
# already exists, and the new database row when the insert is successful;
# when $tries_left is zero, the error shows up in the browser and terminates
# the further output (it would be more user-friendly to explain what happened,
# but the event is unlikely enough to cut the corners).
function session_try_insert_hash ($user_id, $hash, $tries_left)
{
  $vals = ['session_hash' => $hash, 'user_id' => $user_id,
    'ip_addr' => $_SERVER['REMOTE_ADDR'], 'time' => time (),
    'stay_in_ssl' => session_stay_in_ssl ()
  ];
  $saved = utils_disable_warnings (E_ALL, !$tries_left);
  db_query_prevent_die ($tries_left);
  $res = db_autoexecute ('session', $vals);
  db_query_prevent_die (false);
  utils_restore_warnings ($saved);
  if (empty ($res))
    return null;
  return $vals;
}

function session_generate_ticket ()
{
  $ret = microtime (true);
  $ret -= floor ($ret);
  return (int)($ret * 10000);
}

function session_generate_hash ($user_id)
{
  $tries = 17;
  while ($tries--)
    {
      $hash = random_hash ();
      $ticket = session_generate_ticket ();
      $hhash = "$ticket;" . account_encryptpw ($hash, true);
      $vals = session_try_insert_hash ($user_id, $hhash, $tries);
      if (empty ($vals))
        {
          trigger_error ("duplicate hash $hhash detected, tries left: $tries");
          continue;
        }
      $vals['hash_enc'] = $hhash;
      $vals['session_hash'] = "$ticket;$hash";
      return $vals;
    }
  return false;
}

function session_set_new ($user_id, $cookie_for_a_year)
{
  global $G_SESSION, $session_hash;
  $G_SESSION = session_generate_hash ($user_id);
  if (empty ($G_SESSION))
    return;
  session_setglobals ($G_SESSION['user_id']);
  $session_hash = $G_SESSION['session_hash'];

  # If the user specified he wants only one session to be opened at a time,
  # kill all other sessions.
  if (user_get_preference ("keep_only_one_session"))
    db_execute ("DELETE FROM session WHERE session_hash <> ? AND user_id = ?",
      [$G_SESSION['hash_enc'], $user_id]
    );
  session_set_new_cookies ($user_id, $cookie_for_a_year);
}

# Set session cookies.
function session_set_new_cookies ($user_id, $cookie_for_a_year = 0)
{
  $stay_in_ssl = session_stay_in_ssl ();
  # Set a non-secure cookie so that Savane automatically redirects to HTTPS.
  if ($stay_in_ssl)
    session_cookie ('redirect_to_https', 1, $cookie_for_a_year, 0);

  session_cookie ('session_uid', $user_id, $cookie_for_a_year, $stay_in_ssl);
  session_cookie ('session_hash', $GLOBALS['session_hash'], $cookie_for_a_year,
    $stay_in_ssl);
  $_COOKIE['session_uid'] = $user_id;
  $_COOKIE['session_hash'] = $GLOBALS['session_hash'];
  session_delete_cookie ('cookie_probe');
  session_set ();
}

function session_set ()
{
  global $G_SESSION, $G_USER;
  extract (sane_import ('cookie',
    ['hash' => 'session_hash', 'digits' => 'session_uid'])
  );
  if (!($session_hash && $session_uid))
    return;
  $G_SESSION = session_fetch_data ($session_uid, $session_hash);

  if (empty ($G_SESSION['session_hash']))
    unset ($G_SESSION, $G_USER);
  else
    session_setglobals ($G_SESSION['user_id']);
}

function session_count ($uid)
{
  return db_numrows (db_execute (
    "SELECT ip_addr FROM session WHERE user_id = ?", [$uid]
  ));
}

function session_valid_hash ($stored_hash, $hash)
{
  list ($clean_hash, $ticket) = session_hash_parts ($stored_hash);
  return account_validpw ($clean_hash, $hash);
}

function session_exists ($uid, $hash)
{
  return session_fetch_data ($uid, $hash) !== null;
}

function session_logout ()
{
  db_execute ("DELETE FROM session WHERE session_hash = ?",
    [$GLOBALS['G_SESSION']['hash_enc']]
  );
  session_delete_cookie ('redirect_to_https');
  session_delete_cookie ('session_hash');
  session_delete_cookie ('session_uid');
}

# Check if cookies are enabled.
function session_check_cookies ($uri, $uri_urlencoded)
{
  global $sys_default_domain, $sys_https_url, $sys_home;
  $url_prefix = "$sys_https_url{$sys_home}account/login.php?uri=";
  if (isset ($_COOKIE["cookie_probe"]))
    return;
  extract (sane_import ('get', ['true' => 'cookie_test']));
  if (empty ($cookie_test))
    {
      # Request a cookie and reload the page to see
      # if the client actually sends that cookie.
      session_cookie ('cookie_probe', 1);
      # $uri used to be not URL-encoded, it caused login problems,
      # see Savannah sr #108277.
      session_redirect ("$url_prefix$uri_urlencoded&cookie_test=1");
    }
  # TRANSLATORS: the first argument is a domain (like "savannah.gnu.org");
  # the second argument is a URL ("[URL label]" transforms to a link).
  $msg = sprintf (
    _("Savane thinks your cookies are not activated for %s.\nPlease activate "
      . "cookies in your web browser for this website\nand [%s try to login "
      . "again]."), $sys_default_domain, "$url_prefix$uri"
  );
  fb ($msg, 1);
}

# Set the theme from user's preferences unless the cookie is already set.
function session_set_theme ()
{
  if (isset ($_COOKIE['SV_THEME']))
    return;
  $theme = user_get_field (0, 'theme');
  if (strlen ($theme) > 0)
    utils_setcookie ('SV_THEME', $theme, time () + 60 * 60 * 24);
}

# Log in the 'brother' domain if needed, and return back.
# Only returns when the action isn't needed; otherwise exits
# in session_redirect ().
function session_login_brother ($uri, $uri_urlencoded)
{
  global $sys_brother_domain, $sys_home;
  global $brotherhood, $session_hash, $cookie_for_a_year, $stay_in_ssl;
  global $from_brother;
  if (empty ($sys_brother_domain) || empty ($brotherhood))
    return;
  # If a brother server exists, login there too, if we are not
  # already coming from there.
  $root_url = session_protocol () . "://$sys_brother_domain";

  if ($from_brother)
    # Redirect back after logging in the 'brother' domain.
    session_redirect ("$root_url$uri");
  # Log in the 'brother' domain.
  session_redirect ("$root_url{$sys_home}account/login.php?"
    . "session_uid=" . user_getid () . "&session_hash=$session_hash"
    . "&login=1&cookie_for_a_year=$cookie_for_a_year&from_brother=1"
    . "&stay_in_ssl=$stay_in_ssl&brotherhood=1&uri=$uri_urlencoded"
  );
}
?>
