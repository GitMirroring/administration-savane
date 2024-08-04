<?php
# Manage user preferences.
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
foreach (['sane', 'utils', 'form', 'my/admin/general'] as $i)
  require_once ("../../include/$i.php");
$pref_list = ['email_encrypted', 'nonfixed_feedback', 'keep_only_one_session',
  'quiet_ssh', 'reverse_comments_order', 'stone_age_menu', 'use_bookmarks'
];
$fb_on_set = [
 'stone_age_menu' =>
   _("Stone age menu activated, it will be effective the next time "
     . "a page is loaded")
];
$true_fields = ['update', 'theme_rotate_jump', 'form_email_hide'];
foreach ($pref_list as $p)
  $true_fields[] = "form_$p";
extract (sane_import ('post',
  [
    'true' => $true_fields,
    'no_quotes' => ['form_timezone', 'user_theme']
  ]
));

if ($update)
  {
    # Define actions to do before selecting theme.
    function update_theme ()
    {
      global $user_theme, $theme_rotate_jump, $form_timezone, $form_email_hide;

      # Update theme.
      if ($user_theme == "Default")
        $user_theme = "";
      elseif ($user_theme !== 'rotate' && $user_theme !== 'random')
        $user_theme = theme_validate ($user_theme);

      if ($theme_rotate_jump == "1")
        theme_rotate_jump ($user_theme);

      if ($form_timezone == 100)
        $form_timezone = "GMT";

      $success = db_autoexecute (
        'user',
         [
           'email_hide' => ($form_email_hide ? "1" : "0"),
           'theme' => $user_theme, 'timezone' => $form_timezone,
         ],
         DB_AUTOQUERY_UPDATE, "user_id = ?", [user_getid ()]
      );
      if ($success)
        fb (_("Database successfully updated"));
      else
        fb (_("Failed to update the database"),1);
    }
  }
# FIXME init.php has to be included after parsing POST variables and defining
# update_theme that may be used in theme.php that is included in init.php.
# This isn't the most clear possible way to do these things.
require_once ('../../include/init.php');
require_once ('../../include/timezones.php');
form_check ('update');
extract (sane_import ('request', ['pass' => 'feedback']));
session_require (['isloggedin' => 1]);

if ($update)
  foreach ($pref_list as $pref)
    {
      global $fb_on_set;
      $fb = array_key_exists ($pref, $fb_on_set)? $fb_on_set[$pref]: null;
      my_sync_preference ($pref, $fb);
    }

# Print form and links.
site_user_header (['context' => 'account']);
# Get global user vars.
$res_user = db_execute (
  "SELECT * FROM user WHERE user_id = ?", [user_getid ()]
);
$row_user = db_fetch_array ($res_user);

print '<p>' . _("You can change all of your account features from here.")
  . "</p>\n";
utils_get_content ("account/index_intro");
print form_tag ();
print html_h (2, _("Significant Arrangements"));

print $HTML->box_top (_('Authentication Setup'));
print '<p><a href="change.php?item=password">' . _("Change Password")
  . "</a></p>\n";
print '<p class="smaller">'
  . _("This password gives access to the web interface.");
utils_get_content ("account/index_passwd");
print "</p>\n";

$keynum = (count (account_get_authorized_keys ()));

$i = 0;
function print_box_next_item ()
{
  global $i, $HTML;
  print $HTML->box_nextitem (utils_altrow ($i));
}
print_box_next_item ();
print '<p><a href="editsshkeys.php">';
if ($keynum > 0)
  printf (
    ngettext (
      "Edit the %d SSH Public Key registered",
      "Edit the %d SSH Public Keys registered",
      $keynum),
    $keynum
  );
else
  print _("Register an SSH Public Key");

print "</a></p>\n<p class='smaller'>";
utils_get_content ("account/index_ssh");
print "</p>\n";

$i++;
print_box_next_item ();
print '<p><a href="change.php?item=gpgkey">' . _("Edit GPG Key")
  . "</a></p>\n";
print '<p class="smaller">';
utils_get_content ("account/index_gpg");
print "</p>\n";

