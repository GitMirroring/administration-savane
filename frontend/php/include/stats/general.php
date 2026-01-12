<?php
# Get statistics.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2026 Ineiev
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

function stats_get_generic ($query, $params = [])
{
  $res = db_execute ($query, $params);
  if (db_numrows ($res) <= 0)
    return _("Error");
  $row = db_fetch_array ($res);
  return $row['count'];
}

function stats_count_by_type ($stats, $type_ids)
{
  if ($type_ids === null)
    $type_ids = array_keys ($stats);
  if (!is_array ($type_ids))
    $type_ids = [$type_ids];
  $cnt = 0;
  foreach ($type_ids as $type)
    if (!empty ($stats[$type]))
      $cnt += $stats[$type];
  return $cnt;
}

function stats_get_active_groups ($type_id = null)
{
  static $stats = [];
  if (!empty ($stats))
    return stats_count_by_type ($stats, $type_id);
  $res = db_execute ("
    SELECT type, count(*) AS count FROM groups
    WHERE status = '" . GROUP_STATUS_ACTIVE . "'
    GROUP BY type"
  );
  while ($row = db_fetch_array ($res))
    $stats[$row['type']] = $row['count'];
  return stats_count_by_type ($stats, $type_id);
}

function stats_get_pending_groups ()
{
  return stats_get_generic (
    "SELECT count(*) AS count FROM groups
     WHERE status = '" . GROUP_STATUS_PENDING . "'"
  );
}

function stats_getprojects_total ()
{
  return stats_getprojects ();
}

function stats_getprojects ($type_id = "", $is_public = "", $period = "")
{
  $params = [];
  $sql = '';
  if ($type_id)
    {
      $sql = " AND type = ?";
      $params[] = $type_id;
    }
  if ($is_public != "")
    {
      $sql .= " AND is_public = ?";
      $params[] = $is_public;
    }
  if ($period)
    $sql .= " AND $period";

  return stats_get_generic (
    "SELECT count(*) AS count FROM groups
     WHERE status = '" . GROUP_STATUS_ACTIVE . "' $sql", $params
  );
}

function stats_getusers ($period = "")
{
  $sql = '';
  if ($period)
    $sql = " AND $period";

  return stats_get_generic (
    "SELECT count(*) AS count FROM user
     WHERE status = '" . USER_STATUS_ACTIVE . "' $sql"
  );
}

function stats_getitems ($tracker, $only_open = "", $period = "")
{
  $params = [GROUP_NONE];
  $sql = '';
  if ($only_open)
    {
      $sql = " AND status_id = ?";
      $params[] = $only_open;
    }

  if ($period)
    $sql .= " AND $period";

  return stats_get_generic ("
      SELECT count(*) AS count FROM $tracker
      WHERE group_id <> ? AND spamscore < 5 $sql",
      $params
  );
}

function stats_getthemeusers ($theme = "")
{
  return stats_get_generic ("
      SELECT count(*) AS count FROM user
      WHERE status = ? AND theme = ?", [USER_STATUS_ACTIVE, $theme]
  );
}
?>
