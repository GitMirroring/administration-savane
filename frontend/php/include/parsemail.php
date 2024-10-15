<?php
# Functions for parsing and composing email messages.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

function parsemail_normalize_lines ($txt)
{
  return preg_replace ("/\r?\n/s", "\r\n", $txt);
}

function parsemail_get_part_data ($mime, $idx)
{
  return mailparse_msg_get_part_data (
    mailparse_msg_get_part ($mime, $idx)
  );
}

function parsemail_get_part ($mime, $idx, $msg)
{
  $d = parsemail_get_part_data ($mime, $idx);
  # Per RFC 3156, the headers of the part are included, and the line ends
  # are normalized to "\r\n".
  $start = $d['starting-pos'];
  $end = $d['ending-pos'];
  return parsemail_normalize_lines (substr ($msg, $start, $end - $start));
}

function parsemail_extract_callback ($x)
{
  global $parsemail_extract_part_accum;
  $parsemail_extract_part_accum .= $x;
}

function parsemail_extract_part ($mime, $idx, $msg)
{
  global $parsemail_extract_part_accum;
  $p = mailparse_msg_get_part ($mime, $idx);
  $parsemail_extract_part_accum = '';
  mailparse_msg_extract_part ($p, $msg, 'parsemail_extract_callback');
  return $parsemail_extract_part_accum;
}

function parsemail_check_part_attrib ($mime, $idx, $attr)
{
  $data = parsemail_get_part_data ($mime, $idx);
  foreach ($attr as $k => $v)
    {
      if (empty ($data[$k]))
        return 1;
      if ($v != $data[$k])
        return 2;
    }
  return 0;
}

function parsemail_check_topmost_part ($mime, $struct, $idx)
{
  if (strstr ($idx, '.'))
    return false;
  $protocol_is_wrong = parsemail_check_part_attrib (
    $mime, $idx,
    [
      'content-protocol' => 'application/pgp-signature',
      'content-type' => 'multipart/signed'
    ]
  );
  if ($protocol_is_wrong)
    return false;
  if (!(in_array ("$idx.1", $struct) && in_array ("$idx.2", $struct)))
    return false;
  if (in_array ("$idx.3", $struct))
     return false;
  return !parsemail_check_part_attrib (
    $mime, "$idx.2", ['content-type' => 'application/pgp-signature']
  );
}

function parsemail_analyze_struct ($mime, $struct, $error_handler)
{
  $topmost_parts = [];
  foreach ($struct as $idx)
    if (parsemail_check_topmost_part ($mime, $struct, $idx))
      $topmost_parts[] = $idx;
  if (empty ($topmost_parts))
    {
      parsemail_close ($mime);
      return $error_handler (
        'Wrong message structure ' . error_print_r ($struct)
      );
   }
  $ret = PHP_INT_MAX;
  foreach ($topmost_parts as $p)
    if ($p < $ret)
      $ret = $p;
  return ["$ret.2", "$ret.1"];
}

# Remove trailing empty lines, they turn out to break the signature.
function parsemail_fixup_multipart_part ($mime, $idx, $msg)
{
  $part = parsemail_get_part ($mime, $idx, $msg);
  $multipart = !parsemail_check_part_attrib (
    $mime, $idx, ['content-type' => 'multipart/mixed']
  );
  if ($multipart)
    $part = preg_replace ("/(\r\n)+$/s", "\r\n", $part);
  return $part;
}

function parsemail_get_part_charset ($mime, $idx)
{
  $data = parsemail_get_part_data ($mime, $idx);
  if (!empty ($data['charset']))
    return $data['charset'];
  return null;
}

function parsemail_parse_mime ($mime, $msg, $error_handler)
{
  $struct = mailparse_msg_get_structure ($mime);
  if (count ($struct) == 1) # Hopefully a clearsigned message.
    {
      $ret = parsemail_extract_part ($mime, $struct[0], $msg);
      return [[$ret], $ret, 0, parsemail_get_part_charset ($mime, $struct[0])];
    }
  $ret = [];
  foreach (parsemail_analyze_struct ($mime, $struct, $error_handler) as $idx)
    {
      $latest = $idx;
      $ret[] = parsemail_fixup_multipart_part ($mime, $idx, $msg);
    }
  $ret = [$ret, parsemail_extract_part ($mime, $latest, $msg)];
  # Check for nesting like in the 'protected-headers=v1' protocol.
  $nested = in_array ("$latest.1", $struct);
  $ret[] = $nested;
  if ($nested)
    $latest .= '.1';
  $ret[] = parsemail_get_part_charset ($mime, $latest);
  return $ret;
}

