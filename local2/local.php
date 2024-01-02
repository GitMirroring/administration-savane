<?php
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon <assafgordon@gmail.com>
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2024 Ineiev
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

# This is a PHP rounter file,
# used only for savannah local development when running
# under php's built-in web server.

# It solves two issues:
#
# 1.
# On savannah's production server,
# EVERY file under ./frontend/php is treated as a PHP file,
# regardless of '.php' extensions.
# This allows URLs like:
#   https://sv.gnu.org/projects/coreutils
#
# to execute ./frontend/php/projects (which is a PHP file without extension).
#
# The PHP built-in server doesn't like this at all.
#
# So we detect two specific cases ('projects' and 'users')
# and load the PHP files explicitly.
#
# All other cases are passed as-is to the PHP webserver (with 'return false')
# which will then load the corresponding PHP file and work as expected.
#
# 2.
# Savane uses gettext for internationalization,
# and it's annoying to set it up (requires fiddling
# with php's configuration file).
# If 'php-gettext' is not available, fake the require
# functions.
#
# See run-local-dev.sh' file to see how this file is used
# (as the last parameter to 'php -S').


# Create stub internationalization functions, if needed.
if (!function_exists ("bindtextdomain"))
  {
    function bindtextdomain ()
    {
      return "";
    }

    function textdomain ()
    {
      return "";
    }
    function _($a)
    {
      return $a;
    }
  }

$path = parse_url ($_SERVER["REQUEST_URI"], PHP_URL_PATH);

# This is set in run-local-dev.sh script.
$phpdir = getenv ('SAVANE_PHPROOT');
if (empty ($phpdir))
  die ("savannah-dev-error: SAVANE_PHPROOT not empty in " . __FILE__);

if (!is_dir ($phpdir))
  die (
    "savannah-dev-error: SAVANE_PHPROOT points to a non-directory '$phpdir'"
  );

foreach (
  ['projects' => ['p', 'pr', 'projects'], 'users' => ['u', 'us', 'users']]
  as $file => $aliases
)
  foreach ($aliases as $a)
    if (preg_match (".^/$a/.", $path))
      {
        include "$phpdir/$file";
        return true;
      }

if ($path === '/file')
  {
    include "$phpdir$path";
    return true;
  }

return false;
?>
