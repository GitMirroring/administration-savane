<?php
# Check your configuration against recommended values.
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

$testconfig_php = true;
require_once ("include/ac_config.php");
$sys_file_domain = '';
foreach (['database', 'mailman', 'savane-git', 'trackers/data'] as $inc)
  require_once ("include/$inc.php");
if (empty ($inside_siteadmin))
  $inside_siteadmin = false;

function get_section_no ($inc = false)
{
  static $section_no = null;
  if ($section_no === null)
    {
      $section_no = -1;
      $test_summary = [];
      $inc = true;
    }
  if (!$inc)
    return $section_no;
  $section_no++;
  $test_summary[$section_no] = [];
  return $section_no;
}

function test_h ($level, $title, $attr = null)
{
  global $test_sections;
  $sec_no = get_section_no (true);
  $test_sections[$sec_no] = [$title, $attr];
  if (empty ($attr))
    $attr = "sec-$sec_no";
  return html_h ($level, $title, $attr);
}

function add_summary ($summary)
{
  global $test_summary;
  if (empty ($summary))
    return;
  $test_summary[get_section_no ()][] = $summary;
}

function get_test_summaries ()
{
  global $test_summary, $test_sections;
  $h = test_h (2, 'Failed test summary', 'summary');
  $defs = [];
  for ($i = 0; $i <= get_section_no (); $i++)
    {
      if (empty ($test_summary[$i]))
        continue;
      list ($title, $id) = $test_sections[$i];
      $term = $title;
      if (!empty ($id))
        $term = "<a href='#$id'>$term</a>";
      $defs[$term] = join ("<hr />\n", $test_summary[$i]);
    }
  if (empty ($defs))
    return $h . "<p id='no-tests-failed'>No essential tests failed.</p>\n";
  return $h . html_dl ($defs);
}

function gpg_unset ($head)
{
  $msg = "<strong>GnuPG is not configured.</strong>";
  add_summary ($msg);
  return [true, "$head<p>$msg</p>\n"];
}

# Test if GPG executable is configured and can run, basically.
# Return zero on success.
function check_gpg_executable ()
{
  global $sys_gpg_name;
  $ret = test_h (2, "GnuPG", 'gnupg');
  if (!isset ($sys_gpg_name))
    return gpg_unset ($ret);
  $gpg_result = utils_run_proc  (
    gpg\gpg_name () . " --version", $gpg_output, $gpg_stderr
  );
  $defs = [
    'GPG command' => "<code>$sys_gpg_name</code>",
    '<code>--version</code> output' =>
      "<pre>\n" . utils_specialchars ($gpg_output) . "</pre>",
    "Exit code" => "<code>$gpg_result</code>",
    "<code>stderr</code> output" =>
      "<pre>\n" . utils_specialchars ($gpg_stderr) . "</pre>"
  ];
  if ($gpg_result)
    add_summary ('Cannot get GnuPG version.');
  return [$gpg_result, $ret . html_dl ($defs)];
}

function read_test_key ($algo)
{
  $dir = dirname (__FILE__) . "/testing/gpg";
  $keys = [];
  foreach (["$algo-pub.asc", "$algo-priv.asc"] as $p)
    {
      $k = utils_read_file ("$dir/$p");
      $keys[] = $k;
      if (!empty ($k))
        continue;
      $msg = "Cannot read $dir/$p";
      add_summary ($msg);
      return [null, $msg];
    }
  return [join ("\n", $keys), ''];
}

function run_gpg ($command, $msg, $input)
{
  $cmd = gpg\gpg_name () . " $command";
  $res = utils_run_proc ($cmd, $out, $err, ['in' => $input]);
  if (!$res)
    return [$out, $res, ''];
  $ret_msg = "Can't $msg, exit code $res<br />\n"
    . "<pre>$out</pre>\n<pre>$err</pre>\n";
  return [$out, $res, $ret_msg];
}

function gen_signature ($key_id, $home, $option, $input)
{
  list ($out, $res, $msg) = run_gpg (
    "--batch --home '$home' -u '$key_id' $option",
    'generate test signature', $input
  );
  return [[$out], $res, $msg];
}

function run_gpg_verify ($home, $signature, $input)
{
  list ($error_code, $error_msg, $dec) = gpg\verify ($home, $signature);
  if ($dec !== $input)
    return '<b>Extracted message differs from the signed one.</b>';
  if ($error_code)
    $error_msg;
  return 'OK';
}

function run_gpg_sign ($key_id, $home, $algo, &$defs)
{
  $input = gpg\test_message ();
  foreach (['-a --sign', '--clearsign', '--detach-sign'] as $option)
    {
      $term = "$algo, $option";
      list ($signature, $error, $msg) =
        gen_signature ($key_id, $home, $option, $input);
      if ($error)
        {
          add_summary ("Signing failed with $term.");
          $defs[$term] = $msg;
          continue;
        }
      if ($option == '--detach-sign')
        $signature[] = $input;
      $msg = run_gpg_verify ($home, $signature, $input);
      $defs[$term] = $msg;
      if ($msg !== 'OK')
        add_summary ("Verification failed with $term.");
    }
}

function test_gpg_algo_list ()
{
  return ['RSA', 'ECC25519'];
}

function encrypt_test ($key_id, $home, $input)
{
  return run_gpg (
    "--batch --trust-model always --home '$home' -r '$key_id' --encrypt",
    'encrypt', $input
  );
}

function decrypt_test ($key_id, $home, $input)
{
  return run_gpg ("--batch --home '$home' --decrypt", 'decrypt', $input);
}

function run_gpg_encrypt ($key_id, $home, $algo, &$defs)
{
  $input = gpg\test_message ();
  list ($enc, $res, $msg) = encrypt_test ($key_id, $home, $input);
  if (!$res)
    list ($dec, $res, $msg) = decrypt_test ($key_id, $home, $enc);
  if ($res)
    {
      add_summary ("Encryption failed with $algo.");
      $defs[$algo] = $msg;
      return;
    }
  if ($dec === $input)
    {
      $defs[$algo] = 'OK';
      return;
    }
  $defs[$algo] = "Decrypted sample doesn't match the clear text";
  add_summary ("Decrypted sampled does not match with $algo.");
}

function test_gpg_func ($algo, $capability, $func, &$defs)
{
  list ($key, $error) = read_test_key (strtolower ($algo));
  if (empty ($key))
    {
      $defs[$algo] = $error;
      return;
    }
  list ($key_id, $home, $error) =
    gpg\get_key ($key, $capability);
  if ($error)
    {
      $msg = gpg\error_str ($error);
      $defs[$algo] = $msg;
      add_summary ('Cannot get key.');
      return;
    }
  $func ($key_id, $home, $algo, $defs);
  utils_rm_fr ($home);
}

