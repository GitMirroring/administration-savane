<?php
# Instructions about removing groups.
#
# Copyright (C) 2006, 2009 Sylvain Beucler
# Copyright (C) 2017, 2023 Ineiev <ineiev@gnu.org>
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

global $sys_unix_group_name, $sys_default_domain;
print "<p>";
printf (
  _("To remove a group you administer, please contact\n"
    . "<a href=\"%s\">Savannah hackers</a> indicating the new group\n"
    . "location&mdash;we generally keep programs unless they are\n"
    . "available at other places&mdash;and\n"
    . "optionally the reason why you leave."),
  "//$sys_default_domain/support/?group=$sys_unix_group_name"
);
print "</p>\n";

?>
