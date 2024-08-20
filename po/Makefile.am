# Based on gettext-0.21.1/gettext-tools/examples/hello-php/po/Makefile.am.
# This file is in the public domain.
#
# Savane-specific changes:
#
# - set POTFILES, DOMAIN, COPYRIGHT_HOLDER, MSGMERGE_OPTIONS
# - verify POTFILES in dist-hook
# - use configured ${SED} value
# - use sed instead of basename
# - use msgmerge --update with --backup=none.
#
# Makefile configuration - processed by automake.

# List of files which contain translatable strings.
# Omissions are checked in dist-hook.
POTFILES = \
	frontend/php/bug/index.php \
	frontend/php/git/admin/index.php \
	frontend/php/git/index.php \
	frontend/php/bugs/comment.php \
	frontend/php/bugs/item.php \
	frontend/php/bugs/dependencies.php \
	frontend/php/bugs/download.php \
	frontend/php/bugs/export.php \
	frontend/php/bugs/index.php \
	frontend/php/bugs/reporting.php \
	frontend/php/bugs/admin/field_values.php \
	frontend/php/bugs/admin/field_values_transition-ofields-update.php \
	frontend/php/bugs/admin/field_usage.php \
	frontend/php/bugs/admin/field_values_reset.php \
	frontend/php/bugs/admin/conf-copy.php \
	frontend/php/bugs/admin/index.php \
	frontend/php/bugs/admin/userperms.php \
	frontend/php/bugs/admin/other_settings.php \
	frontend/php/bugs/admin/editqueryforms.php \
	frontend/php/bugs/admin/notification_settings.php \
	frontend/php/testing/error.php \
	frontend/php/testing/sane.php \
	frontend/php/testing/markup.php \
	frontend/php/testing/format_date.php \
	frontend/php/testing/index.php \
	frontend/php/testing/test.php \
	frontend/php/testing/utils.php \
	frontend/php/file.php \
	frontend/php/stats/index.php \
	frontend/php/people/resume.php \
	frontend/php/people/editjob.php \
	frontend/php/people/index.php \
	frontend/php/people/createjob.php \
	frontend/php/people/admin/index.php \
	frontend/php/people/viewjob.php \
	frontend/php/people/viewgpg.php \
	frontend/php/projects.php \
	frontend/php/i18n.php \
	frontend/php/files/index.php \
	frontend/php/captcha.php \
	frontend/php/search/index.php \
	frontend/php/account/lostpw-confirm.php \
	frontend/php/account/login.php \
	frontend/php/account/lostpw.php \
	frontend/php/account/lostlogin.php \
	frontend/php/account/logout.php \
	frontend/php/account/index.php \
	frontend/php/account/first.php \
	frontend/php/account/verify.php \
	frontend/php/account/pending-resend.php \
	frontend/php/account/register.php \
	frontend/php/account/impersonate.php \
	frontend/php/account/su.php \
	frontend/php/mail/index.php \
	frontend/php/mail/admin/index.php \
	frontend/php/register/index.php \
	frontend/php/register/requirements.php \
	frontend/php/register/upload.php \
	frontend/php/contact.php \
	frontend/php/index.php \
	frontend/php/cvs/index.php \
	frontend/php/cvs/admin/index.php \
	frontend/php/siteadmin/triggercreation.php \
	frontend/php/siteadmin/grouplist.php \
	frontend/php/siteadmin/group_type.php \
	frontend/php/siteadmin/retestconfig.php \
	frontend/php/siteadmin/lastlogins.php \
	frontend/php/siteadmin/user_changepw.php \
	frontend/php/siteadmin/index.php \
	frontend/php/siteadmin/mailman.php \
	frontend/php/siteadmin/spamlist.php \
	frontend/php/siteadmin/groupedit.php \
	frontend/php/siteadmin/userlist.php \
	frontend/php/siteadmin/usergroup.php \
	frontend/php/testconfig.php \
	frontend/php/vcs/index.php \
	frontend/php/my/groups.php \
	frontend/php/my/quitproject.php \
	frontend/php/my/bookmarks.php \
	frontend/php/my/items.php \
	frontend/php/my/index.php \
	frontend/php/my/admin/resume.php \
	frontend/php/my/admin/cc.php \
	frontend/php/my/admin/change.php \
	frontend/php/my/admin/editsshkeys.php \
	frontend/php/my/admin/change_notifications.php \
	frontend/php/my/admin/sessions.php \
	frontend/php/my/admin/index.php \
	frontend/php/my/votes.php \
	frontend/php/include/error.php \
	frontend/php/include/gpg.php \
	frontend/php/include/context.php \
	frontend/php/include/features_boxes.php \
	frontend/php/include/theme.php \
	frontend/php/include/account.php \
	frontend/php/include/mailman.php \
	frontend/php/include/parsemail.php \
	frontend/php/include/trackers_run/mod.php \
	frontend/php/include/trackers_run/search.php \
	frontend/php/include/trackers_run/add.php \
	frontend/php/include/trackers_run/browse.php \
	frontend/php/include/trackers_run/digest.php \
	frontend/php/include/http.php \
	frontend/php/include/member.php \
	frontend/php/include/sane.php \
	frontend/php/include/savane-git.php \
	frontend/php/include/stats/general.php \
	frontend/php/include/people/general.php \
	frontend/php/include/vcs.php \
	frontend/php/include/vcs/git.php \
	frontend/php/include/graphs.php \
	frontend/php/include/tracker-alias-redirect.php \
	frontend/php/include/trackers/format.php \
	frontend/php/include/trackers/cookbook.php \
	frontend/php/include/trackers/general.php \
	frontend/php/include/trackers/show.php \
	frontend/php/include/trackers/data.php \
	frontend/php/include/trackers/transition.php \
	frontend/php/include/trackers/votes.php \
	frontend/php/include/trackers/view-dependencies.php \
	frontend/php/include/trackers/conf.php \
	frontend/php/include/i18n.php \
	frontend/php/include/search/general.php \
	frontend/php/include/markup.php \
	frontend/php/include/init.php \
	frontend/php/include/utils.php \
	frontend/php/include/pwqcheck.php \
	frontend/php/include/exit.php \
	frontend/php/include/database.php \
	frontend/php/include/layout.php \
	frontend/php/include/session.php \
	frontend/php/include/proj_email.php \
	frontend/php/include/gpl-quick-form.php \
	frontend/php/include/timezones.php \
	frontend/php/include/html.php \
	frontend/php/include/sendmail.php \
	frontend/php/include/user.php \
	frontend/php/include/my/admin/general.php \
	frontend/php/include/my/general.php \
	frontend/php/include/my/bookmarks.php \
	frontend/php/include/project/admin.php \
	frontend/php/include/pagemenu.php \
	frontend/php/include/news/forum.php \
	frontend/php/include/news/general.php \
	frontend/php/include/sitemenu.php \
	frontend/php/include/form.php \
	frontend/php/include/form-check.php \
	frontend/php/include/vars.php \
	frontend/php/include/calendar.php \
	frontend/php/include/group.php \
	frontend/php/include/savane_error.php \
	frontend/php/include/random-bytes.php \
	frontend/php/include/spam.php \
	frontend/php/include/project_home.php \
	frontend/php/project/memberlist-gpgkeys.php \
	frontend/php/project/memberlist.php \
	frontend/php/project/search.php \
	frontend/php/project/index.php \
	frontend/php/project/admin/editgroupnotifications.php \
	frontend/php/project/admin/editgroupinfo.php \
	frontend/php/project/admin/squadadmin.php \
	frontend/php/project/admin/conf-copy.php \
	frontend/php/project/admin/index.php \
	frontend/php/project/admin/useradmin.php \
	frontend/php/project/admin/userperms.php \
	frontend/php/project/admin/editgroupfeatures.php \
	frontend/php/project/admin/history.php \
	frontend/php/forum/forum.php \
	frontend/php/forum/message.php \
	frontend/php/404-export.php \
	frontend/php/news/submit.php \
	frontend/php/news/atom.php \
	frontend/php/news/index.php \
	frontend/php/news/admin/index.php \
	frontend/php/news/approve.php \
	frontend/php/users.php \
	frontend/php/css/graph-widths.php \
	frontend/php/markup-test.php \
	frontend/php/js/hide-span.php \
	frontend/php/js/captcha.php \
	frontend/php/js/show-hide.php \
	frontend/php/js/hide-feedback.php \
	frontend/php/js/show-feedback.php \
	frontend/php/sendmessage.php \
	frontend/php/404.php \
	frontend/site-specific/gnu/fingerprints.php \
	frontend/site-specific/gnu/git/index.php \
	frontend/site-specific/gnu/svn/index.php \
	frontend/site-specific/gnu/people/editjob.php \
	frontend/site-specific/gnu/people/index.php \
	frontend/site-specific/gnu/people/editresume.php \
	frontend/site-specific/gnu/people/index_group.php \
	frontend/site-specific/gnu/people/createjob.php \
	frontend/site-specific/gnu/forbidden_theme.php \
	frontend/site-specific/gnu/hashes.php \
	frontend/site-specific/gnu/account/index_ssh.php \
	frontend/site-specific/gnu/account/index_gpg.php \
	frontend/site-specific/gnu/account/editsshkeys.php \
	frontend/site-specific/gnu/account/first.php \
	frontend/site-specific/gnu/mail/about_list_creation.php \
	frontend/site-specific/gnu/register/confirmation_mail.php \
	frontend/site-specific/gnu/register/index.php \
	frontend/site-specific/gnu/register/confirmation.php \
	frontend/site-specific/gnu/register/requirements.php \
	frontend/site-specific/gnu/page_footer.php \
	frontend/site-specific/gnu/contact.php \
	frontend/site-specific/gnu/forbidden_group_names.php \
	frontend/site-specific/gnu/cvs/index.php \
	frontend/site-specific/gnu/hg/index.php \
	frontend/site-specific/gnu/my/groups.php \
	frontend/site-specific/gnu/my/request_for_inclusion.php \
	frontend/site-specific/gnu/project/admin/index_misc.php \
	frontend/site-specific/gnu/menu.php \
	frontend/site-specific/gnu/gpg-sample.php \
	frontend/site-specific/gnu/page_header.php \
	frontend/site-specific/gnu/arch/index.php \
	frontend/site-specific/gnu/homepage.php \
	frontend/site-specific/gnu/bzr/index.php \
	frontend/site-specific/gnu/admin/groupedit_grouptype.php \
	frontend/site-specific/gnu/admin/groupedit_intro.php \
	frontend/site-specific/gnu/admin/proj_email.php \
	frontend/site-specific/gnu/forbidden_mail_domains.php

