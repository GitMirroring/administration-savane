<?php
# Front page - news, latests groups, etc.
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

require_once ('include/init.php');
require_directory ("people");
require_directory ("news");
require_directory ("stats");
require_once ('include/features_boxes.php');

$HTML->header (['title' => _("Welcome"), 'notopmenu' => 1]);
html_feedback_top ();

print "<div class='indexright'>\n";
print show_features_boxes ();
print "</div><!-- end indexright -->\n";
print '<div class="indexcenter">';
utils_get_content ("homepage");
print "\n<p>&nbsp;</p>\n";
print $HTML->box_top ("<a href=\"{$sys_home}news/\" class='sortbutton'>"
  . _("Latest News") . '</a>'
);
print news_show_latest ($sys_group_id);
print $HTML->box_bottom ();

print "</div><!-- end indexcenter -->\n";

$HTML->footer ([]);
?>
