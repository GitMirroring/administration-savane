<?php
# GPG-specific routines.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2023 Ineiev
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

function gpg_version ($gpg_name)
{
  $cmd = "$gpg_name --version";
  $d_spec =
    [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["file", "/dev/null", "a"]];

  $gpg_proc = proc_open ($cmd, $d_spec, $pipes);
  fclose ($pipes[0]);
  $output = stream_get_contents ($pipes[1]);
  fclose ($pipes[1]);
  proc_close ($gpg_proc);
  return $output;
}

function test_gpg_listing ($gpg_name, $temp_dir, $level, &$ret)
{
  $ret .= "<h$level>" . _("Listing key") . "</h$level>\n"
    . "<p>" . _("Output:") . "</p>\n";
  $cmd = "$gpg_name --home $temp_dir --list-keys --fingerprint ";
  $d_spec = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
  $my_env = $_ENV;
  # Let non-ASCII user IDs show up in a readable way.
  $my_env['LC_ALL'] = "C.UTF-8";
  $gpg_proc = proc_open ($cmd, $d_spec, $pipes, NULL, $my_env);
  fclose ($pipes[0]);
  $gpg_output = stream_get_contents ($pipes[1]);
  $gpg_errors = stream_get_contents ($pipes[2]);
  fclose ($pipes[1]); fclose ($pipes[2]);
  $gpg_result = proc_close ($gpg_proc);
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

function test_gpg_import ($gpg_name, $key, $temp_dir, $level, &$output)
{
  $output .= "<h$level>" . _("Importing keyring") . "</h$level>\n";
  $cmd = "$gpg_name --home '$temp_dir' --batch --import";
  $d_spec = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
  $my_env = $_ENV;
  $my_env['LC_ALL'] = "C.UTF-8";
  $gpg_proc = proc_open ($cmd, $d_spec, $pipes, NULL, $my_env);
  fwrite ($pipes[0], $key);
  fclose ($pipes[0]);
  $gpg_errors = stream_get_contents ($pipes[2]);
  fclose ($pipes[1]); fclose ($pipes[2]);
  $gpg_result = proc_close ($gpg_proc);
  $output .= "<pre>\n";
  $output .= utils_specialchars ($gpg_errors);
  $output .= "</pre>\n";
  $output .= "<p>" . _("Exit status:") . " ";
  $output .= $gpg_result . "</p>\n";
  return $gpg_result;
}

function gpg_encrypt_cmd ($user_id)
{
  global $sys_gpg_name, $sys_dbname, $sys_dbhost;

  return "perl '" . dirname (__FILE__)
    . "/../../perl/encrypt-to-user/index.pl' --gpg='$sys_gpg_name' "
    . "--user='$user_id' --dbname='$sys_dbname' --dbhost='$sys_dbhost'";
}

function test_gpg_encryption ($gpg_name, $temp_dir, $level, &$output)
{
  global $sys_dbuser, $sys_dbpasswd;
  # The message is a slightly modified ASCII art
  # from https://www.gnu.org/graphics/gnu-ascii2.html .
  $message = "
  ,' ,-_-. '.
 ((_/)o o(\\_))
  `-'(. .)`-'
      \\_/\n";
  $cmd = gpg_encrypt_cmd (user_getid ());
  $d_spec = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
  $gpg_proc = proc_open ($cmd, $d_spec, $pipes);

  fwrite ($pipes[0], "$sys_dbuser\n");
  fwrite ($pipes[0], "$sys_dbpasswd\n");
  fwrite ($pipes[0], $message);
  fclose ($pipes[0]);
  $encrypted_message = stream_get_contents ($pipes[1]);
  $gpg_stderr = stream_get_contents ($pipes[2]);
  fclose ($pipes[1]); fclose ($pipes[2]);
  $gpg_result = proc_close ($gpg_proc);
  $gpg_error = "";
  if ($gpg_result != 0 or $encrypted_message === false or $encrypted_message === "")
    {
      $encrypted_message = "";
      if ($gpg_result == 1)
        $gpg_error = _("Encryption failed.");
      elseif ($gpg_result == 2)
        $gpg_error = _("No key for encryption found.");
      elseif ($gpg_result == 3)
        $gpg_error = _("Can't extract user_id from database.");
      elseif ($gpg_result == 4)
        $gpg_error = _("Can't create temporary files.");
      elseif ($gpg_result == 5)
        $gpg_error = _("Extracted GPG key ID is invalid.");
      $encrypted_message = "";
    }
  $output .= "<h$level>" . _("Test Encryption") . "</h$level>\n";
  if ($gpg_result)
    $output .= "<p>" . _("Errors:") . " " . $gpg_error . "</p>\n";
  else
    {
      $output .= "<p>"
       . _("Encryption succeeded; you should be able to decrypt this with\n"
           . "<em>gpg --decrypt</em>:") . "</p>\n";
      $output .= "<pre>" . $encrypted_message . "</pre>\n";
    }
  return $gpg_result;
}

function run_gpg_tests ($gpg_name, $key, $temp_dir, &$output,
  $run_encryption = true, $level = '2'
)
{
  if (test_gpg_import ($gpg_name, $key, $temp_dir, $level, $output))
    return;
  if (test_gpg_listing ($gpg_name, $temp_dir, $level, $output))
    return;
  if (!$run_encryption)
    return;
  test_gpg_encryption ($gpg_name, $temp_dir, $level, $output);
}

function run_gpg_checks ($key, $run_encryption = true, $level = '2')
{
  $ret = "";
  $ret .= "<h$level>" . _("GnuPG version") . "</h$level>\n";
  $gpg_name = "'" . $GLOBALS['sys_gpg_name'] . "'";

  $ret .= "<pre>\n";
  $ret .= utils_specialchars (gpg_version ($gpg_name));
  $ret .= "</pre>\n";

  $temp_dir = exec ("mktemp -d");
  if (is_dir ($temp_dir))
    {
      run_gpg_tests ($gpg_name, $key, $temp_dir, $ret, $run_encryption);
      system ("rm -r '" . $temp_dir . "'");
    }
  else
    $ret .= "<p>" . _("Can't create temporary directory.") . "</p>\n";
  $ret .= "\n<hr />\n";
  return $ret;
}

function encrypt_to_user ($user_id, $message)
{
  global $sys_dbuser, $sys_dbpasswd;
  $cmd = gpg_encrypt_cmd ($user_id);

  $d_spec = [
    0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["file", "/dev/null", "a"]
  ];
  $gpg_proc = proc_open ($cmd, $d_spec, $pipes);
  fwrite ($pipes[0], "$sys_dbuser\n");
  fwrite ($pipes[0], "$sys_dbpasswd\n");
  fwrite ($pipes[0], $message);
  fclose ($pipes[0]);
  $encrypted = stream_get_contents ($pipes[1]);
  fclose ($pipes[1]);
  $error_msg = '';
  $error_code = proc_close ($gpg_proc);
  $codes = [
    1 => _("Encryption failed."),
    2 => _("No key for encryption found."),
    3 => _("Can't extract user_id from database."),
    4 => _("Can't create temporary files."),
    5 => _("Extracted GPG key ID is invalid.")
  ];

  if ($error_code != 0 || $encrypted === false || $encrypted === "")
    {
      $encrypted = $error_msg = "";
      if (array_key_exists ($error_code, $codes))
        $error_msg = $codes[$error_code];
      elseif (!$error_code) # Unknown error, strangely coded as zero.
        $error_code = -1;
    }
  return [$error_code, $error_msg, $encrypted];
}
?>
