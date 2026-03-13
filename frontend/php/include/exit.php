<?php
# Generic functions to clean exit on error.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
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

require_once (dirname (__FILE__) . "/utils.php");

define ('EXIT_403', '403 Forbidden');
define ('EXIT_404', '404 Not Found');

# Base function. The alternatives below should be used whenever relevant,
# as they may wrap this one with additional useful things
# (set HTTP response, etc).
function exit_error ($title = '', $text = null, $status = false)
{
  global $HTML;

  exit_header ($status);
  init_ensure_constants ();
  $msg = $title;
  if ($msg === null)
    $msg = $text;
  elseif ($text)
    # TRANSLATORS: this string separates error title from further description,
    # like _("Invalid User") . _(': ') . _("That user does not exist.")
    $msg .= _(': ') . $text;
  if ($title !== null)
    $msg = html_h (1, _("Error")) . "<p>$msg</p>\n";

  $HTML->header (['title' => _("Exiting with Error"), 'notopmenu' => 1]);
  html_feedback_top ();
  print $msg;
  $HTML->footer ([]);
  exit;
}

function exit_permission_denied ($text = null)
{
  $status = EXIT_403;
  exit_log ("permission denied");
  exit_error (_("Permission Denied"), $text, $status);
}

function exit_not_logged_in ()
{
  # Instead of a simple error page, take user to the login page.
  global $REQUEST_URI, $sys_https_host, $sys_default_domain;

  $uri = utils_urlencode ($REQUEST_URI);
  $domain = "http://$sys_default_domain";
  if (!empty ($sys_https_host))
    $domain = "https://$sys_https_host";

  session_redirect ("$domain/account/login.php?uri=$uri");
}

function exit_no_group ()
{
  exit_error (_("No group chosen"));
}

function exit_if_no_group ()
{
  global $group_id;
  if (empty ($group_id))
    exit_no_group ();
  $group = group_get_object ($group_id);
  if ($group->isError ())
    exit_no_group ();
}

function exit_missing_param ($param_list = [])
{
  exit_error (_("Missing Parameters"), join (', ', $param_list));
}

function exit_if_missing ($param_list, $var_array = null)
{
  if (!is_array ($param_list))
    $param_list = [$param_list];
  $missing = [];
  foreach ($param_list as $p)
    {
      if ($var_array === null && !empty ($GLOBALS[$p]))
        continue;
      if ($var_array !== null && !empty ($var_array[$p]))
        continue;
      $missing[] = $p;
    }
  if (!empty ($missing))
    exit_missing_param ($missing);
}

# Standardize the way we log important exit on error.
function exit_log ($message)
{
  if (empty ($GLOBALS['sys_log_exits']))
    return;
  $username = "anonymous user";
  if (user_isloggedin ())
    $username = "user " . user_getname ();
  trigger_error ("$message - $username at " . $_SERVER['REQUEST_URI']);
}

# Standardize the HTTP error head
# (not cgi compliant, but Savane not supposed to run with PHP as CGI but
# as apache module).
function exit_header ($status = false)
{
  if (headers_sent ())
    return false;

  if (!$status)
    $status = EXIT_404;

  header("{$_SERVER['SERVER_PROTOCOL']} $status");
}

# Exit unless group uses mailing lists.
function exit_test_usesmail ($group_id)
{
  $project = project_get_object ($group_id);
  if (!$project->Uses ('mail'))
    exit_error (_("Error"), _("This project has turned off mailing lists"));
}

function exit_user_not_found ($user)
{
  if (ctype_digit ($user))
    $msg = _("User #%s not found.");
  else
    $msg = _("User *%s* not found.");
  exit_error (markup_rich (sprintf ($msg, $user)));
}
?>