# Usually the message domain is the same as the package name.
DOMAIN = savane

# These options get passed to xgettext.
XGETTEXT_OPTIONS =

# This is the copyright holder that gets inserted into the header of the
# $(DOMAIN).pot file.  Set this to the copyright holder of the surrounding
# package.  (Note that the msgstr strings, extracted from the package's
# sources, belong to the copyright holder of the package.)  Translators are
# expected to transfer the copyright for their translations to this person
# or entity, or to disclaim their copyright.  The empty string stands for
# the public domain; in this case the translators are expected to disclaim
# their copyright.
COPYRIGHT_HOLDER = Savane developers

# This tells whether or not to prepend "GNU " prefix to the package
# name that gets inserted into the header of the $(DOMAIN).pot file.
# Possible values are "yes", "no", or empty.  If it is empty, try to
# detect it automatically by scanning the files in $(top_srcdir) for
# "GNU packagename" string.
PACKAGE_GNU = no

# This is the email address or URL to which the translators shall report
# bugs in the untranslated strings:
# - Strings which are not entire sentences, see the maintainer guidelines
#   in the GNU gettext documentation, section 'Preparing Strings'.
# - Strings which use unclear terms or require additional context to be
#   understood.
# - Strings which make invalid assumptions about notation of date, time or
#   money.
# - Pluralisation problems.
# - Incorrect English spelling.
# - Incorrect formatting.
# It can be your email address, or a mailing list address where translators
# can write to without being subscribed, or the URL of a web page through
# which the translators can contact you.
MSGID_BUGS_ADDRESS = savannah-hackers-public@gnu.org

