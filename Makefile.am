# Based on gettext-0.16.1/gettext-tools/examples/hello-php.
# Public domain.

# General automake options.
AUTOMAKE_OPTIONS = gnits
ACLOCAL_AMFLAGS = -I autotools/m4

# The list of subdirectories containing Makefiles.
SUBDIRS = etc frontend lib autotools/m4 po local

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

bin_SCRIPTS = $(backend_scripts)

subst_in_scripts = \
	sed \
	-e 's,[@]PERL[@],$(PERL),' \
	-e 's,[@]libexecdir[@],$(libexecdir),' \
	-e 's,[@]sysconfdir[@],$(sysconfdir),'

$(backend_scripts): %: %.in
	$(MKDIR_P) $(dir $@)
	$(subst_in_scripts) < $^ > $@
	chmod +x $@

EXTRA_DIST = bootstrap AUTHORS.Savannah AUTHORS.SourceForge \
	REQUIREMENTS db tests $(backend_scripts:%=%.in) \
	backend/README \
	caboose/copyright.awk caboose/fixup-notices.awk caboose/notices \
	caboose/parse-files.awk

CLEANFILES = $(backend_scripts)

dist-hook:
	find $(distdir) -type d -name '.svn' -o -name '*~' | xargs -r rm -rf

distcheck-hook:
	if grep -q '^This is a stub' $(distdir)/ChangeLog; then \
	  echo; echo; \
	  echo "ChangeLog hasn't been generated.  " \
	    "Configure with --enable-changelog."; \
	  echo; echo; false; \
	fi
