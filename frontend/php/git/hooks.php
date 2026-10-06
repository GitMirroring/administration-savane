<?php
# Show Git repository configuration: a read-only
# version of /git/admin/index.php without the web browsing settings.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2013, 2014, 2017-2026 Ineiev
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

foreach (['init', 'sane', 'vcs', 'vcs/git-ui', 'form', 'gpg'] as $i)
  require_once ("../include/$i.php");

if (!db_numrows (group_get_result ($group_id)))
  exit_error (_("Invalid Group"));

function show_intro ($group_id)
{
  $group_name = group_get_object ($group_id)->getUnixName ();
  site_project_header (['group' => $group_id, 'context' => 'git']);
  print '<p>'
    . sprintf (
        _("This page displays the configuration of Git repositories\n"
          . "of the group &lt;%s&gt;."), $group_name
      )
    . "</p>\n";
}

function show_repo ($r)
{
  global $php_self;
  $name = $r['name'];
  $anchor = git_repo_html_id ($name);
  print "<form action='$php_self#$anchor' method='post'>\n";
  print html_h (2, $name, $anchor);
  $dl = [
    _("Description:") => utils_specialchars_decode ($r['desc'], ENT_QUOTES)
  ];
  print html_dl ($dl);
  if (!git_have_savane_hooks (git_get_repo_path ($r)))
    return;
  $prefs = git_get_hook_prefs ($r, $GLOBALS['group_id']);
  print html_h (3, _('Git hooks'));
  print git_all_hook_controls ($r, $prefs);
}

git_hook_help (null);
git_disable_controls ();

show_intro ($group_id);
git_display_repos (vcs_get_repos ('git', $group_id));
$HTML->footer ([]);
?>
