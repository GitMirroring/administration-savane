<?php
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
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
require_once ('../include/init.php');
require_once ('../include/form.php');
require_once ('../include/news/general.php');

if (!$group_id)
  $group_id = $sys_group_id;

extract (sane_import ('request',
  ['pass' => 'feedback', 'digits' => ['id', 'limit']])
);

if (empty ($limit))
  $limit = 10;
else
  $limit = intval ($limit);

$project = project_get_object ($group_id);
if (!$project->Uses ("news"))
  exit_error (_("This group doesn't post news."));

site_project_header (['group' => $group_id, 'context' => 'news']);

if ($id)
  {
    news_show_news_item ($id);
    site_project_footer ([]);
    exit;
  }

$form_opening = form_tag (['method' => 'get'], "#options");
$format_string =
  ngettext (
    "Show details for the %s latest news.",
    "Show details for the %s latest news.",
    $limit
  );
$news_no_input =
  '<input type="text" title=\'' . _("Number of news to show")
  . "' name='limit' size='4' value='$limit' />\n";

$form = sprintf ($format_string, $news_no_input);
if (isset ($group))
  $form .= form_hidden (['group' => $group]);

$form_submit =
   '<input class="bold" type="submit" value="' . _("Apply") . "\" />\n";

print html_show_displayoptions ($form, $form_opening, $form_submit);
print "<br />\n";
print $HTML->box_top (_("Latest News Approved"));
print news_show_latest ($group_id, $limit, true, -1);
print $HTML->box_bottom ();

# A box with no summaries, if they are not all already shown.
if ($limit < news_total_number ($group_id))
  {
    print "<br />\n";
    print $HTML->box_top (_("Older News Approved"));
    print news_show_latest ($group_id, 0, false, $limit);
    print $HTML->box_bottom ();
  }
site_project_footer ([]);
?>
