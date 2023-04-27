<?php

# Instructions about mailing lists.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry
# Copyright (C) 2013, 2014, 2017-2023 Ineiev <ineiev@gnu.org>
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

print "\n<h2>" . _('New mailing lists at Savannah') . "</h2>\n\n<p>";
printf (
  _("New mailing lists created through Savannah have the following\n"
    . "features enabled by default, in addition to the usual\n"
    . "<a href=\"%s\">GNU Mailman</a> defaults."),
  '//www.gnu.org/software/mailman/'
);

print "</p>\n\n<ul>\n<li>"
  . _("No footer is added to messages (<tt>footer</tt>, under\n"
      . "&lsquo;Non-digest options&rsquo;).")
  . "</li>\n\n<li>"
  . _("When a new member joins the list, their initial posting is held for\n"
      . "the list owner's review (<tt>default_member_moderation</tt>, under\n"
      . "&lsquo;Privacy &gt; Sender filters&rsquo;).  This is necessary "
      . "because\nsmart spam systems can now automatically subscribe "
      . "to mailman lists and\nthen start sending their junk mail.  However, "
      . "provided the subscription\nis legitimate (as it usually will be), "
      . "we strongly recommend\n<i>accept</i>ing their address for future "
      . "postings.")
  . "</li>\n<li>";
printf (
  _("Automated (though asynchronous) spam deletion, through the <a\n"
    . "href=\"%s\">listhelper system</a>.  More details about this:"),
  "//www.nongnu.org/listhelper/"
);
print "</li>\n<ul>\n<li>"
  . _("The first time a person posts to a list under a particular email\n"
      . "address, the message is held for review, potentially by the mailman "
      . "list\nowners, the listhelper automated system, and the listhelper\n"
      . "human volunteers.  This is when spam is detected and deleted.")
  . "</li>\n<li>"
  . _("Therefore, if you are concerned about your list's messages being\n"
      . "seen by the small group of listhelper volunteers, you should "
      . "disable\nlisthelper (remove listhelper@nongnu.org from the "
      . "<tt>moderator</tt>\nfield on the &lsquo;General Options&rsquo; "
      . "page), and consequently deal\nwith any incoming spam yourself.")
  . "</li>\n<li>"
  . _("By default, Mailman sends a &ldquo;request for approval&rdquo;\n"
      . "notification on every message, and this is critical for "
      . "listhelper's\noperation.  You will probably want to filter these "
      . "from your inbox, with\na pattern such as this:")
  . "<br />\n<tt>^Subject: confirm [a-f0-9]{40}</tt>\n<br />\n"
  ._("(Despite appearances, that needs to match in the body of the message,\n"
     . "not the headers.)  Alternatively, if you choose to turn off "
     . "listhelper,\nyou may also want to turn off this option "
     . "(<tt>admin_immed_notify</tt>\non the &lsquo;General Options&rsquo; "
     . "page).")
  . "</li>\n<li>";
printf (
  _('For more information, see the <a href="%s">listhelper home page</a>.'),
  "//www.nongnu.org/listhelper/"
);

print "</li>\n</ul>\n</ul>\n\n<p>"
  . _("Of course, as the list owner, you can make changes to the\n"
      . "configuration at any time, but if in any doubt, please ask.  The\n"
      . "defaults are set to minimize spam, list administrator overhead, "
      . "and the\nchance of our mail server being blacklisted.")
  . "</p>\n";
?>
