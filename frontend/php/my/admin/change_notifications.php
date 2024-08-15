<?php
# Changing notifications.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2001, 2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2024 Ineiev
#
# Modified 2016 Karl Berry (trivial wording changes)
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

require_once ('../../include/init.php');
foreach (['init', 'account', 'my/admin/general'] as $i)
  require_once ("../../include/$i.php");
require_directory ("trackers");

$notif_arr = [
  'notify_unless_im_author', 'notify_item_closed',
  'notify_item_statuschanged', 'skipcc_postcomment',
  'skipcc_updateitem', 'removecc_notassignee',
];
$names = [
  'true' => ['update'],
  'digits' => [['form_frequency', [0, 3]]],
  'pass' => 'form_subject_line', # Validated later.
];

foreach ($notif_arr as $n)
  $names['true'][] = "form_$n";

extract (sane_import ('post', $names));
form_check ('update');

function update_subject_line ($form_subject_line)
{
  if (!preg_replace("/ /", "", $form_subject_line))
    {
      if (user_get_preference ("subject_line"))
        user_unset_preference ("subject_line");
      return;
    }
  $permitted = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVW'
    . 'XYZ0123456789-_[]()&=$*:!,;?./%$ <>|';
  if (strspn ($form_subject_line, $permitted) != strlen ($form_subject_line))
    {
      $msg = sprintf (
        _("Characters other than '%s' in the proposed subject line,\n"
        . "subject line configuration skipped."), $permitted
      );
      fb ($msg, 1);
      return;
    }
  if (user_set_preference ("subject_line", $form_subject_line))
    fb (_("Successfully configured subject line."));
}

if ($update)
  {
    $success = false;
    foreach ($notif_arr as $n)
      if (${"form_$n"})
        $success += user_set_preference ($n, 1);
      else
        $success += user_unset_preference ($n);

    if ($success == count ($notif_arr))
      fb (_("Successfully set notification exceptions."));
    else
      fb (_("Failed to set notification exceptions."), 1);

    if (user_set_preference ("batch_frequency", $form_frequency))
      fb (_("Successfully updated reminder settings."));
    else
      fb (_("Failed to update reminder setting."), 1);

    update_subject_line ($form_subject_line);
  } # if ($update)

site_user_header (
  ['title' => _("Mail Notification Settings"), 'context' => 'account']
);

print html_h (2, _("Notification Exceptions"));
print '<p>'
  . _("When you post or update an item, you are automatically added to\nits "
      . "Carbon-Copy list to receive notifications regarding future updates. "
      . "You can\nalways remove yourself from an item Carbon-Copy list.")
  . "</p>\n<p>"
  . _("If an item is assigned to you, you will receive notifications as long "
      . "as\nyou are the assignee; however, you will not be added to the "
      . "Carbon-Copy list.\nIf you do not post any comment or update to the "
      . "item while you are the\nassignee, and the item gets reassigned, you "
      . "will not receive further update\nnotifications.")
  . "</p>\n<p>"
  . _("Here, you can tune your notification settings.") . "</p>\n";

print "\n" . form_header ();

print '&nbsp;&nbsp;<span class="preinput">'
  . _("Send notification to me only when:") . "</span><br />\n&nbsp;&nbsp;";

$my_pref_cbox_compact = true;
$pref_arr = [
  "notify_unless_im_author" => _("I am not the author of the item update"),
  "notify_item_closed" => _("the item was closed"),
  "notify_item_statuschanged" => _("the item status changed"),
];

foreach ($pref_arr as $n => $t)
  my_pref_cbox ($n, $t);

print '<span class="preinput">' . _("Do not add me to Carbon-Copy when:")
  . "</span><br />\n&nbsp;&nbsp;";


$pref_arr = [
  "skipcc_postcomment" => _("I post a comment"),
  "skipcc_updateitem" =>
    _("I update a field, add dependencies, attach file, etc"),
];

foreach ($pref_arr as $n => $t)
  my_pref_cbox ($n, $t);

print '<span class="preinput">' . _("Remove me from Carbon-Copy when:")
  . "</span><br />\n&nbsp;&nbsp;";

my_pref_cbox ("removecc_notassignee", _("I am no longer assigned to the item"));

print html_h (2, _("Subject Line"));
print '<p>';
printf (_("The header &ldquo;%s&rdquo; will always be included, and when\n"
  . "applicable, so will &ldquo;%s,&rdquo; &ldquo;%s,&rdquo; and "
  . "&ldquo;%s.&rdquo;"),
  "X-Savane-Server", "X-Savane-Project", "X-Savane-Tracker", "X-Savane-Item-ID"
);
print "</p>\n<p>";
printf (_("Another option for message filtering is to configure the prefix "
  . "of\nthe subject line with the following form. In this form, you can use "
  . "the strings\n&ldquo;%s,&rdquo; &ldquo;%s,&rdquo; &ldquo;%s,&rdquo; and "
  . "&ldquo;%s.&rdquo;\nThey will be replaced by the appropriate values. If "
  . "you leave this form empty,\nyou will receive the default subject line."),
  "%SERVER", "%PROJECT", "%TRACKER", "%ITEM"
);
print "</p>\n";

print '<span class="preinput">'
  . html_label ("form_subject_line", _("Subject Line:"))
  . "</span><br />\n&nbsp;&nbsp;";
print "<input name='form_subject_line' id='form_subject_line' size='50'\n"
  . "type='text' value=\"" . user_get_preference ("subject_line")
  . "\" />\n\n";

print html_h (2, _("Reminder"));
print '<p>' . _("You can also receive reminders about opened items assigned to\n"
  . "you, when their priority is higher than 5.")
  . "</p>\n";

$frequency = [
  # TRANSLATORS: this is frequency.
  "0" => _("Never"), "1" => _("Daily"), "2" => _("Weekly"), "3" => _("Monthly")
];

print '<span class="preinput">'
  . html_label ("form_frequency", _("Frequency of reminders:"))
  . "</span><br />\n&nbsp;&nbsp;";

print html_build_select_box_from_array (
  $frequency, "form_frequency", user_get_preference("batch_frequency")
);
print "<br />\n" . form_footer (_("Update"));
site_user_footer ([]);
?>
