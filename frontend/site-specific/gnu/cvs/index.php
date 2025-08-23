<?php
# Instructions about CVS usage.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
# Copyright (C) 2005, 2006, 2010-2012 Michael J. Flickinger
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry
# Copyright (C) 2015-2020, 2022, 2024 Bob Proulx
# Copyright (C) 2013, 2014, 2017-2025 Ineiev <ineiev@gnu.org>
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
include dirname (__FILE__) . '/../vcs.php';

$cvs_cmd_base = "cvs -z3 -d:pserver:anonymous@cvs.$base_host:";
$module_list = cvs_list_modules ($unix_name);
$modules = [];
foreach (cvs_repo_types () as $type)
  if (empty ($module_list[$type]))
    $modules[$type] = '';
  else
    $modules[$type] = '<p>' . _("Available CVS modules:") . "</p>\n<ul>\n<li>"
      . join ("</li>\n<li>", $module_list[$type]) . "</li>\n</ul>\n";

# Only list modules if more than the one default module is present.
if (
  empty ($module_list['web'])
    || empty (array_diff ($module_list['web'], [$unix_name]))
)
  $modules['web'] = '';

if ($project->isPublic ())
  {
    vcs_print_anon_access ();
    print '<p>'
     . _("The CVS repository of this group can be checked out through anonymous "
          . "CVS with the following instruction set.  The module you wish "
          . "to check out must be specified as the "
          . "&lt;<var>modulename</var>&gt;.")
     . "</p>\n";

    if ($project->Uses ("cvs"))
      {
        $cvs_cmd = $cvs_cmd_base . $project->getTypeDir ('cvs') . " co ";
        print html_h (4, _('Software repository:'));
        print "<pre>$cvs_cmd$unix_name</pre>\n";
        print "<p>" . _('With other CVS modules:') . "</p>\n";
        print "<pre>$cvs_cmd&lt;<var>" . _('modulename') . "</var>&gt;</pre>\n";
        print $modules['sources'];
      }
    if ($project->CanUse ("homepage") || $project->UsesForHomepage ("cvs"))
      {
        print html_h (4, _('Webpage repository:'));
        print "<pre>$cvs_cmd_base" . $project->getTypeDir ('homepage')
          . " co $unix_name</pre>\n" . $modules['web'];
      }

    print '<p>';
    print _("<em>Hint:</em> When you update your working copy from within "
      . "the\nmodule's directory (with <code>cvs update</code>) you do not "
      . "need the\n<code>-d</code> option anymore.  Simply use");
    print "</p>\n\n<pre>";
    print "cvs update\ncvs -qn update\n";
    print "</pre>\n\n";
    print "<p>" . _('to preview and status check.') . "</p>\n";
  }
vcs_print_auth_access ();
$cvs_cmd_base = "cvs -z3 -d:ext:$username@cvs.$base_host:";
if ($project->Uses ("cvs"))
  {
    $cvs_cmd = "$cvs_cmd_base" . $project->getTypeDir ("cvs") . " co ";
    print html_h (4, _('Software repository:'));
    print "<pre>$cvs_cmd$unix_name</pre></p>\n";
    print "<p>" . _('With other CVS modules:') . "</p>\n";
    print "<pre>$cvs_cmd&lt;<var>" . _('modulename') . '</var>&gt;'
      . "</pre></p>\n";
    print $modules['sources'];
  }
if ($project->CanUse ("homepage") || $project->UsesForHomepage ("cvs"))
  {
    print html_h (4, _('Webpage repository:'));
    print "<pre>$cvs_cmd_base"
      . preg_replace ('#/$#', "", $project->getTypeDir ("homepage"))
      . " co $unix_name</pre></p>\n" . $modules['web'];
  }
vcs_print_auth_description ();

if (member_check (0, $project->getGroupId (), 'A'))
  {
    print html_h (2, _('Email Notifications'));
    print "<p>";
    printf (
      _('You can <a href="%s">configure commit notifications</a>.'),
      "/cvs/admin/?group=$unix_name"
    );
    print "</p>\n";
  }

print html_h (2, _('CVS Newbies')) . "<p>";
printf (
  _("If you've never used CVS, you should read some documentation about\n"
    . "it; a useful URL is %s. Using\nCVS is not complex but you have "
    . "to understand what is going on. The\nbest way to start is to ask "
    . "a friend to show you the way."),
  "<a href=\"//www.nongnu.org/cvs/#documentation\">\n"
  . "https://www.nongnu.org/cvs/#documentation</a>"
);

print "</p>\n<p>";

printf (
  _("The basic information described further on this page is detailed in\n"
    . "the <a href=\"%s\">Savannah user doc</a>."),
  "//savannah.gnu.org/maintenance/back-page/#cvs"
);

print "</p>\n";

if ($project->CanUse ("cvs"))
  {
    print html_h (2, _('What are CVS modules?')) . "<p>";
    printf (
      _("The CVS repository of each group is divided into modules which you "
        . "can download separately.  The list of existing modules for this "
        . "group can be obtained by looking at <a href=\"%s\">the root of "
        . "the CVS repository</a>; each directory listed there is "
        . "the name of a module, which can substitute the generic "
        . "&lt;<var>modulename</var>&gt; used below in the examples of the "
        . "<code>co</code> command of CVS.  Note that <code>.</code> (dot) "
        . "is always also a valid module name which stands for &ldquo;all "
        . "available modules&rdquo; in a group.  Most groups have "
        . "a module with the same name of the group, where the main "
        . "software development takes place."),
      $project->getTypeUrl ("cvs_viewcvs")
    );
    print "</p>\n";
  }

print '<p>' . _('The same applies to the Webpage Repository.') . "</p>\n\n";
print html_h (2, _('Import your CVS tree')) . "<p>";

print
  _("If your group already has an existing CVS repository that you "
    . "want to move to Savannah, make an appointment with us for the "
    . "migration.");

print "</p>\n\n";

print html_h (2, _('Symbolic Links in Webpage CVS')) . "<p>";

printf (
  _("As a special feature in CVS web repositories (only), a file named\n"
    . "<tt>.symlinks</tt> can be put in any directory where you want to make "
    . "symbolic\nlinks.  Each line of the file lists a real file name "
    . "followed by the name of the\nsymbolic link. The symbolic links are "
    . "built twice an hour.  More information in\n<a href=\"%s\">GNU "
    . "Webmastering Guidelines</a>."),
  "//www.gnu.org/server/standards/README.webmastering.html#symlinks"
);

print "</p>\n";
if ($base_host == "savannah.gnu.org")
  {
    print html_h (2, _('Web pages for GNU packages')) . "<p>";
    printf (
      _("When writing web pages for official GNU packages, please keep the\n"
        . "<a href=\"%s\"> guidelines</a> in mind."),
      '//www.gnu.org/prep/maintain/maintain.html#Web-Pages'
    );
    print "</p>\n";
  }
?>
