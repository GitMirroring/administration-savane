<?php
# Custom error handler.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2026 Ineiev
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

if (function_exists ('hrtime'))
  {
    function error_timestamp ()
    {
      return hrtime (true) / 1000000;
    }
  }
else
  {
    function error_timestamp ()
    {
      return microtime (true) * 1000;
    }
  }

function error_test_timestamp ($delay = 5000)
{
  $t = error_timestamp ();
  usleep ($delay);
  $t -= error_timestamp ();
  $t *= -1000;
  $dt = abs ($t - $delay);
  if ($dt / $delay < .1)
    return 'OK';
  if ($delay < 30000)
    # No success; give it a chance with a lower resolution.
    return error_test_timestamp ($delay * 4);
  add_fb ('Timestamps are inaccurate.');
  return "<strong>Timestamp mismatch: $t vs $delay</strong>";
}

$TIMESTAMP_START = error_timestamp ();

function error_level_name ($errno)
{
  $names = [
    E_ERROR => "E_ERROR",
    E_WARNING => "E_WARNING",
    E_PARSE => "E_PARSE",
    E_NOTICE => "E_NOTICE",
    E_CORE_ERROR => "E_CORE_ERROR",
    E_CORE_WARNING => "E_CORE_WARNING",
    E_COMPILE_ERROR => "E_COMPILE_ERROR",
    E_COMPILE_WARNING => "E_COMPILE_WARNING",
    E_USER_ERROR => "E_USER_ERROR",
    E_USER_WARNING => "E_USER_WARNING",
    E_USER_NOTICE => "E_USER_NOTICE",
    E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
    E_DEPRECATED => "E_DEPRECATED",
    E_USER_DEPRECATED => "E_USER_DEPRECATED",
    E_ALL => "E_ALL"
  ];
  if (version_compare (PHP_VERSION, '8.4.0') < 0)
    $names[E_STRICT] = "E_STRICT";
  if (array_key_exists ($errno, $names))
    return $names[$errno];
  return $errno;
}

function error_array_is_list ($val)
{
  $i = 0;
  foreach ($val as $k => $ignored)
    {
      if (!is_int ($k))
        return false;
      if ($k != $i++)
        return false;
    }
  return true;
}

function error_print_array ($val)
{
  $ret = [];
  if (error_array_is_list ($val))
    {
      foreach ($val as $v)
        $ret[] = error_print_r ($v);
      return '[' . join (', ', $ret) . ']';
    }
  foreach ($val as $k => $v)
    $ret [] = error_print_r ($k) . " => " . error_print_r ($v);
  return '[' . join (",\n", $ret) . ']';
}

function error_print_r ($val)
{
  if ($val === null)
    return '<NULL>';
  if (is_bool ($val))
    {
      if ($val)
        return '<true>';
      return '<false>';
    }
  if (is_string ($val))
    {
      $val = str_replace ('\\', '\\\\', $val);
      return "'" . str_replace ("'", "\\'", $val) . "'";
    }
  if (is_scalar ($val))
    return $val;
  if (!is_array ($val))
    return print_r ($val, true);
  return error_print_array ($val) . "\n";
}

function error_format_bt_args (&$entry)
{
  if (!array_key_exists ('args', $entry))
    return '<no args>';

  $a = [];
  foreach ($entry['args'] as $v)
    $a[] =  error_print_r ($v);
  return '(' . join (', ', $a) .  ')';
}

function error_relative_source_path ($path)
{
  global $sys_www_topdir;
  static $prefix = null;
  if ($prefix === null && isset ($sys_www_topdir))
    $prefix = dirname ($sys_www_topdir);
  if ($prefix === null)
    return $path;
  return preg_replace (",$prefix,", '$topdir', $path);
}

function error_format_backtrace_entry ($entry, $include_args)
{
  $ret = '';
  $k = 'file';
  if (array_key_exists ($k, $entry))
    $ret .= error_relative_source_path ($entry[$k]) . ':';
  $k = 'line';
  if (array_key_exists ($k, $entry))
    $ret .= $entry[$k] . ':';
  $k = 'function';
  if (array_key_exists ($k, $entry))
    {
      $ret .= $entry[$k];
      if ($include_args)
        $ret .= error_format_bt_args ($entry);
    }
  return $ret;
}

function error_format_backtrace ($include_args = true)
{
  $bt = debug_backtrace ();
  array_shift ($bt); array_shift ($bt); array_shift ($bt);
  $ret = [];
  while (count ($bt))
    $ret[] = error_format_backtrace_entry (array_pop ($bt), $include_args);
  return join ("\n-> ", $ret);
}

function error_format_request ()
{
  if (empty ($_SERVER))
    return '';
  $ret = "_SERVER: ";
  $a = [];
  foreach (['REQUEST_URI', 'QUERY_STRING', 'REQUEST_METHOD'] as $f)
    if (array_key_exists ($f, $_SERVER))
      $a[$f] = $_SERVER[$f];
  $ret .= error_print_r ($a);
  if (isset ($_SERVER['REQUEST_METHOD']))
    {
      if ($_SERVER['REQUEST_METHOD'] == 'POST')
        $ret .= 'POST: ' . error_print_r ($_POST);
      if ($_SERVER['REQUEST_METHOD'] == 'GET')
        $ret .= 'GET: ' . error_print_r ($_GET);
    }
  return "request params $ret";
}

