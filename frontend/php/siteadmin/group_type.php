<?php
# Edit group types configuration.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2008 Aleix Conchillo Flaque
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

require_once ('../include/init.php');
require_once ('../include/vars.php');
require_once ('../include/form.php');
require_directory ("project");

session_require (['group' => '1','admin_flags' => 'A']);

# We don't internationalize messages in this file because they are
# for Savannah admins who use English.

function specific_showinput ($title, $form, $id = false)
{
  $head = $tail = '';
  if ($id !== false)
    {
      $head = "<label for=\"$id\">";
      $tail = '</label>';
    }
   print "\n<span class='preinput'>$head$title$tail</span><br />\n"
     . "&nbsp;&nbsp; $form<br />\n";
}
function specific_input ($title, $row, $idx, $sz)
{
  specific_showinput (
    $title,
    "<input type='text' name='$idx' id='$idx'\n  value=\"$row\" size='$sz' />",
    $idx
  );
}
function show_checkbox ($title, $field, $row)
{
  $checkbox = form_checkbox ($field, $row[$field] == 1);
  $id = $field;
  print "<p>\n$checkbox\n"
    . "<span class=\"preinput\"><label for=\"$id\">$title</label></span>"
    . "</p>\n";
}

$submits = ['delete', 'update'];
extract (sane_import ('request', ['digits' => 'type_id']));
extract (sane_import ('get', ['true' => 'create']));
extract (sane_import ('post', ['true' => $submits]));
form_check ($submits);

$trackers_labelled = [
  'cookbook' => no_i18n ("Cookbook Manager"),
  'bugs' => no_i18n ("Bug Tracking"), 'news' => no_i18n ("News Manager"),
  'task' => no_i18n ("Task Tracking"),
  'support' => no_i18n ("Support Tracking"),
  'patch' => no_i18n ("Patch Tracking"),
];

$trackers = utils_get_tracker_list ();
$trackers[] = 'news';
$tracker_labels = [];
foreach ($trackers as $t)
  $tracker_labels[] = $trackers_labelled[$t];

$vcs_list = [
  no_i18n ("CVS") => 'cvs', no_i18n ("GNU Arch") => 'arch',
  no_i18n ("Subversion") => 'svn', no_i18n ("Git") => 'git',
  no_i18n ("Mercurial") => 'hg', no_i18n ("Bazaar") => 'bzr',
];

if ($delete)
{
  $result = db_execute ("DELETE FROM group_type WHERE type_id = ?", [$type_id]);

  if (!$result)
    fb (no_i18n ("Unable to delete group type"), 0);
  else
    fb (no_i18n ("group type deleted"));

  site_admin_header (
    ['title' => no_i18n ('Group Type Management'), 'context' => 'admgrptype']
  );
  site_admin_footer ();
  exit;
}

$name_matching = function ($trackers, $vcs_list)
{
  $names = [
    'specialchars' => [
      'name', 'description',  'base_host', 'homepage_scm',
      'admin_email_adress', # Sic! adress not address, single 'd'.
    ],
    'true' => []
  ];
  $hm_dw = ['download', 'homepage'];
  $vcs_extra = array_merge ($vcs_list, $hm_dw);
  foreach ($vcs_extra as $vcs)
    {
      $names['specialchars'][] = "dir_type_$vcs";
      $names['specialchars'][] = "dir_$vcs";
    }
  foreach ($hm_dw as $hd)
    $names['specialchars'][] = "url_$hd";
  foreach ($vcs_list as $vcs)
    $names['specialchars'][] = "url_{$vcs}_viewcvs";
  $names['specialchars'][] = "url_cvs_viewcvs_homepage";
  foreach (
    [
      'listinfo', 'subscribe', 'unsubscribe', 'archives', 'archives_private',
      'admin'
    ] as $f
  )
    $names['specialchars'][] = "url_mailing_list_$f";
  foreach (['address', 'virtual_host', 'format'] as $f)
    $names['specialchars'][] = "mailing_list_$f";
  $can_use_ = array_merge (
    $vcs_extra, $trackers,
    ['forum', 'license', 'devel_status', 'mailing_list', 'bug']
  );
  foreach ($can_use_ as $art)
    if ($art != 'bugs' && $art != 'cookbook')
      $names['true'][] = "can_use_$art";
  $conf = array_merge (
    ['forum', 'extralink_documentation', 'mail'], $trackers, $vcs_extra
  );
  foreach ($conf as $art)
    if ($art != 'cookbook' && $art != 'news')
      $names['true'][] = "is_menu_configurable_$art";
  foreach ($vcs_list as $vcs)
    $names['true'][] = "is_menu_configurable_{$vcs}_viewcvs";
  $names['true'][] = "is_configurable_download_dir";
  return $names;
};

