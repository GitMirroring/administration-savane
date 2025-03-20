<?php
# Setup a minimal environment (database, configuration file...)
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
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

$file_dir = dirname (__FILE__);
$inc = [
  'error', 'ac_config', 'database', 'session', 'user', 'context', 'exit',
  'utils'
];
foreach ($inc as $i)
  require_once ("$file_dir/$i.php");
unset ($inc);

# Default values, so they cannot be found undefined in the code.
$sys_name = "Change-This-Site-Name-with-\$sys_name";
$sys_logo_name = 'floating.png';
$stone_age_menu = false;
$sys_spamcheck_spamassassin = false;
$sys_upload_max = 512;

$sys_dbhost = 'localhost';
$sys_dbname = 'savane';
$sys_dbuser = 'root';

$sys_incdir = "$sys_etc_dir/content";
$sys_appdatadir = '/var/lib/savane';
$sys_trackers_attachments_dir = "$sys_appdatadir/trackers_attachments";
$sys_themedefault = 'Emeraud';
$sys_enable_forum_comments = 1;
$sys_registration_captcha = 0;
$sys_registration_text_spam_test = 1;

$sys_default_domain = $_SERVER['SERVER_NAME'];
if ($_SERVER['SERVER_PORT'] != 80)
  $sys_default_domain .= ':' . $_SERVER['SERVER_PORT'];
$sys_unix_group_name = 'siteadmin';

$sys_mail_domain = 'localhost';
$sys_mail_admin = get_current_user ();
$sys_mail_replyto = "NO-REPLY.INVALID-ADDRESS";
$sys_email_adress="$sys_mail_admin@$sys_mail_domain";
$sys_gpg_name = "gpg";
$sys_min_uidNumber = $sys_min_gidNumber = 5000;
$sys_passwd_common_gid = 1003;
$sys_passwd_home_dir = "$sys_pkgconfdir/user-home-dir";
$sys_passwd_user_shell = "$sys_pkgconfdir/user-shell";

# Debug variables
# (add them in tests/minimal_configs/Makefile, possibly commented out).
# Prevent redirections like sv.gnu.org -> sv.nongnu.org.
$sys_debug_nobasehost = false;

# Password strength checking.
# Do we have the pwqcheck(1) program from the passwdqc package?
$use_pwqcheck = TRUE;
# We can override the default password policy
# max = 40 is overridden because some users want longer passwords.
# min = default,24,11,8,7 is overridden for N0 passwords
# (the passwords consisting of characters from single class)
# because NIST Electronic Authentication Gudeline
# (Special Publication 800-63-1, Table A.1 on page 107
#  http://csrc.nist.gov/publications/nistpubs/800-63-1/SP-800-63-1.pdf)
# suggests that user-chosen 7 characters long password passing extensive
# checks has 27 bits of entropy, the same as 22 characters long
# user-chosen password composed from 10-character alphabet with no checks
# implied, so we can safely admit any 24 characters long passwords.
$pwqcheck_args = 'match=0 max=256 min=24,24,11,8,7';

# Default uploads directory for './register2/upload.html'.
$sys_upload_dir = "/var/www/submissions_uploads" ;

$sys_mailman_wrapper = "$bindir/sv_mailman-wrapper.pl";

if (empty ($sys_linguas))
  $sys_linguas = "en:ru";

if (false === strpos (":$sys_linguas:", ":en:"))
  $sys_linguas .= ":en";

if (!empty ($sys_conf_file))
  include ("$sys_conf_file");

if (empty ($sys_file_domain))
  $sys_file_domain = $sys_default_domain;
if (empty ($sys_dbport))
  $sys_dbport = null;
if (empty ($sys_dbsocket))
  $sys_dbsocket = null;

init_detect_topdir ();

# Add a trailing slash.
$sys_home = $GLOBALS['sys_url_topdir'];
if (!preg_match ('|/$|', $GLOBALS['sys_url_topdir']))
  $sys_home = $GLOBALS['sys_url_topdir'] . '/';

if (empty ($sys_savane_cgit))
  $sys_savane_cgit =
    "//git.savannah.nongnu.org/cgit/administration/savane.git";

# Defines the https url, if available -- no path is added since this
# variable can be used with REQUEST_URI added. It's used when we need
# to point a https URL (cannot be expressed using a http-relative
# path) and in e-mails.
if (isset ($GLOBALS['sys_https_host']))
  $sys_https_url = 'https://' . $GLOBALS['sys_https_host'];