function parsemail_open ($email)
{
  $mime = mailparse_msg_create ();
  mailparse_msg_parse ($mime, $email);
  return $mime;
}

function parsemail_close ($mime)
{
  global $mime;
  if ($mime === null)
    return;
  mailparse_msg_free ($mime);
  $mime = null;
}
function parsemail_parse_body ($mime, $msg, $error_handler)
{
  $struct = mailparse_msg_get_structure ($mime);
  $idx = '1.1';
  if (in_array ($idx, $struct))
    return parsemail_extract_part ($mime, $idx, $msg);
  parsemail_close ($mime);
  return $error_handler ("No subpart $idx found");
}

function parsemail_extract_body ($email, $error_handler)
{
  if (!function_exists ('mailparse_msg_create'))
    return $error_handler ('Mailparse extension not found');
  $mime = parsemail_open ($email);
  $ret = parsemail_parse_body ($mime, $email, $error_handler);
  parsemail_close ($mime);
  return $ret;
}

function parsemail_extract_message ($email, $error_handler)
{
  if (!function_exists ('mailparse_msg_create'))
    return [[$email], $email]; # This may work with clearsigned messages.
  $mime = parsemail_open ($email);
  $ret = parsemail_parse_mime ($mime, $email, $error_handler);
  parsemail_close ($mime);
  return $ret;
}

function parsemail_list_headers ($headers)
{
  $ret = [];
  foreach (explode ("\n", $headers) as $l)
    {
      $arr = preg_split ("/:/", $l, 2);
      if (count ($arr) == 2)
        $ret[$arr[0]] = trim ($arr[1]);
    }
  return $ret;
}

function parsemail_make_multipart ($body)
{
  $b = [[
    'type' => TYPEMULTIPART, 'subtype' => 'signed',
    'type.parameters' => ['protocol' => 'application/pgp-signature'],
    'disposition.type' => 'inline'
  ]];
  $b[] = ['type' => TYPETEXT, 'subtype' => 'plain',
    'encoding' => ENCQUOTEDPRINTABLE, 'disposition.type' => 'inline',
    'charset' => 'utf-8',
    'contents.data' => imap_8bit ($body)
  ];
  $b[] = ['type' => TYPEAPPLICATION, 'subtype' => 'pgp-signature',
    'type.parameters' => ['name' => 'signature.asc'],
    'contents.data' => ''
  ];
  return $b;
}

function parsemail_compose ($parts)
{
  $msg = imap_mail_compose ([], $parts);
  $boundary = preg_replace ("/.*\n(--.*)--\s*$/s", '$1', $msg);
  $chunks = explode ("$boundary", $msg);
  # /^\r\n/ follows $boundary, it doesn't belong the body; likewise, /\r\n$/
  # is part of the boundary and must be excluded from the body (the latter
  # is optional per RFC 3156, but sendmail routines add the newline
  # unconditionally) .
  $body = preg_replace ("/^\r\n(.*)\r\n$/s", '$1', $chunks[1]);
  return [$msg, parsemail_normalize_lines ($body)];
}

function parsemail_sign_chunk ($msg)
{
  list ($out, $res, $msg, $micalg) = gpg_sign ($msg);
  if ($res)
    $out = $msg;
  return [$out, $micalg];
}

function parsemail_will_sign ()
{
  global $sys_gpg_home;
  if (empty ($sys_gpg_home))
    return false;
  return extension_loaded ('imap');
}

function parsemail_sign_message (&$body, &$headers)
{
  if (!parsemail_will_sign ())
    return;
  $h = parsemail_list_headers ($headers);
  $parts = parsemail_make_multipart ($body);
  list ($msg, $b) = parsemail_compose ($parts);
  list ($sig, $micalg) = parsemail_sign_chunk ($b);
  if ($micalg !== null)
    # IMAP coming with PHP 5.4 omits the 'protocol' part when 'micalg'
    # is set separately like $parts[0]['type.parameters']['micalg'] = $micalg.
    $parts[0]['type.parameters'] = [
      'protocol' => 'application/pgp-signature', 'micalg' => $micalg
    ];
  $parts[2]['contents.data'] = $sig;
  list ($msg) = parsemail_compose ($parts);
  $r = preg_match (
    "/Content-Disposition: inline\s*\n(.*)$/s", $msg, $m, PREG_OFFSET_CAPTURE
  );
  if (!$r)
    return;
  $headers .= substr ($msg, 0, $m[1][1]);
  $body = substr ($msg, $m[1][1]);
}
?>
