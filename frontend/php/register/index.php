<?php
# Group registration.
#
# Copyright (C) 1999-2000 The SourceForge Crew
# Copyright (C) 2003-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007  Sylvain Beucler
# Copyright (C) 2017, 2019, 2021, 2022, 2023 Ineiev
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

# Tricks for automatically opening a task in the tracker (sigh..).
define ('ARTIFACT', 'task');
require_once ('../include/gpl-quick-form.php');
require_once ('../include/init.php');
require_once ('../include/database.php');
require_once ('../include/vars.php'); # $LICENSE
require_once ('../include/account.php'); # account_groupnamevalid
require_once ('../include/group.php'); # getTypeBaseHost()
require_once ('../include/sendmail.php');

# GPLQuickForm validation callback.
# Make sure the name is not already taken, ignoring incomplete
# registrations: risks of a name clash seems near 0, while not doing that
# require maintainance, since some people interrupt registration and
# try to redoit later with another name.
# And even if a name clash happens, admins will notice it during approval.
function group_does_not_already_exist ($form_unix_name)
{
  $res = db_execute ("
    SELECT group_id FROM groups
    WHERE unix_group_name LIKE ? AND status <> 'I'", [$form_unix_name]
  );
  return db_numrows ($res) < 1;
}

function license_exists ($form_license)
{
  global $LICENSE;
  return isset ($LICENSE[$form_license]);
}

function group_type_exists ($type_id)
{
  global $types;
  return isset ($types[$type_id]);
}

session_require (['isloggedin' => '1']);
# TRANSLATORS: the argument is site name (like Savannah).
$HTML->header (['title' => sprintf (_("%s hosting request"), $sys_name)]);

if (db_numrows (db_execute ("SELECT type_id FROM group_type")) < 1)
  {
    # group_type is empty; it's not possible to register groups.
    print _("No group type has been set. Admins need to create at least one\n"
            . "group type. They can make it so visiting the link &ldquo;Group "
            . "Type\nAdmin&rdquo; on the Administration section of the "
            . "left side menu, while logged\nin as admin.");
    $HTML->footer ([]);
    exit (0);
  }

$form = new GPLQuickForm ('change_date');

$form->addElement ('header', 'title_name', _('Group name'));
$form->addElement ('text', 'full_name', _('Full name'));
$form->addElement ('text', 'unix_name', _('System name'),
  _("Used in URLs, mailing list names, etc&mdash;4&ndash;16 characters,\n"
    . "starting with a letter and only containing ASCII letters, digits "
    . "and dashes.  System names should be reasonably descriptive, rather "
    . "than terse abbreviations\nor confusingly general.")
);

$form->addElement ('header', 'title_information', _('Package information'));
$form->addElement ('textarea', 'purpose', _('~20-line technical description'),
  _("What is your package? (Purpose, topic, programming language...)\n"
    . "What is special about it?")
);
$types = [];
$result = db_execute ("SELECT type_id, name FROM group_type ORDER BY type_id");
while ($line = db_fetch_array ($result))
  $types[$line['type_id']] = gettext ($line['name']);
$form->addElement ('select', 'group_type', _('Group type'), $types);
$form->setDefaults (['group_type' => 2]);
$form->addElement ('select', 'license', _('Package license'), $LICENSE);
$form->addElement ('textarea', 'license_other', _('Other license, details'));

$form->addElement ('header', 'title_checklist', _("Checklist"));
# <savannah-specific>
$form->addElement ('checkbox', 'cl1',
  _('My software runs primarily on a completely free OS'));
$form->addElement ('checkbox', 'cl2',
  _('My license is compatible with GNU GPLv3 and later '
    . 'or GFDLv1.3 and later'));
$form->addElement ('checkbox', 'cl3',
  _('My dependencies are compatible with my package license'));
$form->addElement ('checkbox', 'cl4',
  sprintf (_('All my files include <a href="%s">valid copyright notices</a>'),
    'https://www.gnu.org/prep/maintain/html_node/Copyright-Notices.html')
);
$form->addElement ('checkbox', 'cl5',
  sprintf (
    _("All my files include a <a href=\"%s\">license notice</a>\n"),
    '//www.gnu.org/licenses/gpl-howto.html')
);
$form->addElement ('checkbox', 'cl6',
  _('Origin and license of media files is specified'));
$form->addElement ('checkbox', 'cl7',
  _('My tar file includes a copy of the license'));
# </savannah-specific>
$form->addElement ('checkbox', 'cl_foolproof',
  _("I read carefully and don't check this one"));
$form->addElement ('checkbox', 'cl_requirements',
  sprintf (_('I agree with the <a href="%s">hosting requirements</a>'),
   'requirements.php')
);
$form->addElement ('header', 'title_details', _('Details'));
$form->addElement ('textarea', 'required_sw', _('Dependencies'),
  _('name + license + website for each dependency')
);
$form->addElement ('textarea', 'comments', _('Other Comments'));
$form->addElement ('text', 'tarball_url', _('Tarball (.tar.gz) URL'),
  sprintf (
    _('(or <a href="%s" target="_blank">upload file</a> to Savannah.)'),
    'upload.php')
);
$form->addElement ('submit', null, _("Register group"));

$form->addRule ('full_name', _("Invalid full name"), 'minlength', 2);
$form->addRule ('unix_name', _("Invalid system name"), 'callback',
  'account_groupnamevalid');
$form->addRule ('unix_name', _("A group with that name already exists."),
  'callback', 'group_does_not_already_exist');
$form->addRule ('license', _("Invalid license"), 'callback', 'license_exists');
$form->addRule ('group_type', _("Invalid group type"),
  'callback', 'group_type_exists');

