<?php
# List mailing lists for a group.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
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

require_once ('../include/init.php');
require_once ('../include/html.php');

if (!$group_id)
  exit_no_group ();

exit_test_usesmail ($group_id);
site_project_header (['group' => $group_id, 'context' => 'mail']);

$public_flag = '1';
if (user_isloggedin () && user_ismember ($group_id))
  $public_flag = '0, 1';

$result = db_execute ("
  SELECT * FROM mail_group_list WHERE group_id = ?
  AND is_public IN ($public_flag) ORDER BY list_name ASC", [$group_id]
);

if (!db_numrows ($result))
  {
    print "<p><strong>";
    # TRANSLATORS: The argument is Savannah group (project) name.
    printf (_("No mailing lists found for %s."), $project->getName ());
    print "</strong></p>\n<p>"
      . _("Group administrators can add mailing lists using the admin "
          . "interface.")
    . "</p>\n";
    $HTML->footer ([]);
    exit;
  }

function url_is_real ($url)
{
  return $url && $url != 'http://';
}

print "<dl>";
while ($row = db_fetch_array ($result))
  {
    $is_public = $row['is_public'];
    $list = $row['list_name'];

    print "<dt>" . html_image ("contexts/mail.png") . "\n";
    # Pointer to listinfo or to the mailing list address, if no listinfo
    # is found.
    $url = $project->getTypeMailingListListinfoUrl ($list);
    if (url_is_real ($url))
      $list_string = "<a href=\"$url\">$list</a> ";
    else
      $list_string = "$list ";

    print "$list_string&nbsp;&nbsp;<em>" . $row['description'] . "</em>\n";
    print "</dt>\n<dd class='smaller'>";

    $lines = [];
    $url = $project->getTypeMailingListArchivesUrl ($list);
    if ($is_public && url_is_real ($url))
      # TRANSLATORS: the second argument is mailing list name.
      $lines[] = sprintf (
        _("To see the collection of prior posting to the list,\n"
          . "visit the <a href=" . '"%1$s">%2$s' . " archives</a>."),
        $url, $list
      );

    $url = $project->getTypeMailingListArchivesPrivateUrl ($list);
    if (!$is_public && url_is_real ($url))
      # TRANSLATORS: the second argument is mailing list name.
      $lines[] = sprintf (
        _("To see the collection of prior posting to the list,\nvisit the "
          . "<a href=".'"%1$s">%2$s'." archives</a> (authorization "
          . "required)."),
        $url, $list
      );
    $url = $project->getTypeMailingListAddress ($list);
    if ($url)
      # TRANSLATORS: the argument is mailing list address.
      $lines[] = sprintf (
        _("To post a message to all the list members, write to %s."),
        utils_email ($url)
      );
    else
      $lines[] = "<span class='error'>"
        . _("No mailing list address was found, the configuration of the\n"
            . "server is probably broken, contact the admins!")
        . "</span>";
    # Subscribe, unsubscribe:
    # if these fields are empty, go back on the listinfo page.
    $url = $project->getTypeMailingListSubscribeUrl ($list);
    $url1 = $project->getTypeMailingListUnsubscribeUrl ($list);
    $url2 = $project->getTypeMailingListListinfoUrl ($list);
    if (url_is_real ($url) && url_is_real ($url1))
      {
        $lines[] = "<a href=\"$url\">" . _("Subscribe to the list.") . "</a>";
        $lines[] = "<a href=\"$url1\">"
          . _("Unsubscribe from the list.") . "</a>";
      }
    elseif (url_is_real ($url2))
      $lines[] = sprintf (
        _("You can subscribe to the list\n"
          . "and unsubscribe from the list by following\n"
          . "instructions on the <a href=\"%s\">list information page</a>."),
        $url2
      );
    $url = $project->getTypeMailingListAdminUrl ($list);
    if (url_is_real ($url))
      $lines[] = sprintf (
        _("Group administrators can use the\n<a href=\"%s\">"
          . "administrative interface</a> to manage the list."),
        $url
      );
    print join ("<br />\n", $lines) . "</dd>\n";
  } # while ($row = db_fetch_array ($result))
print "</dl>\n";
site_project_footer ([]);
?>
