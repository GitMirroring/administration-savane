<?php
# Forms and functions to manage accounts (including groups).
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

require_once (dirname (__FILE__) . '/utils.php');
require_once (dirname (__FILE__) . '/pwqcheck.php');
require_once (dirname (__FILE__) . '/random-bytes.php');

# Return a string explaining current pwcheck requirements.
function expand_pwqcheck_options ()
{
  global $pwqcheck_args;

  $args = "$pwqcheck_args ";
  $help = "";

  if (preg_match ("/max=([[:digit:]]*) /", $args, $matches))
    {
      $help .= "<br />\n"
        . sprintf (_("The maximum allowed password length: %s."), $matches[1]);
    }
  if (preg_match ("/passphrase=([[:digit:]]*) /", $args, $matches))
    {
      $help .= "<br />\n"
        . sprintf (
            _("The number of words required for a passphrase: %s."),
            $matches[1]
          );
    }
  if (preg_match ("/match=([[:digit:]]*) /", $args, $matches))
    {
      $help .= "<br />\n";
      if ($matches[1])
        $help .= sprintf (
          _("The length of common substring required to conclude "
            . "that a password\nis at least partially based on information "
            . "found in a character string: %s."),
          $matches[1]
        );
      else
        $help .= _("Checks for common substrings are disabled.");
    } # preg_match ($args, "/match=([^ ]*)/ ", $matches)

  $single_field = "([[:digit:]]*|disabled)";
  $fields = $single_field . str_repeat (",$single_field", 4);
  if (!preg_match ("/min=$fields /", $args, $matches))
    return $help;
  $msg_disabled = [
    _("Passwords consisting of characters from one class only "
        . "are not allowed."),
    _("Passwords consisting of characters from two classes that don't "
      . "meet\nrequirements for passphrases are not allowed."),
    _("Check for passphrases is disabled."),
    _("Passwords consisting of characters from three classes "
      . "are not allowed."),
    _("Passwords consisting of characters from four classes "
      . "are not allowed."),
  ];
  $msg_number = [
    _("The minimum length for passwords consisting of characters "
      . "from one class: %s."),
    _("\nThe minimum length for passwords consisting of characters "
      . "from two classes\nthat don't meet requirements "
      . "for passphrases: %s."),
    _("The minimum length for passphrases: %s."),
    _("The minimum length for passwords consisting of characters\n"
      . "from three classes: %s."),
    _("The minimum length for passwords consisting of characters\n"
      . "from four classes: %s."),
  ];
  for ($i = 0; $i < 4; $i++)
    {
      $help .= "<br />\n";
      if ($matches[$i + 1] == 'disabled')
        $help .= $msg_disabled[$i];
      else
        $help .= sprintf ($msg_number[$i], $matches[$i + 1]);
    }
  return $help;
}

function account_password_help ()
{
  global $use_pwqcheck, $pwqcheck_args;
  $help =
    _("Note: The password should be long enough\n"
      . "or containing multiple character classes:\n"
      . "symbols, digits (0-9), upper and lower case letters.");
  if (!$use_pwqcheck)
    return $help;
  $pwqgen = exec ("pwqgen");
  # TRANSLATORS: the argument is an example of passphrase.
  $help .= " " . sprintf (_("For instance: %s."), utils_specialchars ($pwqgen));
  $help .= " <br />\n"
    . sprintf (
        _("pwqcheck options are '%s':"), utils_specialchars ($pwqcheck_args)
      );
  $help .= expand_pwqcheck_options ();
  return $help;
}

# Modified from
# http://www.openwall.com/articles/PHP-Users-Passwords#enforcing-password-policy
function account_pwcheck ($newpass, $oldpass, $user)
{
  # Some really trivial and obviously-insufficient password strength
  # checks - we ought to use the pwqcheck(1) program instead.
  # TRANSLATORS: this string in used in the context "Bad password (%s)".
  if (strlen ($newpass) < 7)
    return _('way too short');
  if (
    stristr ($oldpass, $newpass)
    || (strlen ($oldpass) >= 4 && stristr ($newpass, $oldpass))
  )
    # TRANSLATORS: this string in used in the context "Bad password (%s)".
    return _('based on the old one');
  if (
    stristr ($user, $newpass)
    || (strlen ($user) >= 4 && stristr ($newpass, $user))
  )
    # TRANSLATORS: this string in used in the context "Bad password (%s)".
    return _('based on the username');
  return 0;
}

