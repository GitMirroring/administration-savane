<?php
# Make and verify hashed and salted passphrases.
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
require_once (dirname (__FILE__) . '/random-bytes.php');

define ('HASH_PREFIX_YES', '$y$');
define ('HASH_PREFIX_SHA512', '$6$');
define ('HASH_DEFAULT_PREFIX', HASH_PREFIX_YES);
define ('HASH_TESTING_PREFIX', HASH_PREFIX_SHA512);
if (defined ('TESTING_HASH') && !defined ('INSTALLCHECK'))
  define ('NO_SV_CRYPT', true);

function hash_pw_prefix_rank ($pfx = null)
{
  $ranks = [HASH_PREFIX_YES => 2, HASH_PREFIX_SHA512 => 1];
  if ($pfx == null)
    return $ranks;
  if (empty ($ranks[$pfx]))
    return -1;
  return $ranks[$pfx];
}

function hash_supported_pw_prefices ()
{
  return array_keys (hash_pw_prefix_rank ());
}

function hash_pw_infix ($pfx = null)
{
  $infices = [HASH_PREFIX_YES => 'j', HASH_PREFIX_SHA512 => 'rounds='];
  if ($pfx === null)
    return $infices;
  if (array_key_exists ($pfx, $infices))
    return $infices[$pfx];
  return null;
}

function hash_extract_prefix ($stored_pw)
{
  $pw_len = strlen ($stored_pw);
  foreach (hash_supported_pw_prefices () as $pfx)
    if ($pw_len >= strlen ($pfx) && !strncmp ($pfx, $stored_pw, strlen ($pfx)))
      return $pfx;
  return null;
}

function hash_yescrypt_costs ()
{
  return ['75', '85', '7T', '8T', '9T', 'AT', 'BT', 'CT', 'DT', 'ET', 'FT'];
}

function hash_yescript_cost_cmp ($a, $b)
{
  if ($a == $b)
    return 0;
  foreach (hash_yescrypt_costs () as $cost)
    {
      if ($cost == $a)
        return -1;
      if ($cost == $b)
        return 1;
    }
  return 0;
}

function hash_yescrypt_cost_is_valid ($cost)
{
  return in_array ($cost, hash_yescrypt_costs ());
}

function hash_sha512_cost_is_valid ($cost)
{
  return is_int ($cost) && $cost >= 1000 && $cost <= 999999999;
}

function hash_cost_is_valid ($pfx, $cost)
{
  $funcs = [
    HASH_PREFIX_YES => 'hash_yescrypt_cost_is_valid',
    HASH_PREFIX_SHA512 => 'hash_sha512_cost_is_valid'
  ];
  if (empty ($funcs[$pfx]))
    return false;
  return $funcs[$pfx] ($cost);
}

function hash_extract_cost ($stored_pw)
{
  $pfx = hash_extract_prefix ($stored_pw);
  if (empty ($pfx))
    return null;
  $cost = substr ($stored_pw, strlen ($pfx . hash_pw_infix ($pfx)));
  $pos = strpos ($cost, '$');
  if (!$pos) # Either no '$' in the string or '$' starts it; both are invalid.
    return null;
  $cost = substr ($cost, 0, $pos);
  if (hash_cost_is_valid ($pfx, $cost))
    return $cost;
  return null;
}

function hash_gensalt ($salt_base64_length)
{
  $salt_byte_length = $salt_base64_length * 6 / 8;
  $rand_bytes = phpass_get_random_bytes ($salt_byte_length);
  return phpass_encode64 ($rand_bytes, $salt_byte_length);
}

function hash_get_pw_prefix ()
{
  global $sys_pw_prefix;
  if (defined ('NO_SV_CRYPT'))
    return HASH_TESTING_PREFIX;

  if (empty ($sys_pw_prefix))
    return HASH_DEFAULT_PREFIX;
  if (hash_pw_prefix_rank ($sys_pw_prefix) < 0)
    return HASH_DEFAULT_PREFIX;
  return $sys_pw_prefix;
}

function hash_get_pw_cost ($use_low_cost = false)
{
  global $sys_pw_rounds;
  $pfx = hash_get_pw_prefix ();
  $costs = [
    HASH_PREFIX_YES => [hash_yescrypt_costs ()[0], 'DT'],
    # rounds=5000 is the 2010 glibc default, possibly we'll upgrade in
    # the future, better have this explicit.
    # Cf. http://www.akkadia.org/drepper/sha-crypt.html
    HASH_PREFIX_SHA512 => [1000, 5000]
  ];
  $i = 1;
  if ($use_low_cost)
    # When storing a random hash as opposed to a passphrase, the cost
    # doesn't matter because the search space is guaranteed to be wide.
    # Use the minimum round number in such cases.
    return $costs[$pfx][0];
  if (!empty ($sys_pw_rounds) && hash_cost_is_valid ($pfx, $sys_pw_rounds))
    return $sys_pw_rounds;
  return $costs[$pfx][1];
}