if ($update)
  {
    $names = $name_matching ($trackers, $vcs_list);
    $values = sane_import ('post', $names);
    foreach ($names['true'] as $k)
      if ($values[$k] === null)
        $values[$k] = 0;
    $result = db_autoexecute (
      'group_type', $values, DB_AUTOQUERY_UPDATE, "type_id = ?", [$type_id]
    );

    if ($result)
      fb (no_i18n ("group type general settings updated"));
    else
      fb (
        sprintf (
          # TRANSLATORS: the argument is error message.
          no_i18n ("Unable to update group type settings: %s"),
          db_error ()),
        1
      );

    $names = [];
    foreach ($trackers as $art)
      {
        $names[] = "{$art}_user_";
        $names[] = "{$art}_restrict_event1";
      }
    $names[] = '/^(\d+|NULL)$/';
    extract (sane_import ('post', ['preg' => [$names]]));
    $arg_arr = [];
    foreach ($trackers as $art)
      {
        $var = "{$art}_user_";
        $arg_arr["{$art}_flags"] = $$var;
        $var = "{$art}_restrict_event1";
        $arg_arr["{$art}_rflags"] = $$var;
      }

    $result = db_autoexecute (
      'group_type', $arg_arr , DB_AUTOQUERY_UPDATE, "type_id = ?", [$type_id]
    );
  }


if (empty ($type_id))
{
  site_admin_header (
    ['title' => no_i18n ('Group Type Management'), 'context' => 'admgrptype']
  );

  $result = db_query ("SELECT type_id, name FROM group_type ORDER BY type_id");
  print "<br />\n";
  while ($usr = db_fetch_array ($result))
    {
      $last = $usr['type_id'];
      print "<a href=\"$php_self?type_id=$last\">";
      printf (no_i18n ('Type #%1$s: %2$s'), $last, $usr['name']);
      print "</a><br />\n";
    }
  # Find an appropriate unused group type ID (skip value 100).
  $type = $last + 1;
  if ($type == 100)
    $type = 101;

  print "<a href=\"$php_self?type_id=$type&amp;create=1\">"
    . no_i18n ('Create new group type') . '</a>';
  site_admin_footer ();
  exit (0);
}

$update_button_text = no_i18n ("Update");
if ($create == "1")
  {
    db_execute (
      "INSERT INTO group_type (type_id, name) VALUES (?, 'New type')",
      [$type_id]
    );
    $update_button_text = no_i18n ("Create");
  }

$result = db_execute ("SELECT * FROM group_type WHERE type_id = ?", [$type_id]);
$row_grp = db_fetch_array ($result);

site_admin_header (
  [ 'title' => no_i18n ("Edit group types"), 'context' => 'admgrptype']
);


print "<h1>{$row_grp['name']} (#{$row_grp['type_id']})</h1>\n";
print form_tag () . form_hidden (['type_id' => $type_id]);

print '<h2>' . no_i18n ("General default settings for groups of this type")
  . "</h2>\n\n";
$textfield_size = '65';
print '<p>'
  . no_i18n (
      'Basic Help: host means hostname (like savannah.gnu.org), '
      . 'dir means directory (like /var/www/savane).'
    )
  . "</p>\n<p class='warn'>"
  . no_i18n (
      'Everytime group unix_group_name should appear, use the '
      . 'special string %PROJECT.')
  . "</p>\n<p>"
  . no_i18n (
      'Fields marked with [BACKEND SPECIFIC] are only useful is you use '
      . 'the savannah backend.')
  ."</p>\n<p>"
  . no_i18n (
      'Fill only the fields that have a specific setting, differing '
      . 'from the whole installation settings.')
  . "</p>\n";