function test_gpg_verify ($algo, &$defs)
{
  test_gpg_func ($algo, GNUPG_SIGN_CAPABILITY, 'run_gpg_sign', $defs);
}

function test_gpg_encrypt ($algo, &$defs)
{
  test_gpg_func ($algo, GNUPG_ENCRYPT_CAPABILITY, 'run_gpg_encrypt', $defs);
}

function test_sys_gpg_key ()
{
  $defs = [];
  $key = gpg_get_sys_key ();
  $ret = test_h (3, 'Frontend key', 'frontend-key');
  if (empty ($key))
    {
      $msg = "GnuPG key isn't defined.";
      add_summary ($msg);
      return "$ret<p>$msg</p>\n";
    }
  $ret .= "<pre>" . utils_specialchars ($key) . "</pre>\n";
  return $ret . gpg_run_sys_checks ($key, 4);
}

function test_gpg ()
{
  list ($res, $ret) = check_gpg_executable ();
  if ($res)
    return $ret;
  $ret .= test_h (3, "Verify signature", 'gpg-verify-sig');
  $defs = [];
  foreach (test_gpg_algo_list () as $algo)
    test_gpg_verify ($algo, $defs);
  $ret .= html_dl ($defs);
  $ret .= test_h (3, 'Encrypt', 'gpg-encrypt');
  $defs = [];
  foreach (test_gpg_algo_list () as $algo)
    test_gpg_encrypt ($algo, $defs);
  $ret .= html_dl ($defs);
  return $ret . test_sys_gpg_key ();
}

function test_cgitrepos_presence ()
{
  if (!isset ($GLOBALS['sys_etc_dir']))
    return ['<strong>no $sys_etc_dir set</strong>', null];
  if (!file_exists ($GLOBALS['sys_etc_dir']))
    return ['<strong>no $sys_etc_dir directory exists</strong>', null];
  $fname = $GLOBALS['sys_etc_dir'] . '/cgitrepos';
  if (!file_exists ($fname))
    return ['<strong>no cgitrepos file exists in $sys_etc_dir</strong>', null];
  if (!is_readable ($fname))
    return ['<strong>cgitrepos in $sys_etc_dir is not readable</strong>', null];
  return [null, $fname];
}

function test_cgitrepos_time ($fname)
{
  $mtime = time () - filemtime ($fname);
  if ($mtime <= 3600)
    return null;
  $ret = ('<strong>cgitrepos has not been updated for ');
  if ($mtime < 100)
    return $ret . sprintf ('%.0f minutes</strong>', $mtime / 60);
  if ($mtime < 24 * 3600)
    return $ret . sprintf ('%.0f hours</strong>',  $mtime / 3600);
  return $ret . sprintf ('%.1f days</strong>',  $mtime / 24. / 3600);
}

function test_cgitrepos ()
{
  $disclaimer = 'Note that cgitrepos is an obsolete way to get git info.';
  list ($ret, $path) = test_cgitrepos_presence ();
  if ($ret !== null)
    return "$ret<br />$disclaimer";
  $ret = test_cgitrepos_time ($path);
  if ($ret === null)
    return 'OK';
  return "$ret<br />$disclaimer";
}

function sys_vcs_dir_not_set ()
{
  global $sys_vcs_dir;
  if (empty ($sys_vcs_dir))
    return '<em>$sys_vcs_dir not set</em>';
  if (empty ($sys_vcs_dir['git']))
    return '<em>$sys_vcs_dir["git"] not set</em>';
  if (empty ($sys_vcs_dir['git']['dir']))
    return '<em>$sys_vcs_dir["git"]["dir"] not set</em>';
  return null;
}

function test_git_dirs ()
{
  global $sys_vcs_dir;
  $error = sys_vcs_dir_not_set ();
  if ($error !== null)
    {
      add_summary ($error);
      return $error;
    }
  $git = $sys_vcs_dir["git"];
  $ret = "'dir': " . $git["dir"] . ' ';
  if (!is_dir ($git["dir"]))
    return "$ret <strong>no such directory</strong>";
  $ret .= " (directory exists)";
  $suf = '[\'clone-path\']';
  if (!empty ($git["clone-path"]))
    $ret .= "<br />\n'clone-path': " . $git["clone-path"];
  return $ret;
}

function test_repos (&$defs)
{
  $defs['cgitrepos'] = ['cgitrepos', test_cgitrepos ()];
  $defs['git directories'] = ['gitrepos', test_git_dirs ()];
}

function test_sys_upload_dir ()
{
  $path = utils_make_upload_file ("test.txt", $errors);
  if ($path === null)
    {
      add_summary ('Cannot make upload file.');
      return "<b>can't make file:</b> $errors";
    }
  $state = utils_disable_warnings (E_WARNING);
  $res = unlink ($path);
  utils_restore_warnings ($state);
  if ($res)
    return 'OK';
  add_summary ('Cannot remove upload file.');
  $e = error_get_last ();
  return '<b>unlink failed:</b>' . $e['message'];
}

function test_strftime_alternatives ($t, &$defs)
{
  global $sys_use_strftime;
  if (empty ($sys_use_strftime))
    {
      if (!function_exists ('strftime'))
        return;
      $sys_use_strftime = 1;
    }
  else
    unset ($sys_use_strftime);
  $mark = utils_strftime ('', null);
  $defs["$mark implementation"] =
    ['strftime-alt-timestamp', utils_strftime ($t, '%c')];
}

function test_strftime ()
{
  global $sys_use_strftime;
  $ret = test_h (2, "Implementation of strftime", 'strftime');
  $saved = $sys_use_strftime;
  $t = time ();
  $defs = [];
  list_sysvar ('use_strftime', $defs);
  $defs['Check for internal strftime'] =
    function_exists ('strftime')? 'Exists.': 'Absent.';
  $defs['Used implementation'] = ['strftime-imp10n', utils_strftime ('', null)];
  $defs["Timestamp $t"] = ['strftime-timestamp', utils_strftime ($t, '%c')];
  test_strftime_alternatives ($t, $defs);
  $ret .= html_dl ($defs);
  $sys_use_strftime = $saved;
  return $ret;
}

function test_captchadir ()
{
  global $sys_captchadir;
  $default_dir = '/usr/share/php';
  $ret = '';
  if (empty ($sys_captchadir))
    {
      $sys_captchadir = $default_dir;
      return "<p><strong>sys_captchadir isn't set.</strong></p>\n"
        . "<p>Falling back to default, $default_dir</p>\n";
    }
  return "<p><b>sys_captchadir</b> is set to $sys_captchadir</p>\n";
}

