<?php
# Page showing selected job posts.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler <beuc--beuc.net>
# Copyright (C) 2013, 2014, 2017-2023 Ineiev <ineiev@gnu.org>
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
require_once ('../include/people/general.php');

extract (sane_import ('get',
  [
    'true' => 'submit',
    'array' => [['categories', 'types', [null, 'digits']]],
  ]
));

if (!isset ($types))
  $types = [];
if (!isset ($categories))
  $categories = [];

$finish_page = function ()
{
  site_project_footer ([]);
  exit (0);
};

if ($group_id)
  {
    site_project_header (
      [
        'title' => _('Group Help Wanted'),
        'group' => $group_id, 'context' => 'people',
      ]
    );
    utils_get_content ("people/index_group");
    print people_show_project_jobs ($group_id);
    $finish_page ();
  }

site_header (['title' => _('Groups Needing Help'), 'context' => 'people']);

if (!($categories || $types || $submit))
  {
    utils_get_content ("people/index");
    print people_show_table ();
    $finish_page ();
  }

foreach ($categories as $cat)
  if (people_get_category_name ($cat) == 'Invalid ID')
    {
      fb (sprintf (_("Job category #%s does not exist"), $cat), 1);
      $finish_page ();
    }
foreach ($types as $ty)
  if (people_get_type_name ($ty) == 'Invalid ID')
    {
      fb (sprintf (_("Group type #%s does not exist"), $ty), 1);
      $finish_page ();
    }
print people_show_jobs ($categories, $types);
$finish_page ();
?>
