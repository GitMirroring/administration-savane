<?php
# Common reusable HTML code.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Pogonyshev <pogonyshev--gmx.net>
# Copyright (C) 2008 Aleix Conchillo Flaque
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

$dir_name = dirname (__FILE__);
require_once ("$dir_name/sane.php");
require_once ("$dir_name/markup.php");
require_once ("$dir_name/form.php");

# Display browsing/display options: should be on top of pages, after the
# specific content/page description.
# The form should end by #options so it the user does not have to scroll down
# too much.
function html_show_displayoptions ($content, $form_opening=0, $submit=0)
{
  return html_show_boxoptions (
    _("Display Criteria"), $content, $form_opening, $submit
  );
}

function html_show_boxoptions ($legend, $text, $form_opening = 0,
  $submit = 0
)
{
  $ret = "\n";
  extract (sane_import ('request', ['true' => 'boxoptionwanted']));

  if ($boxoptionwanted != 1)
    $boxoptionwanted = 0;
  else
    $boxoptionwanted = 1;

  $ret .= "\n<script type='text/javascript' src=\"/js/show-hide.php?"
    . "deploy=$boxoptionwanted&amp;legend=" . utils_urlencode ($legend)
    . "&amp;box_id=boxoptions&amp;suffix=\"></script>";
  $ret .= "\n<noscript>\n<span id='boxoptionslinkshow'>$legend</span>\n"
    . "</noscript>\n";

  $ret .= "<div id='boxoptionscontent'>\n";
  if ($boxoptionwanted != 1)
    $ret .= "\n<script type=\"text/javascript\" "
      . "src=\"/js/hide-span.php?box_id=boxoptionscontent\"></script>\n";

  if ($form_opening && $submit)
    $ret .= "\n$form_opening <span class='boxoptionssubmit'>$submit</span>";

  # We add boxoptionwanted to be able to determine if a boxoption was used
  # to update the page, in which case the boxoption must appear deployed.
  $ret .= $text . form_hidden (["boxoptionwanted" => "1"]);

  if ($form_opening && $submit)
    $ret .= "\n</form>\n";
  $ret .= "</div>\n";

  return "$ret\n";
}

function html_hidsubpart_set_deployed ($uniqueid, $deployed)
{
  global $is_deployed;

  # Try to find a deployed value that match the unique id.
  # If found, override the deployed setting (the deployed setting should be
  # used to set a default behavior, but if in the case we explicitely
  # use an array to determine what is deployed, this matters more).
  if (is_array ($is_deployed)
      && array_key_exists ($uniqueid, $is_deployed))
    $deployed = $is_deployed[$uniqueid];
  if ($deployed != 1)
    $deployed = 0;
  return $deployed;
}

function html_hidsubpart_js ($deployed, $title, $uniqueid)
{
 return '<script type="text/javascript" src="/js/show-hide.php?'
  . "deploy=$deployed&amp;legend=" . utils_urlencode ($title)
  . "&amp;box_id=hidsubpart&amp;suffix=$uniqueid\"></script>\n"
  . "\n<noscript>\n<a href=\"#$uniqueid\">$title</a>\n</noscript>";
}

# Function to create a an area in the page that can be hidden or shown
# in one click with a JavaScript.
# Per policy, this must work with a browser that does not support at all
# JavaScript.
# This is useful on item pages because we have some info that is not
# essential to be shown (like CC list etc), but still very nice be able to
# access easily.
function html_hidsubpart_header ($uniqueid, $title, $deployed = false)
{
  $deployed = html_hidsubpart_set_deployed ($uniqueid, $deployed);
  $ret = "\n<h2 id=\"$uniqueid\">\n"
    . html_hidsubpart_js ($deployed, $title, $uniqueid)
    . "</h2>\n\n";
  $ret .= "<div id=\"hidsubpartcontent$uniqueid\">\n";
  if ($deployed)
    return $ret;
  return $ret . '<script type="text/javascript" src="/js/hide-span.php'
    . "?box_id=hidsubpartcontent$uniqueid\"></script>\n";
}

function html_hidsubpart_footer ()
{
  return "\n</div><!-- closing hidsubpart -->\n";
}

function html_splitpage ($how)
{
  if ($how == 'start' || $how == '1')
    return "\n<div class='splitright'>\n";
  if ($how == 'middle' || $how == '2')
    return  "\n</div><!-- end  splitright -->\n<div class='splitleft'>\n";
  return "\n</div><!-- end  splitleft -->\n";
}

