#!/usr/bin/env perl

=pod
A URL Mirror redirector for GNU Savannah's "download" and "ftpmirror" servers.

Copyright (C) 2016 Assaf Gordon <assafgordon\@gmail.com)
License: GPLv3+

To try from the command line:

    REMOTE_ADDR=5.6.111.43 \
    MIRROR_FILE=/opt/savannah/mirrors/active-mirror-lists/download-mirrors.txt \
    PATH_INFO=/datamash/datamash-win32.exe \
    ./mirror-redirect.pl

From NGINX:

    location /releases-redirect {
        gzip off;
        fastcgi_split_path_info            ^(/releases-redirect)(/?.+)$;
        fastcgi_param PATH_INFO            $fastcgi_path_info;
        fastcgi_param MIRROR_FILE          /opt/savannah/mirror-lists/download-mirrors.txt;
        fastcgi_param SCRIPT_FILENAME      /opt/savannah/cgi-bin/mirrors/mirror-redirect.pl;

        include fastcgi_params;
        fastcgi_pass unix:/var/run/fcgiwrap.socket;
    }


Parameters (environment variables):
1. MIRROR_FILE - the mirror list to use.
   Suggested values:
   For mirroring 'download.sv.gnu.org':
     /opt/savannah/mirror-lists/download-mirrors.txt
   For mirroring ftpmirror.gnu.org:
     /opt/savannah/mirror-lists/gnu-ftpmirrors.txt

2. PATH_INFO:
   This MUST be the exact path of the requested file,
   WITHOUT the "/releases/" or "/releases-redirect/" part.
   It will be concatenated to the URL of the mirror.

3. REMOTE_ADDR:
   The IP of the user.

=cut

use strict;
use warnings;
use Geo::IP;
use Geo::Mirror;

# Ensure GeoIP Database is installed
my $geoip_db = "/usr/share/GeoIP/GeoIP.dat";
die "configuration error: file '$geoip_db' not found"
	unless -e "$geoip_db";

# Ensure we have a "REMOTE_ADDR" (this is a CGI script, after all).
my $remote_addr = $ENV{'REMOTE_ADDR'};
die "REMOTE_ADDR envvar not found or empty." .
    "If running from command-line, set REMOTE_ADDR=w.x.y.z "
	unless $remote_addr;
die "Invalid REMOTE_ADDR($remote_addr)"
        unless $remote_addr =~ /^[0-9a-fA-F\.\:\/]+$/;

my $scheme = $ENV{'REQUEST_SCHEME'};
die "REQUEST_SCHEME envvar not found or empty." .
    "If running from command-line, set REQUEST_SCHEME=https "
	unless $scheme;

# Ensure we have a "MIRROR_FILE" variable
# (should be set by the upstream webserver cgi configuration)
my $mirror_file = $ENV{'MIRROR_FILE'};
die "MIRROR_FILE envvar not found or empty." .
    "If running from command line, set MIRROR_FILE=/path/to/file.txt "
	unless $mirror_file;
die "MIRROR_FILE ($mirror_file) not found "
	unless -e $mirror_file;

# Ensure there is a PATH_INFO component.
my $path_info = $ENV{'PATH_INFO'};
die "PATH_INFO envvar not found or empty." .
    "If running from command line, set PATH_INFO=/path/to/file.txt "
	unless $path_info;
# Silently discard any funky character in the requested URL
$path_info =~ s/[^\041-\177]//g;

# Make a pretty URL, without extra slashes
$path_info = "/" . $path_info unless substr($path_info,0,1) eq "/";

# Uses the system's default (/usr/share/GeoIP),
# which is the one installed with 'apt-get install geoip-database'.
my $gm = Geo::Mirror->new(database_file => $geoip_db,
			  mirror_file => $mirror_file);

my $url;
my $mirror =  $gm->find_mirror_by_addr($remote_addr);
if (defined($mirror) && length($mirror) > 0) {
    chop $mirror if $mirror =~ m|/$| ;
    $url = $mirror . $path_info;
} else {
    # FIXME: No mirror available.  And we have painted ourselves in a
    # corner.  We can't serve the file locally or it will redirect
    # again.  Disabled the redirection temporarily to avoid the problem.
    $url = "$scheme://download-mirror.savannah.gnu.org/releases" . $path_info;
}

## If all went well, return result to the user (=upstream webserver)
print<<"EOF";
HTTP/1.1 302 Found\r
Location: $url\r
\r
EOF


=pod
print<<"EOF";
content-type: text/plain\r
\r

GNU Savannah - mirror check

Detected Remote Address: $remote_addr

suggested mirror: $mirror

redirect to: $mirror$path_info

EOF

print join("\n", map { "$_ : " . $ENV{$_} } keys %ENV);
=cut

exit(0);
