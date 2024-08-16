<?php
# Functions for checking form_id.
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
  exit_if_missing ('form_id', ['form_id' => $form_id]);
}

function form_check_query ($prefix, $args)
{
  return db_execute (
    "$prefix FROM form WHERE user_id = ? AND form_id = ?", $args
  );
}

# Exit with error unless form_id exists and belongs to the user defined
# in file_uid; else return normally.  When $assert_uid, make sure additionally
# that current user ID is the same as provided in file_uid.
function form_check_id ($assert_uid = false)
{
  $v = sane_import ('request', ['digits' => 'file_uid', 'hash' => 'form_id']);
  exit_if_missing (['form_id', 'file_uid'], $v);
  if ($assert_uid && user_getid () != $v['file_uid'])
    exit_permission_denied ();
  $result = form_check_query ("SELECT *", [$v['file_uid'], $v['form_id']]);
  if (db_numrows ($result))
    return;
  exit_error (_("Form ID is absent in the database"));
}

# Remove form_id from the database; make sure it belongs to the current
# user.  Return 0 in case of success, else 1.
function form_reset_form_id ($form_id)
{
  $result = form_check_query ("DELETE", [user_getid (), $form_id]);
  if (db_affected_rows ($result))
    return 0;
  fb (_("Duplicate Post: this form was already submitted."), 1);
  return 1;
}

# Return false either if $submit_list is null or any listed
# global variables are set; else return true.
function form_vars_empty ($submit_list)
{
  if ($submit_list === null)
    return false;
  if (!is_array ($submit_list))
    $submit_list = [$submit_list];
  foreach ($submit_list as $var)
    if (!empty ($GLOBALS[$var]))
      return false;
  return true;
}

# Return a request array for a summary with unneeded fields removed.
function form_filter_request ()
{
  $suppress_keys = [
    'depends_search', 'depends_search_only_artifact',
    'depends_search_only_group',
    'file_description', 'form_id', 'func',
    'reassign_change_artifact', 'reassign_change_new_group',
    'reassign_change_group_search',
    'submit', 'submitreturn'
  ];
  $ret = [];
  foreach (array_replace ($_GET, $_POST) as $k => $v)
    {
      if (in_array ($k, $suppress_keys) || !is_scalar ($v))
        continue;
      $ret[$k] = $v;
    }
  return $ret;
}

# Sort request fields in a summary of a rejected request:
# put longer entries first, and when the lengths equal, fall back to strcmp.
function form_summary_cmp ($a, $b, $request)
{
  $a_ = strlen (strval ($request[$a]));
  $b_ = strlen (strval ($request[$b]));
  if ($a_ == $b_)
    {
      # Favor shorter keys.
      $b_ = strlen (strval ($a));
      $a_ = strlen (strval ($b));
    }
  if ($a_ == $b_)
    {
      $c = strcmp (strval ($request[$a]), strval ($request[$b]));
      if ($c)
        return $c;
      return strcmp (strval ($a), strval ($b));
    }
  return $a_ > $b_? -1: 1;
}

# Return a HTML summary of a rejected request.
function form_summarize_request ()
{
  $request = form_filter_request ();
  uksort ($request,
    function ($a, $b) use ($request)
    {
      return form_summary_cmp ($a, $b, $request);
    }
  );
  $ret = html_h (1, _("Rejected Request"));
  $defs = [];
  foreach ($request as $k => $v)
    $defs[utils_specialchars ($k)] =
      "<pre>" . utils_specialchars ($v) . "</pre>";
  return $ret . html_dl ($defs);
}

# Check whether this is a duplicate or not: exit when the form_id is absent
# in the DB, which may mean that it has already been submitted (user's mistake)
# or has never been registered (CSRF).
function form_check ($submit_list = null)
{
  if (form_vars_empty ($submit_list))
    return;
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
    exit_error (null, form_summarize_request ());
}
?>
