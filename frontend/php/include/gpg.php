<?php
# GPG-specific routines.
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

namespace {
require_once (dirname (__FILE__) . "/utils.php");
require_once (dirname (__FILE__) . "/user.php");
}

namespace gpg {
# Path to gpg, quoted just in case for using in command line.
function gpg_name ()
{
  return "'{$GLOBALS['sys_gpg_name']}'";
}
function gpg_version ()
{
  utils_run_proc (gpg_name () . " --version", $output, $err);
  return $output;
}

function expand_command_results ($res, $out, $err, $skip_output = true)
{
  $ret = '';
  $have_out = !($skip_output || empty ($out));
  if ($have_out)
    {
      $ret .= "<p>" . _("Output:") . "</p>\n<pre>\n";
      $ret .= utils_specialchars ($out);
      $ret .= "</pre>\n";
    }
  if (empty ($err) && empty ($res) && $have_out)
    return $ret;
  if (!empty ($err))
    {
      $ret .= "<p>" . _("Errors:") . "</p>\n";
      $ret .= "<pre>\n";
      $ret .= utils_specialchars ($err);
      $ret .= "</pre>\n";
    }
  return "$ret<p>" . _("Exit status:") . " $res</p>\n";
}

function minified_tests ($key, $home, $level, &$ret)
{
  $ret .= html_h ($level, _('Importing minified key'));
  $res = test_import ($key, $home, $ret);
  if ($res)
    return $res;
  $ret .= html_h ($level, _('Listing minified key'));
  return test_gpg_command ($home, '--list-sigs --fingerprint', $ret);
}

function test_minify ($key, $temp_dir, $level, &$ret)
{
  list ($minified, $error, $err, $res) = export_minified ($temp_dir);
  $ret .= html_h ($level, _("Minifying key"));
  $ret .= expand_command_results ($res, $minified, $err);
  if ($res)
    return $res;
  $ret .= html_dl ([
    _("Original key size") => strlen ($key),
    _("Minified key size") => strlen ($minified)
  ]);
  $home = utils_mktemp ("sv-gpg-mini", 'dir');
  if (empty ($home))
    {
      $ret .= "<p>" . _("Can't create temporary directory.") . "</p>\n";
      return 1;
    }
  $res = minified_tests ($minified, $home, $level, $ret);
  utils_rm_fr ($home);
  return $res;
}

# Let non-ASCII user IDs show up in a readable way.
function no_i18n_env ()
{
  $my_env = $_ENV;
  $my_env['LC_ALL'] = "C.UTF-8";
  return $my_env;
}

function test_gpg_command ($temp_dir, $command, &$ret, $in = null)
{
  $cmd = gpg_name () . " --home '$temp_dir' $command";
  $aux = ['env' => no_i18n_env ()];
  if ($in !== null)
    $aux['in'] = $in;
  $res = utils_run_proc ($cmd, $out, $err, $aux);
  $ret .= expand_command_results ($res, $out, $err, false);
  return $res;
}

function test_listing ($temp_dir, $level, &$ret, $test_against_email = false)
{
  $label = _("Listing key");
  if ($test_against_email)
    {
      $email = user_get_email (0);
      $email_string = utils_specialchars ("<$email>");
      # TRANSLATORS: The argument is email address.
      $label = sprintf (_("Listing keys for %s"), $email_string);
    }
  $ret .= html_h ($level, $label);
  $options = '--list-keys --fingerprint';
  if ($test_against_email)
    $options .= " $email";
  $res = test_gpg_command ($temp_dir, $options, $ret);
  if (!$test_against_email || !$res)
    return $res;
  # TRANSLATORS: The argument is email address.
  $msg = sprintf (
    _("Note: no key for your registered email %s is found."), $email_string
  );
  $ret .= "<p><b>$msg</b></p>\n";
  return $res;
}

function test_import ($key, $temp_dir, &$output)
{
  return test_gpg_command ($temp_dir, '--batch --import', $output, $key);
}

define ('GPG_ERROR_GPG_FAILED', 1);
define ('GPG_ERROR_VERIFY_FAILED', 2);
define ('GPG_ERROR_NO_USABLE_KEY', 3);
define ('GPG_ERROR_NO_USER_ID', 4);
define ('GPG_ERROR_NO_TEMP_DIR', 5);
define ('GPG_ERROR_INVALID_KEY', 6);

define ('GNUPG_ENCRYPT_CAPABILITY', 'E');
define ('GNUPG_SIGN_CAPABILITY', 'S');

# The message is a slightly modified ASCII art
# from https://www.gnu.org/graphics/gnu-ascii2.html .
function test_message ()
{
  return "
  ,' ,-_-. '.
 ((_/)o o(\\_))
  `-'(. .)`-'
      \\_/\n";
}

function test_encryption ($temp_dir, $level, &$output)
{
  test_listing ($temp_dir, $level, $output, true);
  $message = test_message ();
  list ($key_id, $gpg_result) = find_appropriate_key ($temp_dir);
  if ($gpg_result)
    {
      $gpg_result = GPG_ERROR_NO_USABLE_KEY;
      $gpg_error = error_str ($gpg_result);
    }
  else
    list ($gpg_result, $gpg_error, $gpg_out) =
      run_encryption ($key_id, $message, $temp_dir);
  $output .= html_h ($level, _("Test Encryption"));
  if ($gpg_result)
    $output .= "<p>" . _("Errors:") . " $gpg_error</p>\n";
  else
    {
      $output .= "<p>"
       . _("Encryption succeeded; you should be able to decrypt this with\n"
           . "<em>gpg --decrypt</em>:") . "</p>\n";
      $output .= "<pre>$gpg_out</pre>\n";
    }
  return $gpg_result;
}

function run_tests ($key, $temp_dir, &$output, $run_encryption, $level)
{
  $output .= html_h ($level, _("Importing keyring"));
  if (test_import ($key, $temp_dir, $output))
    return;
  if (test_listing ($temp_dir, $level, $output))
    return;
  if (test_minify ($key, $temp_dir, $level, $output))
    return;
  if ($run_encryption)
    test_encryption ($temp_dir, $level, $output);
}

function import_key ($key)
{
  $error = 0;
  if (empty ($key))
    return GPG_ERROR_NO_USER_ID;
  $temp_dir = utils_mktemp ("sv-gpg", 'dir');
  if (empty ($temp_dir))
    return [$temp_dir, GPG_ERROR_NO_TEMP_DIR];
  $cmd = gpg_name () . " --home '$temp_dir' --batch -q --import";
  if (utils_run_proc ($cmd, $out, $err, ['in' => $key]))
    $error = GPG_ERROR_NO_USABLE_KEY;
  return [$temp_dir, $error];
}

function list_keys ($home)
{
  $cmd = gpg_name () . " --home='$home' --list-keys --with-colons";
  $res = utils_run_proc ($cmd, $out, $err);
  if ($res)
    return null;
  return explode ("\n", $out);
}

# Find first key with the given capability listed.
function find_appropriate_key ($home, $capability = GNUPG_ENCRYPT_CAPABILITY)
{
  $key_list = list_keys ($home);
  if (empty ($key_list))
    return [null, GPG_ERROR_NO_USABLE_KEY];
  foreach ($key_list as $line)
    {
      $fields = explode (':', $line);
      if (empty ($fields[11]))
        continue;
      if ($fields[0] !== 'pub')
        continue;
      if (false === strpos ($fields[11], $capability))
        continue;
      $key_id = $fields[4];
      if (preg_match ("/^[0-9A-F]*$/", $key_id))
        return [$key_id, 0];
    }
  return [null, GPG_ERROR_NO_USABLE_KEY];
}

# Import keys of user $uid_k when it's a number, or use $uid_k as the keys
# to import, look for a key with requested $capability.  Return an array
# with key ID, the directory where the keys are imported, and the error code.
function get_key ($uid_k, $capability = GNUPG_ENCRYPT_CAPABILITY)
{
  $key = $uid_k;
  if (ctype_digit ($uid_k))
    {
      if (user_exists ($uid_k))
        $key = user_get_gpg_key ($uid_k, true);
      else
        return [null, null, GPG_ERROR_NO_USER_ID];
    }
  list ($temp_dir, $error) = import_key ($key);
  $key_id = null;
  if (empty ($error))
    list ($key_id, $error) = find_appropriate_key ($temp_dir, $capability);
  if ($error && !empty ($temp_dir))
    utils_rm_fr ($temp_dir);
  return [$key_id, $temp_dir, $error];
}

function error_str ($code)
{
  $codes = [
    GPG_ERROR_GPG_FAILED => _("GnuPG invocation failed."),
    GPG_ERROR_VERIFY_FAILED => _("GPG signature verification failed."),
    GPG_ERROR_NO_USABLE_KEY => _("No usable key found."),
    GPG_ERROR_NO_USER_ID => _("Can't extract user_id from database."),
    GPG_ERROR_NO_TEMP_DIR => _("Can't create temporary files."),
    GPG_ERROR_INVALID_KEY => _("Extracted GPG key ID is invalid.")
  ];
  if (array_key_exists ($code, $codes))
    return $codes[$code];
  return '';
}

function expand_error ($res, $e_code, $out = null, $err = null)
{
  if (empty ($res))
    return [0, ''];
  $error_msg = error_str ($e_code);
  foreach (['output' => $out, 'error' => $err] as $k => $v)
    if ($v !== null)
      $error_msg .= "\n$k: $v\n";
  if ($error_msg === '')
    {
      trigger_error ("Unknown error code $e_code");
      $e_code = -1;
    }
  return [$e_code, $error_msg];
}

function run_encryption ($key, $message, $home)
{
  $cmd = gpg_name () . " --home='$home' --trust-model always --batch "
    . "-a --encrypt -r $key";
  $res = utils_run_proc ($cmd, $encrypted, $err, ['in' => $message]);
  list ($error_code, $error_msg) =
    expand_error ($res, GPG_ERROR_GPG_FAILED);
  if ($error_code)
    $encrypted = '';
  return [$error_code, $error_msg, $encrypted];
}

# Write signed data and signature to temporary files.  Return
# an array with temporary file names.
function make_verify_input ($input)
{
  $ret = [];
  foreach ($input as $i => $data)
    {
      $t = utils_mktemp ("sv-gpgv$i");
      if (empty ($t))
        {
          foreach ($ret as $f)
            unlink ($f);
          return null;
        }
      $ret[] = $t;
      $fd = fopen ($t, 'w');
      fwrite ($fd, $data);
      fclose ($fd);
    }
  return $ret;
}

function verify ($home, $input)
{
  $cmd = gpg_name () . " --home='$home' --batch --trust-model always";
  $op = count ($input) > 1? 'verify': 'decrypt';
  $in_files = make_verify_input ($input);
  if (empty ($in_files))
    return [GPG_ERROR_NO_TEMP_DIR, error_str (GPG_ERROR_NO_TEMP_DIR), ''];
  $files = join (' ', $in_files);
  $res = utils_run_proc ("$cmd --$op $files", $out, $err);
  list ($error_code, $error_msg) =
    expand_error ($res, GPG_ERROR_VERIFY_FAILED);
  $decrypted = $op == 'decrypt'? $out: $input[1];
  foreach ($in_files as $f)
    unlink ($f);
  return [$error_code, $error_msg, $decrypted];
}

# Check if the message is signed with a key registered for the given user,
# and extract it.
function verify_for ($user_id, $input)
{
  list ($key, $home, $error) = get_key ($user_id, GNUPG_SIGN_CAPABILITY);
  if ($error)
    return [$error, error_str ($error), ''];
  $ret = verify ($home, $input);
  utils_rm_fr ($home);
  return $ret;
}

function encrypt_to ($uid_k, $message)
{
  list ($key, $temp_dir, $error) = get_key ($uid_k);
  if ($error)
    return [$error, error_str ($error), ''];
  $ret = run_encryption ($key, $message, $temp_dir);
  utils_rm_fr ($temp_dir);
  return $ret;

}

function export_minified ($temp_dir)
{
  # As of 2024-06, Savannah has the registered GPG keys as long as 2M
  # due to a big photo id, and as long as 300K due to hundreds of other
  # people's signatures.  Don't include that data in the minimized version.
  $cmd = gpg_name () . " --home='$temp_dir' --batch -a --export "
    . "--export-options=export-minimal,no-export-attributes";
  $res = utils_run_proc ($cmd, $out, $err, ['env' => no_i18n_env ()]);
  if ($res)
    {
      $error = GPG_ERROR_GPG_FAILED;
      return [$out, expand_error ($res, $err, $out, $err), $err, $res];
    }
  return [$out, expand_error ($res, 0), $err, $res];
}

function minify_key ($key)
{
  list ($temp_dir, $error) = import_key ($key);
  if ($error)
    return [null, $temp_dir, error_str ($error)];
  list ($minified, $error) = export_minified ($temp_dir);
  return [$minified, $temp_dir, $error[0]];
}
} # namespace gpg {

namespace {
function gpg_run_checks ($key, $run_encryption = true, $level = '2')
{
  $ret = html_h ($level, _("GnuPG version"));

  $ret .= "<pre>\n";
  $ret .= utils_specialchars (gpg\gpg_version ());
  $ret .= "</pre>\n";

  $temp_dir = utils_mktemp ("sv-gpg", 'dir');
  if (empty ($temp_dir))
    $ret .= "<p>" . _("Can't create temporary directory.") . "</p>\n";
  else
    {
      gpg\run_tests ($key, $temp_dir, $ret, $run_encryption, $level);
      utils_rm_fr ($temp_dir);
    }
  $ret .= "\n<hr />\n";
  return $ret;
}

function gpg_encrypt_to_user ($user_id, $message)
{
  return gpg\encrypt_to ($user_id, $message);
}
function gpg_minify_key ($key)
{
  list ($mini_key, $temp_dir, $error) = gpg\minify_key ($key);
  if (!empty ($temp_dir))
    utils_rm_fr ($temp_dir);
   return [$mini_key, $error];
}
} # namespace {
?>
