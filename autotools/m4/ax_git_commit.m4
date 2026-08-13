dnl Find out Git commit for the source tree from ChangeLog or Git repository.

dnl Copyright (C) 2026 Ineiev <ineiev@gnu.org>

dnl Copying and distribution of this file, with or without modification, are
dnl permitted in any medium without royalty provided the copyright notice
dnl and this notice are preserved.  This file is offered as-is, without any
dnl warranty.

dnl Look for Git repository in the source tree, then set SV_GIT_COMMIT
dnl to the head of the current branch; set SV_REPO_HEAD to the files
dnl that should change when HEAD changes.  When no Git repo is present,
dnl guess the commit from ChangeLog, leave SV_REPO_HEAD empty.  Define
dnl SV_COMMIT_FROM_REPO Automake conditional.

AC_DEFUN([AX_GIT_COMMIT],
  [
    AC_MSG_CHECKING([for Git commit])
    SV_GIT_COMMIT=`$SED 's,^commit ,,;q' $srcdir/ChangeLog`
    SV_REPO_HEAD=
    ax_have_repo=no
    ax_commit_source='guessed from ChangeLog'
    AS_IF([test -d "$srcdir/.git"], [
        head=`$SED 's,^ref: ,,;q' $srcdir/.git/HEAD`
        SV_REPO_HEAD="$srcdir/.git/HEAD"
        AS_CASE(["$head"], [*/*],
          [
            SV_GIT_COMMIT=`cat $srcdir/.git/$head`
            SV_REPO_HEAD="$SV_REPO_HEAD $srcdir/.git/$head"
          ],
          [SV_GIT_COMMIT="$head"])
        ax_commit_source='extracted from repository'
        ax_have_repo=yes
      ]
    )
    AC_MSG_RESULT([$SV_GIT_COMMIT])
    AS_IF([echo $SV_GIT_COMMIT | grep -q ['^[a-f0-9]\{40,\}$']],
      [],
      [AC_MSG_ERROR([Failed to identify Git commit of the source code.])
    ])
    AC_SUBST([SV_GIT_COMMIT])
    AC_SUBST([SV_REPO_HEAD])
    AM_CONDITIONAL([SV_COMMIT_FROM_REPO], [test $ax_have_repo = yes])
  ]
)

dnl Report the results of AX_GIT_COMMIT.

AC_DEFUN([AX_REPORT_GIT_COMMIT],
  AS_ECHO(["Git commit: $SV_GIT_COMMIT ($ax_commit_source)"])
)
