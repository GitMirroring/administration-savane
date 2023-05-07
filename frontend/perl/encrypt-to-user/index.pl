#! /usr/bin/perl
# Encrypt a message to specified savane user.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2023 Ineiev <ineiev@gnu.org>
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

use strict;
use DBI;
use File::Temp qw(tempdir tempfile);
use Getopt::Long;
my $getopt;
my $help;
my $user;
our $gpg_home;
our $gpg_name;
my $sys_dbname;
my $sys_dbhost;
my $sys_dbuser;
my $sys_dbparams;
my $sys_dbpasswd;
my $exit_code = 0;

eval
{
  $getopt = GetOptions ("help" => \$help, "user=s" => \$user,
    "dbname=s" => \$sys_dbname, "dbhost:s" => \$sys_dbhost,
    "dbparams:s" => \$sys_dbparams, "home=s" => \$gpg_home,
    "gpg=s" => \$gpg_name
  );
};

sub print_help
{
  my $help = <<"  EOF";
    Usage: $0 [OPTIONS]

    Encrypt a message to user's registered GPG key.

      -h, --help            Display this help and exit
          --user            Savannah user to encrypt to
          --dbname          Savannah database name
          --dbhost          Savannah database host
          --dbparams        Savannah database parameters
          --gpg             Use specified program as GPG

    Database user and password are passed in the first two lines of input.

  EOF
  $help =~ s,(^|\n)    ,$1,g;
  print STDERR $help;
  exit 0;
}

print_help () if $help;

our $dbd;

if (!$gpg_home)
  {
    $sys_dbuser = <> or die "No database user is supplied.";
    $sys_dbpasswd = <> or die "No database password is supplied.";

    $sys_dbuser =~ s/\n$//;
    $sys_dbpasswd =~ s/\n$//;

    $dbd = DBI->connect ("DBI:mysql:database=$sys_dbname"
      . ":host=$sys_dbhost$sys_dbparams", $sys_dbuser, $sys_dbpasswd,
      { RaiseError => 1, AutoCommit => 1}
    );
  }

$gpg_name = 'gpg' unless $gpg_name;

## Encrypt to user GPG key if available
# arg1: user id
# arg2: message
# return encrypted message when encryption succeeded,
#        empty string encryption failed.
# Exit codes:
#   0 when encryption succeeded,
#   1 when it failed,
#   2 when no suitable key was found,
#   3 when key selection error occurred,
#   4 when creating temporary files failed,
#   5 when extracted key_id is invalid.
sub UserEncrypt
{
  my $temp_dir = $gpg_home;
  my ($user, $message) = @_;

  $exit_code = 4;
  my ($mh, $mname) = tempfile (UNLINK => 1);
  return "" if $mname eq "";
  my $key;

  if (!$gpg_home)
    {
      $key = $dbd->selectrow_array (
        "SELECT gpg_key FROM user WHERE user_id=$user"
      );
      $exit_code = 3;
      return "" unless $key ne "";

      $exit_code = 4;
      $temp_dir = tempdir (CLEANUP => 1);
      return "" if $temp_dir eq "";
    }

  my $input;
  my $key_id = "";
  my $msg = "";

  print $mh $message;
  $exit_code = 2;

  unless ($gpg_home)
    {
      open ($input, '|-', "$gpg_name --home='$temp_dir' --batch -q --import");
      print $input $key;
      close ($input) or return "";
    }

  # Get the first ID of a public key with encryption capability.
  open ($input, '-|',
    "$gpg_name --home='$temp_dir' --list-keys --with-colons 2> /dev/null"
  );
  while (<$input>)
    {
      next unless (/^pub/);
      my @fields = split /:/;
      next unless ($fields[11] =~ /[eE]/);
      $key_id = $fields[4];
      last unless $key_id eq "";
    }
  close ($input) or return "";
  return "" unless $key_id ne "";
  $exit_code = 5;
  return "" unless $key_id =~ /^[0-9A-F]*$/;
  $exit_code = 1;
  open ($input, '-|',
    "$gpg_name --home='$temp_dir' --trust-model always --batch -a "
    . "--encrypt -r $key_id -o - $mname"
  );
  while (<$input>)
    {
      $msg .= $_;
    }
  close $input and $exit_code = 0;
  return $msg;
}

my $msg = "";

while (<>)
  {
    $msg .= $_;
  }

print UserEncrypt ($user, $msg);

exit $exit_code;
