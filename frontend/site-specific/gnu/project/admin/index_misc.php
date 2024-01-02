<?php

# Savannah - Misc add here details about special features you bring
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2004, 2006 Rudy Gevaert
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2024 Ineiev <ineiev@gnu.org>
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

global $project;

print "\n\n<h2>" . _('Miscellaneous...') . "</h2>\n\n<h3>"
  . _('Backup') . "</h3>\n\n<p>"
  . _("You can get read-only access to your raw CVS files (the RCS\n"
      . '<code>,v</code> ones) using rsync:')
  . "</p>\n\n<pre>";

$host = $project->getTypeBaseHost();
$grp = $GLOBALS['group'];

print "rsync cvs.$host::sources/$grp/\n";
print "rsync cvs.$host::web/$grp/\n</pre>";

if (substr ($GLOBALS['sys_default_domain'], -8) == ".gnu.org")
  {
    # TRANSLATORS: this is a header (<h3>).
    print "\n\n<h3>" .  _('ftp.gnu.org area') . "</h3>\n<p>";
    # TRANSLATORS: the argument is a mailto: link.
    printf (
      _("Each GNU project has a download area at ftp.gnu.org. This area "
        . "is not\nmanaged via Savannah.  Write to %s to get access."),
     '<a href="mailto:account@gnu.org">account@gnu.org</a>'
    );
    print "</p>\n";
  }
?>
