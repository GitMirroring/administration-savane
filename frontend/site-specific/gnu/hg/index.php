<?php
# Instructions about Hg usage.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
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

include dirname (__FILE__) . '/../fingerprints.php';

global $project;
$base_host = $project->getTypeBaseHost ();
$unix_name = $project->getUnixName ();
$type_dir = preg_replace (':/srv/hg:', '', $project->getTypeDir ('hg'));

if ($project->isPublic ())
  {
    print '<h3>' . _('Anonymous read-only access') . "</h3>\n";
    print "<pre>hg clone https://hg.$base_host/hgweb$type_dir\n</pre>\n";
  }

print '<h3>' . _('Developer write access (SSH)') . "</h3>\n";

$username = user_getname ();
if ($username == "NA")
  $username = '&lt;<i>' . _('membername') . "</i>&gt;\n";

print "<pre>hg clone ssh://$username@hg.$base_host/$unix_name</pre>\n<p>";
print
  _("The SSHv2 public key fingerprints for the machine hosting the source\n"
    . "trees are:")
  . "</p>\n$vcs_fingerprints\n";

print '<h3>' . _('More information') . "</h3>\n";
print "<p><a href=\"//savannah.gnu.org/maintenance/UsingHg\">\n"
 . "https://savannah.gnu.org/maintenance/UsingHg</a></p>\n";
?>
