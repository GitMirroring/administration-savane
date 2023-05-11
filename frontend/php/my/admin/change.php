<?php
# Generic user settings editor.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry
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

require_once ('../../include/init.php');
require_once ('../../include/sendmail.php');
require_once ('../../include/gpg.php');

require (utils_get_content_filename ("gpg-sample"));

session_require (['isloggedin' => '1']);
extract (sane_import ('request',
  [
    'strings' =>
      [
        [
          'item',
          ['delete', 'realname', 'timezone', 'password', 'gpgkey', 'email']
        ],
        ['step', ['confirm', 'confirm2', 'discard']],
      ],
    'true' => ['update', 'test_gpg_key'],
    'hash' => ['session_hash', 'confirm_hash', 'form_id'],
  ]
));

if (!$item)
  exit_missing_param ();

$success = FALSE;

# To delete the account, the user must have first quitted all groups.
# Yes, this form could do automatically this, but when a user quit his group
# it send mails to people that should be informed, so it is best to push
# the user to use the relevant form than to reimplement everything here
if ($item == 'delete')
  {
    $res_check = db_execute (
      "SELECT group_id FROM user_group WHERE user_id = ?",
      [user_getid ()]
    );
    $rows = db_numrows ($res_check);
    $exists = false;
    # Check if the user is a member of any _active_ group.
    for ($i = 0; $i < $rows && !$exists; $i++)
      {
        $group_id = db_result ($res_check, $i, 'group_id');
        $r = db_execute ("
          SELECT unix_group_name FROM groups
          WHERE group_id = ? and status = 'A'",
          [$group_id]
        );
        if (db_numrows ($r))
          $exists = true;
      }
    if ($exists)
      exit_error (
        _("You must quit all groups before requesting account deletion. "
          . "If you registered a group that is still pending,\n"
          . "please ask site admins to cancel that registration."));
  }

