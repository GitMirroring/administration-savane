# Autoconf configuration for Savane.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2024 Ineiev
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
AC_PROG_LN_S
AC_PROG_SED

AC_SUBST([DIRNAME],
  [['${SED} -e "s,\([^/]\)/*$$,\1,;tb;:b;s,^[^/]*$$,.,;tq;" \
    -e "s,\([^/]/\)/*,\1,g;s,/[^/]*$$,/,;s,\([^/]\)/$$,\1,;s,^//*$$,/,;:q"']])

AC_PATH_PROG([PERL], [perl])
AS_CASE(["x$PERL"], [x], [AC_MSG_ERROR([Perl not found])])
AC_ARG_VAR([PERL], [Perl executable])

AC_PATH_PROG([PHP], [php])
AC_ARG_VAR([PHP], [PHP executable])

# Look for Apache2 and MySQL server, needed for local testing.
AC_PATH_PROG([WWW_SERVER_BIN], [apache2], [], [$PATH$PATH_SEPARATOR/usr/sbin])
AC_ARG_VAR([WWW_SERVER_BIN], [Apache2 executable])
AC_PATH_PROG([MYSQLD_BIN], [mysqld], [], [$PATH$PATH_SEPARATOR/usr/sbin])
AC_ARG_VAR([MYSQLD_BIN], [MySQL daemon])
AC_PATH_PROG([MYSQLD_SAFE_BIN], [mysqld_safe], [],
  [$PATH$PATH_SEPARATOR/usr/sbin])
AC_ARG_VAR([MYSQLD_SAFE_BIN], [MySQL '"safe"' daemon])
AC_PATH_PROG([MYSQL_INSTALLDB_BIN], [mysql_install_db], [],
  [$PATH$PATH_SEPARATOR/usr/sbin])
AC_ARG_VAR([MYSQL_INSTALLDB_BIN], [mysql_install_db executable])
AC_PATH_PROG([MYSQL_BIN], [mysql])
AC_ARG_VAR([MYSQL_BIN], [MySQL client])
AS_CASE(["x$MYSQL_BIN"], [x], [AC_MSG_ERROR([MySQL client not found])])
AC_PATH_PROG([GRAPHVIZ_BIN], [dot])
AC_ARG_VAR([GRAPHVIZ_BIN], [Graphviz executable])

AC_MSG_CHECKING([whether to regenerate ChangeLog])
AC_ARG_ENABLE([changelog],
  [AS_HELP_STRING([--enable-changelog], [update ChangeLog from git logs])],
  [], [enable_changelog=no])
AC_MSG_RESULT([$enable_changelog])

AC_PATH_PROG([GIT], [git])
AC_ARG_VAR([GIT], [Git executable])

AS_CASE(["x$GIT"], [x], [], [
  AS_IF([test -d "$srcdir/.git"], [
    AS_CASE(["x$enable_changelog"], [xyes], [
      AS_ECHO(["Updating ChangeLog..."])
      "$SED" '1,/^=\{51\}/{/^=\{51\}/p;d}' "$srcdir/ChangeLog" > ChangeLog.git
      "$GIT" --git-dir="$srcdir/.git" log --no-decorate --numstat \
        | "$SED" "s,[[[:space:]]]*$,," > "$srcdir/ChangeLog"
      echo >> "$srcdir/ChangeLog"
      cat ChangeLog.git >> "$srcdir/ChangeLog"
      rm ChangeLog.git
    ])
  ])
])

AC_MSG_CHECKING([whether to install database])
AC_ARG_ENABLE([database],
  [AS_HELP_STRING([--disable-database], [do not install database])],
  [], [enable_database=yes])
AS_CASE(["x$enable_database"], [xno], [], [enable_database=yes])
AC_MSG_RESULT([$enable_database])
AM_CONDITIONAL([HAVE_DATABASE], [test "x$enable_database" = xyes])
AS_CASE(["$enable_database.$MYSQL_BIN"], [yes.],
  [AC_MSG_ERROR([MySQL client not found])])
AS_CASE(["$enable_database.$MYSQL_INSTALLDB_BIN$MYSQLD_BIN"], [yes.],
  [AC_MSG_ERROR([Neither mysql_install_db nor mysqld found.])])
AS_CASE(["$enable_database.$MYSQL_INSTALLDB_BIN"], [yes.],
  [AC_MSG_WARN([mysql_install_db not found; falling back to mysqld])])
