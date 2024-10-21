<?php
# Display resume.
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

require_once('../include/init.php');
require_once('../include/people/general.php');

extract (sane_import ('get', ['digits' => 'user_id']));

exit_if_missing ('user_id');
$user_arr = user_get_array ($user_id);

if (empty ($user_arr))
  exit_user_not_found ($user_id);

if ($user_arr['people_view_skills'] != 1)
  exit_error (_("Resume & Skills page for this user is disabled"));

$user_status = $user_arr['status'];

if (($user_status == 'D' || $user_status == 'S') && !user_is_super_user())
  exit_error (_("This account was deleted."));

# TRANSLATORS: the argument is user's name.
$title = sprintf (_("%s Resume & Skills"), $user_arr['realname']);
site_header (['title' => $title, 'context' => 'people']);

$link = utils_user_link ($user_arr['user_name'], $user_arr['realname']);
print "<p>";
# TRANSLATORS: the argument is user's name.
printf (_("Follows Resume & Skills of %s."), $link);
print "</p>\n";

utils_get_content ("people/viewprofile");

$resume = $user_arr['people_resume'];
if ($resume != '')
  {
    print html_h (2, _("Resume"));
    print markup_full (utils_specialchars ($resume));
  }
print html_h (2, _("Skills"));
print people_show_skill_inventory ($user_id);

site_footer ([]);
?>