function test_captcha ()
{
  global $sys_captchadir;

  $ret = test_h (2, "Captcha", 'captcha') . test_captchadir ();
  if (!is_dir ($sys_captchadir))
   {
     $msg = "<strong>No $sys_captchadir directory found.</strong>";
     add_summary ($msg);
     return "$ret<p>$msg</p>\n";
   }
  $f = "$sys_captchadir/Text/CAPTCHA.php";
  if (!is_file ($f))
    {
      $msg = "<strong>No $f file found.</strong>";
      add_summary ($msg);
      return "$ret<p>$msg</p>\n";
    }
  return "$ret<p>Sample image:</p>\n"
    . "<p><img id='captcha' src='/captcha.php' alt='CAPTCHA' /></p>\n";
}

function test_mailman_failed ($ver, &$defs)
{
  $fail = false;
  $term = "<b>Error</b>";
  $def = '';
  if (empty ($ver))
    {
      $defs[$term] = "<b>No response</b>";
      return true;
    }
  if (array_key_exists ('pipe::error', $ver))
    {
      $def = "<b>Pipe error:</b>\n<pre>{$ver['pipe::error']}</pre>";
      $fail = true;
    }
  if (array_key_exists ('error', $ver))
    {
      $def .= "<b>Command failed:</b>\n<pre>{$ver['error']}</pre>\n";
      $fail = true;
    }
  if (!empty ($def))
    $defs[$term] = $def;
  return $fail;
}

function output_mailman_version ($ver)
{
  $defs = [];
  if (test_mailman_failed ($ver, $defs))
    return [false, html_dl ($defs)];
  if (empty ($ver['version']))
    return [false, ''];
  $defs = [
    'Version' => $ver['version'],
    'Generated password' => $ver['password'],
    'Timestamp' => $ver['timestamp']
  ];
  return [true, html_dl ($defs)];
}

function output_mailman_query ($q, &$defs)
{
  if (test_mailman_failed ($q, $defs))
    {
      add_summary ('Cannot run query.');
      return false;
    }
  $nested = [];
  foreach ($q as $k => $v)
     $nested[utils_specialchars ($k)] = utils_specialchars ($v);
  $defs['Query results'] = html_dl ($nested);
  return true;
}

function test_mailman ()
{
  $ret = test_h (2, "Mailman connection", 'mailman');
  $defs = [];
  $ver = mailman_get_version ();
  list ($have_version, $msg) = output_mailman_version ($ver);
  if ($have_version)
    {
      $ret .= $msg;
      output_mailman_query (mailman_query_list ('savannah-users'), $defs);
    }
  else
    {
      add_summary ('Cannot get script version.');
      $defs['Run time'] = sprintf ('%s ms', $ver['timestamp']);
    }
  $ret .= html_dl ($defs);
  if ($have_version && preg_match ("/^stub /", $ver['version']))
    $ret .= "<p><strong>This is a stub; write the real command "
      . "in \$sys_mailman_wrapper.</strong></p>\n";
  return $ret;
}

function test_hash_pfx ()
{
  global $sys_pw_prefix;
  $real_pfx = hash_get_pw_prefix ();
  if (empty ($sys_pw_prefix))
    return "<b>unset</b><br />\nThe effective value is <b>$real_pfx</b>.";
  $pfx = $sys_pw_prefix;
  if ($pfx != $real_pfx)
    $pfx .= "<br />\n"
      . "<i>Overridden; the effective value is</i> <b>$real_pfx</b>.";
  return $pfx;
}

function test_hash_cost ()
{
  global $sys_pw_rounds;
  $real_cost = hash_get_pw_cost ();
  if (empty ($sys_pw_rounds))
    return "<b>unset</b><br />\nThe effective value is <b>$real_cost</b>.";
  $cost = $sys_pw_rounds;
  if ($real_cost != $cost)
    $cost .= "<br />\n"
      . "<i>Overridden; the effective value is</i> <b>$real_cost</b>.";
  return $cost;
}

function test_hash_algo ($pfx, $prefix, $rounds)
{
  global $sys_pw_prefix, $sys_pw_rounds;
  $plain = 'foo \xff\xaa\xff \x9f\x98\x85';
  $sys_pw_prefix = $pfx;
  if ($pfx === $prefix)
    $sys_pw_rounds = $rounds;
  else
    unset ($sys_pw_rounds);
  $stored = hash_encryptpw ($plain);
  $ret = "<b>fail</b>";
  if ($stored === '*0')
    $ret .= ': algorithm is unsupported';
  elseif (account_validpw ($stored, $plain))
    $ret = 'OK';
  else
    add_summary ("Algorithm $pfx failed.");
  return $ret;
}

function test_hash_algos ()
{
  global $sys_pw_rounds, $sys_pw_prefix, $hash_silent_crypt;
  list ($prefix, $rounds, $silent) = [
    $sys_pw_prefix, $sys_pw_rounds, $hash_silent_crypt
  ];
  $hash_silent_crypt = true;
  $defs = [];
  foreach (hash_supported_pw_prefices () as $pfx)
    $defs[$pfx] = test_hash_algo ($pfx, $prefix, $rounds);
  list ($sys_pw_prefix, $sys_pw_rounds, $hash_silent_crypt) =
    [$prefix, $rounds, $silent];
  return html_dl ($defs);
}

function test_hash ()
{
  global $sys_use_php_crypt;
  $ret = test_h (2, 'Stored passwords', 'hash');
  $defs = [
    'Supported algorithms for new hashes' =>
      join (', ', hash_supported_pw_prefices ()),
    'Implementation used' => empty ($sys_use_php_crypt)?
      'libc via the sv_crypt script': 'PHP crypt() function',
    'sys_pw_prefix' => test_hash_pfx (), 'sys_pw_rounds' => test_hash_cost ()
  ];
  $ret .= html_dl ($defs);
  $saved_use = $sys_use_php_crypt;
  $ret .= test_h (3, 'Testing sv_crypt', 'sv-crypt');
  $sys_use_php_crypt = false;
  $ret .= test_hash_algos ();
  $ret .= test_h (3, 'Testing PHP crypt() function', 'php-crypt');
  $sys_use_php_crypt = true;
  $ret .= test_hash_algos ();
  $sys_use_php_crypt = $saved_use;
  return $ret;
}

