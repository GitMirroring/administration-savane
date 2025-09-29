# Based on gettext-0.16.1/gettext-tools/examples/hello-php.
# Public domain.

# General automake options.
AUTOMAKE_OPTIONS = gnits
ACLOCAL_AMFLAGS = -I autotools/m4
AM_DISTCHECK_CONFIGURE_FLAGS = --enable-mirror

# The list of subdirectories containing Makefiles.
SUBDIRS = . frontend po
bin_SCRIPTS =
EXTRA_DIST =
nobase_pkgdata_DATA =

mirmon_bin = backend/misc/sv_gnu-mirmon backend/misc/sv_mirmon-to-geoip \
  backend/misc/sv_city-filter backend/misc/sv_collect-mirror-stats \
  backend/misc/sv_regen-stat-index backend/misc/sv_regen-stats \
  backend/misc/sv_summarize-latest-stats \
  backend/external/mirror-redirect

bin_all = $(mirmon_bin)
sbin_files =
conf_files =

mirmon_conf = mirmon-to-geoip.conf
frontend_conf = apache.conf php.ini savane.conf.php
conf_all = $(frontend_conf) $(mirmon_conf)
frontend_sbin = sv_frontendctl.pl sv_php_server.pl
sbin_all = $(frontend_sbin)
mirmon_pkgdata = backend/external/cities15000 backend/external/CC0 \
  backend/external/CC_BY-4.0 backend/external/README
if HAVE_MIRROR
bin_SCRIPTS += $(mirmon_bin)
EXTRA_DIST += $(mirmon_pkgdata)
conf_files += $(mirmon_conf)
nobase_pkgdata_DATA += $(mirmon_pkgdata)
endif
if HAVE_FRONTEND
conf_files += $(frontend_conf)
sbin_files += $(frontend_sbin)
endif

backend_bin_common = \
  backend/accounts/sv_assign_uid_gid \
  backend/accounts/sv_crypt \
  backend/accounts/sv_gen_passwd_group \
  backend/accounts/sv_get_authorized_keys \
  backend/accounts/sv_groups \
  backend/accounts/sv_membersh \
  backend/external/commit_prep.pl \
  backend/external/log_accum.pl \
  backend/extra/git/sv_cgit.pl \
  backend/extra/git/sv_cgit_about_filter.pl \
  backend/extra/git/sv_git_gc_repos.pl \
  backend/mail/sv_aliases \
  backend/mail/sv_mailman-wrapper.pl \
  backend/mail/sv_sendmail.pl \
  backend/misc/sv_cleaner \
  backend/misc/sv_reminder

bin_SCRIPTS += $(backend_bin_common)
bin_all += $(backend_bin_common)

frontend_php_built = frontend/php/include/ac_config.php frontend/local.php
db_sbin = sv_launch_db.pl sv_kill_db.pl
sbin_all += $(db_sbin)
if HAVE_DATABASE
sbin_files += $(db_sbin)
endif
backend_sbin_scripts = $(sbin_files:%=backend/sbin/%)

backend_scripts = $(backend_bin_scripts) $(backend_sbin_scripts)
all_scripts = $(sbin_all:%=backend/sbin/%) $(bin_all)

build_time_scripts = db/init-database.pl
test_scripts = tests/field-usage tests/mail-pm tests/membership \
  tests/register-group tests/register-user tests/repos tests/trackers
all_test_scripts = ${test_scripts} tests/run-tests

common_conf = savane.conf.pl membersh-conf.pl
conf_files += $(common_conf)
conf_all += $(common_conf)

directories = backend/accounts backend/sbin backend/external \
  backend/extra/git backend/mail backend/misc db lib/Savane lib/Savane/Test \
  frontend/php/include sysconf tests

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
  lib/Savane/Mirror.pm \
  lib/Savane/Svn.pm \
  lib/Savane/Test/PHP.pm \
  lib/Savane/Test.pm \
  lib/Savane/User.pm \
  lib/Savane/Util.pm \
  lib/Savane/Version.pm

sbin_SCRIPTS = $(backend_sbin_scripts)
noinst_SCRIPTS = $(build_time_scripts) $(frontend_php_built) $(all_test_scripts)
nobase_pkgdata_DATA += $(modules)
confdir = $(sysconfdir)/$(PACKAGE)
pkgstatedir = $(localstatedir)/lib/$(PACKAGE)
logdir = $(pkgstatedir)/log
mirror_logdir = $(logdir)/mirror
mirror_redirect_logdir = $(mirror_logdir)/redirect
mirror_stat_dir = $(mirror_logdir)/stat
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
if HAVE_DATABASE
  HAVE_DATABASE_VAR = 1
else
  HAVE_DATABASE_VAR = 0
endif

