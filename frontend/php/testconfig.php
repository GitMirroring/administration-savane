<?php
# Check your configuration against recommended values.
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
$sys_linguas = "en:es";
foreach (['i18n', 'database', 'mailman', 'savane-git'] as $inc)
  require_once ("include/$inc.php");

function return_bytes ($v)
{
  $val = trim ($v);
  if (is_int ($val))
    return $val;
  $last = strtolower (substr ($val, -1));
  $val = substr ($val, 0, -1);
  if (!preg_match ('/^\d*$/', $val) || !in_array ($last, ['g', 'm', 'k']))
    return ">$v<";
  switch ($last)
    {
      # Fall through all cases.
      case 'g':
        $val *= 1024;
      case 'm':
        $val *= 1024;
      case 'k':
        $val *= 1024;
    }
  return (int) $val;
}

# Test if GPG executable is configured and can run, basically.
# Return zero on success.
function check_gpg_executable ()
{
  global $sys_gpg_name;
  print html_h (2, "GnuPG");
  if (!isset ($sys_gpg_name))
    {
      print "<p><strong>GnuPG is not configured.</strong></p>\n";
      return true;
    }

  print "<dl><dt>GPG command</dt>\n<dd><code>$sys_gpg_name</code></dd>\n";
  $gpg_result = utils_run_proc  (
    gpg\gpg_name () . " --version", $gpg_output, $gpg_stderr
  );
  print "<dt><code>--version</code> output</dt>\n";
  print "<dd><pre>\n" . utils_specialchars ($gpg_output) . "</pre></dd>\n";
  print "<dt>Exit code</dt><dd><code>$gpg_result</code></dd>\n";
  print "<dt><code>stderr</code> output</dt>\n";
  print "<dd><pre>\n" . utils_specialchars ($gpg_stderr) . "</pre></dd>\n";
  print "</dl>\n";
  return $gpg_result;
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
     print "Can't read $dir/$p";
     return null;
   }
  return join ("\n", $keys);
}

function run_gpg ($command, $msg, $input)
{
  $cmd = gpg\gpg_name () . " $command";
  $res = utils_run_proc ($cmd, $out, $err, ['in' => $input]);
  if (!$res)
    return [$out, $res];
  print "Can't $msg, exit code $res<br />\n";
  print "<pre>$out</pre>\n";
  print "<pre>$err</pre>\n";
  return [$out, $res];
}