function page_start ($inside_siteadmin)
{
  $ret = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n"
    . "  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
  $ret .=
    "<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en_US' lang='en_US'>\n"
    . "<head>\n"
    . "<meta http-equiv='content-type' content='text/html; charset=utf-8' />\n"
    . "<title>Basic configuration tests</title>\n"
    . "<link rel=\"stylesheet\" type=\"text/css\" "
    . "href=\"/css/internal/testconfig.css\" />\n"
    . "</head>\n\n"
    . "<body>\n";

  $ret .= test_h (1, "Basic pre-tests for Savane installation");
  if (empty ($inside_siteadmin))
    $ret .= "<p>This page should help you to check whether your installation\n"
      . "is properly configured. It shouldn't display any sensitive\n"
      . "information, since it could give details about your setup\n"
      . "to anybody.</p>\n";
  return $ret;
}

function check_source_code ()
{
  $h = test_h (2, "Savane source code", 'source-code');
  $commit = git_get_commit ();
  $cgit_url = git_get_savane_url ('');
  $tarball_name = git_get_tarball_name ();
  $tarball_url = git_get_tarball_url ();
  if (git_check_tarball ())
    $avail = "<strong>Fail.  You must make sure you offer the source code "
      . "correctly before making the website available to other "
      . "users.</strong>";
  else
    $avail = "OK";

  $defs = [
    'Configured Git commit' => $GLOBALS['ac_git_commit'],
    'Computed Git commit' => "<a href='$cgit_url'>$commit</a>",
    'Tarball' => "<a href='$tarball_url'>$tarball_name</a>",
    'Availability' => $avail
  ];
  if ($avail !== 'OK' && php_sapi_name () !== 'cli-server')
    add_summary ('Source code looks unaccessible.');
  return $h . html_dl ($defs);
}

function unknown_ini_val ($tag, $gv)
{
  $str = sprintf (
    "<tr><td>%s</td><td class=\"unset\">Unknown*</td><td>%s</td></tr>\n",
    $tag, $gv
  );
  add_summary ("$tag value is not set.");
  return [$str, true];
}

function good_ini_val ($tag, $t, $gv)
{
  $str = '<tr>' . html_join_enclosed ([$tag, $t, $gv], 'td') . "</tr>\n";
  return [$str, false];
}

function compare_ini_vals ($tag, $good, $cmp, $all_inis)
{
  $gv = utils_specialchars ($good);
  if (!array_key_exists ($tag, $all_inis))
    return unknown_ini_val ($tag, $gv);
  $ini_val = ini_get ($tag);
  $t = utils_specialchars ($ini_val);
  if ($cmp ($ini_val, $good))
    return good_ini_val ($tag, $t, $gv);
  $page = sprintf (
    "<tr><td>%s</td><td class=\"different\">%s</td><td>%s", $tag, $t, $gv
  );
  if ($all_inis[$tag]['access'] > PHP_INI_SYSTEM)
    $page .= " (can be set in php.ini, .htaccess or httpd.conf)";
  else
    $page .= " (can be set in php.ini or httpd.conf, but not in .htaccess)";
  $page .= "</td></tr>\n";
  add_summary ("$tag value differs from $good.");
  return [$page, false];
}

function php_conf_table_header ($summary)
{
  return "\n<table border=\"1\" summary=\"$summary\">\n"
    . "<tr><th>PHP Tag name</th><th>Local value</th>"
    . "<th>Suggested value</th></tr>\n";
}

function basic_php_conf_start ()
{
  $ret = test_h (2, "Basic PHP configuration", 'basic-php');
  $ret .= "<p>PHP version: " . phpversion () . "</p>\n";
  $ret .= php_conf_table_header ('PHP configuration');

  # Define missing constant to interpret the 'access' field.
  define ('PHP_INI_SYSTEM', 4);
  # Cf. http://www.php.net/manual/en/ini.core.php

  return $ret;
}

function check_tags ($phptags, $cmp, &$have_unset)
{
  $ret = '';
  $all_inis = ini_get_all ();
  foreach ($phptags as $tag => $good)
    {
      list ($str, $cmp_result) =
        compare_ini_vals ($tag, $good, $cmp, $all_inis);
      $ret .= $str;
      if (!$cmp_result)
        continue;
      $have_unset = true;
    }
  return $ret;
}

function check_basic_tags (&$have_unset)
{
  # cf. http://php.net/manual/en/ini.php
  $phptags = ['file_uploads' => '1', 'allow_url_fopen' => '1'];
  ksort ($phptags);

  $cmp = function ($a, $b) { return $a === $b; };
  return check_tags ($phptags, $cmp, $have_unset);
}

function return_bytes ($size)
{
  return utils_ini_size_bytes ($size);
}

# Check against minimum sizes.
function check_size_tags (&$have_unset)
{
  $phptags = [
    'memory_limit' => '4M', 'post_max_size' => '3M',
    'upload_max_filesize' => '2M'
  ];
  $cmp = function ($a, $b) { return return_bytes ($a) >= return_bytes ($b); };
  return check_tags ($phptags, $cmp, $have_unset);
}

function unset_warning ()
{
  return "<blockquote><p>* This tag was not found at all. "
    . "It is probably irrelevant to your PHP version so you may ignore this "
    . "entry.</p></blockquote>\n";
}

function basic_php_config ()
{
  $ret = basic_php_conf_start ();
  $have_unset = false;
  $ret .= check_basic_tags ($have_unset);
  $ret .= check_size_tags ($have_unset);
  $ret .= "</table>\n\n";
  if (!$have_unset)
    return $ret;
  $ret .= unset_warning ();
  return $ret;
}

function optional_php_config_start ()
{
  return test_h (2, "Optional PHP configuration", 'optional-php')
    . "<p>The following is not required to run Savane, but could enhance\n"
    . "security of your production server. Displaying errors is recommended:\n"
    . "they may annoy the user with warnings, but allow you to spot "
    . "and report\npotentially harmful bugs.</p>\n"
    . php_conf_table_header ('Optional PHP configuration');
}

function optional_php_check_tags (&$have_unset)
{
  $phptags = [
    'disable_functions' => 'passthru,popen,shell_exec,system',
    'display_errors' => '1', 'error_reporting' => (string)(E_ALL),
    'log_errors' => '1',
  ];
  $cmp = function ($a, $b) { return $a === $b; };
  return check_tags ($phptags, $cmp, $have_unset);
}

function optional_php_config ()
{
  $have_unset = false;
  $ret = optional_php_config_start ();
  $ret .= optional_php_check_tags ($have_unset) . "</table>\n";
  if ($have_unset)
    $ret .= unset_warning ();
  return $ret;
}

