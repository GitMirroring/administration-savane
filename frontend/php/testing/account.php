<?php
# Test account_validpw () and auxiliary functions related to passphrase hashing.
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
#
# Invocation:
#
#   php testing/account.php
#
# In case of fail, diagnostic text is output to stdout.

define ('TESTING_HASH', true); # Use deterministic pseudo-random numbers.
require_once ('include/account.php');
function validate_order ($round, $set, $len)
{
  for ($i = 0; $i < $len; $i++)
    {
      if (!array_key_exists ($i, $set))
        {
          print "Round $round: index $i is missing\n";
          continue;
        }
      if ($set[$i] > 1)
        print "Round $round: index $i is found {$set[$i]} times\n";
      unset ($set[$i]);
    }
  if (empty ($set))
    return;
  foreach ($set as $k => $v)
    print "Round $round: extra index $i is found {$set[$i]} times\n";
}

# This is possible in theory, but we'd better look for a bug in our code.
function check_if_order_is_trivial ($round, $order)
{
  foreach ($order as $idx => $val)
    if ($idx != $val)
      return;
  print "Round $round: the order is trivial\n";
}

function test_gen_random_order ($len)
{
  for ($i = 0; $i < 0x121; $i++)
    {
      $order = hash_gen_random_order ($len);
      $set = [];
      foreach ($order as $o)
        if (array_key_exists ($o, $set))
          $set[$o]++;
        else
          $set[$o] = 1;
      check_if_order_is_trivial ($i, $order);
      validate_order ($i, $set, $len);
    }
}

function test_sv_crypt_failure ()
{
  global $bindir, $sys_use_php_crypt;
  list ($bin, $use) = [$bindir, $sys_use_php_crypt];
  $sys_use_php_crypt = false;
  $bindir = '.';
  $res = hash_encryptpw ('passphrase');
  if ($res !== null)
    print "Unexpected success: hash_encryptpw returns $res\n";
  list ($bindir, $sys_use_php_crypt) = [$bin, $use];
}

# '0' will not do as a passphrase because account_validpw checks for emptiness.
# Running checks against non-ASCII strings is essential because Perl may have
# issues with them.
$plain_pw = ['10', '11', '\xff\xaa\xff', '\x9f\x98\x85'];
foreach ($plain_pw as $p)
  $stored_pw[$p] = hash_encryptpw ($p);

foreach ($plain_pw as $p)
  if (!account_validpw ($stored_pw[$p], $p))
    print "False negative for >$p< ({$stored_pw[$p]})\n";
foreach ($plain_pw as $k0 => $p0)
  foreach ($plain_pw as $k => $p)
    {
      if ($k0 == $k)
        continue;
      if (account_validpw ($stored_pw[$p0], $p))
        print "False positive for >$p< against hash generated for >$p0<\n";
    }
test_gen_random_order (strlen ($stored_pw[$plain_pw[0]]));
if (defined ('INSTALLCHECK'))
  test_sv_crypt_failure ();
?>
