<?php
# Configure Git repositories.
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

foreach (['init', 'sane', 'vcs', 'vcs/git-ui', 'form', 'gpg'] as $i)
  require_once ("../../include/$i.php");

# Long help messages are only printed once, for the first repo.
$skip_help = false;

if (!db_numrows (group_get_result ($group_id)))
  exit_error (_("Invalid Group"));

session_require (['group' => $group_id, 'admin_flags' => MEMBER_FLAGS_ADMIN]);
$repos = vcs_get_repos ('git', $group_id);
$desc = $readme = [];
$n = count ($repos);
for ($i = 0; $i < $n; $i++)
  {
    $desc[] = "desc$i";
    $readme[] = "readme$i";
  }

$triggers = sane_import ('post', ['true' => git_known_triggers ()]);
$submit_buttons = ['submit', 'test_gpg_key'];
extract (sane_import ('get', ['strings' => [['func', ['up', 'down']]]]));
extract (sane_import ('post',
  ['digits' => ['repo_no', 'hook_group'], 'true' => $submit_buttons]
));
extract (sane_import ('request', ['path' => ['name']]));
form_check ($submit_buttons);

if ($hook_group === null)
  $hook_group = -1;

function page_start ()
{
  global $group_id;
  site_project_header (['group' => $group_id, 'context' => 'git']);
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
  global $group_id, $repos;
  $n = count ($repos);
  if ($n < 2)
    return;
  for ($i = 0; $i < $n && $repos[$i]['name'] !== $name; $i++)
    ; # Empty cycle body.
  if ($i == $n)
    return; # This shouldn't happen, asserted in check_name ().
  permute_repos ($i, $i + $inc, $n, $repos);
  vcs_save_sorting ('git', $group_id, $repos);
}

function func_up ($name)
{
  func_move ($name, -1);
}

function func_down ($name)
{
  func_move ($name, 1);
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
  print html_h (2, $r['name'], $anchor);
  if (count ($repos) < 2)
    return;
  print "<p>";
  print "<a href=\"{$url_base}up#$anchor\">" . _("Move up") . "</a>";
  print " &nbsp; ";
  print "<a href=\"{$url_base}down#$anchor\">" . _("Move down") . "</a>";
  print "</p>";
}

function display_hooks ($r)
{
  global $group_id;
  if (!git_have_savane_hooks (git_get_repo_path ($r)))
    return;
  $prefs = git_get_hook_prefs ($r, $group_id);
  print html_h (3, _('Git hooks'), 'hooks-' . $r['no']);
  print form_tag ()
    . form_hidden (
        ['group_id' => $group_id, 'hook_group' => $r['no'], 'repo' => $r['name']]
      )
    . git_all_hook_controls ($r, $prefs) . git_itemize_branches ($r)
    . '<p>' . form_submit (_("Update"), 'submit') . "</p>\n</form>\n";
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
  if (!empty ($show_edit_help[$name]) && !$GLOBALS['skip_help'])
    $help = $show_edit_help[$name];
  $id = "$name$repo_no";
  print '<p>' . html_label ($id, $label) . " &nbsp; &nbsp;\n";
  print form_input ("text", "$id", $val, 'size="34" maxlength="102"');
  print "</p>\n";
  if ($help !== null)
    print "<p class='smaller'>$help</p>\n";
}

function show_disable_tarballs ($r)
{
  global $group_id;
  $no_tarball = vcs_tarballs_disabled ('git', $group_id, $r['name']);
  $cb_name = "disable_tarballs{$r['no']}";
  print "<p>";
  print form_checkbox ($cb_name, $no_tarball) . "\n";
  print html_label ($cb_name, _("don't provide links to snapshot tarballs"));
  print "</p>\n";
}

function show_repo_form ($r)
{
  global $group_id;
  $readme = vcs_get_repo_readme ('git', $group_id, $r['name']);
  show_edit (
    "desc", _("Description:"),
    utils_specialchars_decode ($r['desc'], ENT_QUOTES), $r['no']
  );
  print html_h (3, _("Web browsing settings"));
  show_edit ("readme", _("README file:"), $readme, $r['no']);
  show_disable_tarballs ($r);
  print form_hidden (['repo_no' => $r['no']]);
}

function show_repo ($r)
{
  global $group_name, $anchor, $skip_help;
  $name = $r['name'];
  $anchor = git_repo_html_id ($name);

  show_repo_title ($r);
  print form_tag ([], "#$anchor")
    . form_hidden (['group' => $group_name, 'name' => $r['name']]);

  show_repo_form ($r);
  print form_submit (_("Update"), 'submit');
  print "</form>\n";
  display_hooks ($r);
  $skip_help = true;
}

function get_add_hook_from_post ($i)
{
  if (!array_key_exists ("add-hook-$i", $_POST))
    return [null, null, null];
  $h = $_POST["add-hook-$i"];
  if (!preg_match (',^(?<stage>(pre|post))-(?<hook>[^/:]+)$,', $h, $arr))
    return [null, null, null];
  $stage = $arr['stage'];
  $hook = $arr['hook'];
  return [$stage, $hook, !empty ($_POST["$stage-$hook"])];
}