# Update the database.
if ($update)
  {
    if (!form_check ($form_id))
      exit_error (_("Exiting"));

    # Update the database and redirect to account conf page.
    if ($item == "realname")
      {
        extract (sane_import ('request', ['pass' => 'newvalue']));
        if (!account_realname_valid ($newvalue))
          fb (_("You must supply a new real name."), 1);
        else
          {
            $newvalue = account_sanitize_realname ($newvalue);
            $success = db_autoexecute (
              'user', ['realname' => $newvalue], DB_AUTOQUERY_UPDATE,
              "user_id = ?", [user_getid ()]
            );
            if ($success)
              fb (_("Real Name updated."));
            else
              fb (_("Failed to update the database."), 1);
          }
      }
    elseif ($item == "timezone")
      {
        extract (sane_import ('request', ['digits' => 'newvalue']));
        if ($newvalue == 100)
          $newvalue = "GMT";
        $success = db_autoexecute (
          'user', ['timezone' => $newvalue], DB_AUTOQUERY_UPDATE,
          "user_id = ?", [user_getid ()]
        );
        if ($success)
          fb (_("Timezone updated."));
        else
          fb (_("Failed to update the database."), 1);
      }
    elseif ($item == "password")
      {
        extract (sane_import ('request',
          ['pass' => ['oldvalue', 'newvalue', 'newvaluecheck']]
        ));
        require_once ('../../include/account.php');
        $success = 1;
        # Check against old pw.
        db_execute ("SELECT user_pw, status FROM user WHERE user_id = ?",
          [user_getid ()]
        );
        $row_pw = db_fetch_array ();

        if (!account_validpw ($row_pw['user_pw'], $oldvalue))
          {
            # Use basic authentication via user table.
            fb (_("Old password is incorrect."), 1);
            $success = 0;
          }

        # Do standard password sanity checks and update table.
        if (!$newvalue)
          {
            fb (_("You must supply a password."), 1);
            $success = 0;
          }
        if ($newvalue != $newvaluecheck)
          {
            fb (_("New Passwords do not match."), 1);
            $success = 0;
          }
        if (!account_pwvalid ($newvalue))
          $success = 0;

        # Update only if everything was ok before.
        if ($success)
          {
            $success = db_autoexecute (
              'user', ['user_pw' => account_encryptpw($newvalue)],
               DB_AUTOQUERY_UPDATE, "user_id = ?", [user_getid ()]
            );
            if ($success)
              fb (_("Password updated."));
            else
              fb (_("Failed to update the database."), 1);
          }
      }
    elseif ($item == "gpgkey")
      {
        extract (sane_import ('request', ['pass' => ['newvalue']]));
        $success = db_autoexecute (
          'user', ['gpg_key' => $newvalue], DB_AUTOQUERY_UPDATE,
          "user_id = ?", [user_getid ()]
        );
        if ($success)
          fb (_("GPG Key updated."));
        else
          fb (_("Failed to update the database."), 1);
      }
    elseif ($item == "email")
      {
        extract (sane_import ('request',
          [
            'preg' =>
              [
                [
                  'newvalue',
                  '/^[a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+\.)+[a-zA-Z0-9]+$/'
                ]
              ]
          ]
        ));
        # First step.
        if (!$step)
          {
            require_once ('../../include/account.php');
            $newvalue = preg_replace ('/\s/', '', $newvalue);
            # Proceed only if it is a valid email address.
            if (account_emailvalid($newvalue))
              {
                # Build a new confirm hash.
                $confirm_hash = substr (md5 ($session_hash . time()), 0, 16);
                $res_user = db_execute (
                  "SELECT * FROM user WHERE user_id = ?", [user_getid ()]
                );
                if (db_numrows ($res_user) < 1)
                  exit_error (
                    _("Invalid User"), _("That user does not exist.")
                  );

                $row_user = db_fetch_array ($res_user);
                $success = db_autoexecute (
                  'user',
                  ['confirm_hash' => $confirm_hash, 'email_new' => $newvalue],
                  DB_AUTOQUERY_UPDATE, "user_id = ?", [user_getid ()]
                );
                if (!$success)
                  fb (_("Failed to update the database."), 1);
                else
                  {
                    fb (_("Database updated."));

                    if (!empty ($sys_https_host))
                      $url = "https://$sys_https_host";
                    else
                      $url = "http://$sys_default_domain";
                    $url .= "{$sys_home}my/admin/change.php?"
                      . "item=email&confirm_hash=$confirm_hash";
                    $message = sprintf (
                      # TRANSLATORS: the argument is site name (like Savannah).
                      _("You have requested a change of email address on %s.\n"
                        . "Please visit the following URL to complete the "
                        . "email change:"),
                      $sys_name);
                    $message .= "\n\n$url&step=confirm\n\n";
                    # TRANSLATORS: the argument is site name (like Savannah).
                    $message .= sprintf (_("-- the %s team."), $sys_name)
                      . "\n";

                    # TRANSLATORS: the argument is site name (like Savannah).
                    $warning_message =
                      sprintf (
                        _("Someone, presumably you, has requested a change "
                          . "of email address on %s.\nIf it wasn't you, maybe "
                          . "someone is trying to steal your account...")
                        . "\n\n", $sys_name
                      )
                      . sprintf (
                          _("Your current address is %1\$s, the supposedly new "
                            . "address is %2\$s."),
                          $row_user['email'], $newvalue
                        )
                      . "\n "
                      . _("If you did not request that change, please visit "
                          . "the following URL\nto discard the email change "
                          . "and report the problem to us:")
                    . "\n\n$url&step=discard\n\n";

                    # TRANSLATORS: the argument is site name (like Savannah).
                    $warning_message .=
                      sprintf (_("-- the %s team."), $sys_name) . "\n";

                    $success = sendmail_mail (
                      [ 'from' => "$sys_mail_replyto@$sys_mail_domain",
                        'to' => $newvalue],
                      [ 'subject' => $sys_name . ' ' . _("Verification"),
                        'body' => $message]
                    );
                    # yeupou--gnu.org 2003-11-09:
                    # Send also a warning to the current mail address,
                    # just in case.
                    # You can call that paranoia but
                    #  - someone can find a session open on a computer
                    #  - ask for change the mail address
                    #  - after the change, use the lost password process
                    #  ... and so change the password without knowing and
                    #  without having the user noticing that something bad
                    # is going on.
                    # The next step is probably to print the mail change
                    # request on account with the possibility to discard.
                    sendmail_mail (
                      [ 'from' => "$sys_mail_replyto@$sys_mail_domain",
                        'to' => $row_user['email']],
                      [ 'subject' => $sys_name . ' ' . _("Verification"),
                        'body' => $warning_message]
                    );
                    if ($success)
                      {
                        # TRANSLATORS: the argument is email address.
                        $msg = sprintf (
                          _("Confirmation mailed to %s."), $newvalue
                        );
                        fb ($msg . ' '
                          . _("Follow the instructions in the email to "
                              . "complete the email change.")
                        );
                      }
                    else
                      fb (
                        _("The system reported a failure when trying to "
                          . "send\nthe confirmation mail. Please retry and "
                          . "report that problem to\nadministrators."),
                        1
                      );
                  }
              }
          }
        elseif ($step == "confirm")
          {
            # Cf. form at the end.
          }
        # Additional step with a direct POST request to avoid CSRF attacks.
        elseif ($step == "confirm2")
          {
            $success = false;
            if (preg_match ("/^[a-f0-9]{16}$/", $confirm_hash))
              {
                $res_user = db_execute (
                  "SELECT * FROM user WHERE confirm_hash = ?", [$confirm_hash]
                );
                if (db_numrows ($res_user) > 1)
                  $ffeedback = ' '
                   . _("This confirmation hash is included in DB more than "
                       . "once.");
                elseif (db_numrows ($res_user) < 1)
                  exit_error (' ' . _("Invalid confirmation hash."));
                else
                  $success = true;
              }
            else
              exit_error (' ' . _("Invalid confirmation hash."));
            if ($success)
              {
                $row_user = db_fetch_array ($res_user);
                $success = db_autoexecute (
                 'user',
                  [ 'email' => $row_user['email_new'],
                    'confirm_hash' => null, 'email_new' => null ],
                  DB_AUTOQUERY_UPDATE,
                  "user_id = ? AND confirm_hash = ?",
                  [user_getid (), $confirm_hash]
                );

                if ($success)
                  fb (_("Email address updated."));
                else
                  fb (_("Failed to update the database."), 1);
              }
          }
        elseif ($step == "discard")
          {
            # Just remove stuff added.
            $success = db_autoexecute ('user',
              ['confirm_hash' => null, 'email_new' => null],
              DB_AUTOQUERY_UPDATE,
              "user_id = ? AND confirm_hash = ?", [user_getid (), $confirm_hash]
            );
            if ($success)
              fb (_("Address change process discarded."));
            else
              fb (
                _("Failed to discard the address change process, please "
                  . "contact\nadministrators."),
                1
              );
          }
        else
          fb (
            _("Unable to understand what to do, parameters are probably "
              . "missing"),
            1
          );
      }
    elseif ($item == "delete")
      {
        extract (sane_import ('request',
          ['strings' => [['newvalue', ['deletionconfirmed']]]]
        ));
        # First step
        if (!$step && $newvalue == 'deletionconfirmed')
          {
            # Build a new confirm hash.
            $confirm_hash = substr (md5 ($session_hash . time ()), 0, 16);
            $res_user = db_execute ("SELECT * FROM user WHERE user_id = ?",
              [user_getid ()]
            );
            if (db_numrows ($res_user) < 1)
              exit_error (_("Invalid User"), _("That user does not exist."));
            $row_user = db_fetch_array ($res_user);
            $success = db_autoexecute ('user',
              ['confirm_hash' => $confirm_hash], DB_AUTOQUERY_UPDATE,
              "user_id = ?", [user_getid ()]
            );
            if (!$success)
              fb (_("Failed to update the database."), 1);
            else
              {
                fb (_("Database updated."));
                if  (!empty ($sys_https_host))
                  $url = "https://$sys_https_host";
                else
                  $url = "http://$sys_default_domain";
                $url .= "{$sys_home}my/admin/change.php?"
                  . "item=delete&confirm_hash=$confirm_hash";
                # TRANSLATORS: the argument is site name (like Savannah).
                $message = sprintf (
                  _("Someone, presumably you, has requested your %s account "
                    . "deletion.\nIf it wasn't you, it probably means that "
                    . "someone stole your account."),
                  $sys_name
                ) . "\n";
                # TRANSLATORS: the argument is site name (like Savannah).
                $message .= sprintf (
                  _("If you did request your %s account deletion, visit "
                    . "the following URL to finish\nthe deletion process:"),
                  $sys_name
                );
                $message .= "\n\n$url&step=confirm\n\n"
                  . _("If you did not request that change, please visit the "
                      . "following URL to discard\nthe process and report "
                      . "ASAP the problem to us:")
                  . "\n\n$url&step=discard\n\n";
                # TRANSLATORS: the argument is site name (like Savannah).
                $message .= sprintf (_("-- the %s team."), $sys_name) . "\n";
                $success = sendmail_mail (
                  [ 'from' => "$sys_mail_replyto@$sys_mail_domain",
                    'to' => $row_user['email']],
                  [ 'subject' => $sys_name . ' ' . _("Verification"),
                    'body' => $message]
                );
                if ($success)
                  fb (
                    _("Follow the instructions in the email to complete the "
                      . "account deletion.")
                  );
                else
                  fb (
                    _("The system reported a failure when trying to send\nthe "
                      . "confirmation mail. Please retry and report that "
                      . "problem to\nadministrators."),
                    1
                  );
              }
          }
        elseif ($step == "confirm")
          {
            # Cf. form below.
          }
        # Additional step with a direct POST request to avoid CSRF attacks.
        elseif ($step == "confirm2")
          {
            $success = 1;
            $res_user = db_execute (
              "SELECT * FROM user WHERE confirm_hash = ?", [$confirm_hash]
            );
            if (db_numrows ($res_user) > 1)
              {
                $ffeedback =
                  _("This confirmation hash is included in DB more than once.");
                $success = 0;
              }
            if (db_numrows ($res_user) < 1)
              {
                exit_error (_("Invalid confirmation hash."));
                $success = 0;
              }
            if ($success)
              user_delete (0, $confirm_hash);
          }
        elseif ($step == "discard")
          {
            # Just remove stuff added.
            $success = db_autoexecute (
              'user', ['confirm_hash' => null], DB_AUTOQUERY_UPDATE,
              "confirm_hash = ?", [$confirm_hash]
             );
             if ($success)
               fb (_("Account deletion process discarded."));
             else
               fb (
                 _("Failed to discard account deletion process, please "
                   . "contact administrators."),
                 1
               );
          }
        else
          fb (
            _("Unable to understand what to do, parameters are probably "
              . "missing"),
            1
          );
      }
    # Success is set, it means that we can safely go back to the main
    # configuration page.
    if ($success)
      session_redirect (
        "{$sys_home}my/admin/?feedback=" . rawurlencode ($feedback)
      );
  } # if ($update).

