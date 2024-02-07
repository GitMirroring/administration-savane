# Based on gettext-0.16.1/gettext-tools/examples/hello-php.
# Public domain.

# General automake options.
AUTOMAKE_OPTIONS = gnits
ACLOCAL_AMFLAGS = -I autotools/m4

# The list of subdirectories containing Makefiles.
SUBDIRS = . frontend po

if HAVE_MIRROR
mirmon_bin = backend/misc/sv_gnu-mirmon backend/misc/sv_mirmon-to-geoip
endif

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
  backend/mail/sv_sendmail.pl \
  backend/misc/sv_cleaner \
  backend/misc/sv_reminder \
  $(mirmon_bin)

frontend_sbin = sv_frontendctl.pl sv_php_server.pl
frontend_php_built = frontend/php/include/ac_config.php frontend/local.php
if HAVE_DATABASE
db_sbin = sv_launch_db.pl sv_kill_db.pl
endif
backend_sbin = $(frontend_sbin) $(db_sbin)

backend_sbin_scripts = $(backend_sbin:%=backend/sbin/%)

backend_scripts = $(backend_bin_scripts) $(backend_sbin_scripts)

build_time_scripts = db/init-database.pl

conf_files = apache.conf savane.conf.pl membersh-conf.pl php.ini \
  savane.conf.php cgitrepos

directories = backend/accounts backend/sbin backend/extra/git backend/mail \
  backend/misc db sysconf lib/Savane frontend/php/include

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
noinst_SCRIPTS = $(build_time_scripts) $(frontend_php_built)
nobase_pkgdata_DATA = $(modules)
confdir = $(sysconfdir)/$(PACKAGE)
pkgstatedir = $(localstatedir)/lib/$(PACKAGE)
logdir = $(pkgstatedir)/log
rundir = $(runstatedir)/$(PACKAGE)
apache_logdir = $(logdir)/apache
php_log_file = sv.php-error.log
apache_rundir = $(rundir)/apache
apache_pid = $(apache_rundir)/pid
apache_lock = $(apache_rundir)/lock
frontenddir = $(pkgdatadir)/frontend
mysql_datadir = $(pkgstatedir)/mysqld
mysql_tmpdir = $(pkgstatedir)/tmp
mysql_secfile = $(mysql_tmpdir)/mysqld-priv
mysql_rundir = $(rundir)/mysqld
mysql_pid = $(mysql_rundir)/pid
mysql_socket = $(mysql_rundir)/sock
mysql_lock = $(mysql_rundir)/lock
if HAVE_SENDMAIL
  WWW_SENDMAIL_LINE =
  HAVE_SENDMAIL_VAR = 1
else
  WWW_SENDMAIL_LINE = php_admin_value sendmail_path "$(bindir)/sv_sendmail.pl"
  PHP_SENDMAIL_LINE = sendmail_path = "$(bindir)/sv_sendmail.pl"
  HAVE_SENDMAIL_VAR = 0
endif

