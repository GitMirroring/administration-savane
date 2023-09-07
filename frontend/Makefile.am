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

EXTRA_DIST = . php/file php/projects php/users

basic_png = \
  php/images/mime_type/blank.png \
  php/images/OldCERN.theme/floating.png \
  php/images/OldCERN.theme/point.png \
  php/images/OldCERN.theme/icon.png \
  php/images/OldCERN.theme/thin-image.png \
  php/images/Forest.theme/floating.png \
  php/images/Forest.theme/point.png \
  php/images/Forest.theme/icon.png \
  php/images/Forest.theme/thin-image.png \
  php/images/Emeraud.theme/floating.png \
  php/images/Emeraud.theme/point.png \
  php/images/Emeraud.theme/icon.png \
  php/images/Emeraud.theme/thin-image.png \
  php/images/Gna.theme/floating.png \
  php/images/Gna.theme/point.png \
  php/images/Gna.theme/icon.png \
  php/images/Gna.theme/thin-image.png \
  php/images/common/feed16.png \
  php/images/common/floating.png \
  php/images/common/point.png \
  php/images/common/icon.png \
  php/images/common/thin-image.png \
  php/images/Light.theme/floating.png \
  php/images/Light.theme/point.png \
  php/images/Light.theme/icon.png \
  php/images/Light.theme/thin-image.png \
  php/images/Water.theme/floating.png \
  php/images/Water.theme/point.png \
  php/images/Water.theme/icon.png \
  php/images/Water.theme/thin-image.png \
  php/images/LinuxfrPointOrg.theme/floating.png \
  php/images/LinuxfrPointOrg.theme/icon.png \
  php/images/LinuxfrPointOrg.theme/thin-image.png \
  php/images/Reverse.theme/floating.png \
  php/images/Reverse.theme/point.png \
  php/images/Reverse.theme/icon.png \
  php/images/Reverse.theme/thin-image.png \
  php/images/www.gnu.org.theme/floating.png \
  php/images/www.gnu.org.theme/icon.png \
  php/images/www.gnu.org.theme/thin-image.png \
  php/images/pllx.theme/floating.png \
  php/images/pllx.theme/icon.png \
  php/images/pllx.theme/thin-image.png \
  php/images/misc/leaves-Forest.png \
  php/images/misc/sand-Savannah.png \
  php/images/misc/point-Codex.png \
  php/images/misc/cvs.orig-Right.png \
  php/images/misc/sslWarning.png \
  php/images/misc/people.orig-2.png \
  php/images/SlashD.theme/floating.png \
  php/images/SlashD.theme/icon.png \
  php/images/SlashD.theme/thin-image.png \
  php/images/Elessar.theme/floating.png \
  php/images/Elessar.theme/point.png \
  php/images/Elessar.theme/icon.png \
  php/images/Elessar.theme/thin-image.png \
  php/images/Savannah.theme/leopard.png \
  php/images/Savannah.theme/floating.png \
  php/images/Savannah.theme/point.png \
  php/images/Savannah.theme/steel.png \
  php/images/Savannah.theme/icon.png \
  php/images/Savannah.theme/thin-image.png \
  php/images/DarkSlate.theme/floating.png \
  php/images/DarkSlate.theme/point.png \
  php/images/DarkSlate.theme/icon.png \
  php/images/DarkSlate.theme/thin-image.png

