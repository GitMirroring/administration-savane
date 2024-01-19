<?php
# Manage bookmarks
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

function bookmark_add ($bookmark_url, $bookmark_title = null)
{
  if (empty ($bookmark_title))
    $bookmark_title = $bookmark_url;

  $result = db_autoexecute ('user_bookmarks',
    [ 'user_id' => user_getid (), 'bookmark_url' => $bookmark_url,
      'bookmark_title' => $bookmark_title],
    DB_AUTOQUERY_INSERT
  );
  if (!$result)
    print db_error ();
}

function bookmark_update ($bookmark_id, $bookmark_url, $bookmark_title)
{
  db_autoexecute('user_bookmarks',
    ['bookmark_url' => $bookmark_url, 'bookmark_title' => $bookmark_title],
    DB_AUTOQUERY_UPDATE, "bookmark_id = ? AND user_id = ?",
    [$bookmark_id, user_getid ()]
  );
}

function bookmark_delete ($bookmark_id)
{
  db_execute (
    "DELETE from user_bookmarks WHERE bookmark_id = ? AND user_id = ?",
    [$bookmark_id, user_getid ()]
  );
}

function bookmark_edit_form ($bm_id, $result)
{
  $title = db_result ($result, 0, 'bookmark_title');
  $url = db_result ($result, 0, 'bookmark_url');

  print form_tag ();
  print '<span class="preinput">' . _("Title:") . "</span><br />\n";
  print '&nbsp;&nbsp;&nbsp;'
    . form_input ('text', 'title', $title, 'size="50"');
  print "<br />\n<span class='preinput'>"
    . html_label ('url', _("Address:")) . "</span><br />\n&nbsp;&nbsp;&nbsp;";
  print form_input ('text', "url",  $url, 'size="50"') . "\n";
  print form_hidden (['edit' => $bm_id]) . "\n<p>";
  print form_submit (_("Update"), 'update', false, true) . "</p>\n";
  print "</form>\n";
}

function bookmark_edit ($edit, $url, $title)
{
  if (!$edit)
    return;
  if ($url && $title)
    {
      # The URL and title were in the request, we update the database.
      bookmark_update ($edit, $url, $title);
      return;
    }
  $result = db_execute ("
    SELECT * from user_bookmarks WHERE bookmark_id = ? AND user_id = ?",
    [$edit, user_getid ()]
  );
  if (!$result)
    {
      fb (_("Bookmark not found"), 1);
      return;
    }
  bookmark_edit_form ($edit, $result);
}
?>
