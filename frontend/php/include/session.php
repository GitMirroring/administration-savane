<?php
# Session functions.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2003-2006 Derek Feichtinger <derek.feichtinger--cern.ch>
# Copyright (C) 2017, 2018, 2022, 2023 Ineiev
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

# A note on cookies.
#
# A feature is to set the cookies for the domain and subdomains.
# This allows to reuse authentication in subdomains. Check
# frontend/perl for an example.
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
# https://gna.org/support/?func=detailitem&item_id=886 (pb with local domains)
# https://gna.org/bugs/?6694 (first discussion, some mistakes in Beuc's comments)
# https://savannah.gnu.org/task/?6800 (don't use a leading dot)

require_once (dirname (__FILE__) . '/sane.php');
require_once (dirname (__FILE__) . '/account.php');

$G_SESSION = $G_USER = [];

function session_login_valid (
  $form_loginname, $form_pw, $allowpending = 0, $cookie_for_a_year = 0,
  $crypted_pw = 0, $stay_in_ssl = 1
)
{
  global $session_hash;

  if (!$form_loginname || !$form_pw)
    {
      fb (_('Missing Password Or User Name'), 1);
      return false;
    }

  $resq = db_execute (
    "SELECT user_id, user_pw, status FROM user WHERE user_name = ?",
    [$form_loginname]
  );
  if (db_numrows ($resq) < 1)
    {
      fb (_('Invalid User Name'), 1);
      return false;
    }

  $usr = db_fetch_array ($resq);
  $GLOBALS['signal_pending_account'] = 0;

  # Check status first:
  # if allowpending (for verify.php) then allow.
  if ($allowpending && ($usr['status'] == 'P'))
    {
      #1;
    }
  else
    {
      if ($usr['status'] == 'SQD')
        {
          # Squad account, silently exit.
          return false;
        }
      if ($usr['status'] == 'P')
        {
          fb (_('Account Pending'), 1);
          # We can't rely on $ffeedback because it's cleared after use.
          $GLOBALS['signal_pending_account'] = 1;
          return false;
        }
      if ($usr['status'] == 'D' || $usr['status'] == 'S')
        {
          fb (_('Account Deleted'), 1);
          return false;
        }
      if ($usr['status'] != 'A')
        {
          fb (_('Account Not Active'), 1);
          return false;
        }
    }

  if ($usr['user_pw'] == '')
    {
      # Authentication method: Kerberos
      # If both user_pw and unix_pw are empty the user might
      # be able to login if she/he has a Kerberos account.
      # Update unix_pw and user_pw.
      if ($GLOBALS['sys_use_krb5'])
        $ret = krb5_login ($form_loginname, $form_pw);

      if($ret == KRB5_NOTOK)
        {
          fb (_("phpkrb5 module failure"), 1);
          return false;
        }
      if($ret == KRB5_BAD_USER)
        {
          fb (_("user is not a kerberos principal"), 1);
          return false;
        }
      if($ret == KRB5_BAD_PASSWORD)
        {
          fb (_("user is a kerberos principal but passwords do not match"), 1);
          return false;
        }
      $stored_pw = account_encryptpw ($form_pw);
      db_execute ("UPDATE user SET user_pw = ? WHERE user_id = ?",
        [$stored_pw, $usr['user_id']]
      );
    }
  elseif ($usr['user_pw'] == 'SSH')
    {
      fb (_("This user is known, but cannot be authenticated.\n"
          . "Please ask site administrators for a password."), 1
      );
      return false;
    }
  else
    {
      # For this authentication method we enable a brother site
      # login mechanism:
      # Password is crypted (crypt()) if we are coming from the brother site.
      # Normally, users shouldn't use this feature
      # unless they login at brother site one time.
      if ($crypted_pw)
        {
          if (crypt ($usr['user_pw'], $form_pw) != $form_pw)
            {
              fb (_('Invalid Password'), 1);
              return false;
            }
        }
      elseif (!account_validpw ($usr['user_pw'], $form_pw))
        {
          fb (_('Invalid Password'), 1);
          return false;
        }
    }
  # Create a new session.
  session_set_new ($usr['user_id'], $cookie_for_a_year, $stay_in_ssl);
  return true;
}

