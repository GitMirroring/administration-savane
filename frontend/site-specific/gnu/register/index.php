<?php
# Savannah - Group registration STEP 0
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2003 Jaime E. Villate
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007 Steven Robson
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

print '<p>';
print _("Savannah is a hosting facility for the free software movement.  Our\n"
  . "mission is to spread the freedom to copy and modify software.");

print "</p>\n<p>";

printf (
  _("<strong>Important</strong>: check <a href=\"%s\">\n"
    . "Group - How to get it approved quickly</a>."),
  '//savannah.gnu.org/maintenance/HowToGetYourProjectApprovedQuickly'
);

print "</p>\n<p>";
print _("It contains a few advices to get your package compliant with\n"
  . "our hosting policies. Following them will considerably speed up the\n"
  . "registration process.");

print "</p>\n<p>";

print _("You are welcome to host your project in Savannah if it falls within "
  . "one\nof these groups:");

print "</p>\n";

print "<dl>\n<dt>";
print _('Free software');
print "</dt>\n<dd>";
print _("A free software package that can run on a completely free operating\n"
  . "system, without depending on any nonfree software. You can only provide\n"
  . "versions for nonfree operating systems if you also provide free\n"
  . "operating systems versions with the same or more functionalities. Large\n"
  . "software distributions are not allowed; they should be split into "
  . "separate\nprojects.");
print "</dd>\n<dt>";

print _('Free documentation');
print "</dt>\n<dd>";
print _("Documentation for free software, released under a free\n"
  . "documentation license.");
print "</dd>\n<dt>";

print _('Free Educational Textbook Projects');
print "</dt>\n<dd>";
print _("Projects aimed to create educational textbooks, released under "
  . "a free\ndocumentation license.");
print "</dd>\n<dt>";

print _('GNU and FSF groups');

print "</dt>\n<dd>";
print _("Specific groups requested by the FSF or the GNU Project.");
print "</dd>\n<dt>";

print _('GNU/Linux User Groups (GUG)');
print "</dt>\n<dd>";

# TRANSLATORS: the second argument is a link to a mailing list.
printf (
  _('Your group should be listed at <a href="%1$s">GNU user group '
    . 'page</a>&mdash;contact %2$s for details.'),
  '//www.gnu.org/gnu/gnu-user-groups.html',
  '<a href="mailto:user-groups@gnu.org">user-groups@gnu.org</a>'
);
print "</dd>\n</dl>";

print '<p>';
print _("In the following registration steps you will be asked\n"
  . "to describe your package and choose a free license for it.");

print "</p>\n<p>";
printf (_("To keep compatibility among Savannah packages, we only accept\n"
  . "free software licenses that are <a href=\"%s\">compatible\n"
  . "with the GPL</a>."),
  '//www.gnu.org/philosophy/license-list.html#GPLCompatibleLicenses'
);

print "</p>\n<p>";
print _("Keep in mind that your group is not approved automatically\n"
  . "after you follow the registration steps; it will be evaluated\n"
  . "by Savannah administrators first."
);

print "</p>\n<p>";
print _("Please fill in this submission form. Savannah administrators\n"
  . "will then review it for hosting compliance.");

print "</p>\n<p><em>";
printf (_("If you wish to submit your package for GNU evaluation,\n"
  . "please check the <a href='%s'>GNU software evaluation</a> webpage\n"
  . "instead."),
  '//www.gnu.org/help/evaluation.html');
print "</em></p>\n";
?>