function html_image_dir ($theme, $suffix = null)
{
  global $sys_home;
  $ret = "{$sys_home}images$theme.theme/";
  if ($suffix)
    $ret .= "$suffix/";
  return $ret;
}

function html_nextprev_link ($search_url, $varprefix, $offset)
{
  global $max_rows;
  print "<a href=\"$search_url&amp;{$varprefix}offset="
    . "$offset&amp;{$varprefix}max_rows="
    . utils_specialchars ($max_rows) . "#{$varprefix}results\">";
}

function html_nextprev ($search_url, $rows, $rows_returned, $varprefix = false)
{
  global $offset, $sys_home, $max_rows;

  if (!$varprefix)
    $varprefix = '';
  else
    $varprefix .= '_';

  if ($rows_returned < $rows && !$offset)
    return;
  $prev_msg = _("Previous Results");
  $next_msg = _("Next Results");
  print "\n<br /><p class=\"nextprev\">\n";

  if ($offset)
    {
      $prev_offset = $offset - $max_rows;
      if ($prev_offset < 0)
        $prev_offset = 0;
      html_nextprev_link ($search_url, $varprefix, $prev_offset);
      print html_image ("arrows/previous.png") . " $prev_msg</a>";
    }
  else
    print html_image ("arrows/previousgrey.png") . " <i>$prev_msg</i>";
  print "&nbsp; &nbsp; &nbsp;";

  if ($rows_returned > $rows)
    {
      html_nextprev_link ($search_url, $varprefix, $offset + $rows);
      print "$next_msg " . html_image ("arrows/next.png") . "</a>";
    }
  else
    print "<i>$next_msg</i> " . html_image ("arrows/nextgrey.png");
  print "</p>\n";
}

function html_anchor ($content, $name)
{
  if (!$name)
    $name = $content;
  return "<a id=\"$name\" href=\"#$name\">$content</a>";
}

function html_format_feedback ($f)
{
  if (empty ($f))
    $f = '';
  $f = preg_replace ('/\n*$/', '', $f);
  return nl2br (markup_basic (utils_specialchars ($f)));
}

# Print out the feedback.
function html_feedback ($bottom)
{
  global $feedback, $ffeedback, $sys_home;

  # Be quiet when there is no feedback.
  if (!($ffeedback || $feedback))
    return;

  $feedback = html_format_feedback ($feedback);
  $ffeedback = html_format_feedback ($ffeedback);

  $suffix = '';
  if ($bottom)
    $suffix = '_bottom';

  $script_hide = '<script type="text/javascript" '
    . "src=\"/js/hide-feedback.php?suffix=$suffix\"></script>\n";

  $class_hide = 'feedback';

  # Users can choose the same behavior, disallowing the fixed positioning
  # of the feedback.
  if (user_get_preference ("nonfixed_feedback"))
    {
      $class_hide = 'feedback feedback-hide';
      $script_hide = '';
    }

  print "<div id=\"feedbackback$suffix\" class='feedbackback'>"
    . _("Show feedback again") . "</div>\n";
  print '<script type="text/javascript" src="/js/show-feedback.php?suffix='
    . "$suffix\"></script>\n";

  $img_ok = html_image ("bool/ok.png", ['class' => 'feedbackimage']);
  $img_wrong = html_image ("bool/wrong.png", ['class' => 'feedbackimage']);
  # Only success.
  if ($feedback && !$ffeedback)
    print "<div id=\"feedback$suffix\" class=\"$class_hide\">"
      . '<span class="feedbacktitle">' . $img_ok
      . _("Success:") . "</span> $feedback</div>\n$script_hide";

  # Only errors.
  if ($ffeedback && !$feedback)
    print "<div id=\"feedback$suffix\" class=\"feedbackerror $class_hide\">"
      . "<span class='feedbackerrortitle'>$img_wrong"
      . _("Error:") . "</span>\n$ffeedback</div>\n";

  # Errors and success.
  if ($ffeedback && $feedback)
    print "<div id=\"feedback$suffix\" class=\"feedbackerrorandsuccess "
      . "$class_hide\"><span class='feedbackerrorandsuccesstitle'>$img_wrong"
      . _("Some Errors:") . "</span> $feedback $ffeedback</div>\n";

  # We empty feedback so there will be a bottom feedback only if something
  # changes.
  $feedback = $ffeedback = '';
}

function html_feedback_top ()
{
  html_feedback (0);
}

