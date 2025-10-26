<?php
# Modify items.
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

$dirname = dirname (__FILE__);
require_once ("$dirname/../trackers/show.php");
require_once ("$dirname/../trackers/format.php");
require_once ("$dirname/../trackers/votes.php");

require_directory ("search"); # Need search functions.

function restore_feedback ()
{
  $query = sane_import ('get', ['pass' => ['feedback', 'ffeedback']]);
  if ($query['feedback'])
    fb ($query['feedback']);
  if ($query['ffeedback'])
    fb ($query['ffeedback'], 1);
}
restore_feedback ();

$fields_per_line = 2;
$max_size = 40;
$max_rows = 25;
$ro_fields = !$is_trackeradmin;

$result = db_execute ("
  SELECT * FROM " . ARTIFACT . " WHERE bug_id = ? AND group_id = ?",
  [$item_id, $group_id]
);

if (db_numrows ($result)  <= 0)
  {
    trackers_footer ();
    exit (0);
  }
$res_arr = db_fetch_array ($result);

$submitter = $res_arr['submitted_by'];
$own_post = user_isloggedin () && $submitter == user_getid ();
if ($res_arr['spamscore'] >= 5 && !($is_trackeradmin || $own_post))
  exit_permission_denied ();

$item_discussion_lock = $res_arr['discussion_lock'];
$enable_comments = !$item_discussion_lock || $is_trackeradmin;
$preambles = [];
foreach (['comment', 'file'] as $pre)
  $preambles[] = ARTIFACT . "_{$pre}_preamble";

$preambles = group_get_preference  ($group_id, $preambles);

$fill_fields_from_request = $preview || !empty ($form_check_submit);
if ($fill_fields_from_request)
  $field_list = trackers_extract_field_list ();

# Item name, converting bugs to bug.
# (Ideally, the artifact bugs should be named bug).
$item_name = utils_get_tracker_prefix (ARTIFACT) . " #$item_id";
$item_link = utils_link ("?$item_id", $item_name);

$privacy = $res_arr['privacy'];
# Check whether this item is private or not. If it is private, show only to
# the submitter or to people that got the right to see private items
$private_intro = '';
if ($privacy == "2")
  {
    if (member_check_private (0, $group_id))
      {
        # Nothing worth being mentioned.
      }
    elseif ($submitter == user_getid ())
      $private_intro =
        _("This item is private. However, you are allowed to read it "
          . "as you submitted it.");
    else
      exit_error (
        _("This item is private. You are not listed as member\n"
          . "allowed to read private items.")
      );
  }

if (!group_restrictions_check ($group_id, ARTIFACT, TRACKER_EVENT_COMMENT))
  {
    $private_intro .= ' '
       . _("You are not allowed to post comments on this tracker "
         . "with your current\nauthentication level.");
    $enable_comments = false;
  }

if (in_array ($func, ['subscribe', 'unsubscribe']))
  trackers_handle_item_subscription ($func, $item_id, $group_id);

trackers_header (
  ['title' => "$item_name, " . utils_cutstring ($res_arr['summary'])]
);

# Check if the user have a specific role.
function check_member ($role)
{
  return member_check (0, $GLOBALS['group_id'], $role, true);
}

$help_on_member_strings = [
  [
    2,
    _("You are both technician and manager for this tracker."),
    [
      _("technician") =>
      _("can be assigned items, cannot change status or priority"),
      _("manager") => _("fully manage the items"),
    ]
  ],
  [
    1,
    _("You are technician for this tracker."),
    [
      _("technician") =>
      _("you can be assigned tracker's items, but you cannot reassign "
        . "items, change priority, open nor close")
    ]
  ],
  [
    3,
    _("You are manager for this tracker."),
    [
      _("manager") =>
        _("you can fully manage the trackers items, including assigning "
          . "items to technicians, reassigning items over trackers and "
          . "groups, changing priority, opening and closing items")
    ]
  ]
];

function help_on_member ()
{
  foreach ($GLOBALS['help_on_member_strings'] as $item)
    if (check_member ($item[0]))
      {
        print '<p>';
        print utils_help ($item[1], $item[2]);
        print "</p>\n";
        return;
      }
}