function list_facilities ($func, $items, $labels = [])
{
  $lab = [true => 'Exists.', false => 'Not found.'];
  foreach ($labels as $k => $v)
    $lab[$k] = $v;
  $defs = [];
  foreach ($items as $name => $comment)
    {
      if ($func ($name))
        $res = $lab[true];
      else
        {
          $res = sprintf ("<b>%s</b> <i>%s</i>", $lab[false], $comment);
          add_summary ("$name not found.");
        }
      $defs["<b>$name</b>"] = $res;
    }
  return html_dl ($defs);
}

function php_extensions ()
{
  $php_extensions =
    [
      'imap' => 'Composing signed email messages (php-imap) ! [RECOMMENDED]',
      'mailparse' => 'Parsing email messages (php-mailparse) ! [RECOMMENDED]',
      'mysqli' => 'Database access (php-mysqli) ! [REQUIRED]',
    ];
  return test_h (2, "PHP extensions", 'php-ext')
    . list_facilities (
        'extension_loaded', $php_extensions, [true => 'Loaded.']
      );
}

function php_functions ()
{
  $phpfunctions =
    [
      'crypt' => 'Used to handle passwords ! [REQUIRED]',
      'gettext' =>
        'You should install PHP with gettext support '
        . '(--with-gettext --enable-intl) ! [RECOMMENDED]',
      'strftime' => 'When this function is dropped from PHP '
        . '(deprecated in 8.1), date output is expected to slow down '
        . '! [RECOMMENDED]',
      'ctype_digit' => 'You must have a PHP version supporting ctype '
        . '(--enable-ctype) ! [REQUIRED]',
      'imagettfbbox' =>
        'Used by captcha library (--enable-gd --with-freetype) ! [RECOMMENDED]',
      'sem_get' =>
        'Used when connecting mailman and updating the group file '
        . '(--enable-sysvsem) ! [REQUIRED]',
      'hrtime'=> 'Optionally used in diagnostic timestamps.'
    ];
  return test_h (2, "PHP functions", 'php-func')
    . list_facilities ('function_exists', $phpfunctions);
}

function undefined_locale ($lang, $report_fails)
{
  if ($report_fails)
    add_summary ("Undefined locale for $lang.");
  return "Locale for $lang is not defined.";
}

function i18n_setup_failed ($lang, $err, $report_fails)
{
  if ($report_fails)
    add_summary ("$lang failed.");
  $ret = "<b>Fail:</b>\n<ul>\n";
  foreach ($err as $e)
    $ret .= "\n  <li>$e</li>\n";
  return "$ret</ul>\n";
}

function test_language ($lang, $report_fails = false)
{
  global $locale_list;
  if (!array_key_exists ($lang, $locale_list))
    return undefined_locale ($lang, $report_fails);
  $loc = $locale_list[$lang];
  $err = i18n_setup ($loc);
  if (!empty ($err))
    return i18n_setup_failed ($lang, $err, $report_fails);
  foreach (['Any', 'Apply'] as $str)
    {
      $res = gettext ($str);
      if (($res == $str  && $lang == 'en') || ($res != $str && $lang != 'en'))
        return "$str => $res";
    }
  if ($report_fails)
    add_summary ("$lang failed.");
  return "<b>Fail.</b> Check <code>locale -a</code> output,\n"
    . "be sure to install language-pack-* in Trisquel";
}

function test_disabled_languages ($loc_list)
{
  global $sys_linguas;
  $linguas = $sys_linguas;
  $ret = test_h (3, "Other defined languages", 'other-lang') . "<p>";
  if (empty ($loc_list))
    return "{$ret}none.</p>\n";
  $ret .= join (", ", array_keys ($loc_list)) . ".</p>\n";
  $defs = [];
  foreach ($loc_list as $code => $locale_names)
    {
      $sys_linguas .= ":$code";
      register_language ($code, $locale_names[0], $locale_names[1]);
      $defs[$code] = test_language ($code);
    }
  $sys_linguas = $linguas;
  return $ret . html_dl ($defs);
}

function get_locales ()
{
  $h = test_h (3, "Locales defined", 'locales-defined');
  $res = utils_run_proc ('locale -a', $out, $err);
  if ($res)
    return "<p>Can't get locales defined: </p>\n<pre>$err</pre>\n";
  $out = str_replace ("\n", ', ', substr ($out, 0, -1));
  return "$h<p>$out.</p>\n";
}

function test_i18n ()
{
  global $sys_linguas, $languages_available;
  $loc_list = $languages_available;
  $ret = test_h (2, "I18n", 'i18n') . get_locales ()
    . test_h (3, "sys_linguas", 'sys-linguas') . "<p>$sys_linguas</p>\n";
  $defs = [];
  foreach (explode (':', $sys_linguas) as $lang)
    {
      $defs[$lang] = test_language ($lang, true);
      if (array_key_exists ($lang, $loc_list))
        unset ($loc_list[$lang]);
    }
  $ret .= html_dl ($defs) . test_disabled_languages ($loc_list);
  i18n_setup ("en_US.UTF-8");
  return $ret;
}

function apache_envv ()
{
  $h = test_h (2, "Apache environment variables", 'apache-envv');
  $vv = [];
  foreach (['SAVANE_CONF', 'SV_LOCAL_INC_PREFIX'] as $var)
    {
      $vv[$var] = getenv ($var);
      if ($vv[$var] === false)
        $vv[$var] = '<b>unset</b>';
    }
  return $h . html_dl ($vv);
}

function test_db_fields ($t, $field_func, &$defs)
{
  $tstruct = get_table_structure ($t);
  foreach ($field_func as $f => $func)
    if (is_array ($func))
      $defs["$t.$f"] = $func[0] ($tstruct, $t, $f, $func[1]);
    else
      $defs["$t.$f"] = $func ($tstruct, $t, $f);
}

function check_for_db_test_marker ()
{
  $table = 'test_marker';
  $res = db_execute ("SHOW TABLES LIKE '$table'");
  if (!db_numrows ($res))
    return '';
  return "<p><strong>Note:</strong> table '$table' exists. "
    . "<i>This table should only be present in temporary databases "
    . "created for testing.</i></p>\n";
}

function test_db_structure ()
{
  $ret = test_h (2, 'Database structure', 'db-struct');
  $ret .= check_for_db_test_marker ();
  $defs = [];
  $table_fields = [
    'user' =>
      ['gpg_key' => 'check_mediumtext', 'confirm_hash' => 'check_varchar'],
    'user_preferences' => ['preference_value' => 'check_mediumtext'],
    'session' => ['session_hash' => 'check_varchar']
  ];
  foreach ($table_fields as $t => $field_func)
    test_db_fields ($t, $field_func, $defs);
  return $ret . html_dl ($defs);
}

