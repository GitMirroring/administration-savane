<?php
# Functions related to trackers configuration
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
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

# This page should store function related to trackers configuration
# (some of these are in general/data and should be moved here)

# Copy for a given tracker the configuration of the tracker of another
# group. This action is irreversible and can alter in an incoherent way
# already posted items: it is supposed to be mainly used to configure a
# new tracker. It can be used to keep several group using a coherent
# configuration but it should not be used a trackers will divergeant
# configuration already being used.
#
# To ease development, we ll make simple SQL query and we ll parse the
# result. We ll be doing dumb code, code that we ll be able to debug.
# (you need to be smarter than the code to be able to debug it, so lets avoid
# writing the smartest code, so we still have a chance)
namespace trackers_conf {
function artifact_name_prefixed ($artifact)
{
  $trackers = [
    # TRANSLATORS: this string (after removing '[artifact]')
    # is used in context of "%s tracker".
    'bugs' => _('[artifact]bug'), 'patch' => _('[artifact]patch'),
    'task' => _('[artifact]task'), 'cookbook' => _('[artifact]cookbook'),
    'support' => _('[artifact]support'), 'news' => _('[artifact]news')
  ];
  if (array_key_exists ($artifact, $trackers))
    return $trackers[$artifact];
  return $artifact;
}

function artifact_name ($artifact)
{
  $name = artifact_name_prefixed ($artifact);
  $pos = strpos ($name, ']');
  if ($pos === false)
    return $name;
  return substr ($name, $pos + 1);
}

function print_no ($id)
{
  # TRANSLATORS: the argument is id (a number).
  return sprintf (_("#%s"), $id) . " ";
}
function enumerate_items ($result, $artifact_key, $group_id, $field, $field_idx)
{
  $z = 0;
  $ret = '';

  while ($row = db_fetch_array ($result))
    {
      $res = db_createinsertinto (
        $result, $artifact_key, $z++, $field, "group_id", $group_id
      );

      if (db_affected_rows ($res))
        $ret .= print_no ($row[$field_idx]);
    }
  return $ret;
}
function conf_form ($group_id, $artifact, $result)
{
  $vals = $texts = [];
  while ($row = db_fetch_array ($result))
    {
      $vals[] = $row['group_id'];
      $texts[] = $row['group_name'];
    }
  print '<p>';
  # TRANSLATORS: the argument is previously defined string (bug|patch|...)
  printf (_("You can copy the configuration of the %s tracker "
    . "of the following groups (this list was established according to your "
    . "currently membership record)."),
    artifact_name ($artifact)
  );
  print"</p>\n<p class='warn'>"
    . _("Beware, your current configuration will be irremediably lost.")
    . "</p>\n" . form_tag ()
    . form_hidden (['group_id' => $group_id, 'artifact' => $artifact])
    . '<span class="preinput">' . html_label ("from_group_id", _("Groups:"))
    . "</span>&nbsp;&nbsp;&nbsp;\n";
  print html_build_select_box_from_arrays ($vals, $texts, 'from_group_id');
  print form_footer ();
}
function cp_notif_settings ($group_id, $artifact, $from_group_id)
{
  $result = db_execute (
    "SELECT * FROM groups WHERE group_id = ?", [$from_group_id]
  );
  $r = db_fetch_array ($result);
  $res = db_autoexecute ('groups',
    [
      "new_{$artifact}_address" => $r["new_{$artifact}_address"],
      "{$artifact}_glnotif" => $r["{$artifact}_glnotif"],
      "send_all_{$artifact}" => $r["send_all_{$artifact}"],
      "{$artifact}_private_exclude_address"
        => $r["{$artifact}_private_exclude_address"]
    ],
    DB_AUTOQUERY_UPDATE, "group_id = ?", [$group_id]
  );

  if (db_affected_rows ($res))
    fb (_("Notification settings copied"));
}
function delete_from_table ($tbl, $conditions, $msg = null)
{
  if (!is_array ($conditions))
    $conditions = ['group_id' => $conditions];
  $wheres = $params = [];
  foreach ($conditions as $k => $v)
    {
      $wheres[] = "$k = ?";
      $params[] = $v;
    }
  $wheres = join (" AND ", $wheres);
  $res = db_execute ("DELETE FROM $tbl WHERE $wheres", $params);
  if (db_affected_rows ($res) && $msg !== null)
    fb (_($msg));
}
function rm_field_values ($artifact, $group_id)
{
  delete_from_table (
    "{$artifact}_field_value", $group_id,
    _("Previous field values deleted")
  );
}
function rm_field_usages ($artifact, $group_id)
{
  delete_from_table (
    "{$artifact}_field_usage", $group_id,
    _("Previous field usage deleted")
  );
}
function rm_canned_responses ($artifact, $group_id)
{
  delete_from_table ("{$artifact}_canned_responses", $group_id,
    _("Previous canned responses deleted")
  );
}
function rm_ids ($result, $tbl, $field)
{
  $ids = [];
  while ($row = db_fetch_array ($result))
    $ids[] = $row[$field];
  if (empty ($ids))
    return;
  db_execute (
    "DELETE FROM $tbl WHERE $field " . utils_in_placeholders ($ids), $ids
  );
}
function rm_reports ($artifact, $group_id)
{
  $result = db_execute (
    "SELECT * FROM {$artifact}_report WHERE group_id = ?", [$group_id]
  );
  rm_ids ($result, "{$artifact}_report_field", 'report_id');
  delete_from_table ("{$artifact}_report", $group_id,
    _("Previous query forms deleted")
  );
}
function rm_transitions ($artifact, $group_id)
{
  $result = db_execute ("
    SELECT * FROM trackers_field_transition WHERE group_id = ? AND artifact = ?
    ", [$group_id, $artifact]
  );
  $tbl = "trackers_field_transition_other_field_update";
  rm_ids ($result, $tbl, 'transition_id');
  delete_from_table ("trackers_field_transition",
    ['artifact' => $artifact, 'group_id' => $group_id],
    _("Previous field transitions deleted")
  );
}
function cp_field_usages ($artifact, $group_id, $from_group_id)
{
  $result = db_execute (
    "SELECT * FROM {$artifact}_field_usage WHERE group_id = ?",
    [$from_group_id]
  );
  $items = enumerate_items (
    $result, "{$artifact}_field_usage", $group_id, 'none', 'bug_field_id'
  );
  if (!$items)
    return;
  # TRANSLATORS: the argument is space-separated list of field ids.
  fb (sprintf (_("Field usages %s copied"), $items));
}
function cp_field_values ($artifact, $group_id, $from_group_id)
{
  $result = db_execute (
    "SELECT * FROM {$artifact}_field_value WHERE group_id = ?", [$from_group_id]
  );
  $items = enumerate_items (
    $result, "{$artifact}_field_value", $group_id, 'bug_fv_id', 'bug_fv_id'
  );
  if (!$items)
    return;
  # TRANSLATORS: the argument is space-separated list of value ids.
  fb (sprintf (_("Field values %s copied"), $items));
}
function cp_canned_responses ($artifact, $group_id, $from_group_id)
{
  $result = db_execute (
    "SELECT * FROM {$artifact}_canned_responses WHERE group_id = ?",
    [$from_group_id]
  );
  $items = enumerate_items (
    $result, "{$artifact}_canned_responses", $group_id, 'bug_canned_id',
    'bug_canned_id'
  );
  if (!$items)
    return;
  # TRANSLATORS: the argument is space-separated list of response ids.
  fb (sprintf (_("Canned responses %s copied"), $items));
}
function cp_next_report ($artifact, $group_id, $result, $z, &$items)
{
  $row = db_fetch_array ($result);
  if (!$row)
    return false;
  $res = db_createinsertinto (
    $result, "{$artifact}_report", $z, "report_id", "group_id", $group_id
  );
  $new_id = db_insertid ($res);
  if (!$new_id)
    return true;
  $id = $row['report_id'];
  $items .= print_no ($id);
  $res = db_execute (
    "SELECT * FROM {$artifact}_report_field WHERE report_id = ?", [$id]
  );
  $y = 0;
  while (db_fetch_array ($res))
    db_createinsertinto (
      $res, "{$artifact}_report_field", $y++, "none", "report_id",
      $new_id
    );
  return true;
}
function cp_reports ($artifact, $group_id, $from_group_id)
{
  $result = db_execute (
    "SELECT * FROM {$artifact}_report WHERE group_id = ?", [$from_group_id]
  );
  $z = 0;
  $items = '';
  while (cp_next_report ($artifact, $group_id, $result, $z++, $items))
    ; # empty cycle body
  if (!$items)
    return;
  # TRANSLATORS: the argument is space-separated list of report ids.
  fb (sprintf (_("Query forms %s copied"), $items));
}
function cp_next_transition ($artfact, $group_id, $result, $z, &$items)
{
  $row = db_fetch_array ($result);
  if (!$row)
    return false;
  $id = $row['transition_id'];
  $res = db_createinsertinto (
    $result, "trackers_field_transition", $z, "transition_id",
    "group_id", $group_id
  );
  $new_id = db_insertid ($res);
  if (!$new_id)
    return true;
  $items .= print_no ($id);
  $tbl = 'trackers_field_transition_other_field_update';
  $res = db_execute ("SELECT * FROM $tbl WHERE transition_id = ?", [$id]);
  $y = 0;
  while (db_fetch_array ($res))
    db_createinsertinto (
      $res, $tbl, $y++, "other_field_update_id", "report_id", $new_id
    );
  return true;
}
function cp_transitions ($artifact, $group_id, $from_group_id)
{
  $result =
    db_execute ("
      SELECT * FROM trackers_field_transition
      WHERE artifact = ? AND group_id = ?", [$artifact, $from_group_id]
    );
  $z = 0;
  $items = '';
  while (cp_next_transition ($artifact, $group_id, $result, $z++, $items))
    ; # empty cycle body
  if (!$items)
    return;
  # TRANSLATORS: the argument is space-separated list of transition ids.
  fb (sprintf (_("Transitions %s copied"), $items));
}
function cp_entity ($e, $artifact, $gid, $from_gid)
{
  call_user_func ("\\trackers_conf\\rm_$e", $artifact, $gid);
  call_user_func ("\\trackers_conf\\cp_$e", $artifact, $gid, $from_gid);
}
function conf_copy ($group_id, $artifact, $from_group_id)
{
  # TRANSLATORS: the first argument is group id (a number),
  # the second argument is previously defined string (bug|patch|task|...)
  $msg = sprintf (_('Start copying configuration of group #%1$s %2$s tracker'),
    $from_group_id, artifact_name ($artifact)
  );
  fb ($msg);
  cp_notif_settings ($from_group_id, $artifact, $from_group_id);
  $entities = [
    'field_values', 'field_usages', 'canned_responses', 'reports',
    'transitions'
  ];
  foreach ($entities as $e)
    cp_entity ($e, $artifact, $group_id, $from_group_id);
  fb (_("Configuration copy finished"));
}
} # namespace trackers_conf

namespace {
function trackers_conf_copy ($group_id, $artifact, $from_group_id)
{
  trackers_conf\conf_copy ($group_id, $artifact, $from_group_id);
}

function trackers_conf_form ($group_id, $artifact)
{
  $result = db_execute ("
    SELECT groups.group_name,groups.group_id FROM groups, user_group
    WHERE
      groups.group_id = user_group.group_id AND user_group.user_id = ?
      AND groups.status = 'A' AND groups.use_{$artifact} = '1'",
     [user_getid ()]
  );
  if (!db_numrows ($result))
    {
      print '<p>';
      # TRANSLATORS: the argument is previously defined string
      # (bug|patch|task|...)
      printf (_("You cannot copy the configuration of other "
        . "groups because you are not member of any other group "
        . "that uses a %s tracker."),
        trackers_conf\artifact_name ($artifact)
      );
      print "</p>\n";
      return;
    }
  trackers_conf\conf_form ($group_id, $artifact, $result);
}
} # namespace {
?>
