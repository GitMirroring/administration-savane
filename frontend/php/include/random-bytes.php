<?php
# Fallback implementation of random_bytes for PHP 5 and related functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

# <phpass>
# From http://www.openwall.com/phpass/
# Version 0.3 / genuine
# Public domain
# Author: Solar Designer
function phpass_encode64 ($input, $count)
{
  if (!is_int ($count))
    trigger_error ("Count $count isn't integer.");
  $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
  $output = '';
  for ($i = 0; $i < $count; $i++)
    {
      $value = ord ($input[$i++]);
      $output .= $itoa64[$value & 0x3f];
      if ($i < $count)
        $value |= ord ($input[$i]) << 8;
      $output .= $itoa64[($value >> 6) & 0x3f];
      if ($i++ >= $count)
        break;
      if ($i < $count)
        $value |= ord ($input[$i]) << 16;
      $output .= $itoa64[($value >> 12) & 0x3f];
      if ($i >= $count)
        break;
      $output .= $itoa64[($value >> 18) & 0x3f];
    }
  return $output;
}

function phpass_get_random_bytes ($count)
{
  $random_state = microtime ();
  $output = '';
  if (is_readable ('/dev/urandom') && ($fh = @fopen ('/dev/urandom', 'rb')))
    {
      $output = fread ($fh, $count);
      fclose ($fh);
    }
  if (strlen ($output) >= $count)
    return $output;
  $output = '';
  for ($i = 0; $i < $count; $i += 16)
    {
      $random_state = md5 (microtime () . $random_state);
      $output .= pack ('H*', md5 ($random_state));
    }
  $output = substr ($output, 0, $count);
  return $output;
}
# </phpass>

if (!function_exists ('random_bytes'))
  {
    function random_bytes ($count) { return phpass_get_random_bytes ($count); }
  }

function random_hash ($quality = 1)
{
  if ($quality)
    $rand = random_bytes (16);
  else
    $rand = utils_mt_rand ();
  return md5 ($rand);
}
