<?php
# Handle SSH keys.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2024 Ineiev
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

foreach (['init', 'account', 'sendmail'] as $inc)
  require_once ("../../include/$inc.php");
session_require (['isloggedin' => 1]);

extract (sane_import ('post',
  ['true' => 'update', 'array' => [['form_keys', ['digits', 'no_quotes']]]]
));

$key_limit = 25; # Maximum key number to register.
$min_keys = 5; # Minumum key fields to show.

$keys = account_get_authorized_keys ();
$user_id = user_getid ();

if ($update)
  {
    form_check ();
    $orig_keys = $keys;
    $keys = [];
    for ($i = 0; $i < $key_limit; $i++)
      {
        if (!isset ($form_keys[$i]))
          continue;
        $k = $form_keys[$i];
        # Remove useless blank spaces.
        $k = str_replace ("\n", "", trim ($k));
        if ($k !== '' && !in_array ($k, $keys, true))
          {
            fb (sprintf (_("Key #%s seen"), $i + 1));
            $keys[] = $k;
          }
      }
    $new_keys = array_diff ($keys, $orig_keys);

    if (count ($new_keys))
      account_new_keys_alert ($user_id);
    account_register_keys ($keys, $user_id);
  }

# Not valid registration, or first time to page.
site_user_header (
  ['title' => _("Change Authorized Keys"), 'context' => 'account']
);
print form_header ();
print '<h2>' . _("Authorized keys") . "</h2>\n";
utils_get_content ("account/editsshkeys");
print '<p>'
 . _("Fill the text fields below with the public keys for each key you want "
     . "to\nregister. After submitting, verify that the number of keys "
     . "registered is what\nyou expected.")
 . "</p>\n";

$n = count ($keys);
if ($n < $min_keys)
  $n = $min_keys;
if ($n > $key_limit)
  $n = $key_limit;

for ($i = 0; $i < $n; $i++)
  {
    $k = '';
    if (isset ($keys[$i]))
      $k = $keys[$i];
    print "<span class=\"preinput\"><label for=\"form_keys[$i]\">";
    printf (_("Key #%s:"), $i + 1);
    print "</label></span>\n<input type='text' size='60' id='form_keys[$i]' "
      . "name='form_keys[$i]'\n  value='$k' /><br />\n";
  }
print "<br />\n" . form_footer (_("Update"));
site_user_footer ([]);
?>
