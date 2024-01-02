<?php
# URLs related to offering the corresponding source code (via Cgit).
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Tobias Toedter <t.toedter--gmx.net>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
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

# Figure out the commit of the corresponding source code.
function git_get_commit ()
{
  $default_val = $GLOBALS['ac_git_commit'];
  $git_dir = dirname (__FILE__) . "/../../../.git";
  if (!is_dir ($git_dir))
    return $default_val;
  $ref_file = "$git_dir/HEAD";
  if (!is_file ($ref_file) || !is_readable ($ref_file))
    return $default_val;
  $ref = file_get_contents ($ref_file);
  if (!preg_match ("/^ref: ([^\n]*)\n$/", $ref, $matches))
    return $default_val;
  $ref_file = "$git_dir/{$matches[1]}";
  if (!is_file ($ref_file) || !is_readable ($ref_file))
    return $default_val;
  return trim (file_get_contents ($ref_file));
}
function git_get_savane_url ($commit)
{
  global $sys_savane_url, $sys_savane_cgit;
  if (empty ($commit))
    return $sys_savane_url;
  return "$sys_savane_cgit/commit/?id=$commit";
}

function git_get_tarball_name ()
{
  $commit = git_get_commit ();
  return "{$GLOBALS['ac_package_tarname']}-$commit.tar.gz";
}

function git_get_tarball_url ()
{
  $tarball_name = git_get_tarball_name ();
  $prot = 'http';
  if (isset ($GLOBALS['sys_https_host']))
    $prot .= 's';
  return "$prot:{$GLOBALS['sys_savane_cgit']}/snapshot/$tarball_name";
}

# Return non-zero when tarball URL results in an error.
function git_check_tarball ()
{
  $f = fopen (git_get_tarball_url (), 'r');
  if ($f === false)
    return 1;
  fclose ($f);
  return 0;
}
?>
