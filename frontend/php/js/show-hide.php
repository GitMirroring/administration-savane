<?php
# Generate show/hide JavaScript code.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Pogonyshev <pogonyshev--gmx.net>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2024 Ineiev <ineiev@gnu.org>
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

require_once ('../include/sane.php');
header ('Content-Type: text/javascript');

extract (sane_import('request',
  [
    'digits' => 'deploy',
    'preg' => [['box_id', 'suffix', '/^\w*$/']],
    'specialchars' => 'legend'
  ]
));

if ($box_id === null)
  $box_id = "";
if ($suffix === null)
  $suffix = "";

foreach (
  ['hide' => 'linkhide' , 'show' => 'linkshow', 'cont' => 'content']
  as $k => $v
)
  {
    $$k = "{$box_id}$v$suffix";
    ${"{$k}_el"} = "document.getElementById('{$$k}')";
  }

$sign_func = function ($sign, $id, $legend)
{
  return "<span class=\"show-hide\" id=\"$id\">"
   . "<span class=\"minusorplus\">($sign)</span>$legend</span>";
};
print "document.write('"
  .  $sign_func ('-', $hide, $legend) .  $sign_func ('+', $show, $legend)
  . "');\n";

$inline_el = $show_el;
$none_el = $hide_el;
if ($deploy)
  {
    $inline_el = $hide_el;
    $none_el = $show_el;
  }
print "$inline_el.style.display = 'inline';\n";
print "$none_el.style.display = 'none';\n";

$on_click_func = function ($this_el, $that_el, $cont_el, $disp)
{
  print "$this_el.onclick = function ()\n"
    . "{\n"
    . "  $cont_el.style.display='$disp';\n"
    . "  $this_el.style.display='none';\n"
    . "  $that_el.style.display='inline';\n"
    . "}\n";
};
$on_click_func ($hide_el, $show_el, $cont_el, 'none');
$on_click_func ($show_el, $hide_el, $cont_el, 'inline');
unset ($on_click_func, $sign_func);
?>
