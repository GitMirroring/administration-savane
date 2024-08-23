<?php
# Generic user settings editor.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry
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

foreach (['init', 'sendmail', 'gpg', 'account', 'timezones'] as $i)
  require_once ("../../include/$i.php");

require (utils_get_content_filename ("gpg-sample"));

session_require (['isloggedin' => '1']);
$submit_buttons = ['update', 'test_gpg_key'];
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
    'true' => $submit_buttons,
    'hash' => ['session_hash', 'confirm_hash'],
  ]
));

form_check ($submit_buttons);

exit_if_missing ('item');
if (!user_getid ())
  exit_error (_("Invalid User"), _("That user does not exist."));
$row_user = user_get_fields (['realname', 'user_pw', 'email', 'email_new']);

function update_realname ()
{
  global $row_user;
  extract (sane_import ('request', ['pass' => 'newvalue']));
  $newvalue = account_sanitize_realname ($newvalue);
  if (!account_realname_valid ($newvalue))
    {
      fb (_("Please supply a new name."), 1);
      return;
    }
  $success = db_autoexecute (
    'user', ['realname' => $newvalue], DB_AUTOQUERY_UPDATE,
    "user_id = ?", [user_getid ()]
  );
  if (!$success)
    {
      fb (_("Failed to update the database."), 1);
      return false;
    }
  fb (_("Display name updated."));
  $row_user['realname'] = $newvalue;
  return true;
}

function update_timezone ()
{
  extract (sane_import ('request', ['digits' => 'newvalue']));
  if ($newvalue == 100)
    $newvalue = "GMT";
  $success = db_autoexecute (
    'user', ['timezone' => $newvalue], DB_AUTOQUERY_UPDATE,
    "user_id = ?", [user_getid ()]
  );
  if (!$success)
    {
      fb (_("Failed to update the database."), 1);
      return false;
    }
  fb (_("Timezone updated."));
  return true;
}

function validate_pw_fields ($oldvalue, $newvalue, $newvaluecheck)
{
  global $row_user;
  $fail = 0;
  if (!account_validpw ($row_user['user_pw'], $oldvalue))
    {
      fb (_("Old password is incorrect."), 1);
      $fail = 1;
    }
  if (!$newvalue)
    {
      fb (_("You must supply a password."), 1);
      $fail = 1;
    }
  if ($newvalue != $newvaluecheck)
    {
      fb (_("New passwords do not match."), 1);
      $fail = 1;
    }
  if (!account_pwvalid ($newvalue))
    $fail = 1;
  return $fail;
}

function update_password ()
{
  extract (sane_import ('request',
    ['pass' => ['oldvalue', 'newvalue', 'newvaluecheck']]
  ));

  if (validate_pw_fields ($oldvalue, $newvalue, $newvaluecheck))
    return false;
  $success = db_autoexecute (
    'user', ['user_pw' => account_encryptpw ($newvalue)],
     DB_AUTOQUERY_UPDATE, "user_id = ?", [user_getid ()]
  );
  if ($success)
    fb (_("Password updated."));
  else
    fb (_("Failed to update the database."), 1);
  return $success;
}

function update_gpgkey ()
{
  extract (sane_import ('request', ['pass' => ['newvalue']]));
  $success = user_set_gpg_key ($newvalue);
  $msg = _("Failed to update the database.");
  if ($success)
    $msg = _("GPG Key updated.");
  fb ($msg, !$success);
  return $success;
}

function extract_email_newvalue ()
{
  $vals = sane_import ('request',
    [
      'preg' =>
        [['newvalue', '/^[a-zA-Z0-9_.+-]+@(([a-zA-Z0-9-])+\.)+[a-zA-Z0-9]+$/']]
    ]
  );
  if (empty ($vals['newvalue']))
    return '';
  return preg_replace ('/\s/', '', $vals['newvalue']);
}

function confirm_hash_url ($confirm_hash, $func = 'email')
{
  global $sys_https_host, $sys_home, $sys_default_domain;
  if (!empty ($sys_https_host))
    $url = "https://$sys_https_host";
  else
    $url = "http://$sys_default_domain";
  return "$url{$sys_home}my/admin/change.php?"
    . "item=$func&confirm_hash=$confirm_hash";
}

function savane_team_signature ()
{
  global $sys_name;
  # TRANSLATORS: the argument is site name (like Savannah).
  return sprintf (_("-- the %s team."), $sys_name) . "\n";
}

function email_step0_message ($url)
{
  global $sys_name;
  $message = sprintf (
    # TRANSLATORS: the argument is site name (like Savannah).
    _("You have requested a change of email address on %s.\n"
      . "Please visit the following URL to complete the "
      . "email change:"),
    $sys_name);
  $message .= "\n\n$url&step=confirm\n\n";
  # TRANSLATORS: the argument is site name (like Savannah).
  return $message . savane_team_signature ();
}

