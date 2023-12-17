<?php
# Check your configuration against recommended values.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
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

$testconfig_php = true;
include ("include/ac_config.php");
$sys_debug_sqlprofiler = false;
$sys_file_domain = '';
$sys_linguas = "en:es";
require_once ("include/i18n.php");
require_once ("include/database.php");
require_once ("include/mailman.php");
require_once ("include/savane-git.php");

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

function test_gpg ()
{
  print "\n<h3>GnuPG</h3>\n\n";

  if (!isset ($GLOBALS['sys_gpg_name']))
    {
      print "<p><strong>GnuPG is not configured.</strong></p>\n";
      return;
    }

  print "<dl><dt>GPG command</dt>\n<dd><code>" . $GLOBALS['sys_gpg_name']
        . "</code></dd>\n";

  $gpg_result = utils_run_proc  (
    "'" . $GLOBALS['sys_gpg_name'] . "' --version", $gpg_output,
     $gpg_stderr
  );
  $dd_pre = "<dd style='border: thin dashed black; border-right: none'>"
            ."<pre style='padding-left: 1em'>\n";
  print "<dt><code>--version</code> output</dt>\n";
  print $dd_pre . utils_specialchars ($gpg_output) . "</pre></dd>\n";
  print "<dt>Exit code</dt><dd><code>" . $gpg_result . "</code></dd>\n";
  print "<dt><code>stderr</code> output</dt>\n";
  print $dd_pre . utils_specialchars ($gpg_stderr) . "</pre></dd>\n";
  print "</dl>\n";
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

  print "<h3>Captcha</h3>\n\n";
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
    . "<p><img id='captcha' src='/captcha.php' alt='CAPTCHA' /></p>";
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
  print "<h3>Mailman connection</h3>";
  print "<dl>\n";
  $ver = mailman_get_version ();
  $have_version = output_mailman_version ($ver);
  if ($have_version)
    output_mailman_query (mailman_query_list ('savannah-users'));
  else
    printf ("<dt>Run time</dt><dd>%s ms</dd></dl>\n", $ver['timestamp']);
  if ($have_version && preg_match ("/^stub /", $ver['version']))
    print "<p><strong>This is a stub; write the real command "
      . "in \$sys_mailman_wrapper.</strong></p>\n";
}

print "<?xml version=\"1.0\" encoding=\"utf-8\"?"
  # Separate the previous "?" from ">" to workaround broken syntax
  # highlighting in some editors.
  . ">\n";
print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\"
    \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n\n";

print "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en_US\">\n"
. "<head>\n"
. "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" />\n"
. "<title>Basic configuration tests</title>\n"
. "<link rel=\"stylesheet\" type=\"text/css\" "
. "href=\"/css/internal/testconfig.css\" />\n"
. "</head>\n\n"
. "<body>\n";

print "<h1>Basic pre-tests for Savane installation</h1>\n\n";
if (empty($inside_siteadmin))
  print "<p>This page should help you to check whether your
installation is properly configured. It shouldn't display any sensitive
information, since it could give details about your setup to anybody.</p>\n";

print "\n<h2>Savane source code</h2>\n\n";
function check_source_code ()
{
  $commit = git_get_commit ();
  $cgit_url = git_get_savane_url ($commit);
  $tarball_name = git_get_tarball_name ();
  $tarball_url = git_get_tarball_url ();
  print "<dl>\n";
  print "<dt>Configured Git commit</dt>\n";
  print "<dd>{$GLOBALS['ac_git_commit']}</dd>\n";
  print "<dt>Computed Git commit</dt>\n";
  print "<dd><a href='$cgit_url'>$commit</a></dd>\n";
  print "<dt>Tarball</dt>\n";
  print "<dd><a href='$tarball_url'>$tarball_name</a></dd>\n";
  print "<dt>Availability</dt>\n<dd>";
  if (git_check_tarball ())
    print "<strong>Fail.  You must make sure you offer the source code "
      . "correctly before making the website available to other "
      . "users.</strong>";
  else
    print "OK";
  print "</dd>\n</dl>\n";
}

