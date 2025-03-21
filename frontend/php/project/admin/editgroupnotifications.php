<?php
# Edit notifications.
#
#  Copyright (C) 1999, 2000 The SourceForge Crew
#  Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
#  Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
#  Copyright (C) 2014, 2016, 2017 Assaf Gordon
#  Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
#  Copyright (C) 2013, 2014, 2017-2025 Ineiev
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
require_once ('../../include/form.php');
require_once ('../../include/vars.php');

require_directory ("trackers");
session_require (['group' => $group_id, 'admin_flags' => MEMBER_FLAGS_ADMIN]);

extract (sane_import ('post',
  [
    'true' => 'update', 'pass' => 'form_news_address',
    'digits' => [['form_frequency', [0, 3]]],
  ]
));
form_check ('update');

if (empty ($form_news_address))
  $form_news_address = '';

$artifacts = [
  'bugs' => _("Bug Tracker Email Notification Settings"),
  'support' => _("Support Tracker Email Notification Settings"),
  'task' => _("Task Tracker Email Notification Settings"),
  'patch' => _("Patch Tracker Email Notification Settings"),
  'cookbook' => _("Cookbook Manager Email Notification Settings"),
];

if ($update)
  {
    group_add_history ('Changed Group Notification Settings', '', $group_id);
    foreach ($artifacts as $art => $label)
      trackers_data_post_notification_settings ($group_id, $art);
    db_execute (
      "UPDATE groups SET new_news_address = ? WHERE group_id = ?",
      [$form_news_address, $group_id]
    );
  }

$res_grp = db_execute ("SELECT * FROM groups WHERE group_id = ?", [$group_id]);
if (db_numrows ($res_grp) < 1)
  exit_no_group ();
$row_grp = db_fetch_array ($res_grp);

site_project_header (
  ['title' => _("Set Notifications"),'group' => $group_id, 'context' => 'ahome']
);
print form_tag () . form_hidden (['group_id' => $group_id]);

foreach ($artifacts as $art => $label)
  {
    print html_h (2, $label);
    trackers_data_show_notification_settings ($group_id, $art);
    print "<br />\n";
  }
print html_h (2, _("News Manager Email Notification Settings"));
print '<span class="preinput">' . _("Carbon-Copy List:")
  . "</span><br />\n&nbsp;&nbsp;"
  . form_input ('text', 'form_news_address', $row_grp['new_news_address'],
      "size='40' maxlength='255'")
  . "<br /><br />\n";
print form_footer (_("Update"));
site_project_footer ([]);
?>
