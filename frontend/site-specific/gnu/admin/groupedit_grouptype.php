<?php
# Direction on how to check if the package is GNU software.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry
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

$url = '//www.gnu.org/help/evaluation.html';
# No need for i18n: this is for admins only.
printf (("To check if a package is official GNU software, see the file\n"
  . "fencepost.gnu.org:/gd/gnuorg/maintainers.  If it is not listed there,\n"
  . "and no official dubbing message has been sent, it must <b>not</b> be\n"
  . "approved as &ldquo;Official GNU Software&rdquo;.  The type should be\n"
  . "changed to non-GNU in this case.  If the submitter wants to offer the\n"
  . "project to GNU, please point them to %s."),
  "<a href=\"$url\">\nhttps:$url</a>"
);
print "<br />\n";
function group_type_list_for_i18n ()
{
  $group_type_names_as_of_feb_2023 = [
    _('Official GNU software'),
    _('non-GNU software and documentation'),
    _('www.gnu.org portions'),
    _('GNU user groups'),
    _('www.gnu.org translation teams'),
    _('Extras for official GNU software')
  ];
  $group_type_descriptions_as_of_feb_2023 = [
    _("This software is part of the GNU Project."),
    _("This group is not part of the GNU Project."),
    _("This group is related to www.gnu.org webmastering. "
      . "It's part of the GNU Project."),
    _("This is GNU user group. It is not part of the GNU Project."),
    _("This is a translation team of www.gnu.org."),
    _("This group contains extras for official GNU software.")
  ];
}
?>
