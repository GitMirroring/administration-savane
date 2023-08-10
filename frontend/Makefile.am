# Makefile.am for frontend part (mostly PHP).
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

SUBDIRS = php/images 

EXTRA_DIST = . php/link-to-file.php php/link-to-projects.php php/link-to-users.php

frontenddir = $(pkgdatadir)/frontend
dest_dir = $(DESTDIR)$(frontenddir)
if HAVE_FRONTEND
install-data-hook:
	find $(srcdir) ! -type d ! -type l ! -name 'Makefile*' -print \
	  | while read f; do \
	      dir=`echo "$$f" \
	        | sed 's,$(srcdir),$(dest_dir),;s,/[^/]*$$,,'`; \
	      $(INSTALL_DATA) -D -t $$dir $$f || exit 1; \
	    done
	for pass in dir file; do \
	  find $(srcdir) -type l -print \
	    | while read l; do \
	        if test -d "$$l/"; then type=dir; else type=file; fi; \
	        if test $$pass != $$type; then continue; fi; \
	        dir=`echo "$$l" \
	          | sed 's,$(srcdir),$(dest_dir),;s,/[^/]*$$,,'`; \
	        source=`readlink "$$l"`; \
	        base=`basename $$l`; \
	        test -d "$$dir" || $(INSTALL) -d $$dir; \
	        saved_pwd=`pwd`; cd "$$dir"; \
	        $(LN_S) -f "$$source" $$base || exit 1; \
	        cd $$saved_pwd; \
	      done \
	done

nobase_frontend_DATA = php/include/ac_config.php
uninstall-hook: ; rm -fr $(dest_dir)

installcheck-local: ; cd $(dest_dir)/php; \
  for t in markup sane format_date; do \
    res=`$(PHP) testing/$$t.php`; \
    if test "x$$res" = x; then continue; fi; \
    echo $$t failed: ; echo $$res; exit 1; \
  done
endif # HAVE_FRONTEND
