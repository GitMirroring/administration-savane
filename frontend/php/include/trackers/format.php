<?php
# Format tracker data.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2001, 2002 Laurent Julliard, CodeX Team, Xerox
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Yves Perrin <yves.perrin--cern.ch>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2025 Ineiev
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
require_once (dirname (__FILE__) . '/../utils.php');
require_once (dirname (__FILE__) . '/../savane-git.php');

function format_entry ($entry, &$hist_id, $preview = false)
{
  $idx = array_key_exists ('user_id', $entry)? 'user_id': 'submitted_by';
  $user_id = $entry[$idx];
  $data['user_id'] = $user_id;
  $data['user_name'] = user_getname ($user_id);
  $data['realname'] = user_getrealname ($user_id);
  $data['date'] = $entry['date'];
  if (array_key_exists ('comment_type', $entry))
    $data['comment_type'] = $entry['comment_type'];
  if (array_key_exists ('old_value', $entry))
    $data['text'] = trackers_decode_value ($entry['old_value']);
  else
    $data['text'] = $entry['details'];
  $data['comment_internal_id'] = array_key_exists ('bug_history_id', $entry)?
    $entry['bug_history_id']: 0;
  if ($data['comment_internal_id'] < 0)
    $data['comment_internal_id'] = $hist_id;
  else
    $hist_id = $data['comment_internal_id'] + 1;
  $data['spamscore'] = $entry['spamscore'];
  $data['preview'] = $preview;
  return $data;
}

function format_fetch_details ($item_id, $preview)
{
  $max_entries = $hist_id = 0;
  $result = db_execute ("
    SELECT submitted_by, date, details, spamscore
    FROM " . ARTIFACT . " WHERE bug_id = ? LIMIT 1", [$item_id]
  );
  $data = [format_entry (db_fetch_array ($result), $hist_id)];

  # Get comments (spam is included to preserve comment No).
  $result = trackers_data_get_followups ($item_id);
  while ($entry = db_fetch_array ($result))
    {
      $data[] = format_entry ($entry, $hist_id);
      $max_entries++;
    }
  if (!empty ($preview))
    {
      $data[] = format_entry ($preview, $hist_id, true);
      $max_entries++;
    }
  return [$data, $max_entries, $hist_id];
}

# Sort entries according to user config.
function format_enforce_comment_order (&$data, $ascii)
{
  global $reverse_comment_order;
  $comment_order = user_get_preference ("reverse_comments_order");
  if ($reverse_comment_order)
    $comment_order = !$comment_order;
  if ($ascii || !$comment_order)
    $data = array_reverse ($data, true);
  reset ($data);
  return $comment_order;
}

# Highlight the latest comment of the assignees, icon for assignees,
# other cosmetics.
function format_mark_assignees (&$data, $item_assigned_to, $group_id)
{
  extract (sane_import ('get', ['funcs' => 'func']));
  $assignees_id = format_details_assignees ($item_assigned_to, $group_id);
  $img = html_image (
    "roles/assignee.png", ['title' => _("In charge of this item.")]
  );
  for ($i = count ($data) - 1; $i >= 0; $i--)
    {
      $uid = $data[$i]['user_id'];
      $data[$i]['assignee_mark'] = '';
      $data[$i]['class'] = utils_altrow ($i);
      if ($uid === 100 || empty ($assignees_id[$uid]))
        continue;
      if (empty ($have_highlighted))
        $data[$i]['class'] = 'boxhighlight';
      $have_highlighted = true;
      $data[$i]['assignee_mark'] = $img;
    }
  if ($data[0]['is_spam'] && (empty ($func) || $func !== "flagspam"))
    fb (_("This item has been reported to be a spam"), 1);
}

function format_comment_type ($entry, $ascii)
{
  $ret = null;
  if (isset ($entry['comment_type']) && $entry['comment_type'] !== 'None')
    $ret = $entry['comment_type'];
  if (empty ($ret))
    return '';
  $ret = "[$ret]";
  if (!$ascii)
    $ret = "<b>$ret</b>";
  return $ret;
}