function html_feedback_bottom ()
{
  html_feedback (1);
}

# Return image size attributes for a file.
function html_image_get_size ($src, $path)
{
  static $img_attr = [];
  # Check to see if we've already fetched the image data.
  if (!array_key_exists ($src, $img_attr) && is_file ($path))
    list ($width, $height, $type, $img_attr[$src]) =
      @getimagesize ($path);
  return $img_attr[$src];
}

# Compile attributes for <img /> and <input type="image" />.
function html_image_attributes ($src, $args)
{
  global $sys_home, $sys_www_topdir;
  $base = "images/" . SV_THEME . ".theme/$src";
  $path = "$sys_www_topdir/$base";

  if (empty ($args['alt']))
    $args['alt'] = '';

  if (empty ($args['border']))
    $args['border'] = 0;

  $return = "src=\"$sys_home$base\"";

  foreach ($args as $k => $v)
    $return .= " $k=\"$v\"";

  # If there is neither height nor width tag, insert them both.
  if (empty ($args['height']) && empty ($args['width']))
    $return .= ' ' . html_image_get_size ($src, $path);

  return $return;
}

function html_image_base ($attr)
{
  return "<img $attr />";
}

function html_image ($src, $args = [])
{
  return html_image_base (html_image_attributes ($src, $args));
}

function html_image_trash_attributes ($args)
{
  if (!array_key_exists ('alt', $args))
    $args['alt'] = _("Delete");
  return html_image_attributes ('misc/trash.png', $args);
}

function html_image_trash ($args = [])
{
  $attr = html_image_trash_attributes ($args);
  return html_image_base ($attr);
}

function html_label ($for, $title)
{
  return "<label for='$for'>$title</label>";
}

function html_h ($no, $title)
{
  return "<h$no>$title</h$no>\n";
}

# Start a list table from an array of titles and builds.
# The first row of a new table.
#
# Optionally take a second array of links for the titles.
function html_build_list_table_top (
  $title_arr, $links_arr = false, $table = true
)
{
  $return = '';

  if ($table)
    $return = "\n<table class='box'>\n";

  $return .= "<tr>\n";

  $count = count ($title_arr);
  if ($links_arr)
    for ($i = 0; $i < $count; $i++)
      {
        $ln = $links_arr[$i]; $ti = $title_arr[$i];
        $return .= "<th class='boxtitle'>"
          . "<a class='sortbutton' href=\"$ln\">$ti</a></th>\n";
      }
  else
    for ($i = 0; $i < $count; $i++)
      $return .= "<th class='boxtitle'>{$title_arr[$i]}</th>\n";
  return "$return</tr>\n";
}

function html_get_alt_row_color ($i)
{
  if ($i % 2)
    return 'boxitem';
  return 'boxitemalt';
}

# Auxiliary function to use in html_build_select*box*.
function html_title_attr ($title)
{
  if ($title == "")
    return "";
  return "title=\"$title\" ";
}

# Build a select box from arrays, with the first array being the "id"
# or value and the array being the text you want displayed.
#
# The second parameter is the name you want assigned to this form element.
#
# The third parameter is the value of the item that should be checked.
function html_build_select_box_from_array (
  $vals, $select_name, $checked_val = 'xzxz', $samevals = 0, $title = ""
)
{
  $return = "<select " . html_title_attr ($title) . "name=\"$select_name\">\n";
  $rows = count ($vals);
  for ($i = 0; $i < $rows; $i++)
    {
      $v = $i;
      if ($samevals)
        $v = $vals[$i];
      $return .= "  " . form_option ($v, $checked_val, $vals[$i]);
    }
  return "$return\n</select>\n";
}

