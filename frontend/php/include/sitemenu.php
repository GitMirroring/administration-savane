<?php
# Sitewide menu.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
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

require_once (dirname (__FILE__) . '/html.php');
# Search tools, frequently needed.
require_directory ('search');

function menu_print_sidebar ($params)
{
  return sitemenu ($params);
}

function sitemenu ($params)
{
  global $HTML, $sys_home, $sys_logo_name, $sys_name;

  # Define variables.
  if (!isset ($params['title']))
    $params['title'] = '';
  if (!isset ($params['toptab']))
    $params['toptab'] = '';
  if (!isset ($params['group']))
    $params['group'] = '';

  print "\n<ul class='menu'>\n<li class='menulogo'>\n";

  # TRANSLATORS: the argument is site name (like Savannah).
  $alt_arg = sprintf (_("Back to %s Homepage"), $sys_name);
  $img = html_image ($sys_logo_name, ['alt' => $alt_arg]);
  print "          ";
  if ($sys_logo_name)
    print  utils_link ($sys_home, $img);
  else
    print "<br />\n" . utils_link ($sys_home, $sys_name, 0, 1, $alt_arg);

  print "\n</li><!-- end menulogo -->\n";

  if (user_isloggedin ())
    print menu_loggedin ($params['title'], $params['toptab'], $params['group']);
  else
    print menu_notloggedin ();
  print menu_thispage ($params['title'], $params['toptab'], $params['group']);
  print menu_search ();
  if (user_can_be_super_user ())
    print menu_site_admin ();
  print menu_projects ();
  print menu_help ();
  utils_get_content ("menu");
  print "\n</ul><!-- end menu -->\n";
}

# Extract some context information that could be necessary to provide links
# that need to keep such context.
function sitemenu_extraurl ($only_with_post = false)
{
  global $goup_name, $item_id;
  # If only_with_post is set, it means that we will return nothing if the
  # page was loaded with a get.
  if ($only_with_post && $_SERVER["REQUEST_METHOD"] != "POST")
    return '';

  $extraurl = '';
  if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
      if (!empty($group_name))
        $extraurl .= "&amp;group=" . utils_specialchars ($group_name)
          . "&amp;";
      if (!empty ($item_id))
        $extraurl .= "&amp;func=detailitem&amp;item_id="
          . utils_specialchars ($item_id) . "&amp;";
      return $extraurl;
    }
  $query_string = '';
  if (!empty ($_SERVER['QUERY_STRING']))
    $query_string = $_SERVER['QUERY_STRING'];
  if (!empty ($item_id) && ctype_digit (strval ($query_string)))
    {
      # Short link case (like /bugs/?212).
      $extraurl .= "&amp;func=detailitem&amp;item_id="
        . utils_specialchars ($item_id);
      return $extraurl;
    }
  $extraurl = utils_specialchars ($query_string);
  $extraurl = str_replace ("reload=1&amp;", "", $extraurl);
  $extraurl = "&amp;$extraurl";
  return $extraurl;
}

function menu_site_admin ()
{
  return sitemenu_site_admin ();
}

# Menu entry for all admin tasks when logged as site admin.
function sitemenu_site_admin ()
{
  # This menu is shown whether the user is a logged as super user or not.
  global $HTML, $sys_home, $sys_unix_group_name;
  $is_su = user_is_super_user ();
  $HTML->menuhtml_top (no_i18n ("Site Administration"));
  $HTML->menu_entry ("{$sys_home}siteadmin/", no_i18n ("Main page"), $is_su);
  $HTML->menu_entry ("{$sys_home}task/?group=$sys_unix_group_name"
    . '&amp;category_id=1&amp;status_id=1&amp;set=custom#results',
    no_i18n ("Pending registrations")
  );
  $HTML->menuhtml_bottom ();
}

function menu_search ()
{
  return sitemenu_search ();
}

function sitemenu_search ()
{
  global $HTML, $group_id;
  $HTML->menuhtml_top (_("Search"));
  print '       <li class="menusearch">';
  print search_box("", "sitewide");
  print '       </li><!-- end menusearch -->';
  $HTML->menuhtml_bottom ();
}

function menuhtml_top ($string)
{
  return sitemenuhtml_top ($string);
}

# Deprecated - theme wrapper.
function sitemenuhtml_top ($title)
{
  # Use only for the topmost menu.
  theme_menuhtml_top ($title);
}

