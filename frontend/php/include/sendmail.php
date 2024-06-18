<?php
# Every mails sent should be using functions listed here.
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
foreach (['utils', 'gpg', 'parsemail'] as $h)
  require_once (dirname (__FILE__) . "/$h.php");

function sendmail_signature ()
{
  global $int_delayspamcheck, $sys_default_domain, $sys_home, $sys_name;

  if (!empty ($int_delayspamcheck))
    return '';
  return "\n\n_______________________________________________\n"
    # TRANSLATORS: the argument is site name (like Savannah).
    . sprintf (_("Message sent via %s"), $sys_name)
    . "\nhttps://$sys_default_domain$sys_home\n";
}

function sendmail_format_body (&$message, $context)
{
  $message['sig'] = '';
  if (!empty ($context['skip_format_body']))
    return;
  $body = $message['body'];
  $body = wordwrap ($body, 78);
  $message['body'] = $body;
  $message['sig'] = sendmail_signature ();
}

function sendmail_post_format ($body, $context)
{
  if (!empty ($context['skip_format_body']))
     return $body;
  # Beuc - 20050316
  # That is what I intended to do:

  # All newlines should be \r\n; this is apparently more
  # RFC821-compliant.
  # $message = preg_replace("/(?<!\r)\n/", "\r\n", $message);

  # However the opposite is certainly more Mailman-compliant.
  return str_replace ("\r\n", "\n", $body);
}

function sendmail_x_savane_server_header ()
{
  $ret = '';
  if (!empty ($GLOBALS['int_delayspamcheck']))
    return $ret;
  foreach (['NAME' => '', 'PORT' => ':', 'ADDR' => ' '] as $key => $sep)
    {
      $val = '';
      $key = "SERVER_$key";
      if (!empty ($_SERVER[$key]))
        $val = trim ($_SERVER[$key]);
      if ($val !== '')
        $ret .= "$sep$val";
    }
  return "X-Savane-Server: $ret\n";
}

function sendmail_cc_addresses ($user_name, $context)
{
  if (!sendmail_have_reply_to ($context))
    return '';
  $emails = [];
  foreach ($user_name as $n)
    foreach ($n as $e)
      $emails[$e] = 1;
  return $emails;
}

function sendmail_reply_to_headers ($uid, $u_email, $context)
{
  global $sys_reply_to;
  if (!sendmail_have_reply_to ($context, $uid))
    return '';
  $reply_to = null;
  $cc = $context['cc'];
  $context['uid'] = $uid;
  unset ($cc[$u_email[0]]);
  $reply_to = sendmail_expand_template ($sys_reply_to, $context);
  $cc = array_keys ($cc);
  if ($reply_to !== null)
    array_unshift ($cc, $reply_to);
  return "Reply-To: " . sendmail_encode_recipients ($cc) . "\n";
}

function sendmail_savane_headers ($context)
{
  # Add a signature for the server (not if delayed, because it will be added
  # we the mail will be actually sent).
  $ret = sendmail_x_savane_server_header ();

  # Necessary for proper utf-8 support.
  # These headers are added in parsemail.php when signed email is formed.
  if (!parsemail_will_sign ())
    $ret .= "MIME-Version: 1.0\nContent-Type: text/plain;charset=UTF-8\n";

  foreach (['group' => 'X-Savane-Project', 'tracker' => 'X-Savane-Tracker',
    'item' => 'X-Savane-Item-ID'] as $k => $h
  )
  if (!empty ($context[$k]))
    $ret .= "$h: {$context[$k]}\n";
  return $ret;
}

function sendmail_extract_comment_id (&$context)
{
  $context['comment_id'] = 0;
  if (empty ($context['item']))
    return;

  # Look if there is a (internal) comment id set.
  if (strpos ($context['item'], ":"))
    list ($context['item'], $context['comment_id']) =
      explode (":", $context['item']);
}

