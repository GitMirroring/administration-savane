# Makefile.am for images.
#
# Copyright (C) 2007 Sylvain Beucler
# Copyright (C) 2021, 2023 Ineiev
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

stamp-icons:
	for theme in *.theme mime_type common/arrows*/ common/bool*/ \
	    common/contexts*/ common/misc*/ common/roles*/; do \
	  ( \
	    cd $$theme; \
	    for icon in *.orig.png; do \
	      if [ -e $$icon ]; then \
	        convert -resize 24x24 -background white -antialias $$icon \
	          `basename $$icon .orig.png`.png; \
	      fi; \
	    done \
	  ) \
	done
	touch $@

# Keep those separate for AM1.9 to recognize them
# (ie not the "all-local distdir-local: stamp-icons" one-liner)
all-local: stamp-icons
DISTCLEANFILES = stamp-icons

maintainer-clean-local:
	find -name "*.orig.png" | while read file; do \
	  if [ -e $${file%.orig.png}.png ]; then \
	    rm $${file%.orig.png}.png; \
          fi; \
        done
	$(RM) stamp-icons
