<?php
# Edit notifications.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2001, 2002 Laurent Julliard, CodeX Team, Xerox
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

require_once ('../../include/init.php');
require_once ('../../include/trackers/general.php');
require_once ('../../include/trackers/data.php');
require_directory ('project');

$is_admin_page = 'y';

extract (sane_import ('post', ['true' => 'submit']));

if (!$group_id)
  exit_no_group ();

if (!user_isloggedin ())
  # Must be at least logged in to set up your personal notification
  # preference.
  exit_permission_denied ();

$is_user_a_member = user_ismember ($group_id);

# Set up some data structure needed throughout the script.
$user_id = user_getid ();
# Get notification roles.
$res_roles = trackers_data_get_notification_roles ();
$num_roles = db_numrows ($res_roles);

for ($i = 0; $arr = db_fetch_array ($res_roles); $i++)
  $arr_roles[$i] = $arr;

# Get notification events.
$res_events = trackers_data_get_notification_events ();
$num_events = db_numrows ($res_events);

for ($i = 0; $arr = db_fetch_array ($res_events); $i++)
  $arr_events[$i] = $arr;

# Build the default notif settings: all 'yes'.
for ($i = 0; $i < $num_roles; $i++)
  {
    $role_id = $arr_roles[$i]['role_id'];
    for ($j = 0; $j < $num_events; $j++)
      {
        $event_id = $arr_events[$j]['event_id'];
        $arr_notif[$role_id][$event_id] = 1;
      }
  }

# Overwrite with user settings if any.
$res_notif = trackers_data_get_notification ($user_id);
while ($arr = db_fetch_array ($res_notif))
  $arr_notif[$arr['role_id']][$arr['event_id']] = $arr['notify'];

# The form has been submitted - update the database.
if ($submit)
  {
    $res_new = trackers_data_post_notification_settings ($group_id, ARTIFACT);

    if ($res_new == 1)
      {
        fb (_("Changed notification email settings"));
        group_add_history (
          'Changed Notification Email Settings', '', $group_id
        );
      }
    else
      fb (_("Update failed"));
  }

trackers_header_admin (['title' => _("Set Notifications")]);

print form_tag () . form_hidden (["group_id" => $group_id]);

if (user_ismember ($group_id, 'A'))
  trackers_data_show_notification_settings ($group_id, ARTIFACT, 1);

print "\n<p align='center'><input type='submit' name='submit' class='bold' "
  . 'value="' . _("Submit Changes") . "\" />\n</form>\n";

trackers_footer ([]);
?>