function sendmail_create_msgid ()
{
  return date ("Ymd-His", time ()) . ".sv" . user_getid () . "."
    . utils_mt_rand () . "@" . $_SERVER["HTTP_HOST"];
}

function sendmail_msgid_headers ($context)
{
  $msg_id = sendmail_create_msgid ();
  $headers = "Message-Id: <$msg_id>\n";
  if (empty ($context['tracker']) || empty ($context['item']))
    return $headers;
  $tracker = $context['tracker']; $item_id = $context['item'];
  $refs = trackers_get_msgid ($tracker, $item_id);
  if (count ($refs))
    {
      $headers .= "References: " . join (' ', $refs) . "\n";
      # This code assumes that it's the latest message that's
      # replied to, which may be wrong, actually.
      $headers .= "In-Reply-To: {$refs[0]}\n";
    }
  trackers_register_msgid ($msg_id, $tracker, $item_id);
  return $headers;
}

function sendmail_logged_in_header ()
{
  if (!user_isloggedin ())
    return '';
  # User details: user agent and REMOTE_ADDR are not included
  # per Savannah sr #110592.
  return
    "X-Apparently-From: Savane authenticated user " . user_getname () . "\n";
}

function sendmail_build_headers ($from, &$context, &$message)
{
  sendmail_extract_comment_id ($context);
  # RFC-821 recommends to use \r\n as line break in headers but \n
  # works and there are report of failures with \r\n so we let \n for now.
  $headers = "From: " . sendmail_encode_header ($from) . "\n";

  $headers .= sendmail_logged_in_header ();
  $headers .= sendmail_savane_headers ($context);
  $headers .= sendmail_msgid_headers ($context);
  if (!empty ($message['headers']))
    foreach ($message['headers'] as $k => $v)
      $headers .= "$k: $v\n";
  $message['headers'] = $headers;
}

function sendmail_explode_addr_list ($to)
{
  if ($to === null)
    return [];
  $to = trim ($to);
  if ($to == "")
    return [];
  $to = str_replace ([";", ' '], [","], $to);
  $to = explode (",", $to);
  $ret = [];
  foreach ($to as $v)
    $ret[$v] = true;
  return $ret;
}

# If $addresses['from'] is a login name, replace it with a nice From: field.
# if no $addresses['from'] is empty, set it to default.
function sendmail_format_from (&$addresses)
{
  global $sys_mail_replyto, $sys_mail_domain;
  if (empty ($addresses['from']))
    $addresses['from'] = "$sys_mail_replyto@$sys_mail_domain";
  $uid = user_getid ($addresses['from']);
  if (!user_exists ($uid))
    return;
  $from = sendmail_email_lines ([$uid]);
  $addresses['from'] = $from[$uid];
}

# Check if $delayspamcheck makes sense, unset otherwise.
function sendmail_check_displayspamcheck ($context)
{
  global $int_delayspamcheck;

  foreach (['group', 'tracker', 'item'] as $k)
    if (empty ($context[$k]))
      {
        unset ($int_delayspamcheck);
        return;
      }
}

function sendmail_cc_to_uid ($address)
{
  if (sendmail_addr_is_uid ($address))
    return $address;
  return user_getid ($address);
}

function sendmail_debug_override_address (&$rcp, &$subj, &$msg, $email)
{
  if (!isset ($GLOBALS['sys_debug_email_override_address']))
    return;
  $head = "Savane debug: email override is turned on\n"
    . "Original recipient list:\n" . sendmail_encode_recipients ($rcp) . "\n";
  if (!empty ($subj))
    $head .= "Additional recipients with custom subject lines:\n";
  foreach ($subj as $a => $s)
    $head .= $email[$a] . " => $s\n";
  $head .= "------------\n\n";
  $msg['body'] = "$head{$msg['body']}";
  $rcp = [$GLOBALS['sys_debug_email_override_address']];
  $subj = []; # No recipients with custom subject lines.
}

