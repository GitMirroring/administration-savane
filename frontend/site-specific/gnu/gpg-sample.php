<?php
# GPG-specific clarifications.
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry
# Copyright (C) 2013, 2014, 2017-2024 Ineiev <ineiev@gnu.org>
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

if (!isset($gpg_heading_level))
  $gpg_heading_level = 2;

$gpg_sample_text =
"<h$gpg_heading_level>" . _("Sample GPG export") . "</h$gpg_heading_level>
<p>" . _('The exported public GPG keys should look like this:')
. '</p>
<pre>
-----BEGIN PGP PUBLIC KEY BLOCK-----

mQENBFr1PisBCAC9xQcWyOZRLa6K2g7NJbvQmm7p89/xifFYXPpMTQAnlSoCtUdZ
oznXNR4oFYIqTasaXCFpG5uFCTDObPOSg1JqRDZYckijkAvbYlieBY6/ItrQxjyS
... many lines of ASCII data ...
1rMbVMNua84/W98JMFHvu/RNNpmnHvIQoEw7yjVZYt2aTJN/uuGtugNCZ+wri+xh
yl1VWoHhHrHs1zAWDiJSmB4k0zV9Yyw/OMMlPrmMX3SfFEjMDqnC1SNi
=hZua
-----END PGP PUBLIC KEY BLOCK-----
</pre>
<p>'
. _("Do not remove the begin and end markers when submitting your GPG keys.")
. "</p>\n"
. "<h$gpg_heading_level>" . _("Update your keys in this input area")
. "</h$gpg_heading_level>\n"
. '<p>'
. _("Insert your (ASCII) public keys here (made with gpg --export --armor KEYID...):")
. "</p>\n";

$gpg_gnu_maintainers_note = '<p>' . sprintf (_('For GNU maintainers:
If these keys are to be used for GNU uploads,
you must also email them to ftp-upload@gnu.org.
There is no automatic propagation.
See the GNU Maintainer Information, node
<a href="%s">Automated Upload Registration</a>.'),
"//www.gnu.org/prep/maintain/maintain.html#Automated-Upload-Registration")
. "</p>\n";

function gpg_sample_output ()
{
  global $project, $gpg_sample_text, $gpg_gnu_maintainers_note;
  print $gpg_sample_text;

  if ($project->getTypeBaseHost () == "savannah.gnu.org")
    print $gpg_gnu_maintainers_note;
}
?>