function query_required_field_usage ()
{
  $subqueries = [];
  foreach (utils_get_tracker_list () as $tracker)
    $subqueries[] = "
      (
        SELECT
          '$tracker' AS `tracker`, `g`.`group_id`, `unix_group_name`,
          `f`.`bug_field_id`, `field_name`, `label`
        FROM
          `groups` `g` JOIN `{$tracker}_field_usage` `u`
            ON `g`.`group_id` = `u`.`group_id`
          JOIN `{$tracker}_field` `f` ON `u`.`bug_field_id` = `f`.`bug_field_id`
        WHERE `f`.`required` = 1 AND `u`.`use_it` = 0
      )";

  return db_execute (
    join (' UNION ', $subqueries)
    . ' ORDER BY `unix_group_name`, `tracker`, `field_name`, `label`'
  );
}

function print_db_values ($res, $columns)
{
  $header_row = "<tr>" . html_join_enclosed ($columns, 'th', '') . "</tr>\n";
  $ret = "<table border='1'>\n$header_row";
  while ($row = db_fetch_array ($res))
    {
      $ret .= "<tr>";
      foreach ($columns as $col)
        $ret .= "<td>{$row[$col]}</td>";
      $ret .= "</tr>\n";
    }
  return "$ret$header_row\n</table>\n";
}

function explain_unused_required_tracker_fields ($ids)
{
  $ret = "<p>Having required fields that aren't used is counter-intuitive "
    . "and may result in unexpected behavior when configuring trackers.</p>\n";
  $q = [];
  foreach ($ids as $tracker => $vals)
    $q[] = "
      UPDATE `{$tracker}_field_usage` SET `use_it` = 1
      WHERE `bug_field_id` IN (" . join (', ', array_unique ($vals)) . ");
    ";
  $query = join ("\n", $q);
  return
    "$ret<p>You can fix this issue with a query like <code>$query</code></p>\n";
}

function list_unused_required_tracker_fields ()
{
  $ret = test_h (3, 'Unused required tracker fields', 'unused-required-fields');
  $res = query_required_field_usage ();
  if (!db_numrows ($res))
    return "$ret<p>No such fields found.</p>\n";
  add_summary ('Unused required tracker fields found.');
  $columns = [
   'group_id', 'unix_group_name', 'tracker',
   'bug_field_id', 'field_name', 'label'
  ];
  $ids = [];
  while ($row = db_fetch_array ($res))
    $ids[$row['tracker']][] = $row['bug_field_id'];
  db_data_seek ($res);
  $ret .= explain_unused_required_tracker_fields ($ids);
  return $ret . print_db_values ($res, $columns);
}

function query_duplicate_usage ()
{
  $subqueries = [];
  foreach (utils_get_tracker_list () as $tracker)
    $subqueries[] =  "
      (
        SELECT
          '$tracker' AS `tracker`, `unix_group_name`, `u`.`group_id`,
          `u`.`bug_field_id`, `field_name`, `label`, COUNT(*) AS `count`
        FROM
          `{$tracker}_field_usage` `u` JOIN `groups` `g`
            ON `u`.`group_id` = `g`.`group_id`
          JOIN `{$tracker}_field` `f` ON `f`.`bug_field_id` = `u`.`bug_field_id`
        GROUP BY
          `tracker`, `unix_group_name`, `u`.`group_id`, `u`.`bug_field_id`,
          `field_name`, `label`
      )";
  return db_execute ("
    SELECT * FROM (" . join (' UNION ', $subqueries) . ") `data`
    WHERE `count` > 1
    ORDER BY `unix_group_name`, `tracker`, `bug_field_id`"
  );
}

function explain_duplicate_field_usage ($ids)
{
  $q = [];
  foreach ($ids as $tracker => $vals)
    foreach ($vals as $group_id => $field_ids)
      $q[] = "
        SELECT * FROM `{$tracker}_field_usage`
        WHERE
          `bug_field_id` IN (" . join (', ', $field_ids) . ")
          AND `group_id` = $group_id
        ORDER BY `bug_field_id`
      ";
  $queries = html_join_enclosed ($q, ['li', 'code']);
  $ret = "<p>Generally, every group should have no more than one usage record "
    . "for every field.  To look into the issue, you can use queries like,</p>"
    . "\n<ul>$queries</ul>\n";
  return "$ret<p>Editing field usage should also fix this issue "
    . "for that field and group.</p>\n";
}

function list_duplicate_field_usage ()
{
  $ret = test_h (3, 'Duplicate field usage', 'duplicate-field-usage');
  $columns = [
    'group_id', 'unix_group_name', 'tracker', 'bug_field_id',
    'field_name', 'label', 'count'
  ];
  $res = query_duplicate_usage ();
  if (!db_numrows ($res))
    return "$ret<p>No duplicate usage found.</p>\n";
  add_summary ('Duplicate usage records found.');
  $ids = [];
  while ($row = db_fetch_array ($res))
    $ids[$row['tracker']][$row['group_id']][] = $row['bug_field_id'];
  db_data_seek ($res);
  $ret .= print_db_values ($res, $columns);
  return $ret . explain_duplicate_field_usage ($ids);
}

function test_db_values ()
{
  $ret = test_h (2, 'Database value consistency', 'db-val');
  $ret .= list_unused_required_tracker_fields ();
  return $ret . list_duplicate_field_usage ();
}

function array_add_suff ($arr, $suff)
{
  if (!is_array ($suff))
    $suff = [$suff];
  $ret = [];
  foreach ($suff as $s)
    $ret = array_merge ($ret,
      array_map (function ($x) use ($s) { return "{$x}_$s"; }, $arr)
    );
  return $ret;
}

function test_utf8_search_get_test_set ()
{
  $trackers = ['cookbook', 'patch'];
  $descr = array_add_suff ($trackers, ['field', 'field_value', 'report']);
  array_push ($descr,
    'group_type', 'trackers_notification_event', 'trackers_notification_role'
  );
  $named = ['people_job_category', 'people_job_status', 'people_skill'];
  return [
    'name' => $named, 'description' => $descr, 'originator_phone' => $trackers,
    'title' => array_add_suff ($trackers, 'canned_responses')
  ];
}

function try_utf8_search ()
{
  $saved = utils_disable_warnings (E_ALL);
  db_query_prevent_die (true);
  $ret = [];
  foreach (test_utf8_search_get_test_set () as $col => $tables)
    foreach ($tables as $tbl)
      {
        $res = db_query (
          "SELECT NULL FROM `$tbl` WHERE `$col` LIKE '%😅' LIMIT 1"
        );
        if ($res !== false)
          continue;
        $ret[] = "<strong>Search in $tbl.$col failed:</strong> " . db_error ();
      }
  db_query_prevent_die (false);
  utils_restore_warnings ($saved);
  if (empty ($ret))
    return 'OK';
  return join ("<br />\n", $ret);
}