# This is the list of locale categories, beyond LC_MESSAGES, for which the
# message catalogs shall be used.  It is usually empty.
EXTRA_LOCALE_CATEGORIES =

# These options get passed to msgmerge.
# Useful options are in particular:
#   --previous            to keep previous msgids of translated messages,
#   --quiet               to reduce the verbosity.
MSGMERGE_OPTIONS = --previous

# These options get passed to msginit.
# If you want to disable line wrapping when writing PO files, add
# --no-wrap to MSGMERGE_OPTIONS, XGETTEXT_OPTIONS, and
# MSGINIT_OPTIONS.
MSGINIT_OPTIONS =

MSGMERGE = @MSGMERGE@
MSGMERGE_UPDATE = @MSGMERGE@ --update --backup=none
MSGMERGE_FOR_MSGFMT_OPTION = @MSGMERGE_FOR_MSGFMT_OPTION@
MSGINIT = msginit
MSGCONV = msgconv
MSGFILTER = msgfilter

# This is computed as $(foreach file, $(POTFILES), $(top_srcdir)/$(file))
POTFILES_DEPS = @POTFILES_DEPS@

# This is computed as $(foreach lang, $(LINGUAS), $(srcdir)/$(lang).po)
POFILES = @POFILES@
# This is computed as $(foreach lang, $(LINGUAS), $(srcdir)/$(lang).gmo)
GMOFILES = @GMOFILES@
# This is computed as $(foreach lang, $(LINGUAS), $(lang).po-update)
UPDATEPOFILES = @UPDATEPOFILES@
# This is computed as $(foreach lang, $(LINGUAS), $(lang).nop)
DUMMYPOFILES = @DUMMYPOFILES@

