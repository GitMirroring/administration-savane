dnl Check if PHP loads a specific extension.

dnl Copyright (C) 2026 Ineiev <ineiev@gnu.org>

dnl Copying and distribution of this file, with or without modification, are
dnl permitted in any medium without royalty provided the copyright notice
dnl and this notice are preserved.  This file is offered as-is, without any
dnl warranty.

dnl AX_PHP_EXTENSION(EXTENSION)
dnl Assign $ax_have_php_$1: "yes" if PHP is configured and loads $1 extension,
dnl at start, else "no".

AC_DEFUN([AX_PHP_EXTENSION],
  [
    AC_MSG_CHECKING([whether PHP loads the "$1" extension])
    AS_CASE(["${PHP}."], [.], [ax_have_php_$1=no],
      [ax_have_php_$1=`php -r 'print (extension_loaded ("$1"))? "yes": "no";'`]
    )
    AC_MSG_RESULT([[$]ax_have_php_$1])
  ]
)