# The infamous '100 row' has to do with the
# SQL Table joins done throughout all this code.
# There must be a related row in users, categories, etc, and by default that
# row is 100, so almost every pop-up box has 100 as the default
# Most tables in the database should therefore have a row with an id of 100 in it
# so that joins are successful.
#
# There is now another infamous row called the Any row. It is not
# in any table as opposed to 100. it's just here as a convenience mostly
# when using select boxes in queries (bug, task, ...). The 0 value is reserved
# for Any and must not be used in any table.
#
# Takes two arrays, with $vals being the "id" or value
# and $texts being the text you want displayed.
#
# $select_name is the name you want assigned to this form element.
#
# $checked_val is the value of the item that should be checked.
#
# $show_100 is a boolean - whether or not to show the '100 row'.
#
# $text_100 is what to call the '100 row', defaults to none.
#
# $show_any is a boolean - whether or not to show the 'Any row'.
#
# $text_any is what to call the 'Any row' defaults to 'Any'.
#
# $show_unknown is a boolean - whether to show "Unknown" row.
#
# $title is the title for the box.
function html_build_select_box_from_arrays (
  $vals, $texts, $select_name, $checked_val = 'xzxz', $show_100 = true,
  $text_100 = 'None', $show_any = false, $text_any = 'Any',
  $show_unknown = false, $title = ""
)
{
  if ($text_100 == 'None')
    $text_100 = _('None');
  if ($text_any == 'Any')
    $text_any = _('Any');
  if ($title != '')
    $id_attr = '';
  else
    $id_attr = " id=\"$select_name\"";

  $return = "\n<select " . html_title_attr ($title)
    . "name=\"$select_name\"$id_attr >\n";

  # We want the "Default" on item initial post, only at this momement.
  if ($show_unknown)
    $return .= form_option ("!unknown!", NULL, _("Unknown"));

  # We don't always want the default any  row shown.
  if ($show_any)
    $return .= form_option ("0", $checked_val, $text_any);

  # We don't always want the default 100 row shown.
  if ($show_100)
    $return .= form_option ("100", $checked_val, $text_100);

  $rows = count ($vals);
  if (count ($texts) != $rows)
    $return .= _('ERROR - number of values differs from number of texts');

  for ($i = 0; $i < $rows; $i++)
    #  Uggh - sorry - don't show the 100 row and Any row.
    #  If it was shown above, otherwise do show it.
    if ((($vals[$i] != '100') && ($vals[$i] != '0'))
         || ($vals[$i] == '100' && !$show_100)
         || ($vals[$i] == '0' && !$show_any))
      $return .= form_option ($vals[$i], $checked_val, $texts[$i]);
  $return .= "</select>\n";
  return $return;
}

# Build a select box from a result set, with the first column being the "id"
# or value and the second column being the text you want displayed.
#
# The second parameter is the name you want assigned to this form element.
#
# The third parameter is the value of the item that should
# be checked.
#
# The fourth parameter is a boolean - whether or not to show
# the '100 row'.
#
# The fifth parameter is what to call the '100 row' defaults to none.
function html_build_select_box (
  $result, $name, $checked_val = "xzxz", $show_100 = true, $text_100 = 'None',
  $show_any = false, $text_any = 'Any', $show_unknown = false, $title = ""
)
{
  return html_build_select_box_from_arrays (
    utils_result_column_to_array ($result),
    utils_result_column_to_array ($result, 1), $name, $checked_val, $show_100,
    $text_100, $show_any, $text_any, $show_unknown, $title
  );
}

# The same as html_build_select_box, but the items are localized.
function html_build_localized_select_box (
  $result, $name, $checked_val = "xzxz", $show_100 = true, $text_100 = 'None',
  $show_any = false, $text_any = 'Any', $show_unknown = false, $title = ""
)
{
  return html_build_select_box_from_arrays (
    utils_result_column_to_array($result),
    utils_result_column_to_array($result, 1, true),
    $name, $checked_val, $show_100, $text_100, $show_any, $text_any,
    $show_unknown, $title
  );
}

# Build a select box from a result set, with the first column being the "id"
# or value and the second column being the text you want displayed.
#
# The second parameter is the name you want assigned to this form element.
# The third parameter is an array of checked values.
# The fourth parameter is the size of this box.
# Fifth to eigth params determine whether to show None and Any.
#
# Ninth param determine whether to show numeric values next to
# the menu label (default true for backward compatibility.
function html_build_multiple_select_box (
  $result, $name, $checked_array, $size = '8', $show_100 = true,
  $text_100 = 'None', $show_any = false, $text_any = 'Any', $show_value = true,
  $title = ""
)
{
  $title_attr = html_title_attr ($title);
  $return = "\n<select $title_attr name=\"$name\" multiple size='$size'>\n";
  if ($show_any)
    $return .= form_option ("0", $checked_array, $text_any);
  if ($show_100)
    $return .= form_option ("100", $checked_array, $text_100);
  while ($row = db_fetch_array ($result))
    {
      if ($row[0] == '100')
        continue;
      $val = $row[0] . '-';
      if (!$show_value)
        $val = '';
      $label = $val . substr ($row[1], 0, 35);
      $return .= form_option ($row[0], $checked_array, $label);
    }
  return "$return</select>\n";
}

