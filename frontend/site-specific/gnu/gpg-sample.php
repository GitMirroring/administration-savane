<?php
# GPG-specific clarifications.
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry
# Copyright (C) 2013, 2014, 2017-2026 Ineiev <ineiev@gnu.org>
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

$gpg_sample_text = "<p>"
  . _('The exported public GPG keys should look like this:')
  . "</p>\n\n<pre>\n"
  . "-----BEGIN PGP PUBLIC KEY BLOCK-----\n\n"
  . "mQENBFr1PisBCAC9xQcWyOZRLa6K2g7NJbvQmm7p89/xifFYXPpMTQAnlSoCtUdZ\n"
  . "oznXNR4oFYIqTasaXCFpG5uFCTDObPOSg1JqRDZYckijkAvbYlieBY6/ItrQxjyS\n"
  . _("... many lines of ASCII data ...") . "\n"
  . "1rMbVMNua84/W98JMFHvu/RNNpmnHvIQoEw7yjVZYt2aTJN/uuGtugNCZ+wri+xh\n"
  . "yl1VWoHhHrHs1zAWDiJSmB4k0zV9Yyw/OMMlPrmMX3SfFEjMDqnC1SNi\n"
  . "=hZua\n"
  . "-----END PGP PUBLIC KEY BLOCK-----\n"
  . "</pre>\n"
  . "<p>"
  . _("Do not remove the begin and end markers when submitting the keys.")
  . "</p>\n";

$gpg_insert_note = '<p>'
  . sprintf (
      _("Insert the new set of public GPG keys\n"
        . "(made with gpg --export --armor KEYID...) in this input area.\n"
        . "This text will be offered to Savannah visitors for download.\n"
        . "For more info, see <a href='%s'>hints on editing keyrings</a>."),
      '/maintenance/GpgKeyrings'
    )
  . "</p>\n";

$gpg_gnu_maintainers_note = '<p>'
  . sprintf (
      _("For GNU maintainers:\nif these keys are to be used for GNU uploads,\n"
        . "you must also email them to ftp-upload@gnu.org.\n"
        . "There is no automatic propagation.\n"
        . "See the GNU Maintainer Information, node\n"
        . "<a href=\"%s\">Automated Upload Registration</a>."),
      "//www.gnu.org/prep/maintain/maintain.html#Automated-Upload-Registration"
    )
  . "</p>\n";

function gpg_sample_output ($personal_keys = false)
{
  global $project;
  global $gpg_sample_text, $gpg_insert_note, $gpg_gnu_maintainers_note;
  $level = $personal_keys? 2: 3;

  print html_h ($level, _("Sample GPG export")) . $gpg_sample_text;
  print html_h ($level, _("Update GPG keys")) . $gpg_insert_note;

  $host_is_gnu =
    isset ($project) && $project->getTypeBaseHost () == "savannah.gnu.org";

  if ($personal_keys || $host_is_gnu)
    print $gpg_gnu_maintainers_note;
}
?>
