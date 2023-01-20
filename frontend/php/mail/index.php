<?php
# List mailing lists for a group.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2023 Ineiev
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
$rows = db_numrows ($result);

if (!$result || $rows < 1)
  {
    # TRANSLATORS: The argument is Savannah group (project) name.
    printf ('<h1>' . _("No Lists found for %s") . '</h1>',
      $project->getName ()
    );
    print '<p>'
      . _("Project administrators can add mailing lists using the admin "
          . "interface.")
    . "</p>\n";
    $HTML->footer ([]);
    exit;
  }

# The <br /> is here to put some space with the menu.  Please, keep it.
print "<br />\n";
function url_is_custom ($url)
{
  return $url && $url != 'http://';
}

for ($j = 0; $j < $rows; $j++)
  {
    $is_public = db_result ($result, $j, 'is_public');
    $pass = db_result ($result, $j, 'password');
    $list = db_result ($result, $j, 'list_name');

    # Pointer to listinfo or to the mailing list address, if no listinfo
    # is found.
    $url = $project->getTypeMailingListListinfoUrl ($list);
    if (url_is_custom ($url))
      $default_pointer = $url;
    else
      unset ($default_pointer);

    print html_image ("contexts/mail.png")
      . " <a href=\"$default_pointer\">$list</a> ";

    print '&nbsp;&nbsp;<em>'
      . db_result ($result, $j, 'description') . '</em>';
    print "\n<p class='smaller'>";

    $text = '';
    $url = $project->getTypeMailingListArchivesUrl ($list);
    if ($is_public && url_is_custom ($url))
      {
        # TRANSLATORS: the second argument is mailing list name.
        $text .= sprintf (
          _("To see the collection of prior posting to the list,\n"
            . "visit the <a href=" . '"%1$s">%2$s' . " archives</a>."),
          $url, $list
        );
        $text .= "\n<br />\n";
      }

    $url = $project->getTypeMailingListArchivesPrivateUrl ($list);
    if (!$is_public && url_is_custom ($url))
      {
        # TRANSLATORS: the second argument is mailing list name.
        $text .= sprintf (
          _("To see the collection of prior posting to the list,\nvisit the "
            . "<a href=".'"%1$s">%2$s'." archives</a> (authorization "
            . "required)."),
          $url, $list
        );
        $text .= "\n<br />\n";
      }
    $url = $project->getTypeMailingListAddress ($list);
    if ($url)
      {
        # TRANSLATORS: the argument is mailing list address.
        $text .= sprintf (
          _("To post a message to all the list members, write to %s."),
          utils_email ($url)
        );
        $text .= "\n<br />\n";
      }
    else
      $text .= '<br /><span class="error">'
        . _("No mailing list address was found, the configuration of the\n"
            . "server is probably broken, contact the admins!")
        . "</span><br />\n";

    # Subscribe, unsubscribe:
    # if these fields are empty, go back on the listinfo page.
    $url = $project->getTypeMailingListSubscribeUrl ($list);
    $url1 = $project->getTypeMailingListUnsubscribeUrl ($list);
    $url2 = $project->getTypeMailingListListinfoUrl ($list);
    if (url_is_custom ($url) && url_is_custom ($url1))
      {
        if (url_is_custom ($url))
          {
            $text .= "<a href=\"$url\">"
              . _("Subscribe to the list.") . "</a>";
            $text .= "\n<br />\n";
          }
        if (url_is_custom ($url1))
          {
            $text .= "<a href=\"$url1\">"
              . _("Unsubscribe from the list.") . "</a>";
            $text .= "\n<br />\n";
          }
      }
    elseif (url_is_custom ($url2))
      {
        $text .= sprintf (
          _("You can subscribe to the list\n"
            . "and unsubscribe from the list by following\n"
            . "instructions on the <a href=\"%s\">list information page</a>."),
          $url2
        );
        $text .= "\n<br />\n";
      }
    $url = $project->getTypeMailingListAdminUrl ($list);
    if (url_is_custom ($url))
      {
        $text .= sprintf (
          _("Project administrators can use the\n<a href=\"%s\">"
            . "administrative interface</a> to manage the list."),
          $url
        );
        $text .= "\n<br />\n";
      }
    if (substr ($text, -7) == "<br />\n")
      $text = substr ($text, 0, -7);
    print "$text</p>\n";
  }
site_project_footer ([]);
?>
