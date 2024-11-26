<?php
# Sample configuration file with comments.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2024 Ineiev
#
# Copying and distribution of this file, with or without modification,
# are permitted in any medium without royalty provided the copyright
# notice and this notice are preserved.  This file is offered as-is,
# without any warranty.

# Note: the configure script generates a file with basic settings
# from sysconf/savane.conf.php.in.  This file resembles the configuration
# used at Savannah.

# Host of Savane instance.
$sys_default_domain = "savannah.gnu.org";
# Port to use.
$sys_www_server_port = '4913';
# Host to use with HTTPS.
$sys_https_host = $sys_default_domain;
# Host of 'brother' Savane instance.
# The configuration of the 'brother' host is expected to point
# at our $sys_default_domain.
$sys_brother_domain = "savannah.nongnu.org";
# Host for serving files.
$sys_file_domain = "file.$sys_default_domain";
if ($sys_www_server_port != 80)
  foreach (['default', 'brother', 'file'] as $v)
    ${"sys_{$v}_domain"} .= ":$sys_www_server_port";

# Set to true to avoid automatic redirection to 'brother' host
# when accessing a group hosted there.  The group will be just shown
# in 'our' host.
$sys_debug_nobasehost = false;

# Parameters for database access.
$sys_dbhost = "127.51.188.169";
$sys_dbname = "savane";
$sys_dbuser = "savannahscripts";
$sys_dbpasswd =
  rtrim (file_get_contents (dirname (__FILE__) . '/savane.pass'));
# Whether to access the database through network;
# set to 'no' when using a socket.
$sys_dbnetworking = 'yes';

# The directory from frontend/site-specific/ to use.
$sys_incdir = "gnu";
# Name of Savane instance.
$sys_name = "Savane-dev";
# The name of Savane administration group.
$sys_unix_group_name = "administration";
# The default theme.
$sys_themedefault = "Savannah";
# The path to Graphviz executable.
$sys_graphviz = "/usr/bin/dot";
# Template for the Reply-To: header in tracker follow-up notifications,
# see doc/comments-via-email.
$sys_reply_to = '%UID-%TRACKER-sv-dev <savane-trackers@example.org>';

# URL of the Cgit instance hosting the corresponding source code.
# Used to implement AGPL reqirements.
$sys_savane_cgit = '//git.example.org/cgit/savane/savane.git';

# Address to redirect all email to (don't set if not needed).
$sys_debug_email_override_address = "webmasters@localhost";
unset ($sys_debug_email_override_address);

# Just an auxiliary variable to base other variables on it.
$sys_appdatadir = "/var/lib/savane";

# Directory for group registration uploads.
$sys_upload_dir = "$sys_appdatadir/submissions_uploads/";
# Directory for tracker attachments.
$sys_trackers_attachments_dir = "{$sys_upload_dir}trackers_attachments";

# Parameters of the logo.
$sys_logo_name = "floating.png";
$sys_logo_name_width = "148";
$sys_logo_name_height = "125";

# Domain used in the 'From:' header.
$sys_mail_domain = "localhost";
# Mailbox used for reports to Savane admins.
$sys_mail_admin = "savannah-reports-private";
# Mailbox used in the 'From:' header.
$sys_mail_replyto = "INVALID.NOREPLY";

# New forum comments are not supported.
$sys_enable_forum_comments = 0;

# Whether to use a simple anti-spam test on user registration page.
$sys_registration_text_spam_test = 0;
# Whether to use captcha on user registration page.
$sys_registration_captcha = 1;
# Maximum size of uploaded files.
$sys_upload_max = 16384;

# The directory where Savane configuration files are located.
$sys_etc_dir = "/etc/savane";

# Command to connect mailman.
$sys_mailman_wrapper = "ssh -T list@lists.gnu.org";

# Send a copy of anonymous comments to $sys_mail_admin.
$sys_watch_anon_posts = true;
# Send a copy of comments of users newer than the specified number
# to $sys_mail_admin.
$sys_new_user_watch_days = 34;

# Git configuration, see doc/git-repos.
$sys_vcs_dir = [
  'git' => ['dir' => "$sys_appdatadir/vcs/git", 'clone-path' => '/srv/git']
];

# The GnuPG executable.
$sys_gpg_name = 'gpg';
# Directory where email signing key is located,
# see doc/signed-notifications.
$sys_gpg_home = "$sys_appdatadir/gnupg";
# Enabled languages for web UI.
$sys_linguas = "en:es:fr:he:pt:pt-br:ru";
# Report PHP errors to specified addresses, see doc/cc-error.
$sys_cc_error = [
  'sv-dev@example.org' => ['exclude' => ['[E_USER_NOTICE] permission denied']]
];

# Add debugging summary to error log every time when page output completes.
$sys_debug_footer = true;
# Character set to select when connecting to the database.
# It looks like various combinations of DB server versions and database
# initialization settings may call for specific character sets when connecting
# to the database.  In testconfig.php, a few values are tested if needed.
$sys_dbcharset = 'utf8';
# Number of rounds used to hash passwords.
$sys_pw_rounds = 500000;

# Minimum user ID and group ID numbers used for Savane users and groups
# for SSH access.
$sys_min_uidNumber = 5000;
$sys_min_gidNumber = 5000;

# The group file for libnss-extrausers updated every time when users are added
# or removed from any group.
$sys_group_file = "$sys_appdatadir/group";
# The passwd file for libnss-extrausers updated every time when uidNumber
# is assigned.
$sys_passwd_file = "$sys_appdatadir/passwd";
# Settings used when updating $sys_passwd_file.
$sys_passwd_common_gid = 1003;
$sys_passwd_home_dir = "$sys_pkgconfdir/user-home-dir";
$sys_passwd_user_shell = "$sys_pkgconfdir/user-shell";

# The limiting value for the maximum item number shown at once when browsing
# trackers.
$sys_max_items_per_page = 1000;
?>
