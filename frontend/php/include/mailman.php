<?php
# Talk to mailman wrapper.
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
require_once ("init.php");

if (function_exists ('hrtime'))
  {
    function mailman_timestamp ()
    {
      return hrtime (true) / 1000000;
    }
  }
else
  {
    function mailman_timestamp ()
    {
      return microtime (true) / 1000;
    }
  }

function mailman_acquire_lock ()
{
  $tok = ftok (__FILE__, 'a');
  if ($tok === -1)
    return null;
  $sem = sem_get ($tok);
  if ($sem === false)
    return null;
  sem_acquire ($sem);
  return $sem;
}

function mailman_send_request ($cmd, $args)
{
  global $sys_mailman_wrapper;
  $d_spec = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
  $proc = proc_open ("$sys_mailman_wrapper", $d_spec, $pipes);
  if (false === $proc)
    return ['', "can't open pipe"];
  fwrite ($pipes[0], "command=$cmd\n");
  foreach ($args as $k => $v)
    fwrite ($pipes[0], "$k=$v\n");
  fclose ($pipes[0]);
  $ret = explode ("\n", stream_get_contents ($pipes[1]));
  $errors = stream_get_contents ($pipes[2]);
  fclose ($pipes[2]);
  return [$ret, $errors];
}

function mailman_parse_response ($lines)
{
  $ret = ['error' => []];
  foreach ($lines as $l)
    {
      if ($l === '')
        continue;
      if (preg_match ("/^Error/", $l))
        {
          $ret['error'][] = $l;
          continue;
        }
      if (false === strpos ($l, '='))
        {
          $ret['error'][] = "no assignment in $l";
          continue;
        }
      $pos = strpos ($l, '=');
      $ret[substr ($l, 0, $pos)] = substr ($l, $pos + 1);
    }
  return $ret;
}

function mailman_run ($cmd, $args)
{
  $t0 = mailman_timestamp ();
  $lock = mailman_acquire_lock ();
  if ($lock === null)
    return ['error' => "Error: can't acquire semaphore",
        'timestamp' => mailman_timestamp () - $t0
      ];
  list ($lines, $errors) = mailman_send_request ($cmd, $args);
  sem_release ($lock);
  $t0 = mailman_timestamp () - $t0;
  $ret = mailman_parse_response ($lines);
  if (empty ($ret['error']))
    unset ($ret['error']);
  else
    $ret['error'] = join ("\n", $ret['error']);
  if (!empty ($errors))
    $ret['pipe::error'] = $errors;
  $ret['timestamp'] = $t0;
  return $ret;
}

function mailman_get_version ()
{
  return mailman_run ('version', []);
}

function mailman_change_pw ($name)
{
  return mailman_run ('change_pw', ['list_name' => $name, 'password' => '']);
}

function mailman_rmlist ($name)
{
  return mailman_run ('rmlist', ['list_name' => $name]);
}

function mailman_newlist ($name, $admin_email)
{
  return mailman_run ('newlist',
    ['list_full_name' => $name, 'amdin_mail' => $admin_mail, 'password' => '']
  );
}
?>
