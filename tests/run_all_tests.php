<?php
# <one line to give a brief idea of what this does.>
# 
#  Copyright (C) 1999, 2000 The SourceForge Crew
#  Copyright (C) 2000-2006 Mathieu Roy
#  Copyright (C) 2002-2006 Tobias Toedter <t.toedter--gmx.net>
#  Copyright (C) 2014, 2016, 2017 Assaf Gordon
#  Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
#  Copyright (C) 2013, 2014, 2017-2024 Ineiev
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


require_once 'PHPUnit.php';

# Collect all test files in the current directory
if ($handle = opendir("./"))
  {
    while (false !== ($file = readdir($handle)))
      {
        if (substr($file, 0, 5) == "test_")
	  {
	    # Create a hash with the filename as the key
	    # and the test class (without test_ and .php)
	    # as the value
	    $tests[$file] = substr($file, 5, -4);
	  }
      }
    closedir($handle);
  }
else
  {
    print "Could not open current directory.\n";
  }


$stats_tests = 0;
$stats_failures = 0;
$stats_errors = 0;

# Run each test file separately
foreach ($tests as $filename => $testclass)
  {
    require_once $filename;

    $suite  = new PHPUnit_TestSuite($testclass);
    $result = PHPUnit::run($suite);

    print $result->toString();
    if ($result->wasSuccessful())
      {
        printf("  -> OK (%s tests)\n", $result->runCount());
      }
    else
      {
        printf("  -> FAILED (%s tests, %s failures, %s errors)\n", $result->runCount(),
          $result->failureCount(), $result->errorCount());
      }

    $stats_tests += $result->runCount();
    $stats_failures += $result->failureCount();
    $stats_errors += $result->errorCount();
  }

print "\nDone.\n";
printf("%s tests, %s failures, %s errors\n", $stats_tests,
  $stats_failures, $stats_errors);
?>