# This is computed as
# $(foreach lang, user-specified subset of $(LINGUAS), $(lang).gmo)
CATALOGS = @CATALOGS@

SUFFIXES = .po .gmo .sed .sin .nop .po-create .po-update

# The .pot file, stamp-po, .po files, and .gmo files appear in release tarballs.
# The GNU Coding Standards say in
# <https://www.gnu.org/prep/standards/html_node/Makefile-Basics.html>:
#   "GNU distributions usually contain some files which are not source files
#    ... . Since these files normally appear in the source directory, they
#    should always appear in the source directory, not in the build directory.
#    So Makefile rules to update them should put the updated files in the
#    source directory."
# Therefore we put these files in the source directory, not the build directory.

# During .po -> .gmo conversion, take into account the most recent changes to
# the .pot file. This eliminates the need to update the .po files when the
# .pot file has changed, which would be troublesome if the .po files are put
# under version control.
$(GMOFILES): $(srcdir)/$(DOMAIN).pot
.po.gmo: ; \
  lang=`echo $* | ${SED} -e 's,.*/,,'`; \
  rm -f $${lang}.gmo \
    && $(MSGMERGE) $(MSGMERGE_FOR_MSGFMT_OPTION) \
        -o $${lang}.1po $(srcdir)/$${lang}.po $(srcdir)/$(DOMAIN).pot \
    && $(GMSGFMT) -c --statistics --verbose -o t-$${lang}.gmo $${lang}.1po \
    && mv t-$${lang}.gmo $${lang}.gmo && rm -f $${lang}.1po

.sin.sed:
	${SED} -e '/^#/d' $< > t-$@
	mv t-$@ $@


all-local: all-local-@USE_NLS@

all-local-yes: $(srcdir)/stamp-po
all-local-no:

# $(srcdir)/$(DOMAIN).pot is only created when needed. When xgettext finds no
# internationalized messages, no $(srcdir)/$(DOMAIN).pot is created (because
# we don't want to bother translators with empty POT files). We assume that
# LINGUAS is empty in this case, i.e. $(POFILES) and $(GMOFILES) are empty.
# In this case, $(srcdir)/stamp-po is a nop (i.e. a phony target).

# $(srcdir)/stamp-po is a timestamp denoting the last time at which the CATALOGS
# have been loosely updated. Its purpose is that when a developer or translator
# checks out the package from a version control system, and the $(DOMAIN).pot
# file is not under version control, "make" will update the $(DOMAIN).pot and
# the $(CATALOGS), but subsequent invocations of "make" will do nothing. This
# timestamp would not be necessary if updating the $(CATALOGS) would always
# touch them; however, the rule for $(POFILES) has been designed to not touch
# files that don't need to be changed.
$(srcdir)/stamp-po: $(srcdir)/$(DOMAIN).pot
	test ! -f $(srcdir)/$(DOMAIN).pot || \
	  test -z "$(GMOFILES)" || $(MAKE) $(GMOFILES)
	@test ! -f $(srcdir)/$(DOMAIN).pot || { \
	  echo "touch $(srcdir)/stamp-po" && \
	  echo timestamp > $(srcdir)/stamp-poT && \
	  mv $(srcdir)/stamp-poT $(srcdir)/stamp-po; \
	}

