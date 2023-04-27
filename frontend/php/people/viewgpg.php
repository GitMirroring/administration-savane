<?php
# Offer user's keys for download.
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

require_once ('../include/init.php');
extract (sane_import ('get', ['digits' => 'user_id']));

if (empty ($user_id))
  exit_missing_param (['user_id']);

$result = db_execute (
  "SELECT user_name, gpg_key FROM user WHERE user_id = ?", [$user_id]
);

if (db_numrows ($result) < 1)
  exit_error (_("User not found."));

$user_name = db_result ($result, 0, 'user_name');
$gpg_key = db_result ($result, 0, 'gpg_key');

if (!$gpg_key)
  exit_error (_("This user hasn't registered a GPG key."));

header ('Content-Type: application/pgp-keys');
header ("Content-Disposition: attachment; filename=$user_name-key.gpg");
# TRANSLATORS: the argument is user's name.
$description = sprintf (_('GPG Key of the user %s'), $user_name);
header ("Content-Description: $description");
print $gpg_key;
?>
