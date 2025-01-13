<?php
# Edit news CC list.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2025 Ineiev
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
require_once ('../../include/sane.php');
require_once ('../../include/form.php');

extract (sane_import ('post',
  [
    'true' => 'update',
    'preg' => [['form_news_address', '/^[-+_@.,;\s\da-zA-Z]*$/']],
  ]
));
form_check ('update');
if (!$group_id)
  exit_no_group ();

if (!user_ismember ($group_id, 'A'))
  exit_permission_denied ();

$grp = project_get_object ($group_id);

if (!$grp->Uses ("news"))
  exit_error (_("Error"), _("This group doesn't post news"));

if ($update)
  {
    db_execute (
      "UPDATE groups SET new_news_address = ? WHERE group_id = ?",
      [$form_news_address, $group_id]
    );
    fb (_("Updated"));
  }
site_project_header (['group' => $group_id, 'context' => 'anews']);

$res_grp = db_execute (
 "SELECT new_news_address FROM groups WHERE group_id = ?", [$group_id]
);
$row_grp = db_fetch_array ($res_grp);

print html_h (2, _("News Tracker Email Notification Settings"))
  . form_tag () . form_hidden (['group_id' => $group_id])
  . '<span class="preinput">'
  . html_label ("form_news_address", _("Carbon-Copy List:"))
  . "</span>\n<br />\n&nbsp;&nbsp;"
  . form_input ('text', 'form_news_address', $row_grp['new_news_address'],
      "size='40' maxlength='255'"
    )
  . "\n" . form_footer (_("Update"));

site_project_footer ();
?>
