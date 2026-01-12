<?php
# Test utils_strftime ().
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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
#
# Invocation:
#
#   php testing/strftime.php
#
# In case of fail, diagnostic text is output to stdout.

require_once ('include/utils.php');

$exit_code = 0;
$timestamp = ': ' . time ();
$res = utils_strftime ($timestamp, null);
if ($res !== "date-based$timestamp")
  {
    print "Fail to use date-based implementation: '$res'.\n";
    $exit_code = 1;
  }
$sys_use_strftime = 1;
$res = utils_strftime ($timestamp, null);
$val = "PHP$timestamp";
if (function_exists ('strftime'))
  {
    if ($res !== $val)
      {
        print "Fail to use PHP implementation: '$res'.\n";
        $exit_code = 1;
      }
  }
elseif ($res === $val)
  {
    print "Using non-existent PHP implementation.\n";
    $exit_code = 1;
  }

exit ($exit_code);
?>
