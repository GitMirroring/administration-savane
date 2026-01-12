<?php
# Contact page.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2003 Jaime E. Villate
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2004, 2006 Rudy Gevaert
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry
# Copyright (C) 2013, 2014, 2017-2026 Ineiev <ineiev@gnu.org>
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

print html_h (2, _('Savannah contact')) . "<p>"
  . _("First and foremost: <strong>don't use the links here for contact about\n"
      . "specific projects!</strong>")
  . "</p>\n<p>"
  . _("If you have a question about using a particular program hosted on\n"
      . "Savannah, or a bug report, please please\n<b>visit&nbsp;the&nbsp;"
      . "specific&nbsp;project&nbsp;page</b>&nbsp;(i.e.,&nbsp;"
      . "<code>/projects/projname</code>)\nto find the appropriate contact.  "
      . "Savannah administrators generally have\nno knowledge of specific "
      . "projects and cannot help with questions about\nthem, so it is "
      . "a waste of everyone's time to write us.")
  . "</p>\n<p>"
  . _("Contact points for Savannah itself:") . "</p>\n<ul>\n<li>";

printf (
  _("For general help with and discussion about using Savannah (<b>not</b>\n"
      . "about a specific project hosted here), you can use the <a\n"
      . "href=\"%s\">savannah-users mailing list</a>."),
  '//lists.gnu.org/mailman/listinfo/savannah-users'
);

print "</li>\n<li>"
  . _("To report an issue or submit a request about the Savannah\n"
      . "infrastructure (once again: <b>not</b> for a specific project), "
      . "the best\nway is to");

print ' <a href="/support/?func=additem&amp;group='
  . $GLOBALS['sys_unix_group_name'] . '">'
  . _('submit a request to the Savannah administration tracker')
  . "</a>.</li>\n<li>";

printf (
  _("As an alternative to submitting a tracker item, you can send mail "
    . "to the\n<a href=\"%s\">savannah-help-public</a> list, after "
    . "<a href=\"%s\">searching\nthe archives</a>."),
  '//lists.gnu.org/mailman/listinfo/savannah-hackers',
  '//lists.gnu.org/archive/html/savannah-hackers/'
);

print "</li>\n<li>"
  . _("Finally, to report security-related or confidential issues, you can\n"
      . "use the savannah-help-private list, which is not publicly archived\n"
      . "(unlike all the others here).")
  . "</li>\n</ul>\n";
require_once (dirname (__FILE__) . "/../../php/include/gpg.php");
$key = utils_specialchars (gpg_get_sys_key ());
if (!empty ($key))
  {
    print "\n\n<p>"
      . _("Tracker notifications are signed with the next key,") . "</p>\n";
    print "<pre>$key</pre>\n";
  }

?>
