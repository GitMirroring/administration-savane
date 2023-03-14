<?php
# Group administration start page.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2000-2003 Free Software Foundation
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2020, 2023 Ineiev
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

require_once ('../../include/init.php');
require_once ('../../include/account.php');

# Get current information.
$res_grp = group_get_result ($group_id);

if (db_numrows ($res_grp) < 1)
  exit_error (_("Invalid Group"));

# If the group isn't active, require being a member of the super-admin group.
if (db_result ($res_grp, 0, 'status') != 'A')
  session_require (['group' => 1]);

session_require (['group' => $group_id]);
site_project_header (['group' => $group_id, 'context' => 'ahome']);

print '<p>'
  . _("You can change all of your group configuration from here.")
  . "</p>\n";
utils_get_content ("project/admin/index_misc");

print "\n\n" . html_splitpage (1);
print $HTML->box_top (_("Features"));
# Activate features.
print "<a href=\"editgroupfeatures.php?group=$group\">"
  . _("Select Features") . '</a>';
print '<p class="smaller">'
  . _("Define which features you want to use for this group.")
  . "</p>\n";

$i = 0;
print $HTML->box_nextitem (utils_altrow ($i));

# Feature-specific configuration.
# TRANSLATORS: this is used in a comma-separated list as the argument
# of "Configure Features: %s".
$features = [
  "cookbook" => _("Cookbook"), "support" => _("Support Tracker"),
  "bugs" => _("Bug Tracker"), "task" => _("Task Manager"),
  "patch" => _("Patch Tracker"), "news" => _("News Manager"),
  "mail" => _("Mailing Lists")
];
$link = '';

foreach ($features as $case => $name)
  if ($case == "cookbook" || $project->Uses ($case))
    $link .= "<a href=\"../../$case/admin/?group=$group\">$name</a>, ";
$link = rtrim ($link, ', ');
# TRANSLATORS: the argument is comma-separated list of links to features.
printf (_("Configure Features: %s"), $link);
print '<p class="smaller">'
 . _("You can manage fields used, define query forms, manage mail "
     . "notifications,\netc.")
 . "</p>\n";

$i++;
print $HTML->box_nextitem (utils_altrow ($i));
print "<a href=\"editgroupnotifications.php?group=$group\">"
  . _("Set Notifications") . '</a>';
print '<p class="smaller">'
  . _("For many features, you can modify the type of email notification\n"
      . "(global/per category), the related address lists and the "
      . "notification\ntriggers.")
  . "</p>\n";

$i++;
print $HTML->box_nextitem (utils_altrow ($i));
print "<a href=\"conf-copy.php?group=$group\">"
  . _("Copy Configuration") . '</a>';
print '<p class="smaller">'
  . _("Copy the configuration of trackers of other groups you are member of.")
  . "</p>\n";

print $HTML->box_bottom ();
print "<br />\n";

print html_splitpage (2);

unset ($i);
print $HTML->box_top (_('Information'));
print "<a href=\"editgroupinfo.php?group=$group\">"
  . _("Edit Public Information") . '</a>';
print '<p class="smaller">';
printf (_("Your current short description is: %s"),
  db_result ($res_grp, 0, 'short_description')
);
print "</p>\n";

$i = 0;
print $HTML->box_nextitem (utils_altrow ($i));
print "<a href=\"history.php?group=$group\">" . _("Show History") . '</a>';
print '<p class="smaller">'
  . _("This allows you to keep tracks of important changes occurring on your\n"
      . "group configuration.")
  . "</p>\n";

print $HTML->box_bottom ();
print "<br />\n";

$i = 0;
print $HTML->box_top (_('Members'));
print "<a href=\"useradmin.php?group=$group\">" . _("Manage Members") . '</a>';
print '<p class="smaller">'
  . _("Add, remove members, approve or reject requests for inclusion.")
  . "</p>\n";

$i = 0;
print $HTML->box_nextitem (utils_altrow ($i));
print "<a href=\"squadadmin.php?group=$group\">" . _("Manage Squads") . '</a>';
print '<p class="smaller">'
  . _("Create and delete squads, add members to squads. Members of a squad "
      . "will\nshare this squad's items assignation, permissions, etc.")
  . "</p>\n";

$i++;
print $HTML->box_nextitem (utils_altrow ($i));
print "<a href=\"userperms.php?group=$group\">" . _("Set Permissions").'</a>';
print '<p class="smaller">'
  . _("Set members and group default permissions, set posting\n"
      . "restrictions.")
  . "</p>\n";

$i++;
print $HTML->box_nextitem (utils_altrow ($i));
print "<a href=\"../../people/createjob.php?group=$group\">" . _("Post Jobs")
      .'</a>';
print '<p class="smaller">' . _("Add a job offer.") . "</p>\n";

$i++;
print $HTML->box_nextitem (utils_altrow ($i));
print "<a href=\"../../people/editjob.php?group=$group\">"
  . _("Edit Jobs") . '</a>';
print '<p class="smaller">' . _("Edit jobs offers for this group.") . "</p>\n";

print $HTML->box_bottom ();
print html_splitpage (3);

site_project_footer ([]);
?>
