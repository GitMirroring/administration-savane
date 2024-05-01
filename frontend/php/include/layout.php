<?php /*-*-PHP-*-*/
# Page layout.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Derek Feichtinger <derek.feichtinger--cern.ch>
# Copyright (C) 2000-2006 Free Software Foundation, Inc.
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2000-2006 Stéphane Urbanovski <s.urbanovski--ac-nancy-metz.fr>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
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

$dir_name = dirname (__FILE__);
require_once ("$dir_name/savane_error.php");
require_once ("$dir_name/sitemenu.php");
require_once ("$dir_name/pagemenu.php");
require_once ("$dir_name/i18n.php");
require_once ("$dir_name/theme.php");
require_once ("$dir_name/utils.php");
require_once ("$dir_name/savane-git.php");

class Layout extends savane_error
{

  # Basic HTML.
  var $bgpri = [];

  function __construct ()
  {
    GLOBAL $bgpri;
    parent::__construct ();

    # Setup the priority color array one time only.
    $bgpri[1] = 'priora';
    $bgpri[2] = 'priorb';
    $bgpri[3] = 'priorc';
    $bgpri[4] = 'priord';
    $bgpri[5] = 'priore';
    $bgpri[6] = 'priorf';
    $bgpri[7] = 'priorg';
    $bgpri[8] = 'priorh';
    $bgpri[9] = 'priori';

    $bgpri[11] = 'prioraclosed';
    $bgpri[12] = 'priorbclosed';
    $bgpri[13] = 'priorcclosed';
    $bgpri[14] = 'priordclosed';
    $bgpri[15] = 'prioreclosed';
    $bgpri[16] = 'priorfclosed';
    $bgpri[17] = 'priorgclosed';
    $bgpri[18] = 'priorhclosed';
    $bgpri[19] = 'prioriclosed';
  }

  function box_top ($title, $subclass = "", $noboxitem = 0)
  {
    $return = "<div class=\"box$subclass\">\n"
      . "<div class='boxtitle'>$title</div><!-- end boxtitle -->\n";
    if ($noboxitem)
      return $return;
    return "$return\n<div class='boxitem'>";
  }

  function box_middle ($title, $noboxitem = 0)
  {
    $return = "</div><!-- end boxitem -->\n"
      . "<div class='boxtitle'>$title</div><!-- end boxtitle -->\n";
    if ($noboxitem)
      return $return;
    return "$return\n<div class='boxitem'>";
  }

  function box_nextitem ($class)
  {
    return "</div><!-- end boxitem -->\n<div class=\"$class\">";
  }

  function box_bottom ($noboxitem = 0)
  {
    $return = "\n</div><!-- end box -->\n";
    if ($noboxitem)
      return $return;
    return "</div><!-- end boxitem -->\n$return";
  }

  function box1_top ($title, $echoout = 1, $subclass = "")
  {
    $return = "<table class=\"box$subclass\">\n<tr>\n"
      . "<td colspan='2' class='boxtitle'>$title</td>\n</tr>\n<tr>\n"
      . "<td colspan='2' class='boxitem'>";
    if ($echoout)
      print $return;
    else
      return $return;
  }

  function box1_middle ($title, $bgcolor = '')
  {
    return "\n</td>\n</tr>\n<tr>\n"
      . "<td colspan='2' class='boxtitle'>$title</td>\n</tr>\n<tr>\n"
      . "<td colspan='2' class='boxitem'>";
  }

  function box1_bottom ($echoout = 1)
  {
    $return = "\n</td>\n</tr>\n</table>";
    if ($echoout)
      print $return;
    else
      return $return;
  }

