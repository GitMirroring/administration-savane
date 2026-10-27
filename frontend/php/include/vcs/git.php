<?php
# Git-specific functions.
# To be included from php/include/vcs.php only.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
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

define ('HOOK_SEPARATOR', '/./');
define ('HOOK_NON_GREEDY', 'l');

function git_known_triggers ()
{
  return [
    'closes-item', 'opens-item', 'updates-item', 'no-merges', 'check-gpg',
    'mail'
  ];
}

function git_extra_trigger_controls ()
{
  return ['check-gpg--keys', 'mail--address', 'mail--show-diffs'];
}

function git_have_savane_hooks ($path)
{
  return is_dir ("$path/hooks/savane.d");
}

function git_pref_name_pfx ($repo)
{
  return "$repo:hooks:receive:";
}

function git_input_to_pref ($repo, $x)
{
  $pfx = git_pref_name_pfx ($repo);
  return $pfx .  str_replace ('--', ':', $x);
}

function git_pref_to_input ($x)
{
  if (strpos ($x, '--') !== false)
    {
      trigger_error ("Malformed hook pref '$x'.");
      return null;
    }
  return str_replace (':', '--', $x);
}

function git_description_fallback (&$desc, $url)
{
  $default_description =
    "Unnamed repository; edit this file 'description' to name the repository.";
  if (empty ($desc) || $desc == $default_description)
    $desc = $url;
}

function git_get_repo_path ($repo, $repos = null)
{
  $ret = $repo;
  if ($repos !== null)
    foreach ($repos as $r)
      {
        if ($repo !== $r['name'])
          continue;
        $ret = $r;
        break;
      }
  if (is_string ($ret))
    return null;
  return vcs_get_root ('git') . '/' . $ret['url'];
}

function git_known_trigger_hook_prefs ($repo)
{
  $ret = [];
  foreach (git_known_triggers () as $t)
    $ret[] = git_input_to_pref ($repo, $t);
  return $ret;
}

function git_get_external_pref_list ($r)
{
  $repo = $r['name'];
  $ret = [];
  foreach (['pre', 'post'] as $s)
    {
      $ret[] = git_input_to_pref ($repo, "disabled-hooks:$s");
      foreach (git_read_hook_dir ($r, $s) as $h)
        {
          if (strpos ("-$h", '--') === false)
            {
              $ret[] = git_input_to_pref ($repo, "$s-$h:exclude-branches");
              continue;
            }
          trigger_error ("Malformed hook script name ($s) '$h'.");
        }
    }
  return $ret;
}

function git_get_fetched_pref_list ($r)
{
  $repo = $r['name'];
  $hooks = git_known_trigger_hook_prefs ($repo);
  $ret = $hooks;
  foreach ($hooks as $h)
    $ret[] = "$h:exclude-branches";
  $ret = array_merge ($ret, git_get_external_pref_list ($r));
  foreach (git_extra_trigger_controls () as $k)
    $ret[] = git_input_to_pref ($repo, $k);
  return git_drop_nulls ($ret);
}

function git_get_hook_prefs ($r, $group_id)
{
  $repo = $r['name'];
  $pfx = git_pref_name_pfx ($repo);
  $len = strlen ($pfx);
  $prefs = vcs_get_pref ('git', $group_id, git_get_fetched_pref_list ($r));
  $ret = [];
  foreach ($prefs as $k => $v)
    $ret[substr ($k, $len)] = $v;
  return $ret;
}

function git_repo_name_from_path ($path)
{
  $dir = utils_dirname ($path, 5);
  if (is_dir ($dir) . '.git')
    {
      # /srv/git/proba/semel.git/hooks/savane.d/pre-receive.d
      $ret = utils_dirname ($path, 4); # /srv/git/proba/semel.git
      return preg_replace (',.*([^/]+/[^/]+)[.]git$,', '$1', $ret);
    }
  # /srv/git/proba.git
  $ret = basename ($path);
  return substr ($ret, 0, -strlen ('.git'));
}

function git_list_hook_entry ($path, $entry)
{
  if (is_dir ($entry))
    return null;
  if (is_file ($entry) && !is_executable ($entry))
    return null;
  # The symbolic links are trusted because the hooks may be present
  # on the Git server but missing on the frontend.
  $repo = git_repo_name_from_path ($path);
  return basename ($entry);
}

function git_drop_nulls ($x)
{
  return array_filter ($x, function ($x) { return $x !== null; });
}

function git_open_hook_dir ($path)
{
  if (!is_dir ($path))
    {
      trigger_error ("Directory '$path' doesn't exist.");
      return false;
    }
  $dir = opendir ($path);
  if ($dir === false)
    trigger_error ("Cannot opendir '$path'.");
  return $dir;
}

function git_read_hook_dir ($repo, $stage)
{
  static $cache = [];
  $path = git_get_repo_path ($repo);
  if (
    array_key_exists ($stage, $cache)
    && array_key_exists ($path, $cache[$stage])
  )
    return $cache[$stage][$path];
  $hook_dir = "$path/hooks/savane.d/$stage-receive.d";
  if (false === ($dir = git_open_hook_dir ($hook_dir)))
    return [];
  $hooks = [];
  while (false !== ($entry = readdir ($dir)))
    $hooks[] = git_list_hook_entry ($path, "$hook_dir/$entry");
  closedir ($dir);
  $cache[$stage][$path] = git_drop_nulls ($hooks);
  return $cache[$stage][$path];
}