# Note: Target 'all' must not depend on target '$(DOMAIN).pot-update',
# otherwise packages like GCC can not be built if only parts of the source
# have been downloaded.

# This target rebuilds $(DOMAIN).pot; it is an expensive operation.
# Note that $(DOMAIN).pot is not touched if it doesn't need to be changed.
# The determination of whether the package xyz is a GNU one is based on the
# heuristic whether some file in the top level directory mentions "GNU xyz".
# If GNU 'find' is available, we avoid grepping through monster files.
$(DOMAIN).pot-update: $(POTFILES_DEPS) remove-potcdate.sed
	package_gnu="$(PACKAGE_GNU)"; \
	test -n "$$package_gnu" || { \
	  if { if (LC_ALL=C find --version) 2>/dev/null \
	           | grep GNU >/dev/null; then \
	         LC_ALL=C find -L $(top_srcdir) -maxdepth 1 -type f \
	           -size -10000000c \
	           -exec grep -i 'GNU $(PACKAGE)' /dev/null '{}' ';' \
	           2>/dev/null; \
	       else \
	         LC_ALL=C grep -i 'GNU $(PACKAGE)' $(top_srcdir)/* 2>/dev/null; \
	       fi; \
	     } | grep -v 'libtool:' >/dev/null; then \
	     package_gnu=yes; \
	   else \
	     package_gnu=no; \
	   fi; \
	}; \
	if test "$$package_gnu" = "yes"; then \
	  package_prefix='GNU '; \
	else \
	  package_prefix=''; \
	fi; \
	if test -n '$(MSGID_BUGS_ADDRESS)'; then \
	  msgid_bugs_address='$(MSGID_BUGS_ADDRESS)'; \
	else \
	  msgid_bugs_address='$(PACKAGE_BUGREPORT)'; \
	fi; \
	case `$(XGETTEXT) --version | ${SED} 1q | ${SED} -e 's,^[^0-9]*,,'` in \
	  '' | 0.[0-9] | 0.[0-9].* | 0.1[0-5] | 0.1[0-5].* | 0.16 | 0.16.[0-1]*) \
	    $(XGETTEXT) --default-domain=$(DOMAIN) --directory=$(top_srcdir) \
	      --add-comments=TRANSLATORS: \
	      --copyright-holder='$(COPYRIGHT_HOLDER)' \
	      --msgid-bugs-address="$$msgid_bugs_address" \
	      $(XGETTEXT_OPTIONS) @XGETTEXT_EXTRA_OPTIONS@ \
	      $(POTFILES) \
	    ;; \
	  *) \
	    $(XGETTEXT) --default-domain=$(DOMAIN) --directory=$(top_srcdir) \
	      --add-comments=TRANSLATORS: \
	      --copyright-holder='$(COPYRIGHT_HOLDER)' \
	      --package-name="$${package_prefix}$(PACKAGE)" \
	      --package-version='$(VERSION)' \
	      --msgid-bugs-address="$$msgid_bugs_address" \
	      $(XGETTEXT_OPTIONS) @XGETTEXT_EXTRA_OPTIONS@ \
	      $(POTFILES) \
	    ;; \
	esac
	test ! -f $(DOMAIN).po || { \
	  if test -f $(srcdir)/$(DOMAIN).pot-header; then \
	    ${SED} -e '1,/^#$$/d' < $(DOMAIN).po > $(DOMAIN).1po && \
	    cat $(srcdir)/$(DOMAIN).pot-header $(DOMAIN).1po > $(DOMAIN).po && \
	    rm -f $(DOMAIN).1po \
	    || exit 1; \
	  fi; \
	  if test -f $(srcdir)/$(DOMAIN).pot; then \
	    ${SED} -f remove-potcdate.sed < $(srcdir)/$(DOMAIN).pot \
	      > $(DOMAIN).1po && \
	    ${SED} -f remove-potcdate.sed < $(DOMAIN).po > $(DOMAIN).2po && \
	    if cmp $(DOMAIN).1po $(DOMAIN).2po >/dev/null 2>&1; then \
	      rm -f $(DOMAIN).1po $(DOMAIN).2po $(DOMAIN).po; \
	    else \
	      rm -f $(DOMAIN).1po $(DOMAIN).2po $(srcdir)/$(DOMAIN).pot && \
	      mv $(DOMAIN).po $(srcdir)/$(DOMAIN).pot; \
	    fi; \
	  else \
	    mv $(DOMAIN).po $(srcdir)/$(DOMAIN).pot; \
	  fi; \
	}

# This rule has no dependencies: we don't need to update $(DOMAIN).pot at
# every "make" invocation, only create it when it is missing.
# Only "make $(DOMAIN).pot-update" or "make dist" will force an update.
$(srcdir)/$(DOMAIN).pot:
	$(MAKE) $(DOMAIN).pot-update

# This target rebuilds a PO file if $(DOMAIN).pot has changed.
# Note that a PO file is not touched if it doesn't need to be changed.
$(POFILES): $(srcdir)/$(DOMAIN).pot
	@lang=`echo $@ | ${SED} -e 's,.*/,,' -e 's/\.po$$//'`; \
	if test -f "$(srcdir)/$${lang}.po"; then \
	  test "$(srcdir)" = . && cdcmd="" || cdcmd="cd $(srcdir) && "; \
	  cmd_start="$(MSGMERGE_UPDATE) $(MSGMERGE_OPTIONS)"; \
	  cmd_tail="--lang=$${lang} --previous $${lang}.po $(DOMAIN).pot"; \
	  echo "$${cdcmd}$${cmd_start} $${cmd_tail}"; \
	  cd $(srcdir) \
	    && { case `$(MSGMERGE_UPDATE) --version \
	                | ${SED} 1q | ${SED} -e 's,^[^0-9]*,,'` in \
	           '' | 0.[0-9] | 0.[0-9].* | 0.1[0-5] | 0.1[0-5].*) \
	             $${cmd_start} $${lang}.po $(DOMAIN).pot;; \
	           0.1[6-7] | 0.1[6-7].*) \
	             $${cmd_start} --previous $${lang}.po $(DOMAIN).pot;; \
	           *) \
	             $${cmd_start} $${cmd_tail};; \
	         esac; \
	       }; \
	else \
	  $(MAKE) $${lang}.po-create; \
	fi