function session_issecure ()
{
  return (getenv ('HTTPS') == 'on');
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
  $result = db_execute (
    "SELECT user_id, user_name FROM user WHERE user_id = ?", [$user_id]
  );
  if (db_numrows ($result))
    $G_USER = db_fetch_array ($result);
}

function session_set_new ($user_id, $cookie_for_a_year = 0, $stay_in_ssl = 1)
{
  global $G_SESSION, $session_hash;

  # Concatinate current time, and random seed for MD5 hash
  # continue until unique hash is generated (SHOULD only be once).
  do
    {
      $pre_hash = time () . rand () . $_SERVER['REMOTE_ADDR'] . microtime ();
      $session_hash = md5 ($pre_hash);
      $result = db_execute (
        "SELECT session_hash FROM session WHERE session_hash = ?",
        [$session_hash]
      );
    }
  while (db_numrows ($result) > 0); # do

  # Make new session entries into DB.
  if (!isset ($stay_in_ssl))
    $stay_in_ssl = 0; # Avoid passing NULL.
  db_autoexecute ('session',
    [
      'session_hash' => $session_hash, 'ip_addr' => $_SERVER['REMOTE_ADDR'],
      'time' => time(), 'user_id' => $user_id, 'stay_in_ssl' => $stay_in_ssl
    ],
    DB_AUTOQUERY_INSERT
  );
  # Set global.
  $res = db_execute (
    "SELECT * FROM session WHERE session_hash = ?", [$session_hash]
  );
  if (db_numrows ($res) > 1)
    {
      db_execute (
        "DELETE FROM session WHERE session_hash = ?", [$session_hash]
      );
      exit_error (
        _("Two people had the same session hash - re-login.\n"
          . "It should never happen again.")
      );
    }
  $G_SESSION = db_fetch_array ($res);
  session_setglobals ($G_SESSION['user_id']);

  # If the user specified he wants only one session to be opened at a time,
  # kill all other sessions.
  if (user_get_preference ("keep_only_one_session"))
    db_execute ("DELETE FROM session WHERE session_hash <> ? AND user_id = ?",
      [$session_hash, $user_id]
    );
  session_set_new_cookies ($user_id, $cookie_for_a_year, $stay_in_ssl);
}

# Set session cookies.
function session_set_new_cookies (
  $user_id, $cookie_for_a_year = 0, $stay_in_ssl = 1
)
{
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

  # Assume bad session_hash and session. If all checks work, then allow
  # otherwise make new session.
  $id_is_good = 0;

  # Here also check for good hash, set if new session is needed.
  extract (sane_import ('cookie',
    ['hash' =>'session_hash', 'digits' => 'session_uid'])
  );
  if ($session_hash && $session_uid)
    {
      $result = db_execute ("
        SELECT * FROM session WHERE session_hash = ? AND user_id = ?",
        [$session_hash, $session_uid]
      );
      $G_SESSION = db_fetch_array ($result);

      if (!empty ($G_SESSION['session_hash']))
        $id_is_good = 1;
    } # if ($session_hash && $session_uid)

  if ($id_is_good)
    session_setglobals ($G_SESSION['user_id']);
  else
    {
      unset ($G_SESSION);
      unset ($G_USER);
    }
}

function session_count ($uid)
{
  return db_numrows (db_execute (
    "SELECT ip_addr FROM session WHERE user_id = ?", [$uid]
  ));
}

function session_exists ($uid, $hash)
{
  $res = db_execute (
    "SELECT NULL FROM session WHERE user_id = ?  AND session_hash = ?",
    [$uid, $hash]
  );
  return db_numrows ($res) == 1;
}

function session_logout ()
{
  # If the session was validated, we can assume that the cookie session_hash
  # is reliable.
  extract (sane_import ('cookie', ['xdigits' => 'session_hash']));
  db_execute ("DELETE FROM session WHERE session_hash = ?", [$session_hash]);
  session_delete_cookie ('redirect_to_https');
  session_delete_cookie ('session_hash');
  session_delete_cookie ('session_uid');
}
?>
