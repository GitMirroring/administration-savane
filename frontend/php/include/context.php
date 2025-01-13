<?php
# Compute context for given URL.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2008 Aleix Conchillo Flaque
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

require_once (dirname (__FILE__) . '/group.php');

# Guess (and set!) the context of the current page.
function context_guess ()
{
  list ($cont, $subcont) =
    context_guess_from_url ($_SERVER['SCRIPT_NAME']);
  context_set ($cont, $subcont);
}

function context_guess_project_context ($page_basename, $func)
{
  # If we are in project, we need to look at the actuel script pagename
  # as it may gives subcontext details.
  # This is because we want to print very short title for this specific
  # part of the interface, breaking the principle of having generic context
  # and page subtitles added after semicolon.
  $subs = [ 'search.php' => 'search', 'memberlist.php' => 'members',
    'memberlist-gpgkeys.php' => 'keys', 'release-gpgkeys.php' => 'keys'
  ];
  if (array_key_exists ($page_basename, $subs))
    return $subs[$page_basename];
  return null;
}

function context_guess_my_context ($page_basename, $func)
{
  # If we are in my, we need to look at the actuel script pagename
  # to find out the subcontext.
  # This is because we want to print very short title for this specific
  # part of the interface, breaking the principle of having generic context
  # and page subtitles added after semicolon.
  $subs = ['bookmarks.php', 'items.php', 'groups.php', 'votes.php'];
  if (!in_array ($page_basename, $subs))
    return null;
  $sub = str_replace ('.php', '', $page_basename);
  return $sub;
}

function context_guess_siteadmin_context ($page_basename, $func)
{
  $arr = [ 'configure' => ["group_type.php", "retestconfig.php"],
    'manage' =>
      ["grouplist.php", "groupedit.php", "userlist.php", "usergroup.php"],
    'monitor' => ["spamlist.php", "lastlogins.php"]
  ];
  foreach ($arr as $ctx => $list)
    if (in_array ($page_basename, $list))
      return $ctx;
  if (isset ($func))
    return $func;
  return 'browsing';
}

function context_guess_item_context ($page_basename, $func, $context)
{
  # If we are in usual trackers, try to guess the action (subcontext)
  # from the arguments passed in the request.
  # We want to know if the guy is:
  #   - posting new items
  #   - editing items / posting comments
  #   - doing searches
  #   - doing configuration
  # This is relevant if ARTIFACT has been already defined, which means
  # we are for sure in trackers pages.
  if (!(defined ('ARTIFACT') && $context != "admin"))
    return null;
  if ($func == 'additem')
    return [$context, 'postitem'];
  if ($func == 'detailitem')
    return [$context, 'edititem'];
  if ($func == 'search')
    return [$context, 'search'];
  return null;
}

# Get the context given the URL. For best efficiency, this function will
# return guessed values as soon as possible.
# As context should be always available in pages, it will be set as constants.
function context_guess_from_url ($page)
{
  # By default, we consider that the action, called subcontext, is browsing.
  # Only trackers allows actions that are not browsing or configuration.
  $subcontext = "browsing";

  # Obtain the name of the current page.
  $page_basename = basename ($page);

  # Try a first guess of the context.
  $context = basename (dirname ($page));

  # If context is projects, it is actually a the project/ index page
  # that is available at the URL localhost/projects/thisgroup.
  if ($context == "projects")
    return ['project', $subcontext];
  extract (sane_import ('request', ['funcs' => 'func']));
  foreach (['project', 'my', 'siteadmin'] as $f)
    if ($context == $f)
      {
        $fnc = "context_guess_{$f}_context";
        $ret = $fnc ($page_basename, $func);
        if ($ret !== null)
          return [$context, $ret];
      }
  $ret = context_guess_item_context ($page_basename, $func, $context);
  if ($ret !== null)
    return $ret;
  if ($context != 'admin')
    return [$context, $subcontext];
  # If we are in admin pages, we need to go deeper to find the appropriate
  # main context.
  $subcontext = 'configure';

  # If ARTIFACT has been defined, we are in a tracker configuration
  # for sure.  Otherwise, we have to go deeper.
  if (defined ('ARTIFACT'))
    {
      $context = ARTIFACT;
      return [$context, $subcontext];
    }
  $context = basename (dirname (dirname ($page)));
  return [$context, $subcontext];
}

# Define context.
function context_set ($context, $subcontext)
{
  if (!defined ('CONTEXT'))
    define ('CONTEXT', $context);
  define ('SUBCONTEXT', $subcontext);
}

function context_cont_list_title ()
{
  global $sys_name;
  $titles = [
    'siteadmin' => _("Site Administration"), 'cvs' => _("CVS Repositories"),
    'arch' => _("GNU Arch Repositories"), 'bzr' => _("Bazaar Repositories"),
    'svn' => _("Subversion Repositories"), 'git' => _("Git Repositories"),
    'hg' => _("Mercurial Repositories"), 'searchingroup' => _("Search"),
    # TRANSLATORS: the argument is site name (like Savannah).
    'people' => sprintf (_("People at %s"), $sys_name),
    'forum' => _("News")
  ];

  if (array_key_exists (CONTEXT, $titles))
    return $titles[CONTEXT];
  return null;
}

