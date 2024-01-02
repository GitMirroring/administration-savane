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

require_once ('../../include/init.php');
require_once ('../../include/account.php');
require_once ('../../include/sendmail.php');
session_require (['isloggedin' => 1]);

extract (sane_import ('post',
  [
    'true' => 'update',
    'hash' => 'form_id',
    'array' =>
      [
        ['form_authorized_keys', ['digits', 'no_quotes']]
      ]
  ]
));

$key_limit = 25; # Maximum key number to register.
$min_keys = 5; # Minumum key fields to show.
$key_separator = "###";

if ($update)
  {
    if (!form_check ($form_id))
      exit_error (_("Exiting"));

    $keys = [];
    # Build the key string.
    for ($i = 0; $i < $key_limit; $i++)
      {
        if (!isset ($form_authorized_keys[$i]))
          continue;
        $thiskey = $form_authorized_keys[$i];
        # Remove useless blank spaces.
        $thiskey = trim ($thiskey);
        $thiskey = str_replace ("\n", "", $thiskey);
        if ($thiskey !== '' && !in_array ($thiskey, $keys, true))
          {
            fb (sprintf (_("Key #%s seen"), $i + 1));
            $keys[] = $thiskey;
          }
      }
    $keys = join ($key_separator, $keys);
    # Grab original keys from the database for comparison.
    $res_orig_keys = db_execute (
      "SELECT authorized_keys FROM user WHERE user_id = ?", [user_getid ()]
    );
    $row_orig_keys = db_fetch_array ($res_orig_keys);
    $orig_keys = $row_orig_keys['authorized_keys'];
    $new_keys = array_diff (
      explode ($key_separator, $keys), explode ($key_separator, $orig_keys)
    );

    if (count ($new_keys))
      {
        $user_id = user_getid ();
        # TRANSLATORS: the argument is site name (like Savannah).
        $message = sprintf (
          _("Someone, presumably you, has changed your SSH keys on %s.\n"
            . "If it wasn't you, maybe someone is trying to compromise your "
            . "account..."),
          $sys_name
        );
        # TRANSLATORS: the argument is site name (like Savannah).
        $message .= sprintf (_("-- the %s team."), $sys_name) . "\n";

        sendmail_mail (
          [ 'from' => "$sys_mail_replyto@$sys_mail_domain",
            'to' => user_get_email ($user_id)],
          [ 'subject' => "$sys_name " . _("SSH key changed on your account"),
            'body' => $message]
        );
      }
    $success =
      db_execute (
        "UPDATE user SET authorized_keys = ? WHERE user_id = ?",
        [$keys, user_getid ()]
      );
    if ($success)
      {
        if ($keys == '')
          fb (_("No key is registered"));
        else
          fb (_("Keys registered"));
      }
    else
      fb (_("Error while registering keys"), 1);
  }
else # !$update
  {
    # Grab keys from the database.
    $res_keys = db_execute (
      "SELECT authorized_keys FROM user WHERE user_id = ?",
      [user_getid ()]
    );
    $row_keys = db_fetch_array ($res_keys);
    $keys = $row_keys['authorized_keys'];
  }

$form_authorized_keys =  explode ($key_separator, $keys);

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

$n = count ($form_authorized_keys);
if ($n < $min_keys)
  $n = $min_keys;
if ($n > $key_limit)
  $n = $key_limit;

for ($i = 0; $i < $n; $i++)
  {
    $thiskey = '';
    if (isset ($form_authorized_keys[$i]))
      $thiskey = $form_authorized_keys[$i];
    print "<span class=\"preinput\"><label for=\"form_authorized_keys[$i]\">";
    printf (_("Key #%s:"), $i + 1);
    print "</label></span>\n<input type='text' size='60' "
      . "id='form_authorized_keys[$i]' name='form_authorized_keys[$i]'\n"
      . "      value='$thiskey' /><br />\n";
  }
print "<br />\n" . form_footer (_("Update"));
site_user_footer ([]);
?>
