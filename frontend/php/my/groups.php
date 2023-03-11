<?php
# Handle groups of the user.
#
# Copyright (C) 2003-2006 Frederik Orellana <frederik.orellana--cern.ch>
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2023 Ineiev
#
# This file is part of Savane.
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

require_once ('../include/init.php');
require_once ('../include/database.php');
require_directory ('search');
require_directory ('trackers');

$res_user =
  db_execute ("SELECT * FROM user WHERE user_id = ?", [user_getid ()]);
$row_user = db_fetch_array ($res_user);

# Obtain approval_user_gen_email() for site specific content.
utils_get_content ("my/request_for_inclusion");

extract (sane_import ('request',
  [
    'strings' => [['func', ['addwatchee', 'delwatchee']]],
    'digits' => ['watchee_id', 'group_id'],
  ]
));
if ($func)
  {
    if ($func == "delwatchee")
      {
        $result_upd = trackers_data_delete_watchees (
          user_getid (), $watchee_id,$group_id
        );
        if (!$result_upd)
          fb (_("Unable to remove user from the watched users list, "
                . "probably a broken URL")
          );
      }

    if ($func == "addwatchee")
      {
        $result_upd = trackers_data_add_watchees (
           user_getid(), $watchee_id, $group_id
        );
        if (!$result_upd)
          fb (_("Unable to add user in the watched users list, "
                . "probably a broken URL")
          );
      }
  }

# Send an email to group admins when a user joins group.
function send_pending_user_email ($group_id, $user_id, $user_message)
{
  $res_grp =
    db_execute ("SELECT * FROM groups WHERE group_id = ?", [$group_id]);

  if (db_numrows ($res_grp) < 1)
    return 0;
  $row_grp = db_fetch_array ($res_grp);
  $res_admins = db_execute ("
    SELECT user.user_name FROM user, user_group
    WHERE
      user.user_id = user_group.user_id
      AND user_group.group_id = ?
      AND user_group.admin_flags = 'A'", [$group_id]
  );

  if (db_numrows ($res_admins) < 1)
    return 0;
  # Send one email per admin, in one command line comma-separated.
  $admin_list = '';
  while ($row_admins = db_fetch_array ($res_admins))
    $admin_list .= ($admin_list ? ',' : '') . $row_admins['user_name'];

  $message = approval_user_gen_email (
    $row_grp['group_name'], $row_grp['unix_group_name'],
    $group_id, user_getname ($user_id), user_getrealname ($user_id),
    user_getemail ($user_id), $user_message
  );

  sendmail_mail (
    ['from' => user_getname (), 'to' => $admin_list],
    # TRANSLATORS: the argument is group name.
    [ 'subject' =>
        sprintf (_("Membership request for group %s"), $row_grp['group_name']),
      'body' => $message],
    [ 'group' => $row_grp['unix_group_name'], 'tracker' => "usermanagement"]
  );
}

# Request for inclusion.
extract (sane_import ('post',
  [
    'true' => 'update',
    'hash' => 'form_id',
    'pass' => 'form_message',
    'array' => [['form_groups', ['digits', 'true']]],
  ]
));

if ($update)
  {
    $result_upd = db_query ("
      SELECT group_id FROM groups WHERE status = 'A' AND is_public = '1'
      ORDER BY group_id"
    );
    # Check for duplicates.
    if (!form_check ($form_id))
      return 0;
    $form_cleaned_already = false;

    while ($val = db_fetch_array ($result_upd))
      {
        if (!isset ($form_groups[$val['group_id']]))
          continue;
        # If not in group, add user with admin_flag "P"
        # (not very sensible, but this way we avoid changing
        # the table layout).
        if (member_check_pending ($row_user['user_id'], $val['group_id']))
          {
            fb (_("Request for inclusion already registered"), 1);
            continue;
          }
        if (!$form_message)
          {
            fb (_("When joining you must provide a message for the\n"
                  . "administrator, a short explanation of why you want "
                  . "to join this group."), 1
            );
            continue;
          }
        if (!member_add ($row_user['user_id'], $val['group_id'], 'P'))
          continue;
        send_pending_user_email (
          $val['group_id'], $row_user['user_id'], $form_message
        );
        if (!$form_cleaned_already)
          {
            form_clean ($form_id);
            $form_cleaned_already = 1;
          }
      }
  } # if ($update)

# Get global user and group vars.
$result = db_execute ("
  SELECT
    g.group_name, g.group_id, g.unix_group_name, g.status,
    u.admin_flags, h.date
  FROM groups g, user_group u, group_history h
  WHERE
    g.group_id = u.group_id AND u.user_id = ? AND g.status = 'A'
    AND
      ( h.field_name = 'Added User' OR h.field_name = 'Approved User'
        OR u.admin_flags = 'P')
    AND h.group_id = u.group_id AND h.old_value = ?
  GROUP BY g.unix_group_name ORDER BY g.unix_group_name",
  [user_getid (), user_getname ()]
);
$rows = db_numrows ($result);