list ($item_cc_text, $item_cc_list) = format_item_cc_list ($item_id, $group_id);
print format_item_subscription ($item_id, $group_id, $item_cc_list);

$is_manager = member_check (0, $group_id, 3);
help_on_member ();

if (!empty ($private_intro))
  print "<p>$private_intro</p>\n";

$class = utils_get_priority_color (
  $res_arr['priority'], $res_arr['status_id']
);
$caption = "<i>$item_link</i>: {$res_arr['summary']}";
print html_h (1, $caption, ['class' => $class]);

print form_tag (
  ['enctype' => 'multipart/form-data', 'name' => 'item_form'], "?$item_id"
);
$hidden = [
  "group_id" => $group_id,
  'func' => $enable_comments? 'postmoditem': 'detailitem'
];
print form_hidden ($hidden);

# Colspan explanation:
#
#  We want the following, twice much space for the value than for the label:
#
#  | Label:  Value________| Label:  Value______ |
#  | Label:  Value_____________________________ |
$button_attr =
  "colspan='$fields_per_line' width='50%' align='center' valign='top'";

print "\n\n<table cellpadding='0' width='100%'>\n"
  . "<tr>\n<td class='preinput' width='15%'>"
  . _("Submitter:") . "&nbsp;</td>\n<td width='35%'>"
  . utils_user_link (user_getname ($submitter), user_getrealname ($submitter))
  . "</td>\n<td $button_attr><span class='noprint'>"
  . form_submit (_("Submit changes and browse items"), "submit", 'class="bold"')
  . "</span></td>\n</tr>\n<tr>\n<td class='preinput' width='15%'>"
  # TRANSLATORS: This is a label for dates.
  . _("Submitted:") . "&nbsp;</td>\n<td width='35%'>"
  . utils_format_date ($res_arr['date'])
  . "</td>\n<td $button_attr><span class='noprint'>"
  . form_submit (_("Submit changes and return to this item"), "submitreturn")
  . "</span></td>\n</tr>\n";
print "\n<tr>\n<td class='preinput' width='15%'>";
$votes = $res_arr['vote'];
if ($votes)
  print _("Votes:")
    . "</td>\n<td width='35%'><a href='#votes'>$votes</a></td>\n";
else
  print "&nbsp;</td>\n<td width='35%'>&nbsp;</td>\n";
print "<td $button_attr><span class='noprint'>"
  . form_submit (_('Preview'), 'preview') . "</span></td>\n</tr>\n";

print '<tr><td colspan="' . ($fields_per_line * 2) . "\">&nbsp;</td></tr>\n";

# Now display the variable part of the field list (depend on the group).
# Some fields must be displayed differently according to the user role.

function mandatory_sign ($submitter, $field)
{
  global $group_id, $enable_comments;
  if (!$enable_comments)
    return '';
  $star = '<span class="warn"> *</span>';
  $flag = trackers_data_mandatory_flag ($field);
  if ($flag == 3)
    return $star;
  if ($flag != 0)
    return '';
  if (trackers_check_is_shown_to_submitter ($field, $group_id, $submitter))
    return $star;
  return '';
}

$item_assigned_to = null;
$i = 0; # Field counter.

