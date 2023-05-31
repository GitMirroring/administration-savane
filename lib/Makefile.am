# Public domain.  Originally written by Ineiev from the scratch.

dist_libexec_SCRIPTS = sv_pm_true

modules = \
	Savane.pm \
	Savane/Bzr.pm \
	Savane/Conf.pm \
	Savane/Cvs.pm \
	Savane/DB.pm \
	Savane/Download.pm \
	Savane/Frontend.pm \
	Savane/Frontend/Exit.pm \
	Savane/Git.pm \
	Savane/Groups.pm \
	Savane/Hg.pm \
	Savane/Locks.pm \
	Savane/Mail.pm \
	Savane/Session.pm \
	Savane/Svn.pm \
	Savane/Trackers.pm \
	Savane/User.pm \
	Savane/Util.pm \
	Savane/Version.pm

nobase_libexec_SCRIPTS = $(modules)

initial_configs = savane.conf.pl membersh-conf.pl

install-data-local: $(initial_configs); \
  for i in $(initial_configs); do \
    if test -e "$(DESTDIR)$(sysconfdir)/savane/$$i"; then\
       echo; echo; \
       echo "NOTE: $(DESTDIR)$(sysconfdir)/savane/$$i exists;"; \
       echo "      not trying to overwrite it with a new configuration file."; \
       echo; echo; \
    else \
      $(INSTALL) -m 644 -D $$i \
        "$(DESTDIR)$(sysconfdir)/savane/$$i"; \
    fi; \
  done

uninstall-local: ; \
 for i in $(initial_configs); do \
   $(RM) "$(DESTDIR)$(sysconfdir)/savane/$$i"; \
 done

substituted = $(modules) $(initial_configs)

subst_pm = sed \
	-e 's,[@]libexecdir[@],$(libexecdir),' \
	-e 's,[@]sysconfdir[@],$(sysconfdir),' \
	-e 's,[@]PERL[@],$(PERL),' \
	-e 's,[@]VERSION[@],$(VERSION),'

$(substituted): %: %.in
	$(MKDIR_P) $(dir $@)
	$(subst_pm) < $^ > $@
	chmod +x $@

CLEANFILES = $(substituted)

EXTRA_DIST = README $(modules:%=%.in) $(initial_configs:%=%.in)