# Alternative sql that do not use group_history, just in case this history
# would be flawed (history usage has been inconsistent over Savane history).
$history_is_flawed = false;
$result_without_history = db_execute ("
  SELECT g.group_name, g.group_id, g.unix_group_name, g.status, u.admin_flags
  FROM groups g, user_group u
  WHERE g.group_id = u.group_id AND u.user_id = ?  AND g.status = 'A'
  GROUP BY g.unix_group_name ORDER BY g.unix_group_name", [user_getid ()]
);
$rows_without_history = db_numrows ($result_without_history);

if ($rows_without_history != $rows)
  {
    # If number of rows differ, assume that history is flawed. Print a
    # feedback incitating to fix the installation and override flawed result.
    #
    # The following update script was maybe forgot:
    # update/1.0.6/update_group_history.pl
    fb (_("Groups history appears to be flawed.\n"
          . "Please report the incident to administrators."), 1
    );
    $history_is_flawed = true;
    $result = $result_without_history;
    $rows = $rows_without_history;
  }

# Start HTML.
site_user_header (['context'=>'mygroups']);

print '<p>'
 . _("Here is the list of groups you are member of, plus a form which\n"
     . "allows you to ask for inclusion in a group. You can also quit "
     . "groups here.")
 . "</p>\n";

utils_get_content ("my/groups");

# Right part.
print html_splitpage (1);  # Watching other users.
print $HTML->box_top (_("Watched Partners"));
$result_w = trackers_data_get_watchees (user_getid ());
$rows_w = db_numrows ($result_w);

if (!$result_w || $rows_w < 1)
  {
    print '<p>' . _("You are not watching any partners.") . "</p>\n<p>";
    print _("To watch someone, follow the &ldquo;Watch partner&rdquo; link\n"
            . "in group memberlist page. You need to be member of that "
            . "group.");
    print "<br />\n";
    print db_error ();
  }
else
  {
    print '<table>';
    for ($i = 0; $i < $rows_w; $i++)
      {
        $wa_res = db_result ($result_w, $i, 'watchee_id');
        $gr_res = db_result ($result_w, $i, 'group_id');
        print '<tr class="' . utils_altrow ($i) . '"><td width="99%"><strong>'
          . utils_user_link (user_getname ($wa_res), user_getrealname($wa_res))
          . '</strong> <span class="smaller">[' . group_getname ($gr_res) . ']'
          . "</span>\n";

        print "</td>\n"
          . "<td><a href=\"$php_self?func=delwatchee&amp;group_id="
          . "$gr_res&amp;watchee_id=$wa_res" . '" onClick="return confirm(\''
          . _("Stop watching this user") . '\')">'
          . html_image_trash (['alt' => _("Stop watching this user")])
          . "</a></td></tr>\n";
      }
    print "</table>\n";
  }

$result_w = trackers_data_get_watchers (user_getid ());
$watchers = '';
$watchers_num = 0;
while ($row_watcher = db_fetch_array ($result_w))
  {
    $watchers_num += 1;
    $watchers .= "\n"
      . utils_user_link (
          user_getname ($row_watcher['user_id']),
          user_getrealname ($row_watcher['user_id'])
        )
      . ' <span class="smaller">[' . group_getname ($row_watcher['group_id'])
      . ']</span>, ';
  }

if ($watchers)
  {
    $watchers = substr ($watchers, 0, -2); # Remove extra comma at the end.
    $watchers .= ".";

    print '<p>';
    # TRANSLATORS: the message is selected according to number of watchers
    # listed in the first argument; the second argument is comma-separated
    # list of watchers.
    printf (
      ngettext (
        'I am currently watched by %1$s user: %2$s.',
        'I am currently watched by %1$s users: %2$s.',
        $watchers_num
      ),
      $watchers_num, $watchers
    );
    print "</p>\n";
  }
else
  print '<p>' . _("Nobody is currently watching me.")
    . "</p>\n";

print $HTML->box_bottom ();
print "<br />\n";
print $HTML->box_top (_("Request for Inclusion"), '', 1);
print "<div class='boxitem'>\n";
print '<p>';
print
  _("Type below the name of the group you want to contribute to.\n"
    . "Joining a group means getting write access to the repositories\n"
    . "of the group, and involves responsibilities.  Therefore,\n"
    . "usually you would first contact group members (e.g., using\n"
    . "a group mailing list) before requesting formal inclusion using\n"
    . "this form.");
