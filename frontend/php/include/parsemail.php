<?php
# Functions for parsing and composing email messages.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

function parsemail_analyze_parts ($mime, $struct)
{
  $topmost_parts = [];
  foreach ($struct as $idx)
    if (parsemail_check_topmost_part ($mime, $struct, $idx))
      $topmost_parts[] = $idx;
  if (empty ($topmost_parts))
    return [null, 'Wrong message structure ' . error_print_r ($struct)];
  $ret = PHP_INT_MAX;
  foreach ($topmost_parts as $p)
    if ($p < $ret)
      $ret = $p;
  return [["$ret.2", "$ret.1"], null];
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

function parsemail_message_is_encrypted ($mime)
{
  $expected = [
    '1' => [
      'content-protocol' => 'application/pgp-encrypted',
      'content-type' => 'multipart/encrypted'
    ],
    '1.1' => [
      'content-type' => 'application/pgp-encrypted',
      'content-disposition' => 'attachment'
    ],
    '1.2' => ['content-disposition' => 'attachment']
  ];
  $struct = mailparse_msg_get_structure ($mime);
  if (array_diff ($struct, array_keys ($expected)) != [])
    return false;
  if (array_diff (array_keys ($expected), $struct) != [])
    return false;
  foreach ($expected as $idx => $attributes)
  if (parsemail_check_part_attrib ($mime, $idx, $attributes))
    return false;
  return true;
}

function parsemail_decrypt ($mime, $msg, $user_id)
{
  $struct = mailparse_msg_get_structure ($mime);
  $extracted = parsemail_extract_part ($mime, '1.2', $msg);
  list ($error_code, $error_msg, $decrypted)
    = gpg_decrypt_and_verify ($extracted, $user_id);
  if ($error_code)
    return [null, $error_msg];
  $msg = "From savane@savane.test Tue Sep 27 12:35:59 1983\n"
    . "From: <savane@savene.test>\n"
    . "MIME-Version: 1.0\n$decrypted";
  list ($msg, $files, $error) = parsemail_parse_nested ($msg);
  return  [[$msg, $files], $error];
}

function parsemail_analyze_struct ($mime, $struct, $msg)
{
  $ret = [];
  list ($parts, $error) = parsemail_analyze_parts ($mime, $struct);
  if ($error !== null)
    return [null, null, $error];
  foreach ($parts as $idx)
    {
      $latest = $idx;
      $ret[] = parsemail_fixup_multipart_part ($mime, $idx, $msg);
    }
  return [$ret, $latest, null];
}

function parsemail_parse_mime ($mime, $msg)
{
  $struct = mailparse_msg_get_structure ($mime);
  if (count ($struct) == 1) # Hopefully a clearsigned message.
    {
      $ret = parsemail_extract_part ($mime, $struct[0], $msg);
      return [
        [[$ret], $ret, 0, parsemail_get_part_charset ($mime, $struct[0])], null
      ];
    }
  list ($parts, $latest, $error) =
    parsemail_analyze_struct ($mime, $struct, $msg);
  if ($error !== null)
    return [null, $error];
  $ret = [$parts, parsemail_extract_part ($mime, $latest, $msg)];
  # Check for nesting like in the 'protected-headers=v1' protocol.
  $nested = in_array ("$latest.1", $struct);
  $ret[] = $nested;
  if ($nested)
    $latest .= '.1';
  $ret[] = parsemail_get_part_charset ($mime, $latest);
  return [$ret, null];
}

function parsemail_open ($email)
{
  $mime = mailparse_msg_create ();
  mailparse_msg_parse ($mime, $email);
  return $mime;
}

function parsemail_close ($mime)
{
  mailparse_msg_free ($mime);
}

function parsemail_data_is_attachment ($data)
{
  if (empty ($data['content-disposition']))
    return 0;
  return $data['content-disposition'] === 'attachment';
}

function parsemail_extract_attachment ($mime, $email, $i)
{
  $idx = "1.$i";
  $data = parsemail_get_part_data ($mime, $idx);
  if (!parsemail_data_is_attachment ($data))
    return null;
  $msg = parsemail_extract_part ($mime, $idx, $email);
  if (array_key_exists ('charset', $data))
    $msg = iconv ($data['charset'], 'UTF-8//IGNORE', $msg);
  if (!array_key_exists ('disposition-filename', $data))
    $data['disposition-filename'] = $i;
  if (!array_key_exists ('content-type', $data))
    $data['content-type'] = null;
  if (!array_key_exists ('content-description', $data))
    $data['content-description'] = '';
  foreach (
    ['content-type', 'disposition-filename', 'content-description'] as $f
  )
    $data[$f] = iconv_mime_decode ($data[$f]);
  return [
    'name' => $data['disposition-filename'], 'type' => $data['content-type'],
    'body' => $msg, 'description' => $data['content-description']
  ];
}

function parsemail_extract_files ($mime, $email, $struct)
{
  $ret = [];
  for ($i = 2; in_array ("1.$i", $struct); $i++)
    {
      $item = parsemail_extract_attachment ($mime, $email, $i);
      if ($item !== null)
        $ret[] = $item;
    }
  return $ret;
}

function parsemail_nested ($mime, $email)
{
  $struct = mailparse_msg_get_structure ($mime);
  $idx = '1.1';
  if (!in_array ($idx, $struct))
    $idx = '1';
  $ret = parsemail_extract_part ($mime, $idx, $email);
  $files = parsemail_extract_files ($mime, $email, $struct);
  return [$ret, $files, null];
}

function parsemail_parse_nested ($email)
{
  $mime = parsemail_open ($email);
  $ret = parsemail_nested ($mime, $email);
  mailparse_msg_free ($mime);
  return $ret;
}

function parsemail_extract ($mime, $email, $user_id)
{
  if (parsemail_message_is_encrypted ($mime))
    return parsemail_decrypt ($mime, $email, $user_id);
  list ($ret, $error) = parsemail_parse_mime ($mime, $email);
  if ($error !== null)
    return [null, $error];
  list ($input, $msg, $nested, $charset) = $ret;
  list ($error, $error_msg, $decrypted) = gpg\verify_for ($user_id, $input);
  if ($error)
    return [null, $error_msg];
  $files = [];
  if (count ($input) < 2)
    $msg = $decrypted; # Non-detached signature: needs 'decrypting'.
  elseif (!empty ($nested))
    {
      list ($msg, $files, $error) = parsemail_parse_nested ($input[1]);
      if ($error !== null)
        return [null, $error];
    }
  if (!empty ($charset))
    $msg = iconv ($charset, 'UTF-8//IGNORE', $msg);
  return [[$msg, $files], null];
}

# This may work with clearsigned messages: extracting without the mailparse
# extension.
function parsemail_extract_basic ($email, $user_id, $error_handler)
{
  list ($error_code, $error_msg, $text) = gpg\verify_for ($user_id, [$email]);
  if ($error_code)
    return $error_handler ($error_msg);
  return [$text, []];
}
function parsemail_extract_message ($email, $user_id, $error_handler)
{
  if (!function_exists ('mailparse_msg_create'))
    return parsemail_extract_basic ($email, $user_id, $error_handler);
  $mime = parsemail_open ($email);
  list ($ret, $error) = parsemail_extract ($mime, $email, $user_id);
  parsemail_close ($mime);
  if ($error !== null)
    return $error_handler ($error);
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
