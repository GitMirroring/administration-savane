<?php
# Configure Git repositories.
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

require_once ('../../include/init.php');
require_once ('../../include/sane.php');
require_once ('../../include/vcs.php');
require_once ('../../include/form.php');

$vcs = 'git';

$res_grp = group_get_result ($group_id);

if (db_numrows ($res_grp) < 1)
  exit_error (_("Invalid Group"));

session_require (['group' => $group_id, 'admin_flags' => 'A']);
$functions = ['up', 'down'];
$repos = vcs_get_repos ($vcs, $group_id);
$desc = $readme = [];
$submit_tarballs = ['submit'];
$n = count ($repos);
for ($i = 0; $i < $n; $i++)
  {
    $desc[] = "desc$i";
    $readme[] = "readme$i";
    $submit_tarballs[] = "disable_tarballs$i";
  }

extract (sane_import ('get',
  ['strings' => [['func', $functions]], 'path' => ['name']]
));
extract (sane_import ('post', ['digits' => ['repo_no'], 'true' => ['submit']]));

function page_start ()
{
  global $group_id, $vcs;
  site_project_header (['group' => $group_id, 'context' => $vcs]);
}
function permute_repos ($i, $j, $n, &$repos)
{
  if ($j < 0)
    {
      $t = array_shift ($repos);
      array_push ($repos, $t);
      return;
    }
  if ($j >= $n)
    {
      $t = array_pop ($repos);
      array_unshift ($repos, $t);
      return;
    }
  $t = $repos[$i];
  $repos[$i] = $repos[$j];
  $repos[$j] = $t;
}

function func_move ($name, $inc)
{
  global $group_id, $repos, $vcs;
  $n = count ($repos);
  if ($n < 2)
    return;
  for ($i = 0; $i < $n && $repos[$i]['name'] !== $name; $i++)
    ; # Empty cycle body.
  if ($i == $n)
    return; # This shouldn't happen, asserted in check_name ().
  permute_repos ($i, $i + $inc, $n, $repos);
  vcs_save_sorting ($vcs, $group_id, $repos);
}

function func_up ($name)
{
  func_move ($name, -1);
}

function func_down ($name)
{
  func_move ($name, 1);
}

function repo_html_id ($name)
{
  global $group_id, $group_name;
  $group = project_get_object ($group_id);
  $group_name = $group->getUnixName ();
  return "repo-" . preg_replace (":^$group_name/?:", "", $name);
}

function check_name ()
{
  global $name, $repos;
  if (empty ($name))
    return false;

  foreach ($repos as $r)
    if ($r['name'] === $name)
      return true;
  page_start ();
  exit_error (sprintf (_("Repository %s not found"), $name));
  return false;
}

function display_introduction ()
{
  print "<p>";
  print
   _("On this page, group administrators can configure some settings of\n"
     . "the repositories of the group. The changes are in effect immediately\n"
     . "for Savane web pages and within an hour for our repository browsing\n"
     . "web server.");
  print "</p>\n";
}

function show_repo_title ($r)
{
  global $php_self, $group_id, $repos, $group_name, $anchor, $name;
  $url_base = "$php_self?group=$group_name&amp;name=$name&amp;func=";
  print "<h2 id='$anchor'>$name</h2>\n";
  if (count ($repos) < 2)
    return;
  print "<p>";
  print "<a href=\"{$url_base}up#$anchor\">" . _("Move up") . "</a>";
  print " &nbsp; ";
  print "<a href=\"{$url_base}down#$anchor\">" . _("Move down") . "</a>";
  print "</p>";
}

$show_edit_help = [
  'readme' =>
    _("Path to a file like <code><var>branch</var>:<var>directory</var>/"
      . "<var>file</var></code>.\n"
      . "The file will be displayed on the &ldquo;about page&rdquo;\n"
      . "of the repository.  Savane filter passes files with names ending in\n"
      . "&ldquo;.html&rdquo; unmodified; in other files, HTML special\n"
      . "characters are replaced with HTML entities, and the result\n"
      . "is enclosed in a <code>&lt;pre&gt;</code> element.  For more info,\n"
      . "dig into cgitrc (5).")
];