print "<h2>" . no_i18n ("General Settings") . "</h2>\n\n";

$idx = 'name';
specific_input (
  no_i18n ("Name:"), $row_grp[$idx], $idx, $textfield_size
);
$idx = 'base_host';
specific_input (
  no_i18n ("Base Host:"), $row_grp[$idx], $idx, $textfield_size
);
print "<p>";
specific_showinput (
  no_i18n ("Description (will be added on each group main page):"),
  "<textarea cols=\"$textfield_size\" rows='6' wrap='virtual' "
  . "name='description' id='description'>{$row_grp['description']}</textarea>",
  'description'
);
specific_showinput (
  no_i18n ("Admin Email Address:"),
  '<input type="text" name="admin_email_adress" '
  . "id='admin_email_adress'\n  value=\"{$row_grp['admin_email_adress']}\""
  . " size=\"$textfield_size\" />",
  'admin_email_adress'
);
print "</p>\n";

# FIXME: the following more or less assuming that WWW homepage will be
# managed using CVS.
# For instance, there will be no viewcvs possibility for Arch managed
# repository. But this is non-blocker so we let as it is.

print "<h2>" . no_i18n ("Group WWW homepage") . "</h2>\n\n";
print '<p>'
  . no_i18n (
      "This is useful if you provide directly web homepages (created by\n"
      . "the backend) or if you want to allow groups to configure\n"
      . "the related menu entry (see below).  The SCM selection will only\n"
      . "affect the content shown by the frontend related to the homepage\n"
      . "management.")
  . "</p>\n";

print "<p>";
show_checkbox (no_i18n ("Can use homepage"), 'can_use_homepage', $row_grp);
print "</p>";

$sel_val = null;
$selection = $row_grp['homepage_scm'];
foreach ($vcs_list as $title => $name)
  if ($name === $selection)
    {
      $sel_val = $title;
      break;
    }
$vals = array_keys ($vcs_list);
$select_box =
   html_build_select_box_from_array ($vals, "homepage_scm", $sel_val);

print "<p>";
specific_showinput (no_i18n ("Selected SCM:"), $select_box);

html_select_typedir_box ("dir_type_homepage", $row_grp['dir_type_homepage']);
$idx = 'dir_homepage';
specific_input (
  no_i18n ("Homepage Dir (path on the filesystem) [BACKEND SPECIFIC]:"),
  $row_grp[$idx], $idx, $textfield_size
);
$idx = 'url_homepage';
specific_input (
  no_i18n ("Homepage URL:"), $row_grp[$idx], $idx, $textfield_size
);
$idx = 'url_cvs_viewcvs_homepage';
specific_input (
  no_i18n ("Homepage CVS view URL (webcvs, viewcvs):"),
  $row_grp[$idx], $idx, $textfield_size
);
print "</p>";

function source_code_manager ($HTML, $row_grp, $textfield_size,
  $vcs_name, $vcs_offix)
{
  # TRANSLATORS: the argument is VCS name (like Subversion).
  printf ("<h2>" . no_i18n ("Source Code Manager: %s") . "</h2>", $vcs_name);
  print "\n\n<p>";
  printf (
    no_i18n ("This is useful if you provide directly %s repositories\n"
      . "(created by the backend) or if you want to allow groups\n"
      . "to configure the related menu entry (see below)."),
    $vcs_name
  );
  print "</p>\n<p>";
  show_checkbox (
    # TRANSLATORS: the argument is VCS name (like Subversion).
    sprintf (no_i18n ("Can use %s"), $vcs_name),
    "can_use_$vcs_offix", $row_grp
  );
  html_select_typedir_box (
    "dir_type_$vcs_offix", $row_grp['dir_type_'.$vcs_offix]
  );
  $idx = "dir_$vcs_offix";
  specific_input (
    no_i18n ("Repository Dir (path on the filesystem) [BACKEND SPECIFIC]:"),
    $row_grp[$idx], $idx, $textfield_size
  );
  $idx = "url_{$vcs_offix}_viewcvs";
  specific_input (
    no_i18n ("Repository view URL (cvsweb, viewcvs, archzoom...):"),
    $row_grp[$idx], $idx, $textfield_size
  );
  print "</p>\n";
}