while ($field_name = trackers_list_all_fields ())
  {
    # If the field is not used by the group, skip it.
    if (!trackers_data_is_used ($field_name))
      continue;

    # If the field is a special field (not summary) then skip it.
    if (trackers_data_is_special ($field_name))
      {
        if (!$is_trackeradmin)
          continue;
        # If we are on the cookbook, details (a special field) must be
        # allowed too.
        if (
          $field_name != 'summary'
          && !(ARTIFACT == 'cookbook' && $field_name == 'details')
        )
          continue;
      }

    #  Print the originator email field only if the submitter was anonymous.
    if ($field_name == 'originator_email' && $submitter != '100')
      continue;

    if ($field_name == 'discussion_lock' && !$is_manager)
      continue;

    # Display the bug field.
    # If field size is greatest than max_size chars then force it to
    # appear alone on a new line or it won't fit in the page.

    # Look for the field value in the database only if we missing
    # its values. If we already have a value, we are probably in
    # step 2 of a search.

    # If nocache is set, we were explicitely asked to rely only
    # on database content.
    if (!isset ($nocache))
      $nocache = false;
    if ((empty ($$field_name) || $nocache)
      && !($fill_fields_from_request && $is_trackeradmin)
    )
      $field_value = $res_arr[$field_name];
    else
      {
        if ($fill_fields_from_request && isset ($field_list[$field_name]))
          {
            $$field_name = $field_list[$field_name];
            if (trackers_data_is_date_field ($field_name))
              {
                list ($yr, $mn, $dy) = preg_split ("/-/", $$field_name);
                $$field_name = mktime (0, 0, 0, $mn, $dy, $yr);
              }
          }
        if (isset ($$field_name))
          $field_value = utils_specialchars ($$field_name);
      }
    list ($sz,) = trackers_data_get_display_size ($field_name);
    $label = trackers_field_label_display (
      $field_name, $group_id, false, false
    );
    if ($field_name == 'assigned_to')
      {
        $item_assigned_to = trackers_field_display (
          $field_name, $group_id, $field_value, false, false, true
        );
        $value = utils_user_link (
          user_getname ($field_value), user_getrealname ($field_value)
        );
      }
    # Some fields must be displayed read-only,
    # assigned_to, status_id and priority too, for technicians
    # (if super_user, do nothing).
    if (!$is_manager
        && (in_array ($field_name,
            ['status_id', 'assigned_to', 'priority', 'originator_email'])))
      {
        $value = trackers_field_display (
          $field_name, $group_id, $field_value, false, false, true
        );
        if ($field_name == 'originator_email')
          $value = utils_email_basic ($value);
      }
    else
      $value = trackers_field_display (
        $field_name, $group_id, $field_value, false, false,
        $ro_fields, false, false, _("None"), false, _("Any"), true
      );

    $label .= mandatory_sign ($submitter, $field_name);
    $field_class = '';

    cookbook_print_form ($field_name, $field_class);

    if ($previous_form_bad_fields
        && array_key_exists ($field_name, $previous_form_bad_fields))
      $field_class = ' class="highlight"';
    $td = "<td valign='middle'$field_class";

    if ($sz > $max_size)
      {
        $row_class = trackers_get_row_class ($field_name);

        print "\n<tr$row_class>$td width='15%'>$label</td>\n$td colspan=\""
          . (2 * $fields_per_line - 1) . '" width="75%">'
          . "$value</td>\n</tr>\n";
          $i = 0;
          continue;
      }
    # Field getting half of a line for itself.
    if (!($i % $fields_per_line))
      {
        # Every one out of two, prepare the background color change.
        # We do that at this moment because we cannot be sure
        # there will be another field on this line.
        $row_class = trackers_get_row_class ($field_name);
        print  "\n<tr$row_class>";
      }
    print "$td width='15%'>$label</td>\n$td width='35%'>$value</td>\n";
    print (++$i % $fields_per_line? "\n": "</tr>\n");
  } # while ($field_name = trackers_list_all_fields ())

print "</table>\n";
if ($enable_comments)
  print '<div class="warn"><span class="smaller">* '
    . _("Mandatory Fields") . '</span></div>';

$is_deployed = [];

$is_deployed["postcomment"] = false;
if ($preview)
  $is_deployed["postcomment"] = $enable_comments;
$is_deployed["discussion"] = $is_deployed["attached"] = true;
$is_deployed["dependencies"] = true;
$is_deployed["cc"] = $is_deployed["votes"] = $is_deployed["reassign"] = false;

# If at the second step of any two-step activity (add deps, reassign,
# multiple canned answer), deploy only the relevant:
# first set them all to false without question and then set to true only
# the relevant.
if ($depends_search || $reassign_change_group_search)
  {
    foreach ($is_deployed as $key => $value)
      $is_deployed[$key] = false;

    if ($depends_search)
      $is_deployed["dependencies"] = true;
    if ($reassign_change_group_search)
      $is_deployed["reassign"] = true;
  }

if ($comment === null)
  $comment = '';

