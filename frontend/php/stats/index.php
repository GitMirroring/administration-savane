<?php
# Show site statistics.
#
# Copyright (C) 2004-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2004-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2017, 2018, 2020, 2023 Ineiev
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

require_once ('../include/init.php');
require_once ('../include/sane.php');
require_once ('../include/stats/general.php');
require_once ('../include/calendar.php');
require_once ('../include/graphs.php');

register_globals_off();

$digit_names = [];
foreach (['day', 'month', 'year'] as $term)
  foreach (['since', 'until'] as $prep)
    $digit_names[] = "${prep}_$term";

extract (sane_import ('get', ['true' => 'update', 'digits' => $digit_names]));

# Assemble page body first because we need to insert a link to generated
# stylesheet in its header.

$page = '';

if ($update)
  {
    # If the user selected date, assume he speaks of completed days.
    $hour = 0;
    $min = 0;
  }
else
  {
    # Replace since_ and util_ parameters.
    $since_month = date("m")-1;
    $since_day = date("d");
    $since_year = date("Y");

    $until_month = date("m");
    $until_day = date("d");
    $until_year = date("Y");

    $hour = date("H");
    $min = date("i");
  }

$since = mktime ($hour, $min, 0, $since_month, $since_day, $since_year);
$until = mktime ($hour, $min, 0, $until_month, $until_day, $until_year);

$form_opening = '<form action="' . htmlentities ($_SERVER['PHP_SELF'])
  . '#options" method="GET">';
$form_submit = '<input class="bold" value="' . _("Apply")
  . '" name="update" type="submit" />';
$page .= html_show_displayoptions (
  # TRANSLATORS: The arguments are two dates.
  # Example: "From 12. September 2005 till 14. September 2005"
  sprintf (_('From %1$s till %2$s.'),
    calendar_select_date ($since_day, $since_month,
      htmlentities ($since_year), ["since_day", "since_month", "since_year"]
    ),
    calendar_select_date ($until_day, $until_month, htmlentities ($until_year),
      ["until_day", "until_month", "until_year"]
    )
  ), $form_opening, $form_submit
);

$page .= "\n<h2>";
$page .= html_anchor (
  # TRANSLATORS: The arguments are two dates.
  # Example: "From 12. September 2005 till 14. September 2005"
  sprintf (_('From %1$s till %2$s'),
    utils_format_date ($since), utils_format_date ($until)), "between"
);
$page .= "</h2>\n";

if ($since > $until)
  $page .= '<p class="error">'
    . _("The begin of the period you asked for is later than its end.")
    . "</p>\n";

$page .= "<h3>" . _("Accounts") . "</h3>\n<ul>\n";

$count_users = stats_getusers ();
$count_groups = stats_getprojects ();

$content = $total = [];

$count = stats_getusers ("add_date >= $since AND add_date <= $until");
$key = _("New users");
$content[$key] = $count;
$total[$key] = $count_users;
$page .= '<li>';
$page .= sprintf (ngettext ("%s new user", "%s new users", $count), $count);
$page .= "</li>\n";

$count = stats_getprojects (
  "", "", "register_time >= $since AND register_time <= $until"
);
$key = _("New groups");
$content[$key] = $count;
$total[$key] = $count_groups;
$page .= "\n<li>";
$page .=
  sprintf (ngettext ("%s new project", "%s new projects", $count), $count);
$page .= "</li>\n</ul>\n";

$page .= '<h3>' . _("New users and new groups / total") . "</h3>\n";
$graph_id = 0;
$widths = "";
function construct_graph ($content, $total)
{
  global $graph_id, $widths;

  $build = graphs_build ($content, 0, 0, $total, $graph_id);
  if ($graph_id != $build[0])
    {
      $widths = "$widths,{$build[1]}";
      $graph_id = $build[0];
    }
  return $build[2];
}
$page .= construct_graph ($content, $total);

$content = [];
$total = 0;

$total_patch = stats_getitems ("patch");
$total_task = stats_getitems ("task");
$total_bugs = stats_getitems ("bugs");
$total_support = stats_getitems ("support");

$page .= "\n<h3>" . _("Trackers") . "</h3>\n";
if ($total_patch + $total_task + $total_support + $total_bugs > 0)
  $page .= "<ul>\n";

$content = $content_total = [];

