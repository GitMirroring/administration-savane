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

function test_encryption ($temp_dir, $level, &$output)
{
  $gpg_name = gpg_name ();
  # The message is a slightly modified ASCII art
  # from https://www.gnu.org/graphics/gnu-ascii2.html .
  $message = "
  ,' ,-_-. '.
 ((_/)o o(\\_))
  `-'(. .)`-'
      \\_/\n";
  list ($key_id, $gpg_result) = find_enc_key ($temp_dir);
  if ($gpg_result)
    {
      $gpg_result = 2;
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

function import_key ($key, $home)
{
  global $sys_gpg_name;
  $cmd = "$sys_gpg_name --home '$home' --batch -q --import";
  return utils_run_proc ($cmd, $out, $err, ['in' => $key]);
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

# Find first encryption-capable key listed; this doesn't take into account
# expiry dates etc.
function find_enc_key ($home)
{
  $key_list = list_keys ($home);
  if (empty ($key_list))
    return [null, 2];
  foreach ($key_list as $line)
    {
      $fields = explode (':', $line);
      if (empty ($fields[11]))
        continue;
      if ($fields[0] !== 'pub')
        continue;
      if (false === strpos ($fields[11], 'E'))
        continue;
      $key_id = $fields[4];
      if (preg_match ("/^[0-9A-F]*$/", $key_id))
        return [$key_id, 0];
    }
  return [null, 2];
}

function get_enc_key ($user_id)
{
  $key = user_get_field ($user_id, 'gpg_key');
  if (empty ($key))
    return [null, null, 3];
  $temp_dir = utils_mktemp ("sv-gpg", 'dir');
  if (empty ($temp_dir))
    return [null, null, 4];
  if (import_key ($key, $temp_dir))
    list ($key_id, $error) = ['', 2];
  else
    list ($key_id, $error) = find_enc_key ($temp_dir);
  if ($error)
    utils_rm_fr ($temp_dir);
  return [$key_id, $temp_dir, $error];
}

function error_str ($code)
{
  $codes = [
    1 => _("Encryption failed."),
    2 => _("No key for encryption found."),
    3 => _("Can't extract user_id from database."),
    4 => _("Can't create temporary files."),
    5 => _("Extracted GPG key ID is invalid.")
  ];
  if (array_key_exists ($code, $codes))
    return $codes[$code];
  return '';
}

function run_encryption ($key, $message, $home)
{
  global $sys_gpg_name;
  $error_code = 0;
  $error_msg = '';
  $cmd = "$sys_gpg_name --home='$home' --trust-model always --batch "
    . "-a --encrypt -r $key";
  $res = utils_run_proc ($cmd, $encrypted, $err, ['in' => $message]);
  if ($res)
    {
      $error_code = 1;
      $encrypted = '';
    }
  if ($error_code != 0 || $encrypted === "")
    {
      $encrypted = $error_msg = "";
      $error_msg = error_str ($error_code);
      if ($error_msg === '')
        {
          trigger_error ("Unknown error code $error_code");
          $error_code = -1;
        }
    }
  return [$error_code, $error_msg, $encrypted];
}
} # namespace gpg {

namespace {
function gpg_run_checks ($key, $run_encryption = true, $level = '2')
{
  $ret = "";
  $ret .= "<h$level>" . _("GnuPG version") . "</h$level>\n";

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
  global $sys_gpg_name;
  list ($key, $temp_dir, $error) = gpg\get_enc_key ($user_id);
  if ($error)
    return [$error, gpg\error_str ($error), ''];
  $ret = gpg\run_encryption ($key, $message, $temp_dir);
  utils_rm_fr ($temp_dir);
  return $ret;
}
} # namespace {
?>