if (isset ($quote_no))
  {
    $quote = trackers_data_quote_comment ($item_id, $quote_no);
    $cr = $canned_response;
    if ($quote === false)
      {
        # No comment to quote found, probably quoting the preview.
        $preview = true;
        $quote = '';
        if (!empty ($canned_response))
          $canned_response = [];
      }
    $comment = trackers_data_append_canned_response ("$comment$quote", $cr);
  }
if (!empty ($comment))
  $is_deployed['postcomment'] = $enable_comments;

function print_comment_types ($group_id, $comment_type_id, $comment_types)
{
  global $preview, $anon_check_failed;

  if (db_numrows ($comment_types) <= 1)
    {
      unset ($GLOBALS['comment_type_id']);
      return 0;
    }
  print "<span class='preinput'>";
  print html_label ('comment_type_id', _("Comment type:")) . "</span>\n";

  $checked = '';
  if (($preview || !empty ($anon_check_failed)) && !empty ($comment_type_id))
    $checked = $comment_type_id;
  $box = trackers_field_box ('comment_type_id', '', $group_id, $checked, true);
  print "$box<br />\n";
  return 1;
}

function print_canned_box ($group_id, $canned, $size, $res_canned)
{
  global $sys_home;
  $group_admin = user_is_group_admin ($group_id);
  print "<span class='preinput'>";
  print html_label ('canned_response[]', _("Canned response:"))
    . "</span><br />\n";
  print trackers_canned_response_box ($group_id, $canned, $size, $res_canned)
    . "<br />\n";
  if (!$group_admin)
    return;
  print "&nbsp;&nbsp;&nbsp;<a class='smaller' href=\"$sys_home"
    . ARTIFACT . "/admin/field_values.php?group_id=$group_id"
    . '&amp;create_canned=1">' . _("Define a new canned response") . '</a>';
}

function print_comment_type_and_canned (
  $group_id, $canned_response, $ct_id, $res_canned
)
{
  global $sys_home;
  if (!user_ismember ($group_id))
    return;
  $canned_num = db_numrows ($res_canned);
  $comment_types =
    trackers_data_get_field_predefined_values ('comment_type_id', $group_id);
  if ($canned_num == 0 && db_numrows ($comment_types) < 2)
    return;
  print "<p class='noprint'>";
  if ($canned_num)
    print "<br />\n";
  $n = print_comment_types ($group_id, $ct_id, $comment_types);
  if ($canned_num)
    print_canned_box ($group_id, $canned_response, 11 - $n, $res_canned);
  print "</p>\n";
}

function print_comment_box ($group_id, $comment, $have_canned)
{
  $float = '';
  if (user_ismember ($group_id) && $have_canned)
    $float = ' floatleft';
  print "<p class='noprint$float'><span class='preinput'> "
    . _("Add a New Comment") . ' ' . markup_info ("rich");
  print form_submit (_('Preview'), 'preview')
    . "</span><br />&nbsp;&nbsp;&nbsp;\n";
  print trackers_field_textarea ('comment', utils_specialchars ($comment),
    0, 0, _("New comment"));
  print "</p>\n";
}

if ($enable_comments)
  {
    print html_hidsubpart_header (
      "postcomment", _("Post a Comment"), $is_deployed['postcomment']
    );
    if (!empty ($preambles[ARTIFACT . '_comment_preamble']))
      print markup_rich ($preambles[ARTIFACT . '_comment_preamble']);

    $res_canned = trackers_data_get_canned_responses ($group_id);
    $canned_num = db_numrows ($res_canned);
    print_comment_box ($group_id, $comment, $canned_num);
    print_comment_type_and_canned (
      $group_id, $canned_response, $comment_type_id, $res_canned
    );
    print html_hidsubpart_footer ();
  } # $enable_comments
if ($item_discussion_lock)
  {
    print '<p class="warn">' . _("Discussion locked!");
    if ($is_trackeradmin)
      print ' '
        . _("Your privileges however allow to override the lock.");
    print "</p>\n";
  }
print html_hidsubpart_header ("discussion", _("Discussion"));
if ($reverse_comment_order)
  print form_hidden (['reverse_order' => 1]);

