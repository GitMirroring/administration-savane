<?php
# Utility functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2002-2006 Tobias Toedter <t.toedter--gmx.net>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2024 Ineiev
# Copyright (C) 2022, 2024 Bob Proulx
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
namespace {
# Clean initialization for globals.
$GLOBALS['feedback_count'] = 0;
$GLOBALS['feedback'] = '';
$GLOBALS['ffeedback'] = '';

define('FB_ERROR', 1);

# Get path for site-specific content.
function utils_get_content_filename ($file)
{
  global $sys_incdir;

  $f = dirname (__FILE__) . "/../../site-specific/$sys_incdir/$file.php";
  if (is_file ($f))
    return $f;

  $f = "$sys_incdir/php/$file.php"; # Deprecated old location.
  if (is_file ($f))
    return $f;
  return null;
}

# Include site-specific content.
function utils_get_content ($filename)
{
  $file = utils_get_content_filename($filename);
  if ($file != null)
    include($file);
}

# Make sure that to avoid malicious file paths.
function utils_check_path ($path)
{
  if (strpos ($path, "../") !== FALSE)
    exit_error (_('Error'),
      # TRANSLATORS: the argument is file path.
      sprintf (_('Malformed file path %s'), $path)
    );
}

# Return text for a control: a link when $available, else a <span>
# with 'unavailable' CSS class.  Unavailable "links" are written
# with different tags to let CSS-unaware browsers distinguish them.
function utils_link (
  $url, $title, $defaultclass = 0, $available = 1, $help = 0, $extra = ''
)
{
  $closing_tag = '</a>';
  $return = "<a href=\"$url\"";

  if (!$available)
    {
      $defaultclass = 'unavailable';
      $title = "<del>$title</del>";
      $return = '<span';
      $closing_tag = '</span>';
    }

  if ($defaultclass)
    $return .= " class=\"$defaultclass\"";
  if ($help)
    $return .= " title=\"$help\"";
  if ($extra)
    $return .= " $extra";
  return "$return>$title$closing_tag";;
}

# Make an clean email link depending on the authentification level of the user.
# Don't use this on normal text, just on field where only an email address is
# expected.  This may corrupt the text and does extensive search.
function utils_email ($address, $nohtml = 0)
{
  if  (!user_isloggedin ())
    {
      if ($nohtml)
        return _("-email is unavailable-");
      return utils_help (
        _("-email is unavailable-"),
        _("This information is not provided to anonymous users")
      );
    }
  if ($nohtml)
    return $address;

  # Remove eventual extra white spaces.
  $address = trim ($address);

  # If we have < > in the address, only this content must go in the mailto.
  $realaddress = null;;
  if (preg_match ("/\<([\w\d\-\@\.]*)\>/", $address, $matches))
    $realaddress = $matches[1];

  $addr = utils_specialchars ($address);
  $raddr = utils_specialchars ($realaddress);
  # We have a user name.
  if (!strpos ($address, "@"))
    {
      # We found a real address and it is a user login.
      $uid = user_getid ($realaddress);
      if ($realaddress && user_exists ($uid))
        return utils_user_link ($realaddress, user_getrealname ($uid));
      # The whole address is a user login.
      $uid = user_getid ($address);
      if (user_exists ($uid))
        return utils_user_link ($address, user_getrealname ($uid));

      # No @, no real addresses and spaces inside? Looks like someone
      # forgot commas.
      if (!$realaddress && strpos ($address, " "))
        return $addr . ' <span class="warn">'
          . _("(address seems invalid and will probably be ignored)")
          . '</span>';

      # No @ but and not a login?
      # TRANSLATORS: the argument is mail domain (like localhost or
      # sv.gnu.org).
      $msg = sprintf (
        _("(address is unknown to Savane, will fail if not valid at %s)"),
        $GLOBALS['sys_mail_domain']
      );
      return "$addr <span class='warn'>$msg</span>";
    }

  # If we are here, it means that we have an @ in the address,
  # Even if the address is invalid, the system is likely to try to
  # send the mail, and we have no way to know if the address is valid.
  # We will only do a check on the address syntax.

  # We found a real address that is syntactically correct.
  if ($realaddress && validate_email ($realaddress))
    return "<a href=\"mailto:$raddr\">$addr</a>";

  # We found real address but it does not seem correct. Print a warning.
  if ($realaddress)
    return $addr . ' <span class="warn">'
      . _("(address seems invalid and will probably be ignored)")
      . '</span>';
  # No realaddress found, only one string that is an address.
  if (validate_email ($address))
    return "<a href=\"mailto:$addr\">$addr</a>";
  return $addr . ' <span class="warn">'
    . _("(address seems invalid and will probably be ignored)")
    . '</span>';
}

# Like the previous, but does no extended search, just print as it comes.
function utils_email_basic ($address, $nohtml = 0)
{
  if (user_isloggedin () || CONTEXT == 'forum' || CONTEXT == 'news'
      || CONTEXT == '' # frontpage
  )
    {
      $addr = utils_specialchars ($address);
      if ($nohtml)
        return $addr;
      # Make mailto without trying to find out whether it makes sense.
      return "<a href=\"mailto:$addr\">$addr</a>";
    }

  if ($nohtml)
    return _("-email is unavailable-");
  return utils_help (
    _("-email is unavailable-"),
    _("This information is not provided to anonymous users")
  );
}

# Find out if a string is pure ASCII or not.
function utils_is_ascii ($string)
{
  return preg_match ('%^[[:ascii:]]*$%', $string);
}

# Alias function.
function utils_altrow ($i)
{
  return html_get_alt_row_color ($i);
}

function utils_cutstring ($string, $length = 35)
{
  $string = rtrim ($string);
  if (strlen ($string) > $length)
    {
      $string = substr ($string, 0, $length);
      $string = substr ($string, 0, strrpos ($string, ' '));
      $string .= "...";
    }
  return $string;
}

function utils_utils_strftime ($timestamp, $format)
{
  $env['LC_ALL'] = setlocale (LC_TIME, 0);
  $cmd = "date +$format -d @$timestamp";
  utils_run_proc ($cmd, $output, $error, ['env' => $env]);
  $output = substr ($output, 0, -1); # Drop trailing "\n".
  return $output;
}

if (function_exists ("strftime"))
  {
    # Used until strftime is dropped from PHP.
    function utils_strftime ($timestamp, $format)
    {
      $state = utils_disable_warnings (E_DEPRECATED);
      $ret = strftime ($format, $timestamp);
      utils_restore_warnings ($state);
      return $ret;
    }
  }
else # !function_exists ("strftime")
  {
    # Fallback for the PHP versions (> 8.1) when no strftime is provided.
    function utils_strftime ($timestamp, $format)
    {
      return utils_utils_strftime ($timestamp, $format);
    }
  } # !function_exists ("strftime")

# Return a formatted date for a unix timestamp.
#
# The given unix timestamp will be formatted according to
# the $format parameter. Note that this parameter is not one
# of the format strings supported by functions such as
# date (), but a description instead.
#
# Currently, you can use the following values for $format:
#   - default => localized Fri Nov 18 18:51 GMT 2005
#   - natural => 2005-11-18 or (for recent events) 18:51.
#   - minimal => 2005-11-18
#
# @see utils_date_to_unixtime()
function utils_format_date ($timestamp, $format = "default")
{
  if (empty ($timestamp))
    return '-';

  $tm = localtime ($timestamp, true);

  switch ($format)
    {
    case 'natural':
      if (time () < 12 * 60 * 60 + $timestamp && time () > $timestamp)
        # Nearest past events are shown as time.
        return sprintf ("%02d:%02d", $tm['tm_hour'], $tm['tm_min']);
      # Fall through.
    case 'minimal':
      # To be used where place is really lacking, like in feature boxes.
      # Let's use a non-ambiguous format, such as ISO 8601's YYYY-MM-DD
      # extended calendar format.
      return sprintf (
        "%04d-%02d-%02d", $tm['tm_year'] + 1900, $tm['tm_mon'] + 1,
        $tm['tm_mday']
      );
    }
  # The preferred date and time representation for the current locale.
  return utils_strftime ($timestamp, '%c');
}

# Convert a date as used in the bug tracking system and other services (YYYY-MM-DD)
# into a Unix time.
# Return a list with two values: the unix time and a boolean saying whether
# the conversion went well (true) or bad (false).
function utils_date_to_unixtime ($date)
{
  $res = preg_match ("/\s*(\d+)-(\d+)-(\d+)/", $date, $match_arr);
  if ($res == 0)
    return [0, false];
  list (, $year, $month, $day) = $match_arr;
  $time = mktime (0, 0, 0, $month, $day, $year);
  return [$time, true];
}

function utils_read_file ($filename)
{
  @$fp = fopen ($filename, "r");
  if (!$fp)
    return false;
  $val = fread ($fp, filesize ($filename));
  fclose ($fp);
  return $val;
}

function utils_filesize ($filename, $file_size = 0)
{
  # If file size is defined, assume that we just want an unit conversion.

  # Round results: Savane is not a math software.
  if (!isset ($file_size))
    $file_size = filesize ($filename);

  if ($file_size >= 1048576)
    # TRANSLATORS: this expresses file size.
    $file_size = sprintf (_("%sMiB"), round ($file_size / 1048576));
  elseif ($file_size >= 1024)
    # TRANSLATORS: this expresses file size.
    $file_size = sprintf(_("%sKiB"), round ($file_size / 1024));
  else
    # TRANSLATORS: this expresses file size.
    $file_size = sprintf(_("%sB"), round ($file_size));
  return $file_size;
}

# Return human-readable sizes.
# This is public domain, original version from:
# Author:      Aidan Lister <aidan@php.net>
# Version:     1.1.0
# Link:        http://aidanlister.com/repos/v/function.size_readable.php
# Param:       int    $size        Size
# Param:       int    $unit        The maximum unit
# Param:       int    $retstring   The return string format
# Param:       int    $si          Whether to use SI prefixes
function utils_size_readable ($size, $unit = null, $retstring = null, $si = false)
{
  # Units.
  if ($si === true)
    {
      $sizes = [
        # TRANSLATORS: this is file size unit (no prefix).
        _('B'),
        # TRANSLATORS: this is file size unit (with SI prefix.)
        _('kB'), _('MB'), _('GB'), _('TB'), _('PB')
      ];
      $mod   = 1000;
    }
  else
    {
      $sizes = [
        # TRANSLATORS: this is file size unit (no prefix).
        _('B'),
        # TRANSLATORS: this is file size unit (with binary prefix.)
        _('KiB'), _('MiB'), _('GiB'), _('TiB'), _('PiB')
      ];
      $mod   = 1024;
    }
  $ii = count ($sizes) - 1;

  # Find maximum unit applicable.
  $unit = array_search ((string) $unit, $sizes);
  if ($unit === null || $unit === false)
    $unit = $ii;

  if ($retstring === null)
    $retstring = '%01.2f%s';
  $i = 0;
  while ($unit != $i && $size >= 1024 && $i < $ii)
    {
      $size /= $mod;
      $i++;
    }
  return sprintf ($retstring, $size, $sizes[$i]);
}

function utils_unconvert_htmlspecialchars ($string)
{
  if (strlen ($string) < 1)
    return '';
  return str_replace (
    ['&nbsp;', '&quot;', '&gt;', '&lt;', '&#039;', '&apos;',  '&amp;'],
    [' ',      '"',      '>',    '<',     "'",      "'",      '&'],
    $string
  );
}

# Take a result set and turn the optional column into an array.
function utils_result_column_to_array ($result, $col = 0, $localize = false)
{
  $rows = db_numrows ($result);

  $arr = [];
  if ($rows <= 0)
    return $arr;
  for ($i = 0; $i < $rows; $i++)
    {
      $val = db_result ($result, $i, $col);
      if ($localize)
        $val = utils_specialchars (gettext ($val));
      $arr[$i] = $val;
    }
  return $arr;
}

function utils_user_link ($username, $realname = false, $noneisanonymous = false)
{
  global $sys_home;

  if ($username == 'None' || empty($username))
    {
      # Would be nice to always return _("Anonymous"); but in some cases it is
      # really none (assigned_to).
      if (!$noneisanonymous)
        # TRANSLATORS: Displayed when no user is selected.
        return _('None');
      # TRANSLATORS: anonymous user.
      return _("Anonymous");
    }
  $re = "<a href=\"{$sys_home}users/$username\">";
  if ($realname)
    $re .= "$realname &lt;$username&gt;";
  else
    $re .= $username;
  return "$re</a>";
}

function utils_registration_history ($unix_group_name)
{
  # Meaningless with chrooted system; all www system should be chrooted.
}

function show_priority_colors_key ()
{
  print '<p class="smaller">';
  print _("Open Items Priority Colors:") . "<br />&nbsp;&nbsp;&nbsp;\n";

  for ($i = 1; $i < 10; $i++)
    print '<span class="' . utils_get_priority_color ($i) . '">&nbsp;'
      . "$i&nbsp;</span>\n";

  print "<br />\n";
  print _("Closed Items Priority Colors:") . "<br />&nbsp;&nbsp;&nbsp;\n";

  for ($i = 11; $i < 20; $i++)
    print '<span class="' . utils_get_priority_color ($i) . '">&nbsp;'
      . ($i-10) . "&nbsp;</span>\n";
  print  "</p>\n";
}

function utils_get_tracker_list ()
{
  return ['bugs', 'support', 'task', 'patch', 'cookbook'];
}

function utils_get_tracker_icon ($tracker)
{
  if ($tracker == "bugs")
    return "bug";
  if ($tracker == "support")
    return "help";
  if ($tracker == "cookbook")
    return "man";
  return $tracker;
}

function utils_get_tracker_prefix ($tracker)
{
  if ($tracker == "bugs")
    return "bug";
  if ($tracker == "support")
    return "sr";
  if ($tracker == "cookbook")
    return "recipe";
  return $tracker;
}

# Return the localized name for the given tracker, if available.
# Otherwise, return the input string.
function utils_get_tracker_name ($tracker)
{
  if ($tracker == 'bugs')
    return _('bugs');
  if ($tracker == 'cookbook')
    return _('recipes');
  if ($tracker == 'patch')
    return _('patches');
  if ($tracker == 'support')
    return _('support requests');
  if ($tracker == 'task')
    return _('tasks');
  return $tracker;
}

function utils_get_priority_color ($index, $closed = "")
{
  global $bgpri;
  # If the item is closed, add ten to the index number to get closed colors.
  if ($closed == 3)
    $index = $index + 10;

  if (isset ($bgpri[$index]))
    return $bgpri[$index];

  return 'unknown-priority';
}

# Very simple, plain way to show a generic result set.
# Accepts a result set and title.
# Makes certain items into HTML links.
function utils_show_result_set (
  $result, $title = "Untitled", $linkify = false, $level = false
)
{
  global $group_id, $HTML, $php_self;

  if ($level === false)
    $level = '3';

  if ($title == "Untitled")
    $title = _("Untitled");

  if  (!$result)
    {
      print db_error ();
      return;
    }
  $rows = db_numrows ($result);
  $cols = db_numfields ($result);

  print "<h$level>$title</h$level>\n";
  print "<table border='0' width='100%' summary=\"$title\">\n";

  print "<tr>\n";
  for ($i = 0; $i < $cols; $i++)
    print '<th>' . db_fieldname ($result,  $i) . "</th>\n";
  print "</tr>\n";

  $lhead = "<a href=\"$php_self?group_id=$group_id&";
  for ($j = 0; $j < $rows; $j++)
    {
      switch ($linkify)
        {
        case "bug_cat":
          $lhead .= "bug_cat_mod=y&bug_cat_id="
            . db_result ($result, $j, 'bug_category_id') . '">';
          break;
        case "bug_group":
          $lhead .= "bug_group_mod=y&bug_group_id="
            . db_result ($result, $j, 'bug_group_id') . '">';
          break;
        case "patch_cat":
          $lhead .= "patch_cat_mod=y&patch_cat_id="
            . db_result ($result, $j, 'patch_category_id') . '">';
          break;
        case "support_cat":
          $lhead .= "support_cat_mod=y&support_cat_id="
            . db_result ($result, $j, 'support_category_id') . '">';
          break;
        case "pm_project":
          $lhead .= "project_cat_mod=y&project_cat_id="
            . db_result ($result, $j, 'group_project_id') . '">';
          break;
        default:
          $linkify = false;
          $link = $linkend = '';
       }
      if ($linkify)
        {
          $link = $lhead;
          $linkend = '</a>';
        }
      print '<tr class="' . utils_altrow ($j) . '">';
      for ($i = 0; $i < $cols; $i++)
        {
          $res_ji = db_result ($result,  $j,  $i);
          print "<td>$link$res_ji$linkend</td>\n";
          $link = $linkend = '';
        }
      print "</tr>\n";
    }
  print "</table>\n";
}

# Clean up email address (remove spaces...) and put to lower case.
function utils_cleanup_emails ($addresses)
{
  # It was previously removing white spaces:
  # This is a bad idea. If we want to remove spaces, we have to check user
  # input, not to corrupt manually entered data afterwards.
  # If we allow white space to be entered, then we have to keep them.
  # For instance, if we allow to be entered: Robert <bob@bla.org>
  # it must not end up in Robert<bob@bla.org>.
  # (And we want to allow CC to be added like in a mail client).
  return strtolower ($addresses);
}

# Clean up email address (remove spaces...) and add @... if it is a simple
# login name.
function utils_normalize_email ($address)
{
  $address = utils_cleanup_emails ($address);
  if (validate_email ($address))
    return $address;
  return "$address@" . $GLOBALS['sys_mail_domain'];
}

# Clean up email address (remove spaces...) and split comma separated emails.
function utils_split_emails ($addresses)
{
  $addresses = utils_cleanup_emails ($addresses);
  $addresses = str_replace (";", ",", $addresses);
  return explode (',', $addresses);
}

function validate_email ($address)
{
  if (empty ($address))
    return false;
  # FIXME: this allows some characters in domain names that are not allowed.
  return (preg_match (',^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'. '@'
                      . '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.'
                      . '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$,', $address));
}

# Verify comma-separated list of email addresses.
function validate_emails ($addresses)
{
  $arr = utils_split_emails ($addresses);

  foreach ($arr as $addr)
    if (!validate_email ($addr))
      return false;
  return true;
}

function utils_is_valid_filename ($file)
{
  if (preg_match ("/[]~`! ~@#\"$%^,&*();=|[{}<>?\/]/", $file))
    return false;
  if (strstr ($file,'..'))
    return false;
  return true;
}

# Add debugging information.
function util_debug ($msg)
{
  if (!$GLOBALS['sys_debug_on'])
    return;
  $backtrace = debug_backtrace ();
  $location = '';
  if (isset ($backtrace[1]))
    $location = $backtrace[1]['function'];
  else
    {
      $relative_path = str_replace (
        $GLOBALS['sys_www_topdir'] . '/', '', $backtrace[0]['file']
      );
      $location = "$relative_path:{$backtrace[0]['line']}";
    }
  $GLOBALS['debug'] .= "($location) $msg<br />";
}

function dbg ($msg)
{
  util_debug ($msg);
}

# Temporary debug.
# Use it instead of 'echo' so you can easily spot and remove them later after
# debugging is done.
function temp_dbg ($msg)
{
  print '<pre>';
  debug_print_backtrace ();
  var_dump ($msg);
  print '</pre>';
}

# Die with debug information.
function util_die ($msg)
{
  trigger_error ($msg);
  $msg = utils_specialchars ($msg);
  if (empty ($GLOBALS['sys_debug_on']))
    die ($msg);
  print "<strong>Fatal error:</strong> $msg<br />";
  print '<pre>';
  debug_print_backtrace ();
  print '</pre>';
  die ();
}

# Add feedback information.
function fb ($msg, $error = 0)
{
  $GLOBALS['feedback_count']++;

  if ($GLOBALS['sys_debug_on'])
    {
      $msg .= ' [#' . $GLOBALS['feedback_count'] . ']';
      dbg("Add feedback #" . $GLOBALS['feedback_count']);
    }
  $msg .= "\n";
  if ($error)
    $GLOBALS['ffeedback'] .= $msg;
  else
    $GLOBALS['feedback'] .= $msg;
}

# Fb function to be used about database error when context of error is obvious.
function fb_dberror ()
{
  fb (_("Error updating database"), 1);
}

# Fb function to be used about database success when context is obvious.
function fb_dbsuccess ()
{
  fb (_("Database successfully updated"));
}

function utils_help ($text, $explanation_array)
{
  return help ($text, $explanation_array);
}

# Print help about a word.
#   $text is the sentence where ballons are
#   $explanation_array is the table word->explanation, must be in the
#   array syntax.
function help ($text, $explanation_array)
{
  if (!is_array ($explanation_array))
    return "<span class='help' title=\"$explanation_array\">$text</span>";
  foreach ($explanation_array as $word => $explanation)
    $text = str_replace (
      $word, "<span class='help' title=\"$explanation\">$word</span>", $text
    );
  return $text;
}

# Remove old $domain-based cookies, see Savannah sr #111062.
# After 2025-05-13 all such cookies will expire, and this function
# may be removed.
function utils_clear_spurious_cookie ($name, $value, $secure = false)
{
  global $sys_home, $sys_default_domain;
  $domain = preg_replace ('/:\d*$/', '', $sys_default_domain);
  setcookie (
    $name, $value, time () - 3600, $sys_home, $domain, $secure, true
  );
}

function utils_setcookie ($name, $value, $expire, $secure = false)
{
  global $sys_home;
  utils_clear_spurious_cookie ($name, $value, $secure);
  setcookie ($name, $value, $expire, $sys_home, '', $secure, true);
}

function utils_set_csp_headers ()
{
  global $skip_csp_headers;
  if (!empty ($skip_csp_headers))
    return;
  $skip_csp_headers = true; # Only set the headers once.
  # Set up proper use of UTF-8, even if the webserver doesn't serve it by default.
  header('Content-Type: text/html; charset=utf-8');
  # Disallow embedding in any frames.
  header('X-Frame-Options: DENY');
  # Declare more restrictions on how browsers may assemble pages.
  # Security issues apply even during fundrasing periods.  Please don't disable
  # this; instead, add some domain to img-src (or (better?) copy necessary
  # images to images/ or to submissions_uploads/).
  $policy = "Content-Security-Policy: default-src 'self'; frame-ancestors 'none'";
  if ($GLOBALS['sys_file_domain'] != $GLOBALS['sys_default_domain'])
    $policy .= "; img-src 'self' " . $GLOBALS['sys_file_domain'];
  header ($policy);
}

} # namespace {

