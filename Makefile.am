# Based on gettext-0.16.1/gettext-tools/examples/hello-php.
# Public domain.

# General automake options.
AUTOMAKE_OPTIONS = gnits
ACLOCAL_AMFLAGS = -I autotools/m4

# The list of subdirectories containing Makefiles.
SUBDIRS = etc frontend po

backend_bin_scripts = \
  backend/accounts/sv_assign_uid_gid \
  backend/accounts/sv_get_authorized_keys \
  backend/accounts/sv_groups \
  backend/accounts/sv_membersh \
  backend/extra/git/sv_cgit.pl \
  backend/extra/git/sv_cgit_about_filter.pl \
  backend/extra/git/sv_git_gc_repos.pl \
  backend/mail/sv_aliases \
  backend/mail/sv_mailman-wrapper.pl \
  backend/misc/sv_cleaner \
  backend/misc/sv_reminder

backend_sbin_scripts = backend/sbin/sv_frontendctl.pl

backend_scripts = $(backend_bin_scripts) $(backend_sbin_scripts)

conf_files = apache.conf savane.conf.pl membersh-conf.pl savane.conf.php cgitrepos

directories = backend/accounts backend/sbin backend/extra/git backend/mail \
  backend/misc sysconf lib/Savane

modules = \
  lib/Savane.pm \
  lib/Savane/Bzr.pm \
  lib/Savane/Conf.pm \
  lib/Savane/Cvs.pm \
  lib/Savane/DB.pm \
  lib/Savane/Download.pm \
  lib/Savane/Git.pm \
  lib/Savane/Groups.pm \
  lib/Savane/Hg.pm \
  lib/Savane/Locks.pm \
  lib/Savane/Mail.pm \
  lib/Savane/Svn.pm \
  lib/Savane/User.pm \
  lib/Savane/Util.pm \
  lib/Savane/Version.pm

bin_SCRIPTS = $(backend_bin_scripts)
sbin_SCRIPTS = $(backend_sbin_scripts)
nobase_pkgdata_DATA = $(modules)
confdir = $(sysconfdir)/$(PACKAGE)

subst_in_files = \
  ${SED} \
    -e 's,[@]PERL[@],$(PERL),' \
    -e 's,[@]PACKAGE[@],$(PACKAGE),' \
    -e 's,[@]VERSION[@],$(VERSION),' \
    -e 's,[@]SV_CONF[@],$(SV_CONF),' \
    -e 's,[@]WWW_SERVER_BIN[@],$(WWW_SERVER_BIN),' \
    -e 's,[@]WWW_SERVER_GROUP[@],$(WWW_SERVER_GROUP),' \
    -e 's,[@]WWW_SERVER_HOST[@],$(WWW_SERVER_HOST),' \
    -e 's,[@]WWW_SERVER_PORT[@],$(WWW_SERVER_PORT),' \
    -e 's,[@]WWW_SERVER_USER[@],$(WWW_SERVER_USER),' \
    -e 's,[@]SV_DB_HOST[@],$(SV_DB_HOST),' \
    -e 's,[@]SV_DB_NAME[@],$(SV_DB_NAME),' \
    -e 's,[@]SV_DB_PASSWORD[@],$(SV_DB_PASSWORD),' \
    -e 's,[@]SV_DB_USER[@],$(SV_DB_USER),' \
    -e 's,[@]localstatedir[@],$(localstatedir),' \
    -e 's,[@]pkgdatadir[@],$(pkgdatadir),' \
    -e 's,[@]runstatedir[@],$(runstatedir),' \
    -e 's,[@]sysconfdir[@],$(sysconfdir),'

substituted = \
  $(backend_scripts) $(conf_files:%=sysconf/%) $(modules)

dirs.stamp: ; $(MKDIR_P) $(directories) && touch $@

$(substituted): %: %.in dirs.stamp; $(subst_in_files) < $< > $@

# Force trivial cgitrepos for the time being.
cgirepos: ; echo -n > $@

EXTRA_DIST = bootstrap AUTHORS.Savannah AUTHORS.SourceForge \
  db tests $(substituted:%=%.in) backend/README autotools/README \
  caboose/copyright.awk caboose/fixup-notices.awk caboose/notices \
  caboose/parse-files.awk

CLEANFILES = $(substituted) dirs.stamp

dest_local = $(DESTDIR)$(localstatedir)/lib/$(PACKAGE)
dest_run = $(DESTDIR)$(runstatedir)/$(PACKAGE)
dirs_to_make = $(frontend_dirs) $(frontend_cache_dirs)

if HAVE_FRONTEND
frontend_dirs = $(dest_run)/apache $(dest_local)/apache
frontend_cache_dirs = $(dest_local)/submissions_uploads/trackers_attachments
endif # HAVE_FRONTEND

uninstall-hook:
	rm -fr $(frontend_dirs)
	rm -d $(frontend_cache_dirs) || true
	rm -d `echo $(frontend_cache_dirs) | ${DIRNAME}` || true

install-conf-link: ; \
  deprecated_conf="$(DESTDIR)$(confdir)/.savane.conf.php"; \
  new_conf="$(DESTDIR)$(confdir)/savane.conf.php"; \
  if test -e "$$deprecated_conf" -a ! -e "$$new_conf"; then \
    echo; echo; echo "NOTE: linking $$deprecated_conf to $$new_conf"; \
    echo; echo; $(LN_S) "$$deprecated_conf" "$$new_conf" || exit 1; \
  fi

install-conf-files: $(conf_files:%=sysconf/%) ; \
  for i in $(conf_files); do \
    if test -e "$(DESTDIR)$(confdir)/$$i"; then\
       echo; echo; \
       echo "NOTE: $(DESTDIR)$(confdir)/$$i exists;"; \
       echo "      not trying to overwrite it with a new configuration file."; \
       echo; echo; \
    else \
      $(INSTALL) -m 644 -D sysconf/$$i \
        "$(DESTDIR)$(confdir)/$$i"; \
    fi; \
  done

make-var-subdirs: ; $(MKDIR_P) $(dirs_to_make)
install-data-local: make-var-subdirs install-conf-link install-conf-files

uninstall-local: ; \
  for i in $(conf_files); do \
    $(RM) "$(DESTDIR)$(confdir)/$$i"; \
  done

distcheck-hook:
	if grep -q '^This is a stub' $(distdir)/ChangeLog; then \
	  echo; echo; \
	  echo "ChangeLog hasn't been generated.  " \
	    "Configure with --enable-changelog."; \
	  echo; echo; false; \
	fi
