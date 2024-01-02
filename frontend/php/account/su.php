<?php
# Enable or disable admin privileges for the current user.
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
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

require_once ('../include/init.php');
require_once ('../include/sane.php');

# Login was asked and user can be super user? Set a cookie and that's done.
# For now, set a cookie that does not stay long, we'll see if admin complains
# :P
extract(sane_import('get',
  [
    'true' => 'from_brother',
    'internal_uri' => 'uri',
    'strings' => [['action', ['login', 'logout']]]
  ]
));

$brother_domain = '';
if (!empty ($GLOBALS['sys_brother_domain']))
  $brother_domain = $GLOBALS['sys_brother_domain'];

$hdr = [
  1 => $brother_domain . $uri,
  2 => $brother_domain . $GLOBALS['sys_home']
    . "account/su.php?action=login&from_brother=1&uri="
    . utils_urlencode ($uri),
  3 => $brother_domain . $GLOBALS['sys_home']
    . "account/su.php?action=logout&from_brother=1&uri="
    . utils_urlencode ($uri)
];
$prot = su_getprotocol ();
foreach ($hdr as $k => $v)
  $hdr[$k] = "Location: $prot://$v";
$hdr[0] = "Location: $uri";

$i = 1;
$from_brother_not_su = !user_is_super_user () && $from_brother;

if ($action == "login" && user_can_be_super_user ())
  {
    session_cookie ("session_su", "wannabe");
    $i = 0;
    if (!empty ($brother_domain))
      $i = $from_brother? 1: 2;
  }
elseif ($action == "login" && $from_brother_not_su)
  # The user is not logged at this website, go back to the brother website.
  ;
elseif ($action == "logout" && user_is_super_user ())
  {
    session_delete_cookie ("session_su");
    $i = 0;
    if (!empty ($brother_domain))
      $i = $from_brother? 1: 3;
  }
elseif ($action == "logout" && $from_brother_not_su)
  # The user is not logged at this website, go back to the brother website.
  ;
else
  exit_error (_("You shouldn't have come to this page."));

header ($hdr[$i]);

function su_getprotocol ()
{
  if (session_issecure ())
    return "https";
  return "http";
}
?>