function account_pwvalid ($newpass, $oldpass = '', $user = '')
{
  global $use_pwqcheck, $pwqcheck_args;
  if ($use_pwqcheck)
    $check = pwqcheck ($newpass, $oldpass, $user, '', $pwqcheck_args);
  else
    {
      $check = account_pwcheck ($newpass, $oldpass, $user);
      # TRANSLATORS: the argument explains the reason why the password is bad.
      if ($check !== 0)
        $check = sprintf (_("Bad password (%s)"), $check);
    }

  if ($check === 0)
    return 1;
  fb ($check, 1);
  return 0;
}

function account_realname_valid ($name)
{
  if ($name === null)
    return 0;
  utils_get_content ("forbidden_realnames");
  if (empty ($GLOBALS['forbid_realname_regexp']))
    return 1;
  return !preg_match ($GLOBALS['forbid_realname_regexp'], $name);
}

function account_sanitize_realname ($name)
{
  if (!is_string ($name))
    return '';
  return strtr ($name, "'\",<", "    ");
}

function account_namevalid ($name, $allow_dashes=0, $allow_underscores=1,
                            $allow_dots=0, $MAX_ACCNAME_LENGTH=16,
                            $MIN_ACCNAME_LENGTH=3)
{
  $underscore = '';
  $dashe = '';
  $dot = '';

  # By default, underscore are allowed, creating no specific issue for an
  # account name. It may creates trouble if the account is use to handle DNS...
  if ($allow_underscores)
    $underscore = "_";

  # By default, dashes are not allowed, creating issue with mailing list name
  # and many other potential conflicts. However, it is usually convenient for
  # group name.
  $dash = $allow_dashes ? '-' : '';

  # By default, dots are not allowed. Unix systems may allow it but it
  # is a source of confusion (for instance, a problem if you have the habit
  # to things like `chown user.group`)
  # However, it is sometimes wise to allow it, for instance if we check for
  # a mailing-list name, which is almost like an account name + dots
  $dot = $allow_dots ? '.' : '';

  # No spaces.
  if (strrpos ($name, ' ') > 0)
    {
      fb (_("There cannot be any spaces in the name"), 1);
      return 0;
    }

  # Min and max length.
  if (strlen ($name) < $MIN_ACCNAME_LENGTH)
    {
      fb (_("The name is too short"), 1);
      $msg =
        sprintf (
          ngettext (
            "It must be at least %s character.",
            "It must be at least %s characters.",
            $MIN_ACCNAME_LENGTH
          ),
          $MIN_ACCNAME_LENGTH
        );
      fb ($msg, 1);
      return 0;
    }

  if (strlen ($name) > $MAX_ACCNAME_LENGTH)
    {
      fb (_("The name is too long"), 1);
      $msg =
        sprintf (
          ngettext (
            "It must be at most %s character.",
            "It must be at most %s characters.",
            $MAX_ACCNAME_LENGTH
          ),
          $MAX_ACCNAME_LENGTH
        );
      fb ($msg, 1);
      return 0;
    }

  $alphabet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
  if (strspn ($name, $alphabet) == 0)
    {
      fb (_("The name must begin with an alphabetical character."), 1);
      return 0;
    }

  $char_set = "{$alphabet}0123456789$underscore$dash$dot";
  # Must contain only allowed characters, depending on the arguments.
  if (strspn ($name, $char_set) != strlen ($name))
    {
      $tolerated = '';
      if ($allow_underscores)
        $tolerated .= '_, ';
      if ($allow_dashes)
        $tolerated .= '-, ';
      if ($allow_dots)
        $tolerated .= '., ';

      if ($tolerated)
        {
          $tolerated = rtrim ($tolerated, ', ');
          # TRANSLATORS: the argument is comma-separated list of additional
          # characters (possibly single character).
          fb (sprintf (_('The name must only contain alphanumerics and %s.'),
                     $tolerated), 1);
        }
      else
        fb (_("The name must only contain alphanumerics."), 1);
      return 0;
    }

  $unacceptable_name_regex =
    ",^("
      . "(adm)"
      . "|(anoncvs)"
      . "|(anonymous)"
      . "|(apache)"
      . "|(bin)"
      . "|(cvs)"
      . "|(daemon)"
      . "|(debian)"
      . "|(download)"
      . "|(dummy)"
      . "|(ftp)"
      . "|(games)"
      . "|(halt)"
      . "|(httpd)"
      . "|(invalid)"
      . "|(irc)"
      . "|(lp)"
      . "|(mail)"
      . "|(mysql)"
      . "|(news)"
      . "|(nobody)"
      . "|(ns)"
      . "|(opensource)"
      . "|(operator)"
      . "|(root)"
      . "|(savane-keyrings)"
      . "|(shell)"
      . "|(shutdown)"
      . "|(sync)"
      . "|(uucp)"
      . "|(web)"
      . "|(www)"
    . ")$,i";
  if (!preg_match ($unacceptable_name_regex, $name))
    return 1;
  fb (_("That name is reserved."), 1);
  return 0;
}