else
  $sys_https_url = 'http://' . $GLOBALS['sys_default_domain'];

$php_self = utils_specialchars ($_SERVER['PHP_SELF']);

# Security issues apply even during fundrasing periods.  Please don't disable
# this, make the banner work with the headers instead.
utils_set_csp_headers ();
utils_update_decimal_separator ();

# Detect where we are unless explicitly specified in the configuration file.
function init_detect_topdir ()
{
  global $sys_www_topdir, $sys_url_topdir;
  if (!empty ($sys_www_topdir))
    return;
  $sys_www_topdir = getcwd ();
  $sys_url_topdir = dirname ($_SERVER['SCRIPT_NAME']);
  $test = 'testconfig.php';
  while ($sys_www_topdir != '/' && !file_exists ("$sys_www_topdir/$test"))
    {
      $sys_www_topdir = dirname ($sys_www_topdir);
      $sys_url_topdir = dirname ($sys_url_topdir);
    }
  if (!file_exists ("$sys_www_topdir/$test"))
    die ("Could not find Savane's top directory (missing $test)");
}

# Source (require) all specific include files of a module from
# the include area (all include files of a module are arranged
# in subdirectories in the includes area, so this routine sources
# just all of the *.php files found in the module's subdirectory).
function require_directory ($module)
{
  if (!empty ($GLOBALS["directory_{$module}_is_loaded"]))
    return;
  $dir = dirname (__FILE__) . "/$module";
  $GLOBALS["directory_{$module}_is_loaded"] = 1;
  if (!is_dir ($dir))
    return;
  $odir = opendir ($dir);
  while ($file = readdir ($odir))
    # Only include PHP scripts; avoid Emacs temporary files .#filename.php.
    if (preg_match ("/^[^.].*\.php$/i", $file))
      require_once ("$dir/$file");
  closedir ($odir);
}

$tracker_list = utils_get_tracker_list ();

function init_guess_artifact ($script_name)
{
  global $tracker_list;
  $guess = basename (dirname ($script_name));

  if ($guess == "admin") # Need to go deeper.
    $guess = basename (dirname (dirname ($script_name)));
  return $guess;
}

$db_err = db_connect ();
if ($db_err !== null && empty ($testconfig_php))
  {
    print $db_err;
    exit;
  }

# sys_unix_group_name is maybe defined
# in this case, we want sys_group_id.
if (isset ($GLOBALS['sys_unix_group_name']))
  {
    $search_group = $GLOBALS['sys_unix_group_name'];
    $res = db_execute (
      "SELECT group_id FROM groups WHERE unix_group_name = ?",
      [$search_group]
    );
    if (db_numrows ($res) != 0)
      $sys_group_id = db_result ($res, 0, 'group_id');
  }

if (!isset ($sys_group_id))
  fb (
    _("Your \$sys_unix_group_name configuration variable refers to a\n"
      . "non-existing project. Please update the configuration."),
    FB_ERROR
  );

# Determine if they're logged in.
session_set ();

# HTML layout class.
require_once ("$file_dir/layout.php");

$HTML = new Layout ();
theme_select ();

# If logged in, do a few setups.
if (user_isloggedin ())
  {
    # Set timezone.
    putenv ('TZ=' . user_get_timezone ());

    # Find out if the stone age menu is required.
    if (user_get_preference ('stone_age_menu'))
      $GLOBALS['stone_age_menu'] = 1;
  }
else
  # Set default timezone - avoid PHP warning.
  putenv ('TZ=UTC');

# Redirect them from http to https if they said so at login time.
if (!session_issecure () && isset ($_COOKIE['redirect_to_https'])
    && $GLOBALS['sys_https_host'])
  header (
    "Location: https://{$GLOBALS['sys_https_host']}{$_SERVER['REQUEST_URI']}"
  );

# Define every information useful in case of a project page.
extract (sane_import ('get', ['digits' => 'comingfrom']));
extract (sane_import ('request',
  [
    'name' => 'group',
    'digits' => ['group_id', 'item_id', 'forum_id']
  ]));

if (!defined ('ARTIFACT'))
  define ('ARTIFACT', init_guess_artifact ($_SERVER['SCRIPT_NAME']));

function init_extract_item_id ($tracker_list)
{
  global $item_id, $have_item_id;
  # If we are on an artifact index page and we have an argument which is
  # a numeric number, we suppose it is an item_id.
  if (!in_array (ARTIFACT, $tracker_list))
    return;
  if (empty ($_SERVER['QUERY_STRING']))
    return;
  $qs = strval ($_SERVER['QUERY_STRING']);
  if (ctype_digit ($qs))
    $item_id = $qs;
  $have_item_id = !empty ($item_id);
}

