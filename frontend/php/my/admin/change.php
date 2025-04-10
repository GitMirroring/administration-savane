<?php
# Generic user settings editor.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry
# Copyright (C) 2013, 2014, 2017-2025 Ineiev
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

if (empty ($step))
  $step = 0;

exit_if_missing ('item');
if (!user_getid ())
  exit_error (_("Invalid User"), _("That user does not exist."));
$row_user = user_get_fields ([
  'user_name', 'realname', 'user_pw', 'email', 'email_new', 'confirm_hash'
]);

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
  $success = account_set_pw (user_getid (), $newvalue);
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
  return $message . utils_team_signature ();
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
  return $msg . utils_team_signature ();
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
  # TRANSLATORS: the argument is email address.
  $msg = sprintf (_("Confirmation mailed to %s."), $newval) . ' '
    . _("Follow the instructions in the email to complete the email change.");
  if ($fail)
    $msg =
      _("The system reported a failure when trying to send\nthe confirmation "
        . "mail. Please retry and report that problem to\nadministrators.");
  fb ($msg, $fail);
}

function email_is_unchanged ($val)
{
  if ($val !== user_get_email (user_getid ()))
    return 0;
  fb (sprintf (_('*%s* is already your registered email.'), $val), 1);
  return 1;
}

function update_email_step0 ()
{
  global $item;
  $val = '';
  if (array_key_exists ('newvalue', $_REQUEST))
    $val = $_REQUEST['newvalue'];
  if (email_is_unchanged ($val))
    return;
  if (!account_emailvalid ($val))
    return;
  $confirm_hash = account_generate_confirm_hash ($item, ['email_new' => $val]);
  if ($confirm_hash === null)
    return false;
  $fail = email_step0_notify ($val, $confirm_hash);
  report_step0_result ($fail, $val);
  return !$fail;
}

function update_email_confirm2 ()
{
  global $confirm_hash, $row_user, $item;
  account_validate_confirm_hash ($confirm_hash, $item);
  $success = db_autoexecute (
   'user',
    [ 'email' => $row_user['email_new'],
      'confirm_hash' => null, 'email_new' => null ],
    DB_AUTOQUERY_UPDATE, "user_id = ?", [user_getid ()]
  );

  if ($success)
    fb (_("Email address updated."));
  else
    fb (_("Failed to update the database."), 1);
  return $success;
}

function discard_email ()
{
  return discard_hash ([_("Address change process discarded."),
    _("Failed to discard the address change process, please "
      . "contact\nadministrators.")
  ]);
}

function run_steps ($funcs)
{
  global $step;
  if (array_key_exists ($step, $funcs))
    return $funcs[$step] ();
  return false;
}