# Just check if the email address domain is not from a forbidden domain
# or if it is not already associated to an email account.
function account_emailvalid ($email)
{
  $res = db_execute ("SELECT user_id FROM user WHERE email LIKE ?", [$email]);
  if (db_numrows ($res) > 0)
    {
      fb (
        _("An account associated with that email address has already "
          . "been created."),
        1
      );
      return 0;
    }
  utils_get_content ("forbidden_mail_domains");
  if (empty ($GLOBALS['forbid_mail_domains_regexp']))
    return 1;
  if (!preg_match ($GLOBALS['forbid_mail_domains_regexp'], $email))
    return 1;
  fb (
    _("It is not allowed to associate an account with this email address."),
    1
  );
  return 0;
}

function account_groupnamevalid ($name)
{
  # Test with the usual namevalid function, allowing dashes.
  if (!account_namevalid ($name, 1, 0))
    return 0;
  utils_get_content ("forbidden_group_names");
  # All these groups are invalid by default. There can be used for system
  # services and already be existing on the system.
  # Please, keep that list in alphabetic order.
  $forbid_group_regexp =
    "/^("
      . "(adm)"
      . "|(admin)"
      . "|(apache)"
      . "|(bin)"
      . "|(bug)"  # Mailing lists would conflict with <bug-*@gnu.org>.
      . "|(compile)"
      . "|(cvs[0-9]?)"
      . "|(daemon)"
      . "|(disk)"
      . "|(download[0-9]?)"
      . "|(exim)"
      . "|(fencepost)"
      . "|(ftp)"
      . "|(ftp[0-9]?)"
      . "|(gnudist)"
      . "|(help)"  # Mailing lists would conflict with <help-*@gnu.org>.
      . "|(ident)"
      . "|(info)"  # Mailing lists would conflict with <info-*@gnu.org>.
      . "|(irc[0-9]?)"
      . "|(lists)"
      . "|(lp)"
      . "|(mail[0-9]?)"
      . "|(man)"
      . "|(monitor)"
      . "|(mirrors?)"
      . "|(nogroup)"
      . "|(ns[0-9]?)"
      . "|(news[0-9]?)"
      . "|(ntp)"
      . "|(postfix)"
      . "|(projects)"
      . "|(pub)"
      . "|(root)"
      . "|(rpc)"
      . "|(rpcuser)"
      . "|(shadow)"
      . "|(shell[0-9]?)"
      . "|(slayer)"
      . "|(sshd)"
      . "|(staff)"
      . "|(sudo)"
      . "|(savane-keyrings)"   # Reserved for keyrings.
      . "|(svusers)"           # Group for savane users.
      . "|(sys)"
      . "|(tty)"
      . "|(uucp)"
      . "|(users)"
      . "|(utmp)"
      . "|(web.*)"
      . "|(wheel)"
      . "|(www[0-9]?)"
      . "|(www-data)"
      . "|(xfs)"
    . ")$/";

  # Forbidden names: check the hardcoded list unless the variable
  # $only_specific_forbid_group_regexp is true.
  if (!$GLOBALS['only_specific_forbid_group_regexp'])
    {
      dbg ("apply standard regexp");
      if (preg_match ($forbid_group_regexp, $name))
        {
          fb (_("This group name is not allowed."), 1);
          return 0;
        }
    }

  # Forbidden names: check the site-specific list if a list is given
  # (by consequence, the variable return true).
  if ($GLOBALS['specific_forbid_group_regexp'])
    {
      dbg ("apply specific regexp");
      if (preg_match ($GLOBALS['specific_forbid_group_regexp'], $name))
        {
          fb (_("This group name is not allowed."), 1);
          return 0;
        }
    }

  if (strpos ($name, "_") === FALSE)
    return 1;
  fb (_("Group name cannot contain underscore for DNS reasons."), 1);
  return 0;
}