function html_select_permission_box ($artifact, $row, $level = "member")
{
  $num = '';
  if ($level == "type")
    {
      $value = $row;
      $default = 0;
    }
  elseif ($level == "group")
    {
      $value = $row;
      $default = _("Group Type Default");
    }
  else
    {
      $num = $row['user_id'];
      $value = $row["{$artifact}_flags"];
      $default = _("Group Default");
    }

  print "<td align=\"center\">\n"
    . '<select title="' . _("Roles of members")
    . "\" name=\"{$artifact}_user_$num\">\n";
  if ($default)
    {
      $sel = 'NULL';
      if ($value)
        $sel = 'value';
      print "  " . form_option ('NULL', $sel, $default);;
    }
  $labels = [
    [9, _("None")],
    [1, _("Technician"), 1],
    [3, _("Manager")],
    [2, _("Techn. & Manager"), 1],
  ];
  foreach ($labels as $vl)
    {
      if ($artifact == 'news' && count ($vl) > 2)
        continue;
      print "  " . form_option ($vl[0], $value, $vl[1]);
    }
  print "</select>\n";
  if (!$value && $level == "group")
    {
      $value = group_gettypepermissions ($GLOBALS['group_id'], $artifact);
      print "<br />\n(";
      foreach ($labels as $vl)
        if ($value == $vl[0])
          print $vl[1];
      print ")\n";
    }
  print "</td>\n";
}

# Build restriction select box, in a <td> unless $notd.
# $artifact: tracker name
# $row: current selection
# $level = 'type': edit group types
#        = 'group': configure restrictions in a group
# $event = 1: posting items
#        = 2: posting comments
function html_select_restriction_box (
  $artifact, $row, $level = "group", $notd = 0, $event = 1
)
{
  $value = $row;
  $default = 0;
  if ($level != "type")
    {
      $default = _("Group Type Default");
      if ($event == 2)
        $default = _("Same as for new items");
    }

  if (!$notd)
    print "<td align='center'>\n";

  print '<select title="' . _("Permission level")
    . "\" name=\"{$artifact}_restrict_event$event\">\n";

  if ($default)
    {
      $sel = 'NULL';
      if ($value)
        $sel = 'value';
      print form_option ('NULL', $sel, $default);
    }
  $labels = [
    [6, _("Nobody")], [5, _("Group Member")],  [3, _("Logged-in User")],
    [2, _("Anonymous")],
  ];
  foreach ($labels as $vl)
    print form_option ($vl[0], $value, $vl[1]);
  print "</select>\n";

  if (!$value && $level == "group" && $event == 1)
    {
      $value = group_gettyperestrictions ($GLOBALS['group_id'], $artifact);
      print "<br />\n(";
      foreach ($labels as $vl)
        if ($value == $vl[0])
          print $vl[1];
      print ")\n";
    }
  if (!$notd)
    print "</td>\n";
}

# This function must know every type of directory that can be built by the
# backend.
function html_select_typedir_box ($input_name, $current_value)
{
  # The strings are not localized because they are for siteadmin's eyes only.
  print "<br />&nbsp;&nbsp;\n";
  print "<select title='directories' name=\"$input_name\">\n";
  foreach (
    [
      'basicdirectory' => "Basic Directory",
      "basiccvs" => "Basic CVS Directory",
      "basicsvn" => "Basic Subversion Directory",
      "basicgit" => "Basic Git Directory",
      "basichg" => "Basic Mercurial Directory",
      "basicbzr" => "Basic Bazaar Directory",
      "cvsattic" => "CVS Attic/Gna",
      "svnattic" => "Subversion Attic/Gna",
      "svnatticwebsite" => "Subversion Subdirectory Attic/Gna",
      "savannah-gnu" => "Savannah GNU",
      "savannah-nongnu" => "Savannah non-GNU",
    ] as $dir => $title
  )
    print form_option ($dir, $current_value, $title);
  print "</select> [BACKEND SPECIFIC]\n";
  print "<p><span class='smaller'>Basic directory will make the backend\n"
    . "using DownloadMakeArea(), defined in Savannah::Download;<br />\n"
    . "CVS directory will make the backend using CvsMakeArea(), defined\n"
    . "in Savannah::Cvs.\n</span><p>";
}