for ($i = 1; $i <= 7; $i++)
  $form->addRule("cl$i", _("Please recheck your submission"), 'required');

$form->addRule ("cl_foolproof", _(":)"), 'maxlength', 0);
$form->addRule ("cl_requirements", _("Please accept the hosting requirements"),
  'required'
);
$form->addRule ('purpose', _("This is too short!"), 'minlength', 30);
$form->addRule ('tarball_url',
 _("Please give us a link to your package latest release"), 'minlength', 4);

if (!$form->validate ())
  {
    utils_get_content ("register/index");

    $form->display ();
    $HTML->footer ([]);
    exit (0);
  }

utils_get_content ("register/confirmation");
$form_values = $form->exportValues ();
$form->freeze ();

$form_full_name = $form_values['full_name'];
$form_purpose = $form_values['purpose'];
$form_required_sw = $form_values['required_sw'];
$form_comments = $form_values['comments'];
$form_license = $form_values['license'];
$form_license_other = $form_values['license_other'];
$group_type = $form_values['group_type'];

db_autoexecute ('groups',
  [
    'group_name' => utils_specialchars ($form_full_name),
    'unix_group_name' => strtolower($form_values['unix_name']),
    'status' => 'P', 'is_public' => 1, 'register_time' => time(),
    'register_purpose' => utils_specialchars ($form_purpose),
    'required_software' => utils_specialchars ($form_required_sw),
    'other_comments' => utils_specialchars ($form_comments),
    'license' => $form_license,
    'license_other' => utils_specialchars ($form_license_other),
    'type' => $group_type,
  ], DB_AUTOQUERY_INSERT
);
$result = db_execute (
  "SELECT group_id FROM groups WHERE unix_group_name = ?",
  [$form_values['unix_name']]
);
$group_id = db_result ($result, 0, 'group_id');
$project = project_get_object ($group_id);

if (db_affected_rows($result) < 1)
  exit_error (_("Unable to update database, please contact administrators"));

$user_id = user_getid ();

# Make the current user an admin.
$result = member_add ($user_id, $group_id, "A");

if (!$result)
  exit_error (_("Setting you as group admin failed"));

$user_realname = user_getrealname ($user_id);
$user_email = user_getemail ($user_id);
$unix_name = group_getunixname ($group_id);
$sql_type = db_execute ("SELECT name FROM group_type WHERE type_id = ?",
  [$group_type]
);
$type = db_result ($sql_type, 0, 'name');
$type_base_host = $project->getTypeBaseHost ();
$type_admin_email_address = $project->getTypeAdminEmailAddress ();

# This will define confirmation_gen_email().
utils_get_content ("register/confirmation_mail");

$message = confirmation_gen_email (
  $type_base_host, $user_realname, $user_email, $type_admin_email_address,
  $form_license, $form_license_other, $form_full_name, $unix_name, $type,
  $form_purpose, $form_required_sw, $form_comments
);

$message_user = $message;

$group_admin_url =
  "$sys_https_url{$sys_home}siteadmin/groupedit.php?group_id=$group_id";

$message_admin = "A new group has been registered at $sys_name.
This group will remain inactive until a site admin approves
or discards the registration.


= Registration Administration =

Approving or discarding the registration must be done using the specific
[$group_admin_url Group administration] page, accessible only to site
administrators logged in as superusers.

= Registration Details =

* Name: *$form_full_name*
* System Name:  *$unix_name*
* Type: $type
* License: {$LICENSE_EN[$form_license]}";

if ($form_license_other)
  $message_admin .= " ($form_license_other)";

$message_admin .= "\n\n----\n\n== Description: ==\n$form_purpose\n\n";

if ($form_required_sw)
  $message_admin .= "\n== Other Software Required: ==\n"
                    . $form_required_sw . "\n\n";

if ($form_comments)
  $message_admin .= "\n== Other Comments: ==\n$form_comments\n\n";

$message_admin .= "\n== Tarball URL: ==\n" . $form_values['tarball_url']
  . "\n\n";

sendmail_mail (
  [ 'from' => $type_admin_email_address, 'to' => $user_id],
  [ 'subject' => "submission of $form_full_name - $type_base_host",
    'body' => $message_user,
    'headers' => ['Reply-To' => $type_admin_email_address]
  ]
);

{
  require_directory ("trackers");
  trackers_init ($GLOBALS['sys_group_id']);

  $vfl = [];
  $vfl['category_id'] = '1';
  $vfl['summary'] = "Submission of $form_full_name";
  $vfl['details'] = $message_admin;
  $vfl['planned_starting_date'] = date ("Y") . "-" . date ("m") . "-"
    . date ("d");
  $vfl['planned_close_date'] = date ("Y") . "-" . date ("m") . "-"
    . (date ("d") + 10);

  $address = "";
  $item_id = trackers_data_create_item (
    $GLOBALS['sys_group_id'], $vfl, $address
  );
  # Send an email to notify the admins of the ite update.
  list ($additional_address, $sendall) =
    trackers_data_get_item_notification_info ($item_id, ARTIFACT, 1);
  if ((trim ($address) != "") && (trim ($additional_address) != ""))
    $address .= ", ";
  $address .= $additional_address;
  # Exclude the submitter who has already been sent a notification.
  trackers_mail_followup ($item_id, $address, false, $user_id);
}

# Get site-specific content, if it is not the localadmin group.
# Create the page header just like if there was not yet any group_id.
$group_id_not_yet_valid = $group_id;
unset ($group_id);
$group_id = $group_id_not_yet_valid;

$form->display ();
$HTML->footer ([]);
?>