function account_gensalt ($salt_base64_length = 16)
{
  # Note: $salt_base64_length = 16 for SHA-512, cf. crypt(3)
  $salt_byte_length = $salt_base64_length * 6 / 8;
  $rand_bytes = phpass_get_random_bytes ($salt_byte_length);
  return phpass_encode64 ($rand_bytes, $salt_byte_length);
}

# Generate unix pw.
function account_genunixpw ($plainpw)
{
  return account_encryptpw ($plainpw);
}

function account_encryptpw ($plainpw)
{
  $salt = account_gensalt (16);
  # rounds=5000 is the 2010 glibc default, possibly we'll upgrade in
  # the future, better have this explicit.
  # Cf. http://www.akkadia.org/drepper/sha-crypt.html
  $pfx = '$6$rounds=5000$';
  return crypt ($plainpw, "$pfx$salt");
}

# Return next userid.
function account_nextuid ()
{
  db_query ("SELECT max(unix_uid) AS maxid FROM user");
  $row = db_fetch_array ();
  return ($row[maxid] + 1);
}

function account_validpw ($stored_pw, $plain_pw)
{
  if (empty ($stored_pw) || empty ($plain_pw))
    return false;
  if (strlen ($stored_pw) < 2) # Disabled account, for sure.
    return false;
  return crypt ($plain_pw, $stored_pw) == $stored_pw;
}

function account_key_separator ()
{
  return '###';
}

function account_filter_empty_keys ($keys)
{
  return array_filter ($keys, function ($x) { return !empty (trim ($x)); });
}

function account_get_authorized_keys ($uid = 0)
{
  if (empty ($uid))
    $uid = user_getid ();
  $res = db_execute (
    "SELECT authorized_keys FROM user WHERE user_id = ?", [$uid]
  );
  $row = db_fetch_array ($res);
  if (empty ($row['authorized_keys']))
    $row['authorized_keys'] = '';
  $keys = explode (account_key_separator (), $row['authorized_keys']);
  return account_filter_empty_keys ($keys);
}

function account_join_keys ($keys)
{
  return join (account_key_separator (), account_filter_empty_keys ($keys));
}

function account_register_keys ($keys, $uid = 0)
{
  $res = db_execute ("UPDATE user SET authorized_keys = ? WHERE user_id = ?",
    [account_join_keys ($keys), $uid]
  );
  if (!$res)
    {
      fb (_("Error while registering keys"), 1);
      return;
    }
  if (count ($keys))
    fb (_("Keys registered"));
  else
    fb (_("No key is registered"));
}

function account_new_keys_alert ($user_id)
{
  global $sys_name;
  $subject = "$sys_name " . _("SSH key changed on your account");
  # TRANSLATORS: the argument is site name (like Savannah).
  $message = sprintf (
    _("Someone, presumably you, has changed your SSH keys on %s.\n"
      . "If it wasn't you, maybe someone is trying to compromise your "
      . "account..."), $sys_name
  );
  # TRANSLATORS: the argument is site name (like Savannah).
  $message .= "\n" . sprintf (_("-- the %s team."), $sys_name) . "\n";
  sendmail_mail (
    ['to' => user_get_email ($user_id)],
    ['subject' => $subject, 'body' => $message]
  );
}
?>