namespace utils_run_proc_ns {
function dispatch_error ($cmd, $code, $err, $log_error, $in)
{
  if (!$code || !$log_error)
    return;
  $msg = "$cmd failed ($code): $err";
  if (!empty ($in))
    $msg .= "\n  with input:\n$in";
  trigger_error ($msg);
}

function init_aux ($aux)
{
  $in = null; $log_error = false;
  if (!empty ($aux['in']))
    $in = $aux['in'];
  if (!empty ($aux['log_error']))
    $log_error = true;
  $env = $_ENV;
  if (!empty ($aux['env']))
    foreach ($aux['env'] as $k => $v)
      $env[$k] = $v;
  return  [$in, $env, $log_error];
}

function close_pipes ($pipes, $proc)
{
  for ($i = 0; $i < 3; $i++)
    fclose ($pipes[$i]);
  proc_close ($proc);
}

function p_open ($cmd, $env, $log_error)
{
  $d_spec = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
  $proc = proc_open ($cmd, $d_spec, $pipes, NULL, $env);
  if ($proc !== false)
    return [null, '', $pipes, $proc];
  $err = "can't run $cmd\n";
  utils_run_proc_ns\dispatch_error ($cmd, -1, $err, $log_error);
  return ['fail', $err, null, null];
}

function write ($fd, $in, $cmd, $log_error)
{
  if (empty ($in))
    return [null, ''];
  $ns = fwrite ($fd, $in);
  $len = strlen ($in);
  if ($ns === $len)
    return [null, ''];
  if ($ns === false)
    $err = "$cmd: can't pass input data\n";
  else
    $err = "$cmd: can't pass input data; wrote $ns of $len bytes\n";
  return ['fail', $err];
}
} # namespace utils_run_proc_ns

