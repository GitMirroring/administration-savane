<?php
# Savannah - group registration step 6, Confirmation mail.
# Here, you can configure the mail sent to user and admins.

# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2013 Karl Berry
# Copyright (C) 2017, 2022, 2023 Ineiev <ineiev@gnu.org>
#
# This file is part of Savane.
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

function approval_message_fmt ()
{
  # This message is not localized because it's sent to admins.
  return "\n"
    . "Your group has been approved on %s.\n"
    . "Group full name:   %s\n"
    . "Group system name: %s\n"
    . "Group page:        %s\n"
    . "\n"
    . "Please note, that it will take up to half an hour for the system to\n"
    . "be updated (CVS repository creation for instance) before your group\n"
    . "will be fully functional.\n"
    . "\n"
    . "Enjoy the system, and please tell others about %s.\n"
    . "Let us know if there is anything we can do to help you.\n"
    . "\n"
    . " -- the %s volunteers\n"
    . "\n";
}

# We include this as function, it's easier to work with vars this way.
function approval_gen_email ($group_name, $unix_group_name)
{
  global $sys_name, $sys_https_host;

  if (empty ($sys_name))
    $forge = '<unknown>';
  else
    $forge = $sys_name;
  if (empty ($sys_https_host))
    $host = '<no HTTPS host>';
  else
    $host = $sys_https_host;
  return
    sprintf (
      approval_message_fmt (), $forge, $group_name, $unix_group_name,
      "https://$host/projects/$unix_group_name", $forge, $forge
    );
}
?>