function test_initial_utf8_search ($saved_charset)
{
  $defs = ['Initial character set' => $saved_charset];
  $test_result = try_utf8_search ();
  $defs['UTF-8 search'] = $test_result;
  if ($test_result !== 'OK' && php_sapi_name () !== 'cli-server')
    add_summary ('Search with the initial charset failed.');
  return $defs;
}

function test_utf8_search ()
{
  $sets_to_try = ['utf8', 'utf8mb4', 'utf8mb3'];
  $ret = test_h (3, 'Unicode search', 'utf8-search');
  $saved_charset = db_charset_name ();
  $defs = test_initial_utf8_search ($saved_charset);
  if ($defs['UTF-8 search'] === 'OK')
    return $ret . html_dl ($defs);
  $no_good_charset = true;
  foreach ($sets_to_try as $charset)
    {
      if ($charset === $saved_charset)
        continue;
      db_reconnect ($charset);
      $test_result = try_utf8_search ();
      $defs["UTF-8 search ($charset charset)"] = $test_result;
      if ($test_result === 'OK')
        $no_good_charset = false;
    }
  db_reconnect ($saved_charset);
  if ($no_good_charset && php_sapi_name () === 'cli-server')
    add_summary ('Search with all tested charsets failed.');
  return $ret . html_dl ($defs);
}

function test_db_features ()
{
  $ret = test_h (2, 'Database features', 'db-features');
  return $ret . test_utf8_search ();
}

function try_db_connect ()
{
  $db_err = db_connect ();
  if ($db_err === null)
    return [false, ''];
  return [true, $db_err];
}

function mysql_params_to_test ()
{
  $wrong_modes = []; # No unsupported modes these days.
  $comment = null;
  if (!empty ($wrong_modes))
    $comment = "<em>This should</em> <strong>not</strong> <em>include</em> "
      . "<code>" . join (',', $wrong_modes) . "</code><em>.</em>";
  return [
    '@@GLOBAL.version' => [null],
    '@@GLOBAL.sql_mode' => [null, $wrong_modes],
    '@@SESSION.sql_mode' => [$comment, $wrong_modes]
  ];
}

function test_mysql_params ()
{
  $mysql_params = mysql_params_to_test ();
  $defs = [];
  foreach ($mysql_params as $param => $comment)
    {
      $result = db_execute ("SELECT $param");
      $value = db_result ($result, 0, $param);
      if (isset ($comment[1]))
        foreach ($comment[1] as $flag)
          {
            $value = str_replace ($flag, "<strong>$flag</strong>", $value);
            if (strstr ($value, '<strong>') !== false)
              add_summary ("$flag is set in $param.");
          }
      $value = "'$value'";
      if ($comment[0] !== null)
        $value .= "<br />\n{$comment[0]}";
      $defs[$param] = $value;
    }
  return $defs;
}

function test_mysql ()
{
  global $sys_debug_footer, $saved_sys_debug_footer;
  if (empty ($sys_debug_footer))
    $sys_debug_footer = false;
  $saved_sys_debug_footer = $sys_debug_footer;
  $sys_debug_footer = true;
  $ret = test_h (2, "Database configuration", 'db-conf');
  list ($res, $str) = try_db_connect ();
  if ($res)
    {
      add_summary ('Cannot connect database.');
      return "$ret$str";
    }
  $ret .= html_dl (test_mysql_params ());
  $ret .= test_db_features ();
  $ret .= test_db_structure ();
  return $ret . test_db_values ();
}

function list_unset_val ($must_be_unset, $value)
{
  if (!$must_be_unset)
    {
      if ($value === '<>')
        return '<strong>unset</strong>';
      return $value;
    }
  if ($value === '<>')
    return 'unset';
  return "$value\n<br /><strong>This variable should not be set "
    . "at production servers.</strong>";
}

function process_sysvar_flags ($tag, $flags)
{
  $ret = [];
  foreach ($flags as $f => $key)
    $ret[$key] = strstr ($tag, $f) !== false;
  $tag = str_replace (array_keys ($flags), '', $tag);
  return [$tag, $ret];
}

function list_sysvar ($tag, &$defs)
{
  list ($tag, $flags) = process_sysvar_flags (
    $tag, ['!' => 'must_be_unset', '*' => 'must_be_hidden']
  );
  $var = "sys_$tag";
  $value = '<>';
  if (isset ($GLOBALS[$var]))
    $value = utils_specialchars (print_r ($GLOBALS[$var], true));
  if ($flags['must_be_hidden'])
    $value = "**************";
  $defs[$var] = list_unset_val ($flags['must_be_unset'], $value);
}

function output_sysvars ()
{
  $variables = [
    'dbcharset',
    'dbhost', 'dbname', '*dbpasswd', 'dbport', 'dbsocket', 'dbuser',
    'default_domain', 'brother_domain', 'file_domain', 'https_host',
    'gpg_name', 'gpg_home', 'graphviz',
    'etc_dir', 'incdir', 'upload_dir', 'url_topdir',
    'www_topdir', 'linguas', 'localedir',
    'mail_admin', 'mail_domain', 'mail_replyto', 'name',
    'min_gidNumber', 'min_uidNumber', 'group_file', 'passwd_file',
    'passwd_common_gid', 'passwd_home_dir', 'passwd_user_shell',
    'use_php_crypt', 'pw_prefix', 'pw_rounds', 'reply_to',
    'themedefault', 'unix_group_name', 'upload_max', 'use_strftime',
    'watch_anon_posts', 'new_user_watch_days',
    'mailman_wrapper', 'savane_cgit', 'group_file', 'max_items_per_page',
    'registration_text_spam_test', 'registration_captcha', 'ignore_deployed',
    'log_exits',
    'error_cc_limit', 'error_cc_period', '!debug_footer'
  ];
  $defs = [];
  foreach ($variables as $tag)
    list_sysvar ($tag, $defs);
  return html_dl ($defs);
}

function test_sysvars ()
{
  global $sys_file_domain, $sys_default_domain, $inside_siteadmin;
  if (empty ($inside_siteadmin))
    utils_set_csp_headers ();
  $page = output_sysvars ();
  if ($sys_file_domain === $sys_default_domain)
    {
      $page .=
        "<p><strong>Note: sys_file_domain and sys_default_domain coincide.\n"
        . "This setup is vulnerable to cross-site scripting.</strong></p>\n";
      add_summary ('sys_file_domain and sys_default_domain coincide.');
    }
  return "$page<p>Savane generally uses safe default values when variables\n"
    . "are not set in the configuration file.</p>\n";
}