function menu_projects ()
{
  return sitemenu_projects ();
}

# Hosted projects.
function sitemenu_projects ()
{
  global $HTML, $sys_home, $sys_name;
  $HTML->menuhtml_top (_("Hosted Projects"));
  $HTML->menu_entry ("{$sys_home}register/requirements.php",
    _("Hosting requirements"));
  $HTML->menu_entry("{$sys_home}register/", _("Register New Project"), 1,
    # TRANSLATORS: the argument is site name (like Savannah).
    sprintf (_("Register your project at %s"), $sys_name)
  );
  $HTML->menu_entry (
    "{$sys_home}search/index.php?type_of_search=soft&amp;words=%%%",
    _("Full List"), 1, _("Browse the full list of hosted projects")
  );
  $HTML->menu_entry ("{$sys_home}people/", _("Contributors Wanted"),
    1, _("Browse the list of request for contributions")
  );
  $HTML->menu_entry ("{$sys_home}stats/", _("Statistics"), 1,
    # TRANSLATORS: the argument is site name (like Savannah).
    sprintf (_("Browse statistics about %s"), $sys_name)
  );
  $HTML->menuhtml_bottom ();
}

function menu_thispage ($page_title, $page_toptab = 0, $page_group = 0)
{
  return sitemenu_thispage ($page_title, $page_toptab, $page_group);
}

