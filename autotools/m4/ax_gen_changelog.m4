dnl Copyright (C) 2024 Ineiev <ineiev@gnu.org>

dnl Copying and distribution of this file, with or without modification, are
dnl permitted in any medium without royalty provided the copyright notice
dnl and this notice are preserved.  This file is offered as-is, without any
dnl warranty.

AC_DEFUN([AX_GEN_CHANGELOG],[
  AC_MSG_CHECKING([whether to regenerate ChangeLog])
  AC_ARG_ENABLE([changelog],
    [AS_HELP_STRING([--disable-changelog],
    [skip updating ChangeLog from git logs])],
    [], [enable_changelog=yes])
  AC_MSG_RESULT([$enable_changelog])

  AS_CASE(["x$GIT"], [x], [], [
    AS_IF([test -d "$srcdir/.git"], [
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
