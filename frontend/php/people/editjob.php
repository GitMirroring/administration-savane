<?php
# Job editor.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2026 Ineiev
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

require_once ('../include/init.php');
require_once ('../include/sane.php');
require_once ('../include/form.php');
require_once ('../include/people/general.php');

extract (sane_import ('request', ['digits' => 'job_id']));
$submits = ['add_job', 'update_job', 'add_to_job_inventory', 'refresh',
  'update_job_inventory', 'delete_from_job_inventory', 'confirm_refresh'
];
$post_digits = [
  'status_id', 'category_id', 'job_inventory_id', 'skill_id',
  'skill_level_id', 'skill_year_id',
];
extract (sane_import ('post',
  [
    'true' => $submits, 'digits' => $post_digits,
    'specialchars' => 'title', 'pass' => 'description',
  ]
));
form_check ($submits);
user_check_group_admin ();
$job_result = people_verify_job_group ($job_id, $group_id);
foreach ($post_digits as $v)
  {
    if (empty ($GLOBALS[$v]))
      $GLOBALS[$v] = 100;
  }

function assert_missing_info ($cond)
{
  if ($cond)
    exit_error (_("error - missing info"), _("Fill in all required fields"));
}

function report_result_get_fail ($idx)
{
  $fails = [
    'insert' => _('JOB insert FAILED'), 'update' => _('JOB update FAILED'),
    'skill update' => _('JOB skill update FAILED'),
    'skill delete' => _('JOB skill delete FAILED'),
    'refresh' => _('JOB refresh FAILED')
  ];
  if (!empty ($fails[$idx]))
    return $fails[$idx];
  return _('Error');
}

function report_result_get_success ($idx)
{
  $success = [
    'insert' =>  _('JOB inserted successfully'),
    'update' =>  _('JOB updated successfully'),
    'skill update' => _("JOB skill updated successfully"),
    'skill delete' => _("JOB skill deleted successfully"),
    'refresh' => _("JOB refreshed successfully")
  ];
  if (!empty ($success[$idx]))
    return $success[$idx];
  return _('Error');
}

function report_result ($result, $task, $test = null)
{
  if ($test === null)
    $test = !$result || db_affected_rows ($result) < 1;
  if ($test)
    {
      fb (report_result_get_fail ($task));
      print db_error ();
      return 1;
    }
  fb (report_result_get_success ($task));
  return 0;
}

# Create a new job.
function add_job ()
{
  global $title, $description, $category_id, $group_id, $job_id;
  assert_missing_info (!$title || !$description || $category_id == 100);
  $result = db_autoexecute ('people_job',
    [ 'group_id' => $group_id, 'created_by' => user_getid (),
      'title' => $title, 'description' => $description, 'date' => time (),
      'status_id' => PEOPLE_JOB_STATUS_OPEN, 'category_id' => $category_id,
    ], DB_AUTOQUERY_INSERT
  );
  if (report_result ($result, 'insert'))
    return;
  $job_id = db_insertid ($result);
}

# Modify job.
function update_job ()
{
  global $title, $description, $category_id, $status_id, $job_id;
  assert_missing_info (!$title || !$description || $category_id == 100
    || $status_id == 100 || !$job_id
  );
  $result = db_autoexecute ('people_job',
    [
      'title' => $title, 'description' => $description,
      'status_id' => $status_id, 'category_id' => $category_id,
    ], DB_AUTOQUERY_UPDATE, "`job_id` = ?", [$job_id]
  );
  report_result ($result, 'update');
}

# Add item to job inventory.
function add_to_job_inventory ()
{
  global $job_id, $skill_id, $skill_level_id, $skill_year_id;
  assert_missing_info ($skill_id == 100 || $skill_level_id == 100
    || $skill_year_id == 100 || !$job_id
  );
  people_add_to_job_inventory (
    $job_id, $skill_id, $skill_level_id, $skill_year_id
  );
}

# Change Skill level, experience etc.
function update_job_inventory ()
{
  global $job_id, $skill_level_id, $skill_year_id, $job_inventory_id;
  assert_missing_info ($skill_level_id == 100 || $skill_year_id == 100
    || !$job_id || !$job_inventory_id
  );

  $result = db_autoexecute ('people_job_inventory',
    ['skill_level_id' => $skill_level_id, 'skill_year_id' => $skill_year_id],
    DB_AUTOQUERY_UPDATE,
    "`job_id` = ? AND `job_inventory_id` = ?", [$job_id, $job_inventory_id]
  );
  report_result ($result, 'skill update');
}

