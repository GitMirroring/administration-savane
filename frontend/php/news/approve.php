<?php
# News management.
#
# Copyright (C) 1999-2000  The SourceForge Crew
# Copyright (C) 2002-2006  Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017, 2018, 2023 Ineiev
#
# This file is part of Savane.
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

require_once ('../include/init.php');
require_once ('../include/sendmail.php');
require_once ('../include/news/general.php');

extract (sane_import ('all',
  [
    'digits' => ['id', 'status'],
    'strings' => [['status', ['0', '4', '5']]],
    'hash' => 'form_id',
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