derived_png = \
  php/images/common/arrows1/bottom.png \
  php/images/common/arrows1/down.png \
  php/images/common/arrows1/firstgrey.png \
  php/images/common/arrows1/first.png \
  php/images/common/arrows1/lastgrey.png \
  php/images/common/arrows1/last.png \
  php/images/common/arrows1/nextgrey.png \
  php/images/common/arrows1/next.png \
  php/images/common/arrows1/previousgrey.png \
  php/images/common/arrows1/previous.png \
  php/images/common/arrows1/top.png \
  php/images/common/arrows1/up.png \
  php/images/common/arrows2/bottom.png \
  php/images/common/arrows2/down.png \
  php/images/common/arrows2/firstgrey.png \
  php/images/common/arrows2/first.png \
  php/images/common/arrows2/lastgrey.png \
  php/images/common/arrows2/last.png \
  php/images/common/arrows2/nextgrey.png \
  php/images/common/arrows2/next.png \
  php/images/common/arrows2/previousgrey.png \
  php/images/common/arrows2/previous.png \
  php/images/common/arrows2/top.png \
  php/images/common/arrows2/up.png \
  php/images/common/arrows3/bottom.png \
  php/images/common/arrows3/down.png \
  php/images/common/arrows3/firstgrey.png \
  php/images/common/arrows3/first.png \
  php/images/common/arrows3/lastgrey.png \
  php/images/common/arrows3/last.png \
  php/images/common/arrows3/nextgrey.png \
  php/images/common/arrows3/next.png \
  php/images/common/arrows3/previousgrey.png \
  php/images/common/arrows3/previous.png \
  php/images/common/arrows3/top.png \
  php/images/common/arrows3/up.png \
  php/images/common/bool1/ok.png \
  php/images/common/bool1/wrong.png \
  php/images/common/bool-alt/ok.png \
  php/images/common/bool-alt/wrong.png \
  php/images/common/bool-Emeraud/ok.png \
  php/images/common/bool-Emeraud/wrong.png \
  php/images/common/bool-pllx/ok.png \
  php/images/common/bool-pllx/wrong.png \
  php/images/common/contexts1/admin.png \
  php/images/common/contexts1/bug-alt.png \
  php/images/common/contexts1/bug.png \
  php/images/common/contexts1/cvs.png \
  php/images/common/contexts1/desktop.png \
  php/images/common/contexts1/directory-alt.png \
  php/images/common/contexts1/directory.png \
  php/images/common/contexts1/download.png \
  php/images/common/contexts1/help.png \
  php/images/common/contexts1/keys.png \
  php/images/common/contexts1/mail.png \
  php/images/common/contexts1/main.png \
  php/images/common/contexts1/man.png \
  php/images/common/contexts1/news.png \
  php/images/common/contexts1/patch.png \
  php/images/common/contexts1/people.png \
  php/images/common/contexts1/preferences.png \
  php/images/common/contexts1/task.png \
  php/images/common/contexts-DarkSlate/admin.png \
  php/images/common/contexts-DarkSlate/bug.png \
  php/images/common/contexts-DarkSlate/cvs.png \
  php/images/common/contexts-DarkSlate/desktop.png \
  php/images/common/contexts-DarkSlate/directory.png \
  php/images/common/contexts-DarkSlate/download.png \
  php/images/common/contexts-DarkSlate/help.png \
  php/images/common/contexts-DarkSlate/keys.png \
  php/images/common/contexts-DarkSlate/mail.png \
  php/images/common/contexts-DarkSlate/main.png \
  php/images/common/contexts-DarkSlate/man.png \
  php/images/common/contexts-DarkSlate/news.png \
  php/images/common/contexts-DarkSlate/patch.png \
  php/images/common/contexts-DarkSlate/people.png \
  php/images/common/contexts-DarkSlate/preferences.png \
  php/images/common/contexts-DarkSlate/task.png \
  php/images/common/contexts-Emeraud/admin.png \
  php/images/common/contexts-Emeraud/bug.png \
  php/images/common/contexts-Emeraud/cvs.png \
  php/images/common/contexts-Emeraud/desktop.png \
  php/images/common/contexts-Emeraud/directory.png \
  php/images/common/contexts-Emeraud/download.png \
  php/images/common/contexts-Emeraud/help.png \
  php/images/common/contexts-Emeraud/keys.png \
  php/images/common/contexts-Emeraud/mail.png \
  php/images/common/contexts-Emeraud/main.png \
  php/images/common/contexts-Emeraud/man.png \
  php/images/common/contexts-Emeraud/news.png \
  php/images/common/contexts-Emeraud/patch.png \
  php/images/common/contexts-Emeraud/people.png \
  php/images/common/contexts-Emeraud/preferences.png \
  php/images/common/contexts-Emeraud/task.png \
  php/images/common/contexts-Light/admin.png \
  php/images/common/contexts-Light/bug.png \
  php/images/common/contexts-Light/cvs.png \
  php/images/common/contexts-Light/desktop.png \
  php/images/common/contexts-Light/directory.png \
  php/images/common/contexts-Light/download.png \
  php/images/common/contexts-Light/help.png \
  php/images/common/contexts-Light/keys.png \
  php/images/common/contexts-Light/mail.png \
  php/images/common/contexts-Light/main.png \
  php/images/common/contexts-Light/man.png \
  php/images/common/contexts-Light/news.png \
  php/images/common/contexts-Light/patch.png \
  php/images/common/contexts-Light/people.png \
  php/images/common/contexts-Light/preferences.png \
  php/images/common/contexts-Light/task.png \
  php/images/common/contexts-OldCERN/admin.png \
  php/images/common/contexts-OldCERN/bug.png \
  php/images/common/contexts-OldCERN/cvs.png \
  php/images/common/contexts-OldCERN/desktop.png \
  php/images/common/contexts-OldCERN/directory.png \
  php/images/common/contexts-OldCERN/download.png \
  php/images/common/contexts-OldCERN/help.png \
  php/images/common/contexts-OldCERN/keys.png \
  php/images/common/contexts-OldCERN/mail.png \
  php/images/common/contexts-OldCERN/main.png \
  php/images/common/contexts-OldCERN/man.png \
  php/images/common/contexts-OldCERN/news.png \
  php/images/common/contexts-OldCERN/patch.png \
  php/images/common/contexts-OldCERN/people.png \
  php/images/common/contexts-OldCERN/preferences.png \
  php/images/common/contexts-OldCERN/task.png \
  php/images/common/contexts-pllx/admin.png \
  php/images/common/contexts-pllx/bug.png \
  php/images/common/contexts-pllx/cvs.png \
  php/images/common/contexts-pllx/desktop.png \
  php/images/common/contexts-pllx/directory.png \
  php/images/common/contexts-pllx/download.png \
  php/images/common/contexts-pllx/help.png \
  php/images/common/contexts-pllx/keys.png \
  php/images/common/contexts-pllx/mail.png \
  php/images/common/contexts-pllx/main.png \
  php/images/common/contexts-pllx/man.png \
  php/images/common/contexts-pllx/news.png \
  php/images/common/contexts-pllx/patch.png \
  php/images/common/contexts-pllx/people.png \
  php/images/common/contexts-pllx/preferences.png \
  php/images/common/contexts-pllx/task.png \
  php/images/common/misc-DarkSlate/edit.png \
  php/images/common/misc-DarkSlate/print.png \
  php/images/common/misc-DarkSlate/trash.png \
  php/images/common/misc-DarkSlate/www.png \
  php/images/common/misc-default/edit.png \
  php/images/common/misc-default/print.png \
  php/images/common/misc-default/trash.png \
  php/images/common/misc-default/www.png \
  php/images/common/misc-Emeraud/edit.png \
  php/images/common/misc-Emeraud/print.png \
  php/images/common/misc-Emeraud/trash.png \
  php/images/common/misc-Emeraud/www.png \
  php/images/common/misc-Light/edit.png \
  php/images/common/misc-Light/print.png \
  php/images/common/misc-Light/trash.png \
  php/images/common/misc-Light/www.png \
  php/images/common/misc-pllx/edit.png \
  php/images/common/misc-pllx/print.png \
  php/images/common/misc-pllx/trash.png \
  php/images/common/misc-pllx/www.png \
  php/images/common/roles1/assignee.png \
  php/images/common/roles1/people.png \
  php/images/common/roles1/project-admin.png \
  php/images/common/roles1/project-member.png \
  php/images/common/roles1/site-admin.png \
  php/images/common/roles-Emeraud/assignee.png \
  php/images/common/roles-Emeraud/people.png \
  php/images/common/roles-Emeraud/project-admin.png \
  php/images/common/roles-Emeraud/project-member.png \
  php/images/common/roles-Emeraud/site-admin.png \
  php/images/mime_type/a.png \
  php/images/mime_type/back.png \
  php/images/mime_type/bak.png \
  php/images/mime_type/binary.png \
  php/images/mime_type/bomb.png \
  php/images/mime_type/compressed.png \
  php/images/mime_type/c.png \
  php/images/mime_type/css.png \
  php/images/mime_type/deb.png \
  php/images/mime_type/dia.png \
  php/images/mime_type/dir.png \
  php/images/mime_type/down.png \
  php/images/mime_type/dvi.png \
  php/images/mime_type/folder.open.png \
  php/images/mime_type/folder.png \
  php/images/mime_type/folder.sec.png \
  php/images/mime_type/forward.png \
  php/images/mime_type/hand.right.png \
  php/images/mime_type/image.png \
  php/images/mime_type/mpg.png \
  php/images/mime_type/ogg.png \
  php/images/mime_type/pdf.png \
  php/images/mime_type/pgp.png \
  php/images/mime_type/php.png \
  php/images/mime_type/p.png \
  php/images/mime_type/py.png \
  php/images/mime_type/readme.png \
  php/images/mime_type/rpm.png \
  php/images/mime_type/scm.png \
  php/images/mime_type/sh.png \
  php/images/mime_type/so.png \
  php/images/mime_type/tex.png \
  php/images/mime_type/text.png \
  php/images/mime_type/unknown.png \
  php/images/mime_type/up.png \
  php/images/mime_type/xml.png

