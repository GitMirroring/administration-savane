<?php
# Talk to mailman wrapper and update the database.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
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

namespace {
require_once ("init.php");
require_once ("sendmail.php");
}

namespace mm_ns {
if (function_exists ('hrtime'))
  {
    function timestamp ()
    {
      return hrtime (true) / 1000000;
    }
  }
else
  {
    function timestamp ()
    {
      return microtime (true) / 1000;
    }
  }

function acquire_lock ()
{
  $tok = ftok (__FILE__, 'a');
  if ($tok === -1)
    return null;
  $sem = sem_get ($tok);
  if ($sem === false)
    return null;
  sem_acquire ($sem);
  return $sem;
}

function send_request ($cmd, $args)
{
  $in = "command=$cmd\n";
  foreach ($args as $k => $v)
    $in .= "$k=$v\n";

  $ret = utils_run_proc (
    $GLOBALS['sys_mailman_wrapper'], $output, $error, ['in' => $in]
  );
  if ($ret === 'fail')
    $error = "Error: $error\n";
  $output = explode ("\n", $output);
  return [$output, $error];
}

function parse_response ($lines)
{
  $error = [];
  foreach ($lines as $l)
    {
      if ($l === '')
        continue;
      if (preg_match ("/^Error/", $l))
        {
          $error[] = $l;
          continue;
        }
      if (false === strpos ($l, '='))
        {
          $error[] = "no assignment in $l";
          continue;
        }
      $pos = strpos ($l, '=');
      $ret[substr ($l, 0, $pos)] = substr ($l, $pos + 1);
    }
  $ret['error'] = $error;
  return $ret;
}

function run ($cmd, $args)
{
  $t0 = timestamp ();
  $lock = acquire_lock ();
  if ($lock === null)
    return ['error' => "Error: can't acquire semaphore",
        'timestamp' => timestamp () - $t0
      ];
  list ($lines, $error) = send_request ($cmd, $args);
  sem_release ($lock);
  $t0 = timestamp () - $t0;
  $ret = parse_response ($lines);
  if (empty ($ret['error']))
    unset ($ret['error']);
  else
    $ret['error'] = join ("\n", $ret['error']);
  if (!empty ($error))
    $ret['pipe::error'] = $error;
  $ret['timestamp'] = $t0;
  return $ret;
}

function report_errors ($res)
{
  $acc = '';
  foreach (['error', 'pipe::error'] as $k)
    if (!empty ($res[$k]))
      $acc .= " " . $res[$k];
  if (empty ($acc))
    return false;
  fb ($acc, true);
  return true;
}

function action_string ($action, $list_name, $domain)
{
  $action_strings = [
    'change_pw' => _("You requested password reset of the list %1\$s at %2\$s."),
    'newlist' => _("You requested creation of the list %1\$s at %2\$s."),
  ];
  return sprintf ($action_strings[$action], $list_name, $domain);
}

function format_msg ($group_id, $list_name, $res)
{
  $grp = group_get_object ($group_id);
  $domain = $grp->getTypeVirtualHost ();
  $msg = _("Hello,") . "\n\n";
  $msg .= action_string ($res['command'], $list_name, $domain);
  $msg .= "\n\n";
  $msg .= sprintf (
    _("The new list administrator password of the mailing list %s is:"),
    $list_name
  );
  $msg .= "\n          {$res['password']}\n\n";
  $msg .=
    _("You are advised to change the password, and to avoid using a password\n"
      . "you use for important accounts, as mailman does not really provide\n"
      . "security for these list passwords.");
  return $msg . sendmail_signature ();
}

function send_ack ($group_id, $list_name, $res)
{
  global $sys_mail_replyto, $sys_mail_domain;
  $uid = user_getid ();
  $msg = format_msg ($group_id, $list_name, $res);
  sendmail_encrypt_message ($uid, $msg);
  sendmail_mail (
    [ 'from' => "$sys_mail_replyto@$sys_mail_domain", 'to' => $uid],
    # TRANSLATORS: this is a subject line of a message
    ['subject' => sprintf (_("Mailman list %s"), $list_name), 'body' => $msg],
    ['skip_format_body' => true]
  );
}

function report_results ($res, $group_id, $list_name)
{
  if (report_errors ($res))
    return true;
  send_ack ($group_id, $list_name, $res);
  return false;
}

function convert_description ($description)
{
  $ret = utils_specialchars_decode ($description, ENT_QUOTES);
  $ret = preg_replace ('/\s/', ' ', $ret);
  return $ret;
}

function create_list ($group_id, $list_name, $public, $description)
{
  $grp = group_get_object ($group_id);
  $domain = $grp->getTypeVirtualHost ();
  $email = user_getemail ();
  $args = [ 'list_full_name' => "$list_name@$domain", 'admin_mail' => $email,
    'description' => convert_description ($description),
    'visibility' => $public? 'public': 'private', 'password' => '' ];
  $res = run ('newlist', $args);
  return report_results ($res, $group_id, $list_name);
}

function report_db_error ($res, $list_name, $action)
{
  $action_list = [
    # TRANSLATORS: the argument is mailing list name.
    'add' => [_("List %s added"), _("Error adding list %s")],
    'delete' => [_("List %s deleted"), _("Error deleting list %s")],
    'update' => [_("List %s updated"), _("Error updating list %s")]
    # No i18n: this action is for site admins only.
    'unlink' => [("List %s unlinked"), ("Error unlinking list %s")],
  ];
  $err = $res? 0: 1;
  $msg = sprintf ($action_list[$action][$err], $list_name);
  fb ($msg, $err);
}

function unlink_list ($group_list_id, $list_name, $action)
{
  $res = db_execute ("DELETE FROM mail_group_list WHERE group_list_id = ?",
    [$group_list_id]
  );
  report_db_error ($res, $list_name, $action);
}

function delete_list ($group_list_id, $group_id, $list_name)
{
  $res = run ('rmlist', ['list_name' => $list_name]);
  if (report_errors ($res))
    return;
  unlink_list ($group_list_id, $list_name, 'delete');
}

} # namespace mm_ns

