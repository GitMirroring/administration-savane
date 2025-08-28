<?php
# Generic VCS-related functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2025 Ineiev
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

require_once (dirname (__FILE__) . '/vcs/git.php');
require_once (dirname (__FILE__) . '/vcs/cvs.php');

function vcs_sorting_sequence ($vcs, $group_id)
{
  $pref = group_get_preference ($group_id, "vcs:$vcs:repo-order");
  if (empty ($pref))
    return [];
  return explode (':', $pref);
}

function vcs_save_sorting ($vcs, $group_id, &$repos)
{
  $order = [];
  foreach ($repos as $r)
    $order[] = $r['name'];
  $order = join (':', $order);
  group_set_preference ($group_id, "vcs:$vcs:repo-order", $order);
}

function vcs_get_repo_pref ($vcs, $group_id, $pref_name, $repo)
{
  $repos = utils_make_arg_array ($repo);
  $names = $ret = [];
  foreach ($repos as $r)
    $names[] = "vcs:$vcs:$pref_name:$r";
  $pref_array = group_get_preference ($group_id, $names);
  foreach ($pref_array as $name => $val)
    {
      $n = str_replace ("vcs:$vcs:$pref_name:", "", $name);
      $ret[$n] = $val;
    }
  return utils_return_val ($repo, $ret);
}
function vcs_set_repo_pref ($vcs, $group_id, $pref, $repo, $val)
{
  group_set_preference ($group_id, "vcs:$vcs:$pref:$repo", $val);
}

function vcs_set_repo_description ($vcs, $group_id, $name, $description)
{
  vcs_set_repo_pref ($vcs, $group_id, "desc", $name, $description);
}
function vcs_get_repo_description ($vcs, $group_id, $name)
{
  return vcs_get_repo_pref ($vcs, $group_id, "desc", $name);
}

function vcs_tarballs_disabled ($vcs, $group_id, $name)
{
  return vcs_get_repo_pref ($vcs, $group_id, "no-tarball", $name);
}
function vcs_disable_tarballs ($vcs, $group_id, $name, $val)
{
  vcs_set_repo_pref ($vcs, $group_id, "no-tarball", $name, $val);
}

function vcs_set_repo_readme ($vcs, $group_id, $name, $description)
{
  vcs_set_repo_pref ($vcs, $group_id, "readme", $name, $description);
}
function vcs_get_repo_readme ($vcs, $group_id, $name)
{
  return vcs_get_repo_pref ($vcs, $group_id, "readme", $name);
}

# Use descriptions from database when available.
function vcs_override_descriptions ($vcs, $group_id, $repos)
{
  $ret = $names = $desc = [];
  foreach ($repos as $r)
    $names[] = $r['name'];
  $desc = vcs_get_repo_description ($vcs, $group_id, $names);
  foreach ($repos as $r)
    {
      if (!empty ($desc[$r['name']]))
        $r['desc'] = $desc[$r['name']];
      $ret[] = $r;
    }
  return $ret;
}

# Sort repos according to group preferences.
function vcs_sort_repos ($vcs, $group_id, $repos)
{
  $seq = vcs_sorting_sequence ($vcs, $group_id);
  if (empty ($seq))
    return $repos;
  $named = [];
  foreach ($repos as $r)
    $named[$r['name']] = $r;
  $ret = [];
  foreach ($seq as $r)
    if (array_key_exists ($r, $named))
      {
        $ret[] = $named[$r];
        unset ($named[$r]);
      }
  foreach ($named as $r)
    $ret[] = $r;
  return $ret;
}

function vcs_fetch_repos ($vcs, $group_id)
{
  global $sys_vcs_dir;
  $func = "{$vcs}_list_repos";
  if (!function_exists ($func))
    return [];
  $group = group_get_object ($group_id);
  $group_name = $group->getUnixName ();
  if (empty ($sys_vcs_dir) || !is_array ($sys_vcs_dir))
    return [];
  if (empty ($sys_vcs_dir[$vcs]['dir']) || !is_dir ($sys_vcs_dir[$vcs]['dir']))
    return [];
  $vcs_dir = $sys_vcs_dir[$vcs]['dir'];
  if (empty ($sys_vcs_dir[$vcs]['clone-path']))
    $clone_path = $vcs_dir;
  else
    $clone_path = $sys_vcs_dir[$vcs]['clone-path'];
  $repos = $func ($group_name, $vcs_dir, $clone_path);
  $repos = vcs_override_descriptions ($vcs, $group_id, $repos);
  return vcs_sort_repos ($vcs, $group_id, $repos);
}

