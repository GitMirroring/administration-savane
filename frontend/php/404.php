<?php
# 'Not found' error page with a Savane look&feel
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2023 Ineiev
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

require_once ('include/init.php');
site_header (['title' => _("Requested Page not Found (Error 404)")]);
print '<p class="warn">';
# TRANSLATORS: the argument is system name (like Savannah).
printf (
  _("The web page you are trying to access doesn't exist on %s."),
  $sys_name
);
print "</p>\n<p>";
printf (
  _("If you think that there's a broken link on %s that must be\nrepaired, "
    . "<a href=\"%s\">file a support request</a>, mentioning the URL you\n"
    . "tried to access (%s)."),
  $sys_name, "${sys_home}support/?group=$sys_unix_group_name",
  utils_specialchars ($_SERVER['REQUEST_URI'])
);

# TRANSLATORS: the second argument is system name (like Savannah).
print "</p>\n<p>";
printf (
  _("Otherwise, you can return to the <a href=\"%s\">%s main page</a>."),
  $sys_home, $sys_name
);
print "</p>\n";
$HTML->footer ([]);
