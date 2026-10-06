<?php
# Git-specific UI functions.
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

function git_control_func ($idx, $func = null)
{
  static $funcs = [
    'checkbox' => 'form_checkbox', 'text' => 'git_text_control',
    'textarea' => 'git_textarea'
  ];
  $ret = $funcs[$idx];
  if ($func !== null)
    $funcs[$idx] = $func;
  return $funcs[$idx];
}

function git_controls_are_disabled ()
{
  return git_control_func ('checkbox') !== 'form_checkbox';
}

function git_text_control ($name, $val)
{
  return form_input ('text', $name, $val);
}

function git_textarea ($name, $val, $extra)
{
  return form_textarea ($name, $val, $extra);
}

function git_disabled_checkbox ($name, $checked)
{
  return form_checkbox ($name, $checked, ['disabled' => 'disabled']);
}

function git_disabled_text ($name, $val)
{
  return form_input ('text', $name, $val, "disabled='disabled'");
}

function git_disabled_textarea ($name, $val, $extra)
{
  return "<pre>$val</pre>\n";
}
function git_disable_controls ()
{
  git_control_func ('checkbox', 'git_disabled_checkbox');
  git_control_func ('text', 'git_disabled_text');
  git_control_func ('textarea', 'git_disabled_textarea');
}

function git_stage_hook_line ($stage, $r, $hook, $checked, $prefs, $i)
{
  $checkbox_func = git_control_func ('checkbox');
  $h = "$stage-$hook";
  return $checkbox_func ($h, $checked)
    . html_label ($h, $hook) . form_hidden (["add-hook-$i" => $h]);
}

function git_list_stage_hooks (
  $r, $stage = null, $prefs = null, $disabled = null
)
{
  static $i = 0;
  if ($stage === null)
    {
      $ret = form_hidden (['add-hook-no' => $i]);
      $i = 0;
      return $ret;
    }
  $hooks = git_read_hook_dir ($r, $stage);
  if (empty ($hooks))
    return null;
  foreach ($hooks as $h)
    $ret[] = git_stage_hook_line (
      $stage, $r, $h, empty ($disabled[$h]), $prefs, $i++
    );
  return '<ul>' . html_join_enclosed ($ret, ['li']) . "</ul>\n";
}

function git_auto_close_help ()
{
  return "<p class='smaller'>"
    . _('Check this box to enable auto-closing tracker items.  When commits '
        . 'with lines in the footer of the log message like '
        . '&ldquo;<code>Closes-Item: <i>tracker</i> '
        . '#<i>item_id</i></code>&rdquo; are '
        . 'pushed, the item #<i>item_id</i> in the tracker '
        . '<i>tracker</i> will be closed.  The line may end '
        . 'with an optional comment string.  The <i>item_id</i> '
        . 'should be a decimal number, <i>tracker</i> should be '
        . 'selected from the list,')
    . "</p>\n<ul>\n"
    . html_join_enclosed (utils_get_dependable_trackers (), ['li', 'code'])
    . "</ul>\n";
}

function git_auto_open_help ()
{
  return "<p class='smaller'>"
    . _('Likewise, open items with &ldquo;<code>Opens-Item:</code>&rdquo;.')
    . "</p>\n";
}

function git_auto_update_help ()
{
  return "<p class='smaller'>"
    . _('Similarly, &ldquo;<code>Updates-Item:</code>&rdquo; will add '
        . 'a comment on the tracker without changing its status.')
    . "</p>\n";
}

function git_forbid_merges_help ()
{
  return "<p class='smaller'>"
    . _('Pushes with any merge commits will be rejected.') . "</p>\n";
}

function git_extra_hooks_help ()
{
  return '<p>'
    .  _('Additional hooks are installed in this repository. '
         . 'You can enable or disable them.') . "</p>\n";
}

function git_initial_help ()
{
  return [
    'closes-item' => git_auto_close_help (),
    'opens-item' => git_auto_open_help (),
    'updates-item' => git_auto_open_help (),
    'no-merges' => git_forbid_merges_help (),
    'extra-hooks' => git_extra_hooks_help ()
  ];
}

function git_hook_help ($idx, $str = null)
{
  static $help = null;
  if ($idx === null)
    $help = [];
  if ($help === null)
    $help = git_initial_help ();
  $ret = '';
  if (array_key_exists ($idx, $help))
    {
      $ret = $help[$idx];
      unset ($help[$idx]);
    }
  if ($str !== null)
    $help[$idx] = $str;
  return $ret;
}

function git_simple_hook_controls ($r, $prefs)
{
  $controls = [
    'closes-item' => _('Enable auto-closing'),
    'opens-item' => _('Enable auto-opening'),
    'updates-item' => _('Enable commenting on Git push'),
    'no-merges' => _('Forbid merges')
  ];
  $ret = '';
  foreach ($controls as $hook => $msg)
    $ret .= git_hook_help ($hook)
      . git_pref_checkbox ($hook, $prefs, $msg);
  return $ret;
}