init_extract_item_id ($tracker_list);

if (!empty ($item_id) && !is_numeric ($item_id))
  util_die (_("Invalid item ID."));

if (!empty ($forum_id) && !is_numeric ($forum_id))
  util_die (_("Invalid forum ID."));

# Set the CONTEXT and SUBCONTEXT constants, useful to guess page titles
# but also to find out if cookbook entries are relevant.

context_guess ();
# Set the AUDIENCE constant.
user_guess ();

# If we got an item_id and no group_id we need to get the appropriate
# group_id.
if (
  !isset ($group_id) && !isset ($group) && isset ($item_id)
  && in_array (ARTIFACT, $tracker_list)
)
  {
    $group_id = utils_find_item (ARTIFACT, $item_id)['group_id'];

    # Special case: if it the item is from the system group and we are on the
    # cookbook, we may want to pretend that an item belong a given group while
    # it actually belongs to the system group.
    if (ARTIFACT == 'cookbook'
        && $group_id == $sys_group_id && isset ($comingfrom)
        && $comingfrom && ctype_digit (strval ($comingfrom)))
      $group_id = $comingfrom;
  }

# If we got a forum_id and no group_id, we need to get the appropriate
# group_id.
# (FIXME: in the future it could follow the naming scheme of trackers)
if (!isset ($group_id) && !isset ($group) && isset ($forum_id))
  {
    $result = db_execute (
      "SELECT group_id FROM forum_group_list WHERE group_forum_id=?",
      [$forum_id]
    );
    if (db_numrows ($result) > 0)
      $group_id = db_result ($result, 0, 'group_id');
  }

# If we got a msg_id and no group_id, we need to get the appropriate
# group_id.
# (FIXME: in the future it could follow the naming scheme of trackers).
if (!isset ($group_id) && isset ($msg_id))
  {
    $result = db_execute ("
      SELECT
        forum_group_list.group_id, forum_group_list.forum_name,
        forum.group_forum_id, forum.thread_id
      FROM forum_group_list, forum
      WHERE
        forum_group_list.group_forum_id = forum.group_forum_id
        AND forum.msg_id = ?",
      [$msg_id]
    );
    if ($result)
      $group_id = db_result ($result, 0, 'group_id');
  }

# Define $group_id if $group is set; define $group if $group_id is set;
# return group status or null when the group isn't defined or doesn't exist.
function init_group_vars ()
{
  global $group, $group_id;
  $var = 'group_id'; $co_var = 'group';
  $fields = ['group_id' => 'group_id', 'group' => 'unix_group_name'];
  if (empty ($group_id))
    {
      if (empty ($group))
        return null;
      $var = 'group'; $co_var = 'group_id';
    }
  $res = db_execute (
    "SELECT unix_group_name, group_id, status
     FROM groups WHERE {$fields[$var]} = ?", [$$var]
  );
  if (db_numrows ($res) <= 0)
    return null;
  $row = db_fetch_array ($res);
  $$co_var = $row[$fields[$co_var]];
  return $row['status'];
}

function init_check_group_status ($status)
{
  if ($status == 'A' || user_is_super_user ())
    return;
  exit_permission_denied ();
}

# Make sure we are on the correct site.
function init_run_redirections ($group_id)
{
  global $sys_debug_nobasehost;

  if ($sys_debug_nobasehost)
    return;
  $group = project_get_object ($group_id);
  $type_host = $group->getTypeBaseHost ();
  if (!(strcasecmp ($_SERVER['HTTP_HOST'], $type_host) && $type_host))
    return;
  $prot = session_protocol () . '://';
  session_redirect ("$prot$type_host{$_SERVER["REQUEST_URI"]}");
}

function init_check_group ()
{
  global $group_id;

  $status = init_group_vars ();
  if (empty ($group_id))
    return;
  if (empty ($status))
    exit_error (sprintf (_("Group #%s not found"), $group_id));
  init_check_group_status ($status);
  init_run_redirections ($group_id);
};

init_check_group ();
unset ($group_row);
function init_ensure_constants ()
{
  if (!defined ('SV_THEME'))
    define ('SV_THEME', $GLOBALS['sys_themedefault']);
  if (!defined ('CONTEXT'))
    context_guess ();
  if (!defined ('AUDIENCE'))
    user_guess ();
}
?>
