<?php
# Test utils_format_date ().
#
# Copyright (C) 2022 Ineiev
#
# This file is part of Savane.
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
# Invocation:
#
#   php testing/format_date.php
#
# In case of fail, diagnositc text is output to stdout.

require_once('include/utils.php');

$timestamp = 1667230215;

$test = [
  'en_US' => 'Mon 31 Oct 2022 03:30:15 PM GMT',
  'es_ES' =>  'lun 31 oct 2022 15:30:15 GMT',
  'fr_FR' =>  'lun. 31 oct. 2022 15:30:15 GMT',
  'he_IL' =>  "GMT 15:30:15 2022 אוק 31 ב'",
  'pt_BR' =>  'seg 31 out 2022 15:30:15 GMT',
  'ru_RU' =>  'Пн 31 окт 2022 15:30:15',
];

putenv ('TZ=GMT');
$format = 'default';
foreach ($test as $l => $v0)
  {
    setlocale (LC_ALL, "$l.UTF-8");
    $v = utils_format_date ($timestamp, $format);
    if ($v != $v0)
      print "format: $format; locale: $l; expected: $v0; returned: $v\n";
  }
$format = 'minimal';
foreach ($test as $l => $v0)
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
$m = $tod / 60;
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