AS_CASE(["$enable_database.$MYSQLD_SAFE_BIN"], [yes.],
  [AC_MSG_ERROR([mysqld_safe not found])])

AC_MSG_CHECKING([whether to install frontend part])
AC_ARG_ENABLE([frontend],
  [AS_HELP_STRING([--disable-frontend], [skip frontend (PHP) part])],
  [], [enable_frontend=yes])
AS_CASE(["x$enable_frontend"], [xno], [], [enable_frontend=yes])
AC_MSG_RESULT([$enable_frontend])
AS_CASE(["$enable_frontend.$PHP"], [yes.], [AC_MSG_ERROR([PHP not found])])
AM_CONDITIONAL([HAVE_FRONTEND], [test "x$enable_frontend" = xyes])

AC_MSG_CHECKING([for Git commit])
SV_GIT_COMMIT=`"$SED" 's,^commit ,,;q' $srcdir/ChangeLog`
commit_source='guessed from ChangeLog'
AS_IF([test -d "$srcdir/.git"], [
  head=`"$SED" 's,^ref: ,,;q' $srcdir/.git/HEAD`
  SV_GIT_COMMIT=`cat $srcdir/.git/$head`
  commit_source='extracted from Git repository'
])
AC_MSG_RESULT([$SV_GIT_COMMIT])
AS_IF([echo $SV_GIT_COMMIT | grep -q ['^[a-f0-9]\{40,\}$']],
  [],
  [AC_MSG_ERROR([Failed to identify Git commit of the source code.])
])
AC_SUBST([SV_GIT_COMMIT])

AC_MSG_CHECKING([for database name])
AC_ARG_WITH([db-name],
  AS_HELP_STRING([--with-db-name], [name of the database [savane]]),
  [SV_DB_NAME="$withval"], [SV_DB_NAME=savane])
AC_MSG_RESULT([$SV_DB_NAME])

AC_MSG_CHECKING([for database user])
AC_ARG_WITH([db-user],
  AS_HELP_STRING([--with-db-user], [name of the database user [sv]]),
  [SV_DB_USER="$withval"], [SV_DB_USER=sv])
AC_MSG_RESULT([$SV_DB_USER])

AC_MSG_CHECKING([for database host])
AC_ARG_WITH([db-host],
  AS_HELP_STRING([--with-db-host], [host of the database [localhost]]),
  [SV_DB_HOST="$withval"], [SV_DB_HOST=localhost])
AC_MSG_RESULT([$SV_DB_HOST])

AC_MSG_CHECKING([for database port])
AC_ARG_WITH([db-port],
  AS_HELP_STRING([--with-db-port], [port to connect to database [none]]),
  [SV_DB_PORT="$withval"; db_port="$SV_DB_PORT"], [db_port=none])
AC_MSG_RESULT([$db_port])

AC_MSG_CHECKING([whether to disable database networking])
AC_ARG_ENABLE([db-networking],
  AS_HELP_STRING([--enable-db-networking], [run database over network [no]]),
  [], [enable_db_networking=no])
AS_CASE(["x$enable_db_networking"], [xno], [], [enable_db_networking=yes])
AC_MSG_RESULT([$enable_db_networking])

AC_MSG_CHECKING([for database options])
AC_ARG_WITH([db-options],
  AS_HELP_STRING([--with-db-options],
    [parameters to give MySQL when initializing the database []]),
  [SV_DB_OPTIONS="$withval"])
AC_MSG_RESULT([$SV_DB_OPTIONS])

AC_MSG_CHECKING([for database credentials])
AC_ARG_WITH([db-credentials],
  AS_HELP_STRING([--with-db-credentials],
    [path to the file with MySQL credentials in form password=_PASSWD_]))
AC_MSG_RESULT([$with_db_credentials])
AS_CASE(["x$with_db_credentials"], [xno], [],
  [ AS_IF([test -f  "$with_db_credentials"],
      [SV_DB_PASSWORD=`
        "$SED" ['/^password=/!d;s,[^=]*=,,;q'] < "$with_db_credentials"`])
  ])

AC_SUBST([SV_DB_HOST])
AC_SUBST([SV_DB_PORT])
AC_SUBST([SV_DB_NETWORKING], [$enable_db_networking])
AC_SUBST([SV_DB_NAME])
AC_SUBST([SV_DB_USER])
AC_SUBST([SV_DB_PASSWORD])
AC_SUBST([SV_DB_OPTIONS])

