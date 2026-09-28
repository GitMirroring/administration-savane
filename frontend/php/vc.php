<?php
# Run ViewVC for authenticated users.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Free Software Foundation, Inc.
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

foreach (['init'] as $i)
  require_once ("include/$i.php");

function remote_fopen ($url)
{
  $f = fopen ($url, 'r', false, stream_context_create ());
  if (false === $f)
    exit_error ();
  $h = [];
  if (function_exists ('http_get_last_response_headers'))
    $h = http_get_last_response_headers ();
  elseif (isset ($http_response_header))
    $h = $http_response_header;
  return [$f, $h];
}

function connect_viewvc ($env)
{
  global $sys_viewvc;
  $script = $env['SCRIPT_NAME'];
  $path = $env['PATH_INFO'];
  if (!empty ($env['QUERY_STRING']))
    $path .= '?' . $env['QUERY_STRING'];
  list ($f, $headers) = remote_fopen ("$sys_viewvc$script/$path");
  foreach ($headers as $h)
    header ($h);
  while (!feof ($f))
    print fread ($f, 16834);
  fclose ($f);
}

function filter_header_line ($h)
{
  if (substr ($h, -1) !== "\r")
    return false;
  $h = substr ($h, 0, -1);
  if ($h !== '')
    header ($h);
  return true;
}

function pass_to_viewvc ($script, $path, $qs)
{
  global $sys_viewvc;
  $env = [
   'SCRIPT_NAME' => $script, 'PATH_INFO' => $path, 'QUERY_STRING' => $qs
  ];
  if (preg_match (',^[a-z]+://,', $sys_viewvc))
    return connect_viewvc ($env);
  utils_run_proc ([$sys_viewvc], $out, $err, ['env' => $env]);
  $header = true;
  foreach (explode ("\n", $out) as $line)
    {
      if ($header)
        $header = filter_header_line ($line);
      if (!$header)
        print "$line\n";
    }
}

function get_script_name ()
{
  return '/' . preg_replace ('/[.]php$/', '', basename (__FILE__));
}

function process_request ()
{
  global $sys_viewvc;
  session_require_login ();
  $pfx = get_script_name ();
  $qs = '';
  if (array_key_exists ('QUERY_STRING', $_SERVER))
    $qs = $_SERVER['QUERY_STRING'];
  $path = $_SERVER['REQUEST_URI'];
  if (!empty ($qs))
    $path = substr ($path, 0, -strlen ($qs) - 1);
  if ($path === $pfx) # Listing all repos we have would be too hard.
    exit_error ();
  if (!preg_match (":^$pfx/:", $path))
    exit_error ();
  $path = substr ($path, strlen ($pfx) + 1);
  if (empty ($sys_viewvc))
    exit_error ();
  pass_to_viewvc ($pfx, $path, $qs);
  utils_output_debug_footer ();
}

process_request ();
?>
