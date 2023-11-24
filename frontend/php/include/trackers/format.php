<?php
# Format tracker data.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2001, 2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
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
require_once (dirname (__FILE__) . '/../utils.php');
require_once (dirname (__FILE__) . '/../savane-git.php');

function format_details (
  $item_id, $group_id, $ascii = false, $item_assigned_to = false,
  $preview = [], $allow_quote = true
)
{
  global $revert_comment_order;
  # Format the details rows from trackers_history.
  $data = [];
  $i = $max_entries = $hist_id = 0;

  $add_comment_item = function ($entry, $preview = false)
    use (&$i, &$max_entries, &$data, &$hist_id)
  {
    $i++;
    $max_entries++;
    $user_id = $entry['user_id'];
    $data[$i]['user_id'] = $entry['user_id'];
    $data[$i]['user_name'] = user_getname ($user_id);
    $data[$i]['realname'] = user_getname ($user_id, true);
    $data[$i]['date'] = $entry['date'];
    $data[$i]['comment_type'] = $entry['comment_type'];
    $data[$i]['text'] = trackers_decode_value ($entry['old_value']);
    $data[$i]['comment_internal_id'] = $entry['bug_history_id'];
    if ($entry['bug_history_id'] < 0)
      $data[$i]['comment_internal_id'] = $hist_id;
    else
      $hist_id = $entry['bug_history_id'] + 1;

    $data[$i]['spamscore'] = $entry['spamscore'];
    $data[$i]['preview'] = $preview;
  };

  # Get original submission.
  $result = db_execute ("
    SELECT submitted_by, date, details, spamscore
    FROM " . ARTIFACT . " WHERE bug_id = ?  LIMIT 1", [$item_id]
  );
  $entry = db_fetch_array ($result);
  $user_id = $entry['submitted_by'];
  $data[$i]['user_id'] = $user_id;
  $data[$i]['user_name'] = user_getname ($user_id);
  $data[$i]['realname'] = user_getname ($user_id, true);
  $data[$i]['date'] = $entry['date'];
  $data[$i]['text'] = $entry['details'];
  $data[$i]['comment_internal_id'] = '0';
  $data[$i]['spamscore'] = $entry['spamscore'];
  $data[$i]['preview'] = false;

  # Get comments (the spam is included to preserve comment No).
  $result = trackers_data_get_followups ($item_id);
  if (db_numrows ($result))
    {
      while ($entry = db_fetch_array ($result))
        $add_comment_item ($entry);
    }

  if (!empty ($preview))
    $add_comment_item ($preview, true);

  # Sort entries according to user config.
  $comment_order = user_get_preference ("reverse_comments_order");
  if ($revert_comment_order)
    $comment_order = !$comment_order;
  if (!$ascii && $comment_order)
    ksort ($data);
  else
    krsort ($data);

  $out = '';
  if ($ascii)
    $out .= "    _______________________________________________________\n\n"
      . "Follow-up Comments:\n\n";
  else
    $out .= html_build_list_table_top ([]);

  # Find how to which users the item was assigned to: if it is squad, several
  # users may be assignees.
  $assignee_id = user_getid ($item_assigned_to);
  $assignees_id = [$assignee_id => true];
  if (member_check_squad ($assignee_id, $group_id))
    {
      $result_assignee_squad = db_execute("
        SELECT user_id FROM user_squad WHERE squad_id = ? and group_id = ?",
        [$assignee_id, $group_id]
      );
      while ($row_assignee_squad = db_fetch_array ($result_assignee_squad))
        $assignees_id[$row_assignee_squad['user_id']] = true;
    }

  # Loop throuh the follow-up comments and format them.
  reset ($data);
  $i = 0; # Comment counter.
  $j = 0; # Counter for background color.
  $previous = false;
  $is_admin = member_check (0, $group_id, 'A');
  foreach ($data as $entry)
    {
      # Ignore if found an entry without date (should not happen).
      if ($entry['date'] < 1)
        continue;

      # Determine if it is a spam.
      $is_spam = false;
      if ($entry['spamscore'] > 4)
        $is_spam = true;

      # In ascii output, always ignore spam.
      if ($ascii && $is_spam)
        continue;

      $score = sprintf (_("Current spam score: %s"), $entry['spamscore']);
      $score = "title=\"$score\"";
      $int_id = $entry['comment_internal_id'];
      $comment_ids = "&amp;item_id=$item_id&amp;comment_internal_id=$int_id";
      $url_start = "{$GLOBALS['php_self']}?func=";
      $class = utils_altrow (++$j);

      # Find out what would be this comment number.
      if ($comment_order)
        $comment_number = $i;
      else
        $comment_number = ($max_entries - $i);
      $i++;

      extract (sane_import ('get',
        [
          'strings' => [
            [
              'func',
              [
               'flagspam', 'unflagspam', 'viewspam', 'delete_file',
               'delete_cc'
              ]
            ]
          ],
          'digits' => 'comment_internal_id'
        ]
      ));
      # Full markup only for original submission.
      if ($comment_number < 1)
        $markedup_text = markup_full ($entry['text']);
      else
        $markedup_text = markup_rich ($entry['text']);
      if ($is_spam)
        {
          # If we are dealing with the original submission put a feedback
          # warning (not if the item was just flagged).
          if ($entry['comment_internal_id'] < 1 && $func != "flagspam")
            fb (_("This item has been reported to be a spam"), 1);

          if ($entry['user_id'] != 100)
            $spammer_user_name = $entry['user_name'];
          else
            $spammer_user_name = _("anonymous");

          $own_post = user_isloggedin () && user_getid () == $entry['user_id'];

          # The admin may actually want to see the incriminated item.
          # The submitter too.
          if (($func == "viewspam" && $comment_internal_id == $int_id)
              || $own_post)
            {
              $out .= "\n<tr class=\"$class\">\n"
                . "<td valign='top'>\n<span class='warn'>("
                . _("Why is this post is considered to be spam? "
                    . "Users may have reported it to be\nspam or, if it has "
                    . "been recently posted, it may just be waiting for "
                    . "spamchecks\nto be run.")
                . ")</span><br />\n";
              if ($own_post || $is_admin)
                $out .=  $markedup_text;
              $out .= "<br />\n<br /></td>\n<td class=\"{$class}extra\" "
                . "id=\"spam{$int_id}\">\n";

              $out .=
                utils_user_link ($entry['user_name'], $entry['realname'], true);
              $out .= "<br />\n";

              if ($is_admin)
                {
                  $cn = $comment_number + 1;
                  $out .= "\n<br /><br />(<a $score href=\"$url_start"
                    . "unflagspam$comment_ids#comment$cn\">"
                    . html_image ("bool/ok.png", ['class' => 'icon'])
                    . _("Unflag as spam") . '</a>)';
                }
              $out .= "</td></tr>\n";
            }
          else
            {
              $out .= "\n<tr class=\"{$class}extra\">"
                . "<td class='xsmall'>&nbsp;</td>\n"
                . "<td class='xsmall'><a $score href=\"$url_start"
                . "viewspam$comment_ids#spam$int_id\">"
                . sprintf (_("Spam posted by %s"), $spammer_user_name)
                . "</a></td></tr>\n";
            }
          continue;
        } # if ($is_spam)

      $comment_type = null;
      if (isset ($entry['comment_type']))
        $comment_type = $entry['comment_type'];

      if ($comment_type == 'None' || $comment_type == '')
        $comment_type = '';
      else
        $comment_type = "[$comment_type]";

      if ($ascii)
        {
          $out .= "\n-------------------------------------------------------\n";

          $date = utils_format_date ($entry['date']);
          if ($entry['realname'])
            $name = "{$entry['realname']} <{$entry['user_name']}>";
          else
            $name = "Anonymous";
          $out .= sprintf ("Date: %-30s By: %s\n", $date, $name);
          $out .= $comment_type;
          if ($comment_type)
            $out .= "\n";
          $out .= markup_ascii ($entry['text']) . "\n";
          continue;
        }
      if ($comment_type)
        $comment_type = "<b>$comment_type</b><br />\n";

      $icon = $icon_alt = '';
      $poster_id = $entry['user_id'];

      # Ignore user 100 (anonymous).
      if ($poster_id != 100)
        {
          # Cosmetics if the user is assignee.
          if (array_key_exists ($poster_id, $assignees_id))
            {
              # Highlight the latest comment of the assignee.
              if ($previous != 1)
                {
                  $class = "boxhighlight";
                  $previous = 1;
                }
            }

          # Cosmetics if the user is project member (we shan't go as far
          # as presenting a different icon for specific roles, like
          # manager).

          if (member_check ($poster_id, $group_id, 'A'))
            {
              # Project admin case: if the group is the admin group,
              # show the specific site admin icon.
              if ($group_id == $GLOBALS['sys_group_id'])
                {
                  $icon = "site-admin";
                  $icon_alt = _("Site Administrator");
                }
              else
                {
                  $icon = "project-admin";
                  $icon_alt = _("Group administrator");
                }
            }
          elseif (member_check ($poster_id, $group_id))
            {
              # Simple project member.
              $icon = "project-member";
              $icon_alt = _("Group Member");
            }
        } # if ($poster_id != 100)

      $out .= "\n<tr class=\"$class\"><td valign='top'>\n";
      if ($entry['preview'])
        $out .= "<p><b>" . _("This is a preview") . "</b></p>\n";
      $out .= "<a id='comment$comment_number' href='#comment$comment_number' "
        . "class='preinput'>\n" . utils_format_date($entry['date']) . ', ';

      if ($comment_number < 1)
        {
          $msg = _("original submission:");
          if (ARTIFACT == "cookbook")
            $msg = _("recipe preview:");
          $out .= "<b>$msg</b>\n";
        }
      else
        $out .= sprintf (_("comment #%s:"), $comment_number);

      $out .= "</a>&nbsp;";
      if ($allow_quote)
        $out .=  "<button name='quote_no' value='$comment_number'>"
          . _('Quote') . "</button>";
      $out .= "<br />\n$comment_type";
      $out .= "<div class='tracker_comment'>$markedup_text</div>\n</td>\n";

      $out .= "<td class=\"{$class}extra\">"
        . utils_user_link ($entry['user_name'], $entry['realname'], true);

      if ($icon)
        {
          $out .= "<br />\n<span class='help'>"
            . html_image ("roles/$icon.png", ['alt' => $icon_alt])
            . '</span>';
        }

      if ($poster_id != 100 && array_key_exists ($poster_id, $assignees_id))
        $out .= html_image (
          "roles/assignee.png", ['title' => _("In charge of this item.")]
        );

      # If not a member of the project, allow to mark as spam.
      # For performance reason, do not check here if the user already
      # flagged the comment as spam, it will be done only if the user tries
      # to do it twice.
      if (user_isloggedin() && !$icon && $poster_id != user_getid ())
        {
          # Surround by two line breaks, to keep that link clearly
          # separated from anything else, to avoid clicks by error.
          $out .= "<br /><br />\n";
          $cn = $comment_number - 1;
          $out .= "(<a $score\n  href=\"$url_start"
            . "flagspam$comment_ids#comment$cn\">"
            . html_image ("misc/trash.png", ['class' => 'icon'])
            . _("Flag as spam") . "</a>)<br /><br />\n";
        }
      $out .= "</td></tr>\n";
    } # foreach ($data as $entry)
  $out .= $ascii? "\n\n\n": "</table>\n";

  return [$out, $max_entries, $comment_order];
}