namespace {
# Run a command $cmd, return its exit code; put its output and error streams
# to $out and $err; in $aux, ['in'] enters to stdin, ['env'] modifies
# the environment, if ['log_error'], errors are logged.
function utils_run_proc ($cmd, &$out, &$err, $aux = [])
{
  list ($in, $env, $log_error) = utils_run_proc_ns\init_aux ($aux);
  $out = null;
  list ($ret, $err, $pipes, $proc) =
    utils_run_proc_ns\p_open ($cmd, $env, $log_error);
  if (!empty ($ret))
    return $ret;
  list ($ret, $err) =
    utils_run_proc_ns\write ($pipes[0], $in, $cmd, $log_error);
  if (!empty ($ret))
    {
      utils_run_proc_ns\close_pipes ($pipes, $proc);
      return $ret;
    }
  fclose ($pipes[0]);
  $out = stream_get_contents ($pipes[1]);
  $err = stream_get_contents ($pipes[2]);
  fclose ($pipes[1]); fclose ($pipes[2]);
  $res = proc_close ($proc);
  utils_run_proc_ns\dispatch_error ($cmd, $res, $err, $log_error, $in);
  return $res;
}

function utils_disable_warnings ($level = E_ALL, $dry_run = false)
{
  if ($dry_run)
    return null;
  return [error_reporting (E_ALL ^ $level)];
}

function utils_restore_warnings ($state)
{
  if ($state !== null)
    error_reporting ($state[0]);
}

# Try to move $tmp_path to $path without overwriting if the latter exists;
# return $path when successful, $tmp_path otherwise.
function utils_try_move ($tmp_path, $path)
{
  $error_state = utils_disable_warnings (E_WARNING);
  $res = link ($tmp_path, $path);
  utils_restore_warnings ($error_state);
  if (!$res) # Already exists; fallback to temporary file name.
    return $tmp_path;
  unlink ($tmp_path);
  return $path;
}

function utils_mktemp ($template, $type = 'file')
{
  $cmd = "mktemp --tmpdir ";
  if ($type !== 'file')
    {
      $type = 'dir';
      $cmd .= "-d ";
    }
  if (substr ($template, -3) !== 'XXX')
    $template .= '.XXXXXXXXX';
  $cmd .= $template;
  $res = utils_run_proc ($cmd, $out, $err);
  if ($res)
    {
      trigger_error ("'$cmd' failed, $res: $err");
      return null;
    }
  $out = trim ($out);
  $is_func = "is_$type";
  if ($is_func ($out))
    return $out;
  trigger_error ("'$cmd' failed: no $out $type");
  return null;
}

function utils_rm_fr ($dir)
{
  exec ("rm -fr $dir");
}

# Make a file with a name based on $tarball_name in $sys_upload_dir without
# overwriting existing files; the new file name is $tarball_name unless
# such file already exists, otherwise a name based on a template is used.
# Return the path to the new file; in case of failure return null and
# put a diagnostic string to $errors.
function utils_make_upload_file ($tarball_name, &$errors)
{
  global $sys_upload_dir;
  $name = $tarball_name;
  $path = "$sys_upload_dir/$name";
  # It might be easier to use tempnam (), but it has no --suffix feature.
  $name = strtr ($name, "'/", ".-");
  $res = utils_run_proc (
    "mktemp -p \"$sys_upload_dir\" --suffix='-$name' XXXXXX", $out, $err
  );
  if ($res)
    {
      # Can't create a temporary file; $path may work,
      # but it would at least create a race condition, so just don't proceed.
      $errors = "$res: $err";
      return null;
    }
  $out = substr ($out, 0, -1); # Remove trailing "\n".
  return utils_try_move ($out, $path);
}

# A helper function (mix of str_repeat and implode) used further.
function utils_str_join ($separator, $str, $n)
{
  return str_repeat ("$str$separator", $n - 1) . $str;
}

# Return a random number from 0 to $end.
# For sensitive tokens, use random_bytes instead.
function utils_mt_rand ($end = 1000000)
{
  $micro = 1000000;
  $t = (int)(microtime (true) * $micro);
  mt_srand ($t % $micro);
  return mt_rand (0, $end);
}

function utils_placeholders ($array)
{
  return utils_str_join (', ', '?', count ($array));
}

function utils_in_placeholders ($array)
{
  return "IN (" . utils_placeholders ($array) . ")";
}

function utils_update_decimal_separator ()
{
  global $decimal_separator;
  $loc = localeconv ();
  $decimal_separator = $loc['decimal_point'];
  return $decimal_separator;
}

# RFC822 requires some characters to be escaped. We usually care about this
# compliance only in email headers.
function utils_comply_with_rfc822 ($string)
{
  if (preg_match ("#\.|\,|\@|\/|\\|\||\;|\!#", $string))
    return "\"$string\"";
  return $string;
}

# Work-around PHP 8.1 deprecation of null parameter #1 and change of defaults
# for parameter #2.
function utils_specialchars ($string, $flags = ENT_COMPAT)
{
  if ($string === null)
    $string = '';
  return htmlspecialchars ($string, $flags, 'UTF-8');
}
function utils_specialchars_decode ($string, $flags = ENT_COMPAT)
{
  if ($string === null)
    $string = '';
  return htmlspecialchars_decode ($string, $flags);
}
function utils_htmlentities ($string, $flags = ENT_COMPAT)
{
  if ($string === null)
    $string = '';
  return htmlentities ($string, $flags, 'UTF-8');
}
function utils_urlencode ($string)
{
  if ($string === null)
    $string = '';
  return urlencode ($string);
}
function utils_disable_cache ()
{
  header ("Expires: Wed, 11 Nov 1998 11:11:11 GMT");
  header ("Cache-Control: max-age=0");
  header ("Cache-Control: no-cache", false);
  header ("Cache-Control: must-revalidate", false);
}
function utils_public_file_url ($file)
{
  global $sys_home;
  return "{$sys_home}file/{$file['name']}?file_id={$file['id']}";
}

# Find an item in a tracker, fetch the fields listed in $fields
# (but group_id in any case), return the array of fields on success,
# invoke $error_handler ('Item not found') on fail.
function utils_find_item (
  $tracker, $item_id, $fields = [], $error_handler = 'exit_error'
)
{
  if (!in_array ('group_id', $fields))
    $fields[] = 'group_id';
  $field_list = join (', ', $fields);
  $result = db_execute (
    "SELECT $field_list FROM $tracker WHERE bug_id = ?", [$item_id]
  );
  if (!db_numrows ($result))
    return $error_handler (sprintf (_("Item #%s not found."), $item_id));
  $arr = db_fetch_array ($result);
  $ret = [];
  foreach ($fields as $k)
    $ret[$k] = $arr[$k];
  return $ret;
}
} # namespace {
?>