function get_table_structure ($table)
{
  $ret = [];
  $res = db_execute ("DESCRIBE `$table`");
  while ($row = db_fetch_array ($res))
    $ret[$row['Field']] = $row;
  return $ret;
}

function db_field_type_expected ($type, $table, $field)
{
  add_summary ("Field $field is undefined in $table has a wrong type.");
  return "<br />\n<strong>$type is expected.</strong><br />\n"
    . " Run <code>ALTER TABLE `$table` MODIFY `$field` $type;</code>\n"
    . "to upgrade the column.";
}

function db_field_undefined ($type, $table, $field)
{
  add_summary ("Field $field is undefined in $table.");
  return "<strong>Undefined</strong><br />\n"
   . " Run <code>ALTER TABLE `$table` ADD `$field` $type;</code>\n"
    . "to create the column.";
}

function check_mediumtext ($table_struct, $table, $field)
{
  if (!array_key_exists ($field, $table_struct))
    return db_field_undefined ('mediumtext', $table, $field);
  $type = strtolower ($table_struct[$field]['Type']);
  if (in_array ($type, ['mediumtext', 'bigtext']))
    return $type;
  return $type . db_field_type_expected ('mediumtext', $table, $field);
}

function check_varchar ($table_struct, $table, $field, $n = 153)
{
  $right_type = "varchar($n)";
  if (!array_key_exists ($field, $table_struct))
    return db_field_undefined ($right_type, $table, $field);
  $type = strtolower ($table_struct[$field]['Type']);
  $wrong_type = false;
  if (!preg_match ('/varchar[(]([0-9]+)[)]/', $type, $matches))
    $wrong_type = true;
  if ($n > $matches[1])
    $wrong_type = true;
  if ($wrong_type)
    $type .= db_field_type_expected ($right_type, $table, $field);
  return $type;
}

function ini_size_note ($former, $latter)
{
  static $have_reported;
  if (empty ($have_reported))
    {
      add_summary ('Sizes are inconsistent.');
      $have_reported = 1;
    }
  return "<br /><strong>Note: $former is greater than $latter. "
    . 'The effective limit will be the latter.</strong>';
}

function test_upload_ini_vals (&$defs)
{
  $mem_limit = utils_ini_size_bytes (ini_get ('memory_limit'));
  $defs['PHP memory_limit setting'] = utils_human_readable_size ($mem_limit);
  $post_max = utils_ini_size_bytes (ini_get ('post_max_size'));
  $defs['PHP post_max_size setting'] = utils_human_readable_size ($post_max);
  if ($mem_limit < $post_max && $mem_limit >= 0)
    $defs['PHP memory_limit setting'] .=
      ini_size_note ('post_max_size', 'memory_limit');
  $umfs = utils_ini_size_bytes (ini_get ('upload_max_filesize'));
  $defs['PHP upload_max_filesize setting'] = utils_human_readable_size ($umfs);
  if ($post_max < $umfs)
    $defs['PHP upload_max_filesize setting'] .=
      ini_size_note ('upload_max_filesize', 'post_max_size');
  return $umfs;
}

function test_uploads (&$defs)
{
  $defs['sys_upload_dir writability'] =
    ['sys-upload-dir', test_sys_upload_dir ()];
  $ini_val = test_upload_ini_vals ($defs);
  list_sysvar ('upload_max', $arr);
  $val = $arr['sys_upload_max'];
  if (strstr ($val, '>') === false)
    {
      $arr['sys_upload_max'] *= 1024;
      $val = utils_human_readable_size ($arr['sys_upload_max']);
    }
  $defs['sys_upload_max'] = ['sys-upload-max', $val];
  if (!array_key_exists ('sys_upload_max', $GLOBALS))
    return '';
  if ($arr['sys_upload_max'] <= $ini_val)
    return '';
  $defs['sys_upload_max'][1] .=
    ini_size_note ('$sys_upload_max', 'upload_max_filesize');
}

function run_other_tests ()
{
  $defs = [];
  $ret = test_h (2, "Other tests", 'other-tests');
  test_repos ($defs);
  test_uploads ($defs);
  $ret .= html_dl ($defs);
  $ret .= test_h (3, 'Limiting email error report rate', 'error-cc-limit');
  $ret .= error_test_cc_limit ();
  $ret .= test_h (3, 'Timestamps', 'error-timestamp');
  $ret .= '<p>' . error_test_timestamp () . "</p>\n";
  return $ret;
}

function test_sysconfigs ()
{
  include $GLOBALS['sys_conf_file'];
  $ret = test_sysvars ();
  $ret .= "<p><img src='/file?file_id=test.png' alt='Test image'/></p>\n";
  $ret .= test_strftime ();
  $ret .= test_captcha ();
  $ret .= test_mailman ();
  $ret .= test_hash ();
  $ret .= test_mysql ();
  $ret .= test_gpg ();
  $ret .= test_i18n ();
  $ret .= run_other_tests ();
  return $ret;
}

function savane_conf_file ()
{
  global $sys_conf_file;
  $page = test_h (2, "Savane configuration", 'savane-config') . '<p>';
  $err = null;
  if (empty ($sys_conf_file))
    $err = $str = "<strong>sys_conf_file not set.</strong>\n";
  else
    {
      $str = "File <strong>$sys_conf_file</strong> ";
      if (is_readable ($sys_conf_file))
        $str .= "exists and is readable.";
      else
        {
          $str .= "does not exist or is not readable!";
          $err = $str;
        }
      $str = "sys_conf_file is set to $sys_conf_file<br />\n$str";
    }
  add_summary ($err);
  return "$page$str</p>\n";
}

function savane_settings ()
{
  global $sys_conf_file;
  $page = test_h (2, "Configured settings", 'settings');

  if (!is_readable ($sys_conf_file))
    return "$page\nSince $sys_conf_file does not exist or is not readable, "
      . "this part cannot be checked.";
  require_once ("include/i18n.php");
  return $page . test_sysconfigs ();
}

$page = page_start ($inside_siteadmin);
$page .= check_source_code ();
$page .= basic_php_config ();
$page .= optional_php_config ();
$page .= php_extensions ();
$page .= php_functions ();
$page .= apache_envv ();
$page .= savane_conf_file ();
$page .= savane_settings ();
$page .= get_test_summaries ();
$page .= '<pre>' . utils_specialchars (utils_debug_footer ())
  . "</pre>\n</body>\n</html>\n";
print $page;
$sys_debug_footer = $saved_sys_debug_footer;
utils_output_debug_footer ();
?>
