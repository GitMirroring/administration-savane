dnl A shortcut for a summarizing AC_PATH_PROG with AC_ARG_VAR

dnl Copyright (C) 2025 Ineiev <ineiev@gnu.org>

dnl Copying and distribution of this file, with or without modification, are
dnl permitted in any medium without royalty provided the copyright notice
dnl and this notice are preserved.  This file is offered as-is, without any
dnl warranty.

dnl AX_PATH_PROG(VARIABLE, PROG-TO-CHECK-FOR, DESCRIPTION,
dnl   [PATH=$PATH], [ERROR-MESSAGE])
dnl Declare VARIABLE as a precious variable with DESCRIPTION added to help strings,
dnl check for program PROG-TO-CHECK-FOR in PATH, set VARIABLE to the absolute
dnl path of the program if found, else to 'not found'.  When ERROR-MESSAGE
dnl is provided, produce an error with that message when the program is not found.
dnl Append the program to ax_path_progs to be used in AX_PATH_PROG_REPORT.

AC_DEFUN([AX_PATH_PROG],
  [
    AC_PATH_PROG([$1], [$2], [not found], [$4])
    AC_ARG_VAR([$1], [$3])
    AS_CASE(["$$1"], ["not found"],
      [
        AS_CASE(["x$5"], [x], [], [AC_MSG_ERROR([$5])])
      ]
    )
    ax_pp_arg="[$3] ("'$'"[$1])"
    ax_pp_arg=`AS_ECHO(["${ax_pp_arg}: $[$1]"]) | ${SED} "s/[[\t ]]/+/g; q"`
    ax_path_progs="$ax_path_progs ${ax_pp_arg}"
  ]
)

dnl Report the accumulated results of AX_PATH_PROG runs.

AC_DEFUN([AX_PATH_PROG_REPORT],
  [
    for prog in $ax_path_progs
    do
      AS_ECHO(["`echo $prog | ${SED} 's/+/ /g'`"])
    done
  ]
)
