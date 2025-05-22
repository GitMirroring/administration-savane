<?php
# Common strings and variables used on VCS pages.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2005, 2006, 2010-2012 Michael J. Flickinger
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry
# Copyright (C) 2015-2020, 2022, 2024 Bob Proulx
# Copyright (C) 2013, 2014, 2017-2025 Ineiev <ineiev@gnu.org>
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
global $project;
$base_host = $project->getTypeBaseHost ();
$unix_name = $project->getUnixName ();
if (user_isloggedin ())
  $username = user_getname ();
else
  $username = '&lt;<var>' . _('username') . "</var>&gt;";

function vcs_print_anon_access ()
{
  print html_h (3, _('Anonymous access'));
}
function vcs_print_auth_access ()
{
  print html_h (3, _('Authenticated SSH access'));
}
function vcs_print_auth_access_note ()
{
  global $project, $sys_name;
  if (!$project->isPublic ())
    return;
  print '<p>';
  # TRANSLATORS: the first argument is Unix group name, the second argument
  #   is website name (like Savannah).
  printf (
    _('The remote repository will be writable for %1$s group members '
      . 'and read-only for other %2$s users.'),
    '<code>&lt;' . $project->getUnixName () . '&gt;</code>', $sys_name
  );
  print "</p>\n";
}
function vcs_print_auth_description ()
{
  include dirname (__FILE__) . '/fingerprints.php';
  vcs_print_auth_access_note ();
  print "<p>"
    . _("The SSHv2 public key fingerprints for the machine hosting the source\n"
        . "trees are:")
    . "</p>\n$vcs_fingerprints";
}
function vcs_print_more_info ()
{
  print html_h (2, _('More information'));
}
?>
