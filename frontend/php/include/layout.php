<?php /*-*-PHP-*-*/
#
# Page layout.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2000-2003 Free Software Foundation
# Copyright (C) 2000-2003 Stéphane Urbanoski <s.urbanovski--ac-nancy-metz.fr>
# Copyright (C) 2000-2003 Derek Feichtinger <derek.feichtinger--cern.ch>
#
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2004-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2017, 2018, 2019 Ineiev
#
# This file is part of Savane.
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


# base error library for new objects
require_once(dirname(__FILE__).'/savane_error.php');
# left-hand and top menu nav library (requires context to be set)
require_once(dirname(__FILE__).'/sitemenu.php');
require_once(dirname(__FILE__).'/pagemenu.php');
# i18n setup
require_once(dirname(__FILE__).'/i18n.php');
# theme - color scheme informations
require_once(dirname(__FILE__).'/theme.php');
require_once(dirname(__FILE__).'/utils.php');

class Layout extends savane_error
{

########################################## BASIC HTML
  var $bgpri = array();

# Constuctor
  function __construct ()
  {
    GLOBAL $bgpri;
    parent::__construct();

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

  function box_top ($title, $subclass="", $noboxitem=0)
  {
    $return = '     <div class="box'.$subclass.'">
       <div class="boxtitle">'.$title.'</div><!-- end boxtitle -->';
    if (!$noboxitem)
      {
        $return .= '
       <div class="boxitem">';
      }
    return $return;
  }

# Box Middle, equivalent to html_box1_middle().
  function box_middle ($title, $noboxitem=0)
  {
    $return = '</div><!-- end boxitem -->
       <div class="boxtitle">'.$title.'</div><!-- end boxtitle -->';
    if (!$noboxitem)
      {
        $return .= '
       <div class="boxitem">';
      }
    return $return;
  }

# Box Middle, equivalent to html_box1_middle().
  function box_nextitem ($class)
  {
    return '</div><!-- end boxitem -->
       <div class="'.$class.'">';
  }

# Box Bottom, equivalent to html_box1_bottom().
  function box_bottom ($noboxitem=0)
  {
    $return = '';
    if (!$noboxitem)
      {
        $return .= '</div><!-- end boxitem -->';
      }

    $return .= '
     </div><!-- end box -->
';
    return $return;
  }

# Box Top, equivalent to html_box1_top().
  function box1_top ($title,$echoout=1,$subclass="")
  {
    $return = '<table class="box'.$subclass.'">
                <tr>
                        <td colspan="2" class="boxtitle">'.$title.'</td>
                </tr>
                <tr>
                        <td colspan="2" class="boxitem">';
    if ($echoout)
      print $return;
    else
      return $return;
  }

# Box Middle, equivalent to html_box1_middle().
  function box1_middle ($title,$bgcolor='')
  {
    return '
                        </td>
                </tr>
                <tr>
                        <td colspan="2" class="boxtitle">'.$title.'</td>
                </tr>
                <tr>
                        <td colspan=2 class="boxitem">';
  }

# Box Bottom, equivalent to html_box1_bottom().
  function box1_bottom ($echoout=1)
  {
    $return = '
                        </td>
                </tr>
        </table>';
    if ($echoout)
      print $return;
    else
      return $return;
  }

  function generic_header_start ($params)
  {
    global $G_USER, $G_SESSION;

# Avoid any cache by setting an expire time in the past, without
# distinction.
# On Savane there are many forms, the content changes frequently,
# it is probably better to avoid any cache problem that way.
# We could use the Lastest-Modification header, but it would require
# an extra call to time().
    $context = '';
    if (!empty($params['context']))
      {
        $context = $params['context'];
# Only make the test if context is set.
# Then look for usual trackers (bugs, patch...), project admin part
# and personal area (/my)
        if ("news" == $context
            || "support" == $context
            || "bugs" == $context
            || "task" == $context
            || "my" == $context
            || "myitems" == $context
            || "mygroups" == $context
            || preg_match ("/^a/", $context))
          {
            header("Expires: Thu, 22 Dec 1977 15:00:00 GMT");
            dbg("Expires is set.");
          }
      }
    $title = context_title($context, isset($params['group']) ? $params['group'] : '');
    if (!empty($params['title']) && $title)
      {
        $params['title'] = sprintf("%s: %s", $title, $params['title']);
      }
    elseif ($title)
      {
        $params['title'] = $title;
      }
    $params['title'] = sprintf("%s [%s]", $params['title'], $GLOBALS['sys_name']);
    $theme = SV_THEME;
    # Path of printer.css changed to internal/printer.css.
    if ($theme == "printer")
      $theme = "internal/printer";

    print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="'.SV_LANG.'" xml:lang="'
          .SV_LANG.'">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <title>'.$params['title'].'</title>
  <meta name="Generator" content="Savane '.$GLOBALS['savane_version']
          .', see '.$GLOBALS['savane_url'].'" />
  <meta http-equiv="Content-Script-Type" content="text/javascript" />
  <link rel="stylesheet" type="text/css" href="'.$GLOBALS['sys_home']
          .'css/'.$theme.'.css" />
';
    # If the user want the stone age menu, we must add the appropriate
    # additional CSS.
    if (!empty($GLOBALS['stone_age_menu']))
      {
        print '  <link rel="stylesheet" type="text/css" href="'
              .$GLOBALS['sys_home'].'css/internal/stone-age-menu.css" />
';
      }
    if (!empty($params['css']))
      print '  <link rel="stylesheet" type="text/css" href="'
            . $params['css'] . '" />' . "\n";

    print '  <link rel="icon" type="image/png" href="'.$GLOBALS['sys_home']
          .'images/'.SV_THEME.'.theme/icon.png" />
';
    utils_get_content("page_header");
  }
  function generic_header_end ($params)
  {
    print '
</head>';
  }

  function generic_footer ($params)
  {
    global $savane_url, $savane_version;
    print '<p class="footer">';
    utils_get_content ("page_footer");
    print "</p>\n<div align='right'><p>"
      . utils_link ($savane_url,
          # TRANSLATORS: the argument is version of Savane (like 3.2).
          sprintf (_("Powered by Savane %s"), $savane_version)
        )
      . "</p></div>\n";
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

    if (empty ($_GET['printer']))
      print "\n<!-- not closing yet main and realbody -->\n";
    else
      print "\n</div><!-- end main -->\n<br class='clear' />\n"
        . "</div><!-- end realbody -->\n";
    $this->generic_footer ($params);
  }

# Left menu
# Most of it is in sitemenu.php.

# Title of left menu part.
  function menuhtml_top ($title)
  {
    print "<li class='menutitle'>$title</li><!-- end menutitle -->\n";
  }

# left menu entry
  function menu_entry ($link, $title, $available=1, $help=0)
  {
    print '
        <li class="menuitem">
           '.utils_link($link, $title, "menulink", $available, $help).'
        </li><!-- end menuitem -->';
  }

# end of left menu part
  function menuhtml_bottom ()
  {

  }

# ######################################### TOP MENU
# It is in pagemenu.php
}
?>