function get_disabled_hooks_from_post ()
{
  $data = sane_import ('post', ['digits' => ['add-hook-no']]);
  $ret = [];
  for ($i = 0; $i < $data['add-hook-no']; $i++)
    {
      list ($stage, $hook, $enabled) = get_add_hook_from_post ($i);
      if (empty ($stage))
        continue;
      $ret[$stage][$hook] = !$enabled;
    }
  return $ret;
}

function import_excluded_branches ($hook)
{
  $n = sane_import ('post',
     [
       'digits' => ["branch-num-0-$hook", "branch-num-1-$hook"],
       'true' => ["branch-greedy-$hook"]
     ]
  );
  foreach (['branch-num-0', 'branch-num-1', 'branch-greedy'] as $v)
    $ret[$v] = $n["$v-$hook"];
  if ($ret['branch-num-0'] === null || $ret['branch-num-1'] === null)
    return null;
  return $ret;
}

function import_branch_data ($i, $n, &$branches)
{
  $in = sane_import ('post',
    ['true' => ["branch-cb-$i"], 'path' => ["branch-name-$i"]]
  );
  $vex = empty ($in["branch-cb-$i"]);
  if (empty ($n['branch-greedy']))
    $vex = !$vex;
  if ($vex)
    $branches[] = $in["branch-name-$i"];
}

function set_excluded_branches ($repo, $hook, &$prefs)
{
  $pfx = "vcs:git:" . git_pref_name_pfx ($repo);
  $n = import_excluded_branches ($hook);
  if ($n === null)
    return;
  $branches = [];
  for ($i = $n['branch-num-0']; $i < $n['branch-num-1']; $i++)
    import_branch_data ($i, $n, $branches);
  $val = join (HOOK_SEPARATOR, $branches);
  if (empty ($n['branch-greedy']))
    $val = HOOK_SEPARATOR . HOOK_NON_GREEDY . HOOK_SEPARATOR . $val;
  $prefs["$pfx$hook:exclude-branches"] = $val;
}

function set_additional_hooks ($repo, &$prefs)
{
  $disabled = get_disabled_hooks_from_post ();
  if (empty ($disabled))
    return;
  $name = $repo['name'];
  $pfx = "vcs:git:" . git_pref_name_pfx ($name);
  foreach ($disabled as $stage => $hooks)
    {
      $dis_list = [];
      foreach ($hooks as $hook => $dis)
        if ($dis)
          $dis_list[] = $hook;
      $prefs["{$pfx}disabled-hooks:$stage"] = join (HOOK_SEPARATOR, $dis_list);
      foreach (git_read_hook_dir ($repo, $stage) as $v)
        set_excluded_branches ($name, "$stage-$v", $prefs);
    }
}

function set_gpg_prefs ($repo, &$pref)
{
  $k = "check-gpg--keys";
  if (!array_key_exists ($k, $_POST))
    return;
  $pref["vcs:git:" . git_input_to_pref ($repo, $k)] = $_POST[$k];
}

function set_hooks ($repos, $hook_group)
{
  global $group_id, $triggers;
  if (empty ($repos[$hook_group]))
    trigger_error ("No repos[$hook_group]: " . error_print_r ($repos));
  $repo = $repos[$hook_group];
  $name = $repo['name'];
  $pref = [];
  foreach (git_known_triggers () as $v)
    {
      $val = 0;
      if (!empty ($triggers[$v]))
        $val = $triggers[$v];
      $pref["vcs:git:" . git_input_to_pref ($name, $v)] = $val;
      set_excluded_branches ($name, $v, $pref);
    }
  set_additional_hooks ($repo, $pref);
  set_gpg_prefs ($name, $pref);
  if (empty ($pref))
    return;
  group_set_preference ($group_id, $pref);
}

function set_repos ()
{
  global $repo_no, $disable_tarballs, $desc, $readme;
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

function set_data ($submit, $repos, $hook_group)
{
  if (!isset ($submit))
    return;
  if ($hook_group >= 0)
    set_hooks ($repos, $hook_group);
  else
    set_repos ();
}

function process_func ($func, $name)
{
  global $php_self, $group_name;
  $have_name = check_name ();
  if (!(isset ($func) && $have_name))
    return $have_name;
  $id = git_repo_html_id ($name);
  $f = "func_$func";
  $f ($name);
  header ("Location: $php_self?group=$group_name#$id");
  return $have_name;
}

function update_repos ($submit, $have_name)
{
  global $desc, $readme, $disable_tarballs, $group_id, $name, $repos;
  if (!(isset ($submit) && $have_name))
    return;
  foreach (
    ['desc' => $desc, 'readme' => $readme, 'no-tarball' => $disable_tarballs]
    as $k => $v
  )
    if (isset ($v))
      vcs_set_repo_pref ('git', $group_id, $k, $name, $v);
  $repos = vcs_get_repos ('git', $group_id, true);
}

set_data ($submit, $repos, $hook_group);
$have_name = process_func ($func, $name);
update_repos ($submit, $have_name);

page_start ();
display_introduction ();
git_display_repos ($repos);
$HTML->footer ([]);
?>