function update_email ()
{
  return run_steps ([
    0 => 'update_email_step0',
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
  global $sys_name, $row_user;
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
  return $message . utils_team_signature ();
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
      . "mail. Please retry and report that problem to\nadministrators.");
  if ($success)
    $msg =
      _("Follow the instructions in the email to complete the "
        . "account deletion.");
  fb ($msg, !$success);
}

function delete_account_step0 ()
{
  global $item;
  extract (sane_import ('request',
    ['strings' => [['newvalue', ['deletionconfirmed']]]]
  ));
  if ($newvalue != 'deletionconfirmed')
    return;
  $confirm_hash = account_generate_confirm_hash ($item);
  if ($confirm_hash === null)
    return false;
  $success = delete_step0_notify ($confirm_hash);
  report_delete_step0_result ($success);
  return $success;
}

function delete_confirm2 ()
{
  global $confirm_hash, $item;
  account_validate_confirm_hash ($confirm_hash, $item);
  user_delete ();
  return true;
}

function discard_hash ($str)
{
  global $row_user, $confirm_hash, $item;
  account_validate_confirm_hash ($confirm_hash, $item);
  $success = account_clear_confirm_hash ($row_user['user_name']);
  fb ($str[$success? 0: 1], !$success);
  return $success;
}

function delete_discard ()
{
  return discard_hash ([("Account deletion process discarded."),
    _("Failed to discard account deletion process, please "
      . "contact administrators.")
  ]);
}

function delete_account ()
{
  return run_steps ([0 => 'delete_account_step0',
    'confirm2' => 'delete_confirm2', 'discard' => 'delete_discard'
  ]);
}

function page_option ($option_set, $def_val = false)
{
  global $step, $item;
  if (!array_key_exists ($item, $option_set))
    return $def_val;
  $ret = $option_set[$item];
  if (is_string ($ret))
    return $ret;
  if (!array_key_exists ($step, $ret))
    return $def_val;
  return $ret[$step];
}

function preinp_label ($str, $for = 'newvalue')
{
  return "<span class='preinput'>" . html_label ($for, $str)
    . "</span>&nbsp;&nbsp;";
}

function default_input ($title, $value = '')
{
  print preinp_label ($title) . "<br />\n"
    . form_input ('text', 'newvalue', $value);
}

function input_realname ()
{
  global $row_user;
  default_input (_("New display name:"), $row_user['realname']);
}

function input_timezone ()
{
  global $TZs;
  print preinp_label (
    _("No matter where you live, you can see all dates and times as if "
      . "it were in\nyour neighborhood.")
  );
  $input_specific = html_build_select_box_from_arrays (
    $TZs, $TZs, 'newvalue', user_get_timezone (), true, 'GMT', false,
    'Any', false, _('Timezone')
  );
}

function input_password ()
{
  $titles = ["oldvalue" => _("Current passphrase:"),
    "newvalue" => _("New passphrase:"),
    "newvaluecheck" => _("Re-type new passphrase:")
  ];
  $ret = [];
  foreach ($titles as $k => $v)
    $ret[] .= '<p>' . preinp_label ($v, $k) . "<br />\n"
      . form_input ('password', $k) . "</p>\n";
  print join ('', $ret);
}

function input_gpgkey ()
{
  global $gpg_sample_text, $gpg_gnu_maintainers_note, $test_gpg_key;
  $old_key = user_get_gpg_key ();
  extract (sane_import ('request', ['pass' => ['newvalue']]));
  if (!$newvalue)
    $newvalue = $old_key;
  print $gpg_sample_text;
  print preinp_label (_("New GPG key")) . "<br />\n"
    . form_textarea ('newvalue', utils_specialchars ($newvalue),
        'cols="70" rows="20" wrap="virtual"'
      );
  print "\n";
  print '<p>' . form_submit (_("Test GPG keys"), 'test_gpg_key')
    . ' ' . _("(Testing is recommended before updating.)") . "</p>\n"
    . "\n<hr />\n";
  print $gpg_gnu_maintainers_note;
  if ($test_gpg_key)
    print gpg_run_checks ($newvalue);
}

function email_input_0 ()
{
  default_input (_('New email address:'));
}

function email_input_confirm ()
{
  global $confirm_hash;
  print preinp_label (_('Confirmation hash:'), 'confirm_hash')
    . form_input ('text', 'confirm_hash', $confirm_hash, "readonly='readonly'")
    . form_hidden (['step' => 'confirm2']);
}

function input_discard ()
{
  global $confirm_hash;
  print preinp_label (_('Discard hash:'), $confirm_hash)
    . form_input ('text', 'confirm_hash', $confirm_hash, "readonly='readonly'")
    . form_hidden (['step' => 'discard']);
}

function delete_input_0 ()
{
  print preinp_label (_('Do you really want to delete your user account?'));
  print form_checkbox (
      "newvalue", 0,
      ['value' => "deletionconfirmed", 'title' => _("Delete account"),]
    )
    . ' ' . _("Yes, I really do");
}

$titles = [
  'realname' => _("Change Display Name"), 'timezone' => _("Change Timezone"),
  'password' => _("Change Password"), 'gpgkey' => _("Change GPG Keys"),
  'email' => [
    0 => _("Change Email Address"), 'confirm' => _("Confirm Email Change"),
    'discard' => _("Discard Email Change")
  ],
  'delete' => [
    0 => _("Delete Account"), 'confirm' => _("Confirm account deletion"),
    'discard' => _("Discard account deletion"),
  ]
];

$preambles = [
  'password' => account_password_help (),
  'email' => [
    0 =>
      _("Changing your email address will require confirmation from\n"
        . "your new email address, so that we can ensure we have "
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
         . "request."),
    'confirm' => _('Push &ldquo;Update&rdquo; to confirm your email change')
  ],
  'delete' => [
    0 => _("This process will require email confirmation."),
    'confirm' =>
      _('Push &ldquo;Update&rdquo; to confirm your account deletion'),
  ]
];

$update_func = [
  'realname' => 'update_realname', 'timezone' => 'update_timezone',
  'password' => 'update_password', 'gpgkey' => 'update_gpgkey',
  'email' => 'update_email', 'delete' => 'delete_account'
];

$input_func = [
  'realname' => 'input_realname', 'timezone' => 'input_timezone',
  'password' => 'input_password', 'gpgkey' => 'input_gpgkey',
  'email' => [
    0 => 'email_input_0', 'confirm' => 'email_input_confirm',
    'discard' => 'input_discard'
  ],
  'delete' => [0 => 'delete_input_0', 'confirm' => 'email_input_confirm',
    'discard' => 'input_discard'
  ]
];

if ($item == 'delete')
  exit_if_member_of_any_group ();

if (in_array ($step, ['confirm', 'discard']))
  {
    # At this step, a GET request is used because the URL comes
    # from the confrimation email.  That means no form_id-based CSRF
    # mitigation; in order to use it, we form a POST request as
    # the 'confirm2' step.  The hash is validated just to reveal
    # the errors at an earlier stage.
    account_validate_confirm_hash ($confirm_hash, $item);
  }

if ($update)
  {
    $f = page_option ($update_func);
    if ($f !== false)
      if ($f ())
        session_redirect (
          "{$sys_home}my/admin/?feedback=" . rawurlencode ($feedback)
        );
  } # if ($update).

site_user_header (['title' => page_option ($titles), 'context' => 'account']);
print '<p>' . page_option ($preambles, '') . "</p>\n";
print form_header ();

$f = page_option ($input_func);
if ($f !== false)
  $f ();

print form_hidden (['item' => $item]);
print '<p>' . form_submit (_("Update")) . "</p>\n</form>\n";
site_user_footer ([]);
?>
