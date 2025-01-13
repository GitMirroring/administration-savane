#!/usr/bin/perl
# Test a project creation
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

use strict;
use warnings;
use WWW::Mechanize;
use Test::More qw(no_plan);

use Digest::MD5 qw(md5_hex);

# Dev test environment
$ENV{SAVANE_CONF} = '/tmp/savane/savane';
use Savane;
our $homepage_url = 'http://localhost:50080';

# Default admin credentials
our $admin_name = 'admin';
our $admin_pass = 'admin';

# Default agent - tests can generate some more though
our $agent = WWW::Mechanize->new();

###################

require 'create_user.mech.pl';

# Create a new project
my $group1_number = 0;
my $group1_system_name = '';
my $group1_status = '';
do {
    $group1_number++;
    $group1_system_name = 'testgroup' . $group1_number;
    $group1_status = GetGroupSettings($group1_system_name, 'status');
} while (defined($group1_status));
my $group1_full_name = "Test Group $group1_number";
# Generate easily recognizable name for Mechanize:
$group1_full_name .= ' [' . md5_hex($group1_system_name) . ']';

$agent->follow_link(text => 'Register New Project', n => '1');
$agent->form_number(2);
$agent->click('Submit');

$agent->form_number(2);
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_comments', '');
# Test string escaping / quoting
$agent->field('form_purpose', "'");
$agent->field('form_required_sw', '');
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_unix_name', $group1_system_name);
$agent->field('form_full_name', $group1_full_name);
$agent->click('Submit');

$agent->form_number(2);
$agent->field('form_license', 'gpl');
$agent->field('form_license_other', '');
$agent->click('Submit');

$agent->form_number(2);
# Don't touch recap
#$agent->field('form_comments', '');
#$agent->field('form_purpose', "'");
#$agent->field('group_type', '1');
#$agent->field('form_required_sw', '');
#$agent->field('form_license', 'gpl');
#$agent->field('form_full_name', 'test');
#$agent->field('form_license_other', '');
$agent->click('i_agree');

# Check that the project exists
ok(GetGroupSettings($group1_system_name, 'status') eq 'P', 'Register project');

# Log out
$agent->follow_link(text => 'Logout', n => '1');


# Check that the associated task was created
# TODO


# Login in as admin
$agent->get('http://localhost:50080/');
$agent->follow_link(text => 'Login', n => '1');

$agent->form_number(2);
$agent->field('form_loginname', 'admin');
$agent->field('form_pw', 'admin');
$agent->click('login');

$agent->follow_link(text => 'Become Superuser', n => '1');
$agent->follow_link(text => 'Pending projects', n => '1');
$agent->follow_link(text => "Submission of $group1_full_name", n => '1');
$agent->follow_link(text => 'Group Administration', n => '2');
#$agent->follow_link(n => '43'); # activate
#$agent->follow_link(n => '45'); # configure
$agent->form_number(2);
#$agent->field('form_name', 'test');
$agent->field('form_status', 'A');
$agent->click('update');


ok(GetGroupSettings($group1_system_name, 'status') eq 'A', 'Approve project');
