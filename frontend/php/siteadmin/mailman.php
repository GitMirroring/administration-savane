<?php
# Assign Mailman lists to specified groups.
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

require_once ("../include/mailman.php");

session_require (['group' => '1', 'admin_flags' => 'A']);

$submits = ['assign', 'confirm'];
extract (sane_import ('request',
  [
    'preg' => [['list_name', '/^[a-zA-Z0-9-]+$/']],
    'name' => ['new_group'], 'true' => $submits
  ]
));
form_check ($submits);

if (!empty ($confirm))
  $assign = null;

function group_link ($row)
{
  global $sys_home;

  $name = $row['unix_group_name'];
  return sprintf (
    no_i18n ('&lt;%3$s&gt; <a href="%1$s">%2$s</a>'),
    "{$sys_home}mail/admin/?group=$name", $row['group_name'], $name
  );
}

function find_group ($grp_id)
{
  $res = db_execute (
    'SELECT unix_group_name, group_name FROM groups WHERE group_id = ?',
    [$grp_id]
  );
  if (!db_numrows ($res))
    return [false, sprintf (no_i18n ("Group #%s not found."), $grp_id)];
  return [true, group_link (db_fetch_array ($res))];
}

function fetch_list_from_mailman ($list_name)
{
  $res = mailman_query_list ($list_name);
  if (empty ($res) || empty ($res['qry::host_name']))
    return [];
  return $res;
}

$use_mail = $list_data = null;

function look_for_list ()
{
  global $list_name, $group_list_id, $list_data;

  if (empty ($list_name))
    return [false, null];
  $list_name = strtolower ($list_name);
  if (null === $list_data)
    $list_data = fetch_list_from_mailman ($list_name);
  $res = db_execute ("
    SELECT group_list_id, group_id, is_public, description
    FROM mail_group_list WHERE list_name = ?", [$list_name]
  );
  if (!db_numrows ($res))
    return [
      false, ['link' => no_i18n ("<b>List not found in the database.</b>")]
    ];
  $row = db_fetch_array ($res);
  $group_list_id = $row['group_list_id'];
  list ($found, $row['link']) = find_group ($row['group_id']);
  return [$found, $row];
}

function look_for_group ()
{
  global $new_group, $new_group_id, $use_mail;

  if (empty ($new_group))
    return [false, null];
  $res = db_execute ("
    SELECT unix_group_name, group_name, group_id, use_mail FROM groups
    WHERE unix_group_name = ?",
    [$new_group]
  );
  if (!db_numrows ($res))
    return [false, sprintf (no_i18n ("Group %s not found."), $new_group)];
  $row = db_fetch_array ($res);
  $new_group_id = $row['group_id'];
  $use_mail = $row['use_mail'];
  return [true, group_link ($row)];
}

function fetch_data ()
{
  global $have_list, $have_group, $new_group_link, $frontend_list_data;
  list ($have_list, $frontend_list_data) = look_for_list ();
  list ($have_group, $new_group_link) = look_for_group ();
}

function add_list_to_db ()
{
  global $new_group_id, $list_data, $list_name;
  $public = 0;
  if (!empty ($list_data['qry::advertised']))
    $public = 1;
  $desc = '';
  if (array_key_exists ('qry::description', $list_data))
    $desc = $list_data['qry::description'];

  $fields = [ 'group_id' => $new_group_id, 'list_name' => $list_name,
    'is_public' => $public,
    # list_admin doesn't really matter: the notifications on password
    # changes are sent to those who request the reset, anyway.
    'list_admin' => user_getid (), 'description' => $desc
  ];
  mailman_add_list_to_db ($fields);
}

function modify_list_in_db ()
{
  global $new_group_id, $group_list_id, $list_data;
  $arg = ['group_id' => $new_group_id];
  if (!empty ($list_data))
    {
      if (array_key_exists ('qry::advertised', $list_data))
        $arg['is_public'] = $list_data['qry::advertised'];
      if (array_key_exists ('qry::description', $list_data))
        $arg['description'] = $list_data['qry::description'];
    }
  $res = db_autoexecute ('mail_group_list', $arg, DB_AUTOQUERY_UPDATE,
    'group_list_id = ?', [$group_list_id]
  );
  if ($res)
    fb ('List assigned');
  else
    fb ('List assignment failed', 1);
}

function assign_list ()
{
  if (empty ($GLOBALS['group_list_id']))
    add_list_to_db ();
  else
    modify_list_in_db ();
  fetch_data ();
}

