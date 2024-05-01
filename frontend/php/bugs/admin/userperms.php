<?php
# Set user permissions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Free Software Foundation, Inc.
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
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

require_once ('../../include/init.php');
require_once ('../../include/trackers/general.php');
require_directory ("project");
$is_admin_page = 'y';

session_require (['group' => $group_id, 'admin_flags' => 'A']);

$event_name_prefix = ARTIFACT . '_restrict_event';
$event1_name = $event_name_prefix . TRACKER_EVENT_COMMENT;
$event2_name = $event_name_prefix . TRACKER_EVENT_NEW_ITEM;

extract (sane_import ('post',
  [
    'true' => 'update', 'digits' => [$event1_name, [$event2_name, [0, 99]]]
  ]
));
form_check ('update');

if ($update)
  {
    # If the group entry does not exist, create it.
    if (!db_result (db_execute ("
      SELECT groups_default_permissions_id
      FROM groups_default_permissions WHERE group_id = ?",
      [$group_id]),
      0, "groups_default_permissions_id")
    )
      db_execute (
        "INSERT INTO groups_default_permissions (group_id) VALUES (?)",
        [$group_id]
      );

    $flags = $$event1_name * TRACKER_FLAG_FACTOR + $$event2_name;
    if (!$flags)
      # If equal to 0, manually set to NULL, since 0 has a different meaning.
      $flags = NULL;

    # Update the table.
    $result = db_execute ("
      UPDATE groups_default_permissions SET " . ARTIFACT . "_rflags = ?
      WHERE group_id = ?",
      [$flags, $group_id]
    );
    if ($result)
      {
        group_add_history ('Changed Posting Restrictions', '', $group_id);
        fb (_("Posting restrictions updated."));
      }
    else
      {
        print db_error ();
        fb (_("Unable to change posting restrictions."), 1);
      }
  }

trackers_header_admin (['title' => _("Set Permissions")]);

print html_h (2, _("Posting Restrictions"));
print form_tag () . form_hidden (["group" => $group]);

print '<span class="preinput">'
  . _("Authentication level required to be able to post new items on this "
      . "tracker:")
  . " </span><br />\n";
print '&nbsp;&nbsp;&nbsp;';
print html_select_restriction_box (
  ARTIFACT, group_getrestrictions ($group_id, ARTIFACT), $group, '',
  TRACKER_EVENT_NEW_ITEM
);
print "<br /><br />\n<span class='preinput'>"
  . _("Authentication level required to be able to post comments (and to "
      . "attach\nfiles) on this tracker:")
  . " </span><br />\n";
print '&nbsp;&nbsp;&nbsp;';
print html_select_restriction_box (
  ARTIFACT, group_getrestrictions ($group_id, ARTIFACT, TRACKER_EVENT_COMMENT),
  $group, '', TRACKER_EVENT_COMMENT
);
print form_footer (_("Update Permissions"));
trackers_footer ();
?>