function email_step0_warning ($url, $newvalue)
{
  global $row_user, $sys_name;
  # TRANSLATORS: the argument is site name (like Savannah).
  $msg = sprintf (
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
  return $msg . savane_team_signature ();
}

function generate_confirm_hash ($new_email = null)
{
  global $session_hash;
  # Build a new confirm hash.
  $confirm_hash = substr (md5 ($session_hash . time ()), 0, 16);
  $params = ['confirm_hash' => $confirm_hash];
  if ($new_email !== null)
    $params['email_new'] = $new_email;
  $success = db_autoexecute ('user', $params,
    DB_AUTOQUERY_UPDATE, "user_id = ?", [user_getid ()]
  );
  if (!$success)
    {
      fb (_("Failed to update the database."), 1);
      return null;
    }
  fb (_("Database updated."));
  return $confirm_hash;
}

function email_step0_notify ($newvalue, $confirm_hash)
{
  global $sys_name, $row_user;
  $url = confirm_hash_url ($confirm_hash);
  $message = email_step0_message ($url);
  $warning = email_step0_warning ($url, $newvalue);
  $subject = $sys_name . ' ' . _("Verification");

  $success = sendmail_mail (
    ['to' => $newvalue], ['subject' => $subject, 'body' => $message]
  );
  # yeupou--gnu.org 2003-11-09:
  # Send also a warning to the current mail address just in case:
  # someone can find a session open on a computer, change the mail address,
  # then use the lost password process---so change the password without knowing
  # and without having the user noticing that something bad is going on.
  sendmail_mail (
    ['to' => $row_user['email']], ['subject' => $subject, 'body' => $warning]
  );
  return !$success;
}

function report_step0_result ($fail, $newval)
{
  if ($fail)
    {
      fb (
        _("The system reported a failure when trying to "
          . "send\nthe confirmation mail. Please retry and "
          . "report that problem to\nadministrators."),
        1
      );
      return;
    }
  # TRANSLATORS: the argument is email address.
  $msg = sprintf (
    _("Confirmation mailed to %s."), $newval
  );
  fb ($msg . ' '
    . _("Follow the instructions in the email to "
        . "complete the email change.")
  );
}

function update_email_step0 ()
{
  $newval = extract_email_newvalue ();
  if (!account_emailvalid ($newval))
    return;
  $confirm_hash = generate_confirm_hash ($newval);
  if ($confirm_hash === null)
    return false;
  $fail = email_step0_notify ($newval, $confirm_hash);
  report_step0_result ($fail, $newval);
  return !$fail;
}

function validate_confirm_hash ($confirm_hash)
{
  if (!preg_match ("/^[a-f0-9]{16}$/", $confirm_hash))
    exit_error (_("Invalid confirmation hash."));
  $res_user = db_execute (
    "SELECT user_id FROM user WHERE confirm_hash = ?", [$confirm_hash]
  );
  if (db_numrows ($res_user) > 1)
    exit_error (_("This confirmation hash is included in DB more than once."));
  if (!db_numrows ($res_user))
    exit_error (_("Invalid confirmation hash."));
  $uid = db_result ($res_user, 0, 'user_id');
  if ($uid !== user_getid ())
    exit_error (_("Invalid User"));
}

function update_email_confirm2 ()
{
  global $confirm_hash, $row_user;
  validate_confirm_hash ($confirm_hash);
  $success = db_autoexecute (
   'user',
    [ 'email' => $row_user['email_new'],
      'confirm_hash' => null, 'email_new' => null ],
    DB_AUTOQUERY_UPDATE, "user_id = ? AND confirm_hash = ?",
    [user_getid (), $confirm_hash]
  );

  if ($success)
    fb (_("Email address updated."));
  else
    fb (_("Failed to update the database."), 1);
  return $success;
}

function discard_email ()
{
  $success = db_autoexecute ('user',
    ['confirm_hash' => null, 'email_new' => null], DB_AUTOQUERY_UPDATE,
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
  return $success;
}

function update_confirm ()
{
  # Cf. form at the end.
  return false;
}

function run_steps ($funcs)
{
  global $step;
  $st = $step? $step: 0;
  if (array_key_exists ($st, $funcs))
    return $funcs[$st] ();
  return false;
}

function update_email ()
{
  return run_steps ([
    0 => 'update_email_step0', 'confirm' => 'update_confirm',
    'confirm2' => 'update_email_confirm2', 'discard' => 'discard_email'
  ]);
}

# Check if the user is a member of any _active_ group.
function exit_if_member_of_any_group ()
{
  $result = db_execute (
    "SELECT g.group_id
     FROM user_group u JOIN groups g ON u.group_id = g.group_id
     WHERE user_id = ? AND g.status = 'A'",
    [user_getid ()]
  );
  if (!db_numrows ($result))
    return;
  exit_error (
    _("You must quit all groups before requesting account deletion. "
      . "If you registered a group that is still pending,\n"
      . "please ask site admins to cancel that registration."));
}

function deletion_message ($url)
{
  global $sys_name;
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
  return $message . savane_team_signature ();
}

function delete_step0_notify ($confirm_hash)
{
  global $sys_name, $row_user;
  $url = confirm_hash_url ($confirm_hash, 'delete');
  $message = deletion_message ($url);
  return sendmail_mail (
    ['to' => $row_user['email']],
    [ 'subject' => $sys_name . ' ' . _("Verification"), 'body' => $message]
  );
}

function report_delete_step0_result ($success)
{
  $msg =
    _("The system reported a failure when trying to send\nthe confirmation "
      . "mail. Please retry and report that " . "problem to\nadministrators.");
  if ($success)
    $msg =
      _("Follow the instructions in the email to complete the "
        . "account deletion.");
  fb ($msg, !$success);
}

function delete_account_step0 ()
{
  extract (sane_import ('request',
    ['strings' => [['newvalue', ['deletionconfirmed']]]]
  ));
  if ($newvalue != 'deletionconfirmed')
    return;
  $confirm_hash = generate_confirm_hash ();
  if ($confirm_hash === null)
    return false;
  $success = delete_step0_notify ($confirm_hash);
  report_delete_step0_result ($success);
  return $success;
}

function delete_confirm2 ()
{
  global $confirm_hash;
  validate_confirm_hash ($confirm_hash);
  user_delete (0, $confirm_hash);
  return true;
}

function delete_discard ()
{
  $success = db_autoexecute (
    'user', ['confirm_hash' => null], DB_AUTOQUERY_UPDATE,
    "confirm_hash = ?", [$confirm_hash]
  );
  $msg = _("Failed to discard account deletion process, please "
           . "contact administrators.");
  if ($success)
    $msg = _("Account deletion process discarded.");
  fb ($msg, !$success);
  return $success;
}

function delete_account ()
{
  return run_steps ([0 => 'delete_account_step0', 'confirm' => 'update_confirm',
    'confirm2' => 'delete_confirm2', 'discard' => 'delete_discard'
  ]);
}

if ($item == 'delete')
  exit_if_member_of_any_group ();

# Update the database.
if ($update)
  {
    $funcs = [
      'realname' => 'update_realname', 'timezone' => 'update_timezone',
      'password' => 'update_password', 'gpgkey' => 'update_gpgkey',
      'email' => 'update_email', 'delete' => 'delete_account'
    ];
    $success = false;
    if (array_key_exists ($item, $funcs))
      $success = $funcs[$item] ();
    if ($success)
      session_redirect (
        "{$sys_home}my/admin/?feedback=" . rawurlencode ($feedback)
      );
  } # if ($update).

# If we reach this point, it means that not successful update has been
# already made.

# Texts to be displayed.
$preamble = '';
$input_specific = '';

# Defines some information if not specific.
$form_item_names = ['newvalue'];
$input_titles = [''];
$input_types = ['text'];

# Define the page depending on the item given.
if ($item == "realname")
  {
    $title = _("Change Display Name");
    $input_titles[0] = _("New display name:");
    $input_specific = form_input ('text', 'newvalue', $row_user['realname']);
  }
elseif ($item == "timezone")
  {
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
    $preamble = account_password_help ();
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
    $old_key = user_get_gpg_key ();
    $title = _("Change GPG Keys");
    $input_titles = [""];
    $input_specific = $gpg_sample_text;

    if (!$newvalue)
      $newvalue = $old_key;

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
      $input_specific .= gpg_run_checks ($newvalue);
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
        $input_specific .= form_hidden (['step' => 'confirm2']);
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
            ['value' => "deletionconfirmed", 'title' => _("Delete account"),]
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
          . form_hidden (['step' => 'confirm2']);
      }
    elseif ($step == 'discard')
      {
        $title = _("Discard account deletion");
        $input_titles = [_('Discard hash:')];
        $input_specific = "<input type='text' readonly='readonly' "
          . "name='confirm_hash' value='$confirm_hash' />"
          . form_hidden (['step' => 'discard']);
      }
  }

if (empty ($title))
  $title = sprintf (_("Unknown user settings item (%s)"), $item);

site_user_header (['title' => $title, 'context' => 'account']);
if (empty ($input_titles[0]))
  $input_titles[0] = $title;

if ($preamble)
  print "<p>$preamble</p>\n";

print form_header ();

$input_spec = [$input_specific];
for ($i = 0; $i < 3; $i++)
  {
    $head = $tail = '';
    if (!isset ($form_item_names[$i]))
      break;
    $input_title = $input_titles[$i];
    $n = $form_item_names[$i];
    if (empty ($input_spec[$i]))
      $input_title = html_label ($n, $input_title);
    print "<br />\n<span class='preinput'>$input_title</span>&nbsp;&nbsp;";
    if (empty ($input_spec[$i]))
      print "<input name=\"$n\" id=\"$n\" type=\"{$input_types[$i]}\" />";
    else
      print $input_spec[$i];
  }

print form_hidden (['item' => $item]);
print '<p>' . form_submit (_("Update")) . "</p>\n</form>\n";
site_user_footer ([]);
?>
