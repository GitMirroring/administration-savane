<?php
# Handle bookmarks.
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
require_once ('../include/init.php');
require_once ('../include/sane.php');
require_once ('../include/html.php');
require_once ('../include/form.php');
require_once ('../include/my/bookmarks.php');

site_user_header (['context' => 'bookmark']);
extract (sane_import ('get', ['true' => 'add', 'digits' => 'delete']));
extract (sane_import ('request',
  [
    'digits' => 'edit', 'pass' => ['url', 'title']
  ]));
extract (sane_import ('post', ['true' => 'update']));
form_check ('update');

if ($add && $url)
  bookmark_add ($url, $title);
if ($delete)
  bookmark_delete ($delete);
bookmark_edit ($edit, $url, $title);
$result = db_execute ("
  SELECT bookmark_url, bookmark_title, bookmark_id FROM user_bookmarks
  WHERE user_id = ? ORDER BY bookmark_title",
  [user_getid ()]
);
$rows = db_numrows ($result);
if (!$rows)
  {
    print _("There is no bookmark saved");
    site_user_footer ([]);
    exit (0);
  }
print "<br />\n";
print $HTML->box_top (_("Saved Bookmarks"), '', 1);
print "\n<ul>\n";
$i = 0;
while ($row = db_fetch_array ($result))
  {
    $url = utils_specialchars ($row['bookmark_url']);
    $title = utils_specialchars ($row['bookmark_title']);
    $bm_id = $row['bookmark_id'];
    print '<li class="' . utils_altrow ($i++) . '">';
    print "<span class='trash'><a href=\"?edit=$bm_id\">"
      . html_image ('misc/edit.png', ['alt' => _("Edit this bookmark")])
      . "</a> <a href=\"?delete=$bm_id\">"
      . html_image_trash (['alt' => _("Delete this bookmark")])
      . "</a></span>\n";
    print "<a href=\"$url\">$title</a><br />\n";
    print "<span class='smaller'>$url</span></li>\n";
  }
print "</ul>\n";
print $HTML->box_bottom (1);
site_user_footer ([]);
?>