  function generic_header_start ($params)
  {
    global $G_USER, $G_SESSION, $sys_name, $savane_version;
    global $sys_home, $stone_age_menu;

    $url = git_get_savane_url (git_get_commit ());

    # Avoid any cache by setting an expire time in the past, without
    # distinction.
    # On Savane there are many forms, the text changes frequently,
    # it is probably better to avoid any cache problem that way.
    # We could use the Lastest-Modification header, but it would require
    # an extra call to time().
    $context = '';
    if (!empty ($params['context']))
      {
        $context = $params['context'];
        # Only make the test if context is set.
        # Then look for usual trackers (bugs, patch...), project admin part
        # and personal area (/my)
        $ct = ["news", "support", "bugs", "task", "my", "myitems", "mygroups"];
        if (in_array ($context, $ct) || preg_match ("/^a/", $context))
          {
            header ("Expires: Thu, 22 Dec 1977 15:00:00 GMT");
            dbg ("Expires is set.");
          }
      }
    $title = context_title ($context,
      isset ($params['group'])? $params['group'] : ''
    );
    if (!empty ($params['title']) && $title)
      $params['title'] = sprintf ("%s: %s", $title, $params['title']);
    elseif ($title)
      $params['title'] = $title;
    $params['title'] = sprintf ("%s [%s]", $params['title'], $sys_name);
    $theme = SV_THEME;
    $la = SV_LANG;
    print "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"\n"
      . "  \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n"
      . "<html xmlns=\"http://www.w3.org/1999/xhtml\" "
      . "lang=\"$la\" xml:lang=\"$la\">\n<head>\n"
      . "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" "
      . "/>\n"
      . "<title>{$params['title']}</title>\n"
      . "<meta name=\"Generator\" "
      . "content=\"Savane $savane_version, see $url\" />\n"
      . "<meta http-equiv=\"Content-Script-Type\" "
      . "content=\"text/javascript\" />\n"
      . "<link rel=\"stylesheet\" type=\"text/css\" "
      . "href=\"{$sys_home}css/$theme.css\" />\n";
    # If the user want the stone age menu, we must add the appropriate
    # additional CSS.
    if (!empty ($stone_age_menu))
      print "<link rel=\"stylesheet\" type=\"text/css\" "
        . "href=\"{$sys_home}css/internal/stone-age-menu.css\" />\n";
    if (!empty ($params['css']))
      print "<link rel=\"stylesheet\" type=\"text/css\" "
        . "href=\"{$params['css']}\" />\n";

    print "<link rel=\"icon\" type=\"image/png\" "
      . "href=\"{$sys_home}images/$theme.theme/icon.png\" />\n";
    utils_get_content ("page_header");
  }
  function generic_header_end ($params)
  {
    print "\n</head>";
  }

  function generic_footer ($params)
  {
    global $savane_version;
    $commit = git_get_commit ();
    print '<p class="footer">';
    utils_get_content ("page_footer");
    $root = realpath (dirname (__FILE__) . "/../../..");
    $page =
      preg_replace (":^$root:", '', realpath ($_SERVER['SCRIPT_FILENAME']));
    $url = git_get_savane_url ($commit, $page);
    print "<br />\n" . utils_link ($url, _('Page source code'));

    print "</p>\n<div align='right'><p>";
    # TRANSLATORS: the argument is version of Savane (like 3.2).
    printf (_("Powered by Savane %s."), $savane_version);
    $url = git_get_savane_url ($commit);
    print "<br />" . utils_link ($url, _("Corresponding source code"));
    print "</p></div>\n</div> <!-- class='main' -->\n";
    print "</div> <!-- class='realbody' -->\n";
    print "\n</body>\n</html>\n";
  }

  function header ($params)
  {
    $this->generic_header_start ($params);
    $this->generic_header_end ($params);

    print "\n<body>\n<div class='realbody'>\n";
    sitemenu ($params);
    print "<div id='top' class='main'>\n";
    pagemenu ($params);
  }

  function footer ($params)
  {
    print "\n<p class='backtotop'>\n"
      . utils_link ("#top",
          html_image ('arrows/top.orig.png', ['alt' => _("Back to the top")])
        )
      . "\n</p>\n";
    $this->generic_footer ($params);
  }

  # Left menu.
  # Most of it is in sitemenu.php.

  # Title of left menu part.
  function menuhtml_top ($title)
  {
    print "<li class='menutitle'>$title</li><!-- end menutitle -->\n";
  }

  # Left menu entry.
  function menu_entry ($link, $title, $available = 1, $help = 0)
  {
    print "\n<li class='menuitem'>\n"
      . utils_link ($link, $title, "menulink", $available, $help)
      . "</li><!-- end menuitem -->\n";
  }

  function menuhtml_bottom ()
  {
  }
  # Top menu is in pagemenu.php.
}
?>
