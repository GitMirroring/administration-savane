<?php
# Send message to given user via Savane.
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

require_once ('include/init.php');
require_once ('include/sendmail.php');

extract (sane_import ('request',
  [
    'true' => ['cc_me', 'send_mail'],
    'digits' => 'touser',
    'name' => 'fromuser',
    'pass' => ['subject', 'body', 'feedback']
  ])
);

if (!user_isloggedin ())
  exit_not_logged_in ();

if (!$touser)
  exit_missing_param (['touser']);

$result = db_execute ("
  SELECT email, user_name FROM user
  WHERE user_id = ? AND (status = 'A' OR status = 'SQD')",
  [$touser]
);

if (db_numrows ($result) < 1)
  # TRANSLATORS: the argument is user id (a number).
  exit_error (sprintf (_('User %s does not exist'), $touser));

if (!$send_mail)
  {
    $HTML->header (['title' => _('Send a message')]);
    sendmail_form_message ($php_self, $touser, $cc_me);
    $HTML->footer ([]);
    exit;
  }

$missing_params = [];
foreach (['subject', 'body', 'fromuser'] as $p)
  if (empty ($$p))
    $missing_params[] = $p;
if (!empty ($missing_params))
  exit_missing_param ($missing_params);

if ($cc_me)
  $touser .= ", $fromuser";

sendmail_mail (
  ['from' => $fromuser, 'to' => $touser],
  ['subject' => $subject, 'body' => $body]
);
$HTML->header (['title' => _('Message Sent')]);
print html_feedback_top ();
$HTML->footer ([]);
exit;
?>