foreach ($vcs_list as $title => $name)
  source_code_manager ($HTML, $row_grp, $textfield_size, $title, $name);

print "<h2>" . no_i18n ("Download Area") . "</h2>\n\n";
print '<p>'
  . no_i18n (
      "This is useful if you provide directly download areas\n"
      . "(created by the backend) or if you want to allow groups\n"
      . "to configure the related menu entry (see below).")
  . "</p>\n";

show_checkbox (
  no_i18n ("Can use Download Area"), "can_use_download", $row_grp
);
html_select_typedir_box ("dir_type_download", $row_grp['dir_type_download']);
$idx = 'dir_download';
specific_input (
  no_i18n ("Repository Dir (path on the filesystem) [BACKEND SPECIFIC]:"),
  $row_grp[$idx], $idx, $textfield_size
);
$idx = 'url_download';
specific_input (
  no_i18n ("Repository URL:"), $row_grp[$idx], $idx, $textfield_size
);

print "<h2>" . no_i18n ("Licenses") . "</h2>\n\n";
print '<p>'
  . no_i18n (
      "This is useful if you want group to select a license on\n"
      . "submission.  Edit site-specific/hashes.txt\n"
      . "to define the list of accepted licenses.")
  . "</p>\n";
show_checkbox (no_i18n ("Can use licenses"), 'can_use_license', $row_grp);

print "<h2>" . no_i18n ("Development Status") . "</h2>\n\n";
print '<p>'
  . no_i18n (
      "This is useful if you want group to be able to defines\n"
      . "their development status that will be shown on their main page.\n"
      . "This is purely a matter of cosmetics.  This option is mainly here\n"
      . "just to remove this content in case it is useless (it does not make\n"
      . " sense for organizational groups).  Edit site-specific/hashes.txt \n"
      . "to define the list of possible development status."
    )
  . "</p>\n";
show_checkbox (
  no_i18n ("Can use development status"), "can_use_devel_status", $row_grp
);

print "<h2>" . no_i18n ("Mailing List") . "</h2>\n\n";
print '<p class="warn">'
  . no_i18n (
      "Important: Everytime a mailing list name should appear,\n"
      . "use the special string %LIST."
    )
  . "</p>\n<p>"
  . no_i18n (
      "Do not configure Mailing list Host, this is a deprecated\n"
      . "feature left for backward compatibility."
    )
  . "</p>\n<p>"
  . no_i18n (
      "Mailing list virtual host only need to be set if you use mailman\n"
      . "list, set up via the backend, and have several mailman virtual\n"
      . "hosts set."
    )
  . "</p>\n";

show_checkbox (
  no_i18n ("Can use mailing lists"), "can_use_mailing_list", $row_grp
);

$idx = 'mailing_list_host';
specific_input (no_i18n ("Mailing list Host (DEPRECATED):"),
  $row_grp[$idx], $idx, $textfield_size
);
$idx = 'mailing_list_address';
specific_input (
  no_i18n (
    "Mailing list address (would be %LIST@gnu.org for GNU groups\n"
    . "at sv.gnu.org):"
  ),
  $row_grp[$idx], $idx, $textfield_size
);
$idx = "mailing_list_virtual_host";
specific_input (
  no_i18n (
    "Mailing list virtual host (would be lists.gnu.org or lists.nongnu.org at "
    . "sv.gnu.org) [BACKEND SPECIFIC]:"
  ),
  $row_grp[$idx], $idx, $textfield_size
);

print '<p>'
  . no_i18n (
      "With the following, you can force groups to follow a specific\n"
      . "policy for the name of the %LIST. Here you should use the special\n"
      . "wildcard %NAME, which is the part the of the mailing list name that\n"
      . "the group admin can define (would be %PROJECT-%NAME for non-GNU\n"
      . "groups at sv.gnu.org)."
    )
  . "</p>\n<p class='warn'>"
  . no_i18n ('Do no add any @hostname here!') . "</p>\n";