$new_comment = [];
if ($preview)
  {
    $new_comment['user_id'] = user_getid ();
    $new_comment['user_name'] = user_getname (user_getid (), 0);
    $new_comment['realname'] = user_getname (user_getid (), 1);
    $new_comment['date'] = time ();
    if (empty ($comment_type_id))
      $new_comment['comment_type'] = false;
    else
      $new_comment['comment_type'] =
        trackers_data_get_cached_field_value (
          'comment_type_id', $group_id, $comment_type_id
        );
    $comm = trackers_data_append_canned_response ($comment, $canned_response);
    if (!empty ($comm))
      $comm = trackers_encode_value (utils_specialchars ($comm));
    $new_comment['old_value'] = $comm;
    $new_comment['bug_history_id'] = -1;
    $new_comment['spamscore'] = '0';
  }
print show_item_details (
  $item_id, $group_id, $item_assigned_to, $new_comment, $enable_comments
);
print "<p>&nbsp;</p>\n";
print html_hidsubpart_footer ();

print html_hidsubpart_header ("attached", _("Attached Files"));

if (!empty ($preambles[ARTIFACT . '_file_preamble']))
  print markup_rich ($preambles[ARTIFACT . '_file_preamble']);

$public = trackers_item_is_public ($privacy, $group_id);
show_item_attached_files ($item_id, $group_id, $public);

function file_input ($n)
{
  if ($n % 2)
    print "<br />\n&nbsp;&nbsp;&nbsp;";
  print
    "<input type='file' name='input_file$n' size='10' title=\""
    . _("File to attach") . '" /> ';
}

if ($enable_comments)
  {
    print '<p class="noprint">' . trackers_upload_size_limit_note ();
    print "</p>\n";
    print '<p class="noprint"><span class="preinput"> '
      . _("Attach Files:") . "</span>";

    for ($i = 1; $i < 5; $i++)
      file_input ($i);

    print "\n<br />\n"
      . '<span class="preinput">' . _("Comment:")
      . "</span><br />\n&nbsp;&nbsp;&nbsp;"
      . '<input type="text" name="file_description" title="'
      . _("File description") . "\" size='60' maxlength='255' />\n</p>\n";
  }

print "<p>";

print "</p>\n<p>&nbsp;</p>\n";
print html_hidsubpart_footer ();

# Deployed by default, important item info.
print html_hidsubpart_header ("dependencies", _("Dependencies"));
if ($is_trackeradmin)
  {
    print '<p class="noprint"><span class="preinput">';
    print html_label ('depends_search',
      $depends_search?
        _("New search, in case the previous one was not satisfactory "
          . "(to\nfill a dependency against):"):
        _("Search an item (to fill a dependency against):")
    );
    print "</span><br />\n&nbsp;&nbsp;&nbsp;"
      . form_input ('text',  'depends_search', $depends_search,
          "size='40' maxlength='255'"
        ) . "<br />\n";

    $tracker_select =
    '&nbsp;&nbsp;&nbsp;<select title="' . _("Tracker to search in")
      . '" name="depends_search_only_artifact">';

    # Generate the list of searchable trackers.
    $tracker_list = [
      'all'     => _("any tracker"),
      'support' => _("the support tracker only"),
      'bugs'    => _("the bug tracker only"),
      'task'    => _("the task manager only"),
      'patch'   => _("the patch manager only"),
    ];

    foreach ($tracker_list as $option_value => $text)
      $tracker_select .=
        form_option ($option_value, $depends_search_only_artifact, $text);
    $tracker_select .= "</select>\n";

    $group_select = '<select title="' . _("Whether to search in any group")
      . '" name="depends_search_only_group">';

    # By default, search restricted to the group (lighter for the CPU,
    # probably also more accurate).
    $group_select .=
      # TRANSLATORS: this string is used in the context like
      # "search an item of [the bug tracker only] of [any group]".
      form_option ('any', $depends_search_only_group, _("any group"));
    $selected = 'val';
    if ($depends_search_only_group != 'any')
      $selected = 'notany';
    # TRANSLATORS: this string is used in the context like
    # "search an item of [the bug tracker only] of [this group only]".
    $group_select .= form_option ('notany', $selected, _("this group only"))
      . "</select>&nbsp;";

    # TRANSLATORS: the first argument is tracker type (like the bug tracker),
    # the second argument is either 'this group only' or 'any group'.
    printf (_('Of %1$s of %2$s'), $tracker_select, $group_select);

    if ($depends_search)
      print form_submit (_("New search"), "submit");
    else
      print form_submit (_("Search"), "submit");

    trackers_search_dependencies (
      $depends_search, $depends_search_only_artifact,
      $depends_search_only_group
    );
    print "</p>\n";
  } # if ($is_trackeradmin)