# If we reach this point, it means that not sucessful update has been
# already made.

# Texts to be displayed.
$preamble = '';
$input_specific = '';

# Defines some information if not specific.
$form_item_names = ['newvalue'];
$input_titles = [''];
$input_types = ['text'];

# Defines the page depending on the item given.
if ($item == "realname")
  {
    $title = _("Change Real Name");
    $input_titles[0] = _("New Real Name:");
  }
elseif ($item == "timezone")
  {
    require_once ('../../include/timezones.php');
    $title = _("Change Timezone");
    $input_titles[0] =
      _("No matter where you live, you can see all dates and times as if "
        . "it were in\nyour neighborhood.");
    $input_specific = html_build_select_box_from_arrays (
      $TZs, $TZs, 'newvalue', user_get_timezone (), true, 'GMT', false,
      'Any', false, _('Timezone')
    );
  }
elseif ($item == "password")
  {
    $title = _("Change Password");
    $preamble = account_password_help();
    $input_titles = [
      _("Current password:"), _("New password / passphrase:"),
      _("Re-type new password:"),
    ];

    $form_item_names = ["oldvalue", "newvalue", "newvaluecheck"];
    $input_types = ["password", "password", "password"];
  }
elseif ($item == "gpgkey")
  {
    extract (sane_import ('request', ['pass' => ['newvalue']]));
    $res_user = db_execute ("SELECT gpg_key FROM user WHERE user_id = ?",
      [user_getid ()]
    );
    $row_user = db_fetch_array ($res_user);
    $title = _("Change GPG Keys");
    $input_titles = [""];
    $input_specific = $gpg_sample_text;

    if (!$newvalue)
      $newvalue = $row_user['gpg_key'];

    $input_specific .= form_textarea (
      'newvalue', utils_specialchars ($newvalue),
      'title="' . _("New GPG key") . '" cols="70" rows="20" wrap="virtual"'
    );
    $input_specific .= "\n";
    $input_specific .= '<p><input type="submit" name="test_gpg_key" value="'
      . _("Test GPG keys") . '" /> '
      . _("(Testing is recommended before updating.)") . "</p>\n"
      . "\n<hr />\n";
    $input_specific .= $gpg_gnu_maintainers_note;
    if ($test_gpg_key)
      $input_specific .= run_gpg_checks ($newvalue);
  }