function hash_try_sv_crypt ($plainpw, $salt)
{
  global $bindir;
  for ($i = 0; $i < 5; $i++)
    {
      $exit_code = utils_run_proc (
        "$bindir/sv_crypt", $out, $err, ['in' => "$salt\n$plainpw\n"]
      );
      if (!$exit_code)
        return substr ($out, 0, -1);
      if (!defined ('TESTING_HASH'))
        sleep (1);
      if ($i)
        continue;
      utils_disable_warnings (); # Don't report further failures.
    }
  return null;
}

function hash_crypt ($plainpw, $salt)
{
  global $sys_use_php_crypt, $hash_silent_crypt;
  if (!empty ($sys_use_php_crypt) || defined ('NO_SV_CRYPT'))
    {
      if (!empty ($hash_silent_crypt))
        $saved = utils_disable_warnings (E_DEPRECATED);
      $ret = crypt ($plainpw, $salt);
      if (!empty ($hash_silent_crypt))
        utils_restore_warnings ($saved);
      return $ret;
    }
  $state = utils_disable_warnings (0, true);
  $ret = hash_try_sv_crypt ($plainpw, $salt);
  utils_restore_warnings ($state);
  if ($ret === null && !defined ('TESTING_HASH'))
    exit_error ();
  return $ret;
}

function hash_get_prefix_with_cost ($use_low_cost = false)
{
  $pfx = hash_get_pw_prefix ();
  return $pfx . hash_pw_infix ($pfx) . hash_get_pw_cost ($use_low_cost) . '$';
}

function hash_encryptpw ($plainpw, $use_low_cost = false)
{
  $salt_lengths = [
    HASH_PREFIX_YES => 36, # 216 bits, 128+ is recommended in crypt(5).
    HASH_PREFIX_SHA512 => 16 # 96 bits, maximum for SHA512.
  ];
  $pfx = hash_get_pw_prefix ();
  $salt = hash_gensalt ($salt_lengths[$pfx]);
  $pfx = hash_get_prefix_with_cost ($use_low_cost);
  return hash_crypt ($plainpw, "$pfx$salt");
}

function hash_needs_upgrading ($stored_pw)
{
  if (!preg_match ('/[$]/', $stored_pw))
    # Don't upgrade DES hashes, they must have been added on purpose
    # in demo instances.
    return false;
  $pfx = hash_get_pw_prefix ();
  $start = hash_get_prefix_with_cost ();
  return strncmp ($stored_pw, $start, strlen ($start));
}

function hash_get_random_byte ()
{
  if (!defined ('TESTING_HASH'))
    return ord (random_bytes (1));
  # Return deterministic numbers when testing.
  static $init_needed = true;
  if ($init_needed)
    mt_srand (0);
  $init_needed = false;
  return mt_rand (0, 255);
}

function hash_gen_random_order ($len)
{
  $ret = [];
  for ($i = 0; $i < $len; $i++)
    $ret[] = $i;
  for ($i = $j = 0; $i < $len - 1; $i++)
    {
      if (!$j)
        {
          $byte = hash_get_random_byte ();
          for ($s = $k = 0; $k < 4; $k++)
            $s = $s * 256 + $byte;
          mt_srand ((int)$s);
          $j = 63;
        }
      $r = mt_rand ($i, $len - 1);
      $t = $ret[$i];
      $ret[$i] = $ret[$r];
      $ret[$r] = $t;
      $j--;
    }
  return $ret;
}

function hash_compare_hash ($h0, $h)
{
  # Try to run in input-independent time; randomize the order
  # the characters are compared in (hopefully PHP optimizing procedures
  # won't be able to figure out that the result is just string comparison).
  $n = strlen ($h0);
  if ($n != strlen ($h))
    return false;
  $order = hash_gen_random_order ($n);
  $weights = [false => 2, true => 1];
  $ret = $n * 2;
  for ($i = 0; $i < $n; $i++)
    {
      $j = $order[$i];
      $ret -= $weights[substr ($h0, $j, 1) === substr ($h, $j, 1)];
    }
  return $ret == $n;
}
?>
