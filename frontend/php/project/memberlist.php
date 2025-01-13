<?php
# List members.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Free Software Foundation, Inc.
# Copyright (C) 2000-2006 Lorenzo Hernandez Garcia-Hierro <lorenzohgh--tuxedo-es.org>
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

require_once ('../include/init.php');
require_once ('../include/form.php');
require_directory ("trackers");

function is_squad ($m)
{
  return $m['admin_flags'] === MEMBER_FLAGS_SQUAD;
}

# Return lists of members sorted by the onduty flag
# [[(active members)], [(inactive members)]].
function fetch_member_list ($group_id)
{
  $flag_select = '';
  foreach (['admin', 'bugs', 'task', 'patch', 'news', 'support'] as $f)
    $flag_select .= " g.{$f}_flags,";
  $res = db_execute ("
    SELECT
      u.user_name, u.user_id, u.realname, u.add_date, u.people_view_skills,
      u.email,$flag_select g.onduty
    FROM user u JOIN user_group g ON u.user_id = g.user_id
    WHERE g.group_id = ? AND g.admin_flags <> ? ORDER BY u.user_name",
    [$group_id, MEMBER_FLAGS_PENDING]
  );

  $members = [0 => [], 1 => []];
  while ($row = db_fetch_array ($res))
    {
      $od = $row['onduty']? 1: 0;
      $members[$od][] = $row;
    }
  return $members;
}

# Return string listing specific roles of given member.
function expand_roles ($m)
{
  # TRANSLATORS: these roles are explained in
  # html.php:html_member_explain_roles.
  $roles = [
    1 => _("technician"), 3 => _("manager"), 2 => _("techn. & manager")
  ];
  # TRANSLATORS: these strings are combined
  # with (technician|manager|techn. & manager)
  $trackers = ['support' => _("support tracker"), 'bugs' => _("bug tracker"),
    'task' => _("task tracker"), 'patch' => _("patch tracker"),
    'news' => _("news tracker")
  ];
  $ret = '';
  foreach ($trackers as $art => $title)
    {
      $idx = $m[$art . '_flags'];
      if (isset ($roles[$idx]))
        $ret .= sprintf (
          _('<!-- tracker -->%1$s <!-- role -->%2$s'), $title, $roles[$idx])
          . "<br />\n";
    }
  if ($ret === '')
    return '&nbsp;';
  return $ret;
}

function role_cell ($m, $is_admin)
{
  if ($is_admin)
    return _("project admin");
  return expand_roles ($m);
}

function print_in_td ($s)
{
  print "\t\t<td align=\"middle\">$s</td>\n";
}

function role_icon ($flags, $group_id)
{
  global $sys_group_id;
  if ($flags == MEMBER_FLAGS_ADMIN)
    {
      if ($group_id == $sys_group_id)
        return ["site-admin",  _("Site Administrator")];
      return ["project-admin", _("Group administrator")];
    }
  if ($flags == MEMBER_FLAGS_SQUAD)
    return ["people", _("Squad")];
  return ["project-member", _("Group member")];
}

function print_role_icon ($m, $group_id)
{
  list ($icon, $icon_alt) = role_icon ($m['admin_flags'], $group_id);

  print "\t\t<td><span class='help' title=\"$icon_alt\">"
    . html_image ("roles/$icon.png", ['alt' => $icon_alt, 'class' => 'icon'])
    . "</span></td>\n";
}

function resume_cell ($m, $sys_home)
{
  if (is_squad ($m))
    return '&nbsp;';
  if ($m['people_view_skills'] != 1)
    # TRANSLATORS: this is a label shown when user's skills
    # are unavailable.
    return _("Set to private");
  return "<a href=\"{$sys_home}people/resume.php?user_id={$m['user_id']}\">"
    . _("View Skills") . "</a>";
}

function watched_link ($this_user, $m, $group_id, $sys_home)
{
  if (is_squad ($m))
    return '&nbsp;';
  $is_watched = trackers_data_is_watched ($this_user, $m['user_id'], $group_id);
  if ($m['user_id'] == $this_user || $is_watched)
    return "---";
  return "<a href=\"{$sys_home}my/groups.php?"
    . "func=addwatchee&amp;group_id=$group_id&amp;watchee_id="
    . $m['user_id'] . "\">" . _("Watch partner") . "</a>";
}

