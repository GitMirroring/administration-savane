# Autoconf configuration for Savane.
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

AC_INIT([Savane], [3.12], [savannah-hackers-public@gnu.org], [savane],
  [https://savannah.gnu.org/projects/administration])

AC_CONFIG_AUX_DIR(autotools)
# For future version of aclocal:
AC_CONFIG_MACRO_DIR(autotools/m4)
AM_INIT_AUTOMAKE([1.11.1])
AM_PO_SUBDIRS
AC_PROG_INSTALL

AC_PATH_PROG(PERL, [perl])
AS_CASE(["x$PERL"], [x], [AC_MSG_ERROR([Perl not found])])

AC_PATH_PROG(SV_DB_CMD, [mysql])
AS_CASE(["x$SV_DB_CMD"], [x], [AC_MSG_ERROR([MySQL client not found])])

# Look for Apache2 and MySQL server, needed for local testing.
AC_PATH_PROG([APACHE2_SERVER_BIN], [apache2], [],
  [$PATH$PATH_SEPARATOR/usr/sbin$PATH_SEPARATOR/usr/local/sbin/])
AC_SUBST([APACHE2_SERVER_BIN])
AC_PATH_PROG([MYSQLD_SAFE_BIN], [mysqld_safe], [],
  [$PATH$PATH_SEPARATOR/usr/sbin$PATH_SEPARATOR/usr/local/sbin/])
AC_SUBST([MYSQLD_SAFE_BIN])
AC_PATH_PROG([MYSQL_INSTALLDB_BIN], [mysql_install_db], [],
  [$PATH$PATH_SEPARATOR/usr/sbin$PATH_SEPARATOR/usr/local/sbin/])
AC_SUBST([MYSQL_INSTALLDB_BIN])
AC_PATH_PROG([MYSQLADMIN_BIN], [mysqladmin], [],
  [$PATH$PATH_SEPARATOR/usr/sbin$PATH_SEPARATOR/usr/local/sbin/])
AC_SUBST([MYSQLADMIN_BIN])
AC_PATH_PROG([MYSQL_BIN], [mysql], [],
  [$PATH$PATH_SEPARATOR/usr/sbin$PATH_SEPARATOR/usr/local/sbin/])
AC_SUBST([MYSQL_BIN])
AC_PATH_PROG(GIT, [git])

AC_MSG_CHECKING([whether to regenerate ChangeLog])
AC_ARG_ENABLE([changelog],
  [AS_HELP_STRING([--enable-changelog], [update ChangeLog from git logs])],
  [], [enable_changelog=no])
AC_MSG_RESULT([$enable_changelog])

AS_CASE(["x$GIT"], [x], [], [
  AS_IF([test -d "$srcdir/.git"], [
    AS_CASE(["x$enable_changelog"], [xyes], [
      AS_ECHO(["Updating ChangeLog..."])
      sed '1,/^=\{51\}/{/^=\{51\}/p;d}' "$srcdir/ChangeLog" > ChangeLog.git
      "$GIT" --git-dir="$srcdir/.git" log --numstat \
        | sed "s,[[[:space:]]]*$,," > "$srcdir/ChangeLog"
      echo >> "$srcdir/ChangeLog"
      cat ChangeLog.git >> "$srcdir/ChangeLog"
      rm ChangeLog.git
    ])
  ])
])

AC_ARG_WITH([db-name], AS_HELP_STRING([--with-db-name],
  [name of the database (default: 'savane')]),
  SV_DB_NAME="$withval",
  SV_DB_NAME="savane")
AC_ARG_WITH([db-options], AS_HELP_STRING([--with-db-options],
  [parameters to give MySQL when initializing the database (default: empty)]),
  SV_DB_OPTIONS="$withval")
# Add these vars in the Makefile's:
AC_SUBST(SV_DB_NAME)
AC_SUBST(SV_DB_OPTIONS)

SV_CONF="$sysconfdir/savane"
AC_SUBST(SV_CONF)

AC_MSG_NOTICE([checking for webserver user])
if test -e  "/etc/debian_version"; then
    # Debian-based
    GUESS_WWW_SERVER_USER="www-data"
    GUESS_PERL_INC="/usr/lib/perl5"
elif test -e "/etc/redhat-release"; then
    # Fedora- or RedHat-based
    GUESS_WWW_SERVER_USER="apache"
    GUESS_PERL_INC="/usr/lib/perl5/site_perl"
elif test -e "/etc/gentoo-release"; then
    # Gentoo-based
    GUESS_WWW_SERVER_USER="apache"
elif test -e "/etc/slackware-version"; then
    # Slackware-based"
    GUESS_WWW_SERVER_USER="nobody"
fi
AC_MSG_RESULT([$GUESS_WWW_SERVER_USER])
# TODO: --with-php-uid=... (AC_ARG_ENABLE)
# TODO: ps aux | grep -E 'http|apache' | parse_uid_field...
# TODO: else { echo "Couldn't guess Apache user"; exit 1}

AC_CONFIG_FILES([
  Makefile autotools/m4/Makefile etc/Makefile frontend/Makefile
  frontend/php/images/Makefile lib/Makefile local/Makefile
  frontend/php/include/ac_config.php
])
AC_CONFIG_FILES([po/Makefile], [AM_POSTPROCESS_PO_MAKEFILE])
AC_OUTPUT

AS_ECHO([])
AS_ECHO(["Configuration directory: $SV_CONF"])
AS_ECHO(["Guessed HTTPd/PHP user: $GUESS_WWW_SERVER_USER"])
AS_ECHO(["Database name: $SV_DB_NAME"])
AS_ECHO(["Update ChangeLog from git: $enable_changelog"])
AS_CASE(["x$SV_DB_OPTIONS"], [x], [],
  [AS_ECHO(["Database options: $SV_DB_OPTIONS"])]
)

AS_ECHO([])
AS_ECHO(["Now you can run"])
AS_ECHO(["  make all install installcheck"])
AS_ECHO(["to install and check the backend scripts and their Savane library."])
