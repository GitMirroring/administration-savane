<?php
# Manage commit hooks/triggers.
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2026 Ineiev
#
# This file is part of the Savane project
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

require_once ('../../include/init.php');
require_once ('../../include/sane.php');
require_once ('../../include/account.php');
require_once ('../../include/form.php');

function match_type_box ($arr_match_type, $row_match_type)
{
  return
    html_build_select_box_from_arrays (
      ['ALL', 'dir_list', 'DEFAULT'],
      [_('Always'), _('Module list'), _('Fallback')],
      $arr_match_type, $row_match_type, false, 'None', false, 'Any', false,
      'match type'
    );
}

# Get current information.
$res_grp = group_get_result ($group_id);

if (!db_numrows ($res_grp))
  exit_error (_("Invalid Group"));

session_require (['group' => $group_id, 'admin_flags' => MEMBER_FLAGS_ADMIN]);
$key_func = ['preg', '/^(([\d]+)|(new))$/'];
$email_regex =
   '([a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+\.)+[a-zA-Z0-9]+,)*'
   . '([a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+\.)+[a-zA-Z0-9]+)';
extract (sane_import ('post',
  [
    'true' => 'log_accum',
    'array' => [
      [
        'arr_branches',
        [$key_func, ['preg', '/^[-~!@#$%^&*()+=:.,_\da-zA-Z]+$/']]
      ],
      ['arr_id', [$key_func, 'true']],
      ['arr_remove', [['preg', '/^[\d]+$/'], 'true']],
      ['arr_repo_name', [$key_func, ['strings', ['sources', 'web']]]],
      [
        'arr_match_type',
        [$key_func, ['strings', ['ALL', 'dir_list', 'DEFAULT']]]
      ],
      [
        'arr_dir_list',
        [
          $key_func,
          ['preg', '/^(([a-zA-Z0-9_.+\/-]+,)*([a-zA-Z0-9_.+\/-]+))$/']
        ]
      ],
      ['arr_emails_notif', [$key_func, ['preg', "/^$email_regex" . '$/']]],
      ['arr_emails_diff', [$key_func, ['preg', "/^($email_regex)|$/"]]],
      ['arr_enable_diff', [$key_func, ['digits', [1, 1]]]]
    ]
  ]));
form_check ('log_accum');

if (isset ($log_accum))
  {
    $have_arr_id = isset ($arr_id) && is_array ($arr_id);
    if (isset ($arr_remove) && is_array ($arr_remove))
      foreach ($arr_remove as $hook_id => $ignored)
        {
          db_execute ("
            DELETE cvs_hooks, cvs_hooks_log_accum
            FROM cvs_hooks, cvs_hooks_log_accum
            WHERE
              cvs_hooks.id = cvs_hooks_log_accum.hook_id
              AND group_id = ? AND id = ?",
            [$group_id, $hook_id]) or util_die (db_error ());
          if ($have_arr_id)
            unset ($arr_id[$hook_id]);
        }
    if ($have_arr_id)
      foreach ($arr_id as $hook_id => $ignored)
        {
          if (!isset ($arr_repo_name[$hook_id]))
            continue;
          if (!isset ($arr_match_type[$hook_id]))
            continue;
          if (empty ($arr_emails_notif[$hook_id]))
            continue;
          $repo_name = $arr_repo_name[$hook_id];
          $match_type = $arr_match_type[$hook_id];
          $dir_list = null;
          if (isset ($arr_dir_list[$hook_id]))
            $dir_list = $arr_dir_list[$hook_id];
          $branches = null;
          if (isset ($arr_branches[$hook_id])
              && $arr_branches[$hook_id] !== '')
            $branches = $arr_branches[$hook_id];
          $enable_diff = '0';
          if (!empty ($arr_enable_diff[$hook_id]))
            $enable_diff = $arr_enable_diff[$hook_id];
          $emails_notif = $arr_emails_notif[$hook_id];
          $emails_diff = null;
          if (isset ($arr_emails_diff[$hook_id])
              && $arr_emails_diff[$hook_id] !== '')
            $emails_diff = $arr_emails_diff[$hook_id];

          if ($hook_id == 'new')
            {
              db_autoexecute ('cvs_hooks',
                [
                  'group_id' => $group_id,
                  'repo_name' => $repo_name,
                  'match_type' => $match_type,
                  'dir_list' => $dir_list,
                  'hook_name' => 'log_accum'
                ],
                DB_AUTOQUERY_INSERT) or util_die (db_error ());
              $new_hook_id = db_insertid (NULL);
              db_autoexecute ('cvs_hooks_log_accum',
                [
                  'hook_id' => $new_hook_id,
                  'branches' => $branches,
                  'emails_notif' => $emails_notif,
                  'enable_diff' => $enable_diff,
                  'emails_diff' => $emails_diff
                ],
                DB_AUTOQUERY_INSERT) or util_die (db_error ());
              continue;
            }
          db_autoexecute ('cvs_hooks, cvs_hooks_log_accum',
            [
              'repo_name' => $repo_name, 'match_type' => $match_type,
              'dir_list' => $dir_list, 'hook_name' => 'log_accum',
              'branches' => $branches, 'emails_notif' => $emails_notif,
              'enable_diff' => $enable_diff, 'emails_diff' => $emails_diff
            ],
            DB_AUTOQUERY_UPDATE,
            "cvs_hooks.id = cvs_hooks_log_accum.hook_id
            AND group_id = ? AND id = ?",
            [$group_id, $hook_id]) or util_die (db_error ());
        } # foreach ($arr_id as $hook_id => $ignored)
  } # if (isset($log_accum))
