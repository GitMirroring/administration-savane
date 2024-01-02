<?php
# Send site admins email about approved group.
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

# We don't internationalize messages in this file because they are
# for Savannah admins who use English. (no_i18n() is defined elsewhere).

require_once (dirname(__FILE__) . '/sendmail.php');

function send_new_project_email ($group_id)
{
  $res_grp = db_execute (
    "SELECT * FROM groups WHERE group_id = ?", [$group_id]
  );

  if (db_numrows ($res_grp) < 1)
    # TRANSLATORS: the argument is group id (number).
    exit_error (sprintf (
      no_i18n ("Group [ %s ] does not exist. Shame on you, sysadmin."),
      $group_id)
    );
  $row_grp = db_fetch_array($res_grp);
  $res_admins = db_execute ("
    SELECT user.user_id FROM user, user_group
    WHERE
      user.user_id = user_group.user_id AND user_group.group_id = ?
      AND user_group.admin_flags = 'A'", [$group_id]
  );
  if (db_numrows ($res_admins) < 1)
    # TRANSLATORS: the argument is group id (number).
    exit_error (
      sprintf (
        no_i18n ("Group [ %s ] does not seem to have any administrators."),
        $group_id
      )
    );

  # Send one email per admin.
  utils_get_content ("admin/proj_email");
  $message = approval_gen_email (
    $row_grp['group_name'], $row_grp['unix_group_name']
  );
  $to = [];
  while ($row_admins = db_fetch_array ($res_admins))
    $to[] = $row_admins['user_id'];
  $to = join (',', $to);
  sendmail_mail (
    ['from' => $GLOBALS['sys_email_adress'], 'to' => $to],
    ['subject' => no_i18n ("Project Approved"), 'body' => $message]
  );
}
?>