print show_item_dependency ($item_id);
print show_dependent_item ($item_id);
print "\n<p>&nbsp;</p>\n";
print html_hidsubpart_footer ();

print
  html_hidsubpart_header ("cc", _("Mail Notification Carbon-Copy List"));
if ($is_trackeradmin)
  {
    print '<p class="noprint">';
    # TRANSLATORS: the argument is site name (like Savannah).
    printf (
      _("(Note: for %s users, you can use their login name\n"
        . "rather than their email addresses.)"),
      $GLOBALS['sys_name']
    );

    print "</p>\n"
      . "<p class='noprint'><span class='preinput'>"
      . html_label ('add_cc', _("Add Email Addresses (comma as separator):"))
      . "</span><br />\n&nbsp;&nbsp;&nbsp;"
      . form_input ('text', 'add_cc', $add_cc,  'size="40"')
      . "&nbsp;&nbsp;&nbsp;\n<br />\n<span class='preinput'>"
      . html_label ('cc_comment', _("Comment:"))
      . "</span><br />\n&nbsp;&nbsp;&nbsp;"
      . form_input ('text', "cc_comment", $cc_comment,
          'size="40" maxlength="255"')
      . "\n";
    print "<p>&nbsp;</p>\n";
  }
print $item_cc_text;
print "<p>&nbsp;</p>\n";
print html_hidsubpart_footer ();

function display_votes ($group_id, $item_id, $votes,  $new_vote, $lock)
{
  if (!trackers_data_is_used ("vote"))
    return;
  print html_hidsubpart_header ("votes", _("Votes"));

  print '<p>';
  printf (
    ngettext (
      "There is %s vote so far.", "There are %s votes so far.", $votes
    ),
    $votes
  );

  print ' '
    . _("Votes easily highlight which items people would like to see "
        . "resolved\nin priority, independently of the priority of the item "
        . "set by tracker\nmanagers.");
  $end = "</p>\n<p>&nbsp;</p>\n" .  html_hidsubpart_footer ();
  if ($lock)
    {
      print $end;
      return;
    }
  print "</p>\n<p class='noprint'>";
  if (!(trackers_data_is_showed_on_add ("vote")
      || member_check (user_getid(), $group_id)))
    {
      print '<span class="warn">' . _("Only group members can vote.")
        . "</span>$end";
      return;
    }
  if (!user_isloggedin ())
    {
       print '<span class="warn">' . _("Only logged-in users can vote.")
         . "</span>$end";
       return;
    }
  $votes_given = trackers_get_user_votes (ARTIFACT, $item_id);
  $votes_remaining = trackers_votes_remaining () + $votes_given;
  if (!$new_vote)
    $new_vote = $votes_given;

  # Show how many vote he already gave and allows to remove
  # or give more votes.
  # The number of remaining points must be 100 - others votes.
  print '<span class="preinput">' . html_label ('new_vote', _("Your vote:"))
    . "</span><br />\n&nbsp;&nbsp;&nbsp;"
    . '<input type="text" name="new_vote" id="new_vote" '
    . "size='3' maxlength='3' value='$new_vote' /> ";
  printf (
    ngettext (
      "/ %s remaining vote", "/ %s remaining votes",
      $votes_remaining),
    $votes_remaining
  );
  print $end;
  return;
}

function specific_reassign_artifact ($art, $title)
{
  $checked = '';
  if (!$GLOBALS['reassign_change_artifact'] && ARTIFACT == $art
      || $GLOBALS['reassign_change_artifact'] == $art)
    $checked = $art;
  return form_option ($art, $checked, $title);
}
display_votes ($group_id, $item_id, $votes, $new_vote, !$enable_comments);

