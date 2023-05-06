<?php
# Add and edit group mailing lists.
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2003-2006 BBN Technologies Corp
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2023 Ineiev
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

# Status of list:
# - 0: list is deleted (i.e. does not exist).
# - 1: list is marked for creation.
# - 2: list is marked for reconfiguration.
# - 5: list has been created (i.e. it exists).
#
# This frontend PHP script sets status to:
#   0 if user deletes a list before the backend ever actually created it.
#   1 if user adds a list
#   2 if user reconfigures an _existing_ list (ie, status was 5)
#
# The backend sv_mailman.pl script sets status to:
#   0 when a list is actually deleted
#   5 when a list is actually created
#
# When we create an alias, which mean someone was able, according to
# group type restriction to add a list that was already
# inside the database, we add the list inside the database with a status
# of 5, so sv_mailman does not try to recreate it.
# In the worse case, if two persons creates the same list at the same.

# The field password will not contact real password, it will contain
# '1' when the backend is supposed to reset it.

define ('LIST_STATUS_DELETED', 0);
define ('LIST_STATUS_NEED_CREATION', 1);
define ('LIST_STATUS_NEED_RECONFIGURATION', 2);
define ('LIST_STATUS_CREATED', 5);

require_once ('../../include/init.php');
require_once ('../../include/account.php');

$key_func = ['preg', '/^(\d+|new)$/'];
extract (sane_import ('post',
  [
    'true' => 'post_changes',
    'digits' => 'newlist_format_index',
    'array' =>
      [
        [
          'list_name',
          [
            $key_func,
            ['name', ['min_len' => 0, 'max_len' => 80, 'allow_dots' => true]]
          ]
        ],
        ['description', [$key_func, 'specialchars']],
        ['reset_password', [$key_func, 'true']],
        ['is_public', [$key_func, 'digits']],
      ],
  ]
));

if (!$group_id)
  exit_no_group ();

if (!member_check (0, $group_id))
  exit_permission_denied ();

exit_test_usesmail ($group_id);

$grp = project_get_object ($group_id);

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

