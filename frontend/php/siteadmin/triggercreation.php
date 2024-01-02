<?php
# Trigger group creation.
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

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.

require_once ('../include/init.php');
require_once ('../include/proj_email.php');

# Skip admin rights check if we are dealing with the sys group.
if ($sys_group_id != $group_id)
  session_require (['group' => '1','admin_flags' => 'A']);

# Configure the group according to group type settings.
# If a group can use a feature for its group type, assume he would
# use it by default.
# Exception: the patch tracker is deprecated, so it is ignored.
$res = db_execute ("SELECT type FROM groups WHERE group_id = ?", [$group_id]);
$group_type = db_result ($res, 0,'type');
$res_type = db_execute (
  "SELECT * FROM group_type WHERE type_id = ?", [$group_type]
);
$user_id = user_getid ();

$to_update = ["homepage", "download", "cvs", "forum", "mailing_list", "task",
  "news", "support", "bug"
];
$upd_list = [];

foreach ($to_update as $field)
  {
    $value = db_result ($res_type, 0, "can_use_$field");

    if ($field == 'mailing_list')
      $field = 'mail';
    if ($field == 'bug')
      $field = 'bugs';
    $field = "use_$field";

    # TRANSLATORS: the first argument is field, the second is value.
    fb (sprintf (no_i18n ('%1$s will be set to %2$s'), $field, $value));
    $upd_list[$field] = $value;
  }

if ($upd_list)
  {
    $result = db_affected_rows (
      db_autoexecute ('groups', $upd_list, DB_AUTOQUERY_UPDATE, "group_id = ?",
        [$group_id]
      )
    );
    if ($result)
      {
        fb_dbsuccess ();
        group_add_history (
          'Set Active Features to the default for the Group Type',
          user_getname ($user_id), $group_id
        );
      }
    else
      fb (no_i18n ("No field to update or SQL error"));
  }

# Now set a default notification setup for the trackers.
# We do not even check whether the trackers are used, because we want this
# configuration to be already done if at some point the tracker gets activated,
# if it is not the case by default.
$to_update = ''; $upd_list = [];

# Build the notification list.
$res_admins = db_execute ("
  SELECT user.user_name FROM user, user_group
  WHERE
    user.user_id = user_group.user_id AND user_group.group_id = ?
    AND user_group.admin_flags = 'A'", [$group_id]
);
if (db_numrows ($res_admins) > 0)
  {
    $admin_list = '';
    while ($row_admins = db_fetch_array ($res_admins))
      $admin_list .= ($admin_list? ', ': '') . $row_admins['user_name'];

    $to_update = ["news", "support", "task", "bugs", "patch", "cookbook"];

    foreach ($to_update as $field)
      {
        $upd_list["new_{$field}_address"] = $admin_list;
        if ($field != "news")
          $upd_list["send_all_$field"] = $value;
      }
  }
if ($upd_list)
  {
    # Strip the excess comma at the end of the update field list.
    $result = db_affected_rows (db_autoexecute ('groups', $upd_list,
      DB_AUTOQUERY_UPDATE, "group_id = ?", [$group_id])
    );

    if ($result)
      {
        fb_dbsuccess ();
        group_add_history ('Set Mail Notification to a sensible default',
          user_getname ($user_id), $group_id
        );
      }
    else
      fb (no_i18n("No field to update or SQL error"));
  }

# Send email and do site specific triggered stuff that comes along.
send_new_project_email ($group_id);
fb (no_i18n ("Mail sent, site-specific triggers executed"));

if ($sys_group_id != $group_id)
  {
    site_admin_header (['title' => no_i18n ("Group Creation Trigger")]);
    site_admin_footer ([]);
    exit;
  }
site_header (['title' => no_i18n ("Local Administration Group Approved")]);
site_footer ([]);
?>