function format_item_details (
  $item_id, $group_id, $ascii = false, $item_assigned_to = false,
  $preview = [], $allow_quote = true
)
{
  list ($out, , ) = format_details (
    $item_id, $group_id, $ascii, $item_assigned_to, $preview, $allow_quote
  );
  return $out;
}

function format_message_trailer ($bug_ref)
{
  return "\n    _______________________________________________________\n\n"
  . "Reply to this item at:\n\n  <$bug_ref>";
}

function format_item_regular_fields ($res, $group_id)
{
  $body = '';
  while ($field_name = trackers_list_all_fields ())
    {
      # If the field is a special field or if not used by this group
      # then skip it. Otherwise print it in ASCII format.
      if (trackers_data_is_special ($field_name))
        continue;
      if (!trackers_data_is_used ($field_name))
        continue;
      $body .= trackers_field_display (
        $field_name, $group_id, $res[$field_name], false, true, true, true
      );
      $body .= "\n";
    }
  return $body;
}

function format_item_summary ($res, $bug_ref, $artifact)
{
  $group_id = $res['group_id'];
  $item_id = $res['bug_id'];
  $body = "URL:\n  <$bug_ref>\n\n";
  $body .= trackers_field_display (
    'summary', $group_id, $res['summary'], false, true, true, true
  );
  $body .= "\n";
  $body .= sprintf ("%25s %s\n", "Group:", group_getname ($group_id));
  $body .= trackers_field_display (
    'submitted_by', $group_id, $res['submitted_by'], false, true, true, true
  );
  $body .= "\n";
  $body .= trackers_field_display (
    'date', $group_id, $res['date'], false, true, true, true
  );
  $body .= "\n" . format_item_regular_fields ($res, $group_id);
  if (ARTIFACT === $artifact)
    $body .= "\n\n" . format_item_details ($item_id, $group_id, true)
      . "\n\n" . format_item_attached_files ($item_id, $group_id, true);
  return $body;
}

