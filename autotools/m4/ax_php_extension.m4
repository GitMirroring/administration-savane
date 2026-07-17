dnl Check if PHP loads a specific extension.

dnl Copyright (C) 2026 Ineiev <ineiev@gnu.org>

dnl Copying and distribution of this file, with or without modification, are
dnl permitted in any medium without royalty provided the copyright notice
dnl and this notice are preserved.  This file is offered as-is, without any
dnl warranty.

dnl AX_PHP_EXTENSION(EXTENSION, WHY)
dnl Assign $ax_have_php_$1: "yes" if PHP is configured and loads $1 extension,
dnl at start, else "no."
dnl When $2 is supplied and the the result is "no," append a note on the missing
dnl extention to ax_php_extension_missing to be used in AX_PHP_EXTENSION_REPORT.

AC_DEFUN([AX_PHP_EXTENSION],
  [
    AC_MSG_CHECKING([whether PHP loads the "$1" extension])
    AS_CASE(["${PHP}."], [.], [ax_have_php_$1=no],
      [ax_have_php_$1=`php -r 'print (extension_loaded ("$1"))? "yes": "no";'`]
    )
    AC_MSG_RESULT([[$]ax_have_php_$1])
    AS_IF([test $ax_have_php_$1 != yes && test $# -gt 1],
      [
        ax_php_extension_missing="$ax_php_extension_missing
        > Note: PHP extension \"$1\" is missing.
        `echo "$2" | ${SED} 's,^[[[:space:]]]*, >       ,'`"
      ]
    )
  ]
)

dnl Report the accumulated results of AX_PHP_EXTENSION runs.

AC_DEFUN([AX_PHP_EXTENSION_REPORT],
  [
    ax_php_extension_missing=`
      echo "$ax_php_extension_missing" | ${SED} 's/^ *> \?//'
    `
    AS_ECHO(["$ax_php_extension_missing"])
  ]
)