elseif ($item == "email")
  {
    # First step.
    if (!$step)
      {
        $title = _("Change Email Address");
        $input_titles = [_('New email address:')];
        $preamble = _("Changing your email address will require confirmation "
          . "from\nyour new email address, so that we can ensure we have "
          . "a good email address on\nfile.")
          . "</p>\n<p>"
          . _("We need to maintain an accurate email address for each user "
              . "due to the\nlevel of access we grant via this account. If "
              . "we need to reach a user for\nissues related to this server, "
              . "it is important that we be able to do so.")
          . "</p>\n<p>"
          . _("Submitting the form below will mail a confirmation URL to "
             . "the new email\naddress; visiting this link will complete "
             . "the email change. The old address\nwill also receive "
             . "an email message, this one with a URL to discard the\n"
             . "request.")
          . "</p>\n";
      }
    elseif ($step == "confirm")
      {
        $title = _("Confirm Email Change");
        $preamble = _('Push &ldquo;Update&rdquo; to confirm your email change');
        $input_titles = [_('Confirmation hash:')];
        $input_specific = "<input type='text' readonly='readonly' "
          . "name='confirm_hash' value='$confirm_hash' />";
        $input_specific .=
          "<input type='hidden' name='step' value='confirm2' />";
      }
    elseif ($step == "discard")
      {
        $title = _("Discard Email Change");
        $input_specific = "<input type='text' readonly='readonly' "
          . "name='confirm_hash' value='$confirm_hash' />";
      }
  }