SV_CONF="$sysconfdir/savane"
AC_SUBST(SV_CONF)

AC_MSG_CHECKING([for Apache user])
AC_ARG_WITH([apache-user],
  AS_HELP_STRING([--with-apache-user], [webserver user [current user]]),
  [WWW_SERVER_USER="$withval"], [WWW_SERVER_USER=`whoami`])
AC_MSG_RESULT([$WWW_SERVER_USER])
AC_SUBST([WWW_SERVER_USER])

AC_MSG_CHECKING([for Apache group])
AC_ARG_WITH([apache-group],
  AS_HELP_STRING([--with-apache-group], [webserver group [the same as user]]),
  [WWW_SERVER_GROUP="$withval"], [WWW_SERVER_GROUP="$WWW_SERVER_USER"])
AC_MSG_RESULT([$WWW_SERVER_GROUP])
AC_SUBST([WWW_SERVER_GROUP])
AC_MSG_CHECKING([for Apache host])
AC_ARG_WITH([apache-host],
  AS_HELP_STRING([--with-apache-host], [webserver host [127.0.0.85]]),
  [WWW_SERVER_HOST="$withval"], [WWW_SERVER_HOST="127.0.0.85"])
AC_MSG_RESULT([$WWW_SERVER_HOST])
AC_SUBST([WWW_SERVER_HOST])
AC_MSG_CHECKING([for Apache port])
AC_ARG_WITH([apache-port],
  AS_HELP_STRING([--with-apache-port], [webserver port [4913]]),
  [WWW_SERVER_PORT="$withval"], [WWW_SERVER_PORT="4913"])
AC_MSG_RESULT([$WWW_SERVER_PORT])
AC_SUBST([WWW_SERVER_PORT])

AC_CONFIG_FILES([
  Makefile etc/Makefile frontend/Makefile db/mk-db.sql
  frontend/php/include/ac_config.php
])
AC_CONFIG_FILES([po/Makefile], [AM_POSTPROCESS_PO_MAKEFILE])
AC_OUTPUT

AS_ECHO([])
AS_ECHO(["Configured $PACKAGE_STRING"])
AS_ECHO(["Git commit: $SV_GIT_COMMIT ($commit_source)"])
AS_ECHO(["Configuration directory: $SV_CONF"])
AS_ECHO(["Apache user: $WWW_SERVER_USER"])
AS_ECHO(["Apache group: $WWW_SERVER_GROUP"])
AS_ECHO(["Apache host: $WWW_SERVER_HOST"])
AS_ECHO(["Apache port: $WWW_SERVER_PORT"])
AS_ECHO(["Database name: $SV_DB_NAME"])
AS_ECHO(["Database user: $SV_DB_USER"])
AS_ECHO(["Database host: $SV_DB_HOST"])
AS_ECHO(["Database port: $db_port"])
AS_ECHO(["Run database over network: $enable_db_networking"])
AS_ECHO(["Install frontend (PHP) part: $enable_frontend"])
AS_ECHO(["Install database: $enable_database"])
AS_ECHO(["Update ChangeLog from git: $enable_changelog"])
AS_CASE(["x$SV_DB_OPTIONS"], [x], [],
  [AS_ECHO(["Database options: $SV_DB_OPTIONS"])]
)

AS_IF([test "$WWW_SERVER_PORT" -lt 1024],
  [
    AS_ECHO(["Note: Apache port is less than 1024;"])
    AS_ECHO(["      sv_frontendctl will require root privileges."])
  ])
AS_CASE(["x$SV_DB_USER"], [x],
  [AS_ECHO(["Note: Database user is unconfigured"])])
AS_CASE(["x$SV_DB_PASSWORD"], [x],
  [AS_ECHO(["Note: Database password is empty"])])

AS_CASE(["x$SV_DB_NAME"], [x],
  [AS_ECHO(["Note: Database name is unconfigured"])])
AS_CASE(["x$SV_DB_HOST"], [x],
  [AS_ECHO(["Note: Database host is unconfigured"])])

AS_CASE(["x$GRAPHVIZ_BIN"], [x],
  [
    AS_ECHO(["Note: graphviz is unconfigured."])
    AS_ECHO(["      Dependency graphs will not be shown."])
  ])

AS_ECHO([])
AS_ECHO(["Now you can run"])
AS_ECHO(["  make all install installcheck"])
AS_ECHO(["to install and check the package."])
