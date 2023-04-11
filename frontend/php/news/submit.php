<?php
# News subnissions.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2023 Ineiev
#
# This file is part of Savane.
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

require_once ('../include/init.php');
require_once ('../include/news/forum.php');

extract (sane_import ('post',
  [
    'hash' => 'form_id',
    'true' => 'update',
    'specialchars' => ['summary', 'details'],
  ]
));

if (!group_restrictions_check ($group_id, "news"))
  {
    $msg = sprintf (_("Action Unavailable: %s"),
      group_getrestrictions_explained ($group_id, 'news')
    );
    exit_error ($msg);
  }

if ($update)
  {
    $valid = form_check ($form_id);
    if (!$summary)
      {
        fb (_("Title is missing"), 1);
        $valid = 0;
      }
    $result = false;
    if ($valid)
      {
        # Insert the new item, with 5 as status: group admin
        # must moderate it. There must be a title.
        $new_id = forum_create_forum ($group_id, $summary, 1, 0);

        $t = time ();
        $fields = [ 'group_id' => $group_id, 'submitted_by' => user_getid (),
          'is_approved' => 5, 'date' => $t, 'date_last_edit' => $t,
          'forum_id' => $new_id, 'summary' => $summary, 'details' => $details
        ];
        $result = db_autoexecute ('news_bytes', $fields, DB_AUTOQUERY_INSERT);
      }
    if ($result)
      {
        $feedback = _("News Posted: it will need to be approved by a news "
         . "manager of this group before it shows on the front page.");
        form_clean ($form_id);
        session_redirect (
          "${sys_home}news/?group=$group&feedback="
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
  . form_header ($_SERVER['PHP_SELF'], $form_id)
  . form_hidden (["group_id" => $group_id])
  . "<span class='preinput'><label for='summary'>" . _("Subject:")
  . "</label></span><br/>&nbsp;&nbsp;\n"
  . "<input type='text' id='summary' name='summary' value=\"$summary\" "
  . "size='65' maxlenght='80' /><br />\n<span class='preinput'>"
  . "<label for='details'>" . _("Details") . '</label> '
  . markup_info ("full") . "</span><br />&nbsp;&nbsp;\n"
  . "<textarea name='details' id='details' rows='20' cols='65' wrap='soft'>\n"
  . "$details</textarea><br />\n" . form_footer ();
site_project_footer ([]);
?>