function format_file_agpl_notice ()
{
  return git_agpl_notice ('These attachments are served by Savane.');
}

function format_change_files ($out, $changes, $separator, $item_group)
{
  global $sys_file_domain;
  if (empty ($changes['attach']))
    return $out;
  if ($out)
    $out .= $separator;

  $out_att = "Additional Item Attachment";
  if (!$out)
    $out_att .= ", $item_group";
  $out_att .= ":\n\n";

  foreach ($changes['attach'] as $file)
    $out_att .= sprintf (
       "File name: %-30s Size:%d KB\n    <%s>\n\n",
       $file['name'], intval ($file['size'] / 1024),
       "https://$sys_file_domain/file/{$file['name']}?file_id={$file['id']}"
    );
  return "$out$out_att" . format_file_agpl_notice ();
}

function format_change_comments (
  $out, $changes, $separator, $item_group, $item_id
)
{
  if (empty ($changes['details']))
    return $out;
  if ($out)
    $out .= $separator;

  $out_com = "Follow-up Comment #"
    . db_numrows (trackers_data_get_followups ($item_id));
  if (!$out)
    $out_com .= ", $item_group";

  $out_com .= ":\n\n";
  if ($changes['details']['type'] != 'None')
    $out_com .= "[{$changes['details']['type']}]\n";
  $out_com .= markup_ascii ($changes['details']['add']);
  return "$out$out_com";
}