install-data-local: install-data-local-@USE_NLS@
install-data-local-no: all-local
install-data-local-yes: all-local
	@catalogs='$(CATALOGS)'; \
	for cat in $$catalogs; do \
	  cat=`echo " $$cat" | ${SED} 's,.*/,,;s,^ ,,'`; \
	  lang=`echo $$cat | ${SED} -e 's/\.gmo$$//'`; \
	  dir=$(localedir)/$$lang/LC_MESSAGES; \
	  $(MKDIR_P) $(DESTDIR)$$dir; \
	  if test -r $$cat; then realcat=$$cat; else realcat=$(srcdir)/$$cat; fi; \
	  $(INSTALL_DATA) $$realcat $(DESTDIR)$$dir/$(DOMAIN).mo; \
	  echo "installing $$realcat as $(DESTDIR)$$dir/$(DOMAIN).mo"; \
	  for lc in '' $(EXTRA_LOCALE_CATEGORIES); do \
	    if test -n "$$lc"; then \
	      if (cd $(DESTDIR)$(localedir)/$$lang \
	            && LC_ALL=C ls -l -d $$lc 2>/dev/null) \
	          | grep ' -> ' >/dev/null; then \
	        link=`cd $(DESTDIR)$(localedir)/$$lang \
	          && LC_ALL=C ls -l -d $$lc | ${SED} -e 's/^.* -> //'`; \
	        mv $(DESTDIR)$(localedir)/$$lang/$$lc \
	          $(DESTDIR)$(localedir)/$$lang/$$lc.old; \
	        mkdir $(DESTDIR)$(localedir)/$$lang/$$lc; \
	        (cd $(DESTDIR)$(localedir)/$$lang/$$lc.old && \
	         for file in *; do \
	           if test -f $$file; then \
	             ln -s ../$$link/$$file \
	               $(DESTDIR)$(localedir)/$$lang/$$lc/$$file; \
	           fi; \
	         done); \
	        rm -f $(DESTDIR)$(localedir)/$$lang/$$lc.old; \
	      else \
	        if test -d $(DESTDIR)$(localedir)/$$lang/$$lc; then \
	          :; \
	        else \
	          rm -f $(DESTDIR)$(localedir)/$$lang/$$lc; \
	          mkdir $(DESTDIR)$(localedir)/$$lang/$$lc; \
	        fi; \
	      fi; \
	      rm -f $(DESTDIR)$(localedir)/$$lang/$$lc/$(DOMAIN).mo; \
	      ln -s ../LC_MESSAGES/$(DOMAIN).mo \
	        $(DESTDIR)$(localedir)/$$lang/$$lc/$(DOMAIN).mo 2>/dev/null \
	      || ln $(DESTDIR)$(localedir)/$$lang/LC_MESSAGES/$(DOMAIN).mo \
	        $(DESTDIR)$(localedir)/$$lang/$$lc/$(DOMAIN).mo 2>/dev/null \
	      || cp -p $(DESTDIR)$(localedir)/$$lang/LC_MESSAGES/$(DOMAIN).mo \
	        $(DESTDIR)$(localedir)/$$lang/$$lc/$(DOMAIN).mo; \
	      link_target="$(DESTDIR)$(localedir)/$$lang/$$lc/$(DOMAIN).mo"; \
	      echo "installing $$realcat link as $${link_target}"; \
	    fi; \
	  done; \
	done