$i++;
print_box_next_item ();
print '<p><a href="sessions.php">';
printf (
  ngettext (
    "Manage the %d opened session", "Manage the %d opened sessions",
    session_count (user_getid ())
  ),
  session_count (user_getid ())
);
print "</a></p>\n";
print_box_next_item ();

my_pref_cbox (
  'keep_only_one_session',
  _("Keep only one session opened at a time")
);
print '<p class="smaller">'
  . _("By default, you can open as many session concurrently as you want. "
      . "But you\nmay prefer to allow only one session to be opened at a "
      . "time, killing previous\nsessions each time you log in.");
print "</p>\n";
print $HTML->box_bottom ();
$update_btn = '<p class="center"><span class="clearr" />'
  . form_submit (_("Update")) . "</span></p>\n";
print $update_btn;

print $HTML->box_top (_('Identity Record'));

print '<p>';
printf (_("Account #%s"), $row_user['user_id']);
print "</p>\n<p class='smaller'>";
printf (_("Your login is %s."), "<strong>{$row_user['user_name']}</strong>");
# TRANSLATORS: the argument is registration date.
printf (
  ' ' . _("You registered your account on %s."),
  '<strong>' . utils_format_date ($row_user['add_date']) . '</strong>'
);
print "</p>\n";

$i = 0;
print_box_next_item ();
print '<p><a href="change.php?item=realname">' . _("Change Real Name")
  . "</a></p>\n";
print '<p class="smaller">';
# TRANSLATORS: the argument is full name.
printf (_("You are %s."), "<strong>{$row_user['realname']}</strong>");
print "</p>\n";

$i++;
print_box_next_item ();
print '<p><a href="resume.php">' . _("Edit Resume and Skills") . "</a></p>\n";
print '<p class="smaller">'
  . _("Details about your experience and skills may be of interest to other "
      . "users\nor visitors.")
  . "</p>\n";

$i++;
print_box_next_item ();
print "<p><a href=\"{$sys_home}users/{$row_user['user_name']}\">"
  . _("View your Public Profile") . "</a></p>\n";
print '<p class="smaller">' . _("Your profile can be viewed by everybody.")
 . "</p>\n";

print $HTML->box_bottom ();

print $HTML->box_top (_('Mail Setup'));

print '<p><a href="change.php?item=email">' . _("Change Email Address")
  . "</a></p>\n";
print '<p class="smaller">';
printf (
  _("Your current address is %s. It is essential to us that this\n"
    . "address remains valid. Keep it up to date."),
  "<strong>{$row_user['email']}</strong>"
);
print "</p>\n";

$i = 0;
print_box_next_item ();

print '<p><a href="change_notifications.php">'
  . _("Edit Personal Notification Settings") . "</a></p>\n";
print '<p class="smaller">'
  . _("Here is defined when the trackers should send email notifications. It\n"
      . "permits also to configure the subject line prefix of sent mails.")
  . "</p>\n";

$i++;
print_box_next_item ();
print '<p><a href="cc.php">' . _("Cancel Mail Notifications") . "</a></p>\n";
print '<p class="smaller">' . _("Here, you can cancel all mail notifications.")
  . "</p>\n";

print $HTML->box_bottom ();

print html_h (2, _("Secondary Arrangements"));

print $HTML->box_top (_('Optional Features'));

my_pref_cbox ("use_bookmarks", _("Use integrated bookmarks"));
print '<p class="smaller">'
  . _("By default, integrated bookmarks are deactivated to avoid redundancy "
      . "with\nthe bookmark feature provided by most modern web browsers. "
      . "However, you may\nprefer integrated bookmarks if you frequently use "
      . "different workstations\nwithout web browsers bookmarks "
      . "synchronization.")
  . "</p>\n";

$i = 0;
print_box_next_item ();

my_pref_cbox (
  "email_hide", _("Hide email address from your account information"),
  $row_user['email_hide']
);
print '<p class="smaller">'
  . _("When checked, the only way for users to get in touch with you would be "
      . "to\nuse the form available to logged-in users. It is generally a bad "
      . "idea to choose\nthis option, especially if you are a project "
      . "administrator.")
  . "</p>\n";

$i++;
print_box_next_item ();
my_pref_cbox ('email_encrypted', _("Encrypt emails when resetting password"));
print '<p class="smaller">'
  . _("When checked, Savannah will encrypt email messages\nwith your "
      . "registered public GPG key when resetting password is requested.\n"
      . "If no suitable key is available, the messages still go unencrypted.")
  . "</p>\n";

