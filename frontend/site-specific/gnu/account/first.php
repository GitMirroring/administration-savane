<?php
# Note for first valid login.
#
# Copyright (C) 2019, 2023 Ineiev <ineiev@gnu.org>
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

# The code is included from functions, so we need 'global'.
global $sys_home, $sys_name;

print '<p>';
# TRANSLATORS: the second argument is system name (like Savannah).
printf (
  _("You should take some time to read the <a href=\"%1\$s\">Savane User\n"
    . "Guide</a> so that you may take full advantage of %2\$s."),
    "${sys_home}/maintenance/back-page/", $sys_name
);
print "</p>\n<p>";
printf (
  _("Note that <a href=\"%s\">unused accounts</a>\n"
    . "may be removed without notice."),
  "${sys_home}maintenance/IdleAccounts/"
);
print "</p>\n";
?>
