<?php
# Output dependency graph in the SVG format.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

require_once ("../include/sane.php");
extract (sane_import ('request',
  ['strings' => [['list_format', ['default' => 'html', 'text', 'svg']]]]
));

if (empty ($list_format))
  $list_format = 'html';

if ($list_format != 'html')
  $skip_csp_headers = 1;

$includes = ['init', 'http', 'trackers/general', 'trackers/view-dependencies'];
foreach ($includes as $i)
  require_once ("../include/$i.php");

extract (sane_import ('request',
  [
    'true' => 'include_closed',
    'digits' =>  ['chunksz', ['offset', [0, 410338673]]]
  ]
));
extract (sane_import ('get', ['digits' => [['max_rows', [1, 4913]]]]));

if (!empty ($max_rows))
  $chunksz = $max_rows;

$default_chunksz = 10;
if (empty ($chunksz))
  $chunksz = $default_chunksz;
$chunksz = intval ($chunksz);
if ($chunksz <= 0)
  $chunksz = $default_chunksz;

trackers_view_dependencies ($list_format);
?>