print "</p>\n\n";

print form_tag () . form_hidden (['action' => 'searchgroup']);
print "<input type='text' title=\""
  . _("Group to look for") . "\" size='35' name='words' value=\""
  . htmlspecialchars ($words) . "\" /><br />\n";
print "<br /><br />\n";
print form_submit (_("Search Groups"), "Submit", false, true);
print "</form>\n\n</div><!-- end boxitem -->\n";

extract (sane_import ('request', ['pass' => 'words']));
if (!is_scalar ($words))
  $words = '';
if ($words)
  {
    # Avoid to big search by asking for more than 1 characters.
    # Restricting to more than 2 chars skips a great deal of group names
    # (eg: gv, gdb).
    $result_search = 0;
    if (strlen ($words) > 1)
      $result_search = search_run ($words, "soft", 0);

    print "<div class='boxitemalt' id='searchgroup'>\n<p>";
    print _("Below is the result of the search in the groups database.");
    print "</p>\n";

    if (db_numrows ($result_search) < 1)
      print '<p class="warn">'
        . _("None found. Please note that only search words of more "
            . "than one character\nare valid.")
        . "</p>\n";
    else
      {
        # We do not put pointer to group page along with checkbox,
        # to avoid creating any confusion (for instance, should I check
        # the box or click on the link?).
        # This tool is to search groups for inclusion, not to look around
        # to get information about groups.
        print '<p>';
        print
          _("To request inclusion in one or several groups, check the\n"
            . "boxes, write a meaningful message for group administrator\n"
            . "who will approve or discard the request, and submit the form.");
        print "</p>\n" . form_header ($_SERVER['PHP_SELF']);

        while ($val = db_fetch_array ($result_search))
          {
            if (user_check_ismember ($row_user['user_id'], $val['group_id']))
              {
                print "+ {$val['group_name']} ";
                print _('(already a member)') . "<br />\n";
                continue;
              }
            print form_checkbox ("form_groups[{$val['group_id']}]")
              . "\n<label for=\"form_groups[{$val['group_id']}]\">";
            print "{$val['group_name']}</label><br />\n";
          }

        print "<br />\n<label for='form_message'>" . _("Comments (required):")
          . "</label><br />\n"
          . "<textarea name='form_message' id='form_message' cols='40'\n"
          . "rows='7'></textarea><br /><br />\n";
        print form_submit (_("Request Inclusion"), 'update', false, true);
        print "</form>\n";
      }
    print "</div><!-- end boxitemalt -->\n";
  }
print $HTML->box_bottom (1);
print html_splitpage (2);

if (!$result || $rows < 1)
  {
    print $HTML->box_top (_("My Groups"), '', 1);
    print _("You're not a member of any public group");
    print $HTML->box_bottom (1);
  }
else
  {
    $titles = [
      'A' => [
        _("Groups I'm administrator of"),
        _("I am not administrator of any groups"),
        _("Quit this group")],
      '' => [
        _("Groups I'm contributor of"),
        _("I am not contributor member of any groups"),
        _("Quit this group")],
      'P' => [
        _("Requests for inclusion waiting for approval"),
        _("None found"),
        _("Discard this request")]
    ];
    foreach ($titles as $k => $t)
      {
        print $HTML->box_top ($t[0], '', 1);
        $j = 1;
        $content = '';
        for ($i = 0; $i < $rows; $i++)
          {
            if (db_result ($result, $i, 'admin_flags') != $k)
              continue;
            $content .= '<li class="'. utils_altrow ($j) . '">';
            $content .= '<span class="trash">'
             . '<a href="../my/quitproject.php?quitting_group_id='
             . db_result ($result, $i, 'group_id') . '">'
             . html_image_trash (['alt' => $t[2]])
             . "</a><br /></span>\n";

            $content .= "<a href=\"{$sys_home}projects/"
              . db_result ($result, $i, 'unix_group_name') . '/">'
              . db_result ($result, $i, 'group_name') . "</a><br />\n";
            $date_joined = null;
            if (!$history_is_flawed)
              $date_joined = db_result ($result, $i, 'date');
            if ($date_joined)
              # If the group history is flawed (site install problem),
              # the date may be unavailable.
              $content .= '<span class="smaller">'
                . sprintf (_("Member since %s"), utils_format_date ($date_joined))
                . '</span>';
            $content .= '</li>';
            $j++;
          }
        if ($content != '')
          print "<ul class='boxli'>$content</ul>\n";
        else
          print $t[1];
        print $HTML->box_bottom (1);
        print "<br />\n";
      }
  }
print html_splitpage (3);
$HTML->footer ([]);
?>