function sendmail_spamcheck_queue ($context, $u_name, $u_subj, $message)
{
  db_autoexecute ('trackers_spamcheck_queue_notification',
    [ 'to_header' => $u_name, 'subject_header' => $u_subj,
      'message' => $message['body'], 'other_headers' => $message['headers'],
      'artifact' => $context['tracker'], 'item_id' => $context['item'],
      'comment_id' => $context['comment_id']],
    DB_AUTOQUERY_INSERT
  );
}

# Return the parameter line for bugs/comment.php unless $sys_reply_to
# is unconfigured or $uid is an email address rather than a number.
function sendmail_tracker_line ($uid, $context)
{
  if (!sendmail_have_reply_to ($context, $uid))
    return '';
  $note = _("Include the next line when replying by email.");
  return "\n\n{savane: $note}\n"
    . "{savane: user = $uid; tracker = {$context['tracker']}; "
    . "item = {$context['item']}}";
}

function sendmail_mail_signed ($to, $subj, $body, $headers)
{
  parsemail_sign_message ($body, $headers);
  return mail ($to, $subj, $body, $headers);
}

# Send mails with specific subject line.
function sendmail_send_to_list ($user_name, $user_subj, $message, $context)
{
  global $int_delayspamcheck;
  $ret = '';
  $context['cc'] = sendmail_cc_addresses ($user_name, $context);
  foreach ($user_subj as $v => $u_subj)
    {
      $u_name = sendmail_encode_recipients ($user_name[$v]);
      $body = $message['body']
        . sendmail_tracker_line ($v, $context) . $message['sig'];
      $body = sendmail_post_format ($body, $context);
      $headers = $message['headers'];
      $headers .= sendmail_reply_to_headers ($v, $user_name[$v], $context);
      if (empty ($int_delayspamcheck))
        {
          $ret .= sendmail_mail_signed ($u_name, $u_subj, $body, $headers);
          # TRANSLATORS: the argument is a comma-separated list of recipients.
          fb (sprintf (_("Mail sent to %s"), join (', ', $user_name[$v])));
          continue;
        }
      sendmail_spamcheck_queue ($context, $u_name, $u_subj, $message);
    }
  return $ret;
}

function sendmail_get_squad_ids ($addresses)
{
  $uids = $squads = [];
  foreach ($addresses as $arr)
    foreach ($arr as $a => $ignore)
      if (sendmail_addr_is_uid ($a))
        $uids[$a] = true;
  if (empty ($uids))
    return [];
  $ph = utils_in_placeholders ($uids);
  $result = db_execute (
    "SELECT user_id AS id FROM user WHERE status = 'SQD' AND user_id $ph",
    array_keys ($uids)
  );
  while ($row = db_fetch_array ($result))
    $squads[$row['id']] = true;
  return $squads;
}

# Convert array of squad IDs (id => true) to array of user IDs
# (squad_id => [user_id, ...]); for empty squads, entries like
# (squad_id => true) are left in the returned array so that
# further functions could easily tell between no squad and empty squad.
function sendmail_get_squad_set ($squads)
{
  $ph = utils_in_placeholders ($squads);
  $result = db_execute (
    "SELECT user_id AS u, squad_id AS s FROM user_squad WHERE squad_id $ph",
    array_keys ($squads)
  );
  $ret = $squads;
  while ($row = db_fetch_array ($result))
    {
      $s = $row['s'];
      if (!is_array ($ret[$s]))
        $ret[$s] = [];
      $ret[$s][] = $row['u'];
    }
  return $ret;
}

function sendmail_expand_squad_set ($addresses, $squads)
{
  $ret = [];
  foreach ($addresses as $arr)
    {
      $a = [];
      foreach ($arr as $addr => $ignore)
        {
          if (empty ($squads[$addr]))
            { # This address isn't a squad.
              $a[$addr] = true;
              continue;
            }
          if (!is_array ($squads[$addr])) # Don't pass empty squads to output.
            continue;
          foreach ($squads[$addr] as $uid)
            $a[$uid] = true;
        }
      $ret[] = $a;
    }
  return $ret;
}