$i++;
print_box_next_item ();
my_quiet_ssh_control ();

print $HTML->box_bottom ();
print $update_btn;
print $HTML->box_top (_('Cosmetics Setup'));

# The select box comes before the name of the category so all the clickable
# part of the form stays on a same line (better UI design).
print '<p>'
  . html_build_select_box_from_arrays (
      $TZs, $TZs, 'form_timezone', $row_user['timezone'], true, 'GMT',
      false, 'Any', false, _('Timezone')
    );
print ' ' . _("Timezone") . "</p>\n";
print '<p class="smaller">'
  . _("No matter where you live, you can see all dates and times as if it "
      . "were in\nyour neighborhood.")
  . "</p>\n";

$i = 0;
print_box_next_item ();

print "<p>";
html_select_theme_box ("user_theme", $row_user['theme']);
print ' ' . _("Theme") . "</p>";

if ("rotate" === $row_user['theme'] || 'random' === $row_user['theme'])
  print "<p>\n" . form_checkbox ('theme_rotate_jump')
    . html_label ("theme_rotate_jump", _("Jump to the next theme")) . "</p>\n";
print '<p class="smaller">'
  . _("Not satisfied with the default color theme of the interface?")
  . "</p>\n";

if (!theme_guidelines_check (SV_THEME))
  {
    print '<p class="smaller"><span class="warn">'
      . _("The theme you are currently using does not follow the latest "
          . "Savane CSS\nguidelines. As a result, page layout may be more "
          . "or less severely broken. It is\nnot advised to use this theme.")
      . ' ';
    # If the non-valid theme is the default one, tell users they should fill
    # a support request.
    if (SV_THEME == $GLOBALS['sys_themedefault'])
      {
        # TRANSLATORS: the argument is site name (like Savannah).
        $link_text =
          sprintf (
            _("%s administrators should be asked to take\ncare of "
              . "Savane CSS Guidelines, since it is the default theme."),
            $sys_name
          );
        print utils_link (
          "{$sys_home}support/?group=$sys_unix_group_name",
          $link_text, "warn"
        );
      }
    print '</span></p>';
  }

$i++;
print_box_next_item ();

my_pref_cbox (
  "reverse_comments_order",
  _("Print comments from the oldest to the latest")
);
print '<p class="smaller">'
  . _("By default, comments are listed in reverse chronological order. This "
      . "means\nthat for a given item, comments are printed from the latest "
      . "to the oldest. If\nthis behavior does not suit you, select this "
      . "option.")
  . "</p>\n";

$i++;
print_box_next_item ();

my_pref_cbox ("stone_age_menu", _("Use the Stone Age menu"));
print '<p class="smaller">'
  . _("By default, the top menu includes links to all relevant pages\n"
      . "context (group area, personal area) in dropdown submenus. However,\n"
      . "the dropdown submenus may not work with a few lightweight browsers,\n"
      . "for instance, NetSurf (as of 3.10, released in 2020).\n"
      . "Selecting this option enables an old-fashioned submenu like the one\n"
      . "shipped in older Savane releases (< 2.0).")
  . "</p>\n";

$i++;
print_box_next_item ();

my_pref_cbox ("nonfixed_feedback", _("Show feedback in relative position"));
print '<p class="smaller">'
  . _("By default, the feedback box appear as a fixed box on top of the "
      . "window.\nIf you check this option, the feedback will\n"
      . "be added in the page flow, after the top menu.")
   . "</p>\n";

print $HTML->box_bottom ();
print "$update_btn</form>\n";
print html_h (2, _('Account Deletion'));

print '<p><a href="change.php?item=delete">' . _("Delete Account")
  . "</a></p>\n";
print '<p class="smaller">';
# TRANSLATORS: the argument is site name (like Savannah).
printf (
  _("If you are no longer member of any project and do not intend to use\n"
    . "%s further, you may want to delete your account. This action cannot "
    . "be undone\nand your current login will be forever lost."),
  "<strong>$sys_name</strong>"
);
print "</p>\n";

$HTML->footer ([]);
?>
