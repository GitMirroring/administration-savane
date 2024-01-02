<?php
# Download attachments.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2001, 2002 Laurent Julliard, CodeX Team, Xerox
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

require_once ('../include/init.php');
require_once ('../include/trackers/general.php');

extract (sane_import ('get', ['digits' => 'file_id']));

if (empty ($file_id))
  exit_missing_param ();

# Check privacy of the item this file is attached to and reject access by
# non-authorized users.

$artifact = ARTIFACT;
$result = db_execute ("
  SELECT trackers_file.item_id, $artifact.group_id
  FROM trackers_file, $artifact
  WHERE
    trackers_file.file_id = ? AND $artifact.bug_id = trackers_file.item_id",
  [$file_id]
);

if (db_numrows ($result) > 0)
  {
    $item_id  = db_result ($result, 0, 'item_id');
    $group_id = db_result ($result, 0, 'group_id');
  }
$result = db_execute ("
  SELECT privacy FROM $artifact WHERE bug_id = ? AND group_id = ?",
  [$item_id, $group_id]
);

if (
  db_numrows ($result) > 0 && db_result ($result, 0, 'privacy') == '2'
  && !member_check_private (0, $group_id)
)
  exit_error (_("Non-authorized access to file attached to private item"));

$result = db_execute ("
  SELECT filename, filesize FROM trackers_file WHERE file_id = ? LIMIT 1",
  [$file_id]
);

if (db_numrows ($result) <= 0)
  {
    # TRANSLATORS: the argument is file id (a number).
    $msg = sprintf (_("Couldn't find attached file (file #%s)"), $file_id);
    exit_error ($msg);
  }

if (db_result ($result, 0, 'filesize') == 0)
  exit_error (_("File has a null size"));

# Redirect to an URL that will pretend the file really exists with
# this name, so all browsers will propose its name as filename when
# saving it.
$prot = 'http';
if (session_issecure ())
  $prot = 'https';
session_redirect (
  "$prot://$sys_file_domain{$sys_home}file/"
  . rawurlencode (db_result ($result, 0, 'filename')) . "?file_id=$file_id"
);
?>