namespace {
function mailman_get_version ()
{
  return mm_ns\run ('version', []);
}

function mailman_delete_list ($group_list_id, $group_id, $list_name)
{
  mm_ns\delete_list ($group_list_id, $group_id, $list_name);
}

function mailman_reset_password ($group_id, $name)
{
  $res = mm_ns\run ('change_pw', ['list_name' => $name, 'password' => '']);
  mm_ns\report_results ($res, $group_id, $name);
}

function mailman_make_list ($group_id, $list_name, $public, $description)
{
  if (mm_ns\create_list ($group_id, $list_name, $public, $description))
    return;
  $result = db_autoexecute ('mail_group_list',
    [ 'group_id' => $group_id, 'list_name' => $list_name, 'is_public' => $public,
      'list_admin' => user_getid (), 'description' => $description],
    DB_AUTOQUERY_INSERT
  );
  mm_ns\report_db_error ($result, $list_name, 'add');
}

function mailman_config_list ($group_list_id, $group_id, $list_name, $public,
  $desc
)
{
  $grp = group_get_object ($group_id);
  $domain = $grp->getTypeVirtualHost ();
  $args = ['list_full_name' => "$list_name@$domain"];
  if ($public !== null)
    $args['visibility'] = $public? 'public': 'private';
  if ($desc !== null)
    $args['description'] = mm_ns\convert_description ($desc);
  if (mm_ns\report_errors (mm_ns\run ('config', $args)))
    return;
  $res = db_autoexecute ('mail_group_list',
    ['description' => $desc, 'is_public' => $public], DB_AUTOQUERY_UPDATE,
    "group_list_id = ?", [$group_list_id]
  );
  mm_ns\report_db_error ($res, $list_name, 'update');
}

# Find the specified list in the database.
function mailman_find_list ($group_list_id, $group_id, $list_name)
{
  # Be sure to match both group_list_id and group_id so that people
  # avoid configuring lists of other groups.
  $res = db_execute (
    "SELECT * FROM mail_group_list WHERE group_list_id = ? AND group_id = ?",
    [$group_list_id, $group_id]
  );
  if (!db_numrows ($res))
    {
      fb (sprintf (_("List %s not found in the database"), $list_name), 1);
      return null;
    }
  return db_fetch_array ($res);
}
function mailman_unlink_list ($group_list_id, $list_name)
{
  mm_ns\unlink_list ($group_list_id, $list_name, 'unlink');
}
} # namespace {
?>
