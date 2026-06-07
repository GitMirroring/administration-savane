<?php
# Handle open sessions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2026 Ineiev
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

function cmp_against_current_session ($row)
{
  global $G_SESSION;
  if ($row['time'] != $G_SESSION['time'])
    return 1;
  list ($h, $ticket) = session_hash_parts ($G_SESSION['hash_enc']);
  list ($h, $rticket) = session_hash_parts ($row['session_hash']);
  return $ticket != $rticket;
}

function print_session ($row, $i)
{
  global $HTML, $php_self;
  if ($i)
    print $HTML->box_nextitem (utils_altrow ($i));
  $dsession_hash = "..." . substr ($row['session_hash'], -8);
  print '<span class="trash">';
  if (cmp_against_current_session ($row))
    print utils_link (
      "$php_self?func=del&amp;dsession_hash=$dsession_hash&amp;"
      . "dip_addr={$row['ip_addr']}&amp;dtime={$row['time']}",
      html_image_trash (['alt' => _("Kill this session")])
    );
  else
    print _("Current session") . ' ';
  print '</span>';

  # TRANSLATORS: The variables are session identifier, time, remote host.
  printf (_('Session %1$s opened on %2$s from %3$s'), $dsession_hash,
    utils_format_date ($row['time']), gethostbyaddr ($row['ip_addr'])
  );
  print "<br />\n&nbsp;";
}

# Delete one session.  Hopefully 8 digits of $dsession_hash are sufficient
# to filter out other sessions made by the same user in the same second.
function delete_single_session ($dsession_hash, $dip_addr, $dtime)
{
  $dsession_hash = "%" . substr ($dsession_hash, 3);
  $res = db_execute ("
    DELETE FROM `session`
    WHERE
      `session_hash` LIKE ? AND `ip_addr` = ? AND `time` = ?
      AND `user_id` = ?
    LIMIT 1", [$dsession_hash, $dip_addr, $dtime, user_getid ()]
  );
  if ($res)
    # TRANSLATORS: this is a report of a successful action.
    fb (_("Old session deleted"));
  else
    fb(_("Failed to delete old session"), 1);
  return 0;
}

function keep_one ()
{
  $res = session_delete_other_sessions ();
  if ($res)
    # TRANSLATORS: this is a report of a successful action.
    fb (_("Old sessions deleted"));
  else
    fb (_("Failed to delete old sessions"), 1);
  return 0;
}

function kill_sessions ($dsession_hash, $dip_addr, $dtime, $dkeep_one)
{
  if ($dsession_hash && $dip_addr && $dtime)
    return delete_single_session ($dsession_hash, $dip_addr, $dtime);
  if ($dkeep_one)
    return keep_one ();
  fb (_("Parameters missing, update canceled"), 1);
}

# Check if the user is logged in.
session_require (['isloggedin' => '1']);

extract (sane_import ('get',
  [
    'strings' => [['func', 'del']],
    'true' => 'dkeep_one',
    'digits' => 'dtime',
    'specialchars' => 'dsession_hash',
    'preg' => [['dip_addr', ',^[[:xdigit:]./:]+$,']],
  ]
));
extract (sane_import ('cookie', ['hash' => 'session_hash']));

if ($func == 'del')
  kill_sessions ($dsession_hash, $dip_addr, $dtime, $dkeep_one);

site_user_header (['title' => _("Manage sessions"), 'context' => 'account']);
$sessions = session_list_sessions (user_getid ());
if (empty ($sessions))
  exit_error (_("No session found."));

print $HTML->box_top (_("Opened Sessions"));
$i = 0;
foreach ($sessions as $row)
  print_session ($row, $i++);

if ($i > 3)
  {
    print $HTML->box_nextitem (utils_altrow ($i));
    print '<span class="trash">';
    print utils_link (
      "$php_self?func=del&amp;dkeep_one=1",
      html_image_trash (['alt' => _("Kill all sessions")])
    );
    print '</span><em>';
    print _("All sessions apart from the current one") . "</em><br />&nbsp;\n";
  }
print $HTML->box_bottom ();
site_user_footer ([]);
?>
