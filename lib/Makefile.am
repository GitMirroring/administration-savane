# Public domain.  Originally written by Ineiev from the scratch.

modules = \
	Savane.pm \
	Savane/Bzr.pm \
	Savane/Conf.pm \
	Savane/Cvs.pm \
	Savane/DB.pm \
	Savane/Download.pm \
	Savane/Git.pm \
	Savane/Groups.pm \
	Savane/Hg.pm \
	Savane/Locks.pm \
	Savane/Mail.pm \
	Savane/Svn.pm \
	Savane/User.pm \
	Savane/Util.pm \
	Savane/Version.pm

directories = Savane

nobase_pkgdata_DATA = $(modules)

initial_configs = savane.conf.pl membersh-conf.pl

install-data-local: $(initial_configs); \
  for i in $(initial_configs); do \
    if test -e "$(DESTDIR)$(sysconfdir)/$(PACKAGE)/$$i"; then\
       echo; echo; \
       echo "NOTE: $(DESTDIR)$(sysconfdir)/$(PACKAGE)/$$i exists;"; \
       echo "      not trying to overwrite it with a new configuration file."; \
       echo; echo; \
    else \
      $(INSTALL) -m 644 -D $$i \
        "$(DESTDIR)$(sysconfdir)/$(PACKAGE)/$$i"; \
    fi; \
  done

uninstall-local: ; \
 for i in $(initial_configs); do \
   $(RM) "$(DESTDIR)$(sysconfdir)/$(PACKAGE)/$$i"; \
 done

substituted = $(modules) $(initial_configs)

subst_pm = sed \
	-e 's,[@]pkgdatadir[@],$(pkgdatadir),' \
	-e 's,[@]sysconfdir[@],$(sysconfdir),' \
	-e 's,[@]PERL[@],$(PERL),' \
	-e 's,[@]VERSION[@],$(VERSION),'

$(substituted): %: %.in
	$(MKDIR_P) $(directories)
	$(subst_pm) < $^ > $@

CLEANFILES = $(substituted)

EXTRA_DIST = $(modules:%=%.in) $(initial_configs:%=%.in)
