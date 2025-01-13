<?php
# URLs related to offering the corresponding source code (via Cgit).
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Tobias Toedter <t.toedter--gmx.net>
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

# Figure out the commit of the corresponding source code.
function git_get_commit_ ()
{
  $default_val = $GLOBALS['ac_git_commit'];
  $git_dir = dirname (__FILE__) . "/../../../.git";
  if (!is_dir ($git_dir))
    return $default_val;
  $ref_file = "$git_dir/HEAD";
  if (!is_file ($ref_file) || !is_readable ($ref_file))
    return $default_val;
  $ref = file_get_contents ($ref_file);
  if (ctype_xdigit ($ref))
    return $ref;
  if (!preg_match ("/^ref: ([^\n]*)\n$/", $ref, $matches))
    return $default_val;
  $ref_file = "$git_dir/{$matches[1]}";
  if (!is_file ($ref_file) || !is_readable ($ref_file))
    return $default_val;
  return trim (file_get_contents ($ref_file));
}
function git_get_commit ()
{
  static $ret = null;
  if ($ret === null)
    $ret = git_get_commit_ ();
  return $ret;
}
function git_get_savane_url ($page = null)
{
  global $sys_savane_cgit;
  $commit = git_get_commit ();
  if (null === $page)
    return git_get_tarball_url ();
  if ('' === $page)
    return "$sys_savane_cgit/commit/$commit";
  return "$sys_savane_cgit/plain/$page?id=$commit";
}

function git_get_tarball_name ()
{
  $commit = git_get_commit ();
  return "{$GLOBALS['ac_package_tarname']}-$commit.tar.gz";
}

function git_get_tarball_url ($force_git = false)
{
  global $sys_default_domain, $sys_www_topdir, $sys_home, $sys_savane_cgit;
  $tarball_name = git_get_tarball_name ();
  $prot = 'http';
  if (isset ($GLOBALS['sys_https_host']))
    $prot .= 's';
  $base = "$sys_savane_cgit/snapshot";
  $src_dir = 'source';
  if (file_exists ("$sys_www_topdir/$src_dir/$tarball_name") && !$force_git)
    $base = "//$sys_default_domain$sys_home$src_dir";
  return "$prot:$base/$tarball_name";
}

# Return non-zero when tarball URL results in an error.
function git_check_tarball ()
{
  # The built-in server may be blocked if given two requests at once.
  $force_git = php_sapi_name () === 'cli-server';
  $url = git_get_tarball_url ($force_git);
  # Don't emit warnings when fopen fails.
  $error_state = utils_disable_warnings (E_WARNING);
  $f = fopen ($url, 'r');
  utils_restore_warnings ($error_state);
  if ($f === false)
    return 1;
  fclose ($f);
  return 0;
}

function git_agpl_notice ($msg = null)
{
  if (null !== $msg)
    $msg .= ' ';
  $msg = "\n    AGPL NOTICE\n\n$msg"
    . "You can download the corresponding source code of Savane at "
    . git_get_tarball_url () . "\n";
  return wordwrap ($msg, 78);
}

function git_agpl_notice_commented ($msg = null, $line_prefix = '#')
{
  return preg_replace ('/^/m', "$line_prefix ", git_agpl_notice ($msg));
}
function git_agpl_notice_for_js ()
{
  return git_agpl_notice_commented ("This script is served with Savane.", '//');
}
?>
