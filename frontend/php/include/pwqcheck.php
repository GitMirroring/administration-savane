<?php
# Check password strength.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2000-2002, 2010, 2013, 2016, 2020 Solar Designer
# Copyright (C) 2013, 2014, 2017-2026 Ineiev
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
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
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
# along with this program.  If not, see <https://www.gnu.org/licenses/>.
#
# The pwqcheck() function was originally taken from
# http://www.openwall.com/articles/PHP-Users-Passwords#enforcing-password-policy
#
# The article disclaimed:
#
# No copyright to the source code snippets found in this article and
# to the sample programs included in the accompanying archive is
# claimed, and they're hereby placed in the public domain. Please feel
# free to reuse them in your programs.
#
# Author: Alexander Peslyak (original pwqcheck())
# Author: Ineiev (i18n).
# 2023, Ineiev: rewrite with utils_run_proc.

# The original pwqcheck is not internationalized. Strings to localize
# are taken from passwdqc_check.c (the 1.3.1 release); they are copyrighted
# by Solar Designer (if copyrightable at all).
# The license for passwdqc_check.c is:
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted.
#
# THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND
# ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
# IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
# ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
# FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
# DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
# OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
# HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
# OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
# SUCH DAMAGE.

$pwqcheck_messages_for_i18n = [
  # Can't actually run pwqcheck.
  _('Bad passphrase (check failed)'),
  # Available in passwdqc-1.3.0 and passwdqc-1.3.1.
  _("Bad passphrase (is the same as the old one)"),
  _("Bad passphrase (is based on the old one)"),
  _("Bad passphrase (too short)"),
  _("Bad passphrase (too long)"),
  _("Bad passphrase (not enough different characters or classes for this "
    . "length)"),
  _("Bad passphrase (not enough different characters or classes)"),
  _("Bad passphrase (based on personal login information)"),
  _("Bad passphrase (based on a dictionary word and not a passphrase)"),
  _("Bad passphrase (based on a common sequence of characters and not "
    . "a passphrase)"),
  # Added since passwdqc-1.3.1 to passwdqc-2.0.2.
  _("Bad passphrase (based on a word list entry)"),
  _("Bad passphrase (is in deny list)"),
  _("Bad passphrase (appears to be in a database)"),
];

function pwqcheck ($newpass, $oldpass = '', $user = '', $aux = '', $args = '')
{
  # pwqcheck(1) itself returns the same message on internal error.
  $retval = 'Bad passphrase (check failed)';

  # Replace characters that would violate the protocol.
  $newpass = strtr ($newpass, "\n", '.');
  $oldpass = strtr ($oldpass, "\n", '.');
  $user = strtr ($user, "\n:", '..');

  # Trigger a "too short" rather than "is the same" message in this special
  # case.
  if (!$newpass && !$oldpass)
    $oldpass = '.';

  if ($args)
    $args = " $args";
  if (!$user)
    $args = " -2 $args"; # passwdqc 1.2.0+

  $command = "pwqcheck$args";
  $err = 0;
  $in = "$newpass\n$oldpass\n";
  if ($user)
    $in .= "$user::::$aux:/:\n";
  $status = utils_run_proc (
    $command, $output, $e, ['in' => $in, 'env' => ['LC_ALL' => 'C.UTF-8']]
  );
  if ($status === 'fail')
    return gettext ($retval);

  # There must be a linefeed character at the end.  Remove it.
  if (substr ($output, -1) === "\n")
    $output = substr ($output, 0, -1);
  else
    $err = 1;

  if ($err === 0 && ($status === 0 || $output !== 'OK'))
    $retval = $output;

  if ($retval === 'OK')
    return 0;
  return gettext ($retval);
}

function pwqcheck_match_min_numbers ($args)
{
  $single_field = "([[:digit:]]*|disabled)";
  $fields = $single_field . str_repeat (",$single_field", 4);
  if (preg_match ("/min=$fields /", $args, $matches))
    return $matches;
  return null;
}

function pwqcheck_min_number_messages ()
{
  return [
    [
      _("Passwords consisting of characters from one class only "
          . "are not allowed."),
      _("Passwords consisting of characters from two classes that don't "
        . "meet\nrequirements for passphrases are not allowed."),
      _("Check for passphrases is disabled."),
      _("Passwords consisting of characters from three classes "
        . "are not allowed."),
      _("Passwords consisting of characters from four classes "
        . "are not allowed."),
    ],
    [
      _("The minimum length for passwords consisting of characters "
        . "from one class: %s."),
      _("\nThe minimum length for passwords consisting of characters "
        . "from two classes\nthat don't meet requirements "
        . "for passphrases: %s."),
      _("The minimum length for passphrases: %s."),
      _("The minimum length for passwords consisting of characters\n"
        . "from three classes: %s."),
      _("The minimum length for passwords consisting of characters\n"
        . "from four classes: %s."),
    ]
  ];
}

function pwqcheck_explain_min_numbers ($args, &$help)
{
  $matches = pwqcheck_match_min_numbers ($args);
  if ($matches === null)
    return;
  list ($msg_disabled, $msg_number) = pwqcheck_min_number_messages ();
  for ($i = 0; $i < 4; $i++)
    {
      if ($matches[$i + 1] == 'disabled')
        $help[] = $msg_disabled[$i];
      else
        $help[] = sprintf ($msg_number[$i], $matches[$i + 1]);
    }
}

function pwqcheck_explain_max ($args, &$help)
{
  if (!preg_match ("/max=([[:digit:]]*) /", $args, $matches))
    return;
  $help[] =
    sprintf (_("The maximum allowed password length: %s."), $matches[1]);
}

function pwqcheck_explain_passphrase ($args, &$help)
{
  if (!preg_match ("/passphrase=([[:digit:]]*) /", $args, $matches))
    return;
  $help[] = sprintf (
    _("The number of words required for a passphrase: %s."), $matches[1]
  );
}

function pwqcheck_explain_match ($args, &$help)
{
  if (!preg_match ("/match=([[:digit:]]*) /", $args, $matches))
    return;
  if (!$matches[1])
    {
      $help[] = _("Checks for common substrings are disabled.");
      return;
    }
  $help[] = sprintf (
    _("The length of common substring required to conclude "
      . "that a password\nis at least partially based on information "
      . "found in a character string: %s."),
    $matches[1]
  );
}

# Return a string explaining current pwcheck requirements.
function pwqcheck_explain_options ($pwqcheck_args)
{
  $args = "$pwqcheck_args ";
  $help = [];
  $sep = "<br />\n";

  pwqcheck_explain_max ($args, $help);
  pwqcheck_explain_passphrase ($args, $help);
  pwqcheck_explain_match ($args, $help);
  pwqcheck_explain_min_numbers ($args, $help);
  return $sep . join ($help);
}
?>