site_project_header (['group' => $group_id, 'context' => 'ahome']);

$hook = 'log_accum';
# Show the project's log_accum hooks.
$result =  db_execute ("
  SELECT
    hook_id, repo_name, match_type, dir_list, hook_name, branches,
    emails_notif, enable_diff, emails_diff
  FROM cvs_hooks JOIN cvs_hooks_$hook ON cvs_hooks.id = hook_id
  WHERE group_id = ?", [$group_id]
);

$repo_keys =  ['sources', 'web'];
# TRANSLATORS: this is the type of repository (sources  or web).
$repo_vals = [_('sources'), _('web')];

function current_notifications ($result)
{
  global $repo_keys, $repo_vals;
  $ret = '';
  while ($row = db_fetch_array ($result))
    {
      $cur = $row['hook_id'];
      $ret .= "<tr>\n<td>";
      $ret .= form_hidden (["arr_id[$cur]" => "$cur"]);
      $ret .= form_checkbox ("arr_remove[$cur]", 0);
      $ret .= "</td>\n<td>";
      $ret .= html_build_select_box_from_arrays (
        $repo_keys, $repo_vals, "arr_repo_name[$cur]", $row['repo_name'], false
      );
      $ret .= "</td>\n<td>";
      $ret .= match_type_box ("arr_match_type[$cur]", $row['match_type']);
      $ret .= "</td>\n";
      $ret .= "<td><input type='text' name='arr_dir_list[$cur]' "
        . "value='{$row['dir_list']}' size='10' /></td>\n";
      $ret .= "<td><input type='text' name='arr_branches[$cur]' "
        . "value='{$row['branches']}' size='10' /></td>\n";
      $ret .= "<td><input type='text' name='arr_emails_notif[$cur]' "
        . "value='{$row['emails_notif']}' size='13' /></td>\n";
      $ret .= "<td>";
      $ret .= form_checkbox ("arr_enable_diff[$cur]", $row['enable_diff']);
      $ret .= "</td>\n";
      $ret .= "<td><input type='text' name='arr_emails_diff[$cur]' "
        . "value='{$row['emails_diff']}' size='13' /></td>\n";
      $ret .= "</tr>\n";
    }
  return $ret;
}

function show_current_notifications ($group, $result)
{
  $cur_notif = current_notifications ($result);
  if (empty ($cur_notif))
    return;
  print form_tag () . form_hidden (["group" => $group]);
  print html_build_list_table_top (
    [
      html_image_trash (), _('Repo'), _('Match'), _('Modules'), _('Branches'),
      _('Send to'), _('Diff?'), _('Diffs to')
    ]
  );
  print $cur_notif;
  print "</table>\n";
  print form_footer (_("Modify"), 'log_accum');
}

print html_h (2, _("Current notifications"));
show_current_notifications ($group, $result);

print html_h (2, _("New notification"));
print form_tag () . form_hidden (["group" => $group]);
print "<ol>\n";
print "<li>" . _("Repository:") . " ";
print html_build_select_box_from_arrays (
  $repo_keys, $repo_vals, "arr_repo_name[new]", 'xzxz', false
);
print "</li>\n<li>";
print _("Matching type:") . " ";
print match_type_box ("arr_match_type[new]", 'xzxz');
print "<ul>
  <li>" . _("<i>Always</i> is always performed (even in addition to Fallback)")
  . "</li>
  <li>"
  . _("<i>Module list</i> if you specify a list of directories (see below)")
  . "</li>
  <li>" . _("<i>Fallback</i> is used when nothing matches") . "</li>
</ul>\n";
print "</li>\n<li>";
print _("Filter by directory: if match is <i>Module list</i>, enter a list of
  directories separated by commas,
  e.g. <code>emacs,emacs/lisp,manual</code><br />
  You'll only get notifications if the commit is performed in one of these
  directories.<br />
  Leave blank if you want to get notifications for all the repository:")
  . "<br />";
print "<input type='text' name='arr_dir_list[new]' value='' />";
print "</li>\n<li>";
print _("List of comma-separated emails to send notifications to, e.g.
  winnie@the-pooh.mil, yu@guan.edu:") . "<br />";
print "<input type='text' name='arr_emails_notif[new]' "
  . "value='' />";
print "</li>\n<li>";
print _("Send diffs?") . " ";
print form_checkbox ("arr_enable_diff[new]", 0);
print "</li>\n<li>";
print _("Optional alternate list of emails to send diffs separately to, e.g.
  winnie@the-pooh.mil, yu@guan.edu.<br />
  If empty, the diffs will be included in commit notifications:")
  . "<br />";
print "<input type='text' name='arr_emails_diff[new]' "
  . "value='' />\n";
print "</li>\n<li>";
print _("Filter by branch: you will be notified only commits in these branches,
  separated by commas.<br />
  Enter <i>HEAD</i> if you only want trunk commits.<br />
  Leave blank to get notifications for all commits:")
  . "<br />";
print "<input type='text' name='arr_branches[new]' value='' />";
print "</li>\n</ol>\n";
print form_hidden (['arr_id[new]' => 'new']);
print form_footer (_('Add'), 'log_accum');

print "<p>" . _("The changes come into effect within an hour.") . "</p>\n";

site_project_footer ();
?>