installdirs-local: installdirs-local-@USE_NLS@
installdirs-local-no:
installdirs-local-yes:
	@catalogs='$(CATALOGS)'; \
	for cat in $$catalogs; do \
	  cat=`echo " $$cat" | ${SED} 's,.*/,,;s,^ ,,'`; \
	  lang=`echo $$cat | ${SED} -e 's/\.gmo$$//'`; \
	  dir=$(localedir)/$$lang/LC_MESSAGES; \
	  $(MKDIR_P) $(DESTDIR)$$dir; \
	  for lc in '' $(EXTRA_LOCALE_CATEGORIES); do \
	    if test -n "$$lc"; then \
	      if (cd $(DESTDIR)$(localedir)/$$lang \
	          && LC_ALL=C ls -l -d $$lc 2>/dev/null) \
	        | grep ' -> ' >/dev/null; then \
	        link=`cd $(DESTDIR)$(localedir)/$$lang \
	          && LC_ALL=C ls -l -d $$lc | ${SED} -e 's/^.* -> //'`; \
	        mv $(DESTDIR)$(localedir)/$$lang/$$lc \
	          $(DESTDIR)$(localedir)/$$lang/$$lc.old; \
	        mkdir $(DESTDIR)$(localedir)/$$lang/$$lc; \
	        (cd $(DESTDIR)$(localedir)/$$lang/$$lc.old && \
	         for file in *; do \
	           if test -f $$file; then \
	             ln -s ../$$link/$$file \
	               $(DESTDIR)$(localedir)/$$lang/$$lc/$$file; \
	           fi; \
	         done); \
	        rm -f $(DESTDIR)$(localedir)/$$lang/$$lc.old; \
	      else \
	        if test -d $(DESTDIR)$(localedir)/$$lang/$$lc; then \
	          :; \
	        else \
	          rm -f $(DESTDIR)$(localedir)/$$lang/$$lc; \
	          mkdir $(DESTDIR)$(localedir)/$$lang/$$lc; \
	        fi; \
	      fi; \
	    fi; \
	  done; \
	done

uninstall-local: uninstall-local-@USE_NLS@
uninstall-local-no:
uninstall-local-yes:
	catalogs='$(CATALOGS)'; \
	for cat in $$catalogs; do \
	  cat=`echo " $$cat" | ${SED} 's,.*/,,;s,^ ,,'`; \
	  lang=`echo $$cat | ${SED} -e 's/\.gmo$$//'`; \
	  for lc in LC_MESSAGES $(EXTRA_LOCALE_CATEGORIES); do \
	    rm -f $(DESTDIR)$(localedir)/$$lang/$$lc/$(DOMAIN).mo; \
	  done; \
	done

html ID:

CLEANFILES = $(GMOFILES)

MOSTLYCLEANFILES =
MOSTLYCLEANFILES += remove-potcdate.sed
MOSTLYCLEANFILES += stamp-poT
MOSTLYCLEANFILES += core core.* *.stackdump $(DOMAIN).po $(DOMAIN).1po $(DOMAIN).2po *.new.po
MOSTLYCLEANFILES += *.o

MAINTAINERCLEANFILES = $(DOMAIN).pot stamp-po

EXTRA_DIST = remove-potcdate.sin LINGUAS $(POFILES)

