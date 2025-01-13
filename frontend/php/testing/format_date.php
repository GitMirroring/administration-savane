<?php
# Test utils_format_date ().
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
#
# Invocation:
#
#   php testing/format_date.php
#
# In case of fail, diagnostic text is output to stdout.

require_once('include/utils.php');

$timestamp = 1667230215;

$test = ['en_US', 'es_ES', 'fr_FR', 'he_IL', 'pt_BR', 'ru_RU'];

putenv ('TZ=GMT');
$format = 'default';
$l = 'en_US';
$v0 = 'Mon 31 Oct 2022 03:30:15 PM GMT';
setlocale (LC_ALL, "$l.UTF-8");
$v = str_replace ('UTC', 'GMT', utils_format_date ($timestamp, $format));
if ($v != $v0)
  print "format: $format; locale: $l; expected: $v0; returned: $v\n";
$format = 'minimal';
foreach ($test as $l)
  {
    setlocale (LC_ALL, "$l.UTF-8");
    $v = utils_format_date ($timestamp, $format);
    $v0 = '2022-10-31';
    if ($v != $v0)
      print "format: $format; locale: $l; expected: $v0; returned: $v\n";
  }
$format = 'natural';
$t = time ();
$day = 60 * 60 * 24;
$delta = 3;
$t0 = $t - 17 * 60 - 51;
$tod = $t0 % $day;
if ($tod <= $day / 2 && $tod >= $day / 2 - $delta)
  $t0 -= 34 * 60;
if (!$tod || $tod >= $day - $delta)
  $t0 -= 34 * 60;
$tod = $t0 % $day;
$m = intval($tod / 60);
$hour = intval ($m / 60);
$minute = $m % 60;
foreach ($test as $l => $v0)
  {
    setlocale (LC_ALL, "$l.UTF-8");
    $v = utils_format_date ($t0, $format);
    $v0 = sprintf ("%02d:%02d", $hour, $minute);
    if ($v != $v0)
      print "format: $format; locale: $l; expected: $v0; returned: $v\n";
  }
?>