$total_open = 0;
$trackers = [
  [ 'art' => 'support', 'key' => _("Support requests"),
    # TRANSLATORS: The next two msgids form one sentence.
    # The HTML comment in the second part is used to differentiate it
    # from the same texts used with other first part.
    'n' => ngettext ("%s new support request,", "%s new support requests,", 1),
    'c' => ngettext ("including %s already closed<!-- support request -->",
      "including %s already closed<!-- support requests -->", 1),
    'new' => ["%s new support request,", "%s new support requests,"],
    'cls' => ["including %s already closed<!-- support request -->",
      "including %s already closed<!-- support requests -->"],
  ],
  [ 'art' => 'bugs', 'key' => _("Bugs"),
    'n' => ngettext ("%s new bug,", "%s new bugs,", 1),
    'c' => ngettext ("including %s already closed<!-- bug -->",
      "including %s already closed<!-- bugs -->", 1),
    'new' => ["%s new bug,", "%s new bugs,"],
    'cls' => ["including %s already closed<!-- bug -->",
      "including %s already closed<!-- bugs -->"],
  ],
  [ 'art' => 'task', 'key' => _("Tasks"),
    'n' => ngettext ("%s new task,", "%s new tasks,", 1),
    'c' => ngettext ("including %s already closed<!-- task -->",
      "including %s already closed<!-- tasks -->", 1),
    'new' => ["%s new task,", "%s new tasks,"],
    'cls' => ["including %s already closed<!-- task -->",
      "including %s already closed<!-- tasks -->"],
  ],
  [ 'art' => 'patch', 'key' => _("Patches"),
    'n' => ngettext ("%s new patch,", "%s new patches,", 1),
    'c' => ngettext ("including %s already closed<!-- patch -->",
      "including %s already closed<!-- patches -->", 1),
    'new' => ["%s new patch,", "%s new patches,"],
    'cls' => ["including %s already closed<!-- patch -->",
      "including %s already closed<!-- patches -->"],
  ],
];
foreach ($trackers as $tr)
  {
    $art = $tr['art'];
    $total_art = ${"total_$art}"};
    if ($total_art <= 0)
      continue;
    $count = stats_getitems ($art, 0, "date >= $since AND date <= $until");
    $total = $count;
    $count_open = stats_getitems (
      $art, 3, "date >= $since AND date <= $until"
    );
    $total_open += $count_open;

    $page .= '<li>';
    $page .= sprintf (ngettext ($tr['new'][0], $tr['new'][1], $count), $count);
    $page .= " ";
    $page .= sprintf (ngettext ($tr['cls'][0], $tr['cls'][1], $count_open),
      $count_open);
    $page .= "</li>\n";
    $key = $tr[$key];
    $content[$key] = $count;
    $content_total[$key] = $total_art;
  }

if ($total_patch < 1 && $total_task < 1 && $total_support < 1 && $total_bugs < 1)
  $page .= _("The trackers look unused, no items were found");
else
  {
    $page .= "<li>";
    $page .=
      # TRANSLATORS: The next two msgids form one sentence.
      # The HTML comment in the second part is used to differentiate it
      # from the same texts used with other first part.
      sprintf (ngettext ("%s new item,", "%s new items,", $total), $total);
    $page .= " ";
    $page .=
      sprintf (ngettext ("including %s already closed<!-- item -->",
        "including %s already closed<!-- items -->", $total_open),
        $total_open) . "</li>\n";
    $page .= "</ul>\n<h3>" . _("New items per tracker / tracker total")
      . "</h3>\n";
    $page .= construct_graph ($content, $content_total);
    unset ($content, $content_total);
  }
$page .= "</p>\n<p>&nbsp;</p>\n";
$page .= "<h2>" . html_anchor (_("Overall"), "overall") . "</h2>\n";
$page .= "\n<h3>" . _("Accounts") . "</h3>\n<ul>\n";
$content = [];

$page .= '<li>';
$page .= sprintf (ngettext ("%s user", "%s users", $count_users), $count_users)
  . "</li>\n";
$count_groups_private = stats_getprojects ("", "0");
$page .= '<li>';
$page .=
  # TRANSLATORS: The next two msgids form one sentence.
  sprintf (ngettext ("%s project,", "%s projects,", $count_groups),
    $count_groups
  );
$page .= " ";
$page .=
  sprintf (ngettext ("including %s in private state",
   "including %s in private state", $count_groups_private),
   $count_groups_private
  );
$page .=  "</li>\n</ul>\n";

$result = db_query ("SELECT type_id,name FROM group_type ORDER BY name");
while ($eachtype = db_fetch_array ($result))
  $content[$eachtype['name']] = stats_getprojects ($eachtype['type_id']);

$page .= "<h3>" . _("Projects per group type") . "</h3>\n";
$page .= construct_graph ($content, 0);
$page .= '<h3>' . _("Trackers") . "</h3>\n<ul>\n";