function sendmail_expand_squads ($addresses)
{
  $squads = sendmail_get_squad_ids ($addresses);
  if (empty ($squads))
    return ($addresses);
  $squads = sendmail_get_squad_set ($squads);
  return sendmail_expand_squad_set ($addresses, $squads);
}

# The address is a user id, an account name or an email;
# strings starting with '@' also belong in account names.
function sendmail_addr_is_email ($a)
{
  return strpos ($a, '@');
}
function sendmail_addr_is_uid ($a)
{
  return ctype_digit (strval ($a));
}
function sendmail_addr_is_account_name ($a)
{
  return !(sendmail_addr_is_uid ($a) || sendmail_addr_is_email ($a));
}

# Normalize account names in the address lists to lower case.
# Return extracted set of account names.
function sendmail_extract_account_names (&$to, &$exclude)
{
  $lowered_names = [];
  foreach ([$to, $exclude] as $arr)
    {
      $names = [];
      foreach ($arr as $k => $ignored)
        if (sendmail_addr_is_account_name ($k))
          {
            $names[$k] = true;
            $lowered_names[strtolower ($k)] = true;
          }
       foreach ($names as $k => $ignored)
         {
           unset ($arr[$k]);
           $arr[strtolower ($k)] = true;
         }
    }
  return array_keys ($lowered_names);
}

function sendmail_reduce_names_to_uids ($to, $exclude)
{
   $names = sendmail_extract_account_names ($to, $exclude);
   if (empty ($names))
     return [$to, $exclude];
   foreach (user_get_array ($names) as $row)
     foreach (['to', 'exclude'] as $a)
       if (!empty (${$a}[strtolower ($row['user_name'])]))
         {
           unset (${$a}[strtolower ($row['user_name'])]);
           ${$a}[$row['user_id']] = true;
         }
   return [$to, $exclude];
}

function sendmail_email_lines ($uids)
{
  if (empty ($uids))
    return [];
  $lines = [];
  foreach (user_get_array ($uids) as $row)
    $lines[$row['user_id']] =
      utils_comply_with_rfc822 ($row['realname']) . " <{$row['email']}>";
  return $lines;
}

function sendmail_have_reply_to ($context, $uid = 289)
{
  if (empty ($GLOBALS['sys_reply_to']))
    return false;
  if (!sendmail_addr_is_uid ($uid))
    return false;
  if (empty ($context['tracker']) || empty ($context['item']))
    return false;
  return true;
}

function sendmail_user_prefs ($uids, $context)
{
  $ret = [];
  if (empty ($uids))
    return $ret;
  $have_reply_to = sendmail_have_reply_to ($context);
  foreach (user_get_preference ('subject_line', $uids) as $id => $pref)
    if ($pref !== null)
      $ret[$id] = sendmail_format_subject_line ($pref, $context);
    elseif ($have_reply_to)
      $ret[$id] = '';
  return $ret;
}

# Check for reserved domains, RFC 2606.
function sendmail_domain_is_reserved ($addr)
{
  if (preg_match ('/@savane[.]test$/', $addr)) # Allow for Savane tests.
    return false;
  $regexp =
    '/@('
        . '(example[.]((com)|(net)|(org)))'
        . '|([a-z0-9._-]*[.]((test)|(example)|(invalid)|(localhost)))'
    . ')\b/';
  return preg_match ($regexp, $addr);
}

