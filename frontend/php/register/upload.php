<?php
# Temporary download area for project registration.
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

require_once ('../include/init.php');
require_once ('../include/form.php');
extract (sane_import ('files', ['pass' => 'tarball']));
session_require (['isloggedin' => '1']);
$title = ['title' => _("Temporary upload")];

if (empty ($tarball['tmp_name']))
 {
   $HTML->header ($title);
   print form_tag (['enctype' => 'multipart/form-data'])
     . "<p>" . _("Select file to upload:") . "<br />\n"
     . "<input type='file' name='tarball'/>\n"
     . "<input type='submit' value='" . _('Upload file') . "' />"
     . "</p>\n</form>\n";
   $HTML->footer ([]);
   exit (0);
 }

form_check ();

if (!is_uploaded_file ($tarball['tmp_name']))
  exit (0);
if ($tarball['error'] != 0)
  exit_error (sprintf (_("Error during upload: %s"), $tarball['error']));

# Try to move $tmp_path to $path without overwriting if the latter exists;
# return $path when successful, $tmp_path otherwise.
function try_move ($tmp_path, $path)
{
  $state = utils_disable_warnings (E_WARNING);
  $res = link ($tmp_path, $path);
  utils_restore_warnings ($state);
  if (!$res) # Already exists; fallback to temporary file name.
    return $tmp_path;
  unlink ($tmp_path);
  return $path;
}

$path = utils_make_upload_file ($tarball['name'], $errors);

if (empty ($path) || !move_uploaded_file ($tarball['tmp_name'], $path))
  exit_error (_("Cannot move file to the download area."));

$HTML->header ($title);

$name = rawurlencode (basename ($path));
print "<p>" . _("Here's your temporary tar file URL:")
  . " https://$sys_default_domain/submissions_uploads/$name</p>\n";

$HTML->footer ([]);
?>