subst_in_files = \
  ${SED} \
    -e 's,[@]GIT[@],$(GIT),g' \
    -e 's,[@]PERL[@],$(PERL),g' \
    -e 's,[@]PACKAGE[@],$(PACKAGE),g' \
    -e 's,[@]PACKAGE_TARNAME[@],$(PACKAGE_TARNAME),g' \
    -e 's,[@]VERSION[@],$(VERSION),g' \
    -e 's,[@]SV_CONF[@],$(SV_CONF),g' \
    -e 's,[@]SV_GIT_COMMIT[@],$(SV_GIT_COMMIT),g' \
    -e 's,[@]WWW_SERVER_BIN[@],$(WWW_SERVER_BIN),g' \
    -e 's,[@]WWW_SERVER_GROUP[@],$(WWW_SERVER_GROUP),g' \
    -e 's,[@]WWW_SERVER_HOST[@],$(WWW_SERVER_HOST),g' \
    -e 's,[@]FILE_SERVER_HOST[@],$(FILE_SERVER_HOST),g' \
    -e 's,[@]WWW_HOST[@],$(WWW_HOST),g' \
    -e 's,[@]FILE_HOST[@],$(FILE_HOST),g' \
    -e 's,[@]WWW_SERVER_PORT[@],$(WWW_SERVER_PORT),g' \
    -e 's,[@]WWW_SERVER_USER[@],$(WWW_SERVER_USER),g' \
    -e 's,[@]WWW_SENDMAIL_LINE[@],$(WWW_SENDMAIL_LINE),g' \
    -e 's,[@]PHP_SENDMAIL_LINE[@],$(PHP_SENDMAIL_LINE),g' \
    -e 's,[@]PHP[@],$(PHP),g' \
    -e 's,[@]HAVE_DATABASE[@],$(HAVE_DATABASE_VAR),g' \
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
    -e 's,[@]mirror_logdir[@],$(mirror_logdir),g' \
    -e 's,[@]mirror_redirect_logdir[@],$(mirror_redirect_logdir),g' \
    -e 's,[@]mirror_stat_dir[@],$(mirror_stat_dir),g' \
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
    -e 's,[@]sbindir[@],$(sbindir),g' \
    -e 's,[@]sysconfdir[@],$(sysconfdir),g' \
    -e 's,[@]USE_CAPTCHA[@],$(use_captcha),g' \
    -e 's,[@]DETECT_WWW_PORT[@],$(detect_www_port),g' \
    -e 's,[@]test_scripts[@],$(test_scripts),g'

substituted = \
  $(all_scripts) $(build_time_scripts) $(conf_all:%=sysconf/%) $(modules) \
  $(frontend_php_built)

dirs.stamp: ; $(MKDIR_P) $(directories) && touch $@

$(substituted): %: %.in Makefile dirs.stamp; $(subst_in_files) < $< > $@
$(all_test_scripts): %: %.in Makefile dirs.stamp
	$(subst_in_files) < $< > $@
	chmod +x $@
all_substituted = $(substituted) $(all_test_scripts)

# Force trivial cgitrepos for the time being.
cgirepos: ; echo -n > $@

EXTRA_DIST += backend/external/cities15000.txt bootstrap AUTHORS.Savannah \
  AUTHORS.SourceForge db/initial.sql db/mk-db.sql.in db/sample.sql \
  $(all_substituted:%=%.in) $(mirmon_pkgdata) backend/README \
  autotools/README caboose/cleanup-dump.sed \
  caboose/copyright.awk caboose/fixup-notices.awk caboose/notices \
  caboose/parse-files.awk doc/comments-via-email doc/git-repos \
  doc/signed-notifications

CLEANFILES = $(all_substituted) dirs.stamp db/init.stamp

dest_local = $(DESTDIR)$(pkgstatedir)
dest_run = $(DESTDIR)$(rundir)
if HAVE_MIRROR
dest_mirror_log_dirs = \
  $(DESTDIR)$(mirror_redirect_logdir) $(DESTDIR)$(mirror_stat_dir)
endif
dest_log_dirs = $(DESTDIR)$(logdir) $(dest_mirror_log_dirs)
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
dirs_to_make = $(db_dirs) $(dest_log_dirs) $(frontend_dirs) \
  $(frontend_cache_dirs)
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
if HAVE_FRONTEND
	test `${SED} '1s/-.*//;q' ${srcdir}/NEWS` != '????' \
	  || ${SED} -i '1s/.*/0000-00-00 '"${VERSION}-${SV_GIT_COMMIT}/" \
	       ${srcdir}/NEWS
	$(MAKE) $(AM_MAKEFLAGS) dist
endif

init-database: make-var-subdirs install-conf-link install-conf-files
	$(skip_init_db) $(PERL) db/init-database.pl "$(DESTDIR)" \
	  $(add_test_marker)
	$(skip_init_db) test -e db/init.stamp || exit 1

.PHONY: install-conf-link install-conf-files make-var-subdirs init-database test

uninstall-local: ; \
  for i in $(conf_files) launch-db.pl; do \
    $(RM) "$(DESTDIR)$(confdir)/$$i"; \
  done

distcheck-hook: test
	if grep -q '^This is a stub' $(distdir)/ChangeLog; then \
	  echo; echo; \
	  echo "ChangeLog hasn't been generated.  " \
	    "Configure with --enable-changelog."; \
	  echo; echo; false; \
	fi

if USE_CAPTCHA
  use_captcha = 1
else
  use_captcha = 0
endif
if MARK_DB_FOR_TEST
  add_test_marker = 1
endif
if DETECT_WWW_PORT
  detect_www_port = true
else
  detect_www_port = false
endif

test:
	${RM} -r test
	${MKDIR_P} test
	abs_src_dir=`cd ${srcdir}; pwd`; \
	cd test; $$abs_src_dir/configure --prefix=`pwd`/install \
	  --disable-sendmail --enable-mirror --disable-captcha \
	  --disable-db-networking --enable-sample-db --enable-db-test-marker \
	  --enable-detect-www-port
	${MAKE} -C test all install
	for script in ${all_test_scripts}; do chmod +x test/$$script; done
	cd ./test/tests && ./run-tests
	test 'x${KEEP_TEST}' = xyes || ${RM} -r test
