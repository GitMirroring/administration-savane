#!/usr/bin/perl
#
# Sample membersh conf (restricted shell)
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2008 Aleix Conchillo Flaque <aleix@member.fsf.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2023 Ineiev
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
#
# $use_cvs = "1";
# $bin_cvs = "/usr/bin/cvs";
#
# $use_scp = "1";
# $bin_scp = "/usr/bin/scp";
# $regexp_scp = "^scp( -[prv])* -t (-- )?/(upload|var/ftp)";

# $use_sftp = "1";
# $bin_sftp = "/usr/lib/sftp-server";
# $regexp_sftp = "^(/usr/lib/ssh/sftp-server|/usr/lib/sftp-server|/usr/libexec/sftp-server)";
#
# $use_rsync = "1";
# $bin_rsync = "/usr/bin/rsync";
# $regexp_rsync = "^rsync --server";
# $regexp_dir_rsync = "^(/upload)|(/var/ftp)";

# $use_svn = "1";
# $bin_svn = "svnserve";
# $regexp_svn = "^svnserve -t";
# @prepend_args_svn = ( '-r', '/svn' );

# $use_git = "1";
# $bin_git = "git-shell";

# $use_bzr = "1";
# $bin_bzr = "bzr";
# $dir_bzr = "/srv/bzr";

# $use_hg = "1";
# $bin_hg = "hg-ssh";
# @prepend_args_hg = ( '.', '*' );
