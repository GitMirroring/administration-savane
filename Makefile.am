# Based on gettext-0.16.1/gettext-tools/examples/hello-php.
# Public domain.

# General automake options.
AUTOMAKE_OPTIONS = gnits
ACLOCAL_AMFLAGS = -I autotools/m4

# The list of subdirectories containing Makefiles.
SUBDIRS = etc frontend lib po local

backend_scripts = \
	backend/accounts/sv_assign_uid_gid \
	backend/accounts/sv_get_authorized_keys \
	backend/accounts/sv_groups \
	backend/accounts/sv_membersh \
	backend/extra/git/sv_cgit.pl \
	backend/extra/git/sv_cgit_about_filter.pl \
	backend/mail/sv_aliases \
	backend/mail/sv_mailman-wrapper.pl \
	backend/misc/sv_cleaner \
	backend/misc/sv_reminder

directories = backend/accounts backend/extra/git backend/mail backend/misc

bin_SCRIPTS = $(backend_scripts)

subst_in_scripts = \
	${SED} \
	-e 's,[@]PERL[@],$(PERL),' \
	-e 's,[@]pkgdatadir[@],$(pkgdatadir),' \
	-e 's,[@]sysconfdir[@],$(sysconfdir),'

$(backend_scripts): %: %.in
	$(MKDIR_P) $(directories)
	$(subst_in_scripts) < $^ > $@
	chmod +x $@

EXTRA_DIST = bootstrap AUTHORS.Savannah AUTHORS.SourceForge \
	db tests $(backend_scripts:%=%.in) \
	backend/README autotools/README \
	caboose/copyright.awk caboose/fixup-notices.awk caboose/notices \
	caboose/parse-files.awk

CLEANFILES = $(backend_scripts)

distcheck-hook:
	if grep -q '^This is a stub' $(distdir)/ChangeLog; then \
	  echo; echo; \
	  echo "ChangeLog hasn't been generated.  " \
	    "Configure with --enable-changelog."; \
	  echo; echo; false; \
	fi