# Page-specific toolbox.
function sitemenu_thispage ($page_title, $page_toptab = 0, $page_group = 0)
{
  global $HTML, $sys_group_id, $group_id, $sys_home, $locale_names;

  $HTML->menuhtml_top (_("This Page"));

  if (count ($locale_names) > 1)
    {
      $extraurl = sitemenu_extraurl (true);
      if ($extraurl)
        $extraurl = "?$extraurl";
      $HTML->menu_entry ("{$sys_home}i18n.php?lang_uri="
        . utils_urlencode ($_SERVER['REQUEST_URI'] . $extraurl),
        _("Language"), 1, _("Choose website language")
      );
    }
  $extraurl = sitemenu_extraurl ();

  $extra_name = '';
  if (isset ($GLOBALS['extra_script_name']))
    $extra_name = $GLOBALS['extra_script_name'];

  $script_extra = $_SERVER['SCRIPT_NAME'] . $extra_name;
  $HTML->menu_entry ("$script_extra?reload=1" . $extraurl,
    _("Clean Reload"), 1,
    _("Reload the page without risk of reposting data")
  );
  $HTML->menuhtml_bottom ();

  if (empty ($_POST))
    {
      if (user_isloggedin () && user_get_preference ("use_bookmarks"))
        {
          $bookmark_title = utils_urlencode (context_title ());
          if ($page_title)
            # TRANSLATORS: this string is used to separate context from
            # further description, like _("Bugs")._(": ").$bug_title.
            $bookmark_title .= utils_urlencode (_(": ") . $page_title);

            $HTML->menu_entry ("{$sys_home}my/bookmarks.php?add=1&amp;url="
              . utils_urlencode ($_SERVER['REQUEST_URI']) . '&amp;title='
              . $bookmark_title,
              _("Bookmark It"), 1, _("Add this page to my bookmarks")
            );
        }
    }

  # Show related recipes. Maybe not the best way to put it, but in "this page"
  # it makes sense.
  # And it is hard to find a place elsewhere where it would not be really nasty.
  $sql_role = $sql_groupid = '';
  $params_groupid = [];
  if ($group_id)
    {
      # We are on a group page.
      $sql_groupid = "OR group_id = ?";
      $params_groupid = [$group_id];
    }
  if (defined('ARTIFACT') && AUDIENCE == 'members')
    {
      # We are on a tracker and we have a project member:
      #  - it may be a manager or a technician, or both
      # We must select
      #  - items for all members
      #  + items for manager if we have a manager
      #  + items for technicians if we have a technician
      # Which leads to
      #  allmembers=1 OR (manager=1 if manager) OR (technician=1 if technician).
      $flag = member_create_tracker_flag (ARTIFACT) . '1';
      if (member_check (0, $group_id, $flag))
        {
          # It is a technician.
          $sql_role = "OR audience_technicians = '1'";
        }
      $flag = member_create_tracker_flag (ARTIFACT) . '3';
      if (member_check (0, $group_id, $flag))
        {
          # It is a manager.
          $sql_role .= " OR audience_managers = '1'";
        }
    }

  # If CONTEXT or SUBCONTEXT was set to non-existent context, the SQL
  # should not fail - so error reporting works in other usages of db_query.
  $result = db_query ("DESCRIBE cookbook_context2recipe");
  $valid_contexts = [];
  while ($row = db_fetch_array ($result))
    $valid_contexts[] = $row['Field'];

  $context = 'context_' . CONTEXT;
  $subcontext = 'subcontext_ ' . SUBCONTEXT;
  $audience = 'audience_' . AUDIENCE;
  if (in_array ($context, $valid_contexts)
      && in_array ($subcontext, $valid_contexts))
    {
      $result = db_execute ("
        SELECT recipe_id FROM cookbook_context2recipe
        WHERE
          (group_id = ? $sql_groupid) AND $context = '1' AND $subcontext = '1'
          AND ($audience='1' $sql_role)",
        array_merge ([$sys_group_id], $params_groupid)
      );
      $rows = db_numrows ($result);
    }
  else
    # No recipe found? End here.
    return;

  # Put a limit on the number of shown recipe to 25.
  $limit = 25;

  # Build a sql to obtain summaries.
  $sql_privateitem = $sql_itemid = '';
  $params_itemid = [];
  # Check whether the user is authorized to read private items for the active
  # project, if there is an active project.
  if ($group_id)
    if (!member_check_private (0, $group_id))
      $sql_privateitem = "AND privacy <> '2'";

  for ($i = 0; $i < $rows; $i++)
    {
      if ($sql_itemid)
        $sql_itemid .= " OR ";
      $sql_itemid .= "bug_id = ?";
      $params_itemid[] = db_result ($result, $i, 'recipe_id');
    }

  $rows = 0;
  if ($sql_itemid)
    {
      $result = db_execute ("
        SELECT bug_id, priority, summary FROM cookbook
        WHERE ($sql_itemid) AND resolution_id = '1'
        $sql_privateitem ORDER BY priority DESC, summary ASC LIMIT ?",
        array_merge ($params_itemid, [$limit]));
      $rows = db_numrows ($result);
    }

  # No recipe found? End here.
  # Such test has been made before, but before we did not knew if the item
  # was actually approved.
  if ($rows < 1)
    return;

  print "\n";
  print '<li class="relatedrecipes">';
  print '<div id="relatedrecipes">'
    . html_image ('contexts/help.png', ['alt=' => "icon"])
    . _("Related Recipes:") . "</div>\n";
  for ($i = 0; $i < $rows; $i++)
    {
      print '<div class="relatedrecipesitem">';
      # Show specific background color only for high priority item, no
      # need to disturb the eye otherwise.
      $priority = db_result ($result, $i, 'priority');
      if ($priority > 4)
        print '<div class="priore">';

      # The full summary will only be in a help balloon, the summary directly
      # shown will be cut to 40 characters.
      # Summaries should be kept short.
      print utils_link (
        "{$sys_home}cookbook/?func=detailitem&amp;comingfrom=$group_id"
        . '&amp;item_id=' . db_result ($result, $i, 'bug_id'),
        utils_cutstring (db_result ($result, $i, 'summary'), 40),
        "menulink", '1', db_result ($result, $i, 'summary')
      );
      if ($priority > 4)
        print "</div>\n";
      print "</div>\n";
    }
  print "</li><!-- end relatedrecipes -->\n";
}

function menu_help ()
{
  return sitemenu_help ();
}

# Help / Docs.
function sitemenu_help ()
{
  global $HTML, $sys_home, $sys_unix_group_name, $sys_name;
  $HTML->menuhtml_top (_("Site Help"));

  $HTML->menu_entry ('/maintenance/FrontPage/', _('User Docs: FAQ'));
  $HTML->menu_entry ("/maintenance/back-page/", _("User Docs: In Depth Guide"),
    1,
    _("In-depth Documentation dedicated to any users, including "
      . "Project Admins")
  );

    # TRANSLATORS: the argument is site name (like Savannah).
  $msg = sprintf (
    _("Get help from the Admins of %s, when documentation is not enough"),
    $sys_name
  );
  $HTML->menu_entry ("{$sys_home}support/?group=$sys_unix_group_name",
    _("Get Support"), 1, $msg
  );
  $HTML->menuhtml_bottom ();
  $HTML->menu_entry ("{$sys_home}contact.php", _("Contact Savannah"), 1,
    # TRANSLATORS: the argument is site name (like Savannah).
    sprintf (_("Contact address of %s Admins"), $sys_name)
  );
  $HTML->menuhtml_bottom ();
}