# Reassign an item, if manager of the tracker.
# Not possible on the cookbook manager, cookbook entries are too specific.
if ($is_manager && ARTIFACT != "cookbook")
  {
    # No point in having this part printable.
    print '<span class="noprint">';
    print html_hidsubpart_header ("reassign", _("Reassign this item"));

    $tracker_select = '<select title="' . _("Tracker to reassign to")
                      . '" name="reassign_change_artifact">';
    $title_arr = [
      # TRANSLATORS: this string is used in the context of
      # "Move to the %s".
      "support" => _("<!-- Move to the -->Support Tracker"),
      "bugs" => _("<!-- Move to the -->Bug Tracker"),
      "task" => _("<!-- Move to the -->Task Tracker"),
      "patch" => _("<!-- Move to the -->Patch Tracker"),
    ];
    foreach ($title_arr as $art => $title)
      $tracker_select .= specific_reassign_artifact ($art, $title);

    $tracker_select .= "</select>\n";
    printf (_("Move to the %s"), $tracker_select);

    print "<br /><br />\n<span class='preinput'>";

    if ($reassign_change_group_search)
      print _("New search, in case the previous one was not satisfactory\n"
              . "(to reassign the item to another group):");
    else
      print _("Move to the group:");

    print "</span><br />\n&nbsp;&nbsp;&nbsp;"
      . '<input type="text" title="' . _("Group to reassign item to")
      . '" name="reassign_change_group_search" size="40" maxlength="255" />';
    if ($reassign_change_group_search)
      print form_submit (_("New search"), "submit");
    else
      print form_submit (_("Search"), "submit");

    # Search results, if we are already at step 2.
    if ($reassign_change_group_search)
      {
        print "\n<p><span class='preinput'>";
        printf (
          _("To which group this bug should be reassigned to? This is\n"
            . "the result of your search of '%s' in the database:"),
          utils_specialchars ($reassign_change_group_search));
        print '</span>';

        # Print a null-option, someone may change his mine without having
        # to use the back button of his browser.
        print "<br />\n&nbsp;&nbsp;&nbsp;"
          . '<input type="radio" name="reassign_change_group" '
          . 'value="0" checked="checked" /> '
          . _("Do not reassign to another group.");

        $res = search_run ($reassign_change_group_search, "soft", 0);
        $list_is_empty = true;
        while ($row = db_fetch_array ($res))
          {
            $grp_id = $row['group_id'];
            $ug_name = $row['unix_group_name'];
            $grp_name = $row['group_name'];
            if ($grp_id == $group_id) # Don't reassign to itself.
              continue;
            $list_is_empty = false;
            print "<br />\n&nbsp;&nbsp;&nbsp;"
              . form_input ("radio", "reassign_change_group", $ug_name)
              . " [$ug_name, #$grp_id] $grp_name";
          }
        if ($list_is_empty)
          print "<br />\n<span class='warn'>"
            . _("None found. Please note that only search words of more\n"
                . "than three characters are valid.")
            . '</span>';
      } # if ($reassign_change_group_search)
    print html_hidsubpart_footer ();
    print '</span>';
  } # if ($is_manager && ARTIFACT != "cookbook")

if ($enable_comments)
  {
    # Minimal anti-spam.
    if (!user_isloggedin ())
      print '<p class="noprint">'
        . html_label ("check",
            _("Please enter the title of <a\n"
              . "href=\"https://en.wikipedia.org/wiki/George_Orwell\">"
              . "George Orwell</a>'s famous\ndystopian book (it's a date):")
          )
        . " <input type='text' id='check' name='check' /></p>\n";

    print '<div align="center" class="noprint">'
      . form_submit (_("Preview"), "preview")
      . ' '
      . form_submit (_("Submit changes and browse items"), "submit", 'class="bold"')
      . ' '
      . form_submit (_("Submit changes and return to this item"), "submitreturn")
      . "</div>\n";
  }
print "</form>\n";
print html_hidsubpart_header ("history", _("History"));
show_item_history ($item_id, $group_id);
print html_hidsubpart_footer ();

trackers_footer ();
?>