png_src = $(derived_png:%.png=%.orig.png)

frontenddir = $(pkgdatadir)/frontend
dest_dir = $(DESTDIR)$(frontenddir)

if HAVE_FRONTEND
$(derived_png): %.png: %.orig.png
	$(MKDIR_P) `dirname $@`
	convert -resize 24x24 -background white -antialias $< $@

install-data-hook:
	find $(srcdir) ! -type d ! -type l ! -name 'Makefile*' \
	  ! -name '*.orig.png' -print \
	  | while read f; do \
	      dir=`echo "$$f" \
	        | ${SED} 's,$(srcdir),$(dest_dir),;s,/[^/]*$$,,'`; \
	      $(INSTALL_DATA) -D -t $$dir $$f || exit 1; \
	    done
	for pass in dir file; do \
	  find $(srcdir) -type l -print \
	    | while read l; do \
	        if test -d "$$l/"; then type=dir; else type=file; fi; \
	        if test $$pass != $$type; then continue; fi; \
	        dir=`echo "$$l" \
	          | ${SED} 's,$(srcdir),$(dest_dir),;s,/[^/]*$$,,'`; \
	        source=`readlink "$$l"`; \
	        base=`basename $$l`; \
	        test -d "$$dir" || $(INSTALL) -d $$dir; \
	        saved_pwd=`pwd`; cd "$$dir"; \
	        $(LN_S) -f "$$source" $$base || exit 1; \
	        cd $$saved_pwd; \
	      done \
	done

nobase_frontend_DATA = \
  php/include/ac_config.php $(derived_png) $(png_src) $(basic_png)
CLEANFILES = $(derived_png)
uninstall-hook: ; rm -fr $(dest_dir)

installcheck-local: ; cd $(dest_dir)/php; \
  for t in markup sane format_date; do \
    res=`$(PHP) testing/$$t.php`; \
    if test "x$$res" = x; then continue; fi; \
    echo $$t failed: ; echo $$res; exit 1; \
  done
endif # HAVE_FRONTEND