function format_mark_data (&$data, $item_assigned_to, $group_id, $ascii)
{
  $logged_in = user_isloggedin ();
  $uid = user_getid ();
  for ($i = count ($data) - 1; $i >= 0; $i--)
    {
      $user_id = $data[$i]['user_id'];
      $data[$i]['is_spam'] = $data[$i]['spamscore'] > 4;
      $data[$i]['comment_type'] = format_comment_type ($data[$i], $ascii);
      if ($ascii)
        continue;
      $score = sprintf (_("Current spam score: %s"), $data[$i]['spamscore']);
      $data[$i]['score'] = "title=\"$score\"";
      $data[$i]['own_post'] =  $logged_in && $uid == $user_id;
      $markup_func = $i? 'markup_rich': 'markup_full';
      $data[$i]['html'] = $markup_func ($data[$i]['text']);
      $data[$i]['icon'] = format_member_icon_img ($user_id, $group_id);
    }
  if (!$ascii)
    format_mark_assignees ($data, $item_assigned_to, $group_id);
}

function format_set_comment_ids (&$data, $item_id, $ascii)
{
  if ($ascii)
    return;
  extract (sane_import ('get',
    ['digits' => 'comment_internal_id', 'funcs' => 'func']
  ));
  foreach (['func', 'comment_internal_id'] as $k)
    if (empty ($$k))
      $$k = 0;
  foreach (array_keys ($data) as $k)
    {
      $int_id = $data[$k]['int_id'] = $data[$k]['comment_internal_id'];
      $data[$k]['comment_ids'] =
        "&amp;item_id=$item_id&amp;comment_internal_id=$int_id";
      $data[$k]['viewspam'] = $data[$k]['own_post']
        || ($func === 'viewspam' && $comment_internal_id == $int_id);
      list ($data[$k]['user_link'], $data[$k]['spam_title'])
        = format_poster_name ($data[$k]);
    }
}

function format_comment_ascii ($entry)
{
  if ($entry['is_spam'])
    # In ascii output, always ignore spam.
    return '';
  $out = "\n-------------------------------------------------------\n";
  $date = utils_format_date ($entry['date']);
  if ($entry['realname'])
    $name = "{$entry['realname']} <{$entry['user_name']}>";
  else
    $name = "Anonymous";
  $out .= sprintf ("Date: %-30s By: %s\n", $date, $name);
  $out .= $entry['comment_type'];
  if ($entry['comment_type'])
    $out .= "\n";
  return $out . markup_ascii ($entry['text']) . "\n";
}

function format_details_header ($ascii)
{
  if ($ascii)
    return "    _______________________________________________________\n\n"
      . "Follow-up Comments:\n\n";
  return html_build_list_table_top ([]);
}

# Find how to which users the item was assigned to: if it is squad, several
# users may be assignees.
function format_details_assignees ($item_assigned_to, $group_id)
{
  $assignee_id = user_getid ($item_assigned_to);
  $assignees_id = [$assignee_id => true];
  if (!member_check_squad ($assignee_id, $group_id))
    return $assignees_id;
  $res = db_execute (
    "SELECT user_id FROM user_squad WHERE squad_id = ? and group_id = ?",
    [$assignee_id, $group_id]
  );
  while ($row = db_fetch_array ($res))
    $assignees_id[$row['user_id']] = true;
  return $assignees_id;
}

# Cosmetics if the user is group member (we shan't go as far
# as presenting a different icon for specific roles, like manager).
function format_member_icon ($poster_id, $group_id)
{
  if ($poster_id == 100)
    return ['', ''];
  if (member_check ($poster_id, $group_id, MEMBER_FLAGS_ADMIN))
    {
      if ($group_id == $GLOBALS['sys_group_id'])
        return ["site-admin", _("Site Administrator")];
      return ["project-admin", _("Group administrator")];
    }
  if (member_check ($poster_id, $group_id))
    # Plain group member.
    return ["project-member", _("Group Member")];
  return ['', ''];
}

