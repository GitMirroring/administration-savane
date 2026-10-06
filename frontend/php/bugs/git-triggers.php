<?php
# Close and re-open tracker items on request from Git hooks.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

$GLOBALS['skip_csp_headers'] = 1;

$includes = ['init', 'trackers/data', 'trackers/general', 'savane-git'];

# The messages on this page aren't localized because it's intended
# for scripted usage.

foreach ($includes as $i)
  require_once ("../include/$i.php");

extract (sane_import ('get', [
  'path' =>  ['dir', 'file'], 'preg' => [['verbose', '/^-?\d+$/']],
  'true' => ['disable_notice']
]));

if (empty ($verbose))
  $verbose = 0;

$page = '';
if (empty ($disable_notice))
  $page = git_agpl_notice ('This text is served with Savane.');

function diag ($str, $level = -1)
{
  global $verbose, $page;
  if ($verbose <= $level)
    return;
  $page .= "$str\n";
}

function params_are_incomplete ()
{
  $ret = false;
  foreach (['group', 'dir', 'file'] as $var)
    if (empty ($GLOBALS[$var]))
      {
        diag ("Error: parameter '$var' is missing.");
        $ret = true;
      }
  if ($ret || $GLOBALS['verbose'] > 0)
    diag ("Query string was, '{$_SERVER['QUERY_STRING']}'");
  return $ret;
}

function validate_request ()
{
  global $sys_vcs_dir;
  if (empty ($sys_vcs_dir['git']) || empty ($sys_vcs_dir['git']['dir']))
    {
      diag ('Error: Git directories are not configured at frontend.');
      return true;
    }
  if (params_are_incomplete ())
    return true;
  $vars = [];
  foreach (['group', 'dir', 'file'] as $v)
    $vars[] = "'$v' => '{$GLOBALS[$v]}'";
  diag ("Proceeding with " . join (', ', $vars) . ".", 0);
  return false;
}

function check_repo_name ($name)
{
  if (substr ($name, -4) === '.git')
    return 0;
  diag ("Error: repository name '$name' does not end in '.git'");
  return 1;
}

function check_request_file ($file)
{
  if (is_file ($file))
     return 0;
  diag ("Error: file $file not found.");
  return 1;
}

function get_request_path ()
{
  global $file, $dir, $sys_vcs_dir, $group;
  $path = $sys_vcs_dir['git']['dir'] . "/$dir/$file";
  if (check_request_file ($path))
    return null;
  if ($dir === "$group.git")
    return $path;
  if (substr ($dir, 0, strlen ($group) + 1) === "$group/")
    return $path;
  diag ("Error: directory '$dir' does not match group '$group'.");
  return null;
}

function read_data ($path)
{
  $bugs = file ($path);
  if ($bugs === false)
    diag ("Error: cannot read file $file.");
  return $bugs;
}

function get_item_data ($artifact, $item_id)
{
  $res = db_execute ("
    SELECT `unix_group_name` AS `group`, `a`.`bug_id`, `a`.`status_id`
    FROM `$artifact` `a` JOIN `groups` `g` ON `a`.`group_id` = `g`.`group_id`
    WHERE `bug_id` = ?", [$item_id]
  );
  if (!db_numrows ($res))
    {
      diag ("Error: item '$artifact #$item_id' not found.");
      return null;
    }
  $row = db_fetch_array ($res);
  $row['tracker'] = $artifact;
  return $row;
}

function parse_item_string ($line)
{
  if (preg_match ('/(\S+)\s+#(\d+)(\s(.*?))?\s*$/', $line, $arr))
    {
      if (empty ($arr[4]))
        $arr[4] = '';
      return [$arr[1], $arr[2], $arr[4]];
    }
  diag ("Error: wrong item specification '$line'.");
  if (!in_array ($arr[1], utils_get_dependable_trackers ()))
    {
      diag ("Error: wrong tracker '{$arr[1]}' in string '$line'.");
      return [null, null, null];
    }
  return [null, null, null];
}

function check_for_group ($row, $group, $item, $line)
{
  if ($row['group'] === $group)
    return 0;
  diag ("Error: $item in '$line' does not belong in the group '$group'.");
  return 1;
}

