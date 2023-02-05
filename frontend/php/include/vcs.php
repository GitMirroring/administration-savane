<?php
# Function to define a generic VCS index.php.
#
# Copyright (C) 2005 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017, 2019, 2023 Ineiev
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

# Get array of repository descriptions.
function vcs_get_repos ($vcs_exfix, $group_id)
{
  global $sys_etc_dir;

  $group = project_get_object ($group_id);
  $ret = [];
  if ($vcs_exfix !== 'git')
    return $ret;

  exec ("grep -A 3 '^repo\.url=" . $group->getUnixName()
        . "\(/\|\.git$\)' " . $sys_etc_dir . "/cgitrepos", $output);
  $n = intval ((count ($output) + 1) / 5);
  for ($i = 0; $i < $n; $i++)
    $ret[$i] = [
      'url' => preg_replace (':repo.url=:', '', $output[$i * 5]),
      'path' => preg_replace (':repo.path=:', '', $output[$i * 5 + 1]),
      'desc' => preg_replace (':repo.desc=:', '', $output[$i * 5 + 2])
    ];
  return $ret;
}

function vcs_print_browsing_preface ($vcs_name)
{
  # TRANSLATORS: The argument is a name of VCS (like Arch, CVS, Git).
  print '<h2>';
  printf (_("Browsing the %s Repository"), $vcs_name);
  print "</h2>\n";
  print '<p>';
  # TRANSLATORS: The argument is a name of VCS (like Arch, CVS, Git).
  printf (_("You can Browse the %s repository of this project with\nyour web "
    . "browser. This gives you a good picture of the current status of the\n"
    . "source files. You may also view the complete histories of any file "
    . "in the\nrepository as well as differences among two versions."),
    $vcs_name
  );
  print "</p>\n";
}

# Enable cache for this page if the user isn't logged in, because
# crawlers particularly like it.
function vcs_exit_if_not_modified ($vcs_exfix)
{
  $file = utils_get_content_filename ("$vcs_exfix/index");
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

function vcs_print_links_to_repos ($group, $group_id, $vcs_exfix, $vcs_name)
{
  global $repo_list;

  $repo_list = vcs_get_repos ($vcs_exfix, $group_id);
  $have_links = $group->Uses ($vcs_exfix)
    && pagemenu_url_is_set ($group, "{$vcs_exfix}_viewcvs");
  $have_web_links = $group->UsesForHomepage ($vcs_exfix)
     && pagemenu_url_is_set ($group, "cvs_viewcvs_homepage");
  if (!($have_links || $have_web_links))
    return;
  vcs_print_browsing_preface ($vcs_name);
  print "<ul>\n";
  if ($have_links)
    vcs_print_source_repo_links ($group, $vcs_exfix, $repo_list);
  if ($have_web_links)
    print '<li><a href="' . $group->getUrl ("cvs_viewcvs_homepage") . '">'
      . _("Browse Web Pages Repository") . "</a></li>\n";
  print "</ul>\n";
}

function vcs_page ($vcs_name, $vcs_exfix, $group_id)
{
  if (!$group_id)
    exit_no_group ();

  $group = project_get_object ($group_id);
  if (!$group->Uses ($vcs_exfix) && !$group->UsesForHomepage ($vcs_exfix))
    exit_error (_("This project doesn't use this tool."));

  vcs_exit_if_not_modified ($vcs_exfix);
  site_project_header (['group' => $group_id,'context' => $vcs_exfix]);
  vcs_print_links_to_repos ($group, $group_id, $vcs_exfix, $vcs_name);

  print '<h2>';
  # TRANSLATORS: The argument is a name of VCS (like Arch, CVS, Git).
  printf (_("Getting a Copy of the %s Repository"), $vcs_name);
  print "</h2>\n";
  utils_get_content ("$vcs_exfix/index");
  site_project_footer ([]);
}
?>
