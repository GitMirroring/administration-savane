<?php
# Search within current group.
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

site_project_header(array('group'=>$group_id,
                          'context'=>'searchingroup'));

# Ideally, we would like to do a real site wide search, which mean being able
# to do a search without selecting a given tracker.
# To do that, we must run a SQL command per tracker, put everything in an
# array. Fine, but the current function returns SQL results, and we shan't
# change that right now. So we ask the user to provide input.
# But it has to be kept in mind that we want this page to allow a search
# over all trackers, in the end.

# TRANSLATORS: the argument is the name of the group to look within.
print '<p>'.sprintf(_("With the following form, you can perform a search in the
item summaries and details of a given tracker of the project %s. If you need
to perform more complex search, use the query forms in Browse items page of
this tracker. If you want to perform a site-wide search, use the search box in
the left menu."), group_getname($group_id)).'</p>
';
# Use the string alltrackers to say we want a search to be performed over
# all of them
# (this must not be in a boxoption, it is no option, it is the only purpose of
# the page).
print '<p>'.search_box('', '', 45).'</p>
';

site_project_footer(array());
?>