function menu_loggedin ($page_title, $page_toptab=0, $page_group=0)
{
  return sitemenu_loggedin ($page_title, $page_toptab, $page_group);
}

function sitemenu_loggedin ($page_title, $page_toptab=0, $page_group=0)
{
  global $HTML, $sys_home;
  # Show links appropriate for logged in people, like account maintenance, etc.
  if (!user_is_super_user ())
    # TRANSLATORS: the argument is user's name.
   $HTML->menuhtml_top (sprintf (_("Logged in as %s"), user_getname ()));
  else
    # TRANSLATORS: the argument is user's name.
    $HTML->menuhtml_top ('<span class="warn">'
      . sprintf (_("%s logged in as superuser"), user_getname())
      . '</span>'
    );
  if (user_can_be_super_user () && !user_is_super_user ())
    $HTML->menu_entry ("{$sys_home}account/su.php?action=login&amp;uri="
      . utils_urlencode ($_SERVER['REQUEST_URI']), _("Become Superuser"), 1,
      _("Superuser rights are required to perform site admin tasks")
    );
  if (user_is_super_user ())
    {
      print form_tag (['action' => "{$sys_home}account/impersonate.php"])
        . '<label for="user_name">' . _("Become this user:")
        . "</label><br/>\n";
      print form_hidden (["uri" => utils_urlencode ($_SERVER['REQUEST_URI'])]);
      print '<input type="text" id="user_name" name="user_name" size=10 '
        . '/>&nbsp;';
      print '<input type="submit" name="impersonate" value="'
        . _("Impersonate") . '" />';
      print "</form>\n";
    }
  $HTML->menu_entry ("{$sys_home}my/", _("My Incoming Items"), 1,
    _("What's new for me: new items I should have a look at")
  );
  $HTML->menu_entry ("{$sys_home}my/items.php",
                    _("My Items"),
                    1,
                    _("Browse my items (submitted by me or assigned to me)"));
  if (user_use_votes ())
    $HTML->menu_entry ("{$sys_home}my/votes.php", _("My Votes"), 1,
      _("Browse items I voted for")
    );

  $HTML->menu_entry ("{$sys_home}my/groups.php", _("My Groups"), 1,
    _("List the groups I belong to")
  );
  if (user_get_preference ("use_bookmarks"))
    $HTML->menu_entry ("{$sys_home}my/bookmarks.php", _("My Bookmarks"), 1,
      _("Show my bookmarks")
    );

  $HTML->menu_entry ("{$sys_home}my/admin/", _("My Account Conf"), 1,
    _("Account configuration: authentication, cosmetics preferences...")
  );

  if (user_is_super_user ())
    $HTML->menu_entry (
      "{$sys_home}account/su.php?action=logout&amp;uri="
      . utils_urlencode ($_SERVER['REQUEST_URI']),
      _("Logout Superuser"), 1,
      _("End the Superuser session, go back to normal user session")
    );
  $HTML->menu_entry ("{$sys_home}account/logout.php", _("Logout"), 1,
    _("End the session, remove the session cookie")
  );
  $HTML->menuhtml_bottom ();
}

function menu_notloggedin ()
{
  return sitemenu_notloggedin ();
}

function sitemenu_notloggedin ()
{
  global $HTML, $sys_home;
  $HTML->menuhtml_top (_("Not Logged in"));

  # Get settings not present in REQUEST_URI in case of a POST form.
  $extraurl = sitemenu_extraurl (true);
  if ($extraurl)
    $extraurl = "?$extraurl";

  $HTML->menu_entry (
    "{$sys_home}account/login.php?uri="
    . utils_urlencode ($_SERVER['REQUEST_URI'] . $extraurl),
    _("Login"), 1,
    _("Login page - you must have registered an account first")
  );

  $HTML->menu_entry ("{$sys_home}account/register.php", _("New User"), 1,
    _("Account registration form")
  );
  $HTML->menuhtml_bottom ();
}
?>
