<?php
# Test markup functions.
#
# Copyright (C) 2022, 2023 Ineiev
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
#
# Invocation:
#
#   php testing/markup.php
#
# In case of fail, diagnositc text is output to stdout.
require_once ('include/utils.php');
require_once ('include/markup.php');

$sys_default_domain = 'i18n.sv.gnu.org';
$sys_file_domain = "file.$sys_default_domain";
function user_isloggedin () { return true; }
function session_issecure () { return true; }

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
<p>
<a href="//www.gnu.org">GNU website</a>

</p>
<h3>Subtitle</h3>
<p>
</p>
<ol>
<li>item 1
</li>
<li>item 2
</li>
<li>item 3
</li>
</ol>
<p>

</p>
<blockquote class=\'verbatim\'><p> 0 verbatim 1<br />
0 verbatim 2<br />
0 verbatim 3<br />
</p></blockquote>
<p>
</p>
';

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

$out_html = '<p>
<i>it</i> <span class=\'nomarkup\'> inline *no* markup</span>

<b>bold</b>
</p>
<p class=\'nomarkup\'> multi-<br />
line<br />
nomarkup</p>
<p>&nbsp;<a href="//www.gnu.org">continued</a>

</p>
';

run_test ($in, $out, $out_html);
?>
