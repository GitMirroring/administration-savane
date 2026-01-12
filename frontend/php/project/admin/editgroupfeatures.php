<?php
# Enable and configure group available services.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2008 Aleix Conchillo Flaque
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

require_once ('../../include/init.php');
require_once ('../../include/sane.php');
session_require (['group' => $group_id, 'admin_flags' => MEMBER_FLAGS_ADMIN]);

$vcs = ['cvs', 'arch', 'svn', 'git', 'hg', 'bzr'];
$use_url = array_merge (
  utils_get_dependable_trackers (), [
    'mail', 'download', 'homepage',
    'forum', 'extralink_documentation', 'cookbook'
  ]
);
$pref_vars = ['cookbook' => 1];
$use_ = array_merge ($vcs, $use_url, ['news']);

function post_names ()
{
  global $vcs, $use_url, $use_;
  $names = ['true' => ['update'], 'specialchars' => ['dir_download']];
  foreach ($use_ as $u)
    $names['true'][] = 'use_' . $u;
  $viewvcs = [];
  foreach ($vcs as $v)
    $viewvcs[] = $v . '_viewcvs';
  $urls = array_merge ($vcs, $viewvcs, $use_url);
  foreach ($urls as $u)
    $names['specialchars'][] = 'url_' . $u;
  $names['specialchars'][] = 'url_cvs_viewcvs_homepage';
  return $names;
};

function get_cases ($names)
{
  $n = $names['true'];
  unset ($n[0]);
  return array_merge ($n, $names['specialchars']);
}

function str_match ($needle, $haystack)
{
  return strpos ($haystack, $needle) !== false;
}

function field_usable ($grp, $field, $field_name)
{
  if ($grp->CanUse ($field_name))
    return true;
  if ($field_name == "extralink_documentation")
    return true;
  if ($field == "url_cvs_viewcvs_homepage" && $grp->CanUse ("homepage"))
    return true;
  foreach (['cvs', 'arch', 'svn', 'git', 'hg', 'bzr'] as $vcs)
    if ($field == "url_{$vcs}_viewcvs" && $grp->CanUse ($vcs))
      return true;
  return false;
}

function type_usable ($grp, $type, $field_name, $field)
{
  if ($type == "use")
    return 1;
  if ($type == "url")
    return $grp->CanModifyUrl ($field_name);
  return
    $type == "dir" && $field == "dir_download" && $grp->CanUse ("download")
    && $grp->CanModifyDir ("download_dir");
}

$names = post_names ();
extract (sane_import ('post', $names));
form_check ('update');

$query = sane_import ('get',
  ['specialchars' => 'feedback', 'digits' => 'error']
);

if ($query['feedback'])
  fb ($query['feedback'], $query['error']);

function normalize_updated_vars ()
{
  # In the database, these all default to '1',
  # so we have to explicitly set 0 (this is ugly).
  foreach ($GLOBALS['use_'] as $u)
    {
      $var = 'use_' . $u;
      if (!$GLOBALS[$var])
        $GLOBALS[$var] = 0;
    }
}

function get_update_list ($grp, $cases)
{
  global $pref_vars;
  $ret = [[], []];

  foreach ($cases as $field)
    {
      $field_name = substr ($field, 4);
      if (!field_usable ($grp, $field, $field_name))
        continue;
      $type = substr ($field, 0, 3);
      if (type_usable ($grp, $type, $field_name, $field))
        $ret[intval (empty ($pref_vars[$field_name]))][$field]
          = $GLOBALS[$field];
    }
  return $ret;
}

function update_groups ($upd_list, $group_id)
{
  if (!$upd_list)
    return 0;

  return !db_autoexecute (
   'groups', $upd_list, DB_AUTOQUERY_UPDATE, "group_id = ?", [$group_id]
  );
}

function update_prefs ($upd_pref, $group_id)
{
  return !group_set_preference ($group_id, $upd_pref);
}

function update_db_redirect ($error)
{
  global $group;
  $err = intval ($error);
  $fb = _("Update successful.");
  if ($err)
    $fb = _("Update failed.");
  $fb = rawurlencode ($fb);
  $fb = "&feedback=$fb&error=$err";
  session_redirect ("{$_SERVER['PHP_SELF']}?group=$group$fb");
}

#FIXME: feeds the database with default values... instead of checkbox,
# it should be select boxes "default/activated/deactivated".
function update_db ()
{
  global $grp, $group_id;
  normalize_updated_vars ();

  $cases = get_cases ($GLOBALS['names']);
  list ($upd_pref, $upd_list) = get_update_list ($grp, $cases);
  if (empty ($upd_list) &&  empty ($upd_pref))
    {
      fb (_("Nothing to update."));
      return;
    }
  group_add_history ('Changed Activated Features', '', $group_id);
  $error = update_groups ($upd_list, $group_id)
    || update_prefs ($upd_pref, $group_id);
  update_db_redirect ($error);
}

$grp = group_get_object ($group_id);
if ($update)
  update_db ();

site_project_header (
  ['title' => _("Select Features"),'group' => $group_id, 'context' => 'ahome']
);