function git_list_additional_hooks ($r, $prefs)
{
  $dl = [];
  foreach (['pre', 'post'] as $stage)
    {
      $disabled = git_get_disabled_additional_hooks ($prefs, $stage);
      $dt = git_list_stage_hooks ($r, $stage, $prefs, $disabled);
      if ($dt !== null)
        $dl["$stage-receive"] = $dt;
    }
  if (empty ($dl))
    return '';
  $msg = html_h (4, _('Extra hooks')) . git_hook_help ('extra-hooks');
  return $msg . html_dl ($dl) . git_list_stage_hooks (null);
}

function git_pref_val ($idx, $prefs, $def = false)
{
  if (!array_key_exists ($idx, $prefs))
    return $def;
  return $prefs[$idx];
}

function git_pref_checkbox ($idx, $prefs, $label)
{
  $checkbox_func = git_control_func ('checkbox');
  $val = git_pref_val ($idx, $prefs);
  $name = git_pref_to_input ($idx);
  return '<p>' . $checkbox_func ($name, $val) . html_label ($name, $label)
    . "</p>\n";
}

function git_gpg_import_test_key ($no, &$key)
{
  if (empty ($GLOBALS['test_gpg_key']) || git_controls_are_disabled ())
    return;
  $idx = "check-gpg--keys";
  $in = sane_import ('request', ['pass' => [$idx]]);
  if (empty ($in[$idx]))
    return;
  $key = $in[$idx];
}

function git_gpg_key_input ($key, $no)
{
  $textarea = git_control_func ('textarea');
  $read_only = git_controls_are_disabled ();
  $msg = _("Allowed GPG keys");
  if ($read_only)
    $ret = html_h (5, $msg);
  else
    $ret = "<p><span class='preinput'>"
      . html_label ('check-gpg--keys', $msg) . "</span><br />\n";
  $ret .=
      $textarea ('check-gpg--keys', utils_specialchars ($key),
        'cols="70" rows="20" wrap="virtual"'
      )
    . "</p>\n";
  if (!$read_only)
    $ret .= '<p>' . form_submit (_("Test GPG keys"), 'test_gpg_key')
    . ' ' . _("(Testing is recommended before updating.)") . "</p>\n";
  return $ret;
}

function git_gpg_controls ($r, $prefs)
{
  $checkbox = git_control_func ('checkbox');
  $key = git_pref_val ('check-gpg:keys', $prefs, '');
  git_gpg_import_test_key ($r['no'], $key);
  $check_gpg = git_pref_val ('check-gpg', $prefs);
  $ret = html_h (4, _('GPG signatures'))
    . git_pref_checkbox ('check-gpg', $prefs, _('Only allow signed commits.'));
  if (!$check_gpg)
    return $ret;
  $ret .= git_gpg_key_input ($key, $r['no']);
  if (git_controls_are_disabled ())
    return $ret;
  if ($GLOBALS['repo_no'] === $r['no'] && !empty ($GLOBALS['test_gpg_key']))
    $ret .= gpg_run_checks ($key, false, 5);
  return $ret;
}
function git_check_if_repo_list_is_empty ($repos)
{
  if (!empty ($repos))
    return 0;
  print '<p>' . _("No repositories are found.") . "</p>\n";
  return 1;
}

function git_display_repos ($repos)
{
  global $repo_no;
  $repo_no = 0;
  if (git_check_if_repo_list_is_empty ($repos))
    return;
  foreach ($repos as $r)
    {
      print "<div id='repo-box$repo_no' class='"
        . utils_altrow ($repo_no) . "'>\n";
      $r['no'] = $repo_no;
      show_repo ($r); # show_repo is page-specific.
      print "</div>\n";
      $repo_no++;
    }
}

function git_repo_html_id ($name)
{
  global $group_id, $group_name;
  $group = project_get_object ($group_id);
  $group_name = $group->getUnixName ();
  return "repo-" . preg_replace (":^$group_name/?:", "", $name);
}

function git_all_hook_controls ($r, $prefs)
{
  return git_simple_hook_controls ($r, $prefs)
    . git_gpg_controls ($r, $prefs) . git_list_additional_hooks ($r, $prefs);
}

function git_print_anon_access_commands ($cmd, $base_host, $repo_list, $group)
{
  if (!$group->isPublic ())
    return '';
  $n = count ($repo_list);
  vcs_print_anon_access ();
  $addr = "https://git.$base_host/git/";

  print '<pre>';
  for ($i = 0; $i < $n; $i++)
    {
      if ($n > 1)
        print $repo_list[$i]['desc'] . "\n";
      print "$cmd $addr" . $repo_list[$i]['url'] . "\n";
      if ($i < $n - 1)
        print "\n";
    }
  print "</pre>\n\n";
}

function git_list_auth_access_commands ($cmd, $base_host, $repo_list, $username)
{
  $n = count ($repo_list);
  $ret = [];
  for ($i = 0; $i < $n; $i++)
    {
      $entry = '';
      if ($n > 1)
        $entry .= $repo_list[$i]['desc'] . "\n";
      $addr = "$username@git.$base_host:";
      $entry .= "$cmd $addr" . $repo_list[$i]['path'] . "\n";
      $ret[] = $entry;
    }
  return '<pre>' . join ("\n", $ret) . "</pre>\n";
}
?>