function format_change_fields ($changes, $separator)
{
  # FIXME: strange, with %25s it does not behave exactly like
  # trackers_field_label_display.
  $fmt = "%24s: %23s => %-23s\n";
  $out = '';
  foreach ($changes as $field => $h)
    {
      # If both removed and added items are empty skip - Sanity check.
      if (empty ($h['del']) && empty ($h['add']))
        continue;

      if ($field == "details" || $field == "attach")
        continue;

      # Since details is used for followups (creepy!), we are forced to play
      # with "realdetails" non existant field.
      if ($field == "realdetails")
        $field = "details";

      $label = trackers_data_get_label ($field);
      if (!$label)
        $label = $field;
      $out .= sprintf ($fmt, $label, isset ($h['del'])? $h['del']: null,
        isset ($h['add'])? $h['add']: null
      );
    }
  return $out;
}

# FIXME: shouldn't this be localized?
function format_item_changes ($changes, $item_id, $group_id)
{
  $separator =
    "\n    _______________________________________________________\n\n";
  $item_group = utils_get_tracker_prefix (ARTIFACT) . "#$item_id"
    . " (group " . group_getunixname ($group_id) . ")";
  $out = format_change_fields ($changes, $separator);

  if ($out)
    $out = "Update of $item_group:\n\n$out";

  $out =
    format_change_comments ($out, $changes, $separator, $item_group, $item_id);
  return format_change_files ($out, $changes, $separator, $item_group);
}

