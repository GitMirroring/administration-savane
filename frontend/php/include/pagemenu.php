<?php
# Page-specific menu (Bugs/Tasks/Admin/Source Code/...)
#
# Copyright (C) 1999, 2000 The SourceForge Crew (was in layout.php)
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2008 Aleix Conchillo Flaque
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry (tiny reordering, downcasing,
#   #devtools)
# Copyright (C) 2013, 2014, 2017-2023 Ineiev
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

# Note about submenus: they should always contain a verb, enlightening the
# action they permit to do.
# The related pages the submenus point to should have title that are the
# same as the submenu, or almost.

require_once (dirname (__FILE__) . "/vcs.php");

# Menu specific to the current page: group if group page, my if my pages etc.
function pagemenu ($params)
{
  global $group_id;
  # Skip topmenu if passed as parameter.
  if (isset ($params['notopmenu']) && $params['notopmenu'])
    return;

  # Reset important variables.
  unset ($GLOBALS['stone_age_submenu'],
    $GLOBALS['stone_age_menu_lastcontext']);
  $GLOBALS['submenucount'] = 0;

  print '<h1 class="toptitle">'
    . html_image ('contexts/' . context_icon () . '.orig.png',
        [ 'width' => '48', 'height' => '48', 'alt' => context_alt (),
           'class' => 'pageicon']
      );
  $title = context_title ();
  if ($title)
    print $title;
  if ($title && !empty ($params['title']))
    print
      # TRANSLATORS: this string is used to separate context from
      # further description, like _("Bugs")._(": ").$bug_title.
      _(": ");
  if (!empty ($params['title']))
    print $params['title'];
  print "</h1>\n\n";

  # Print topmenu subtitle.
  unset ($scope);
  switch (CONTEXT)
    {
    case 'my': $scope = _("My"); break;
    case 'siteadmin': $scope = _("Site Admin"); break;
    default:
      if (isset ($group_id))
        $scope = _("Group");
      else
        $scope = _("Site Wide");
    }
  print "<div class='topmenu' id='topmenu'>\n"
    . "<span class='topmenutitle' title=\"";
  # TRANSLATORS: the argument is context like "My", "Group", "Site Admin",
  # "Site wide", "Submenu".
  printf (_("%s Scope"), $scope);
  print "\"> $scope\n</span><!-- end topmenutitle -->\n";

  $have_context = false;
  switch (CONTEXT)
    {
    case 'my': case 'siteadmin': $have_context = true;
    }
  if (isset ($group_id))
    {
      $project = project_get_object ($group_id);
      if (!$project->isError ())
        $have_context = true;
    }
  if ($have_context)
    print "<div class='topmenuitem'><ul id='topmenuitem'>\n";
  # Call the relevant menu.
  switch (CONTEXT)
    {
    case 'my': pagemenu_my (); break;
    case 'siteadmin': pagemenu_siteadmin (); break;
    default:
      if (isset ($group_id))
        pagemenu_group ();
    }
  if ($have_context)
    print  "</ul></div><!-- end topmenuitem -->\n";
  print "</div><!-- end pagemenu -->\n";
  # Add the stone age submenu if relevant.
  if (!empty ($GLOBALS['stone_age_menu'])
    && !empty ($GLOBALS['stone_age_submenu'])
  )
    {
      $scope = _("Submenu");
      # TRANSLATORS: the argument is context like "My", "Group", "Site Admin",
      # "Site wide", "Submenu".
      print "<!--stone age submenu begin --><br />\n"
        . "<div class='topmenu' id='topmenu'>\n"
        . "<span class='topmenutitle' title=\"";
      printf (_("%s Scope"), $scope);
      print "\">\n$scope</span><!-- end topmenutitle -->\n"
        . "<div class='topmenuitem'><ul id='topmenuitem'>\n"
        . $GLOBALS['stone_age_submenu']
        . "</ul></div><!-- end topmenuitem -->\n</div>\n"
        . "<!-- end stone age subemenu -->\n";
    }

  # Here we do something quite strange to avoid an overlap of the menu:
  # We add two divs, one in float right, the other with clear right.
  # Ideally, only a clear left would have done trick, but it does not
  # because the menu is a float left like the menu.
  # This is required for Mozilla and Konqueror.  Please, don't change that.
  print "<div id='topmenunooverlap'>&nbsp;</div>\n"
    . "<div id='topmenunooverlapbis'>&nbsp;</div>\n";
}

function pagemenu_submenu_class ()
{
  return 'topmenuitemmainitem';
}

# Column title.
function pagemenu_submenu_title (
  $title, $url, $selected = 0, $available = 1, $help = ''
)
{
  global $submenucount, $stone_age_menu, $stone_age_menu_lastcontext;
  $submenucount++;
  $class = $selected? "tabselect": "tabs";

  # If we use the stone age menu, we need to be able to determine later
  # the current submenu.
  # As the current code was not planned to be forced to make context guessing
  # for submenus, we are forced to do it in a quite awkward way.
  if  (!empty ($stone_age_menu))
    list ($stone_age_menu_lastcontext, $ign) = context_guess_from_url ($url);

  $li_class = pagemenu_submenu_class ();
  # We make appear the submenu with both CSS and JavaScript.  That is because
  # some browsers (MSIE) have poor CSS support and cannot do it otherwise.
  # (When it gains focus, the submenu appears.)
  if (empty ($url))
    {
      $ret = "$title\n";
      $li_class .= " sublist";
    }
  else
    $ret = utils_link ($url, $title, $class, $available, $help);
  return "  <li class='$li_class'>\n$ret";
}

function pagemenu_submenu_end ()
{
  return "  </li><!-- end " . pagemenu_submenu_class () . " -->\n\n";
}

function pagemenu_submenu_body ($txt)
{
  global $submenucount, $stone_age_menu;
  global $stone_age_submenu, $stone_age_menu_lastcontext;
  # Stone age menu got submenu in a new menu line below, just like if there
  # was two menus.
  # So when asked to print the content, we determine if this is the content
  # that is supposed to show up in the submenu line (as there is only one
  # submenu, it means that the only submenu available is the one of the
  # current content) and if it is the case, we save it a global to be used
  # later (that was unset at the begin of this page).
  if ($stone_age_menu)
    {
      if ($stone_age_menu_lastcontext == CONTEXT)
        $stone_age_submenu = $txt;
      return;
    }
  return "<ul id='submenu{$submenucount}' "
    . "class='topmenuitemsubmenu'>$txt\n</ul><!-- end submenu -->\n";
}

function pagemenu_submenu_entry ($title, $url, $available = 1, $help = "")
{
  $class = "topmenuitemsubmenu";

  if ($GLOBALS['stone_age_menu'])
    $class = pagemenu_submenu_class ();
  if (empty ($url))
    $ret = $title;
  else
    $ret = utils_link ($url, $title, '', $available, $help);
  return "<li class=\"$class\">$ret</li>\n";
}

function pagemenu_submenu_entry_separator ()
{
  if ($GLOBALS['stone_age_menu'])
    return "<br />\n";
  return "<li class='topmenuitemsubmenuseparator'>&nbsp;</li>\n";
}

# Menu specific to My pages.
function pagemenu_my ()
{
  global $sys_home;
  $url = "{$sys_home}my";
  $titles = [
    [ _("Incoming Items"), "$url/", 'browsing', _("What's new for me?")],
    [ _("Items"), "$url/items.php", 'items',
      _("Browse my items (bugs, tasks, bookmarks...)")],
  ];
  if (user_use_votes ())
    $titles[] = [_("Votes"), "$url/votes.php", 'votes',
      _("Browse items I voted for")];

  $titles[] = [_("Group Membership"), "$url/groups.php", 'groups',
    _("List the groups I belong to")];

  if (user_get_preference ("use_bookmarks"))
    $titles[] = [_("Bookmarks"), "$url/bookmarks.php", 'bookmarks',
      _("List my bookmarks")];

  $titles[] = [_("Account Configuration"), "$url/admin/", 'configure',
    _("Account configuration: authentication, cosmetics preferences...")];

  foreach ($titles as $t)
    {
      print pagemenu_submenu_title ($t[0], $t[1],
        SUBCONTEXT == $t[2], 1, $t[3]);
      print pagemenu_submenu_end ();
    }
}

function pagemenu_tracker_submenu ($project, $tracker, $title, $help)
{
  if (!$project->Uses ($tracker))
    return;

  print pagemenu_submenu_title ($title, $project->getArtifactUrl ($tracker),
    CONTEXT == $tracker, 1, $help);

  # Only add submenu list when the URL wasn't customized.
  if ($project->url_is_default ($tracker))
    print pagemenu_submenu_body (pagemenu_group_trackers ($tracker));
  print pagemenu_submenu_end ();
}

# Submenu for group admins.
function pagemenu_group_admin ($root, $gr_n)
{
  global $sys_home, $group_id;
  $ret = pagemenu_submenu_entry_separator ();
  $titles = [
    '/admin/' => '<strong>' . _("Administer:") . '</strong>',
    '/admin/editgroupinfo.php' => _("Edit public info"),
    '/admin/editgroupfeatures.php' => _("Select features"),
    '/admin/useradmin.php' => _("Manage&nbsp;members"),
    '/admin/squadadmin.php' => _("Manage&nbsp;squads"),
    '/admin/userperms.php' => _("Set&nbsp;permissions"),
    '/admin/editgroupnotifications.php' => _("Set&nbsp;notifications"),
    '/admin/history.php' => _("Show&nbsp;history"),
    '/admin/conf-copy.php' => _("Copy&nbsp;configuration"),
  ];
  foreach ($titles as $u => $t)
    $ret .= pagemenu_submenu_entry ($t, "$root$u$gr_n");
  $root = $sys_home . "people";
  $ret .=
    pagemenu_submenu_entry (_("Post&nbsp;jobs"),
      "$root/createjob.php$gr_n", 1, _("Post a request for contribution")
    )
    . pagemenu_submenu_entry (_("Edit jobs"), "$root/editjob.php$gr_n", 1,
        _("Edit previously posted request for contribution")
      );
  return $ret;
}

function pagemenu_url_is_set ($group, $artifact)
{
  $url = $group->getUrl ($artifact);
  return $url != 'http://' && $url != '';
}

function pagemenu_test_url ($group, $artifact)
{
  return $group->Uses ($artifact) && pagemenu_url_is_set ($group, $artifact);
}

function pagemenu_vcs_use_entry ($group, $vcs, $vcs_name)
{
  $url = $group->getArtifactUrl ($vcs);
  return pagemenu_submenu_entry (
    # TRANSLATORS: the argument is VCS name (like Git or Bazaar).
    sprintf (_("Use %s"), $vcs_name), $url, 1,
    # TRANSLATORS: the argument is VCS name (like Git or Bazaar).
    sprintf (_("%s Repository"), $vcs_name)
  );
}

function pagemenu_vcs_browse_entry ($group, $vcs, $name)
{
  $group_id = $group->getGroupId ();
  $a_idx = $vcs . '_viewcvs';
  if (!($group->Uses ($vcs) && pagemenu_url_is_set ($group, $a_idx)))
    return '';
  $repos = vcs_get_repos ($vcs, $group_id);
  $n = count ($repos);
  $scm_url = $group->getUrl ($a_idx);
  if (count ($repos) < 2)
    {
      $title = sprintf (_("Browse %s repository"), $name);
      return pagemenu_submenu_entry ($title, $scm_url);
    }
  $title = sprintf (_("Browse %s repositories"), $name);
  $ret = pagemenu_submenu_entry ("<span>$title</span>", null);
  $ret .= "\n" . vcs_compile_repo_ul ($repos, $scm_url) . "\n";
  return $ret;
}

function pagemenu_vcs_web_browse_url ($group, $vcs)
{
  if (!pagemenu_url_is_set ($group, "cvs_viewcvs_homepage"))
    return '';
  if ($vcs == 'cvs')
    $have_entry = $group->Uses ('homepage');
  else
    $have_entry = $group->UsesForHomepage ($vcs);
  if (!$have_entry)
    return '';
  return $group->getUrl ("cvs_viewcvs_homepage");
}

function pagemenu_vcs_web_browse_entry ($group, $vcs, $name)
{
  $url = pagemenu_vcs_web_browse_url ($group, $vcs);
  if ($url == '')
    return '';
  return pagemenu_submenu_entry (_("Browse Web Pages Repository"), $url);
}

function pagemenu_vcs_admin_url ($group, $vcs)
{
  if (!member_check (0, $group->getGroupId (), 'A'))
    return '';
  $url = $group->get_vcs_admin_url ($vcs);
  if ($url === null)
    return '';
  return $url;
}

function pagemenu_vcs_admin_entry ($group, $vcs, $name)
{
  $url = pagemenu_vcs_admin_url ($group, $vcs);
  if ($url == '')
    return $url;
  return pagemenu_submenu_entry (_("Administer"), $url);
}

function pagemenu_vcs_append_entry ($func, $group, $vcs, $name, &$count)
{
  $ent = $func ($group, $vcs, $name);
  if ($ent != '')
    $count++;
  return $ent;
}

function pagemenu_vcs_entry ($group, &$count, $vcs, $name)
{
  $ret = '';
  foreach (['use', 'admin', 'browse', 'web_browse'] as $f)
    {
      $func = "pagemenu_vcs_{$f}_entry";
      $ret .= pagemenu_vcs_append_entry ($func, $group, $vcs, $name, $count);
    }
  return $ret;
}

# Menu specific to Group pages.
function pagemenu_group ()
{
  global $group_id, $sys_group_id, $project, $sys_home, $sys_name;
  $url = $sys_home . 'project';
  $is_admin = member_check (0, $group_id, 'A');

  $project = project_get_object ($group_id);
  if ($project->isError ())
    return;
  $unix_name = $project->getUnixName ();
  $uname = "{$url}s/$unix_name/";
  $gr_n = "?group=$unix_name";
  print pagemenu_submenu_title (_("Main"), $uname,
    CONTEXT == 'project', 1,
    # TRANSLATORS: the argument is site name like Savannah.
    sprintf (_("Project Main Page at %s"), $sys_name)
  );
  $ret = pagemenu_submenu_entry (_("Main"), $uname)
    . pagemenu_submenu_entry (_("View members"), "$url/memberlist.php$gr_n")
    . pagemenu_submenu_entry (_("Search"), "$url/search.php$gr_n");

  if ($is_admin)
    $ret .= pagemenu_group_admin ($url, $gr_n);
  print pagemenu_submenu_body ($ret);
  print pagemenu_submenu_end ();

  if (pagemenu_test_url ($project, "homepage"))
    {
      print pagemenu_submenu_title ( _("Homepage"),
        $project->getUrl("homepage"),
        0, 1, _("Browse project homepage (outside of Savane)")
      );
      print pagemenu_submenu_end ();
    }

  if ($project->Uses ("download"))
    {
      print pagemenu_submenu_title (_("Download"),
        $project->getArtifactUrl ("files"), CONTEXT == 'download', 1,
        _("Visit download area: files released")
      );
      print pagemenu_submenu_end ();
    }

  # The cookbook is the default and cannot be deactivated as it contains
  # site docs useful for the project depending on the used features.
  #
  # However, if external doc is set, the link will have no effect
  # (See pagemenu_group_trackers() for more details about the document menu
  # behavior).
  $u = $project->getArtifactUrl ("cookbook");
  if ($project->Uses ("extralink_documentation"))
    $u = '#';

  if ($project->getUrl ("extralink_documentation"))
    {
      print pagemenu_submenu_title (_("Docs"), $u, CONTEXT == 'cookbook', 1,
        _("Docs: Cookbook, etc")
      );
      print pagemenu_submenu_body (pagemenu_group_trackers ("cookbook"));
      print pagemenu_submenu_end ();
    }


  pagemenu_tracker_submenu ($project, "support", _("Support"),
    _("Tech Support Tracker: post, search and manage support requests"));

  # Fora are normally deprecated on savane.
  pagemenu_tracker_submenu ($project, "forum", _("Forum"), "");

  if ($project->Uses ('mail'))
    {
      print pagemenu_submenu_title ( _("Mailing lists"),
        $project->getArtifactUrl ("mail"), CONTEXT == 'mail', 1,
        _("List existing mailing lists")
      );
      if ($is_admin)
        {
          $u = $sys_home . "mail";
          $ret =
            pagemenu_submenu_entry (
              _("Browse"), "$u/$gr_n", _("List existing mailing lists")
            )
            . pagemenu_submenu_entry_separator ()
            . pagemenu_submenu_entry (
                '<strong>' . _("Configure:") . '</strong>',
                "$u/admin/$gr_n"
              );
          print pagemenu_submenu_body ($ret);
        }
      print pagemenu_submenu_end ();
    }

  $count = 0;
  # TRANSLATORS: this string is used as argument in messages 'Use %s'
  # and '%s Repository'.
  $vcses = ['cvs' => _('CVS'), 'svn' => _('Subversion'),
   'arch' => _('GNU Arch'), 'git' => _('Git'), 'hg' => _('Mercurial'),
   'bzr' => _('Bazaar')];
  $last_vcs = '';
  foreach ($vcses as $vcs => $t)
    {
      $have_vcs[$vcs] = false;
      if ($project->Uses ($vcs) || $project->UsesForHomepage ($vcs))
        {
          $have_vcs[$vcs] = true;
          $count++;
          $last_vcs = $vcs;
        }
    }
  if ($count)
    {
      if ($count == 1)
        # Only one SCM - direct link.
        print pagemenu_submenu_title (_("Source code"),
          $project->getArtifactUrl ($last_vcs), CONTEXT == $last_vcs, 1,
          _("Source code management")
        );
      else
        print pagemenu_submenu_title (_("Source code"), "$uname#devtools",
          isset ($vcses[CONTEXT]), 1, _("Source code management")
        );

      $ret = [];
      $count = 0;

      foreach ($vcses as $v => $t)
        if ($have_vcs[$v])
          $ret[] = pagemenu_vcs_entry ($project, $count, $v, $t);

      # Add a submenu only if there is more than one item.
      if ($ret && $count > 1)
        print pagemenu_submenu_body (
          join (pagemenu_submenu_entry_separator (), $ret)
        );
      print pagemenu_submenu_end ();
    } # if ($count)

  pagemenu_tracker_submenu ($project, "bugs", _("Bugs"),
    _("Bug Tracker: report, search and track bugs"));

  pagemenu_tracker_submenu ($project, "task", _("Tasks"),
    _("Task Manager: post, search and manage tasks"));

  pagemenu_tracker_submenu ($project, "patch", _("Patches"),
    _("Patch Manager: post, search and manage patches"));

  if (!$project->Uses ("news"))
    return;
  $news = $sys_home . 'news';
  print pagemenu_submenu_title (_("News"), "$news/$gr_n", CONTEXT == 'news', 1,
    _("Read latest News, post News")
  );
  $ret = pagemenu_submenu_entry (_("Browse"), "$news/$gr_n");
  $ret .= pagemenu_submenu_entry (_("Atom feed"), "$news/atom.php$gr_n");
  $ret .= pagemenu_submenu_entry (_("Submit"), "$news/submit.php$gr_n",
    group_restrictions_check ($group_id, "news")
  );
  $ret .= pagemenu_submenu_entry (_("Manage"), "$news/approve.php$gr_n",
    member_check (0, $group_id, "N3")
  );
  if ($is_admin)
    {
      $ret .= pagemenu_submenu_entry_separator ()
        . pagemenu_submenu_entry (
            '<strong>' . _("Configure") . '</strong>', "$news/admin/$gr_n",
            1, _("News Manager: edit notifications")
          );
    }
  print pagemenu_submenu_body ($ret);
  print pagemenu_submenu_end ();
}

# Menu specific to tracker pages.
function pagemenu_group_trackers ($tracker)
{
  global $project, $group_id, $sys_group_id, $sys_home;

  $is_admin = member_check (0, $group_id, 'A');
  $ret = '';
  $root = "$sys_home$tracker";
  $gr_n = "?group=" . $project->getUnixName ();
  if (in_array ($tracker, ["bugs", "support", "patch", "task"]))
    {
      $ret .= pagemenu_submenu_entry (_("Submit new"),
        $project->get_artifact_url ($tracker, 'additem'),
        group_restrictions_check ($group_id, $tracker)
      );

      $ret .= pagemenu_submenu_entry (_("Browse"),
        $project->get_artifact_url ($tracker)
      );

      $ret .= pagemenu_submenu_entry (_("Reset to open"),
        $project->get_artifact_url ($tracker, 'browse&amp;set=open')
      );

      $ret .= pagemenu_submenu_entry(_("Digest"),
        $project->get_artifact_url ($tracker, 'digest')
      );

      $ret .= pagemenu_submenu_entry (_("Export"),
        $project->get_artifact_url ($tracker, '', 'export.php'),
        member_check (0, $group_id)
      );

      $ret .= pagemenu_submenu_entry (_("Get statistics"),
        $project->get_artifact_url ($tracker, '', 'reporting.php')
      );

      # At the end of the submenu, for cohesion with the "search" in the
      # menu that is also at the end.
      $ret .= pagemenu_submenu_entry (_("Search"),
        $project->get_artifact_url ($tracker, 'search')
      );
    }
  elseif ($tracker == "cookbook")
    {
      # Quite similar to other trackers, the cookbook have some specific
      # links.

      # If there are external docs (extra link), consider them prior
      # to the cookbook: if the users use two doc tool, there is no
      # reason to consider the external less important than the Savane,
      # at the contrary, we can assume that they made the choice to
      # use another one for good reasons and we do not have to enforce
      # anything at this point.
      if ($project->Uses ("extralink_documentation"))
        {
          $ret .=
            pagemenu_submenu_entry (_("Browse (External to Savane)"),
              $project->getUrl ("extralink_documentation"), 1,
              _("Browse Documentation that is located outside of Savane")
            )
            .  pagemenu_submenu_entry_separator ();
        }

      $ret .= pagemenu_submenu_entry (_("Browse"),
        $project->get_artifact_url ($tracker)
      );

      $ret .= pagemenu_submenu_entry (_("Submit"),
        $project->get_artifact_url ($tracker, 'additem', 'edit.php'),
        group_restrictions_check ($group_id, $tracker)
      );

      $ret .= pagemenu_submenu_entry (_("Edit"),
        $project->get_artifact_url ($tracker, 'browse', 'edit.php'),
        group_restrictions_check ($group_id, $tracker)
      );

      $ret .= pagemenu_submenu_entry (_("Digest"),
        $project->get_artifact_url ($tracker, 'digest', 'edit.php'),
        _("Digest recipes")
      );

      $ret .= pagemenu_submenu_entry (_("Export"),
        $project->get_artifact_url ($tracker, '', 'export.php'),
        member_check (0, $group_id)
      );
      # At the end of the submenu, for cohesion with the "search" in the
      # menu that is also at the end.
      $ret .= pagemenu_submenu_entry (_("Search"),
        $project->get_artifact_url ($tracker, 'search')
      );
      # If it is the site admin project, link to savane-doc.
      if ($group_id == $sys_group_id)
        $ret .= pagemenu_submenu_entry_separator ()
          . pagemenu_submenu_entry (
              _("Savane In Depth Guide"), "{$sys_home}maintenance/back-page/"
            );
    } # ($tracker == "cookbook")

  if (!$is_admin)
    return $ret;

  $ret .= pagemenu_submenu_entry_separator ()
    . pagemenu_submenu_entry ('<strong>' . _("Configure:") . '</strong>',
        "$root/admin/$gr_n"
      )
    . pagemenu_submenu_entry (_("Select fields"),
       "$root/admin/field_usage.php$gr_n", 1,
       _("Define what fields you want to use in this tracker")
      )
    . pagemenu_submenu_entry (_("Edit field values"),
        "$root/admin/field_values.php$gr_n", 1,
        _("Define the set of possible values for the fields you have "
          . "decided to use in\nthis tracker")
      )
    . pagemenu_submenu_entry (_("Edit query forms"),
        "$root/admin/editqueryforms.php$gr_n", 1,
        _("Define project-wide query form: what search criteria to use "
          . "and what item\nfields to show in the query form table")
      )
    . pagemenu_submenu_entry (_("Set&nbsp;permissions"),
       "$root/admin/userperms.php$gr_n", 1,
       _("Define posting restrictions")
      )
    . pagemenu_submenu_entry (_("Set&nbsp;notifications"),
        "$root/admin/notification_settings.php$gr_n"
      )
    . pagemenu_submenu_entry (_("Copy&nbsp;configuration"),
       "$root/admin/conf-copy.php$gr_n", 1,
        _("Copy the configuration of another tracker")
      )
    . pagemenu_submenu_entry (_("Other settings"),
        "$root/admin/other_settings.php$gr_n", 1,
        _("Modify the preamble shown on the item submission form")
      );
  return $ret;
}

# Menu specific to the site admin pages; no i18n.
function pagemenu_siteadmin ()
{
  global $sys_home, $group_name, $sys_unix_group_name;
  $root = $sys_home . "siteadmin";
  print pagemenu_submenu_title ("Configuration", "root/?func=configure",
    SUBCONTEXT == 'configure'
  );
  $titles = [
    "$root/retestconfig.php" => "Test System Configuration",
    "$root/group_type.php" => "Configure Group Types",
    "{$sys_home}people/admin/" => "Configure People Area"];
  $txt = "";
  foreach ($titles as $u => $t)
    $txt .= pagemenu_submenu_entry ($t, $u);
  print pagemenu_submenu_body ($txt);
  print pagemenu_submenu_end ();

  print pagemenu_submenu_title ("Management", "$root/?func=manage",
    SUBCONTEXT == 'manage'
  );
  # If the current page shows a group edition page, add extra links.
  $extralinks = '';
  if (SUBCONTEXT == 'manage' && !empty ($group_name))
    {
      $root = $sys_home . "project/admin";
      $gr_n = "?group=$group_name";
      $titles = [
        ["#" => "<strong>Currently Shown Project:</strong>"],
        ["$root/$gr_n" => "Administer"],
        ["$root/editgroupinfo.php$gr_n" => "Edit Public Info"],
        ["$root/editgroupfeatures.php$gr_n" => "Select Features"],
        ["$root/useradmin.php$gr_n" => "Manage Members"],
        ["$root/history.php$gr_n" => "Show History"]
      ];
      $extralinks = pagemenu_submenu_entry_separator ();
      foreach ($titles as $u => $t)
        $extalinks .= pagemenu_submenu_entry ($t, $u);
    }
  $uname = "?group=$sys_unix_group_name";
  $txt =
    pagemenu_submenu_entry ("Pending registrations",
      "{$sys_home}task/$uname&amp;category_id=1"
      . "&amp;status_id=1&amp;go_report=Apply"
    )
    . pagemenu_submenu_entry ("Approve news",
       "{$sys_home}news/approve.php$uname"
      )
    . pagemenu_submenu_entry_separator ()
    . pagemenu_submenu_entry ("Group list", "$root/grouplist.php")
    . pagemenu_submenu_entry ("User list", "$root/userlist.php");
  print pagemenu_submenu_body ("$txt$extralinks");
  print pagemenu_submenu_end ();
  print pagemenu_submenu_title ("Monitoring", "$root/?func=monitor",
    SUBCONTEXT == 'monitor'
  );
  $txt = pagemenu_submenu_entry ("Monitor spam", "$root/spamlist.php")
    . pagemenu_submenu_entry ("Check last logins", "$root/lastlogins.php")
    . pagemenu_submenu_entry ("Anonymous posts",
        "$root/usergroup.php?user_id=100"
      );
  print pagemenu_submenu_body ($txt);
  print pagemenu_submenu_end ();
}
?>