function error_title_filter ($title, $excludes)
{
  if (!is_array ($excludes) || !is_string ($title))
    return false;
  foreach ($excludes as $ex)
    if (!is_string ($ex) || strpos ($title, $ex) !== false)
      return false;
  return true;
}

function error_sendmail_from ()
{
  global $sys_mail_replyto, $sys_mail_domain;
  $from = 'savane-no-reply@localhost';
  if (empty ($sys_mail_replyto) || empty ($sys_mail_domain))
    return $from;
  return "$sys_mail_replyto@$sys_mail_domain";
}

# A simplisic version of sendmail_mail to avoid possible recursive errors.
function error_sendmail ($addr, $subj, $msg)
{
  $headers = "From: " . error_sendmail_from () . "\n";
  list ($usec, $sec) = explode (' ', microtime ());
  $msg_id = date ("Ymd-His", $sec) . ".$usec.sv.@error";
  $headers .= "Message-Id: <$msg_id>\n";
  mail ($addr, $subj, $msg, $headers);
}

function error_normalize_conf ($conf)
{
  $ret = ['subject' => '[savane error]', 'exclude' => []];
  if (!empty ($conf['exclude']))
    {
      $ex = $conf['exclude'];
      if (!is_array ($ex))
        $ex = [$ex];
      foreach ($ex as $e)
        if (is_string ($e))
          $ret['exclude'][] = $e;
    }
  if (!empty ($conf['subject']) && is_string ($conf['subject']))
    {
      $ret['subject'] = $conf['subject'];
      $ret['subject'] = preg_replace (
        [',[^[:ascii:]],', ',[^[:graph:][:blank:]],'],
        '', $ret['subject']
       );
    }
  return $ret;
}

function error_cc_limit_path ($override = null)
{
  global $sys_trackers_attachments_dir, $sys_cc_error_file;
  if ($override !== null)
    return $override;
  if (!empty ($sys_cc_error_file))
    return $sys_cc_error_file;
  return "$sys_trackers_attachments_dir/cc-error";
}

# Maximum number of error email messages sent in error_cc_period () seconds.
function error_cc_limit ()
{
  global $sys_error_cc_limit;
  if (isset ($sys_error_cc_limit) && $sys_error_cc_period > 0)
    return $sys_error_cc_limit;
  return 17;
}

# Period for error email message rate in seconds.
function error_cc_period ()
{
  global $sys_error_cc_period;
  if (isset ($sys_error_cc_period) && $sys_error_cc_period > 0)
    return $sys_error_cc_period;
  return 289;
}

function error_count_cc ($cc_error_file = null, $period = null)
{
  if ($period === null)
    $period = error_cc_period ();
  $t = time ();
  $ret = [$t];
  $t -= $period;
  $lines = file (error_cc_limit_path ($cc_error_file), FILE_IGNORE_NEW_LINES);
  if ($lines === false)
    return $ret;
  foreach ($lines as $l)
    if ($l > $t)
      $ret[] = $l;
  return $ret;
}

function error_check_cc_limit ($cc_error_file = null, $limit = null)
{
  $sem = null; $ret = true;
  $state = utils_disable_warnings ();
  if (function_exists ('sem_get'))
    $sem = utils_sem_acquire (__FILE__);
  $timestamps = error_count_cc ($cc_error_file);
  $limit = $limit === null? error_cc_limit (): $limit;
  if (count ($timestamps) <= $limit)
    {
      $ret = false;
      $f = fopen (error_cc_limit_path ($cc_error_file), 'w');
      if ($f !== false)
        {
          fwrite ($f, join ("\n", $timestamps));
          fclose ($f);
        }
    }
  if ($sem !== null)
    sem_release ($sem);
  utils_restore_warnings ($state);
  return $ret;
}

function error_test_cc_limit_path ()
{
  $p = error_cc_limit_path ();
  $ret = "$p:";
  $tests = [
    'file_exists' => no_i18n ("file doesn't exist"),
    'is_file' => no_i18n ("not a file"),
    'is_readable' => no_i18n ("file isn't readable"),
    'is_writable' => no_i18n ("file isn't writable")
  ];
  foreach ($tests as $f => $label)
    if (!$f ($p))
      return "$ret <strong>$label</strong>";
  return "$ret OK";
}