# Titles for trackers with 'configure' subcontext option.
function context_cont_conf_title ()
{
  $titles = [
    'download' => [_("Filelist Administration"), _("Filelist")],
    'cookbook' => [_("Cookbook Administration"), _("Cookbook")],
    'support' => [_("Support Tracker Administration"), _("Support")],
    'bugs' => [_("Bug Tracker Administration"), _("Bugs")],
    'task' => [_("Task Manager Administration"), _("Tasks")],
    'patch' => [_("Patch Manager Administration"), _("Patches")],
    'news' => [_("News Manager Administration"), _("News")],
    'mail' => [_("Mailing Lists Administration"), _("Mailing Lists")],
  ];
  if (!array_key_exists (CONTEXT, $titles))
    return null;
  if (SUBCONTEXT == 'configure')
    return $titles[CONTEXT][0];
  return $titles[CONTEXT][1];
}

function context_project_context_title ()
{
  switch (SUBCONTEXT)
    {
    case 'configure': return _("Administration Summary");
    case 'search': return _("Search in this Group");
    }
  return _("Summary");
}

function context_my_context_title ()
{
  $titles = [ 'configure' => _("My Account Configuration"),
    'items' => _("My Items"), 'votes' => _("My Votes"),
    'groups' => _("My Group Membership"), 'bookmarks' => _("My Bookmarks")
  ];
  if (array_key_exists (SUBCONTEXT, $titles))
    return $titles[SUBCONTEXT];
  return _("My Incoming Items");
}

# Return title for contexts that need arbitrary functions
# (possibly including side effects).
function context_cont_custom_title ()
{
  $titles = ['project', 'my'];

  if (!in_array (CONTEXT, $titles))
    return null;
  $func = "context_" . CONTEXT . "_context_title";
  return $func ();
}

# Get title depending on the context.
function context_title ()
{
  global $group_id;
  $title = null;
  if (defined ('CONTEXT'))
    foreach (['list', 'conf', 'custom'] as $f)
      {
        $func = "context_cont_{$f}_title";
        $title = $func ();
        if ($title !== null)
          break;
      }

  if (isset ($group_id))
    {
      $group = project_get_object ($group_id);
      $title = sprintf ("%s - %s", $group->getPublicName (), $title);
    }
  return $title;
}

function context_alt ()
{
  $alt_texts = [
    # TRANSLATORS: this is website context.
    'admin' => _('admin'), 'people' => _('people'),
    'preferences' => _('preferences'), 'desktop' => _('desktop'),
    'directory' => _('directory'),
    # TRANSLATORS: this is website context (GPG keys).
    'keys' => _('keys'),
    'main' => _('main'), 'bug' => _('bug'), 'man' => _('man'),
    # TRANSLATORS: this is website context (support).
    'help' => _('help'),
    'mail' => _('mail'), 'task' => _('task'),
    # TRANSLATORS: this is website context (VCS).
    'cvs' => _('cvs'),
    'news' => _('news'), 'patch' => _('patch'), 'download' => _('download'),
    'directory' => _('directory')
  ];
  $icon = context_icon ();
  if (in_array ($icon, $alt_texts))
    return $alt_texts[$icon];
  return $alt_texts['main'];
}

# Return icon name that only depends on CONTEXT (or null if this is not
# the case).  Icon names that coincide with CONTEXT are handled
# in context_icon () directly.
function context_cont_icon ()
{
  $icon_list = [
    'admin' => ['siteadmin'], 'bug' => ['bugs'], 'support' => ['help'],
    'man' => ['doc', 'cookbook'], 'search' => ['directory'],
    'cvs' => ['cvs', 'arch', 'svn', 'git', 'hg', 'bzr'],
    'news' => ['forum', 'news', 'special'],
  ];
  foreach ($icon_list as $k => $v)
    if (in_array (CONTEXT, $v))
      return $k;
  return null;
}

# Return icon that may depend both on CONTEXT and SUBCONTEXT.
function context_sub_icon ()
{
  $sub_icons = [
    'my' => ['groups' => 'people', 'configure' => 'preferences',
      null => 'desktop'],
    'project' => ['search' => 'directory', 'members' => 'people',
      'keys' => 'keys', 'configure' => 'preferences', null => 'main'
    ]
  ];
  if (!array_key_exists (CONTEXT, $sub_icons))
    return 'main';
  $sub = $sub_icons[CONTEXT];
  if (array_key_exists (SUBCONTEXT, $sub))
    return $sub[SUBCONTEXT];
  return $sub[null];
}

# Return icon name depending on the context.
function context_icon ()
{
  if (in_array (CONTEXT, ['mail', 'task', 'patch', 'download', 'people']))
    return CONTEXT;
  $ret = context_cont_icon ();
  if ($ret !== null)
    return $ret;
  return context_sub_icon ();
}
?>