function gen_signature ($key_id, $home, $option, $input)
{
  list ($out, $res) = run_gpg ("--batch --home '$home' -u '$key_id' $option",
    'generate test signature', $input);
  return [[$out], $res];
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

function run_gpg_sign ($key_id, $home, $algo)
{
  $input = gpg\test_message ();
  foreach (['-a --sign', '--clearsign', '--detach-sign'] as $option)
    {
      print "<dt>$algo, $option<dt>\n<dd>";
      list ($signature, $error) =
        gen_signature ($key_id, $home, $option, $input);
      if ($error)
        {
          print "</dd>\n";
          continue;
        }
      if ($option == '--detach-sign')
        $signature[] = $input;
      print run_gpg_verify ($home, $signature, $input) . "</dd>\n";
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

function run_gpg_encrypt ($key_id, $home, $algo)
{
  $input = gpg\test_message ();
  print "<dt>$algo</dt>\n<dd>";
  list ($enc, $res) = encrypt_test ($key_id, $home, $input);
  if (!$res)
    list ($dec, $res) = decrypt_test ($key_id, $home, $enc);
  if (!$res)
    {
      if ($dec === $input)
        print 'OK';
      else
        print "Decrypted sample doesn't match the clear text";
    }
  print "</dd>\n";
}

function test_gpg_func ($algo, $capability, $func)
{
  $key = read_test_key (strtolower ($algo));
  if (empty ($key))
    return;
  list ($key_id, $home, $error) =
    gpg\get_key ($key, $capability);
  if ($error)
    {
      print "<dt>$algo<dt>\n<dd>";
      print gpg\error_str ($error) . "</dd>\n";
      return;
    }
  $func ($key_id, $home, $algo);
  utils_rm_fr ($home);
}

function test_gpg_verify ($algo)
{
  test_gpg_func ($algo, GNUPG_SIGN_CAPABILITY, 'run_gpg_sign');
}

function test_gpg_encrypt ($algo)
{
  test_gpg_func ($algo, GNUPG_ENCRYPT_CAPABILITY, 'run_gpg_encrypt');
}

function test_gpg ()
{
  if (check_gpg_executable ())
    return;
  print html_h (3, "Verify signature") . "<dl>\n";
  foreach (test_gpg_algo_list () as $algo)
    test_gpg_verify ($algo);
  print "</dl>\n" . html_h (3, 'Encrypt');
  foreach (test_gpg_algo_list () as $algo)
    test_gpg_encrypt ($algo);
}

function test_cgitrepos ()
{
  if (!isset ($GLOBALS['sys_etc_dir']))
    {
      print '<strong>no $sys_etc_dir set</strong>';
      return;
    }
  if (!file_exists ($GLOBALS['sys_etc_dir']))
    {
      print '<strong>no $sys_etc_dir directory exists</strong>';
      return;
    }
  $fname = $GLOBALS['sys_etc_dir'] . '/cgitrepos';
  if (!file_exists ($fname))
    {
      print '<strong>no cgitrepos file exists in $sys_etc_dir</strong>';
      return;
    }
  if (!is_readable ($fname))
    {
      print '<strong>cgitrepos in $sys_etc_dir is not readable</strong>';
      return;
    }
  $mtime = time () - filemtime ($fname);
  if ($mtime > 3600)
    {
      print ('<strong>cgitrepos has not been updated for ');
      if ($mtime < 100)
        printf ('%.0f minutes</strong>', $mtime / 60);
      else if ($mtime < 24 * 3600)
        printf ('%.0f hours</strong>',  $mtime / 3600);
      else
        printf ('%.1f days</strong>',  $mtime / 24. / 3600);
      return;
    }
  print 'OK';
}

function sys_vcs_dir_not_set ()
{
  global $sys_vcs_dir;
  if (empty ($sys_vcs_dir))
    {
      print '<em>$sys_vcs_dir not set</em>';
      return true;
    }
  if (empty ($sys_vcs_dir['git']))
    {
      print '<em>$sys_vcs_dir["git"] not set</em>';
      return true;
    }
  if (empty ($sys_vcs_dir['git']['dir']))
    {
      print '<em>$sys_vcs_dir["git"]["dir"] not set</em>';
      return true;
    }
  return false;
}

function test_git_dirs ()
{
  global $sys_vcs_dir;
  if (sys_vcs_dir_not_set ())
    return;
  $git = $sys_vcs_dir["git"];
  print "'dir': " . $git["dir"] . ' ';
  if (!is_dir ($git["dir"]))
    {
      print " <strong>no such directory</strong>";
      return;
    }
  print " (directory exists)";
  $suf = '[\'clone-path\']';
  if (!empty ($git["clone-path"]))
    print "<br />\n'clone-path': " . $git["clone-path"];
}

function test_repos ()
{
  print "<dt id='cgitrepos'>cgitrepos</dt>\n<dd>";
  test_cgitrepos ();
  print "</dd>";
  print "<dt id='gitrepos'>git directories</dt>\n<dd>";
  test_git_dirs ();
  print "</dd>\n";
}
function test_sys_upload_dir ()
{
  $path = utils_make_upload_file ("test.txt", $errors);
  if ($path === null)
    {
      print "<b>can't make file:</b> $errors";
      return;
    }
  $error_handler = function ($errno, $errstr)
  {
    print "<b>unlink failed:</b> $errstr";
    return true;
  };
  $old_handler = set_error_handler ($error_handler, E_WARNING);
  $res = unlink ($path);
  set_error_handler ($old_handler, E_WARNING);
  if ($res)
    print 'OK';
}

function test_captcha ()
{
  global $sys_captchadir;
  $default_dir = '/usr/share/php';

  print html_h (2, "Captcha");
  if (empty ($sys_captchadir))
    {
      print "<p><strong>sys_captchadir isn't set.</strong></p>\n";
      print "<p>Falling back to default, $default_dir</p>\n";
      $sys_captchadir = $default_dir;
    }
  else
    print "<p><b>sys_captchadir</b> is set to $sys_captchadir</p>\n";
  if (!is_dir ($sys_captchadir))
    {
      print "<p><strong>No $sys_captchadir directory found.</strong></p>\n";
      return;
    }
  $f = "$sys_captchadir/Text/CAPTCHA.php";
  if (!is_file ($f))
    {
      print "<p><strong>No $f file found.</strong></p>\n";
      return;
    }
  print "<p>Sample image:</p>\n"
    . "<p><img id='captcha' src='/captcha.php' alt='CAPTCHA' /></p>\n";
}

function test_mailman_failed ($ver)
{
  $fail = false;
  $dte = "<dt><strong>Error</strong></td><dd>";
  if (empty ($ver))
    {
      print "$dte<strong>No response</strong></dd>\n";
      return true;
    }
  if (array_key_exists ('pipe::error', $ver))
    {
      print "$dte<strong>Pipe error:</strong> "
        . "<pre>{$ver['pipe::error']}</pre></dd>\n";
      $fail = true;
    }
  if (array_key_exists ('error', $ver))
    {
      print "$dte<pre>{$ver['error']}</pre></dd>\n";
      $fail = true;
    }
  return $fail;
}

function output_mailman_version ($ver)
{
  if (test_mailman_failed ($ver))
    return false;
  if (empty ($ver['version']))
    return false;
  print "<dt>Version</dt><dd>{$ver['version']}</dd>\n";
  print "<dt>Generated password</dt><dd>{$ver['password']}</dd>\n";
  print "<dt>Timestamp</dt><dd>{$ver['timestamp']}</dd>\n";
  return true;
}
function output_mailman_query ($q)
{
  if (test_mailman_failed ($q))
    return false;
  print "<dt>Query results</dt>\n<dd><dl>\n";
  foreach ($q as $k => $v)
    print "<dt>" . utils_specialchars ($k) . "</dt>\n"
      . "<dd>" . utils_specialchars ($v) . "</dd>\n";
  print "</dl>\n</dd>\n";
  return true;
}

function test_mailman ()
{
  print html_h (2, "Mailman connection");
  print "<dl>\n";
  $ver = mailman_get_version ();
  $have_version = output_mailman_version ($ver);
  if ($have_version)
    output_mailman_query (mailman_query_list ('savannah-users'));
  else
    printf ("<dt>Run time</dt><dd>%s ms</dd></dl>\n", $ver['timestamp']);
  print "</dl>\n";
  if ($have_version && preg_match ("/^stub /", $ver['version']))
    print "<p><strong>This is a stub; write the real command "
      . "in \$sys_mailman_wrapper.</strong></p>\n";
}

$page = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n"
      . "  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n";
$page .=
  "<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en_US' lang='en_US'>\n"
  . "<head>\n"
  . "<meta http-equiv='content-type' content='text/html; charset=utf-8' />\n"
  . "<title>Basic configuration tests</title>\n"
  . "<link rel=\"stylesheet\" type=\"text/css\" "
  . "href=\"/css/internal/testconfig.css\" />\n"
  . "</head>\n\n"
  . "<body>\n";

$page .= html_h (1, "Basic pre-tests for Savane installation");
if (empty ($inside_siteadmin))
  $page .= "<p>This page should help you to check whether your installation\n"
    . "is properly configured. It shouldn't display any sensitive\n"
    . "information, since it could give details about your setup\n"
    . "to anybody.</p>\n";

$page .= html_h (2, "Savane source code");
function check_source_code ()
{
  $commit = git_get_commit ();
  $cgit_url = git_get_savane_url ($commit);
  $tarball_name = git_get_tarball_name ();
  $tarball_url = git_get_tarball_url ();
  $ret = "<dl>\n";
  $ret .= "<dt>Configured Git commit</dt>\n";
  $ret .= "<dd>{$GLOBALS['ac_git_commit']}</dd>\n";
  $ret .= "<dt>Computed Git commit</dt>\n";
  $ret .= "<dd><a href='$cgit_url'>$commit</a></dd>\n";
  $ret .= "<dt>Tarball</dt>\n";
  $ret .= "<dd><a href='$tarball_url'>$tarball_name</a></dd>\n";
  $ret .= "<dt>Availability</dt>\n<dd>";
  if (git_check_tarball ())
    $ret .= "<strong>Fail.  You must make sure you offer the source code "
      . "correctly before making the website available to other "
      . "users.</strong>";
  else
    $ret .= "OK";
  return "$ret</dd>\n</dl>\n";
}
$page .= check_source_code ();
$page .= html_h (2, "Basic PHP configuration");
$page .= "<p>PHP version: " . phpversion () . "</p>\n";

# cf. http://php.net/manual/en/ini.php
$phptags = ['file_uploads' => '1', 'allow_url_fopen' => '1'];

# Get all php.ini values.
$all_inis = ini_get_all ();
# Define missing constant to interpret the 'access' field.
define ('PHP_INI_SYSTEM', 4);
# Cf. http://www.php.net/manual/en/ini.core.php

$page .= "\n<table border=\"1\" summary=\"PHP configuration\">\n";
$page .= "<tr><th>PHP Tag name</th><th>Local value</th>"
  . "<th>Suggested value</th></tr>\n";
$have_unset = false;
ksort ($phptags);
function compare_ini_vals ($tag, $good, $cmp)
{
  global $all_inis, $page;
  $gv = utils_specialchars ($good);
  if (!array_key_exists ($tag, $all_inis))
    {
      $page .= sprintf (
        "<tr><td>%s</td><td class=\"unset\">Unknown*</td><td>%s</td></tr>\n",
        $tag, $gv
      );
      return true;
    }
  $ini_val = ini_get ($tag);
  $t = utils_specialchars ($ini_val);
  if ($cmp ($ini_val, $good))
    {
      $page .=
        sprintf ("<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n", $tag, $t, $gv);
      return false;
    }
  $page .= sprintf (
    "<tr><td>%s</td><td class=\"different\">%s</td><td>%s", $tag, $t, $gv
  );
  if ($all_inis[$tag]['access'] > PHP_INI_SYSTEM)
    $page .= " (can be set in php.ini, .htaccess or httpd.conf)";
  else
    $page .= " (can be set in php.ini or httpd.conf, but not in .htaccess)";
  $page .= "</td></tr>\n";
  return false;
}

$cmp = function ($a, $b) { return $a === $b; };
foreach ($phptags as $tag => $good)
  if (compare_ini_vals ($tag, $good, $cmp))
    $have_unset = true;
# Check against minimum sizes.
$phptags = ['post_max_size' => '3M', 'upload_max_filesize' => '2M'];
$cmp = function ($a, $b) { return return_bytes ($a) >= return_bytes ($b); };
foreach ($phptags as $tag => $good)
  if (compare_ini_vals ($tag, $good, $cmp))
    $have_unset = true;
$page .= "</table>\n\n";
if ($have_unset)
  $page .= "<blockquote><p>* This tag was not found at all. "
    . "It is probably irrelevant to your PHP version so you may ignore this "
    . "entry.</p></blockquote>\n";

$page .= html_h (2, "Optional PHP configuration");

$page .= "<p>The following is not required to run Savane, but could enhance\n"
  . "security of your production server. Displaying errors is recommended:\n"
  . "they may annoy the user with warnings, but allow you to spot "
  . "and report\npotentially harmful bugs.</p>\n";

$phptags = [
  'disable_functions' => 'passthru,popen,shell_exec,system',
  'display_errors' => '1', 'error_reporting' => (string)(E_ALL),
  'log_errors' => '1',
];

$page .= "\n<table border=\"1\">\n"
. "<tr><th>PHP Tag name</th><th>Local value</th>"
. "<th>Suggested value</th></tr>\n";
$have_unset = false;
$cmp = function ($a, $b) { return $a === $b; };
foreach ($phptags as $tag => $good)
  if (compare_ini_vals ($tag, $good, $cmp))
    $have_unset = true;
$page .= "</table>\n\n";
if ($have_unset)
  $page .= "<blockquote><p>* This tag was not found at all. "
    . "It is probably irrelevant to your PHP version "
    . "so you may ignore this entry.</p></blockquote>\n\n";

function list_facilities ($func, $items, $labels = [])
{
  $lab = [true => 'Exists.', false => 'Not found.'];
  foreach ($labels as $k => $v)
    $lab[$k] = $v;
  $ret = "<p><dl>\n";
  foreach ($items as $name => $comment)
    {
      $ret .= "<dt><b>$name</b></dt><dd>";
      if ($func ($name))
        $ret .= $lab[true];
      else
        $ret .= sprintf ("<b>%s</b> <i>%s</i>", $lab[false], $comment);
      $ret .= "</dd>\n";
    }
  return "$ret</dl></p>\n";
}

$page .= html_h (2, "PHP extensions");
$php_extensions =
  [
    'mysqli' => 'Database access (php-mysqli) ! [REQUIRED]',
    'mailparse' =>
      'Used to parse email messages (php-mailparse) ! [RECOMMENDED]',
  ];
$page .=
  list_facilities ('extension_loaded', $php_extensions, [true => 'Loaded.']);

$page .= html_h (2, "PHP functions");
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
    'sem_get' => 'Used in mailman interface (--enable-sysvsem) ! [REQUIRED]'
 ];
$page .= list_facilities ('function_exists', $phpfunctions);

function test_i18n ()
{
  i18n_setup ("es_ES.UTF-8");
  $str = 'Any';
  $res = gettext ($str);
  if ($res == $str)
    {
      print "<strong>Fail.</strong> Check <code>locale -a</code> output,\n";
      print "be sure to install language-pack-* in Trisquel";
    }
  else
    print "$str => $res";
  i18n_setup ("en_US.UTF-8");
}

$page .= html_h (2, "Apache environment variables");
$vv = [];
foreach (['SAVANE_CONF', 'SV_LOCAL_INC_PREFIX'] as $var)
  if (getenv ($var))
    {
      $conf_var = getenv ($var);
      $vv[] = "<code>$var</code> configured to <code>$conf_var</code>";
    }
$page .= "<p>" . join ("<br />\n", $vv) . "</p>\n\n";
$page .= html_h (2, "Savane configuration") . '<p>';

if (empty ($sys_conf_file))
  $page .= "<strong>sys_conf_file not set!</strong>\n";
else
  {
    $page .= "sys_conf_file is set to $sys_conf_file<br />\n";
    $page .= "File <strong>$sys_conf_file</strong> ";

    if (is_readable ($sys_conf_file))
      $page .= "exists and is readable.";
    else
      $page .= "does not exist or is not readable!";
  }
$page .= "</p>\n";

function test_mysql ()
{
  print html_h (2, "Database configuration");
  $db_err = db_connect ();
  if ($db_err !== null)
    {
      print $db_err;
      return;
    }
  # When sql_mode contains 'ONLY_FULL_GROUP_BY', queries like
  # "SELECT groups.group_name, groups.group_id, groups.unix_group_name,"
  # ...
  # . "GROUP BY groups.unix_group_name "
  # . "ORDER BY groups.unix_group_name"
  # used e.g. in my/groups.php result in an error.
  #
  # Since MySQL 5.7, this is default. We could use ANY_VALUE ()
  # to workaround this, but it is only introduced in 5.7,
  # so won't work with older MySQLs.
  $mysql_params = [
    '@@GLOBAL.version' => null,
    '@@GLOBAL.sql_mode' => null,
    '@@SESSION.sql_mode' =>
      "<em>This should</em> <strong>not</strong> <em>include</em> "
        . "<code>ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES</code><em>.</em>"
  ];
  $mysql_highlight = [
    '@@GLOBAL.sql_mode' => 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES',
    '@@SESSION.sql_mode' => 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES'
  ];
  print "<dl>\n";
  foreach ($mysql_params as $param => $comment)
    {
      $result = db_query ("SELECT $param");
      $value = db_result ($result, 0, $param);
      if (isset ($mysql_highlight[$param]))
        {
          $vals = explode (",", $mysql_highlight[$param]);
          foreach ($vals as $i => $v)
            $value = str_replace ($v, "<strong>$v</strong>", $value);
        }
      print "<dt>$param</dt><dd>'$value'";
      if ($comment !== null)
        print "<br />\n$comment";
      print "</dd>\n";
    }
  print "</dl>\n";
}

function list_sysvar ($tag)
{
  $var = "sys_$tag";
  $value = '<strong>unset</strong>';
  if (isset ($GLOBALS[$var]))
    $value = utils_specialchars (print_r ($GLOBALS[$var], true));
  if ($var == "sys_dbpasswd")
    $value = "**************";
  printf ("<dt>%s</dt>\n<dd>%s</dd>\n", $var, $value);
}

function output_sysvars ()
{
  $variables = [
    'dbhost', 'dbname', 'dbpasswd', 'dbport', 'dbsocket', 'dbuser',
    'default_domain', 'etc_dir', 'file_domain', 'graphviz', 'https_host',
    'incdir', 'upload_dir', 'url_topdir', 'www_topdir',
    'linguas', 'localedir',
    'mail_admin', 'mail_domain', 'mail_replyto', 'name', 'reply_to',
    'themedefault', 'unix_group_name', 'upload_max',
    'watch_anon_posts', 'new_user_watch_days',
    'mailman_wrapper', 'savane_cgit'
  ];
  print "<dl>\n";
  foreach ($variables as $tag)
    list_sysvar ($tag);
  print "</dl>\n";
}

function test_sysvars ()
{
  global $page, $sys_file_domain, $sys_default_domain;
  print $page;
  $page = '';
  if (empty ($inside_siteadmin))
    utils_set_csp_headers ();
  output_sysvars ();
  if ($sys_file_domain === $sys_default_domain)
    print "<p><strong>Note: sys_file_domain and sys_default_domain coincide.\n"
      . "This setup is vulnerable to cross-site scripting.</strong></p>\n";
  print "<p>Savane generally uses safe default values when variables\n"
    . "are not set in the configuration file.</p>\n";
}

function test_sysconfigs ()
{
  include $GLOBALS['sys_conf_file'];
  test_sysvars ();
  print "<p><img src='/file?file_id=test.png' alt='Test image'/></p>\n";
  test_captcha ();
  test_mailman ();
  test_mysql ();

  test_gpg ();
  print html_h (2, "Other tests");
  print "<dl>\n";
  test_repos ();
  print "<dt id='sys-upload-dir'>sys_upload_dir writability</dt>\n<dd>";
  test_sys_upload_dir ();
  print "</dd>\n";
  print "<dt id='i18n'>i18n</dt>\n<dd>";
  test_i18n ();
  print "</dd>\n";
  print "</dl>\n";
}

$page .= html_h (2, "Configured settings");

if (is_readable ($sys_conf_file))
  test_sysconfigs ();
else
  print "Since $sys_conf_file does not exist or is not readable, "
    . "this part cannot be checked.";

print "</body>\n</html>\n";
?>
