<?php

# Instructions about Subversion usage.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry
# Copyright (C) 2013, 2014, 2017-2024 Ineiev <ineiev@gnu.org>
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

include dirname (__FILE__) . '/../fingerprints.php';

global $project;

print '<h3>'._('Anonymous / read-only Subversion access').'</h3>

<p>'._("This project's Subversion repository can be checked out anonymously
as follows.  The module you wish to check out must be specified as the
&lt;<i>modulename</i>&gt;.").'</p>

';

print '<h3>'._('Access using the SVN protocol:').'</h3>
<code>svn co svn://svn.'
                        . $project->getTypeBaseHost()
                        . "/"
                        . $project->getUnixName()
                        . "/&lt;<i>"._('modulename')."</i>&gt;</code><br />";
print '<h3>'._('Access using HTTP (slower):').'</h3>
<code>svn co http://svn.'
                        . $project->getTypeBaseHost()
                        . "/svn/"
                        . $project->getUnixName()
                        . "/&lt;<i>"._('modulename')."</i>&gt;</code>";

print '<p>'._("Typically, you'll want to use <tt>trunk</tt> for
<i>modulename</i>. Refer to a project's specific instructions if
you're unsure, or browse the repository with ViewVC.").'</p>


<h3>'._('Group member Subversion access via SSH').'</h3>

<p>'
._('Member access is performed using the Subversion over SSH method.')
.'</p>

<p>
'._('The SSHv2 public key fingerprints for the machine hosting the source
trees are:')."</p>\n".$vcs_fingerprints;

$username = user_getname();
if ($username == "NA") {
        // for anonymous user :
        $username = '&lt;<i>'._('membername').'</i>&gt;';
}

print '<h3>'._('Software repository (over SSH):').'</h3>
<code>svn co svn+ssh://'
              . $username
              . '@svn.'
              . $project->getTypeBaseHost()
              . "/"
              . $project->getUnixName()
              . "/&lt;<i>"._('modulename')."</i>&gt;</code>";
print '

<h3>'._('Importing into Subversion on Savannah').'</h3>

';

printf ('<p>'
._('If your project already has an existing source repository that you
want to move to Savannah, check the <a href="%s">conversion
documentation</a> and then submit a request for the
migration in the <a href="%s">Savannah Administration</a> project.').'</p>

', '//savannah.gnu.org/maintenance/CvSToSvN',
   '//savannah.gnu.org/projects/administration');


print '<h3>'._('Exporting Subversion tree from Savannah').'</h3>

<p>'
._('You can access your subversion raw repository using read-only access via
rsync, and then use that copy as a local SVN repository:').'</p>

<pre>
rsync -avHS rsync://svn.<?php echo $project->getTypeBaseHost(); ?>/svn/'
.$project->getUnixName().'/ /tmp/'.$project->getUnixName().'.repo/
svn co file:///tmp/'.$project->getUnixName().'.repo/ trunk
# ...
</pre>

<p>'._('If you want a dump you can also use svnadmin:').'</p>

<pre>
svnadmin dump /tmp/'.$project->getUnixName().'.repo/
</pre>
';
?>