if ($post_changes)
  {
    foreach ($list_name as $id => $ignored)
      {
        if ($id == 'new')
          {
            # Add a new list.
            if (!isset ($newlist_format_index))
             {
                if (!isset ($list_name['new']) || strlen ($list_name['new']) < 1)
                  # User didn't fill the form.
                  continue;
                # When there's only a single choice, there's no format index.
                $newlist_format_index = 0;
             }
            $new_password = substr (md5 (time () . rand (0, 40000)), 0, 16);

            # Name shorter than two characters are not acceptable (only
            # check if the chosen format requires %NAME substitution).
            if (
              strpos (
                $grp->getTypeMailingListFormat ("%NAME", $newlist_format_index),
                "%NAME"
              ) !== false
              && (strlen ($list_name['new']) < 2)
            )
              {
                # TRANSLATORS: the argument is the new mailing list
                # name entered by the user.
                fb (
                  sprintf (
                    _("You must provide list name that is two or more "
                      . "characters long: %s"),
                    $list_name['new']
                  ),
                  1
                );
                continue;
              }
            # Site may have a strict policy on list names: checks now.
            $new_list_name =
              $grp->getTypeMailingListFormat(strtolower($list_name['new']),
                                                        $newlist_format_index);
            # Check if it is a valid name.
            if (!account_namevalid($new_list_name, 1, 1, 1, 80))
              {
                # TRANSLATORS: the argument is the new mailing list name
                # entered by the user.
                fb (sprintf (_("Invalid list name: %s"), $new_list_name), 1);
                continue;
              }
            # Check on the list_name: must not be equal to a user account,
            # otherwise it can mess up the mail develivery for the list/user.
            $res = db_execute (
              "SELECT user_id FROM user WHERE user_name LIKE ?",
              [$new_list_name]
            );
            if (db_numrows ($res))
              {
                fb (
                  sprintf (
                    _("List name %s is reserved to avoid conflicts with user "
                      . "accounts."),
                    $new_list_name
                  ), 1
                );
                continue;
              }
            # Check if the list does not exists already.
            $result = db_execute (
              "SELECT group_id FROM mail_group_list WHERE lower(list_name) = ?",
              [$new_list_name]
            );
            if (db_numrows ($result))
              {
                $row = db_fetch_array ($result);
                if ($row['group_id'] == $group_id)
                  {
                    $msg = sprintf (
                      _("The list %s already exists."), $new_list_name);
                    fb ($msg, 1);
                    continue;
                  }
                # If the list exists already, we create an alias
                # (same name but attached to a different group),
                # assuming that group type configuration is well-done
                # and disallow list name to persons not supposed to
                # use some names.
                $msg = sprintf (
                  _("List %s is already in the database. We will create "
                    . "an alias."),
                  $new_list_name
                );
                fb ($msg);
                $status = LIST_STATUS_CREATED;
              }
            else # !(db_numrows($result))
              $status = LIST_STATUS_NEED_CREATION;
            $result = db_autoexecute ('mail_group_list',
              [ 'group_id' => $group_id, 'list_name' => $new_list_name,
                'is_public' => $is_public['new'],
                'password' => $new_password,
                'list_admin' => user_getid (), 'status' => $status,
                'description' => $description['new']],
              DB_AUTOQUERY_INSERT
            );

            if ($result)
              fb (_("List Added"));
            else
              fb (_("Error Adding List"),1);
            continue;
          } # if ($id == 'new')

        # Now get the current database data for this list
        # (yes, it means one SQL SELECT per list, but we dont expect to
        # have group with 200 lists so it should scale).
        $res_status = db_execute ("
          SELECT * FROM mail_group_list
          WHERE group_list_id = ? AND group_id = ?",
          [$id, $group_id]
        );
        if (!db_numrows ($res_status))
          {
            fb (
              sprintf(_("List %s not found in the database"), $list_name[$id]),
              1
            );
            continue;
          }
        $row_status = db_fetch_array ($res_status);

        # Armando L. Caro, Jr. <acaro--at--bbn--dot--com> 2/23/06
        # Change the status based on what status is in MySQL and what
        # is_public is being set to.  We need to account for when
        # multiple changes are entered into mysql before the backend
        # has the opportunity to act on them.
        switch (intval ($row_status['status']))
          {
          # Status of 0 or 1, means the mailing list doesnt
          # exist. So signal to backend to create as long as
          # is_public is not set to "deleted" (ie, 9).
          case LIST_STATUS_DELETED:
          case LIST_STATUS_NEED_CREATION:
            if ($is_public[$id] != 9)
              $status = LIST_STATUS_NEED_CREATION;
            else
              $status = LIST_STATUS_DELETED;
            break;

          # Status of 2 or 5, means the mailing list does exist,
          # and user is making a change. The change has to be
          # signaled to backend no matter what.
          case LIST_STATUS_NEED_RECONFIGURATION:
          case LIST_STATUS_CREATED:
            $status = LIST_STATUS_NEED_RECONFIGURATION;
            break;
          }

        if (empty ($reset_password[$id]))
          $reset_password[$id] = '';
        # We need an update only if there is at least one change.
        util_debug ("{$list_name[$id]}: $status == {$row_status['status']}");
        if ($description[$id] == $row_status['description']
            && $is_public[$id] == $row_status['is_public']
            && (($reset_password[$id] == $row_status['password'])
                || ($row_status['password'] != 1
                    && empty ($reset_password[$id]))))
          continue;

        $result = db_autoexecute ('mail_group_list',
          [ 'status' => $status, 'description' => $description[$id],
            'is_public' => $is_public[$id], 'password' => $reset_password[$id]
          ], DB_AUTOQUERY_UPDATE,
          # list_id is enough, but group_id prevents users from
          # modifying other people's lists:
          "group_list_id = ? AND group_id = ?",
          [$id, $group_id]
        );

        if ($result)
          # TRANSLATORS: the argument is list name.
          fb (sprintf (_("List %s updated"), $list_name[$id]));
        else
          # TRANSLATORS: the argument is list name.
          fb (sprintf (_("Error updating list %s"), $list_name[$id]), 1);
      } # foreach ($list_name as $id => $ignored)
  }

