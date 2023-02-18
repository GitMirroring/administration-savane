<?php
# Page listing jobs.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2002 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2013, 2017 Ineiev <ineiev@gnu.org>
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

print "<p>"
  ._('Browse through the category menu to find groups looking for your help.')
  . "</p>\n<p>"
  . _("If you're a group admin, log in, enter your group page, follow\n"
      . "the group admin link (in the navigation bar) and you will find a\n"
      . "<i>Post Jobs</i> section where you can submit help wanted requests\n"
      . "to appear in this list.")
  . "</p>\n<p>";
printf (_("To suggest new job categories, visit the\n"
  . "<a href=\"%s\"> support manager</a>."),
  '//savannah.gnu.org/support/?group=administration'
);
print "</p>\n";
?>