# Forge the real to list, by parsing every item of the $to list.
function sendmail_make_to_list ($addresses)
{
  $to = sendmail_explode_addr_list ($addresses['to']);
  $exclude = [];
  if (!empty ($addresses['exclude']))
    $exclude = sendmail_explode_addr_list ($addresses['exclude']);
  list ($to, $exclude) = sendmail_reduce_names_to_uids ($to, $exclude);
  list ($to, $exclude) = sendmail_expand_squads ([$to, $exclude]);
  foreach ($exclude as $v => $ignore)
    unset ($to[$v]);
  $to1 = [];
  foreach ($to as $v => $ignore)
    {
      if (sendmail_addr_is_account_name ($v))
        $v = utils_normalize_email ($v);
      if (!sendmail_domain_is_reserved ($v))
        $to1[$v] = true;
    }
  return $to1;
}

function sendmail_list_uids ($vals)
{
  $ret = [];
  foreach ($vals as $v => $ignore)
    if (sendmail_addr_is_uid ($v))
      $ret[] = $v;
  return $ret;
}

function sendmail_compile_custom_subject_lines ($to, $context)
{
  $recipients = [];
  $uids = sendmail_list_uids ($to);
  $emails = sendmail_email_lines ($uids);
  $subj_pfx = sendmail_user_prefs (array_keys ($emails), $context);
  foreach ($to as $v => $ignore)
    {
      if (empty ($emails[$v]))
        {
          $recipients[] = $v;
          continue;
        }
      if (array_key_exists ($v, $subj_pfx))
        continue;
      $recipients[] = $emails[$v];
    }
  return [$recipients, $subj_pfx, $emails];
}

function sendmail_add_context_to_subject ($message, $context)
{
  $subject = $message['subject'];
  if (empty ($context['tracker']) || empty ($context['item']))
    return $subject;
  return "[" . utils_get_tracker_prefix ($context['tracker'])
    . " #{$context['item']}] $subject";
}

function sendmail_make_subjects ($to, $message, $context)
{
  list ($recipients, $subj_pfx, $emails)
    = sendmail_compile_custom_subject_lines ($to, $context);
  sendmail_debug_override_address ($recipients, $subj_pfx, $message, $emails);
  $subject = sendmail_add_context_to_subject ($message, $context);

  $user_subj = [];
  foreach ($subj_pfx as $k => $v)
    {
      if (strlen ($v))
        $v .= ' ';
      $user_subj[$k] = "$v$subject";
    }
  if (empty ($recipients))
    return [array_map ("sendmail_encode_header", $user_subj), $emails];
  $v = join (', ', $recipients);
  $emails[$v] = $recipients;
  $user_subj[$v] = $subject;
  return [array_map ("sendmail_encode_header", $user_subj), $emails];
}

function sendmail_encrypt_message ($uid, &$msg)
{
  $encrypted = $gpg_error = "";
  if (user_get_preference ("email_encrypted", $uid))
    list ($gpg_error, $gpg_result, $encrypted) =
      gpg_encrypt_to_user ($uid, $msg);
  if ($encrypted !== "")
    $msg = $encrypted;
  return [$encrypted === '', $gpg_error];
}

# Send the mail. Every mail sent by Savannah should be using this function.
#
# $addresses['to'] is comma-separated list; $addresses['from']
# and $addresses['to'] may contain user names instead of emails.
#
# $message contains a list of headers (['headers']), the subject
# (['subject']), the body (['body']).
#
# $context may contain additional data like relevant unix_group_name
# (['group']), artifact (['tracker']), item and comment IDs (['item']),
# additional flags (['skip_format_body']).
function sendmail_mail ($addresses, $message, $context = [])
{
  sendmail_check_displayspamcheck ($context);
  sendmail_format_body ($message, $context);
  sendmail_format_from ($addresses);
  $to = sendmail_make_to_list ($addresses);
  sendmail_build_headers ($addresses['from'], $context, $message);
  list ($subj, $emails) = sendmail_make_subjects ($to, $message, $context);
  foreach (array_keys ($subj) as $k)
    if (!is_array ($emails[$k]))
      $emails[$k] = [$emails[$k]];
  return sendmail_send_to_list ($emails, $subj, $message, $context);
}

