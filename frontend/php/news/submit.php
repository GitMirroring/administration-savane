<?php
# News submission.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2024 Ineiev
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

require_once ('../include/init.php');

extract (sane_import ('post',
  ['true' => 'update', 'specialchars' => ['summary', 'details']]
));
form_check ('update');

if (!group_restrictions_check ($group_id, "news"))
  {
    $msg = sprintf (_("Action Unavailable: %s"),
      group_getrestrictions_explained ($group_id, 'news')
    );
    exit_error ($msg);
  }

if ($update)
  {
    $result = false;
    if ($summary)
      {
        $t = time ();
        $fields = [ 'group_id' => $group_id, 'submitted_by' => user_getid (),
          'is_approved' => 5, 'date' => $t, 'date_last_edit' => $t,
          'forum_id' => 0, 'summary' => $summary, 'details' => $details
        ];
        $result = db_autoexecute ('news_bytes', $fields, DB_AUTOQUERY_INSERT);
      }
    else
      fb (_("Title is missing"), 1);
    if ($result)
      {
        $feedback = _("News Posted: it will need to be approved by a news "
         . "manager of this group before it shows on the front page.");
        session_redirect (
          "{$sys_home}news/?group=$group&feedback="
          . utils_urlencode ($feedback)
        );
      }
    else
      fb (_("Error doing insert"), 1);
  }

if (empty ($group_id))
  exit_no_group ();

site_project_header (
  ['title' => _("Submit News"), 'group' => $group_id, 'context' => 'news']
);
print '<p class="warn">'
  . _("A news manager of this group will have to review and approve the news.")
  . "</p>\n"
  . form_header () . form_hidden (["group_id" => $group_id])
  . "<span class='preinput'>" . html_label ('summary', _("Subject:"))
  . "</span><br/>&nbsp;&nbsp;\n"
  . form_input ('text', 'summary', $summary, "size='65' maxlength='80'")
  . "<br />\n<span class='preinput'>" . html_label ('details', _("Details"))
  . markup_info ("full") . "</span><br />&nbsp;&nbsp;\n"
  . form_textarea ('details', $details, "rows='20' cols='65' wrap='soft'")
  . "<br />\n" . form_footer ();
site_project_footer ([]);
?>
