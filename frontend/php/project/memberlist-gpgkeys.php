<?php
# Get group keyrings.
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

require_once('../include/init.php');

extract (sane_import ('get', ['true' => 'download']));
$project = project_get_object ($group_id);

if (basename ($php_self) === 'memberlist-gpgkeys.php')
  {
    $keyring = $project->getGPGKeyring();
    $error_no_keyring = _("Member Keyring is empty, no keys were registered");
    $title = _("Group Member GPG Keyring");
    $filename = "$group-members.gpg";
    $description = sprintf(_('Member GPG keyring of %s group.'), $group);
    $note = "\n\n"
      . _("Note that this keyring is not intended for checking releases of "
          . "that group.\nUse Group Release Keyring instead.");
  }
else
  {
    $keyring = group_get_preference ($group_id, 'gpg_keyring');
    $error_no_keyring = _("Group Keyring is empty, no keys were registered");
    $title = _("Group Release GPG Keyring");
    $filename = "$group-keyring.gpg";
    $description = sprintf(_('Release GPG keyring of %s group.'), $group);
    $note = '';
  }

if (!$keyring)
  exit_error ($error_no_keyring);

if ($download)
  {
    header ('Content-Type: application/pgp-keys');
    header ("Content-Disposition: attachment; filename=$filename");
    header ("Content-Description: $description");
    print "$description$note\n\n$keyring";
    exit (0);
  }

site_project_header (
  ['title' => $title, 'group' => $group_id, 'context' => 'keys']
);

print '<p>';
printf (
  _("You can <a href=\"%s\">download the keyring</a> and import it with\n"
    . "the command %s."),
  "$php_self?group=$group&amp;download=1",
  '<em>gpg --import &lt;file&gt;</em>'
);
print "</p>\n";

site_project_footer (array ());
?>