$idx = "mailing_list_format";
specific_input (no_i18n ("Mailing list name format:"),
  $row_grp[$idx], $idx, $textfield_size
);
$idx = "url_mailing_list_listinfo";
specific_input (no_i18n ("Listinfo URL:"),
  $row_grp[$idx], $idx, $textfield_size
);
$cern_fmt =
  "majordomo_interface.php?func=%s&amp;list=%%LIST&amp;"
  . "mailserver=listbox.server@cern.ch):";
$cern_url = sprintf ($cern_fmt, 'subscribe');
$idx = "url_mailing_list_subscribe";
specific_input (
  sprintf (
    no_i18n ("Subscribe URL (for majordomo at CERN, it is %s"),
    $cern_url
  ),
  $row_grp[$idx], $idx, $textfield_size
);
$cern_url = sprintf ($cern_fmt, 'unsubscribe');
$idx = "url_mailing_list_unsubscribe";
specific_input (
  sprintf (
    no_i18n ("Unsubscribe URL (for majordomo at CERN, it is %s"),
    $cern_url
  ),
  $row_grp[$idx], $idx, $textfield_size
);
$idx = "url_mailing_list_archives";
specific_input (no_i18n ("Archives URL:"),
  $row_grp[$idx], $idx, $textfield_size
);
$idx = "url_mailing_list_archives_private";
specific_input (no_i18n ("Private Archives URL:"),
  $row_grp[$idx], $idx, $textfield_size
);
$idx = "url_mailing_list_admin";
specific_input (no_i18n ("Administrative Interface URL:"),
  $row_grp[$idx], $idx, $textfield_size
);

function art_privbox ($art)
{
  global $row_grp;
  if ($art == 'bug')
    $art = 'bugs';
  if ($art == 'forum')
    return;
  $labels = [
    no_i18n ('Default permissions for members'),
    no_i18n ('Default posting restrictions')
  ];
  print html_build_list_table_top ($labels) . "<tr>\n";
  html_select_permission_box ($art, $row_grp["{$art}_flags"], "type");
  print "\n";
  html_select_restriction_box ($art, $row_grp["{$art}_rflags"], "type");
  print "\n</tr>\n</table>\n\n";
}

function artifact_section ($title, $description, $label, $artifact)
{
  global $row_grp, $HTML;

  print "<h2>$title</h2>\n\n";
  if ($description != '')
    print "<p>$description</p>\n";
  if ($artifact != 'cookbook')
    show_checkbox ($label, "can_use_$artifact", $row_grp);
  art_privbox ($artifact);
}

artifact_section (no_i18n ("Forum"),
  no_i18n (
    "Forum is a deprecated feature of Savane. We do not recommend using\n"
    . "it and we do not maintain this code any longer."
  ),
  no_i18n ("Can use forum"), 'forum'
);

artifact_section (no_i18n ("Support Request Manager"),
  no_i18n (
    "This is one of the main issue tracker of Savane.  Groups are\n"
    . "supposed to use it as primary interface with end user."
  ),
 no_i18n ("Can use support request tracker"), 'support'
);

artifact_section (no_i18n ("Bug Tracker"),
  no_i18n (
    "This is one of the main issue tracker of Savane. Unlike the\n"
    . "support tracker, it is supposed to be used mainly to organize\n"
    . "the workflow amongs group members related to bugs.  Groups\n"
    . "with large audience should probably not accept item posting\n"
    . "by people that are not member of the group on this tracker,\n"
    . "and instead redirect end user to the support tracker (and only\n"
    . "real bugs would be reassigned to this tracker). But that's only\n"
    . "a suggestion."
  ),
  no_i18n ("Can use bug tracker"), 'bug'
);

artifact_section (no_i18n ("Task Manager"),
  no_i18n (
    "This is one of the main issue tracker of Savane.  Unlike the\n"
    . "support tracker, it is supposed to be used mainly to organize\n"
    . "the workflow amongs group members related to planned tasks.  It's\n"
    . "the counterpart of the bug tracker for regular and planned activities."
  ),
  no_i18n ("Can use task manager"), 'task'
);

