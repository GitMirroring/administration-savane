<?php
# <one line to give a brief idea of what this does.>
# 
#  Copyright (C) 1999, 2000 The SourceForge Crew
#  Copyright (C) 2000-2006 Mathieu Roy
#  Copyright (C) 2002-2006 Tobias Toedter <t.toedter--gmx.net>
#  Copyright (C) 2014, 2016, 2017 Assaf Gordon
#  Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
#  Copyright (C) 2013, 2014, 2017-2024 Ineiev
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


require_once '../../frontend/php/include/utils.php';

class include_utils extends PHPUnit_TestCase
  {
    function testStringIsASCII()
      {
        $this->assertTrue(utils_is_ascii("ABCaoidn 97a 18923 471 90asf y0l"));
        $this->assertTrue(utils_is_ascii("This is a string with \n a newline"));
        $this->assertFalse(utils_is_ascii("Tobias Tödter"));
      }

    function testMakeLinks()
      {
        # Construct a hash with input strings and expected output strings
        $urls = array(
           ''
        => '',
           'No conversion'
        => 'No conversion',
           'Simple www.conversion.org'
        => 'Simple <a href="http://www.conversion.org">http://www.conversion.org</a>',
           'Simple www.conversion.org/ with backslash'
        => 'Simple <a href="http://www.conversion.org/">http://www.conversion.org/</a> with backslash',
           'Simple www.conversion.org/index.html'
        => 'Simple <a href="http://www.conversion.org/index.html">http://www.conversion.org/index.html</a>',
           'www.dont-include-the-dot.com.'
        => '<a href="http://www.dont-include-the-dot.com">http://www.dont-include-the-dot.com</a>.',
           "Include \n newlines \n www.as-well.com,\n please!"
        => "Include \n newlines \n <a href=\"http://www.as-well.com\">http://www.as-well.com</a>,\n please!",
           'http://this.should.be/marked'
        => '<a href="http://this.should.be/marked">http://this.should.be/marked</a>',
           '<a href="http://dont-touch-me.net/">Go away!</a>'
        => '<a href="http://dont-touch-me.net/">Go away!</a>',
           '<a href="http://dont-touch-me.net/">Go away!</a>, but www.markup-me.com'
        => '<a href="http://dont-touch-me.net/">Go away!</a>, but <a href="http://www.markup-me.com">http://www.markup-me.com</a>',
           'http://www.mail-archive.com/bug-grep@gnu.org/msg00068.html'
        => '<a href="http://www.mail-archive.com/bug-grep&#64;gnu.org/msg00068.html">http://www.mail-archive.com/bug-grep&#64;gnu.org/msg00068.html</a>',
           'www.mail-archive.com/bug-grep@gnu.org/msg00068.html'
        => '<a href="http://www.mail-archive.com/bug-grep&#64;gnu.org/msg00068.html">http://www.mail-archive.com/bug-grep&#64;gnu.org/msg00068.html</a>',
	   'this@is.a-mail-link.org'
	=> '<a href="mailto:this@is.a-mail-link.org">this@is.a-mail-link.org</a>',
	   'Link to bug #1234, please'
	=> 'Link to <a href="bugs/?func=detailitem&amp;item_id=1234" class="italic">bug&nbsp;#1234</a>, please',
           'Other <html> tags should not be touched'
        => 'Other <html> tags should not be touched',
           'Even if in "<quotes>"'
        => 'Even if in "<quotes>"',
           'Or in single \'<quotes> escaped\''
        => 'Or in single \'<quotes> escaped\'',
        );

        foreach ($urls as $testdata => $expected)
          {
            $this->assertEquals($expected, utils_make_links($testdata));
	  }
      }
  }
?>