function show_edit ($name, $label, $val, $repo_no)
{
  global $show_edit_help;
  $help = null;
  if (!empty ($show_edit_help[$name]) && !$repo_no)
    $help = $show_edit_help[$name];
  $id = "$name$repo_no";
  print "<p><label for='$id'>$label</label> &nbsp; &nbsp;\n";
  print form_input ("text", "$id", $val, 'size="34" maxlength="102"');
  print "</p>\n";
  if ($help !== null)
    print "<p class='smaller'>$help</p>\n";
}

function show_disable_tarballs ($repo_no)
{
  global $group_id, $name, $vcs;
  $no_tarball = vcs_tarballs_disabled ($vcs, $group_id, $name);
  $cb_name = "disable_tarballs$repo_no";
  print "<p>";
  print form_checkbox ($cb_name, $no_tarball) . "\n";
  print "<label for='$cb_name'>"
    . _("don't provide links to snapshot tarballs") . "</label>";
  print "</p>\n";
}

function show_repo_form ($r, $repo_no, $name)
{
  global $group_id, $vcs;
  $readme = vcs_get_repo_readme ($vcs, $group_id, $name);
  show_edit (
    "desc", _("Description:"),
    utils_specialchars_decode ($r['desc'], ENT_QUOTES), $repo_no
  );
  print "<h3>" . _("Web browsing settings") . "</h3>\n\n";
  show_edit ("readme", _("README file:"), $readme, $repo_no);
  show_disable_tarballs ($repo_no);
  print form_hidden (['repo_no' => $repo_no]);
}

function show_repo ($r, $repo_no)
{
  global $group_name, $anchor, $name;
  $name = $r['name'];
  $anchor = repo_html_id ($name);
  $suff = "?group=$group_name&amp;name=$name#$anchor";
  $form_tag = form_tag ([], $suff);

  show_repo_title ($r);
  print $form_tag;
  show_repo_form ($r, $repo_no, $name);
  print form_input (
    'submit', "submit", _("Update"), 'class="' . utils_altrow ($repo_no) . '"'
  );
  if (!($repo_no % 2))
    print "<p> &nbsp;</p>\n";
  print "</form>\n";
}

function display_repos ($repos)
{
  global $repo_no;
  $repo_no = 0;
  if (empty ($repos))
    return;
  foreach ($repos as $r)
    {
      print '<div class="' . utils_altrow ($repo_no) . "\">\n";
      show_repo ($r, $repo_no);
      print "</div>\n";
      $repo_no++;
    }
}

if (isset ($submit))
  {
    if (empty ($repo_no))
      $repo_no = 0;
    extract (sane_import ('post',
      [
        'specialchars' => ["desc$repo_no"],
        'path' => ["readme$repo_no"],
        'true' => ["disable_tarballs$repo_no"]
      ]
    ));
    if (empty (${"disable_tarballs$repo_no"}))
      $disable_tarballs = 0;
    else
      $disable_tarballs = 1;
    $desc = ${"desc$repo_no"};
    $readme = ${"readme$repo_no"};
    if ($readme === null)
      $readme = '';
  }

$have_name = check_name ();
if (isset ($func) && $have_name)
  {
    $id = repo_html_id ($name);
    $f = "func_$func";
    $f ($name);
    header ("Location: $php_self?group=$group_name#$id");
  }

if (isset ($submit) && $have_name)
  {
    foreach (
      ['desc' => $desc, 'readme' => $readme, 'no-tarball' => $disable_tarballs]
      as $k => $v
    )
      if (isset ($v))
        vcs_set_repo_pref ($vcs, $group_id, $k, $name, $v);
    $repos = vcs_get_repos ($vcs, $group_id);
  }

page_start ();
display_introduction ();
display_repos ($repos);
$HTML->footer ([]);
?>
