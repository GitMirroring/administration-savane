<?php
# Test some form_* functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2026 Ineiev
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
#
# Invocation:
#
#   php testing/form.php
#
# In case of fail, diagnostic text is output to stdout.
require_once ('include/html.php');
require_once ('include/form.php');

function compare ($out, $expected)
{
  $str = preg_replace ('/\s+/', ' ', $out);
  $str = trim ($str);
  $str = preg_replace ("/='([^']*)'/", '="$1"', $str);
  if ($str == $expected)
    return;
  print "result:\n$out\nexpected:\n$expected\n";
}

$expected = '<input type="radio" name="button" value="val" />';
$out = preg_replace ('/\s/', "\n ", form_radio ('button', 'val', []));
compare ($out, $expected);
$expected =
  '<input type="radio" name="button" value="val" checked="checked" />';
compare (form_radio ('button', 'val', ['checked' => true]) . ' ', $expected);
$expected = '<input type="radio" name="button" value="rbtn" '
  . 'id="val_rbtn_button" /><label for="val_rbtn_button">label</label>';
compare (form_radio ('button', 'rbtn', ['label' => 'label']), $expected);
$expected = '<input type="radio" name="button" value="rbtn" '
  . 'id="button1" /><label for="button1">first button</label>';
compare (
  form_radio ('button', 'rbtn', ['label' => 'first button', 'id' => 'button1']),
  $expected
);
$expected = '<input type="checkbox" id="box" name="box" value="1" />';
compare (form_checkbox ('box'), $expected);
compare (form_checkbox ('box', 0), $expected);
$expected = '<input type="checkbox" id="box" name="box" value="1" />'
  . '<label for="box">first checkbox</label>';
compare (form_checkbox ('box', 0, ['label' => 'first checkbox']), $expected);
$expected = '<input type="checkbox" id="box-0" name="box" value="1" />'
  . '<label for="box-0">first checkbox</label>';
compare (
  form_checkbox ('box', 0, ['label' => 'first checkbox', 'id' => 'box-0']),
  $expected
);
$expected =
  '<input type="checkbox" id="box-1" name="box" value="1" checked="checked" />'
  . '<label for="box-1">first checkbox</label>';
compare (
  form_checkbox ('box', 1, ['label' => 'first checkbox', 'id' => 'box-1']),
  $expected
);

$expected = '<option value="any">any</option>';
compare (form_option ('any'), $expected);
$expected = '<option value="any">Any</option>';
compare (form_option ('any', null, 'Any'), $expected);
compare (form_option ('any', 'none', 'Any'), $expected);
$expected = '<option value="any" selected="selected">Any</option>';
compare (form_option ('any', 'any', 'Any'), $expected);

$expected = '<input type="hidden" name="h0" value="v0" /> '
  . '<input type="hidden" name="h1" value="v1" />';
compare (form_hidden (['h0' => 'v0', 'h1' => 'v1']), $expected);

$sys_www_topdir = '.';
define ('SV_THEME', 'Savannah');
$expected = '<input type="image" id="firefly" name="firefly" '
  . 'alt="mouse" border="0" src="/images/Savannah.theme/leopard.png" '
  . 'width="64" height="62" />';
compare (form_image ('leopard.png', 'mouse', 'firefly'), $expected);
$size = '';
if (file_exists ("images/" . SV_THEME . '.theme/misc/trash.png'))
  $size = 'width="24" height="24" ';
$expected = '<input type="image" id="apple" name="apple" alt="Delete" '
  . 'border="0" src="/images/Savannah.theme/misc/trash.png" ' . "$size/>";
compare (form_image_trash ('apple'), $expected);
$expected = '<input type="image" id="stone" name="stone" alt="drop stone" '
  . 'border="0" src="/images/Savannah.theme/misc/trash.png" ' . "$size/>";
compare (form_image_trash ('stone', 'drop stone'), $expected);
?>
