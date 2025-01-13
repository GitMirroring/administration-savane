<?php
# Instructions about Git usage.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
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

include dirname (__FILE__) . '/../fingerprints.php';

global $project, $repo_list;

$n = count ($repo_list);
if ($n > 1)
  print "<p>" . _('Note: this group has multiple Git repositories.')
    . "</p>\n";
if ($project->isPublic ())
  {
    print "<h3>" . _('Anonymous clone:') . "</h3>\n<pre>";

    for ($i = 0; $i < $n; $i++)
      {
        if ($n > 1)
          print $repo_list[$i]['desc'] . "\n";
        print "git clone https://git." . $project->getTypeBaseHost ()
          . "/git/" . $repo_list[$i]['url'] . "\n";
        if ($i < $n - 1)
          print "\n";
      }
  }

print "</pre>\n\n<h3>" . _('Member clone:') . "</h3>\n\n<pre>";

$username = user_getname ();
if ($username == "NA")
  # For anonymous user.
  $username = '&lt;<i>' . _('membername') . '</i>&gt;';

for ($i = 0; $i < $n; $i++)
  {
    if ($n > 1)
      print $repo_list[$i]['desc'] . "\n";
    print "git clone $username@git." . $project->getTypeBaseHost () . ":"
      . $repo_list[$i]['path'] . "\n";
    if ($i < $n - 1)
      print "\n";
  }
print "</pre>\n\n<p>"
  . _("The SSHv2 public key fingerprints for the machine hosting the source\n"
      . "trees are:")
   . "</p>\n$vcs_fingerprints";

print '<h3>' . _('More information') . "</h3>\n";
print "<a href=\"//savannah.gnu.org/maintenance/UsingGit\">\n";
print 'https://savannah.gnu.org/maintenance/UsingGit</a>';
?>
