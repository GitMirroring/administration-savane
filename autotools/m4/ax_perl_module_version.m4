# ===========================================================================
#  https://www.gnu.org/software/autoconf-archive/ax_perl_module_version.html
# ===========================================================================
#
# SYNOPSIS
#
#   AX_PERL_MODULE_VERSION([MODULE VERSION], [ACTION-IF-TRUE], [ACTION-IF-FALSE])
#
# DESCRIPTION
#
#   Checks to see if the list of 'Module Version' are available in the
#   system. If all the modules in the list are available ACTION-IF-TRUE is
#   executed. Case one module is not available ACTION-IF-FALSE is executed
#   and the macro execution is aborted. NOTE: Perl is needed.
#
#   Example:
#
#     AX_PERL_MODULE_VERSION(CGI::Test 0.104 CGI::Ajax 0.694, ,
#        AC_MSG_ERROR(Need some Perl modules))
#
# LICENSE
#
#   Copyright (C) 2009 Marco Gomes <mpglesi@gmail.com>
#   Copyright (C) 2009 Ruben Fonseca <fonseka@gmail.com>
#   Copyright (C) 2024 Ineiev <ineiev@gnu.org>
#
#   Copying and distribution of this file, with or without modification, are
#   permitted in any medium without royalty provided the copyright notice
#   and this notice are preserved. This file is offered as-is, without any
#   warranty.
#
# CHANGELOG
#
#   2024-10-17 ineiev replace 'cut' and 'wc' with $PERL, use AS_IF and AS_CASE,
#     enclose arguments in brackets, refactor, show detected module versions,
#     improve diagnostics.

# was serial 10

AU_ALIAS([AC_PERL_MODULE_VERSION], [AX_PERL_MODULE_VERSION])
AC_DEFUN([AX_PERL_MODULE_VERSION_CHECK_ARGS], [
  ac_perl_list_modules="$1"
  # Make sure we have Perl.
  AS_CASE(["x$PERL"], [x], [AC_CHECK_PROG([PERL], [perl], [perl])])
  AS_CASE(["x$PERL"], [x], [AC_MSG_ERROR([could not find perl])])
  args_num=`$PERL -e 'print ([$]#ARGV + 1)' $ac_perl_list_modules`
  AS_CASE([`$PERL -e 'print ((shift) % 2)' $args_num`], [1],
    [AC_MSG_ERROR([[AX_PERL_MODULE_VERSION]: wrong argument "$1"])]
  )
])
AC_DEFUN([AX_PERL_MODULE_VERSION_SHIFT_MODULE], [
  module_name=`$PERL -e 'print shift' $ac_perl_list_modules`
  module_version=`$PERL -e 'shift; print shift' $ac_perl_list_modules`
  ac_perl_list_modules=` \
    $PERL -e 'shift; shift; $, = " "; print @ARGV' $ac_perl_list_modules`
  msg="for perl module $module_name"
  AS_CASE([$module_version], [0], [], [msg="$msg version $module_version"])
  AC_MSG_CHECKING([$msg])
])
AC_DEFUN([AX_PERL_MODULE_VERSION_MAKE_RESULT], [
  AS_CASE([$ac_failed], [0], [
      :
      $1
    ], [
      :
      $2
    ]
  )
])
AC_DEFUN([AX_PERL_MODULE_VERSION_FAILED], [
  AS_CASE(["x$1"], [x],
    [AC_MSG_RESULT([not found])], [AC_MSG_RESULT([no: $1])]
  )
  ac_failed=1
  ac_perl_list_modules=""
])
AC_DEFUN([AX_PERL_MODULE_VERSION], [
  AX_PERL_MODULE_VERSION_CHECK_ARGS([$1])
  ac_failed=0
  while test -n "$ac_perl_list_modules" ; do
    AX_PERL_MODULE_VERSION_SHIFT_MODULE
    AS_IF([$PERL "-M$module_name" -e exit > /dev/null 2>&1], [
        version=`\
          $PERL "-M$module_name" -e 'print $'"$module_name::VERSION" 2>&1`
        AS_CASE(
          [`$PERL -e 'print (shift cmp shift)' "$version" "$module_version"`],
          [-1], [AX_PERL_MODULE_VERSION_FAILED([$version])],
          [AC_MSG_RESULT([ok: $version])]
        )
      ], [AX_PERL_MODULE_VERSION_FAILED]
    )dnl AS_IF([$PERL "-M$module_name" -e exit > /dev/null 2>&1],
  done
  AX_PERL_MODULE_VERSION_MAKE_RESULT([$2], [$3])
])dnl AC_DEFUN([AX_PERL_MODULE_VERSION],
