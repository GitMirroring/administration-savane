<?php
# Download attachments.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2001, 2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
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

# This file is preserved for backwards compatibility only.
# Before 2024-04, it was only referred to in notifications.
# Private files are unsupported, the requests will be rejected
# when processing the redirection to file/$file_name?file_id=$file_id.

require_once ('../include/init.php');
require_once ('../include/trackers/general.php');

extract (sane_import ('get', ['digits' => 'file_id']));

if (empty ($file_id))
  exit_missing_param ();

$result = db_execute ("
  SELECT filename, filesize FROM trackers_file WHERE file_id = ? LIMIT 1",
  [$file_id]
);

# Only check for the existence of the database entry, in order to get
# the filename needed for the redirection; all checks are run after
# the redirection to file/$file_name?&c.
if (!db_numrows ($result))
  {
    # TRANSLATORS: the argument is file id (a number).
    $msg = sprintf (_("Couldn't find attached file (file #%s)"), $file_id);
    exit_error ($msg);
  }

# Redirect to an URL that will pretend the file really exists with
# this name, so all browsers will propose its name as filename when
# saving it.
session_redirect (
  session_protocol () . "://$sys_file_domain{$sys_home}file/"
  . rawurlencode (db_result ($result, 0, 'filename')) . "?file_id=$file_id"
);
?>
