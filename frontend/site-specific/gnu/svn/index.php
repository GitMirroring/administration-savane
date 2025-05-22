<?php
# Instructions about Subversion usage.
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
include dirname (__FILE__) . '/../vcs.php';

if ($project->isPublic ())
  {
    vcs_print_anon_access ();
    print '<p>'
      . _("The Subversion repository of this group can be checked out "
        . "anonymously as follows.  The module you wish to check out must be "
        . "specified as the &lt;<var>modulename</var>&gt;.")
      . "</p>\n";

    print html_h (4, _('Access using the SVN protocol:'));
    print "<code>svn co svn://svn.$base_host/$unix_name/&lt;<var>"
      . _('modulename') . "</var>&gt;</code><br />\n";
    print html_h (4, _('Access using HTTP (slower):'));
    print "<code>svn co http://svn.$base_host/svn/$unix_name/&lt;<var>"
      . _('modulename') . "</var>&gt;</code>";

    print '<p>' . _("Typically, you'll want to use <tt>trunk</tt> for "
      . "<var>modulename</var>. Refer to group-specific instructions if "
      . "you're unsure, or browse the repository with ViewVC.")
      . "</p>\n";
  }

vcs_print_auth_access ();
print "<code>svn co svn+ssh://$username@svn.$base_host/$unix_name"
  . "/&lt;<var>" . _('modulename') . "</var>&gt;</code>\n\n";
vcs_print_auth_description ();
print html_h (2, _('Importing into Subversion on Savannah'));
print '<p>';

printf (_("If your group already has an existing source repository that "
  . "you\nwant to move to Savannah, check the <a href=\"%s\">conversion\n"
  . "documentation</a> and then submit a request for the\n"
  . "migration in the <a href=\"%s\">Savannah Administration</a> group."),
  '//savannah.gnu.org/maintenance/CvSToSvN',
  '//savannah.gnu.org/projects/administration'
);
print "</p>\n\n";


print html_h (2, _('Exporting Subversion tree from Savannah'));
print '<p>'
  . _("You can access your subversion raw repository using read-only access "
    . "via\nrsync, and then use that copy as a local SVN repository:")
  . "</p>\n\n<pre>\n";
print "rsync -avHS rsync://svn.$base_host/svn/$unix_name/ /tmp/$unix_name"
  . ".repo/\nsvn co file:///tmp/$unix_name.repo/ trunk\n"
  . "# ...\n</pre>\n\n<p>"
  . _('If you want a dump you can also use svnadmin:') . "</p>\n\n<pre>\n"
  . "svnadmin dump /tmp/$unix_name.repo/\n</pre>\n";
?>