function hidden_vals ()
{
  global $list_name, $new_group;
  return ['list_name' => $list_name, 'new_group' => $new_group];
}

function list_mailman_data ()
{
  global $list_data;
  $ret = [];
  if (empty ($list_data))
    {
      $ret[no_i18n ("List data")] =
        "<b>" . no_i18n ("List not found on Mailman server.") . "</b>\n";
      return $ret;
    }
  $fields = [ 'description' => no_i18n ('Description'),
    'host_name' => no_i18n ('Domain'), 'owner' => no_i18n ('Admin email'),
    'advertised' => no_i18n ('Advertised')
  ];
  foreach ($fields as $k => $label)
    {
      if (array_key_exists ("qry::$k", $list_data))
        $val = utils_specialchars ($list_data["qry::$k"]);
      else
        $val = "<b>unknown</b>";
      $ret[$label] = $val;
    }
  return $ret;
}

function list_frontend_data ()
{
  global $frontend_list_data;
  $ret = [];
  $ret[no_i18n ("Current group")] = $frontend_list_data['link'];

  if (!array_key_exists ('description', $frontend_list_data))
    return $ret;
  $ret[no_i18n ("Description")] = $frontend_list_data['description'];
  $ret[no_i18n ("Public")] = $frontend_list_data['is_public'];
  return $ret;
}

function display_list_data ()
{
  global $frontend_list_data;
  if (empty ($frontend_list_data))
    return;
  print html_h (3, no_i18n ('Frontend data'));
  print html_dl (list_frontend_data ());
  print html_h (3, no_i18n ('Mailman data'));
  print html_dl (list_mailman_data ());
}

function display_group_found ()
{
  global $new_group_link, $have_group, $use_mail;
  if (empty ($new_group_link))
    return;
  $defs = ['Group found' => $new_group_link];
  if ($have_group)
    $defs['Uses mailing lists'] = $use_mail? 'yes': '<b>no</b>';
  print html_dl ($defs) . "\n";
}

function show_search_button ()
{
  global $assign;
  $label = no_i18n ("Search");
  if (!empty ($assign))
    {
      print form_hidden (hidden_vals ());
      $label = no_i18n ("Back to search");
    }
  print "<input type='submit' value=\"$label\" />\n";
}

function show_assign_form ()
{
  global $assign;
  print "<p>"
    . no_i18n ('When the mailing list is assigned, its frontend description '
       . 'and the <code>is_public</code> flag will be updated from '
       . 'the Mailman server.')
    . "</p>\n";
  print form_tag (['name' => 'form_assign']);
  $vals = hidden_vals ();
  $vals[empty ($assign)? 'assign': 'confirm'] = 1;
  print form_hidden ($vals);
  if (empty ($assign))
    $label = no_i18n ("Assign list");
  else
    $label = no_i18n ("Confirm assignment");
  print "<p>" . form_submit ($label, 'submit') . "</p>\n";
  print "</form>\n";
}

function show_list_input ($extra)
{
  global $list_name;
  print "<p><span class='preinput'>"
    . html_label ('list_name', no_i18n ('Mailing list (without domain)'))
    . '</span>&nbsp;&nbsp;';
  print form_input ('text', 'list_name', $list_name, $extra);
  print "</p>\n";
}

function show_group_input ($extra)
{
  global $new_group;
  print "<p><span class='preinput'>"
    . html_label ('new_group',
        no_i18n ('Group to assign the list to (exact &ldquo;unix name&rdquo;)')
      )
    . '</span>&nbsp;&nbsp;';
  print form_input ('text', 'new_group', $new_group, $extra);
  print "</p>\n";
}

function show_page ($may_assign)
{
  global $assign;
  $extra = empty ($assign)? '': " disabled='disabled'";

  print form_tag (['name' => 'search_list']);
  print html_h (2, no_i18n ('Search for mailing list'));
  show_list_input ($extra);
  display_list_data ();
  print html_h (2, no_i18n ('Search for group'));
  show_group_input ($extra);
  display_group_found ();
  show_search_button ();
  print "</form>\n";
  if ($may_assign)
    show_assign_form ();
}

if (empty ($list_name))
  $list_name = '';
if (empty ($new_group))
  $new_group = '';

site_admin_header (
  ['title' => no_i18n ('Assign mailing lists'), 'context' => 'admgroup']
);

fetch_data ();

$may_assign = $use_mail && !empty ($list_data);

if (!$may_assign)
  $confirm = null;

if (!empty ($confirm))
  assign_list ();

show_page ($may_assign);
site_admin_footer ([]);
?>