function error_test_cc_limit_update ()
{
  $cc_limit = 3;
  $ret = $res = false;
  $state = utils_disable_warnings ();
  $cc_file = utils_mktemp ('cc-error');
  utils_restore_warnings ($state);
  if ($cc_file === null)
    return "<strong>" . no_i18n ("can't create temporary file") . "</strong>";
  for ($i = 0; $i < $cc_limit + 2 && !$res; $i++)
    $res = error_check_cc_limit ($cc_file, $cc_limit);
  if ($i < $cc_limit + 1)
    $ret = sprintf (
      no_i18n ("limit is too low (%d vs. %d)"), $i, $cc_limit + 1
    );
  if (!$res)
    $ret = no_i18n ("no limit encountered");
  utils_rm_fr ($cc_file);
  if ($ret !== false)
    return "<strong>$ret</strong>";
  return no_i18n ('OK');
}

function error_list_cc_error_param ($var)
{
  global $saved_sys_debug_footer;
  $msg = no_i18n ('The effective value is %s.');
  if ($saved_sys_debug_footer)
    $msg = no_i18n (
      'With $sys_debug_footer unset, the effective value would be %s.'
    );
  $func = "error_cc_$var";
  $id = "cc-error-$var";
  $val = $func ();
  $val_txt = "<br />\n" . sprintf ($msg, $val);
  if (!isset ($GLOBALS["sys_cc_error_$var"]))
    return [$id, no_i18n ('<b>unset</b>') . $val_txt];
  $ret = $GLOBALS["sys_cc_error_$var"];
  if ($ret != $val)
    $ret .= no_i18n (' <strong>(overridden)</strong>') . $val_txt;
  return [$id, $ret];
}

function error_show_sys_debug_footer ()
{
  global $saved_sys_debug_footer;
  $id = 'error-cc-debug-footer';
  if (!isset ($saved_sys_debug_footer))
    return [$id, no_i18n ('<b>unset</b>')];
  $ret = $saved_sys_debug_footer? 1: 0;
  if ($saved_sys_debug_footer)
    $ret .=
      no_i18n ("<br />\n<i>The error email report rate is not limited.</i>");
  return [$id, $ret];
}

function error_test_cc_limit ()
{
  $defs = [no_i18n ("\$sys_debug_footer") => error_show_sys_debug_footer ()];
  foreach (['limit', 'period'] as $var)
  $defs[no_i18n ("\$sys_error_cc_$var")] =
    error_list_cc_error_param ($var);
  $defs[no_i18n ('timestamp file')] = error_test_cc_limit_path ();
  $state = utils_disable_warnings ();
  $cnt = count (error_count_cc ()) - 1;
  utils_restore_warnings ($state);
  $defs[no_i18n ('timestamp count')] = $cnt;
  $msg = error_test_cc_limit_update ();
  $defs[no_i18n ('updating timestamps')] = $msg;
  if ($msg !== 'OK')
    add_fb ('Updating limit rate failed.');
  $ret = html_dl ($defs);
  if (!function_exists ('sem_get'))
    $ret .= "<p>"
      . "<strong>Note: the sem_get function doesn't exist.  "
      . "A race condition is possible.</strong></p>\n";
  return $ret;
}

function error_cc_log ($location, $title, $msg)
{
  global $sys_cc_error, $sys_debug_footer;
  if (empty ($sys_cc_error))
    return;
  $cc_err = $sys_cc_error;
  if (!is_array ($cc_err))
    $cc_err = [$cc_err => []];
  $location = preg_replace (',^[^:]*/frontend/,', '', $location);
  $email_counter = 0;
  foreach ($cc_err as $addr => $conf)
    {
      $conf = error_normalize_conf ($conf);
      if (!error_title_filter ($title, $conf['exclude']))
        continue;
      # $sys_debug_footer is only used in debugging environments, and it
      # adds an error report for every page served, so error rate isn't
      # limited in that case.
      if (!$sys_debug_footer && !$email_counter++)
        if (error_check_cc_limit ())
          return;
      error_sendmail ($addr, "{$conf['subject']} $location", $msg);
    }
}

function error_request_headers ()
{
  if (function_exists ('apache_request_headers'))
    return apache_request_headers ();
  $ret = [];
  foreach ($_SERVER as $k => $v)
    if (!strncmp ($k, 'HTTP_', strlen ('HTTP_')))
      $ret[$k] = $v;
  return $ret;
}

function error_response_headers ()
{
  if (function_exists ('apache_response_headers'))
    return apache_response_headers ();
  return headers_list ();
}

function error_format_headers ()
{
  return "request headers:\n" . error_print_r (error_request_headers ())
    . "response headers:\n" . error_print_r (error_response_headers ());
}

function error_handler_function ($errno, $errstr, $file = null, $line = null)
{
  if (!($errno & error_reporting ()))
    return true;
  $location = '';
  if ($file !== null)
    $location .= $file;
  $location .= ':';
  if ($line !== null)
    $location .= $line;
  if (!is_string ($errstr))
    $errstr = print_r ($errstr, true);
  $title = "$location: [" . error_level_name ($errno) . "]";
  $msg = "$title\n" . error_format_request () . error_format_headers ();
  $msg .= "$errstr\nbacktrace:\n{\n" . error_format_backtrace () . "\n}";
  error_log ($msg);
  error_cc_log ($location, "$title$errstr", $msg);
  return true;
}

set_error_handler ('error_handler_function');
?>
