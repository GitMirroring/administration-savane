<?php
# Common functions for managing user preferences.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
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

function my_pref_cbox ($name, $label, $checked = null)
{
  $comp = !empty ($GLOBALS['my_pref_cbox_compact']);
  if ($checked === null)
    $checked = user_get_preference ($name);
  if (!$comp)
    print '<p>';
  print form_checkbox ("form_$name", $checked) . "\n";
  print html_label ("form_$name", $label);
  if ($comp)
    print "<br />&nbsp;&nbsp;";
  else
    print "</p>\n";
}

function my_quiet_ssh_control ()
{
  my_pref_cbox ('quiet_ssh', _("Quiet SSH member shell"));
  print '<p class="smaller">';
  printf (
  # TRANSLATORS: the argument is site name (like Savannah).
    _("When SSH access to %s hosts is implemented using Savane scripts, "
      . "the corresponding source code is offered.  "
      . "Use this checkbox if you want to disable that message."),
    $GLOBALS['sys_name']
  );
  print "</p>\n";
}
function my_sync_preference ($pref, $fb = null)
{
  if ($GLOBALS["form_$pref"] != "1")
    {
      user_unset_preference ($pref);
      return;
    }
  user_set_preference ($pref, 1);
  if (null !== $fb)
    fb ($fb);
}

# Actions to do before selecting theme.
function my_update_theme ()
{
  global $user_theme, $theme_rotate_jump;
  $user_theme = theme_actualise_theme ($user_theme);

  if ($theme_rotate_jump == "1")
    theme_rotate_jump ($user_theme);
}
?>
