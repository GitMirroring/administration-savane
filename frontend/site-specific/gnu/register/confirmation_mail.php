<?php
# Project registration STEP 6 Confirmation mail
#    Here, you can configure the mail sent to user and admins.
#    BEWARE, this file's content must be PHP, with no syntax errors.
#    Do not modify it until you really know what you're doing.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2003 Jaime E. Villate
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2026 Ineiev <ineiev@gnu.org>
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

# This string is not localized because it's sent to admins.
function confirmation_gen_email ($type_base_host, $user_realname, $user_email,
   $type_admin_email_address, $form_license, $form_license_other,
   $form_full_name, $unix_name, $type, $form_purpose, $form_required_sw,
   $form_comments)
{
   $message = sprintf (
     no_i18n ("\nA package was submitted to %s\n"
       . "This mail was sent to %s\n\n\n"
       . "%s described the package as follows:\n"
       . "License:       %s\n"
       . "Other License: %s\n"
       . "Package:       %s\n"
       . "System name:   %s\n"
       . "Type:          %s\n"
       . "\n"
       . "Description:\n"
       . "%s\n"
       . "Other Software Required:\n"
       . "%s\n"
       . "\n"
       . "Other Comments:\n"
       . "%s"),
     $type_base_host, "$user_email, $type_admin_email_address",
     "$user_realname <$user_email>",
     $form_license, $form_license_other, $form_full_name,
     $unix_name, $type, $form_purpose, $form_required_sw,
     $form_comments);
   return $message;
}
?>
