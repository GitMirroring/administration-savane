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
function gpg_name ()
{
  return "'{$GLOBALS['sys_gpg_name']}'";
}
function gpg_version ()
{
  utils_run_proc (gpg_name () . " --version", $output, $err);
  return $output;
}

function test_listing ($temp_dir, $level, &$ret)
{
  $gpg_name = gpg_name ();
  $ret .= "<h$level>" . _("Listing key") . "</h$level>\n"
    . "<p>" . _("Output:") . "</p>\n";
  $cmd = "$gpg_name --home $temp_dir --list-keys --fingerprint ";
  $my_env = $_ENV;
  # Let non-ASCII user IDs show up in a readable way.
  $my_env['LC_ALL'] = "C.UTF-8";
  $gpg_result =
    utils_run_proc ($cmd, $gpg_output, $gpg_errors, ['env' => $my_env]);
  $ret .= "<pre>\n";
  $ret .= utils_specialchars ($gpg_output);
  $ret .= "</pre>\n";
  $ret .= "<p>" . _("Errors:") . "</p>\n";
  $ret .= "<pre>\n";
  $ret .= utils_specialchars ($gpg_errors);
  $ret .= "</pre>\n";
  $ret .= "<p>" . _("Exit status:") . " ";
  $ret .= $gpg_result . "</p>\n";
  return $gpg_result;
}

function test_import ($key, $temp_dir, $level, &$output)
{
  $gpg_name = gpg_name ();
  $output .= "<h$level>" . _("Importing keyring") . "</h$level>\n";
  $cmd = "$gpg_name --home '$temp_dir' --batch --import";
  $d_spec = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
  $my_env = $_ENV;
  $my_env['LC_ALL'] = "C.UTF-8";
  $gpg_result = utils_run_proc ($cmd, $gpg_output, $gpg_errors,
    ['in' => $key, 'env' => $my_env]
  );
  $output .= "<pre>\n";
  $output .= utils_specialchars ($gpg_errors);
  $output .= "</pre>\n";
  $output .= "<p>" . _("Exit status:") . " ";
  $output .= $gpg_result . "</p>\n";
  return $gpg_result;
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
  $gpg_name = gpg_name ();
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
  $output .= "<h$level>" . _("Test Encryption") . "</h$level>\n";
  if ($gpg_result)
    $output .= "<p>" . _("Errors:") . " " . $gpg_error . "</p>\n";
  else
    {
      $output .= "<p>"
       . _("Encryption succeeded; you should be able to decrypt this with\n"
           . "<em>gpg --decrypt</em>:") . "</p>\n";
      $output .= "<pre>" . $gpg_out . "</pre>\n";
    }
  return $gpg_result;
}

function run_tests ($key, $temp_dir, &$output, $run_encryption, $level)
{
  if (test_import ($key, $temp_dir, $level, $output))
    return;
  if (test_listing ($temp_dir, $level, $output))
    return;
  if ($run_encryption)
    test_encryption ($temp_dir, $level, $output);
}

function import_key ($key)
{
  global $sys_gpg_name;
  $error = 0;
  if (empty ($key))
    return GPG_ERROR_NO_USER_ID;
  $temp_dir = utils_mktemp ("sv-gpg", 'dir');
  if (empty ($temp_dir))
    return [$temp_dir, GPG_ERROR_NO_TEMP_DIR];
  $cmd = "$sys_gpg_name --home '$temp_dir' --batch -q --import";
  if (utils_run_proc ($cmd, $out, $err, ['in' => $key]))
    $error = GPG_ERROR_NO_USABLE_KEY;
  return [$temp_dir, $error];
}

function list_keys ($home)
{
  global $sys_gpg_name;
  $cmd = "$sys_gpg_name --home='$home' --list-keys --with-colons";
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
        $key = user_get_field ($uid_k, 'gpg_key');
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
  global $sys_gpg_name;
  $cmd = "$sys_gpg_name --home='$home' --trust-model always --batch "
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

function encrypt_to ($uid_k, $message)
{
  global $sys_gpg_name;
  list ($key, $temp_dir, $error) = get_key ($uid_k);
  if ($error)
    return [$error, error_str ($error), ''];
  $ret = run_encryption ($key, $message, $temp_dir);
  utils_rm_fr ($temp_dir);
  return $ret;

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
} # namespace {
?>
