<?php
# Custom error handler.
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
    E_STRICT => "E_STRICT",
    E_RECOVERABLE_ERROR => "E_RECOVERABLE_ERROR",
    E_DEPRECATED => "E_DEPRECATED",
    E_USER_DEPRECATED => "E_USER_DEPRECATED",
    E_ALL => "E_ALL"
  ];
  if (array_key_exists ($errno, $names))
    return $names[$errno];
  return $errno;
}

function error_array_is_list (&$val)
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

function error_print_array (&$val)
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

function error_print_r (&$val)
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

function error_format_backtrace_entry ($entry)
{
  $ret = '';
  $k = 'file';
  if (array_key_exists ($k, $entry))
    $ret .= $entry[$k] . ':';
  $k = 'line';
  if (array_key_exists ($k, $entry))
    $ret .= $entry[$k] . ':';
  $k = 'function';
  if (array_key_exists ($k, $entry))
    {
      $ret .= $entry[$k];
      $ret .= error_format_bt_args ($entry);
    }
  return $ret;
}

function error_format_backtrace ()
{
  $bt = debug_backtrace ();
  array_shift ($bt); array_shift ($bt); array_shift ($bt);
  $ret = [];
  while (count ($bt))
    $ret[] = error_format_backtrace_entry (array_pop ($bt));
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

function error_handler_function (
  $errno, $errstr, $errfile = null, $errline = null
)
{
  if (!($errno & error_reporting ()))
    return true;
  $msg = '';
  if ($errfile !== null)
    $msg .= $errfile;
  $msg .= ':';
  if ($errline !== null)
    $msg .= $errline;
  $msg .= ": [" . error_level_name ($errno) . "] $errstr";
  $msg .=  "\nbacktrace:\n{\n" . error_format_backtrace ();
  $msg .= "\n}\n" . error_format_request ();
  error_log ($msg);
  return true;
}

set_error_handler ('error_handler_function');
?>