extract (sane_import ('get', ['digits' => "detailed"]));
extract (sane_import ('request', ['digits' => "form_grp"]));

if ((!$group_id) && $form_grp)
  $group_id = $form_grp;

site_project_header (
  [
    'title' => _("Group memberlist"), 'group' => $group_id,
    'context' => 'people'
  ]
);

$this_user = user_getid ();
$approved_member = member_check ($this_user, $group_id);
print '<p>';
if ($approved_member)
  print
    _("Note that you can &ldquo;watch&rdquo; a member of your\nproject. "
      . "It allows you, for instance, to be the backup of someone when "
      . "they are\naway from the office, or to review all their activities "
      . "on this project: you\nwill receive a copy of their mail "
      . "notifications related to this\nproject.");
elseif (member_check_pending ($this_user, $group_id))
  printf (_("Your request for inclusion in this group is pending."));
else
  printf (
    _("If you would like to contribute to this project by\nbecoming a member, "
      . "use the <a href=\"%s\">request for inclusion</a> form."),
    "{$sys_home}my/groups.php?words=" . group_getname ($group_id)
    . '#searchgroup'
  );
print "</p>\n";

member_explain_roles ();
print '<p>'
  . _("On this page are only presented specific roles, roles\nwhich "
      . "are not attributed by default when joining this project.")
  . "</p>\n";

$form_opening = form_tag (['method' => 'get'], "#options");
$form_submit = '<input class="bold" type="submit" value="'
  . _("Apply") . '" />';
$selector = '<select title="' . _("basic or detailed")
  # TRANSLATORS: this is used in context of "Browse with the %s memberlist."
  . form_option ('0', $detailed, _("basic"))
  # TRANSLATORS: this is used in context of "Browse with the %s memberlist."
  . form_option ('1', $detailed, _("detailed")) . "</select>\n"
  . form_hidden (['group' => $group]);
# TRANSLATORS: the argument is "basic" or "detailed".
print html_show_displayoptions (
  sprintf (_("Browse with the %s memberlist."), $selector),
  $form_opening, $form_submit
);

$members = fetch_member_list ($group_id);
$title_arr = ["&nbsp;", _("Member")];
if ($detailed)
  $title_arr[] = _("Specific Role");
$title_arr[] = _("Resume and Skills");
if ($approved_member)
  $title_arr[] = _("Watch");

$duty_titles = [
  _('Currently inactive members'),  _('Active members on duty')
];
foreach ([1, 0] as $onduty)
  {
    print html_h (2, $duty_titles[$onduty]);
    print html_build_list_table_top ($title_arr);

    $mem = $members[$onduty];
    $cnt = count ($mem);
    for ($i = 0; $i < $cnt; $i++)
      {
        $m = $mem[$i];
        $color = utils_altrow ($i);
        $is_admin = $m['admin_flags'] == MEMBER_FLAGS_ADMIN;
        if ($is_admin)
          $color = "boxhighlight";
        print "\n\t<tr class=\"$color\">\n";
        print_role_icon ($m, $group_id);
        print "\t\t<td>" . utils_user_link ($m['user_name'], $m['realname'])
          . "</td>\n";
        if ($detailed)
          print_in_td (role_cell ($m, $is_admin));

        print_in_td (resume_cell ($m, $sys_home));
        if ($approved_member)
          print_in_td (watched_link ($this_user, $m, $group_id, $sys_home));
        print "\t<tr>\n";
      }
    print "\t</table>\n";
  } # foreach ([1, 0] as $onduty)

if ($project->getGPGKeyring ())
  {
    print '<p>';
    printf (
      _("You may also be interested in the <a href=\"%s\">GPG Keys of\n"
        . "all members</a>"),
      "memberlist-gpgkeys.php?group=$group"
    );
    print "</p>\n";
  }
site_project_footer ([]);
?>