function format_member_icon_img ($poster_id, $group_id)
{
  list ($icon, $icon_alt) = format_member_icon ($poster_id, $group_id);
  if (empty ($icon))
     return '';
  return "<br />\n<span class='help'>"
    . html_image ("roles/$icon.png", ['alt' => $icon_alt])
    . '</span>';
}

function format_quote_button ($allow_quote, $comment_number)
{
  if (!$allow_quote)
    return '';
  return "<button name='quote_no' value='$comment_number'>"
    . _('Quote') . "</button>";
}

function format_comment_title ($entry, $comment_number, $allow_quote)
{
  $ret = "<td valign='top'>\n";
  if ($entry['preview'])
    $ret .= "<p><b>" . _("This is a preview") . "</b></p>\n";
  $ret .= "<a id='comment$comment_number' href='#comment$comment_number' "
    . "class='preinput'>\n" . utils_format_date($entry['date']) . ', ';
  if ($comment_number > 0)
    $ret .= sprintf (_("comment #%s:"), $comment_number);
  else
    {
      $msg = _("original submission:");
      if (ARTIFACT == "cookbook")
        $msg = _("recipe preview:");
      $ret .= "<b>$msg</b>\n";
    }
  $ret .= "</a>&nbsp;";
  return $ret . format_quote_button ($allow_quote, $comment_number);
}

function format_poster_name ($entry)
{
  $link_user_name = $spammer_user_name = $entry['user_name'];
  if ($entry['user_id'] == 100)
    {
      $spammer_user_name = _("anonymous");
      $link_user_name = null;
    }
  $user_link = utils_user_link ($link_user_name, $entry['realname']);
  $spam_title = sprintf (_("Spam posted by %s"), $spammer_user_name);
  return [$user_link, $spam_title];
}

function format_detail_url_start ()
{
  return $GLOBALS['php_self'] . '?func=';
}

function format_spam_hidden ($entry)
{
  $url_start = format_detail_url_start ();
  return "\n<tr class=\"{$entry['class']}extra\">"
    . "<td class='xsmall'>&nbsp;</td>\n"
    . "<td class='xsmall'><a {$entry['score']} href=\"$url_start"
    . "viewspam{$entry['comment_ids']}#spam{$entry['int_id']}\">"
    . $entry['spam_title'] . "</a></td></tr>\n";
}

function format_spam_foreword ($class)
{
  return "\n<tr class=\"$class\">\n"
    . "<td valign='top'>\n<span class='warn'>("
    . _("Why is this post is considered to be spam? Users may have "
        . "reported it to be\nspam or, if it has been recently posted, "
        . "it may just be waiting for spamchecks\nto be run.")
    . ")</span><br />\n";
}

function format_spam_text ($entry, $comment_no, $is_admin)
{
  if (!$entry['is_spam'])
    return '';
  if (!$entry['viewspam'])
     return format_spam_hidden ($entry);
  $url_start = format_detail_url_start ();
  $ret = format_spam_foreword ($entry['class']);
  # The admin and the submitter may actually see the incriminated item.
  if ($entry['own_post'] || $is_admin)
    $ret .=  $entry['html'];
  $ret .= "<br />\n<br /></td>\n<td class=\"{$entry['class']}extra\" "
    . "id=\"spam{$entry['int_id']}\">\n{$entry['user_link']}<br />\n";

  if ($is_admin)
    $ret .= "\n<br /><br /><a {$entry['score']} href=\"$url_start"
      . "unflagspam{$entry['comment_ids']}#comment$comment_no\">"
      . html_image ("bool/ok.png", ['class' => 'icon'])
      . _("Unflag as spam") . '</a>';
  return "$ret</td></tr>\n";
}

