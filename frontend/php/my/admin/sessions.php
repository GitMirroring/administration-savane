<?php
# Handle open sessions.
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
require_once('../../include/init.php');

# Check if the user is logged in.
session_require (['isloggedin' => '1']);

extract (sane_import ('get',
  [
    'strings' => [['func', 'del']],
    'true' => 'dkeep_one',
    'digits' => 'dtime',
    'preg' =>
      [
        ['dip_addr', ',^[[:xdigit:]./:]+$,'],
        ['dsession_hash', '/^[a-f\d]+[.]{3}$/']
      ],
  ]
));
extract (sane_import ('cookie', ['hash' => 'session_hash']));

if ($func == 'del')
  {
    if ($dsession_hash && $dip_addr && $dtime)
      {
        # Delete one session.
        $dsession_hash = substr ($dsession_hash, 0, 6) . "%";
        $res = db_execute ("
          DELETE FROM session
          WHERE
            session_hash like ? AND ip_addr = ?  AND time = ? AND user_id = ?
          LIMIT 1", [$dsession_hash, $dip_addr, $dtime, user_getid ()]
        );
        if ($res)
          # TRANSLATORS: this is a report of a successful action.
          fb (_("Old session deleted"));
        else
          fb(_("Failed to delete old session"), 1);
      }
    elseif ($dkeep_one)
      {
        # Delete all sessions apart from the current one.
        $res = db_execute ("
          DELETE FROM session WHERE session_hash <> ? AND user_id = ?",
          [$session_hash, user_getid ()]
        );
        if ($res)
          # TRANSLATORS: this is a report of a successful action.
          fb (_("Old sessions deleted"));
        else
          fb (_("Failed to delete old sessions"), 1);
      }
    else
      fb (_("Parameters missing, update canceled"), 1);
  }
site_user_header (['title' => _("Manage sessions"), 'context' => 'account']);
$res = db_execute ("
  SELECT session_hash, ip_addr, time FROM session
  WHERE user_id = ? ORDER BY time DESC", [user_getid()]
);
if (db_numrows ($res) < 1)
  exit_error (_("No session found."));

print $HTML->box_top (_("Opened Sessions"));
$i = 0;
while ($row = db_fetch_array ($res))
  {
    $i++;
    if ($i > 1)
      print $HTML->box_nextitem (utils_altrow ($i));

    # We destroy a part of the session hash because in no case we want to
    # provide in clear text that complete information that could be used
    # for forgery (even if it is true that this page access is normally
    # properly restricted).
    $dsession_hash = substr($row['session_hash'], 0, 6) . "...";
    # Do not incitate users to kill their own session.
    print '<span class="trash">';
    if ($session_hash != $row['session_hash'])
      print utils_link (
        "$php_self?func=del&amp;dsession_hash=$dsession_hash&amp;dip_addr="
        . $row['ip_addr'] . '&amp;dtime=' . $row['time'],
        html_image_trash (['alt' => _("Kill this session")])
      );
    else
      print _("Current session").' ';
    print '</span>';

    # TRANSLATORS: The variables are session identifier, time, remote host.
    printf (_('Session %1$s opened on %2$s from %3$s'), $dsession_hash,
      utils_format_date($row['time']), gethostbyaddr($row['ip_addr']));
    print "<br />\n&nbsp;";
  }

# Allow to kill sessions apart the current one,
# if more than 3 sessions were counted (otherwise, it looks overkill).
if ($i > 3)
  {
    $i++;
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