subst_in_files = \
  ${SED} \
    -e 's,[@]PERL[@],$(PERL),g' \
    -e 's,[@]PACKAGE[@],$(PACKAGE),g' \
    -e 's,[@]PACKAGE_TARNAME[@],$(PACKAGE_TARNAME),g' \
    -e 's,[@]VERSION[@],$(VERSION),g' \
    -e 's,[@]SV_CONF[@],$(SV_CONF),g' \
    -e 's,[@]SV_GIT_COMMIT[@],$(SV_GIT_COMMIT),g' \
    -e 's,[@]WWW_SERVER_BIN[@],$(WWW_SERVER_BIN),g' \
    -e 's,[@]WWW_SERVER_GROUP[@],$(WWW_SERVER_GROUP),g' \
    -e 's,[@]WWW_SERVER_HOST[@],$(WWW_SERVER_HOST),g' \
    -e 's,[@]WWW_SERVER_PORT[@],$(WWW_SERVER_PORT),g' \
    -e 's,[@]WWW_SERVER_USER[@],$(WWW_SERVER_USER),g' \
    -e 's,[@]WWW_SENDMAIL_LINE[@],$(WWW_SENDMAIL_LINE),g' \
    -e 's,[@]PHP_SENDMAIL_LINE[@],$(PHP_SENDMAIL_LINE),g' \
    -e 's,[@]PHP[@],$(PHP),g' \
    -e 's,[@]HAVE_SENDMAIL[@],$(HAVE_SENDMAIL_VAR),g' \
    -e 's,[@]ADD_SAMPLE_DB[@],$(ADD_SAMPLE_DB),g' \
    -e 's,[@]GRAPHVIZ_BIN[@],$(GRAPHVIZ_BIN),g' \
    -e 's,[@]MYSQL_BIN[@],$(MYSQL_BIN),g' \
    -e 's,[@]MYSQLD_BIN[@],$(MYSQLD_BIN),g' \
    -e 's,[@]MYSQLD_SAFE_BIN[@],$(MYSQLD_SAFE_BIN),g' \
    -e 's,[@]MYSQL_INSTALLDB_BIN[@],$(MYSQL_INSTALLDB_BIN),g' \
    -e 's,[@]SV_DB_HOST[@],$(SV_DB_HOST),g' \
    -e 's,[@]SV_DB_PORT[@],$(SV_DB_PORT),g' \
    -e 's,[@]SV_DB_NETWORKING[@],$(SV_DB_NETWORKING),g' \
    -e 's,[@]SV_DB_NAME[@],$(SV_DB_NAME),g' \
    -e 's,[@]SV_DB_PASSWORD[@],$(SV_DB_PASSWORD),g' \
    -e 's,[@]SV_DB_USER[@],$(SV_DB_USER),g' \
    -e 's,[@]prefix[@],$(prefix),g' \
    -e 's,[@]exec_prefix[@],$(exec_prefix),g' \
    -e 's,[@]bindir[@],$(bindir),g' \
    -e 's,[@]datarootdir[@],$(datarootdir),g' \
    -e 's,[@]srcdir[@],$(srcdir),g' \
    -e 's,[@]localstatedir[@],$(localstatedir),g' \
    -e 's,[@]pkgdatadir[@],$(pkgdatadir),g' \
    -e 's,[@]pkgstatedir[@],$(pkgstatedir),g' \
    -e 's,[@]runstatedir[@],$(runstatedir),g' \
    -e 's,[@]mysql_datadir[@],$(mysql_datadir),g' \
    -e 's,[@]confdir[@],$(confdir),g' \
    -e 's,[@]logdir[@],$(logdir),g' \
    -e 's,[@]rundir[@],$(rundir),g' \
    -e 's,[@]apache_logdir[@],$(apache_logdir),g' \
    -e 's,[@]php_log_file[@],$(php_log_file),g' \
    -e 's,[@]apache_rundir[@],$(apache_rundir),g' \
    -e 's,[@]apache_pid[@],$(apache_pid),g' \
    -e 's,[@]apache_lock[@],$(apache_lock),g' \
    -e 's,[@]frontenddir[@],$(frontenddir),g' \
    -e 's,[@]mysql_rundir[@],$(mysql_rundir),g' \
    -e 's,[@]mysql_pid[@],$(mysql_pid),g' \
    -e 's,[@]mysql_secfile[@],$(mysql_secfile),g' \
    -e 's,[@]mysql_socket[@],$(mysql_socket),g' \
    -e 's,[@]mysql_lock[@],$(mysql_lock),g' \
    -e 's,[@]mysql_tmpdir[@],$(mysql_tmpdir),g' \
    -e 's,[@]localedir[@],$(localedir),g' \
    -e 's,[@]sysconfdir[@],$(sysconfdir),g'

substituted = \
  $(backend_scripts) $(build_time_scripts) $(conf_files:%=sysconf/%) $(modules) \
  $(frontend_php_built)

dirs.stamp: ; $(MKDIR_P) $(directories) && touch $@

$(substituted): %: %.in Makefile dirs.stamp; $(subst_in_files) < $< > $@

# Force trivial cgitrepos for the time being.
cgirepos: ; echo -n > $@

EXTRA_DIST = bootstrap AUTHORS.Savannah AUTHORS.SourceForge \
  db/initial.sql db/mk-db.sql.in db/sample.sql \
  tests $(substituted:%=%.in) backend/README autotools/README \
  caboose/cleanup-dump.sed \
  caboose/copyright.awk caboose/fixup-notices.awk caboose/notices \
  caboose/parse-files.awk

CLEANFILES = $(substituted) dirs.stamp db/init.stamp

dest_local = $(DESTDIR)$(pkgstatedir)
dest_run = $(DESTDIR)$(rundir)
dest_log = $(DESTDIR)$(logdir)
if HAVE_DATABASE
db_dirs = $(DESTDIR)$(mysql_rundir) $(DESTDIR)$(mysql_tmpdir)
# Database data directory is created with the initializatoin utility,
# and some versions of it require that it doesn't exist.
uninstalled_db_dirs = $(DESTDIR)$(mysql_datadir) $(DESTDIR)$(mysql_secfile)
skip_init_db =
else
skip_init_db = @echo Skipping
endif

if HAVE_FRONTEND
frontend_dirs = $(DESTDIR)$(apache_rundir) $(DESTDIR)$(apache_logdir)
frontend_cache_dirs = $(dest_local)/submissions_uploads/trackers_attachments
endif # HAVE_FRONTEND
dirs_to_make = $(db_dirs) $(dest_log) $(frontend_dirs) $(frontend_cache_dirs)
uninstalled_dirs = $(dirs_to_make) $(uninstalled_db_dirs)

uninstall-hook:
	rm -fr $(uninstalled_dirs)
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
install-data-local: init-database
init-database: make-var-subdirs install-conf-link install-conf-files
	$(skip_init_db) $(PERL) db/init-database.pl "$(DESTDIR)"
	$(skip_init_db) test -e db/init.stamp || exit 1

.PHONY: install-conf-link instal-conf-files make-var-subdirs init-database

uninstall-local: ; \
  for i in $(conf_files) launch-db.pl; do \
    $(RM) "$(DESTDIR)$(confdir)/$$i"; \
  done

distcheck-hook:
	if grep -q '^This is a stub' $(distdir)/ChangeLog; then \
	  echo; echo; \
	  echo "ChangeLog hasn't been generated.  " \
	    "Configure with --enable-changelog."; \
	  echo; echo; false; \
	fi