check_source_code ();

print "\n<h2>Basic PHP configuration</h2>\n\n";

print "<p>PHP version: " . phpversion () . "</p>\n";

# cf. http://php.net/manual/en/ini.php
$phptags = ['file_uploads' => '1', 'allow_url_fopen' => '1'];

# Get all php.ini values.
$all_inis = ini_get_all ();
# Define missing constant to interpret the 'access' field.
define ('PHP_INI_SYSTEM', 4);
# Cf. http://www.php.net/manual/en/ini.core.php

print "\n<table border=\"1\" summary=\"PHP configuration\">\n";
print "<tr><th>PHP Tag name</th><th>Local value</th>"
    . "<th>Suggested value</th></tr>\n";
$have_unset = false;
ksort ($phptags);
function compare_ini_vals ($tag, $good, $cmp)
{
  global $all_inis;
  $gv = utils_specialchars ($good);
  if (!array_key_exists ($tag, $all_inis))
    {
      printf (
        "<tr><td>%s</td><td class=\"unset\">Unknown*</td><td>%s</td></tr>\n",
        $tag, $gv
      );
      return true;
    }
  $ini_val = ini_get ($tag);
  $t = utils_specialchars ($ini_val);
  if ($cmp ($ini_val, $good))
    {
      printf ("<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n", $tag, $t, $gv);
      return false;
    }
  printf ("<tr><td>%s</td><td class=\"different\">%s</td><td>%s",
    $tag, $t, $gv);
  if ($all_inis[$tag]['access'] > PHP_INI_SYSTEM)
    print " (can be set in php.ini, .htaccess or httpd.conf)";
  else
    print " (can be set in php.ini or httpd.conf, but not in .htaccess)";
  print "</td></tr>\n";
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
print "</table>\n\n";
if ($have_unset)
  print "<blockquote>* This tag was not found at all. It is probably "
    . "irrelevant to your PHP version so you may ignore this "
    . "entry.</blockquote>\n";

print "\n<h2>PHP functions</h2>\n\n";

$phpfunctions =
  [
    'mysqli_connect' =>
      'You must install/configure php-mysqli ! [REQUIRED]',
    'gettext' => 'You should install/configure php with gettext support '
      . '! [RECOMMENDED]',
    'strftime' => 'When this function is dropped from PHP '
      . '(deprecated in 8.1), date output is expected to slow down '
      . '! [RECOMMENDED]',
    'ctype_digit' => 'You must have a PHP version supporting ctype '
      . '(--enable-ctype) ! [REQUIRED]',
    'sem_get' =>
      'Semaphores are used in mailman interface (--enable-sysvsem) ! [REQUIRED]'
 ];
print "<p>";
foreach ($phpfunctions as $func => $comment)
  {
    $funcs = explode ("|", $func);
    $have_func = false;
    foreach ($funcs as $i => $f)
      if (function_exists ($f))
        {
          $have_func = true;
          break;
        }
    if ($have_func)
      print "function <strong>$f</strong> exists.<br />\n";
    else
      print
        "function <strong>$func</strong> not found. <em>$comment</em><br />\n";
  }
print "</p>\n";

function test_i18n ()
{
  i18n_setup ("es_ES.UTF-8");
  $str = 'High';
  $res = gettext ($str);
  if ($res == $str)
    print "<strong>fail</strong>";
  else
    print "$str => $res";
  i18n_setup ("en_US.UTF-8");
}

print "\n<h2>Apache environment vars</h2>\n\n<p>";
if (getenv ('SAVANE_CONF'))
  {
    $conf_var = getenv ('SAVANE_CONF');
    print "SAVANE_CONF configured to $conf_var<br />\n";
  }
if (getenv('SV_LOCAL_INC_PREFIX'))
  {
    $conf_var = getenv ('SV_LOCAL_INC_PREFIX');
    print "SV_LOCAL_INC_PREFIX configured to $conf_var<br />\n";
  }
print "</p>\n\n<h2>Savane configuration:</h2>\n\n<p>";

if (empty ($sys_conf_file))
  print "<strong>sys_conf_file not set!</strong>\n";
else
  {
    print "sys_conf_file is set to $sys_conf_file<br />\n";
    print "File <strong>$sys_conf_file</strong> ";

    if (is_readable ($sys_conf_file))
      print "exists and is readable.";
    else
      print "does not exist or is not readable!";
  }
print "</p>\n";

function test_mysql ()
{
  print "\n<h3>MySQL configuration</h3>\n\n";
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

function test_sysconfigs ()
{
  global $sys_conf_file;
  include $sys_conf_file;
  $variables = [
    'dbhost', 'dbname', 'dbpasswd', 'dbuser', 'default_domain',
    'etc_dir', 'file_domain', 'https_host', 'incdir',
    'url_topdir', 'www_topdir',
    'linguas', 'localedir',
    'mail_admin', 'mail_domain', 'mail_replyto', 'name',
    'themedefault', 'unix_group_name', 'upload_max',
    'watch_anon_posts', 'new_user_watch_days',
    'mailman_wrapper', 'savane_url', 'savane_cgit'
  ];
  if (empty ($inside_siteadmin))
    utils_set_csp_headers ();

  print "<dl>\n";
  foreach ($variables as $tag)
    {
      $var = "sys_$tag";
      $value = '<strong>unset</strong>';
      if (isset ($GLOBALS[$var]))
        $value = utils_specialchars (print_r ($GLOBALS[$var], true));
      if ($var == "sys_dbpasswd")
        $value = "**************";

      printf ("<dt>%s</dt>\n<dd>%s</dd>\n", $var, $value);
    }
  if (!isset ($GLOBALS['sys_debug_on']))
    $GLOBALS['sys_debug_on'] = false;

  print "</dl>\n";
  print "<p>Savane uses safe default values when variables are not set "
    . "in the configuration file.</p>\n";
  print "<p><img src='/file?file_id=test.png' alt='Test image'/></p>\n";
  test_captcha ();
  test_mailman ();
  test_mysql ();

  print "\n<h3>Other tests</h3>\n\n";
  print "<table border=\"1\">\n";
  print "<dl>\n";
  test_repos ();
  print "<dt id='sys-upload-dir'>sys_upload_dir writability</dt>\n<dd>";
  test_sys_upload_dir ();
  print "</dd>\n";
  print "<dt id='i18n'>i18n</dt>\n<dd>";
  test_i18n ();
  print "</dd>\n";
  print "</dl>\n";
  test_gpg ();
}

print "<h2>Configured settings</h2>\n";

if (is_readable ($sys_conf_file))
  test_sysconfigs ();
else
  print "Since $sys_conf_file does not exist or is not readable, "
    . "this part cannot be checked.";

print "\n<h2>Optional PHP configuration</h2>\n\n";

print "<p>The following is not required to run Savane, but could enhance\n"
  . "security of your production server. Displaying errors is recommended:\n"
  . "they may annoy the user with warnings, but allow you to spot "
  . "and report\npotentially harmful bugs.</p>\n";

$phptags = [
  'disable_functions' => 'exec,passthru,popen,shell_exec,system',
  'display_errors' => '1',
  'error_reporting' => (string)(E_ALL | E_STRICT), 'log_errors' => '1',
];

print "\n<table border=\"1\">\n"
. "<tr><th>PHP Tag name</th><th>Local value</th>"
. "<th>Suggested value</th></tr>\n";
$have_unset = false;
$cmp = function ($a, $b) { return $a === $b; };
foreach ($phptags as $tag => $good)
  if (compare_ini_vals ($tag, $good, $cmp))
    $have_unset = true;
print "</table>\n\n";
if ($have_unset)
  print "<blockquote>* This tag was not found at all. It is probably irrelevant "
       . "to your PHP version so you may ignore this entry.</blockquote>\n\n";

print "</body>\n<html>\n";
?>