function validate_item ($line, $must_have_comment)
{
  global $group;
  list ($tracker, $item_id, $comment) = parse_item_string ($line);
  if ($tracker === null)
    return null;
  if ($must_have_comment && empty ($comment))
    {
      diag ("Error: comment is empty in '$line'.");
      return null;
    }
  $row = get_item_data ($tracker, $item_id);
  if ($row === null)
    return null;
  if (!empty ($comment))
    $row['comment'] = $comment;
  if (check_for_group ($row, $group, "$tracker #$item_id", $line))
    return null;
  trackers_data_artifact ($tracker);
  return $row;
}

function item_has_status ($data, $status)
{
  if ($data['status_id'] !== $status)
    return 0;
  $msg = [
    '1' => 'The item is already open.', '3' => 'The item is already closed.'
  ];
  $m = "The item already has status $status.";
  if (array_key_exists ($status, $msg))
    $m = $msg[$status];
  diag ($m);
  return 1;
}

function set_status_data ($status, $data)
{
  if ($status === null)
    return [[], []];
  if (item_has_status ($data, $status))
    return [null, null];
  $vfl = ['status_id' => $status];
  $changes = [
    'status_id' => ['del-val' => $data['status_id'], 'add-val' => $status]
  ];
  return [$changes, $vfl];
}

function change_item_status ($line, $msg, $status)
{
  global $group, $group_id, $item_id;
  $data = validate_item ($line, $status === null);
  if ($data === null)
    return 1;
  $item = $data['tracker'] . ' #' . $data['bug_id'];
  diag ("$msg $item in group #$group_id <$group>.");
  list ($changes, $vfl) = set_status_data ($status, $data);
  if ($changes === null)
    return 0;
  $item_id = $data['bug_id'];
  if (!empty ($data['comment']))
    $vfl['comment'] = $data['comment'];
  $address = '';
  trackers_data_get_all_fields ($group_id);
  $changed = trackers_data_handle_update (
    $group_id, $item_id, null, null, null, null, null, $vfl, $changes, $address
  );
  if (!$changed)
    return 0;
  trackers_mail_followup ($item_id, $address, $changes);
  return 0;
}

function close_item ($line)
{
  return change_item_status ($line, 'Closing', '3');
}

function open_item ($line)
{
  return change_item_status ($line, 'Opening', '1');
}

function update_item ($line)
{
  return change_item_status ($line, 'Updating', null);
}

function set_user ($line)
{
  $uid = user_getid ($line);
  if (!$uid)
    {
      diag ("Note: user '$line' not found, defaulting to 'None'.");
      return 0;
    }
  session_setglobals ($uid);
  return 0;
}

$request_map = [
  'sv-user' => 'set_user',
  'closes-item' => 'close_item', 'opens-item' => 'open_item',
  'updates-item' => 'update_item'
];

function parse_line ($line, $path)
{
  static $i;
  $i++;
  if (!preg_match ('/^(\S+):\s(.*)$/', $line, $arr))
    {
      diag ("Error: $path:$i: wrong line '$line'.");
      return null;
    }
  if (array_key_exists ($arr[1], $GLOBALS['request_map']))
    return $arr;
  diag ("$path: Ignoring unknown trigger '{$arr[1]}'.", 0);
  return [1 => null];
}

function process_request ()
{
  $path = get_request_path ();
  if ($path === null)
    return;
  $bugs = file ($path);
  if ($bugs === false)
    return 1;
  foreach ($bugs as $b)
    {
      $arr = parse_line ($b, $path);
      if (null === $arr)
        return 1;
      if ($arr[1] === null)
        continue;
      if (!array_key_exists ($arr[1], $GLOBALS['request_map']))
        return 1;
      if ($GLOBALS['request_map'][$arr[1]] ($arr[2]))
        return 1;
    }
  return 0;
}

if (validate_request ())
  exit_header ();
elseif (process_request ())
  exit_header ();
header ('Content-Type: text/plain; charset=utf-8');
print $page;
?>
