<?php
# Get statistics.
#
# Copyright (C) 2004 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2004 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2017, 2023 Ineiev
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

function stats_get_generic ($query, $params = [])
{
  $res = db_execute ($query, $params);
  if (db_numrows ($res) <= 0)
    return _("Error");
  $row = db_fetch_array ($res);
  return $row['count'];
}

function stats_getprojects_active ($type_id = "")
{
  return stats_getprojects ($type_id);
}

function stats_getprojects_bytype_active ($type_id)
{
  return stats_getprojects_active ($type_id);
}

function stats_getprojects_pending ()
{
  return stats_get_generic (
    "SELECT count(*) AS count FROM groups WHERE status = 'P'"
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
    "SELECT count(*) AS count FROM groups WHERE status='A' $sql", $params
  );
}

function stats_getusers ($period = "")
{
  $sql = '';
  if ($period)
    $sql = " AND $period";

  return stats_get_generic (
    "SELECT count(*) AS count FROM user WHERE status = 'A' $sql"
  );
}

function stats_getitems ($tracker, $only_open = "", $period = "")
{
  $params = [];
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
      WHERE group_id <> '100' AND spamscore < 5 $sql",
      $params
  );
}

function stats_getthemeusers ($theme = "")
{
  return stats_get_generic ("
      SELECT count(*) AS count FROM user
      WHERE status = 'A' AND theme = ?", [$theme]
  );
}
?>