# Print an theme select box. This function will add the special rotate
# and random meta-themes. This function will hide disallowed theme.
# That is said that theme are not strictly forbidden, someone can
# forge a form and choose a forbidden theme.
# But it is really not a big deal and it is better to have making to
# many checks in theme_list(), which is frequently ran, unlike this
# one.
function html_select_theme_box ($input_name = "user_theme", $current = 0)
{
  print '<select title="' . _("Website theme") . "\" name=\"$input_name\">\n";
  $theme_option = function ($theme, $label = null) use ($current)
  {
    if (empty ($label))
      $label = $theme;
    else
      $label = "&gt; $label";
    if ($theme == $GLOBALS['sys_themedefault'])
      $label .= ' ' . _("(default)");
    print form_option ($theme, $current, $label);
  };
  # Fixed themes.
  foreach (theme_list () as $theme)
    $theme_option ($theme);
  # Two special themes.
  $theme_option ("rotate", _("Pick theme alphabetically every day"));
  $theme_option ("random", _("Pick random theme every day"));
  print "</select>\n";
}

function html_build_checkbox ($name, $is_checked = 0, $title = "")
{
  $attr = [];
  if ($title !== '')
    $attr['title'] = $title;
  print  form_checkbox ($name, $is_checked, $attr);
}

# Catch all header functions.
function html_header ($params)
{
  global $HTML;
  print $HTML->header ($params);
  print html_feedback_top ();
}

function html_footer ($params)
{
  global $HTML, $feedback;
  print html_feedback_bottom ();
  $HTML->footer ($params);
}

# Aliases of catch all header functions.
function site_header ($params)
{
  html_header ($params);
}

function site_footer ($params)
{
  html_footer ($params);
}

# Project page functions.

# Everything required to handle security and state checks for a project web page.
# Params array() must contain $context and $group.
# Result - prints HTML directly.
function site_project_header ($params)
{
  global $group_id;
  $project = project_get_object ($group_id);

  if ($project->isError())
    {
      exit_error (
        # TRANSLATORS: the argument is group id (a number).
        sprintf (_("Invalid Group %s"), $group_id),
        _("That group does not exist.")
      );
    }

  if (!$project->isPublic())
    {
      # If it's a private group, you must be a member of that group.
      session_require (['group' => $group_id]);
    }

  # For dead projects must be member of admin project.
  if (!$project->isActive())
    {
      # Only sys_group people can view non-active, non-holding groups.
      session_require (['group' => $GLOBALS['sys_group_id']]);
    }
  html_header ($params);
}

# Currently a simple shim that should be on every project page,
# rather than a direct call to site_footer() or theme_footer().
# Params array() empty.
# Result - print HTML directly.
function site_project_footer ($params = [])
{
  html_footer ($params);
}

# User page functions.

# Everything required to handle security and
# add navigation for user pages like /my/ and /account/.
# Params array() must contain $user_id.
# Result - print HTML directly.
function site_user_header ($params)
{
  session_require (['isloggedin' => '1']);
  html_header ($params);
}

# Currently a simple shim that should be on every user page,
# rather than a direct call to site_footer() or theme_footer().
# Params array() empty.
# Result - print HTML directly.
function site_user_footer ($params)
{
  html_footer ($params);
}

# Administrative page functions.
function site_admin_header ($params)
{
  session_require (['group' => '1','admin_flags' => 'A']);
  html_header ($params);
}

function site_admin_footer ($params)
{
  html_footer ($params);
}

function show_group_type_box (
  $name = 'group_type', $checked_val = 'xzxz', $show_select_one = false
)
{
  $result = db_query("SELECT * FROM group_type");
  return html_build_select_box (
    $result, 'group_type', $checked_val, $show_select_one,
    "> " . _("Choose one below"), false, 'Any', false, _('Group type')
  );
}

function html_member_explain_roles ()
{
  print '<p>'
    . _("Technicians, and only technicians, can be assigned items of "
        . "trackers. They\ncannot reassign items, change the status or "
        . "priority of items.");
  print "</p>\n<p>";
  print _("Tracker Managers can fully manage items of trackers, including\n"
          . "assigning items to technicians, reassigning items over trackers "
          . "and projects,\nchanging priority and status of items&mdash;but "
          . "they cannot configure the\ntrackers.");
  print "</p>\n<p>";
  print _("Group admins can manage members, configure the trackers, post\n"
          . "jobs, and add mailing lists. They actually also have manager "
          . "rights on every\ntracker and are allowed to read private items.");
  print "</p>\n";
}
?>
