<?php

# Instructions about Bzr usage.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry
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
$http_base_url = "bzr.$base_host/r/$unix_name";
$bzr_base_url = "bzr://bzr.$base_host/$unix_name";

if ($project->isPublic ())
  {
    print '<h3>' . _('Anonymous read-only access') . "</h3>\n";
    print '<p>'
      . _("The Bazaar repositories for projects use separate directories for\n"
        . "each branch. You can see the branch names in the repository by "
        . "pointing\na web browser to:")
      . "<br />\n<code>http://$http_base_url</code></p>\n\n"
      . "<ul>\n<li><p>"
      . _("For a repository with separate branch directories (<tt>trunk</tt>,\n"
      . "<tt>devel</tt>, &hellip;), use:")
      . "</p>\n<pre>bzr branch $bzr_base_url/" . _('<i>branch</i>')
      . "</pre>\n<p>"
      . _('where <i>branch</i> is the name of the branch you want.')
      . "</p>\n</li>\n\n<li><p>"
      . _('For a repository with only a top-level <tt>.bzr</tt> directory, '
         . 'use:')
      . "</p>\n\n<pre>bzr branch $bzr_base_url</pre>\n</li>\n\n<li><p>"
      . _("If you need the low-performance HTTP or HTTPS access, these are "
      . "the URLs:")
      . "</p>\n<pre>http://$http_base_url/" . _('<i>branch</i>')
      . "\nhttps://$http_base_url/" . _('<i>branch</i>')
      . "\n</pre>\n</li>\n</ul>\n\n";
  }

print '<h3>' . _('Developer write access (SSH)') . "</h3>\n";

$username = user_getname ();
if ($username == "NA")
   $username = '&lt;<i>' . _('membername') . "</i>&gt;\n";
print "<pre>bzr branch bzr+ssh://$username@bzr.$base_host/$unix_name/"
  . "<i>branch</i></pre>\n\n";
print '<h3>' . _('More introductory documentation') . "</h3>\n";

print '<p>';
printf (
  _('Check the <a href="%s">UsingBzr</a> page at the documentation wiki.'),
  "//savannah.gnu.org/maintenance/UsingBzr"
);
print "</p>\n";

print "<p>"
  . _("The SSHv2 public key fingerprints for the machine hosting the source\n"
    . "trees are:")
  . "</p>\n$vcs_fingerprints\n";
?>