$result = db_execute ("
  SELECT list_name, group_list_id, is_public, description, password, status
  FROM mail_group_list
  WHERE group_id = ? ORDER BY list_name ASC", [$group_id]
);

# Show the form to modify lists status.
site_project_header (['title' => _("Update Mailing List"),
  'group' => $group_id, 'context' => 'amail']
);

print '<p>';
print
 _("You can administer list information from here. Please note that\n"
   . "private lists are only displayed for members of your group, but not "
   . "for\nvisitors who are not logged in.")
 . "<br />\n</p>\n";

print form_header ($_SERVER['PHP_SELF']);
print form_hidden (["post_changes" => "y", "group_id" => $group_id]);

while ($row = db_fetch_array ($result))
  {
    $id = $row['group_list_id'];
    print "<h2>{$row['list_name']}</h2>\n";

    print '<span class="preinput">'
      . html_label ("description[$id]", _("Description:")) . '</span>';
    print "<br />\n&nbsp;&nbsp;&nbsp;"
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
           'id' => "is_public[$id]", 'label' => _("Public List")]);
    print "<br />\n&nbsp;&nbsp;&nbsp;"
      . form_radio ("is_public[$id]", 0,
          [ 'checked' => $row['is_public'] == "0", 'id' => "'is_private[$id]",
            'label' =>
              _("Private List (not advertised, subscribing requires approval)")
          ]);
    print "<br />\n&nbsp;&nbsp;&nbsp;"
      . form_radio ("is_public[$id]", 9,
          [ 'checked' => $row['is_public'] == "9", 'id' => "to_be_deleted[$id]",
            'label' => _("To be deleted (this cannot be undone!)")]);

    # At this point we have no way to know if the backend brigde to
    # mailman is used or not. We will propose the password change only
    # if the list is marked as created.
    # Do not heavily check this, just skip this in the form.
    if ($row['status'] == LIST_STATUS_CREATED
        || $row['status'] == LIST_STATUS_NEED_RECONFIGURATION)
      {
        print "<br />\n<span class='preinput'>"
          . html_label ("reset_password[$id]", _("Reset List Admin Password:"))
          . '</span>';
        print "<br />\n&nbsp;&nbsp;&nbsp;"
          . form_checkbox (
              "reset_password[$id]", $row['password'] == "1"
            )
          . "\n"
          # TRANSLATORS: this string relates to the previous, it means
          # [checkbox] "request resetting admin password".
          . _("Requested - <em> this will have no effect if this list is not "
              . "managed by\nMailman via Savane</em>");
      }
    else
      print form_hidden (["reset_password[$id]" => $row['password']]);
    print form_hidden (["list_name[$id]" => $row['list_name']]);
  } # while ($row = db_fetch_array($result))

# New list form.
utils_get_content ("mail/about_list_creation");

print "</p>\n<h2>" . _('Create a new mailing list:') . "</h2>\n";

$formats = explode (',', $grp->getTypeMailingListFormat ());
$i = 0;
$add_radio = count ($formats) > 1;
foreach ($formats as $fmt)
  {
    $input = str_replace ('%NAME',
      '<input type="text" title="' . _("Name of new mailing list")
      . '" name="list_name[new]" value="" size="25" maxlength="70" />',
      $fmt
    );
    $addr_line = $grp->getTypeMailingListAddress ($input);
    if ($add_radio)
      $addr_line = form_radio ('newlist_format_index', $i,
        ['id' => "newlist_format_$i", 'label' => $addr_line]
      );
    print "$addr_line<br />\n";
    $i++;
  }
print "<p>\n"
  . form_radio ('is_public[new]', 1,
      [ 'id' => 'is_public_new', 'checked' => true,
        'label' => _('Public (visible to non-members)')])
  . "<br />\n"
  . form_radio ('is_public[new]', 0,
     ['id' => 'is_not_public_new', 'label' => _('Private')])
  . "</p>\n<p><strong>" . html_label ("description_new", _('Description:'))
  . "</strong><br />\n"
  . "<input type='text' name='description[new]' id='description_new' "
  . "value='' size='40' maxlength='80'>\n<br />\n";

print form_footer ();
site_project_footer ([]);
?>
