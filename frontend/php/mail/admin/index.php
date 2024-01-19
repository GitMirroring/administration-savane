<?php
# Add and edit group mailing lists.
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2003-2006 BBN Technologies Corp
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
require_once ('../../include/mailman.php');

define ('PUBLIC_DELETE', 9);
define ('PUBLIC_UNLINK', 10);

$key_func = ['preg', '/^(\d+|new)$/'];
$list_name_func = ['name', ['min_len' => 0, 'max_len' => 80]];
$submit_buttons = ['post_changes', 'add_list'];
extract (sane_import ('post',
  [
    'true' => $submit_buttons,
    'digits' => 'newlist_format_index',
    'array' =>
      [
        ['list_name', ['digits', $list_name_func]],
        ['new_list_name', ['digits', $list_name_func]],
        ['description', [$key_func, 'specialchars']],
        ['reset_password', ['digits', 'true']],
        ['is_public', [$key_func, 'digits']],
      ],
  ]
));
form_check ($submit_buttons);

if (!$group_id)
  exit_no_group ();

if (!member_check (0, $group_id))
  exit_permission_denied ();

exit_test_usesmail ($group_id);

$grp = project_get_object ($group_id);
$formats = explode (',', $grp->getTypeMailingListFormat ());
if (user_is_super_user () && !in_array ('%NAME', $formats))
  $formats[] = '%NAME';

# Check first if the group type set up is acceptable. Otherwise, the form
# will probably be puzzling to the user (ex: no input text for the list
# name).

$ml_address =
  $grp->getTypeMailingListAddress ($grp->getTypeMailingListFormat ("testname"));

if (!$ml_address || $ml_address == "@")
  exit_error (
    _("Mailing lists are misconfigured. Post a support request to ask\n"
      . "your site administrator to review group type setup.")
  );

# Find the filled part of the templated list name and the respective
# $newlist_format_index.  Return an empty string when none found.
function find_out_new_list_name ()
{
  global $new_list_name, $newlist_format_index, $formats;
  if ($newlist_format_index !== null)
    {
      if (empty ($new_list_name[$newlist_format_index]))
        return '';
      return $new_list_name[$newlist_format_index];
   }
  # No radio button selected: take the lowest filled entry.
  for ($i = count ($formats) - 1; $i > 0 && empty ($new_list_name[$i]); $i--)
    ; # Empty cycle body.
  if (empty ($new_list_name[$i]))
    return '';
  $newlist_format_index = $i;
  return $new_list_name[$i];
}

function add_new_list ()
{
  global $newlist_format_index, $list_name, $formats, $grp, $group_id;
  global $is_public, $description;
  if ($newlist_format_index >= count ($formats))
    return;
  $new_list_name = find_out_new_list_name ();
  if ($newlist_format_index === null) # At this point, it should be set.
    return;
  # Names less than two characters long are not acceptable (only
  # check if the chosen format requires %NAME substitution).
  if (
    strpos ($formats[$newlist_format_index], "%NAME") !== false
    && strlen ($new_list_name) < 2
  )
    {
      # TRANSLATORS: the argument is the new mailing list
      # name entered by the user.
      $msg = sprintf (
        _("You must provide list name that is two or more "
          . "characters long: %s"),
        $new_list_name
      );
      fb ($msg, 1);
      return;
    }
  $new_list_name = strtolower ($new_list_name);
  # Site may have a strict policy on list names: checks now.
  if ($formats[$newlist_format_index] !== '%NAME')
    $new_list_name =
      $grp->getTypeMailingListFormat ($new_list_name, $newlist_format_index);
  # Check if it is a valid name.
  if (!account_namevalid ($new_list_name, 1, 1, 1, 80))
    {
      # TRANSLATORS: the argument is the new mailing list name
      # entered by the user.
      fb (sprintf (_("Invalid list name: %s"), $new_list_name), 1);
      return;
    }
  # Check on the list_name: must not be equal to a user account,
  # otherwise it can mess up the mail develivery for the list/user.
  $res = db_execute (
    "SELECT user_id FROM user WHERE user_name LIKE ?", [$new_list_name]
  );
  if (db_numrows ($res))
    {
      $msg = sprintf (
        _("List name %s is reserved to avoid conflicts with "
          . "user accounts."), $new_list_name
      );
      fb ($msg, 1);
      return;
    }
  # Check if the list does not exists already.
  $result = db_execute (
    "SELECT group_id FROM mail_group_list WHERE lower(list_name) = ?",
    [$new_list_name]
  );
  if (db_numrows ($result))
    {
      $msg = sprintf (_("The list %s already exists."), $new_list_name);
      fb ($msg, 1);
      return;
    }
  mailman_make_list (
    $group_id, $new_list_name, $is_public['new'], $description['new']
  );
}

if ($add_list)
  add_new_list ();

function update_list ($id, $name, $group_id)
{
  global $is_public, $reset_password, $description;
  $row_status = mailman_find_list ($id, $group_id, $name);
  if (empty ($row_status))
    return;

  if ($is_public[$id] == PUBLIC_DELETE)
    {
      mailman_delete_list ($id, $name);
      return;
    }
  if ($is_public[$id] == PUBLIC_UNLINK)
    {
      if (user_is_super_user ())
        mailman_unlink_list ($id, $name);
      return;
    }
  if (!empty ($reset_password[$id]))
    {
      mailman_reset_password ($group_id, $name);
      return;
    }

  # We update only when it change.
  $pub = $is_public[$id];
  if ($pub === $row_status['is_public'])
    $pub = null;
  $desc = $description[$id];
  if ($desc === $row_status['description'])
    $desc = null;
  mailman_config_list ($id, $group_id, $name, $pub, $desc);
}

