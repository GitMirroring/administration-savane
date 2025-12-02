<?php
# Test markup functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2025 Ineiev
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
#   php testing/markup.php
#
# In case of fail, diagnostic text is output to stdout.
require_once ('include/utils.php');
function markup_fetch_file_list_override ($file_ids)
{
  global $files;
  $ret = [];
  foreach ($file_ids as $id)
    if (!empty ($files[$id]))
      $ret[$id] = $files[$id];
  return $ret;
}
require_once ('include/markup.php');
$filenames = [
  17 => ['example.txt', 'text', true],
  19 => ['floating.png', 'image', true],
  23 => ['sample.dat', 'data', false]
];
foreach ($filenames as $id => $n)
  {
    $it['file_id'] = $id;
    list ($it['filename'], $it['comment'], $it['public']) = $n;
    $files[$id] = $it;
  }

$sys_default_domain = 'example.org';
$sys_file_domain = "test.org";
$sys_home = '/';
function user_isloggedin () { return true; }
function session_issecure () { return true; }
function format_file_url_path ($file, $public)
{
  return utils_public_file_url ($file);
}

function run_test ($in, $out, $out_html)
{
  $res = markup_ascii ($in);

  if ($out !== $res)
    print "markup_ascii doesn't match\nexpected:\n$out\nresult:\n$res\n"
      . "input:\n$in\n";

  $res = markup_full ($in);
  if ($out_html !== $res)
    print "markup_full doesn't match\nexpected:\n$out_html\nresult:\n$res\n"
      . "input:\n$in\n";
}

$in = '= Title =
0 item 1
0* item 1.1
0* _item_ 1.2
0*0 *item* 1.2.1
0*0 item 1.2.2
0*0 item 1.2.3
 0 item 2
00 item 2.1
0 item 3

[//www.gnu.org GNU website]

== Subtitle ==

0 item 1
0 item 2
0 item 3

+verbatim+
0 verbatim 1
0 verbatim 2
0 verbatim 3
-verbatim-';

$out = '= Title =
1. item 1
	* item 1.1
	* _item_ 1.2
		1. *item* 1.2.1
		2. item 1.2.2
		3. item 1.2.3
2. item 2
	1. item 2.1
3. item 3

[//www.gnu.org GNU website]

== Subtitle ==

1. item 1
2. item 2
3. item 3


0 verbatim 1
0 verbatim 2
0 verbatim 3


';

$out_html = '<h2>Title</h2>
<ol>
<li>item 1
<ul>
<li>item 1.1
</li>
<li><i>item</i> 1.2
<ol>
<li><b>item</b> 1.2.1
</li>
<li>item 1.2.2
</li>
<li>item 1.2.3
</li>
</ol>
</li>
</ul>
</li>
<li>item 2
<ol>
<li>item 2.1
</li>
</ol>
</li>
<li>item 3
</li>
</ol>
<p><br />
<a href="//www.gnu.org">GNU website</a><br />
<br />
</p>
<h3>Subtitle</h3>
<p><br />
</p>
<ol>
<li>item 1
</li>
<li>item 2
</li>
<li>item 3
</li>
</ol>
<p><br />
<br />
</p>
<blockquote class=\'verbatim\'><p> 0 verbatim 1<br />
0 verbatim 2<br />
0 verbatim 3<br />
</p></blockquote>
<p><br />
</p>
';

run_test ($in, $out, $out_html);

$in = '
_it_ +nomarkup+ inline *no* markup-nomarkup-

*bold*+nomarkup+ multi-
line
nomarkup-nomarkup- [www.gnu.org continued]
';

$out = '
_it_  inline *no* markup

*bold*
 multi-
line
nomarkup
 [www.gnu.org continued]

';

$out_html = '<p><br />
<i>it</i> <span class=\'nomarkup\'> inline *no* markup</span><br />
<br />
<b>bold</b><br />
</p>
<p class=\'nomarkup\'> multi-<br />
line<br />
nomarkup</p>
<p>&nbsp;<a href="//www.gnu.org">continued</a><br />
<br />
</p>
';

run_test ($in, $out, $out_html);

$in = '
file #19
bug #289
sr #4913
task #83521
file #17
file #23
';

$out = "$in\n";
$out_html = '<p><br />
<img src="//test.org/file/floating.png?file_id=19" /> <br />
<i><a href="/bugs/?289">bug&nbsp;#289</a></i><br />
<i><a href="/support/?4913">sr&nbsp;#4913</a></i><br />
<i><a href="/task/?83521">task&nbsp;#83521</a></i><br />
<i><a href="//test.org/file/example.txt?file_id=17">file&nbsp;#17</a></i><br />
<i><a href="//test.org/file/sample.dat?file_id=23">file&nbsp;#23</a></i><br />
<br />
</p>
';

run_test ($in, $out, $out_html);

$in = "https://a.test/a?a=&amp;b=c\nhttps://a.test/a?a=&amp;b=";
$out = "https://a.test/a?a=&b=c\nhttps://a.test/a?a=&b=\n";
$out_html = '<p><a href="https://a.test/a?a=&amp;b=c">https://a.te ... '
  . "a?a=&amp;b=c</a><br />\n"
  . '<a href="https://a.test/a?a=&amp;b=">https://a.te ... '
  . "/a?a=&amp;b=</a><br />\n</p>\n";

run_test ($in, $out, $out_html);
?>