function format_flag_as_spam ($entry, $comment_no)
{
  # Allow non-anonymous users to mark spam of non-members except themselves.
  if (!user_isloggedin () || $entry['icon'] !== '' || $entry['own_post'])
    return '';
  $url_start = format_detail_url_start ();
  # Surround by two line breaks, to keep that link clearly
  # separated from anything else, to avoid clicks by error.
  $sep = "<br /><br />\n";
  return "$sep<a {$entry['score']} href=\"$url_start"
    . "flagspam{$entry['comment_ids']}#comment$comment_no\">"
    . html_image_trash (['class' => 'icon']) . _("Flag as spam") . "</a>$sep";
}

function format_comment_body ($entry, $comment_no)
{
  return "<br />\n{$entry['comment_type']}"
    . "<div class='tracker_comment'>{$entry['html']}</div>\n</td>\n"
    . "<td class=\"{$entry['class']}extra\">"
    . $entry['user_link'] . $entry['icon'] . $entry['assignee_mark']
    . format_flag_as_spam ($entry, $comment_no) . '</td>';
}

function format_details (
  $item_id, $group_id, $ascii = false, $item_assigned_to = false,
  $preview = [], $allow_quote = true
)
{
  list ($data, $max_entries, $hist_id) =
    format_fetch_details ($item_id, $preview);
  format_mark_data ($data, $item_assigned_to, $group_id, $ascii);
  format_set_comment_ids ($data, $item_id, $ascii);
  $comment_order = format_enforce_comment_order ($data, $ascii);
  $out = format_details_header ($ascii);
  $is_admin = member_check (0, $group_id, MEMBER_FLAGS_ADMIN);
  foreach ($data as $i => $entry)
    {
      if ($ascii)
        {
          $out .= format_comment_ascii ($entry);
          continue;
        }
      $out .= format_spam_text ($entry, $i, $is_admin);
      if ($entry['is_spam'])
        continue;
      $out .= "\n<tr class=\"{$entry['class']}\">"
        . format_comment_title ($entry, $i, $allow_quote)
        . format_comment_body ($entry, $i)
        . "</tr>\n";
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
  $public = trackers_item_is_public ($res['privacy'], $res['group_id']);
  if (ARTIFACT === $artifact)
    $body .= "\n\n" . format_item_details ($item_id, $group_id, true) . "\n\n"
      . format_item_attached_files ($item_id, $group_id, true, $public);
  return $body;
}

function format_file_agpl_notice ()
{
  return git_agpl_notice ('These attachments are served by Savane.');
}

function format_file_credentials ($sep)
{
  return "{$sep}file_uid=" . user_getid () . "{$sep}form_id=" . form_get_id ();
}

function format_file_url_path ($file, $public)
{
  $lnk = utils_public_file_url ($file);
  if ($public)
    return $lnk;
  return $lnk . format_file_credentials ('&');
}

function format_file_url ($file, $public = true)
{
  global $sys_file_domain;
  return session_protocol () . "://$sys_file_domain"
    . format_file_url_path ($file, $public);
}

function format_attachment_link ($file, $public)
{
  if ($public)
    return sprintf ("    <%s>\n\n", format_file_url ($file));
  return '';
}

function format_item_change_separator ()
{
  return "\n    _______________________________________________________\n\n";
}

function format_attachment ($file, $public)
{
  foreach (['size', 'name', '_id'] as $k)
    if (array_key_exists ("file$k", $file))
     {
        $k1 = str_replace ('_', '', $k);
        $file[$k1] = $file["file$k"];
     }
  $ret = sprintf ("Name: %-30s Size: %s\n",
    $file['name'], utils_filesize (null, intval ($file['size']))
  );
  $description = '';
  if (!empty ($file['description']))
    $description = $file['description'];
  return [$ret . format_attachment_link ($file, $public), $description];
}

function format_file_list ($changes, $public)
{
  $description = $ret = '';
  foreach ($changes['attach'] as $file)
    {
      list ($entry, $description) = format_attachment ($file, $public);
      $ret .= $entry;
    }
  return [$ret, $description];
}

function format_change_files ($out, $changes, $item_group, $public)
{
  if (empty ($changes['attach']))
    return $out;
  list ($attachments, $description) = format_file_list ($changes, $public);
  $out_att = 'Additional Item Attachment';
  if ($out)
    $out .= format_item_change_separator ();
  else
    $out_att .= ", $item_group";
  $out_att .= ":\n\n";
  if (!empty ($description))
    $out_att = "$out_att$description\n\n";
  $out .= "$out_att$attachments";
  if ($public)
    $out .= format_file_agpl_notice ();
  return $out;
}

function format_change_comments ($out, $changes, $item_group, $item_id)
{
  if (empty ($changes['details']))
    return $out;
  $out_com = "Follow-up Comment #"
    . db_numrows (trackers_data_get_followups ($item_id));
  if ($out)
    $out .= format_item_change_separator ();
  else
    $out_com .= ", $item_group";

  $out_com .= ":\n\n";
  if ($changes['details']['type'] != 'None')
    $out_com .= "[{$changes['details']['type']}]\n";
  $out_com .= markup_ascii ($changes['details']['add']);
  return "$out$out_com";
}

function format_change_fields ($changes)
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
      # with "realdetails", a field that doesn't exist.
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
function format_item_changes ($changes, $item_id, $res)
{
  $group_id = $res['group_id'];
  $public = trackers_item_is_public ($res['privacy'], $group_id);
  $item_group = utils_get_tracker_prefix (ARTIFACT) . " #$item_id"
    . " (group " . group_getunixname ($group_id) . ")";
  $out = format_change_fields ($changes);

  if ($out)
    $out = "Update of $item_group:\n\n$out";

  $out = format_change_comments ($out, $changes, $item_group, $item_id);
  return format_change_files ($out, $changes, $item_group, $res['privacy'] != 2);
}

function format_item_file_header ($list_is_empty, $ascii)
{
  global $HTML;
  if ($list_is_empty)
    {
      if ($ascii)
        return '';
      return
        '<span class="warn">' . _("No files currently attached") . '</span>';
    }
  if ($ascii)
    return "    _______________________________________________________\n"
      . "File Attachments:\n\n";
  return $HTML->box_top (_("Attached Files"), '', 1);
}

function format_item_file_details ($row, $href)
{
  $lnk = "<a href=\"$href\">file #{$row['file_id']}: &nbsp;";
  # TRANSLATORS: the first argument is file name, the second is user's name.
  $out = sprintf (_('<!-- file -->%1$s added by %2$s'),
    $lnk . utils_specialchars ($row['filename']) . '</a>',
    utils_user_link ($row['user_name'])
  );
  $out .= ' <span class="smaller">(' . utils_filesize (null, $row['filesize']);
  if ($row['filetype'])
    $out .= ' - ' . $row['filetype'];
  if ($row['description'])
    $out .= ' - ' . markup_basic ($row['description']);
  return "$out)</span>";
}

function format_item_attachment_html ($row, $may_delete, $url)
{
  global $php_self;
  $ret = format_item_file_details ($row, $url);
  if (!$may_delete)
    return $ret;
  $cred = format_file_credentials ('&amp;');
  $del = "<span class='trash'><a href=\"$php_self?func=delete_file"
    . "&amp;item_id={$row['item_id']}&amp;item_file_id={$row['file_id']}"
    . "$cred\">" . html_image_trash (['class' => 'icon']) . '</a></span>';
  return $del . $ret;
}

function format_list_item_files ($result, $may_delete, $ascii, $public)
{
  $out = '';
  for ($i = 0; $row = db_fetch_array ($result); $i++)
    {
      $r = $row;
      $url = format_file_url (
        ['id' => $row['file_id'], 'name' => $row['filename']], $public
      );
      if ($ascii)
        {
          list ($entry, $description) = format_attachment ($row, $public);
          $out .= $entry;
        }
      else
        $out .= '<div class="' . utils_altrow ($i) . '">'
          . format_item_attachment_html ($row, $may_delete, $url) . "</div>\n";
    }
  if ($ascii && !empty ($description))
    # The description is common for all files in the original submission,
    # so only write it once.
    $out = "$description\n\n$out";
  if ($ascii && $public)
    $out .= "\n" . format_file_agpl_notice ();
  return $out;
}

# Show the files attached to this tracker item.
function format_item_attached_files ($item_id, $group_id, $ascii, $public)
{
  global $HTML;
  $result = trackers_data_get_attached_files ($item_id);
  $out = format_item_file_header (!db_numrows ($result), $ascii);
  if (!db_numrows ($result))
    return $out;

  $manager = member_check (0, $group_id, 2);
  $out .= format_list_item_files ($result, $manager, $ascii, $public);
  if (!$ascii)
    $out .= $HTML->box_bottom (1);
  return  $out;
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
  $com_arr = [
    TRACKERS_CC_SUBMITTED => _('Submitted the item'),
    TRACKERS_CC_COMMENTED => _('Posted a comment'),
    TRACKERS_CC_UPDATED => _('Updated the item'),
    TRACKERS_CC_VOTED => _('Voted in favor of this item'),
    TRACKERS_CC_SUBSCRIBED => _('Subscribed to discussion')
  ];

  $comment = $row['comment'];
  if (array_key_exists ($comment, $com_arr))
    return $com_arr[$comment];
  # Remove the space that trackers_extract_cc_comment() may have added.
  $regex = '/^' . TRACKERS_CC_AFFIX . TRACKERS_CC_REGEX . '$/';
  if (preg_match ($regex, $comment))
    return substr ($comment, strlen (TRACKERS_CC_AFFIX));
  return $comment;
}

function format_item_cc_list_user_data ($group_id)
{
  $ret['manager'] = member_check (0, $group_id, 2);
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
  $cc_list = [];
  $result = trackers_data_get_cc_list ($item_id);
  $n = db_numrows ($result);
  $out = format_item_cc_list_header ($n);
  if (!$n)
    return $out;
  $user_data = format_item_cc_list_user_data ($group_id);
  $i = 0;
  while ($row = db_fetch_array ($result))
    {
      $out .= format_item_cc_list_entry ($row, $item_id, $user_data, $i++);
      $cc_list[$row['email']] = $row;
    }
  $out .= $HTML->box_bottom (1);
  return [$out, $cc_list];
}

function format_item_subscribe ($item_id, $group_id)
{
  $name = user_getname ();
  return '<p>' . _('You are not subscribed to this discussion.') . ' '
    . form_submit (_("Subscribe"), "submit")  . "</p>\n";
}

function format_item_unsubscribe ($item_id, $group_id)
{
  return '<p>' . _('You are subscribed to this discussion.') . ' '
    . form_submit (_("Unsubscribe"), "submit") . "</p>\n";
}

function format_item_subscription ($item_id, $group_id, $cc_list)
{
  if (!user_isloggedin ())
    return;
  $subscribed = false;
  foreach ([user_getid (), user_getname (), user_getemail ()] as $email)
    if (!empty ($cc_list[$email]))
      $subscribed = true;
  $ret = form_tag () . form_hidden ([
    'group_id' => $group_id, 'item_id' => $item_id,
    'func' => $subscribed? 'unsubscribe': 'subscribe',
  ]);
  if ($subscribed)
    $ret .= format_item_unsubscribe ($item_id, $group_id);
  else
    $ret .= format_item_subscribe ($item_id, $group_id);
  return "$ret</form>\n";
}
?>
