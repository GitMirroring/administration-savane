<?php
# Serve comments in an item as an ASCII file, encrypted when needed.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

$includes = [
  'init', 'gpg', 'member', 'trackers/general', 'trackers/format',
  'savane-git'
];

foreach ($includes as $i)
  require_once ("../include/$i.php");

extract (sane_import ('get', ['name' => 'user']));

$tracker = ARTIFACT;

exit_if_missing ('item_id');

extract (utils_find_item ($tracker, $item_id, ['privacy', 'spamscore']));
if ($spamscore >= 5)
  exit_error (sprintf (_("Item #%s not found."), $item_id));

$group = project_get_object ($group_id);
if ($group->isError ())
  exit_no_group ();

$data_are_private = $privacy == '2' || !$group->isPublic ();
$ctype = "text/plain";
$fname = "$item_id.txt";
if ($data_are_private)
  {
    exit_if_missing ('user');
    $user_id = user_getid ($user);
    if (empty ($user_id))
      exit_user_not_found ($user);

    if ($privacy == '2' && !member_check_private ($user_id, $group_id))
      exit_permission_denied ();

    if (!($group->isPublic () || member_check ($user_id, $group_id)))
      exit_permission_denied ();
    $fname .= '.gpg';
    $ctype = "application/pgp-encrypted";
  }
$message = format_item_details ($item_id, $group_id, true);
$message .= git_agpl_notice ('This file was served by Savane.');
if ($data_are_private)
  {
    list ($exit_code, $error_msg, $encrypted_message) =
      gpg_encrypt_to_user ($user_id, $message);
    if ($exit_code)
      exit_error ($error_msg);
    $message = $encrypted_message;
  }

header ("Content-Type: $ctype");
header ("Content-Disposition: attachment; filename=$fname");
print $message;
?>
