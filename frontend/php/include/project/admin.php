<?php
# Group administration.
# 
# Copyright (C) 1999, 2000 The SourceForge Crew
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


# Nicely html-formatted output of this group's audit trail

function show_grouphistory ($group_id)
{
  # Show the group_history rows that are relevant to this group_id.
  $result = group_get_history($group_id);
  $rows = db_numrows($result);

  if ($rows <= 0)
    {
      print '<h2>'
        . _("No Changes Have Been Made to This Group") . "</h2>\n";
      return;
    }
  print "\n<h2>" . _("Group Change History") . "</h2>\n<p>";
  $title_arr = [];
  $title_arr[] = _("Field");
  $title_arr[] = _("Value");
  $title_arr[] = _("Date");
  $title_arr[] = _("Author");

  print html_build_list_table_top ($title_arr);

  for ($i = 0; $i < $rows; $i++)
    {
      $field = db_result ($result, $i, 'field_name');
      print "\n\t<tr class=\"" . html_get_alt_row_color ($i)
        . "\"><td>$field</td>\n<td>";
      $str = db_result ($result, $i, 'old_value');
      if ($field == 'removed user')
        $str = user_getname ($str);
      print "$str</td>\n<td>"
        . utils_format_date (db_result ($result, $i, 'date')) . "</td>\n<td>"
        . db_result ($result, $i, 'user_name') . "</td></tr>\n";
    }
  print "\n</table>\n";
}

function project_admin_registration_info ($row_grp)
{
  $res_admin = db_execute ("
    SELECT
      user.user_id AS user_id, user.user_name AS user_name,
      user.realname AS realname, user.email AS email
    FROM user, user_group
    WHERE user_group.user_id = user.user_id
    AND user_group.group_id = ? AND
    user_group.admin_flags = 'A'",
    [$row_grp['group_id']]
  );

  $spp = "<p><span class='preinput'>'";
  $spbr = "</span><br />\n ";
  print $spp . _("Group Admins") . ":$spbr";
  while ($row_admin = db_fetch_array ($res_admin))
    {
      print "<a href=\"{$GLOBALS['sys_home']}"
        . "users/{$row_admin['user_name']}/\">{$row_admin['realname']} "
        . "&lt;{$row_admin['email']}&gt;</a> ; ";
    }
  $spp = "</p>\n$spp";
  print $spp . _("Registration Date") . ":$spbr"
    . utils_format_date ($row_grp['register_time']);
  print $spp . _("System Group Name:") . $spbr . $row_grp['unix_group_name'];
  print $spp . _("Submitted Description:") . $spbr
    . markup_full($row_grp['register_purpose']);
  print $spp . _("Required software:") . $spbr
    . markup_full($row_grp['required_software']);
  print $spp . _("Other comments:") . $spbr
    . markup_full($row_grp['other_comments']);
  print "</p>\n<p>";
  print utils_registration_history ($row_grp['unix_group_name']);
  print "</p>\n";
}
?>
