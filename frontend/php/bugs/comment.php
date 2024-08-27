<?php
# Add a comment authenticated via GnuPG signature.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

$includes = ['init', 'gpg', 'member', 'trackers/general', 'parsemail'];

# The messages on this page aren't localized because it's intended
# for scripted usage.

foreach ($includes as $i)
  require_once ("../include/$i.php");

function display_input_fields ()
{
  global $user_id, $mbox;
  if (empty ($user_id))
    $user_id = '';
  print form_header () . "<p>"
    . html_label ('user', no_i18n ('User:') . '&nbsp;')
    . form_input ('text', 'user', $user_id, 'size=17') . "</p>\n<p>"
    . html_label ('mbox', no_i18n ('Email (mbox):')) . "<br />\n"
    . form_textarea ('mbox', $mbox, ' rows="68" cols="80" wrap="soft"')
    . "</p>\n" . form_footer ();
}

function display_page ($msg = null)
{
  global $HTML;
  if ($msg !== null)
    fb ($msg, 1);
  html_header (['notopmenu' => 1]);
  display_input_fields ();
  $HTML->footer ([]);
  exit;
}

function extract_message ($mbox, $user_id)
{
  list ($input, $msg, $nested) =
    parsemail_extract_message ($mbox, 'display_page');
  list ($error_code, $error_msg, $decrypted)
    = gpg\verify_for ($user_id, $input);
  if ($error_code)
    display_page ($error_msg);
  if (count ($input) < 2)
    # Non-detached signature: the message needs 'decrypting'.
    $msg = $decrypted;
  elseif (!empty ($nested))
    $msg = parsemail_extract_body ($input[1], 'display_page');
  return $msg;
}

function trim_message ($msg)
{
  return preg_replace (
    "/\n[^\n]*_____________________*\s*\n"
    . "([^\n]*\n)?[^\n]*Reply to this item at:.*$/s",
    '', $msg
  );
}

function assign_params (&$ret, $line)
{
  foreach (explode (';', $line) as $k_v)
    {
      $arr = preg_split ("/=/", $k_v, 2);
      if (count ($arr) == 2)
        $ret[trim ($arr[0])] = trim ($arr[1]);
    }
}

# The message should include a few lines containing strings like
# "{savane: user = alice; tracker = task; item = 83521}"; such lines
# define the parameters of the request; anything starting with the first
# such line is excluded from the comment.
function parse_message ($msg)
{
  $ret = [];
  while (
    preg_match (
      "/\n[^\n]*{savane: ([^}]*)}[^\n]*/s", $msg, $m, PREG_OFFSET_CAPTURE
    )
  )
    {
      if (!array_key_exists ('comment', $ret))
        $ret['comment'] = trim_message (substr ($msg, 0, $m[0][1]));
      $msg = substr ($msg, $m[0][1] + strlen ($m[0][0]));
      assign_params ($ret,  $m[1][0]);
    }
  if (
    !empty ($ret['user']) && ctype_digit ($ret['user'])
    && user_exists ($ret['user'])
  )
    $ret['user'] = user_getname ($ret['user']);
  return $ret;
}

function show_sanitized ($missing, $purged)
{
  $msg = '';
  if (!empty ($missing))
    $msg = sprintf (no_i18n ('Missing params: %s'), join (', ', $missing))
      . "\n";
  $lst = [];
  foreach ($purged as $k => $v)
    $lst[] = "$k = $v";
  if (!empty ($purged))
    $msg .= sprintf (no_i18n ('Purged params: %s'), join ('; ', $lst));
  if (empty ($msg))
    return;
  display_page ($msg);
}

function check_for_missing_params ($params, $params_in)
{
  $missing = $purged = [];
  foreach (['user', 'tracker', 'item', 'comment'] as $k)
    if (empty ($params[$k]))
      {
        if (empty ($params_in [$k]))
          $missing[] = $k;
        else
          $purged[$k] = $params_in[$k];
      }
  show_sanitized ($missing, $purged);
}

function validate_params ($params_in)
{
  global $user;
  $names = [
    'name' => 'user', 'digits' => 'item', 'artifact' => 'tracker',
    'pass' => 'comment'
  ];
  $params = sane_import ($params_in, $names);
  check_for_missing_params ($params, $params_in);
  foreach (['user' => $user, 'tracker' => ARTIFACT] as $k => $v)
    if ($v != $params[$k])
      display_page (
        sprintf (
          no_i18n ('*%s* parameter mismatch: %s vs %s'), $k, $v, $params[$k]
        )
      );
}

function check_permissions ($user_id, $item_id)
{
  session_setglobals ($user_id);
  list ($may_comment, $fields) =
    trackers_may_user_comment ($user_id, $item_id);

  if (empty ($fields))
    display_page (sprintf (no_i18n ("Item #%s not found."), $item_id));

  if (!$may_comment)
    display_page (no_i18n ('Permission denied.'));
  return $fields['group_id'];
}

# Make sure the same message isn't posted more than once in the same item.
# On the one hand, people may want to post identical messages, on the other
# hand, it would be quite easy to re-post intercepted messages (which is not
# what we want), on the third hand, for legitimate users, it's elementary
# to slightly modify messages to make them unique.
function check_for_duplicates ($item_id, $user_id, $comment)
{
  $res = db_execute ("
    SELECT bug_history_id FROM " . ARTIFACT . "_history
    WHERE
      bug_id = ? AND mod_by = ? AND field_name = 'details' AND old_value = ?",
    [$item_id, $user_id, $comment]
  );
  if (!db_numrows ($res))
    return;
  display_page (no_i18n ("Duplicate message has been rejected."));
}

function add_follow_up ($params, $user_id, $group_id)
{
  trackers_init ($group_id);
  $vfl = $changes = [];
  $item_id = $params['item'];
  trackers_data\handle_update\update_details (
    $params['comment'], $item_id, $group_id, $vfl, $changes
  );
  $addr = '';
  trackers_append_followup_notif_addresses ($addr, $item_id);
  trackers_mail_followup ($item_id, $addr, $changes);
}

function import_user_id ()
{
  $d = sane_import ('post', ['digits' => 'user']);
  if (isset ($d['user']) && !user_exists ($d['user']))
    display_page (sprintf (no_i18n ("User #%s not found."), $d['user']));
  return [user_getname ($d['user']), $d['user']];
}

function check_user_name ($name)
{
  $user_id = user_getid ($name);
  if (empty ($user_id))
    display_page (sprintf (no_i18n ("User *%s* not found."), $name));
  return [$name, $user_id];
}

function import_post_data ()
{
  global $mbox, $user, $user_id;
  $d = sane_import ('post', ['pass' => 'mbox', 'name' => 'user']);
  $mbox = $d['mbox'];
  if (empty ($d['user']))
    list ($user, $user_id) = import_user_id ();
  else
    list ($user, $user_id) = check_user_name ($d['user']);

  if (empty ($user_id) || empty ($mbox))
    display_page ();
}

import_post_data ();
$params = parse_message (extract_message ($mbox, $user_id));
validate_params ($params);
$group_id = check_permissions ($user_id, $params['item']);

check_for_duplicates ($params['item'], $user_id, $params['comment']);
add_follow_up ($params, $user_id, $group_id);
display_page ();
?>