# Encode each recipient separately and separate them using commas.
function sendmail_encode_recipients ($recipients)
{
  if (!is_array ($recipients))
    $recipients = [$recipients];
  $r = array_map ("sendmail_encode_header", $recipients);
  return join (', ', $r);
}

# Needed to send UTF-8 headers:
# Take a look at http://www.faqs.org/rfcs/rfc2047.html.
# We should use mb_encode_mimeheader () but it just does not work.
#
# We must not encode starting and ending quotes.
# We assume there could be only 2 quotes. Otherwise it would be a malformed
# address.
# The easy way we use to do this is to simply consider as one string the
# content of the quote, if any. If so, we are not working word per word but
# it saves us the time of searching for quotes in every words.
function sendmail_encode_header ($header, $charset = "UTF-8")
{
  # The default behavior is to consider words as strings to encode.
  $separator = ' ';
  if (strpos ($header, '"') !== FALSE)
    # Quotes found, we each quoted part will be a string to encode.
    $separator = '"';
  $words = explode ($separator, $header);
  foreach ($words as $key => $word)
    $encode[$key] = !utils_is_ascii ($word);
  $last_key = count ($words) - 1;
  foreach ($words as $key => $word)
    {
      if (!$encode[$key])
        continue;
      # Embed the space in the encoded word (spaces between encoded
      # words are ignored when rendering).
      if ($separator === ' ' && $key != $last_key && $encode[$key + 1])
        $word .= $separator;
      $words[$key] = "=?$charset?B?" . base64_encode ($word) . "?=";
    }
  return join ($separator, $words);
}

# A form for logged in users to send mails to others users.
function sendmail_form_message ($form_action, $user_id, $cc_me = true)
{
  global $HTML;
  print $HTML->box_top (
    # TRANSLATORS: the argument is user's name.
    sprintf (_("Send a message to %s"), user_getrealname ($user_id))
  );
  $pre = '<span class="preinput">';
  $post = "</span><br />\n&nbsp;&nbsp;&nbsp;";
  # We do not really bother finding out the realname + email, sendmail_mail ()
  # will do it.
  print form_header ($form_action)
    . form_hidden ([
        'touser' => utils_specialchars ($user_id),
        'fromuser' => user_getname ()])
    . "\n$pre" . _("From:") . $post
    . user_getrealname (user_getid (), 1) . ' &lt;'
    . user_getemail (user_getid ()) . "&gt;<br />\n"
    . $pre . _("Mailer:") . $post
    . utils_cutstring ($_SERVER['HTTP_USER_AGENT'], "50")
    . "<br />\n$pre" . html_label ('subject', _("Subject:")) . $post
    . form_input ('text', "subject", '', "size='60' maxlength='45'")
    . "<br />\n$pre"
    . form_checkbox ("cc_me", $cc_me,
        ['value' => 'cc_me', 'label' => _("Send me a copy")]
      )
    . $post . $pre . html_label ('body', _("Message:")) . $post
    . form_textarea ('body', '', "rows='20' cols='60'") . "\n\n"
    . form_footer (_('Send Message'), "send_mail");
  print $HTML->box_bottom ();
}

function sendmail_expand_template ($template, $context)
{
  $keys = ['group', 'tracker', 'item', 'uid', 'project', 'server'];
  $context['server'] = $GLOBALS['sys_default_domain'];
  foreach ($keys as $k)
    if (empty ($context[$k]))
      $context[$k] = '[' . strtoupper ($k) . ']';
  $context['item'] = '#' . $context['item'];
  $context['project'] = $context['group'];
  foreach ($keys as $k)
    $subst['%' . strtoupper ($k)] = $context[$k];
  return strtr ($template, $subst);
}

function sendmail_format_subject_line ($subject_line, $context)
{
  return sendmail_expand_template ($subject_line, $context);
}
?>
