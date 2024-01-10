<?php
# Form functions.
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

$dir_name = dirname (__FILE__);
require_once ("$dir_name/spam.php");
if (!function_exists ("random_bytes"))
  require_once ("$dir_name/random-bytes.php");

function form_get_id ()
{
  static $form_id = null;
  if (!empty ($form_id))
    return $form_id;
  $form_id = md5 (random_bytes (8));
  $result = db_autoexecute ('form',
    [ 'form_id' => $form_id, 'timestamp' => time (),
      'user_id' => user_getid ()],
    DB_AUTOQUERY_INSERT
  );
  if (db_affected_rows ($result) != 1)
    fb (_("System error while creating the form, report it to admins"), 1);
  return $form_id;
}

function form_id_input ($method)
{
  if ($method != 'post')
    return '';
  return form_hidden (['form_id' => form_get_id ()]);
}

# To use this form that disallow duplicates:
#    - form_header must be used on the form
#    - form_check must be used before any insert in the DB after submission

# Start the form with unique ID, store it in the database.
function form_header ($action = null, $method = "post", $extra = false)
{
  if ($action === null)
    $action = $GLOBALS["php_self"];
  if ($extra)
    $extra = " $extra";
  return "\n<form action=\"$action\" method=\"$method\"$extra>\n"
    . form_id_input ($method);
}

# Similar to form_header, but with a different argument parser.
function form_tag ($args = [], $action_suffix = '')
{
  $def_args = ['action' => $GLOBALS['php_self'], 'method' => 'post'];

  foreach ($def_args as $k => $v)
    if (empty ($args[$k]))
      $args[$k] = $def_args[$k];

  $args['action'] .= $action_suffix;
  $attr = '';
  foreach ($args as $k => $v)
    $attr .= " $k=\"$v\"";

  return "<form $attr>\n" . form_id_input ($args['method']);
}

# Usual input.
function form_input ($type, $name, $value = "", $extra = false)
{
  if ($value !== "")
    $value = 'value="' . utils_specialchars ($value) . '"';
  if ($extra)
    $extra = " $extra";
  $id_attr = " id=\"$name\"";
  if ($type == 'hidden' || $type == 'submit' || $type == 'radio')
    $id_attr = '';
  return "<input type=\"$type\"$id_attr name=\"$name\" $value$extra />";
}

function form_set_label_attr (&$attr)
{
  if (!array_key_exists ('label', $attr))
    return null;
  $ret = $attr['label'];
  unset ($attr['label']);
  return $ret;
}

function form_radio ($name, $value, $attr)
{
  $label = form_set_label_attr ($attr);
  $extra = '';
  if (!empty ($attr['checked']))
    $extra .= "checked='checked' ";
  if (empty ($attr['id']) && $label)
    $attr['id'] = "val_{$value}_$name";
  if (!empty ($attr['id']))
    $extra .= "id=\"{$attr['id']}\"";
  $ret = form_input ('radio', $name, $value, $extra);
  if (null === $label || empty ($attr['id']))
    return $ret;
  return $ret . html_label ($attr['id'], $label);
}

function form_checkbox ($name, $is_checked = 0, $attr = [])
{
  $label = form_set_label_attr ($attr);
  $extra = '';
  if ($is_checked)
    $extra .= ' checked="checked"';
  if (!empty ($attr))
    foreach ($attr as $k => $v)
      $extra .= " $k=\"$v\"";
  $val = '1';
  if (isset ($attr['value']))
    $val = '';
  $ret = form_input ('checkbox', $name, $val, $extra);
  if (null === $label)
    return $ret;
  return $ret . html_label ($name, $label);
}

function form_option ($value, $selected_value = NULL, $label = NULL)
{
  if ($label === NULL)
    $label = $value;
  $ret = "<option value=\"$value\"";
  if ($selected_value !== NULL)
     {
       if (!is_array ($selected_value))
         $selected_value = [$selected_value];
       if (in_array ($value, $selected_value))
         $ret .= ' selected="selected"';
     }
  return "$ret>$label</option>\n";
}

function form_hidden ($name_val)
{
  $ret = '';
  foreach ($name_val as $name => $val)
    $ret .= "<input type='hidden' name=\"$name\" value=\"$val\" />\n";
  return $ret;
}

# Special input: textarea.
function form_textarea ($name, $value="", $extra=false)
{
  if ($extra)
    $extra = " $extra";

  return "\n<textarea id=\"$name\" name=\"$name\"$extra>$value</textarea>";
}

# Add submit button.
function form_submit ($text = false, $submit_name = "update", $extra = false)
{
  global $int_trapisset;
  if (!$text)
    $text = _("Submit");

  # Add a trap for spammers: a text input that will have to be kept empty.
  # This won't prevent tailored bots to spam, but that should prevent
  # the rest of them, which is good enough (task #4151).
  # Sure, some bots will someday implement CSS support, but the ones that does
  # not will not disappear as soon as this happen.
  $trap = '';
  if (empty ($int_trapisset) && !user_isloggedin ())
    {
      $trap = " " . form_input ("text", "website", "http://");
      $int_trapisset = true;
    }
  return form_input ("submit", $submit_name, $text, $extra) . $trap;
}

# Close the form, with submit button.
function form_footer ($text = false, $submit_name = "update")
{
  return "\n<div class='center'>\n" . form_submit ($text, $submit_name)
    . "</div>\n</form>\n";
}

# Check whether the trap field has been filled. If so, refuse the post.
# This test should probably be made before remove form id, to be
# dumbuser-compliant.
function form_check_nobot ()
{
  extract (sane_import ('request', ['pass' => 'website']));
  if (in_array ($website, ["", "http://"]))
    return;
  # Not much explanation on the reject, since we are hunting spammers.
  exit_log ("filled the spam trap special field");
  exit_missing_param ();
}

function form_preliminary_check ($form_id)
{
  form_check_nobot ();
  if (empty ($form_id))
    exit_missing_param (['form_id']);
}

# Remove form_id from the database; make sure it belongs to the current
# user.  Return 0 in case of success, else 1.
function form_reset_form_id ($form_id)
{
  $result = db_execute ("DELETE FROM form WHERE user_id = ? AND form_id = ?",
    [user_getid (), $form_id]
  );
  if (db_affected_rows ($result))
    return 0;
  fb (_("Duplicate Post: this form was already submitted."), 1);
  return 1;
}

# Check whether this is a duplicate or not: exit when the form_id is absent
# in the DB, which may mean that it has already been submitted (user's mistake)
# or has never been registered (CSRF).
function form_check ()
{
  $form_id = '';
  extract (sane_import ('post', ['hash' => 'form_id']));
  form_preliminary_check ($form_id);
  # See Savannah bug #6983.
  # We must clean the form ID right now.  Originally, form ID was deleted
  # only when we were sure that the form was posted.
  #
  # However, since apache & all are multithreaded, you can end up with the
  # case that the delay between the initial check and the end of the form
  # is long enough to make possible a duplicate.
  #
  # Now, the check will remove the ID.  If the remove fail, it means that
  # the form ID no longer exists and then we exit.  We will have only one
  # SQL request, reducing as much as possible delays.
  if (form_reset_form_id ($form_id))
    exit_error (_("Exiting"));
}
?>
