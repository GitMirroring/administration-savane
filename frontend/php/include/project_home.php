<?php
# Group homepage.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2008 Aleix Conchillo Flaque
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry (#devtools anchor for "Source code")
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

require_directory ("people");
require_directory ("news");
require_directory ("stats");
foreach (['vars', 'vcs', 'utils', 'member'] as $i)
  require_once (dirname (__FILE__) . "/$i.php");

# If we are at wrong url, redirect.
$host = $project->getTypeBaseHost ();
if (
    $host && !$sys_debug_nobasehost
    && strcasecmp ($_SERVER['HTTP_HOST'], $host)
)
  {
    $prot = session_protocol () . '://';
    session_redirect ("$prot$host{$_SERVER['PHP_SELF']}");
  }
$project = group_get_object ($group_id);
site_project_header ([]);

# Members of this group (little box on the right).
$res_admin = member_admin_flags_query (
  $group_id, '= ? AND onduty = 1', MEMBER_FLAGS_ADMIN
);
print "\n<div class='indexright'>\n";
print $HTML->box_top (html_h (2, _("Membership Info")), "", 1);
function start_div ($j)
{
  print "<div class=\"" . utils_altrow ($j) . '"><span class="smaller">';
};
function end_div ()
{
  print "</span></div>\n";
};
$j = 1;
function print_group_admins ($res_admin)
{
  global $j;
  $adminsnum = db_numrows ($res_admin);
  if (!$adminsnum)
    return;
  start_div ($j++);
  print $adminsnum < 2? _("Group Admin:"): _("Group Admins:");
  end_div ();
  print "\n<ul class='group-page-admins'>\n";
  while ($row_admin = db_fetch_array ($res_admin))
    {
      print "<li>";
      start_div ($j++);
      print utils_link (
        "/users/{$row_admin['user_name']}", $row_admin['realname']
      );
      end_div ();
      print "</li>\n";
    }
  print "</ul>\n";
}
print_group_admins ($res_admin);

# Count of members on this group.
$membersnum = db_fetch_array (db_execute ("
  SELECT COUNT(*) AS count FROM user_group
  WHERE group_id = ? AND admin_flags NOT IN (?, ?) AND onduty = 1",
  [$group_id, MEMBER_FLAGS_PENDING, MEMBER_FLAGS_SQUAD]
));

$membersnum = $membersnum['count'];

start_div ($j++);
printf (
  ngettext ("%s active member", "%s active members", $membersnum),
  "<b>$membersnum</b>"
);
end_div ();

# If member = 1, it's obviously (or it should be) the group admin.
# If there's no admin, we need to get access to the list.
# But we show it anyway: this page can be used for request for membership,
# provide more info that the little infobox.
start_div ($j++);

print '['
  . utils_link ("/project/memberlist.php?group=$group", _("View Members"))
  . ']';
end_div ();
print $HTML->box_bottom (1);
print "<br />\n";
print $HTML->box_top (html_h (2, _("Group identification")), "", 1);
$j = 0;

$item_arr = [
   # TRANSLATORS: the group id (a number) will follow.
  _("Id:") => $group_id,
  _("System Name:") => $group,
  _("Name:") => $project->GetName (),
  _("Group Type:") => $project->getTypeName ()
];

foreach ($item_arr as $key => $val)
  {
    start_div ($j++);
    print "$key <b>$val</b>";
    end_div ();
  }
print $HTML->box_bottom (1) . "<br />\n";

if (search_has_group_anything_to_search ($group_id))
  {
    print $HTML->box_top (html_h (2, _("Search in this group")));
    print '<span class="smaller">' . search_box () . '</span>';
    print $HTML->box_bottom ();
  }

print "\n</div><!-- end indexright -->\n<div class='indexcenter'>\n";

# General Information.
if ($project->getTypeDescription ())
  print '<p>' . $project->getTypeDescription () . "</p>\n";

if ($project->getLongDescription ())
  print markup_full (utils_specialchars ($project->getLongDescription ()));
else
  {
    if ($project->getDescription ())
      print "<p>" . $project->getDescription () . "</p>\n";
    else
      {
        print '<p>';
        printf (
          _("This group hasn't submitted a short description yet. "
            . "You can <a\nhref=\"%s\">submit it</a> now."),
          "/project/admin/editgroupinfo.php?group=$group"
        );
        print "</p>\n";
      }
  }

print '<p>' . _("Registration Date:") . ' '
  . utils_format_date ($project->getStartDate ()) . "\n";

if ($project->CanUse ("license"))
  {
    $license = $project->getLicense ();
    print "<br />\n" . _("License:") . ' ';
    if (!empty ($LICENSE_URL[$license]))
      print utils_link ($LICENSE_URL[$license], $LICENSE[$license]);
    else
      {
        if (!empty ($LICENSE[$license]))
          print $LICENSE[$license];
        else
          print _("License is unknown!");
        if ($license == "other")
          print " - " . $project->getLicense_other ();
      }
  }

if ($project->CanUse ("devel_status"))
  {
    $devel_status = $project->getDevelStatus ();
    print "<br />\n" . _("Development Status:") . ' '
      . $DEVEL_STATUS[$devel_status] . "\n";
  }
print "</p>\n</div><!-- end indexcenter -->\n<p class='clearr'>&nbsp;</p>\n";

if ($project->Uses ("news"))
  {

    print "\n<div class='splitright'>";

    print
      $HTML->box_top (
        html_h (2, _("Latest News") . "&nbsp;"
          . "<a href='/news/atom.php?group=$group' "
          . "class='inline-link'><img alt='rss feed' "
          . "src='/images/common/feed16.png' /></a>"
        )
      );
    print news_show_latest ($group_id, 4);
    print $HTML->box_bottom ();

    print "\n</div><!-- end splitright -->\n<div class='splitleft'>\n";
  }

$odd = 0;
$even = 1;
function proj_home_img ($ctx)
{
  $img_attr = ['width' => '24', 'height' => '24', 'alt' => ''];
  return html_image ("contexts/admin.png", $img_attr) . '&nbsp;';
}
if ($sys_group_id == $group_id && member_check (0, $group_id, MEMBER_FLAGS_ADMIN))
  {
    require "$sys_www_topdir/include/features_boxes.php";
    print $HTML->box_top (
      # TRANSLATORS: the argument is site name (like Savannah).
      html_h (2, sprintf (_("Administration: %s server"), $sys_name))
    );
    print '<div class="justify">';
    # TRANSLATORS: the argument is site name (like Savannah).
    printf (
      _("Since you are administrator of this group, which one is\n"
        . "the &ldquo;system group,&rdquo; you are administrator of the "
        . "whole %s server."),
      $sys_name
    );
    print "</div>\n";

    print $HTML->box_nextitem (utils_altrow ($odd));
    $img = proj_home_img ("contexts/admin.png");
    print utils_link (
      "/siteadmin/", $img . _("Server Main Administration Page")
    );
    print $HTML->box_nextitem (utils_altrow ($even));
    print utils_link (
      "/task/?group={$sys_unix_group_name}"
      . '&amp;category_id=1&amp;status_id=1&amp;set=custom',
      $img . _("Pending Group List")
    );
    $reg_count = stats_get_pending_groups ();
    print ' ';
    printf (
      ngettext (
         "(%s registration pending)", "(%s registrations pending)", $reg_count
      ),
      "<b>$reg_count</b>"
    );
    print $HTML->box_bottom ();
    print "<br />\n";
  }

if (member_check (0, $group_id, MEMBER_FLAGS_ADMIN))
  {
    print $HTML->box_top (
      # TRANSLATORS: the argument is group name (like GNU Coreutils).
      html_h (2, sprintf (_("Administration: %s group"), $project->getName()))
    );
    print '<div class="justify">'
      . _("As administrator of this group, you can manage members and\n"
          . "activate, deactivate and configure tools used in your group.")
      . "</div>\n";

    print $HTML->box_nextitem (utils_altrow ($odd));
    $img = proj_home_img ("contexts/main.png");
    print utils_link (
      "/project/admin/?group=$group",
       $img . _("Group Main Administration Page")
    );
    print $HTML->box_bottom();
    print "<br />\n";
  }

# Public areas.
function specific_makesep ()
{
  # Too specific to be general function.
  global $i, $HTML;
  static $j = 0;
  $j_prev = $j;
  $j = $i;
  if ($i <= $j_prev)
    return;
  print $HTML->box_nextitem (utils_altrow ($i));
}

print $HTML->box_top (html_h (2, _("Quick Overview")));
$i = 1;

if ($project->Uses ("homepage")
    && $project->getUrl ("homepage") != 'http://'
    && $project->getUrl ("homepage") != '')
  {
    $img = proj_home_img ("misc/www.png");
    print utils_link (
      $project->getUrl ("homepage"), $img . _("Group Homepage")
    );
    $i++;
  }

if ($project->Uses ("download"))
  {
    specific_makesep ();

    # The pointer is always the filelist, this page will handle redirect
    # appropriately in case that no download area is here.
    print utils_link (
      $project->getArtifactUrl ("files"),
      proj_home_img ("contexts/download.png") . _("Download Area")
    );
    $i++;
  }

# Cookbook Documentation (internal).
# Groups don't have the choice to use it, as there maybe site recipe that
# applies to features they use.
# FIXME: this should print the number of recipes available.
specific_makesep ();
if ($project->Uses ("extralink_documentation"))
  {
    $cb_url = $project->getArtifactUrl ("cookbook");
    $extra_link = $project->getUrl ("extralink_documentation");
    $img = proj_home_img ("contexts/man.png") . _("Docs");
    if ($extra_link)
      {
        # The group has an external doc? Print it first. See pagemenu.php
        # for explanations about this.
        print $img;
        print "<ul><li>"
          . utils_link ($extra_link, _("Browse docs (external to Savane)"));
        print "</li>\n<li>" . utils_link ($cb_url, _("Browse the cookbook"));
        print "</li>\n<ul>\n";
      }
    else
      print utils_link ($cb_url, $img);
    $i++;
  }

specific_makesep ();
print utils_link(
  "/project/memberlist.php?group=$group",
  proj_home_img ("contexts/people.png") . _("Memberlist")
);

print ' ';
printf (
  ngettext ("(%s member)", "(%s members)", $membersnum),
  "<b>$membersnum</b>"
);
$i++;

if (group_get_preference ($group_id, 'gpg_keyring'))
  {
    specific_makesep ();
    print utils_link (
      "/project/release-gpgkeys.php?group=$group",
      proj_home_img ("contexts/keys.png") . _("Group release GPG keyring")
    );
    $i++;
  }

print $HTML->box_bottom ();
print "<br />\n";

function open_vs_total_items ($url, $group_id, $artifact)
{
  $res_count = db_execute ("
    SELECT count(*) AS count FROM $artifact
    WHERE group_id = ? AND status_id != 3",
    [$group_id]);
  $row_count = db_fetch_array($res_count)['count'];
  $open_num = "<b>$row_count</b>";
  $res_count = db_execute (
    "SELECT count(*) AS count FROM $artifact WHERE group_id = ?",
    [$group_id]
  );
  $row_count = db_fetch_array($res_count)['count'];
  $total_num = "<b>$row_count</b>";
  print ' ';
  # TRANSLATORS: the arguments are numbers of items.
  printf (_('(open items: %1$s, total: %2$s)'), $open_num, $total_num);

  print "<ul>\n<li>"
    . utils_link ("$url&amp;func=browse&amp;set=open", _("Browse open items"));
  print "</li>\n<li>"
    . utils_link (
       "$url&amp;func=additem", _("Submit a new item"),
       0, group_restrictions_check ($group_id, $artifact)
      );
  print "</li>\n</ul>\n";
}

$job_num = people_project_jobs_rows ($group_id);

if ($sys_unix_group_name == $group
  || $project->Uses ("support") || $project->Uses ("mail")
  || $job_num)
  {
    $i = 0; specific_makesep (); $i++;
    print $HTML->box_top (html_h (2, _("Communication Tools")));

    if ($project->Uses ("support"))
      {
        specific_makesep ();
        $url = $project->getArtifactUrl("support");

        print utils_link (
          $url, proj_home_img ("contexts/help.png") . _("Tech Support Manager")
        );
        if (group_get_artifact_url ("support", 0) == $url)
          open_vs_total_items ($url, $group_id, 'support');
        $i++;
      }

    if ($project->Uses ("mail"))
      {
        specific_makesep ();
        $url = $project->getArtifactUrl ("mail");
        print utils_link (
          $url, proj_home_img ("contexts/mail.png") . _("Mailing Lists")
        );
        $res_count = db_execute ("
          SELECT count(*) AS count FROM mail_group_list
          WHERE group_id = ? AND is_public = 1",
          [$group_id]
        );
        $row_count = db_fetch_array ($res_count)['count'];
        print " ";
        printf (
          ngettext (
            "(%s public mailing list)", "(%s public mailing lists)", $row_count
           ),
           "<b>$row_count</b>"
        );
        $i++;
      }

    if ($job_num)
      {
        specific_makesep ();
        print utils_link (
          "/people/?group=$group",
          proj_home_img ("contexts/people.png")
          . _("This group is looking for people")
        ) . ' ';
        printf (
          ngettext (
            "(%s contributor wanted)", "(%s contributors wanted)", $job_num
          ),
          "<b>$job_num</b>"
        );
        $i++;
      }
    print $HTML->box_bottom ();
    print "<br />\n";
  }

function print_scm_entry ($group, &$i, $scm, $scm_name)
{
  if (!($group->Uses ($scm) || $group->UsesForHomepage ($scm)))
    return;

  $group_id = $group->getGroupId ();

  specific_makesep ();
  $url = $group->getArtifactUrl ($scm);

  print proj_home_img ("contexts/cvs.png") . "<a href=\"$url\">";
  # TRANSLATORS: the argument is name of VCS (like Git or Bazaar).
  printf (_("%s Repository"), $scm_name);
  print "</a>\n<ul>\n";
  $admin_url = pagemenu_vcs_admin_url ($group, $scm);
  if ($admin_url != '')
    print "<li><a href=\"$admin_url\">" . _("Administer") . "</a></li>\n";

  $scm_url = $group->getUrl ("{$scm}_viewcvs");
  if (
    $group->Uses ($scm) && $scm_url != 'http://' && $scm_url != ''
  )
    {
      $repos = vcs_get_repos ($scm, $group_id);
      $n = count ($repos);
      if ($n < 2)
        print "<li><a href=\"$scm_url\">"
          . _("Browse Sources Repository") . "</a></li>\n";
      else
        {
          print '<li>' . _("Browse Sources Repository") . "\n</li>\n";
          print vcs_compile_repo_ul ($repos, $scm_url) . "\n";
        }
    }
  $view_url = pagemenu_vcs_web_browse_url ($group, $scm);
  if ($view_url != '')
    print "<li><a href=\"$view_url\">"
      . _("Browse Web Pages Repository") . "</a></li>\n";
  print "</ul>\n";
  $i++;
} # print_scm_entry
# Development.
$uses_dev = false;
foreach (['patch', 'cvs', 'homepage', 'bugs', 'task', 'patch'] as $art)
  if ($project->Uses ($art))
    {
      $uses_dev = true;
      break;
    }
if ($uses_dev)
  {
    $i = 0; specific_makesep (); $i++;
    print $HTML->box_top (
      "<div id='devtools'>" . html_h (2, _("Development Tools")) . "</div>"
    );
    $i = 1;


    $vcses = [
      # TRANSLATORS: the string is used as the argument of "%s Repository".
      'git' => _("Git"), 'hg' => _("Mercurial"), 'bzr' => _("Bazaar"),
      'svn' => _("Subversion"), 'arch' => _("GNU Arch"), 'cvs' => _("CVS"),
    ];
    foreach ($vcses as $v => $label)
      print_scm_entry ($project, $i, $v, $label);

    $tracker_arr = [
      'bugs' => ['bug', _("Bug Tracker")],
      'task' => ['task', _("Task Manager")],
      'patch' => ['patch', _("Patch Manager")],
    ];
    foreach ($tracker_arr as $k => $val)
      if ($project->Uses ($k))
        {
          specific_makesep ();
          $url = $project->getArtifactUrl ($k);
          $img = proj_home_img ("contexts/{$val[0]}.png");
          print utils_link ($url,  $img . $val[1]);
          if (group_get_artifact_url ($k, 0) == $url)
            open_vs_total_items ($url, $group_id, $k);
          $i++;
        }
    print $HTML->box_bottom();
  } # $uses_dev

if ($project->Uses ("news"))
  print "\n</div><!-- end splitleft -->\n";
site_project_footer ([]);
?>