$content = [];

$trackers = [
  [ 'art' => 'support', 'key' => _("Support requests"),
    # TRANSLATORS: The next two msgids form one sentence.
    # The HTML comment in the second part is used to differentiate it
    # from the same texts used with other first part.
    'n' => ngettext ("%s support request,", "%s support requests,", 1),
    'c' => ngettext ("including %s still open<!-- support request -->",
      "including %s still open<!-- support requests -->", 1),
    'new' => ["%s support request,", "%s support requests,"],
    'cls' => ["including %s still open<!-- support request -->",
      "including %s still open<!-- support requests -->"],
  ],
  [ 'art' => 'bugs', 'key' => _("Bugs"),
    'n' => ngettext ("%s bug,", "%s bugs,", 1),
    'c' => ngettext ("including %s still open<!-- bug -->",
      "including %s still open<!-- bugs -->", 1),
    'new' => ["%s bug,", "%s bugs,"],
    'cls' => ["including %s still open<!-- bug -->",
      "including %s still open<!-- bugs -->"],
  ],
  [ 'art' => 'task', 'key' => _("Tasks"),
    'n' => ngettext ("%s task,", "%s tasks,", 1),
    'c' => ngettext ("including %s still open<!-- task -->",
      "including %s still open<!-- tasks -->", 1),
    'new' => ["%s task,", "%s tasks,"],
    'cls' => ["including %s still open<!-- task -->",
      "including %s still open<!-- tasks -->"],
  ],
  [ 'art' => 'patch', 'key' => _("Patches"),
    'n' => ngettext ("%s patch,", "%s patches,", 1),
    'c' => ngettext ("including %s still open<!-- patch -->",
      "including %s still open<!-- patches -->", 1),
    'new' => ["%s patch,", "%s patches,"],
    'cls' => ["including %s still open<!-- patch -->",
      "including %s still open<!-- patches -->"],
  ],
];

$total = 0;
$total_open = 0;
foreach ($trackers as $tr)
  {
    $art = $tr['art'];
    $total_art = ${"total_$art"};
    $count = $total_art;
    $total += $count;
    if ($count <= 0)
      continue;
    $count_open = stats_getitems ($art, 1);
    $total_open += $count_open;
    $page .= '<li>';
    $page .=
      sprintf (ngettext ($tr['new'][0], $tr['new'][1], $count), $count);
    $page .= ' ';
    $page .= sprintf (
      ngettext ($tr['cls'][0], $tr['cls'][1], $count_open), $count_open
    );
    $page .= "</li>\n";
    $content[$tr['key']] = $count;
  }

$page .= "<li>";
$page .=
  # TRANSLATORS: The next two msgids form one sentence.
  # The HTML comment in the second part is used to differentiate it
  # from the same texts used with other first part.
  sprintf (ngettext ("%s item,", "%s items,", $total), $total);
$page .= " ";
$page .= sprintf (ngettext ("including %s still open<!-- item -->",
    "including %s still open<!-- items -->", $total_open),
    $total_open
);
$page .= "</li>\n</ul>\n\n";

$page .= '<h3>' . _("Items per tracker") . "</h3>\n";
$page .= construct_graph ($content, 0);
$css = "";
if ($widths != '')
  $css = '/css/graph-widths.php?widths=' . substr ($widths, 1);

$page .= "\n<h3>" . _("Most popular themes") . "</h3>\n";

# Get the more popular themes. 7 at most, all superior to 0%.
$themes_list = theme_list ();
$popular_themes = [];

# Check if there's already at least one user registered.
if ($count_users)
  {
    $page .= "<ul>\n";
    foreach ($themes_list as $theme)
      {
        # Get the number of users of the theme.
        $count = stats_getthemeusers (strtolower ($theme));
        if (strtolower ($theme) == strtolower ($GLOBALS['sys_themedefault']))
          # If it is the default theme, add the users that use the default.
          $count += stats_getthemeusers ("");

        # Compute the percentage of users using it.
        $percent = ($count / $count_users) * 100;

        # Store it only if superior to 0.
        if (round ($percent))
          $popular_themes[$theme] = $percent;
      }
    arsort ($popular_themes);
    $themes = '';

    foreach ($popular_themes as $theme => $percent)
      {
        $page .= "<li>$theme (" . round ($percent) . "%)</li>\n";
      }
    $page .= "</ul>\n";
  }
else
  $page .= _('No users yet.');

$page .= "</p>\n";

site_header (['title' => "Statistics", 'css' => $css]);
print $page;
site_footer (0);
?>
