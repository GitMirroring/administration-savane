# Test user registration
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

use strict;
our ($homepage_url);

# New pending user
sub register_user {
    my ($agent, $user_name, $user_pass, $user_realname) = @_;
	

    my $user_number = 0;
    if (!defined($user_name)) {
	my $user_status;
	do {
	    $user_number++;
	    $user_name = 'testuser' . $user_number;
	    $user_status = GetUserSettings($user_name, 'status');
	} while (defined($user_status));
	my $user_realname = "Test User $user_number";
    }
    if (!defined($user_pass)) {
	$user_pass = $user_name;
    }
    if (!defined($user_realname)) {
	$user_realname = "Test User $user_name";
    }

    $agent->get($homepage_url);
    $agent->follow_link(text => 'New User', n => '1');
    $agent->form_number(2);
    $agent->field('form_loginname', $user_name);
    $agent->field('form_pw', $user_pass);
    $agent->field('form_pw2', $user_pass);
    $agent->field('form_realname', $user_realname);
    $agent->field('form_email', $user_name.'@localhost');
    $agent->field('website', 'http://'); # anti-spam test or sthing
    $agent->click('update');

    return ($user_name, $user_pass);
}

sub confirm_user {
    my ($agent, $user_name, $user_pass) = @_;
    my $confirm_hash = GetUserSettings($user_name, 'confirm_hash');
    $agent->get("$homepage_url/account/verify.php?confirm_hash=$confirm_hash");
    $agent->form_number(2);
    $agent->field('form_loginname', $user_name);
    $agent->field('form_pw', $user_pass);
    $agent->field('website', 'http://');
    $agent->click('update');
}

1;