if ($post_changes)
  foreach ($list_name as $id => $name)
    update_list ($id, $name, $group_id);

$result = db_execute ("
  SELECT list_name, group_list_id, is_public, description
  FROM mail_group_list
  WHERE group_id = ? ORDER BY list_name ASC", [$group_id]
);

# Show the form to modify lists status.
site_project_header (['title' => _("Update Mailing List"),
  'group' => $group_id, 'context' => 'amail']
);

print '<p>';
print _("You can administer list information from here.\n"
  . "Public lists are visible to non-members; private lists\n"
  . "are not advertised, subscribing requires approval.");
print "</p>\n<p>";
print
  _("Note that changes made with Mailman web interface are not "
    . "reflected here.");
print "</p>\n";

print form_header ();
print form_hidden (["post_changes" => "y", "group_id" => $group_id]);

while ($row = db_fetch_array ($result))
  {
    $id = $row['group_list_id'];
    print "<h2>{$row['list_name']}</h2>\n";

    print '<span class="preinput">'
      . html_label ("description[$id]", _("Description:")) . '</span>';
    print "\n&nbsp;&nbsp;&nbsp;"
     . form_input (
         "text", "description[$id]",
          utils_specialchars_decode ($row['description'], ENT_QUOTES),
          'maxlength="120" size="50"'
      );

    # Status: private or public list, or planned for deletion.
    # It may be weird to have the last one here, but that is how things
    # are in the database and it is simpler to follow the same idea.
    print "<br />\n<span class='preinput'>" . _("Status:") . '</span>';
    print "<br />\n&nbsp;&nbsp;&nbsp;"
      . form_radio ("is_public[$id]", 1,
         [ 'checked' =>  $row['is_public'] == "1",
           'id' => "is_public[$id]", 'label' => _("public")]);
    print "<br />\n&nbsp;&nbsp;&nbsp;"
      . form_radio ("is_public[$id]", 0,
          [ 'checked' => $row['is_public'] == "0", 'id' => "is_private[$id]",
            'label' => _("private")
          ]);
    print "<br />\n&nbsp;&nbsp;&nbsp;"
      . form_radio ("is_public[$id]", PUBLIC_DELETE,
          [ 'checked' => $row['is_public'] == PUBLIC_DELETE,
            'id' => "to_be_deleted[$id]",
            'label' => _("Delete (this cannot be undone!)")]);
    if (user_is_super_user ())
      {
        print "<br />\n&nbsp;&nbsp;&nbsp;";
        print form_radio ("is_public[$id]", PUBLIC_UNLINK,
          [ 'checked' => $row['is_public'] == PUBLIC_UNLINK,
            'id' => "to_be_unlinked[$id]",
            'label' => no_i18n ("Unlink from database")]
        );
        print "<br />\n&nbsp;&nbsp;&nbsp;"
          . "<a href='../../siteadmin/mailman.php?"
          . "list_name={$row['list_name']}'>"
          . no_i18n ('Reassign to another group') . "</a>";
      }

    print "<br />\n&nbsp;&nbsp;&nbsp;"
      . form_checkbox ("reset_password[$id]", 0)
      . "\n"
      . html_label ("reset_password[$id]", _("Reset list admin password"))
      . "\n";
    print form_hidden (["list_name[$id]" => $row['list_name']]);
  } # while ($row = db_fetch_array($result))
print form_footer ();

# New list form.
utils_get_content ("mail/about_list_creation");

print "<h2>" . _('Create a new mailing list:') . "</h2>\n";
$i = 0;
$add_radio = count ($formats) > 1;
if (count ($formats))
  print form_tag () . "<p>";
foreach ($formats as $fmt)
  {
    $input = form_input ('text', "new_list_name[$i]", '',
      'title="' . _("Name of new mailing list") . '" size="25" maxlength="70"'
    );
    $input = str_replace ('%NAME', $input, $fmt);
    $addr_line = $grp->getTypeMailingListAddress ($input);
    if ($add_radio)
      $addr_line = form_radio ('newlist_format_index', $i,
        ['id' => "newlist_format_$i", 'label' => $addr_line]
      );
    print "$addr_line<br />\n";
    $i++;
  }
if (count ($formats))
  print "</p>\n";
print "<p><span class='preinput'>"
  . html_label ("description_new", _('Description:')) . '</span>'
  . "&nbsp;\n<input type='text' name='description[new]' id='description_new' "
  . "value='' size='40' maxlength='80'></p>\n";
print "<p><span class='preinput'>" . _("Status:") . "</span><br />\n"
  . form_radio ('is_public[new]', 1,
      ['id' => 'is_public_new', 'checked' => true, 'label' => _('public')]
    )
  . "<br />\n"
  . form_radio ('is_public[new]', 0,
      ['id' => 'is_not_public_new', 'label' => _('private')]
    )
  . "</p>\n";
if (count ($formats))
  {
    print form_hidden (['add_list' => 'y', 'group_id' => $group_id]);
    print form_footer ();
  }
site_project_footer ([]);
?>