function git_get_disabled_additional_hooks ($prefs, $stage)
{
  $idx = "disabled-hooks:$stage";
  if (empty ($prefs[$idx]))
    return [];
  $ret = [];
  foreach (explode (HOOK_SEPARATOR, $prefs[$idx]) as $hook)
    $ret[$hook] = 1;
  return $ret;
}

function git_extract_unselected_vals ($hook, $prefs)
{
  $idx = "$hook:exclude-branches";
  $ret = [HOOK_SEPARATOR => ''];
  if (!array_key_exists ($idx, $prefs))
    return $ret;
  if (empty ($prefs[$idx]))
    return $ret;
  $br = explode (HOOK_SEPARATOR, $prefs[$idx]);
  if ($br[0] === '')
    {
      array_shift ($br);
      $ret[HOOK_SEPARATOR] = array_shift ($br);
    }
  foreach ($br as $b)
    $ret[$b] = 1;
  return $ret;
}

function git_get_unselected_branches ($r, $hook, $prefs)
{
  $ret = git_extract_unselected_vals ($hook, $prefs);
  $ret[HOOK_SEPARATOR . 'greedy'] =
    false === strpos ($ret[HOOK_SEPARATOR], HOOK_NON_GREEDY);
  return $ret;
}

function git_branch_is_selected ($b, $unselected)
{
  $ret = empty ($unselected[$b]);
  if ($unselected[HOOK_SEPARATOR . 'greedy'])
    return $ret;
  return !$ret;
}

function git_read_description ($dir_name)
{
  # Suppress warnings: when the group is private, the www-data user will be
  # denied access its repositories.
  $error_state = utils_disable_warnings ();
  $desc = file_get_contents ("$dir_name/description");
  utils_restore_warnings ($error_state);
  if ($desc === false)
    return '';
  return trim ($desc);
}

function git_make_entry ($git_dir, $repo_dir, $clone_path)
{
  $dir_name = "$git_dir/$repo_dir";
  if (!is_dir ($dir_name))
    return null;
  $desc = git_read_description ($dir_name);
  git_description_fallback ($desc, $repo_dir);
  $name = preg_replace ('/[.]git$/', "", $repo_dir);
  return ['name' => $name, 'url' => $repo_dir, 'desc' => $desc,
    'path' => "$clone_path/$repo_dir"
  ];
}

function git_get_list_from_cgitrepos ($group_name)
{
  global $sys_etc_dir;
  utils_run_proc (
    [
      'grep', '-A', '3', "^repo\.url=$group_name\(/\|\.git$\)",
      "$sys_etc_dir/cgitrepos"
    ],
    $out, $stderr
  );
  $output = explode ("\n", $out);
  $n = intval ((count ($output) + 1) / 5);
  $ret = [];
  for ($i = 0; $i < $n; $i++)
    {
      $ret[$i] = [
        'url' => preg_replace (':^repo[.]url=:', '', $output[$i * 5]),
        'path' => preg_replace (':repo[.]path=:', '', $output[$i * 5 + 1]),
        'desc' => preg_replace (':^repo[.]desc=:', '', $output[$i * 5 + 2])
      ];
      $ret[$i]['name'] = preg_replace ('/[.]git/', "", $ret[$i]['url']);
      git_description_fallback ($ret[$i]['desc'], $ret[$i]['url']);
    }
  return $ret;
}

function git_list_subdirs ($dir_name)
{
  # We don't catch warnings when opening the directory: if the directory
  # isn't readable, the attributes need fixing in the filesystem.
  $dir_handle = opendir ($dir_name);
  if ($dir_handle === false)
    return [];
  $ret = [];
  while (($entry = readdir ($dir_handle)) !== false)
    if (preg_match ('/[.]git$/', $entry))
      $ret[] = $entry;
  closedir ($dir_handle);
  return $ret;
}

function git_list_dirs ($group_name, $git_dir, $clone_path)
{
  $ret = [];
  $entry = git_make_entry ($git_dir, "$group_name.git", $clone_path);
  if (!empty ($entry))
    $ret[] = $entry;
  $dir_name = "$git_dir/$group_name";
  if (!is_dir ($dir_name))
    return $ret;
  $dir_list = git_list_subdirs ($dir_name);
  foreach ($dir_list as $d)
    {
      $entry = git_make_entry ($git_dir, "$group_name/$d", $clone_path);
      if (!empty ($entry))
        $ret[] = $entry;
    }
  return $ret;
}

function git_list_repos ($group_name, $git_dir, $clone_path)
{
  $ret = git_list_dirs ($group_name, $git_dir, $clone_path);
  if (empty ($ret))
    return git_get_list_from_cgitrepos ($group_name);
  return $ret;
}

function git_run_git_branch ($dir)
{
  global $sys_git_name;
  if (!is_dir ($dir))
    return [];
  $res = utils_run_proc (
    [$sys_git_name, '--git-dir', $dir, 'branch', '--no-color', '-l'], $out, $err
  );
  $ret = [
    'branches' => [], 'default' => [],
    'out' => $out, 'err' => $err, 'dir' => $dir
  ];
  if ($res)
    return $ret;
  foreach (explode ("\n", $out) as $line)
    {
      if (!preg_match ('/^\s*([*]?)\s+(.*)/', $line, $arr))
        continue;
      $ret['branches'][] = $arr[2];
      if ($arr[1] === '*')
        $ret['default'][$arr[2]] = 1;
    }
  return $ret;
}

function git_list_branches ($git_dir, $repo_name)
{
  static $branches = [];
  if (array_key_exists ($repo_name, $branches))
    return $branches[$repo_name];
  $dir = "$git_dir/$repo_name.git";
  $ret = git_run_git_branch ($dir);
  $branches[$repo_name] = $ret;
  return $ret;
}
?>
