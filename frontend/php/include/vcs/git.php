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

function git_description_fallback (&$desc, $url)
{
  $default_description =
    "Unnamed repository; edit this file 'description' to name the repository.";
  if (empty ($desc) || $desc == $default_description)
    $desc = $url;
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

  exec (
    "grep -A 3 '^repo\.url=$group_name\(/\|\.git$\)' $sys_etc_dir/cgitrepos",
    $output
  );
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
?>