elseif ($item == "delete")
  {
    # First step.
    if (!$step)
      {
        $title = _("Delete Account");
        $input_titles = [_('Do you really want to delete your user account?')];
        $input_specific =
          form_checkbox (
            "newvalue", 0,
            ['value' => "deletionconfirmed", 'title' => _("Delete Account"),]
          )
          . ' ' . _("Yes, I really do");
        $preamble = _("This process will require email confirmation.");
      }
    elseif ($step == "confirm")
      {
        $title = _("Confirm account deletion");
        $preamble =
          _('Push &ldquo;Update&rdquo; to confirm your account deletion');
        $input_titles = [_('Confirmation hash:')];
        $input_specific = "<input type='text' readonly='readonly' "
          . 'name="confirm_hash" value="' . $confirm_hash . '" />'
          . "<input type='hidden' name='step' value='confirm2' />";
      }
    elseif ($step == 'discard')
      {
        $title = _("Discard account deletion");
        $input_titles = [_('Discard hash:')];
        $input_specific = "<input type='text' readonly='readonly' "
          . "name='confirm_hash' value='$confirm_hash' />"
          . "<input type='hidden' name='step' value='discard' />";
      }
  }

if (empty ($title))
  $title = sprintf (_("Unknown user settings item (%s)"), $item);

site_user_header (['title' => $title, 'context' => 'account']);
if (empty ($input_titles[0]))
  $input_titles[0] = $title;

if ($preamble)
  print "<p>$preamble</p>\n";

print form_header ($_SERVER['PHP_SELF'], false, "post");

$input_spec = [$input_specific];
for ($i = 0; $i < 3; $i++)
  {
    $head = $tail = '';
    if (!isset ($form_item_names[$i]))
      break;
    $n = $form_item_names[$i];
    if (empty ($input_spec[$i]))
      {
        $head = "<label for=\"$n\">";
        $tail = '</label>';
      }
    print "<span class='preinput'>$head{$input_titles[$i]}$tail</span>";
    print "<br />\n&nbsp;&nbsp;&nbsp;";
    if (empty ($input_spec[$i]))
      print "<input name=\"$n\" id=\"$n\" type=\"{$input_types[$i]}\" />";
    else
      print $input_spec[$i];
    print "<br />\n";
  }

print "<input type='hidden' name='item' value=\"$item\" />\n";
print '<p>' . form_submit (_("Update")) . "</p>\n</form>\n";
site_user_footer ([]);
?>