function format_item_fetch_attachments ($item_id, $ascii)
{
  global $HTML;
  $result = trackers_data_get_attached_files ($item_id);
  if (!db_numrows ($result))
    {
      if ($ascii)
        return [$result, ''];
      return [$result,
        '<span class="warn">' . _("No files currently attached") . '</span>'
      ];
    }
  if ($ascii)
    $msg = "    _______________________________________________________\n"
      . "File Attachments:\n\n";
  else
    $msg = $HTML->box_top (_("Attached Files"), '', 1);
  return [$result, $msg];
}

function format_item_attachment_ascii ($row, $href)
{
  $ret = "\n-------------------------------------------------------\n";
  $ret .= sprintf ("Name: %s  Size: %s\n",
    $row['filename'], utils_filesize (0, intval ($row['filesize']))
  );
  return $ret . '<http://' . $GLOBALS['sys_default_domain']
    . utils_unconvert_htmlspecialchars ($href) . '>';
}
function format_item_file_details ($row, $href)
{
  $lnk = "<a href=\"$href\">file #{$row['file_id']}: &nbsp;";
  # TRANSLATORS: the first argument is file name, the second is user's name.
  $out = sprintf (_('<!-- file -->%1$s added by %2$s'),
    $lnk . utils_specialchars ($row['filename']) . '</a>',
    utils_user_link ($row['user_name'])
  );
  $out .= ' <span class="smaller">(' . utils_filesize (0, $row['filesize']);
  if ($row['filetype'])
    $out .= ' - ' . $row['filetype'];
  if ($row['description'])
    $out .= ' - ' . markup_basic ($row['description']);
  return "$out)</span>";
}

function format_delete_file_link ($item_id, $file_id)
{
  global $php_self;
  return '<span class="trash"><a href="$php_self?func=delete_file'
    . "&amp;item_id=$item_id&amp;item_file_id=$file_id\">"
    . html_image_trash (['class' => 'icon']) . '</a></span>';
}

function format_item_single_attachment ($row, $may_delete, $ascii, $i)
{
  global $sys_home;
  $file_id = $row['file_id'];
  $href = $sys_home . ARTIFACT . "/download.php?file_id=$file_id";
  $out = '';
  if ($ascii)
    {
      # The description is common for all files in the original
      # submission, so only write it once.
      if (!$i && $row['description'] !== '')
        $out .= $row['description'] . "\n";
      return $out . format_item_attachment_ascii ($row, $href);
    }
  $out .= '<div class="' . utils_altrow ($i++) . '">';
  if ($may_delete)
    $out .= format_delete_file_link ($row['item_id'], $file_id);
  return $out . format_item_file_details ($row, $href) . "</div>\n";
}

# Show the files attached to this tracker item.
function format_item_attached_files ($item_id, $group_id, $ascii = false)
{
  global $HTML;
  list ($result, $out) = format_item_fetch_attachments ($item_id, $ascii);
  if (!db_numrows ($result))
    return $out;

  $manager = member_check (
    0, $group_id, member_create_tracker_flag (ARTIFACT) . '2'
  );
  for ($i = 0; $row = db_fetch_array ($result); $i++)
    $out .= format_item_single_attachment ($row, $manager, $ascii, $i);

  if ($ascii)
    return "$out\n" . format_file_agpl_notice ();
  return  $out . $HTML->box_bottom (1);
}