function next_td ($increment = 0)
{
  static $i = 0;
  if ($increment)
    $i++;
  else
    print ' <td class="' . utils_altrow ($i) . '">';
}
function close_td () { print "</td>\n"; }

function specific_line_label ($artifact, $explanation, $increment)
{
  if ($increment)
    next_td (1);
  print "<tr>\n";
  next_td ();
  print html_label ("use_$artifact", $explanation) . "</td>\n";
}

function specific_line_checkbox ($artifact, $use)
{
  next_td ();
  # Print the checkbox to de/activate it
  # (viewcvs cannot be activated or deactivated, they are not in the menu).
  if (str_match ("viewcvs", $artifact))
    print "---";
  else
    print form_checkbox ("use_$artifact", $use);
  close_td ();
}

function specific_line_url ($artifact)
{
  next_td ();
  if (!str_match ("extralink", $artifact))
    {
      $art_url = group_get_artifact_url ($artifact);
      print "<a href=\"$art_url\">$art_url</a>";
    }
  close_td ();
}

function specific_line_modify_url ($artifact)
{
  global $grp;
  next_td ();

  $tail = "</td>\n</tr>\n";
  if (!$grp->CanModifyUrl ($artifact))
    {
      print "---" . $tail;
      return;
    }
  if ($artifact == "homepage" || $artifact == "download"
      || str_match ("viewcvs", $artifact) || str_match ("extralink", $artifact))
    $url = $grp->getUrl ($artifact);
  else
    $url = $grp->getArtifactUrl ($artifact);
  $url = utils_specialchars_decode ($url, ENT_QUOTES);
  $extra = 'size="20" title="' . _("Alternative Address") . '"';
  print form_input ("text", "url_$artifact", $url, $extra) . $tail;
}

function specific_line ($artifact, $explanation, $use, $increment = 1)
{
  specific_line_label ($artifact, $explanation, $increment);
  specific_line_checkbox ($artifact, $use);
  specific_line_url ($artifact);
  specific_line_modify_url ($artifact);
}

print '<p>';
print
  _("You can activate or deactivate feature for your group. In some cases, "
    . "depending on the system administrator's choices, you can "
    . "even use change the URL for a feature. If the field "
    . "&ldquo;alternative address&rdquo; is empty, the standard is used.");
print "</p>\n";

print form_header () . form_hidden (["group_id" => $group_id]);

print html_build_list_table_top (
  [
    _("Feature, Artifact"), _("Activated"),
    _("Standard Address"), _("Alternative Address")
  ]
);

if ($grp->CanUse ("homepage"))
  {
    specific_line ("homepage", _("Homepage"), $grp->Uses ("homepage"));
    specific_line ("cvs_viewcvs_homepage",
      _("Homepage Source Code Web Browsing"), 0, 0
    );
  }

if ($grp->CanModifyUrl ("extralink_documentation"))
  specific_line ("extralink_documentation", _("Documentation"),
    $grp->Uses("extralink_documentation")
  );

function specific_can_use ($grp, $artifact, $explanation)
{
  if ($grp->CanUse ($artifact))
    specific_line ($artifact, $explanation, $grp->Uses ($artifact));
}
specific_can_use ($grp, "download", _("Download Area"));

if ($grp->CanUse ("download") && $grp->CanModifyDir("download_dir"))
  {
    print '<tr>';
    next_td (1);
    next_td ();
    print _("Download Area Directory");
    close_td ();
    next_td ();
    print "---";
    $close_td ();
    next_td ();
    print $grp->getTypeDir("download");
    $close_td ();
    next_td ();
    print ' '
      . form_input (
          "text", "dir_download", $grp->getDir ("download"), 'size="20"'
        );
    print "</td>\n</tr>\n";
  }

if ($grp->CanUse ("cvs") || $grp->CanUse ("homepage"))
  {
    specific_line ("cvs", _("CVS"), $grp->Uses ("cvs"));
    specific_line ("cvs_viewcvs", _("CVS Web Browsing"), 0, 0);
  }
foreach (
  [
    'arch' => [_("GNU Arch"), _("Arch Web Browsing")],
    'svn' => [_("Subversion"), _("Subversion Web Browsing")],
    'git' => [_("Git"), _("Git Web Browsing")],
    'hg' => [_("Mercurial"), _("Mercurial Web Browsing")],
    'bzr' => [_("Bazaar"), _("Bazaar Web Browsing")]
  ] as $vcs => $labels
)
  if ($grp->CanUse ($vcs))
    {
      specific_line ($vcs, $labels[0], $grp->Uses ($vcs));
      specific_line ("{$vcs}_viewcvs", $labels[1], 0, 0);
    }

foreach (
  [
    "mail" => _("Mailing Lists"), "forum" => _("Forum"), "news" => _("News"),
    'cookbook' => _('Cookbook'),
    "support" => _("Support Tracker"), "bugs" => _("Bug Tracker"),
    "task" => _("Task Tracker"), "patch" => _("Patch Tracker"),
  ] as $k => $v
)
  specific_can_use ($grp, $k, $v);

print "</table>\n";
print form_footer ();
site_project_footer ();
?>
