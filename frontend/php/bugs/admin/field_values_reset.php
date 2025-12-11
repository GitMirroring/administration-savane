<?php
# Reset field values.
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
require_once ('../../include/trackers/general.php');
extract (sane_import ('post', ['name' => 'field', 'true' => ['confirm', 'cancel']]));
# This page is only accessible with POST; check form_id unconditionally.
form_check ();
user_check_group_admin ();
trackers_init ($group_id);

if (!$field)
  exit_missing_param ();

$redraw_url = $sys_home . ARTIFACT
  . "/admin/field_values.php?group_id=$group_id&field=$field&list_value=1";

if ($cancel)
  session_redirect ($redraw_url);
if ($confirm)
  {
    db_execute ("
      DELETE FROM " . ARTIFACT . "_field_value
      WHERE group_id = ? AND bug_field_id = ?",
      [$group_id, trackers_data_get_field_id ($field)]
    );
    session_redirect ($redraw_url);
    exit (0);
  }
$hdr = sprintf (_("Reset Values of '%s'"), trackers_data_get_label ($field));
trackers_header_admin (['title' => $hdr]);

print form_tag ();
print form_hidden (["group_id" => $group_id, "field" => $field]);
print '<span class="preinput">';
printf (
  _("You are about to reset values of the field %s.\n"
    . "This action will not be undoable, please confirm:"),
  trackers_data_get_label ($field)
);
print '</span>';
print form_confirm_cancel () . "</form>\n";
?>