function format_item_cc_list_header ($rows)
{
  global $HTML;
  if ($rows <= 0)
    return '<span class="warn">' . _("CC list is empty") . '</span>';
  return $HTML->box_top (_("Carbon-Copy List"), '', 1);
}

function format_item_cc_list_email ($row)
{
  $email = $row['email'];
  # If email is numeric, it must be a user id. Try to convert it
  # to the user name.
  if (ctype_digit (strval ($email)) && user_exists ($email))
    $email =  user_getname ($email);
  return utils_email ($email);
}

function format_item_cc_list_comment ($row)
{
  $vot = _('Voted in favor of this item');
  $com_arr = [
    '-SUB-' => _('Submitted the item'), '-COM-' => _('Posted a comment'),
    '-UPD-' => _('Updated the item'), '-VOT-' => $vot,
    'Voted in favor of this item' => $vot
  ];

  $comment = $row['comment'];
  if (array_key_exists ($comment, $com_arr))
    return $com_arr[$comment];
  return $comment;
}

function format_item_cc_list_user_data ($group_id)
{
  $ret['manager'] = member_check (
    0, $group_id, member_create_tracker_flag (ARTIFACT) . '2'
  );
  $u_id = user_getid ();
  $ret['u_name'] = user_getname ($u_id);
  $ret['u_mail'] = user_getemail ($u_id);
  return $ret;
}

function format_item_cc_list_delete_icon ($u, $row)
{
  global $php_self;
  $cc_id = $row['bug_cc_id'];
  $item = $row['item'];
  $icon = "<span class='trash'><a href=\"$php_self?func=delete_cc"
    . "&amp;item_id=$item&amp;item_cc_id=$cc_id\">"
    . html_image_trash (['class' => 'icon']) . '</a></span>';
  # Show the icon if one of the conditions is met:
  # a) current user is a tracker manager;
  # b) the CC name is the current user;
  # c) the CC email address matches the one of the current user;
  # d) the current user is the person who added the CC.
  if ($u['manager'])
    return $icon;
  if (in_array ($row['email'], [$u['u_name'], $u['u_mail']]))
    return $icon;
  if ($u['u_name'] === $row['user_name'])
    return $icon;
  return '';
}

function format_item_cc_list_entry ($row, $item_id, $user_data, $i)
{
  $out = '';
  $row['item'] = $item_id;
  $comment = format_item_cc_list_comment ($row);
  $row['user_name'] = $user_name = user_getname ($row['added_by']);
  $out .= '<li class="' . utils_altrow ($i) . '">';
  $out .= format_item_cc_list_delete_icon ($user_data, $row);
  $u_link = utils_user_link ($user_name);
  $email = format_item_cc_list_email ($row);
  # TRANSLATORS: the first argument is email, the second is user's name.
  $out .= sprintf (_('<!-- email --> %1$s added by %2$s'), $email, $u_link);
  if ($comment)
    $out .= ' <span class="smaller">(' . markup_basic ($comment) . ')</span>';
  return $out;
}

# Format the notification list for an item.
function format_item_cc_list ($item_id, $group_id)
{
  global $HTML;
  $result = trackers_data_get_cc_list ($item_id);
  $n = db_numrows ($result);
  $out = format_item_cc_list_header ($n);
  if (!$n)
    return $out;
  $user_data = format_item_cc_list_user_data ($group_id);
  $i = 0;
  while ($row = db_fetch_array ($result))
    $out .= format_item_cc_list_entry ($row, $item_id, $user_data, $i++);
  $out .= $HTML->box_bottom (1);
  return $out;
}
?>