# Get array of repository descriptions.
function vcs_get_repos ($vcs, $group_id, $reload = false)
{
  static $cache = [];
  if (array_key_exists ($vcs, $cache)
    && array_key_exists ($group_id, $cache[$vcs]) && !$reload
  )
    return $cache[$vcs][$group_id];
  $cache[$vcs][$group_id] = vcs_fetch_repos ($vcs, $group_id);
  return $cache[$vcs][$group_id];
}

function vcs_print_browsing_preface ($vcs_name)
{
  # TRANSLATORS: The argument is a name of VCS (like Arch, CVS, Git).
  print html_h (2, sprintf (_("Browsing the %s Repository"), $vcs_name));
  print '<p>';
  # TRANSLATORS: The argument is a name of VCS (like Arch, CVS, Git).
  printf (_("You can browse the %s repository of this group with\nyour web "
    . "browser. This gives you a good picture of the current status of the\n"
    . "source files. You can also view the complete history of any file "
    . "in the\nrepository as well as differences among two versions."),
    $vcs_name
  );
  print "</p>\n";
}

# Enable cache for this page if the user isn't logged in, because
# crawlers particularly like it.
function vcs_exit_if_not_modified ($vcs)
{
  $file = utils_get_content_filename ("$vcs/index");
  if ($file == null || user_isloggedin ())
    return;
  $stat = stat ($file);
  $mtime = $stat['mtime'];
  http_exit_if_not_modified ($mtime);
  header ('Last-Modified: ' . date ('r', $mtime));
}

function vcs_print_source_repo_links ($group, $vcs, $repo_list)
{
  $n = count ($repo_list);
  if ($n <= 1)
    {
      print '<li><a href="' . $group->getUrl ("{$vcs}_viewcvs")
        . '">' . _("Browse Sources Repository") . "</a></li>\n";
      return;
    }
  $url0 = preg_replace (
    ':/[^/]*$:', '/', $group->getUrl ("{$vcs}_viewcvs")
  );

  for ($i = 0; $i < $n; $i++)
    print '<li><a href="' . $url0 . $repo_list[$i]['url'] . '">'
      . $repo_list[$i]['desc'] . "</a></li>\n";
}

function vcs_label ($vcs)
{
  # TRANSLATORS: These strings are used in the context of
  # "Browsing the CVS repository" and "You can browse the CVS repository",
  # "Getting a copy of the CVS repository", see include/vcs.php.
  $names = [
    'arch' => _('Arch'), 'bzr' => _('Bazaar'), 'cvs' => _('CVS'),
    'git' => _('Git'), 'hg' => _('Mercurial'), 'svn' => _('Subversion')
  ];
  if (empty ($names[$vcs]))
    return null;
  return $names[$vcs];
}

function vcs_print_links_to_repos ($group, $group_id, $vcs)
{
  global $repo_list;

  $repo_list = vcs_get_repos ($vcs, $group_id);
  $have_links = $group->Uses ($vcs)
    && pagemenu_url_is_set ($group, "{$vcs}_viewcvs");
  $web_link = pagemenu_vcs_web_browse_url ($group, $vcs);
  if (!($have_links || $web_link !== ''))
    return;
  vcs_print_browsing_preface (vcs_label ($vcs));
  print "<ul>\n";
  if ($have_links)
    vcs_print_source_repo_links ($group, $vcs, $repo_list);
  if ($web_link !== '')
    print "<li><a href=\"$web_link\">"
      . _("Browse Web Pages Repository") . "</a></li>\n";
  print "</ul>\n";
}

function vcs_page ($vcs, $group_id)
{
  exit_if_no_group ();
  $vcs_name = vcs_label ($vcs);
  if ($vcs_name === null)
    exit_error ();

  $group = group_get_object ($group_id);
  if (!$group->Uses ($vcs) && !$group->UsesForHomepage ($vcs))
    exit_error (_("This group doesn't use this tool."));

  vcs_exit_if_not_modified ($vcs);
  site_project_header (['group' => $group_id,'context' => $vcs]);
  vcs_print_links_to_repos ($group, $group_id, $vcs, $vcs_name);

  # TRANSLATORS: The argument is a name of VCS (like Arch, CVS, Git).
  print html_h (2,
    sprintf (_("Getting a Copy of the %s Repository"), vcs_label ($vcs))
  );
  utils_get_content ("$vcs/index");
  site_project_footer ([]);
}

function vcs_compile_repo_ul ($repos, $scm_url)
{
  $u = preg_replace (':/[^/]*$:', '/', $scm_url);
  $ret = '';
  foreach ($repos as $r)
    $ret .= "<li><a href=\"$u{$r['url']}\">{$r['desc']}</a></li>\n";
  return $ret;
}
?>
