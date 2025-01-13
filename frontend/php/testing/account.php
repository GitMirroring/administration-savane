<?php
# Test account_validpw () and auxiliary functions.
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

define ('TESTING_ACCOUNT', true); # Use deterministic pseudo-random numbers.
require_once ('include/account.php');
# '0' will not do as a passphrase because account_validpw checks for emptiness.
$plain_pw = ['10', '11'];
foreach ($plain_pw as $p)
  $stored_pw[$p] = account_encryptpw ($p);

foreach ($plain_pw as $p)
  if (!account_validpw ($stored_pw[$p], $p))
    print "False negative for >$p<\n";
foreach ($plain_pw as $k => $p)
  {
    $pw = $plain_pw[1 - $k];
    if (account_validpw ($stored_pw[$p], $pw))
      print "False positive for >$pw< against hash generated for >$pw<\n";
  }
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
      $order = account_gen_random_order ($len);
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
test_gen_random_order (strlen ($stored_pw[$plain_pw[0]]));
?>
