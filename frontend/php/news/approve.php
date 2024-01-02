<?php
# News management.
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
require_once ('../include/sendmail.php');
require_once ('../include/news/general.php');

extract (sane_import ('all',
  [
    'digits' => ['id', 'status'],
    'strings' => [['status', ['0', '4', '5']]],
    'true' => ['update', 'post_changes', 'approve'],
    'specialchars' => ['summary', 'details'],
  ]
));

if (!($group_id && member_check (0, $group_id, 'N3')))
  exit_error (_("Action unavailable: only news managers can approve news."));

if ($post_changes && $approve)
  news_update_news_item (
    ['id' => $group_id, 'name' => $group],
    [ 'id' => $id, 'status' => $status, 'summary' => $summary,
      'details' => $details]
  );
if ($post_changes)
  $approve = null;

site_project_header (
  ['title' => _("Manage"), 'group' => $group_id, 'context' => 'news']
);

if ($approve)
  {
    $result = db_execute ("
      SELECT * FROM news_bytes WHERE id = ? AND group_id = ?", [$id, $group_id]
    );
    if (db_numrows ($result))
      news_print_approve_form (db_fetch_array ($result));
    else
      fb (_("No news item found"), 1);
  }
else
  news_list_news_to_manage ($group_id, $group);
site_project_footer ([]);
?>