artifact_section (no_i18n ("Patch Manager"),
  no_i18n (
    "This is a deprecated issue tracker. It was originally designed\n"
    . "to get all the submitted patches; but it seems to us more sensible\n"
    . "that patch get attached to the relevant item (task, bug...)."
  ),
  no_i18n ("Can use patch manager (deprecated)"), 'patch'
);

artifact_section (
  no_i18n ("News Manager"), '', no_i18n ("Can use news manager"), 'news'
);

artifact_section (no_i18n ("Cookbook"), '', '', 'cookbook');

$update_delete_buttons = "\n<p align='center'>\n"
  . form_submit ($update_button_text) . "&nbsp;\n"
  . form_submit (no_i18n ("Delete this Group Type"), 'delete')
  . "\n</p>\n";
print $update_delete_buttons;

print "<h2>" . no_i18n ("Group Menu Settings") . "</h2>\n\n";

$i = 1;
print '<p class="' . utils_altrow ($i) . '">'
  . no_i18n (
      "This form allows you to choose which menu entries are configurable\n"
      . "by group administrators.")
  . "</p>\n";
function specific_checkbox ($val, $explanation, $row_grp, $class)
  {
    # Just a little function to clean that part of the code, no
    # interest to generalize it.
    $field = "is_menu_configurable_$val";
    print "<li class=\"$class\">" . form_checkbox ($field, $row_grp[$field]);
    print "<span class='preinput'><label for=\"$field\">"
      . "$explanation</label></span></li>\n";
  }

$row_grp["is_menu_configurable_download_dir"] =
  $row_grp["is_configurable_download_dir"];
$checkboxes = [
  "homepage" => no_i18n ("the homepage link can be modified"),
  "extralink_documentation" =>
     no_i18n ("the documentation &ldquo;extra&rdquo; link can be modified"),

  "download" => no_i18n ("the download area link can be modified"),
  "download_dir" => [
    no_i18n (
      "The download <i>directory</i> can be modified&mdash;beware, if\n"
      . "the backend is running and creating download dir, it can be used\n"
      . "maliciously.  Don't activate this feature unless you truly know\n"
      . "what you're doing."
    )
  ],
  "support" => no_i18n ("the support link can be modified"),
  "bugs" => no_i18n ("the bug tracker link can be modified"),
  "task" => no_i18n ("the task tracker link can be modified"),
  "patch" => no_i18n ("the patch tracker link can be modified"),
  "forum" => no_i18n ("the forum link can be modified"),
  "mail" => no_i18n ("the mailing list link can be modified"),

  "cvs" => no_i18n ("the cvs link can be modified"),
  "cvs_viewcvs" => [no_i18n ("the viewcvs link can be modified")],
  "cvs_viewcvs_homepage" =>
     [no_i18n ("the viewcvs link for homepage code can be modified")],

  "arch" => no_i18n ("the GNU Arch link can be modified"),
  "arch_viewcvs" => [no_i18n ("the GNU Arch viewcvs link can be modified")],

  "svn" => no_i18n ("the Subversion link can be modified"),
  "svn_viewcvs" => [no_i18n ("the Subversion viewcvs link can be modified")],

  "git" => no_i18n ("the Git link can be modified"),
  "git_viewcvs" => [no_i18n ("the Git viewcvs link can be modified")],

  "hg" => no_i18n ("the Mercurial link can be modified"),
  "hg_viewcvs" => [no_i18n ("the Mercurial viewcvs link can be modified")],

  "bzr" => no_i18n ("the Bazaar link can be modified"),
  "bzr_viewcvs" => [no_i18n ("the Bazaar viewcvs link can be modified")],
];
foreach ($checkboxes as $k => $v)
  {
    if (is_array ($v))
      $v = $v[0];
    else
      $i++;
    specific_checkbox ($k, $v, $row_grp, utils_altrow ($i));
  }

print "$update_delete_buttons</form>\n";
site_admin_footer ();
?>