# Remove this skill from this job.
function delete_from_job_inventory ()
{
  global $job_id, $job_inventory_id;
  assert_missing_info (!$job_id);
  $result = db_execute ("
    DELETE FROM `people_job_inventory`
    WHERE `job_id` = ? AND `job_inventory_id` = ?",
    [$job_id, $job_inventory_id]
  );
  report_result ($result, 'skill delete');
}

function list_positions ()
{
  global $group_id;
  site_project_header (
    [ 'title' => _("Looking for a job to Edit"),
      'group' => $group_id,'context' => 'ahome']
  );
  print '<p>'
    . _("Here is a list of positions available for this project, choose "
        . "the\none you want to modify.")
    . "</p>\n";
  print people_show_group_jobs ($group_id, true);
}

function refresh ()
{
  # Empty function body.
}

function confirm_refresh ()
{
  global $job_id;
  if (empty ($job_id))
    return;
  $t = time ();
  $result = db_autoexecute (
    'people_job', ['date' => $t], DB_AUTOQUERY_UPDATE, '`job_id` = ?', [$job_id]
  );
  report_result ($result, 'refresh');
}

function print_date_form ($row)
{
  global $refresh;
  $preamble = '';
  $buttons = form_submit (_('Refresh'), 'refresh');
  if (!empty ($refresh))
    {
      $preamble = '<p><span class="preinput">'
        . _('You are about to refresh job post date, please confirm:')
        . "</span></p>\n";
      $buttons = form_submit (_('Confirm'), 'confirm_refresh')
        . ' ' . form_submit (_('Cancel'), 'cancel');
    }
  print form_tag ()
    . form_hidden (['group_id' => $row['group_id'], 'job_id' => $row['job_id']])
    . "$preamble<p><b>" . _('Date:') . '</b> '
    . utils_format_date ($row['date']) . ' ' . "$buttons</p>\n</form>\n";
}

function print_edit_form ($job_id, $group_id, $row)
{
  print_date_form ($row);
  print form_tag ()
    . form_hidden (['group_id' => $group_id, 'job_id' => $job_id])
    . "<b>" . _("Category:") . "</b>\n"
    . people_job_category_box ('category_id', $row['category_id'])
    . "\n<p><b>" . _("Status") . ":</b>\n"
    . people_job_status_box ('status_id', $row['status_id']) . "</p>\n<p>"
    . html_label ('title', '<b>' . _("Short Description:") . '</b>') . "\n"
    . form_input_arr (['type' => 'text', 'name' => 'title',
        'value' => $row['title'], 'size' => '40', 'maxlength' => '80'])
    . "</p>\n<p>"
    . html_label ('description', '<b>' ._("Long Description:") . '</b>')
    . "<br />\n"
    . form_textarea ('description', utils_specialchars ($row['description']),
        "rows='10' cols='60' wrap='soft'")
    . "\n</p>\n<p>" . form_submit (_("Update Descriptions"), "update_job")
    . "\n</form>\n";
  print '<p>' . people_edit_job_inventory ($job_id, $group_id)
    . "</p>\n<p><a href='/people/editjob.php?group_id=$group_id'>"
    . _("Back to jobs listing") . "</a></p>\n";
}

# Fill in the info to create a job.
function print_edit_job ($job_id, $group_id, $result)
{
  site_project_header (
    [ 'title' => _("Edit a job for your project"),
      'group' => $group_id, 'context' => 'ahome']
  );
  if ($result === null)
    return;
  $row = db_fetch_array ($result);
  utils_get_content ("people/editjob");
  print_edit_form ($job_id, $group_id, $row);
}

function run_action ()
{
  global $job_result, $job_id, $group_id;
  $actions = [
    'refresh', 'confirm_refresh', 'add_job', 'update_job',
    'add_to_job_inventory', 'update_job_inventory', 'delete_from_job_inventory'
  ];
  foreach ($actions as $a)
    {
      if (empty ($GLOBALS[$a]))
        continue;
       $a ();
       $job_result = people_verify_job_group ($job_id, $group_id);
       return;
    }
}

run_action ();

if ($job_id)
  print_edit_job ($job_id, $group_id, $job_result);
else
  list_positions ();
site_project_footer ();
?>