# Hidden from automake, but really activated. Works around an automake-1.5 bug.
#distdir: distdir1
distdir1:
	$(MAKE) update-po
	$(MAKE) $(srcdir)/stamp-po
	@if test -f $(srcdir)/$(DOMAIN).pot; then \
	  for file in $(DOMAIN).pot stamp-po; do \
	    if test -f $$file; then d=.; else d=$(srcdir); fi; \
	    cp -p $$d/$$file $(distdir)/$$file || exit 1; \
	  done; \
	else \
	  case $(XGETTEXT) in \
	    :) echo "Warning: Creating a tarball without '$(DOMAIN).pot', " \
              "because a suitable 'xgettext' program was not found in PATH." \
	      1>&2;; \
	    *) echo "Warning: Creating a tarball without '$(DOMAIN).pot', " \
	       "because 'xgettext' found no strings to extract." \
	       "Check the POTFILES and the XGETTEXT_OPTIONS" \
	       "in the Makefile.am file." 1>&2;; \
	  esac; \
	fi

update-po: Makefile
	$(MAKE) $(DOMAIN).pot-update
	test -z "$(UPDATEPOFILES)" || $(MAKE) $(UPDATEPOFILES)
	$(MAKE) update-gmo

# General rule for creating PO files.

.nop.po-create:
	@lang=`echo $@ | ${SED} -e 's/\.po-create$$//'`; \
	echo "File $$lang.po does not exist. " \
	  "If you are a translator, you can create it through 'msginit'." \
	  1>&2; \
	exit 1

# General rule for updating PO files.

.nop.po-update:
	@lang=`echo $@ | ${SED} -e 's/\.po-update$$//'`; \
	tmpdir=`pwd`; \
	echo "$$lang:"; \
	test "$(srcdir)" = . && cdcmd="" || cdcmd="cd $(srcdir) && "; \
	cmd_start="$(MSGMERGE) $(MSGMERGE_OPTIONS)"; \
	cmd_tail="--lang=$${lang} --previous $${lang}.po $(DOMAIN).pot"; \
	echo "$${cdcmd}$${cmd_start} $${cmd_tail} -o $$lang.new.po"; \
	cmd_tail="-o $$tmpdir/$$lang.new.po  $${lang}.po $(DOMAIN).pot"; \
	cd $(srcdir); \
	if { case `$(MSGMERGE) --version | ${SED} 1q \
	         | ${SED} -e 's,^[^0-9]*,,'` in \
	       '' | 0.[0-9] | 0.[0-9].* | 0.1[0-5] | 0.1[0-5].*) \
	         $${cmd_start} $${cmd_tail};; \
	       0.1[6-7] | 0.1[6-7].*) \
	         $${cmd_start} --previous $${cmd_tail};; \
	       *) \
	         $${cmd_start} --lang=$$lang --previous $${cmd_tail};; \
	     esac; \
	   }; then \
	  if cmp $$lang.po $$tmpdir/$$lang.new.po >/dev/null 2>&1; then \
	    rm -f $$tmpdir/$$lang.new.po; \
	  else \
	    if mv -f $$tmpdir/$$lang.new.po $$lang.po; then \
	      :; \
	    else \
	      echo "msgmerge for $$lang.po failed: cannot move" \
	        "$$tmpdir/$$lang.new.po to $$lang.po" 1>&2; \
	      exit 1; \
	    fi; \
	  fi; \
	else \
	  echo "msgmerge for $$lang.po failed!" 1>&2; \
	  rm -f $$tmpdir/$$lang.new.po; \
	fi

$(DUMMYPOFILES):

update-gmo: Makefile $(GMOFILES)
	@:

dist-hook:
	if ! test -h "$(top_srcdir)/frontend/php/patch"; then \
	  echo; \
	  echo Symlinks were dereferenced in the source tree';' \
	    skipping the check for POTFILES.; \
	  echo; \
	  exit 0; \
	fi; \
	pot_regexp=`echo $(POTFILES) | ${SED} 's,  *,|,g;s,^|*,^(,;s,|*$$,)$$,'`; \
	files=`cd $(top_srcdir) \
	  && find frontend/ -type f -name '*.php' ! -name ac_config.php`; \
	files=`cd $(top_srcdir); ls $$files`; \
	missing_potfiles=`echo "$$files" | grep -E -v $$pot_regexp`; \
	if test "x$$missing_potfiles" != x; then \
	  echo "The POTFILES variable misses these files: $$missing_potfiles" \
	    1>&2; \
	  exit 1; \
	fi
