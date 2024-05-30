# Makefile.am for frontend part (mostly PHP).
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

basic_png = \
  php/images/mime_type/blank.png \
  php/images/OldCERN.theme/floating.png \
  php/images/OldCERN.theme/point.png \
  php/images/OldCERN.theme/icon.png \
  php/images/OldCERN.theme/thin-image.png \
  php/images/Forest.theme/floating.png \
  php/images/Forest.theme/point.png \
  php/images/Forest.theme/icon.png \
  php/images/Forest.theme/thin-image.png \
  php/images/Emeraud.theme/floating.png \
  php/images/Emeraud.theme/point.png \
  php/images/Emeraud.theme/icon.png \
  php/images/Emeraud.theme/thin-image.png \
  php/images/Gna.theme/floating.png \
  php/images/Gna.theme/point.png \
  php/images/Gna.theme/icon.png \
  php/images/Gna.theme/thin-image.png \
  php/images/common/feed16.png \
  php/images/common/floating.png \
  php/images/common/point.png \
  php/images/common/icon.png \
  php/images/common/thin-image.png \
  php/images/Light.theme/floating.png \
  php/images/Light.theme/point.png \
  php/images/Light.theme/icon.png \
  php/images/Light.theme/thin-image.png \
  php/images/Water.theme/floating.png \
  php/images/Water.theme/point.png \
  php/images/Water.theme/icon.png \
  php/images/Water.theme/thin-image.png \
  php/images/LinuxfrPointOrg.theme/floating.png \
  php/images/LinuxfrPointOrg.theme/icon.png \
  php/images/LinuxfrPointOrg.theme/thin-image.png \
  php/images/Reverse.theme/floating.png \
  php/images/Reverse.theme/point.png \
  php/images/Reverse.theme/icon.png \
  php/images/Reverse.theme/thin-image.png \
  php/images/www.gnu.org.theme/floating.png \
  php/images/www.gnu.org.theme/icon.png \
  php/images/www.gnu.org.theme/thin-image.png \
  php/images/pllx.theme/floating.png \
  php/images/pllx.theme/icon.png \
  php/images/pllx.theme/thin-image.png \
  php/images/misc/leaves-Forest.png \
  php/images/misc/sand-Savannah.png \
  php/images/misc/point-Codex.png \
  php/images/misc/cvs.orig-Right.png \
  php/images/misc/sslWarning.png \
  php/images/misc/people.orig-2.png \
  php/images/SlashD.theme/floating.png \
  php/images/SlashD.theme/icon.png \
  php/images/SlashD.theme/thin-image.png \
  php/images/Elessar.theme/floating.png \
  php/images/Elessar.theme/point.png \
  php/images/Elessar.theme/icon.png \
  php/images/Elessar.theme/thin-image.png \
  php/images/Savannah.theme/leopard.png \
  php/images/Savannah.theme/floating.png \
  php/images/Savannah.theme/point.png \
  php/images/Savannah.theme/steel.png \
  php/images/Savannah.theme/icon.png \
  php/images/Savannah.theme/thin-image.png \
  php/images/DarkSlate.theme/floating.png \
  php/images/DarkSlate.theme/point.png \
  php/images/DarkSlate.theme/icon.png \
  php/images/DarkSlate.theme/thin-image.png

derived_png = \
  php/images/common/arrows1/bottom.png \
  php/images/common/arrows1/down.png \
  php/images/common/arrows1/firstgrey.png \
  php/images/common/arrows1/first.png \
  php/images/common/arrows1/lastgrey.png \
  php/images/common/arrows1/last.png \
  php/images/common/arrows1/nextgrey.png \
  php/images/common/arrows1/next.png \
  php/images/common/arrows1/previousgrey.png \
  php/images/common/arrows1/previous.png \
  php/images/common/arrows1/top.png \
  php/images/common/arrows1/up.png \
  php/images/common/arrows2/bottom.png \
  php/images/common/arrows2/down.png \
  php/images/common/arrows2/firstgrey.png \
  php/images/common/arrows2/first.png \
  php/images/common/arrows2/lastgrey.png \
  php/images/common/arrows2/last.png \
  php/images/common/arrows2/nextgrey.png \
  php/images/common/arrows2/next.png \
  php/images/common/arrows2/previousgrey.png \
  php/images/common/arrows2/previous.png \
  php/images/common/arrows2/top.png \
  php/images/common/arrows2/up.png \
  php/images/common/arrows3/bottom.png \
  php/images/common/arrows3/down.png \
  php/images/common/arrows3/firstgrey.png \
  php/images/common/arrows3/first.png \
  php/images/common/arrows3/lastgrey.png \
  php/images/common/arrows3/last.png \
  php/images/common/arrows3/nextgrey.png \
  php/images/common/arrows3/next.png \
  php/images/common/arrows3/previousgrey.png \
  php/images/common/arrows3/previous.png \
  php/images/common/arrows3/top.png \
  php/images/common/arrows3/up.png \
  php/images/common/bool1/ok.png \
  php/images/common/bool1/wrong.png \
  php/images/common/bool-alt/ok.png \
  php/images/common/bool-alt/wrong.png \
  php/images/common/bool-Emeraud/ok.png \
  php/images/common/bool-Emeraud/wrong.png \
  php/images/common/bool-pllx/ok.png \
  php/images/common/bool-pllx/wrong.png \
  php/images/common/contexts1/admin.png \
  php/images/common/contexts1/bug-alt.png \
  php/images/common/contexts1/bug.png \
  php/images/common/contexts1/cvs.png \
  php/images/common/contexts1/desktop.png \
  php/images/common/contexts1/directory-alt.png \
  php/images/common/contexts1/directory.png \
  php/images/common/contexts1/download.png \
  php/images/common/contexts1/help.png \
  php/images/common/contexts1/keys.png \
  php/images/common/contexts1/mail.png \
  php/images/common/contexts1/main.png \
  php/images/common/contexts1/man.png \
  php/images/common/contexts1/news.png \
  php/images/common/contexts1/patch.png \
  php/images/common/contexts1/people.png \
  php/images/common/contexts1/preferences.png \
  php/images/common/contexts1/task.png \
  php/images/common/contexts-DarkSlate/admin.png \
  php/images/common/contexts-DarkSlate/bug.png \
  php/images/common/contexts-DarkSlate/cvs.png \
  php/images/common/contexts-DarkSlate/desktop.png \
  php/images/common/contexts-DarkSlate/directory.png \
  php/images/common/contexts-DarkSlate/download.png \
  php/images/common/contexts-DarkSlate/help.png \
  php/images/common/contexts-DarkSlate/keys.png \
  php/images/common/contexts-DarkSlate/mail.png \
  php/images/common/contexts-DarkSlate/main.png \
  php/images/common/contexts-DarkSlate/man.png \
  php/images/common/contexts-DarkSlate/news.png \
  php/images/common/contexts-DarkSlate/patch.png \
  php/images/common/contexts-DarkSlate/people.png \
  php/images/common/contexts-DarkSlate/preferences.png \
  php/images/common/contexts-DarkSlate/task.png \
  php/images/common/contexts-Emeraud/admin.png \
  php/images/common/contexts-Emeraud/bug.png \
  php/images/common/contexts-Emeraud/cvs.png \
  php/images/common/contexts-Emeraud/desktop.png \
  php/images/common/contexts-Emeraud/directory.png \
  php/images/common/contexts-Emeraud/download.png \
  php/images/common/contexts-Emeraud/help.png \
  php/images/common/contexts-Emeraud/keys.png \
  php/images/common/contexts-Emeraud/mail.png \
  php/images/common/contexts-Emeraud/main.png \
  php/images/common/contexts-Emeraud/man.png \
  php/images/common/contexts-Emeraud/news.png \
  php/images/common/contexts-Emeraud/patch.png \
  php/images/common/contexts-Emeraud/people.png \
  php/images/common/contexts-Emeraud/preferences.png \
  php/images/common/contexts-Emeraud/task.png \
  php/images/common/contexts-Light/admin.png \
  php/images/common/contexts-Light/bug.png \
  php/images/common/contexts-Light/cvs.png \
  php/images/common/contexts-Light/desktop.png \
  php/images/common/contexts-Light/directory.png \
  php/images/common/contexts-Light/download.png \
  php/images/common/contexts-Light/help.png \
  php/images/common/contexts-Light/keys.png \
  php/images/common/contexts-Light/mail.png \
  php/images/common/contexts-Light/main.png \
  php/images/common/contexts-Light/man.png \
  php/images/common/contexts-Light/news.png \
  php/images/common/contexts-Light/patch.png \
  php/images/common/contexts-Light/people.png \
  php/images/common/contexts-Light/preferences.png \
  php/images/common/contexts-Light/task.png \
  php/images/common/contexts-OldCERN/admin.png \
  php/images/common/contexts-OldCERN/bug.png \
  php/images/common/contexts-OldCERN/cvs.png \
  php/images/common/contexts-OldCERN/desktop.png \
  php/images/common/contexts-OldCERN/directory.png \
  php/images/common/contexts-OldCERN/download.png \
  php/images/common/contexts-OldCERN/help.png \
  php/images/common/contexts-OldCERN/keys.png \
  php/images/common/contexts-OldCERN/mail.png \
  php/images/common/contexts-OldCERN/main.png \
  php/images/common/contexts-OldCERN/man.png \
  php/images/common/contexts-OldCERN/news.png \
  php/images/common/contexts-OldCERN/patch.png \
  php/images/common/contexts-OldCERN/people.png \
  php/images/common/contexts-OldCERN/preferences.png \
  php/images/common/contexts-OldCERN/task.png \
  php/images/common/contexts-pllx/admin.png \
  php/images/common/contexts-pllx/bug.png \
  php/images/common/contexts-pllx/cvs.png \
  php/images/common/contexts-pllx/desktop.png \
  php/images/common/contexts-pllx/directory.png \
  php/images/common/contexts-pllx/download.png \
  php/images/common/contexts-pllx/help.png \
  php/images/common/contexts-pllx/keys.png \
  php/images/common/contexts-pllx/mail.png \
  php/images/common/contexts-pllx/main.png \
  php/images/common/contexts-pllx/man.png \
  php/images/common/contexts-pllx/news.png \
  php/images/common/contexts-pllx/patch.png \
  php/images/common/contexts-pllx/people.png \
  php/images/common/contexts-pllx/preferences.png \
  php/images/common/contexts-pllx/task.png \
  php/images/common/misc-DarkSlate/edit.png \
  php/images/common/misc-DarkSlate/print.png \
  php/images/common/misc-DarkSlate/trash.png \
  php/images/common/misc-DarkSlate/www.png \
  php/images/common/misc-default/edit.png \
  php/images/common/misc-default/print.png \
  php/images/common/misc-default/trash.png \
  php/images/common/misc-default/www.png \
  php/images/common/misc-Emeraud/edit.png \
  php/images/common/misc-Emeraud/print.png \
  php/images/common/misc-Emeraud/trash.png \
  php/images/common/misc-Emeraud/www.png \
  php/images/common/misc-Light/edit.png \
  php/images/common/misc-Light/print.png \
  php/images/common/misc-Light/trash.png \
  php/images/common/misc-Light/www.png \
  php/images/common/misc-pllx/edit.png \
  php/images/common/misc-pllx/print.png \
  php/images/common/misc-pllx/trash.png \
  php/images/common/misc-pllx/www.png \
  php/images/common/roles1/assignee.png \
  php/images/common/roles1/people.png \
  php/images/common/roles1/project-admin.png \
  php/images/common/roles1/project-member.png \
  php/images/common/roles1/site-admin.png \
  php/images/common/roles-Emeraud/assignee.png \
  php/images/common/roles-Emeraud/people.png \
  php/images/common/roles-Emeraud/project-admin.png \
  php/images/common/roles-Emeraud/project-member.png \
  php/images/common/roles-Emeraud/site-admin.png \
  php/images/mime_type/a.png \
  php/images/mime_type/back.png \
  php/images/mime_type/bak.png \
  php/images/mime_type/binary.png \
  php/images/mime_type/bomb.png \
  php/images/mime_type/compressed.png \
  php/images/mime_type/c.png \
  php/images/mime_type/css.png \
  php/images/mime_type/deb.png \
  php/images/mime_type/dia.png \
  php/images/mime_type/dir.png \
  php/images/mime_type/down.png \
  php/images/mime_type/dvi.png \
  php/images/mime_type/folder.open.png \
  php/images/mime_type/folder.png \
  php/images/mime_type/folder.sec.png \
  php/images/mime_type/forward.png \
  php/images/mime_type/hand.right.png \
  php/images/mime_type/image.png \
  php/images/mime_type/mpg.png \
  php/images/mime_type/ogg.png \
  php/images/mime_type/pdf.png \
  php/images/mime_type/pgp.png \
  php/images/mime_type/php.png \
  php/images/mime_type/p.png \
  php/images/mime_type/py.png \
  php/images/mime_type/readme.png \
  php/images/mime_type/rpm.png \
  php/images/mime_type/scm.png \
  php/images/mime_type/sh.png \
  php/images/mime_type/so.png \
  php/images/mime_type/tex.png \
  php/images/mime_type/text.png \
  php/images/mime_type/unknown.png \
  php/images/mime_type/up.png \
  php/images/mime_type/xml.png

png_src = $(derived_png:%.png=%.orig.png)

ordinary_files = \
  php/404-export.php \
  php/404.php \
  php/account/first.php \
  php/account/impersonate.php \
  php/account/index.php \
  php/account/login.php \
  php/account/logout.php \
  php/account/lostlogin.php \
  php/account/lostpw-confirm.php \
  php/account/lostpw.php \
  php/account/pending-resend.php \
  php/account/register.php \
  php/account/su.php \
  php/account/verify.php \
  php/bug/index.php \
  php/bugs/admin/conf-copy.php \
  php/bugs/admin/editqueryforms.php \
  php/bugs/admin/field_usage.php \
  php/bugs/admin/field_values.php \
  php/bugs/admin/field_values_reset.php \
  php/bugs/admin/field_values_transition-ofields-update.php \
  php/bugs/admin/index.php \
  php/bugs/admin/notification_settings.php \
  php/bugs/admin/other_settings.php \
  php/bugs/admin/userperms.php \
  php/bugs/dependencies.php \
  php/bugs/download.php \
  php/bugs/export.php \
  php/bugs/index.php \
  php/bugs/item.php \
  php/bugs/reporting.php \
  php/captcha.php \
  php/contact.php \
  php/css/Cafe.css \
  php/css/Cappuccino.css \
  php/css/CERN.css \
  php/css/CodeX.css \
  php/css/DarkSlate.css \
  php/css/Elessar.css \
  php/css/Emeraud.css \
  php/css/Forest.css \
  php/css/Gna.css \
  php/css/graph-widths.php \
  php/css/internal/base.css \
  php/css/internal/stone-age-menu.css \
  php/css/internal/testconfig.css \
  php/css/LCD.css \
  php/css/Light2.css \
  php/css/Light.css \
  php/css/LinuxfrPointOrg.css \
  php/css/OldCERN.css \
  php/css/pllx.css \
  php/css/README \
  php/css/Reverse.css \
  php/css/Savanedu.css \
  php/css/Savannah.css \
  php/css/SlashD.css \
  php/css/SoftGreen.css \
  php/css/UGent.css \
  php/css/Water.css \
  php/css/www.gnu.org.css \
  php/cvs/admin/index.php \
  php/cvs/index.php \
  php/docs/index.php \
  php/file \
  php/file.php \
  php/files/index.php \
  php/forum/forum.php \
  php/forum/message.php \
  php/git/admin/index.php \
  php/git/index.php \
  php/i18n.php \
  php/images/common/COPYING.CC0 \
  php/images/common/COPYING.GPL \
  php/images/common/icon.xcf \
  php/images/common/logo-fsf.org.svg \
  php/images/common/meditate.tar.xz \
  php/images/common/README \
  php/images/Elessar.theme/README \
  php/images/Forest.theme/README \
  php/images/Light.theme/README \
  php/images/LinuxfrPointOrg.theme/README \
  php/images/mime_type/README \
  php/images/misc/README \
  php/images/pllx.theme/README \
  php/images/README \
  php/images/README.tangoset \
  php/images/Reverse.theme/README \
  php/images/SlashD.theme/README \
  php/images/Water.theme/README \
  php/images/www.gnu.org.theme/README \
  php/include/ac_config.php.in \
  php/include/account.php \
  php/include/calendar.php \
  php/include/context.php \
  php/include/database.php \
  php/include/error.php \
  php/include/exit.php \
  php/include/features_boxes.php \
  php/include/form.php \
  php/include/form-check.php \
  php/include/gpg.php \
  php/include/gpl-quick-form.php \
  php/include/graphs.php \
  php/include/group.php \
  php/include/.htaccess \
  php/include/html.php \
  php/include/http.php \
  php/include/i18n.php \
  php/include/init.php \
  php/include/layout.php \
  php/include/mailman.php \
  php/include/markup.php \
  php/include/member.php \
  php/include/my/admin/general.php \
  php/include/my/bookmarks.php \
  php/include/my/general.php \
  php/include/news/forum.php \
  php/include/news/general.php \
  php/include/pagemenu.php \
  php/include/people/general.php \
  php/include/project/admin.php \
  php/include/project_home.php \
  php/include/proj_email.php \
  php/include/pwqcheck.php \
  php/include/random-bytes.php \
  php/include/sane.php \
  php/include/savane_error.php \
  php/include/savane-git.php \
  php/include/search/general.php \
  php/include/sendmail.php \
  php/include/session.php \
  php/include/sitemenu.php \
  php/include/spam.php \
  php/include/stats/general.php \
  php/include/theme.php \
  php/include/timezones.php \
  php/include/trackers/conf.php \
  php/include/trackers/cookbook.php \
  php/include/trackers/data.php \
  php/include/trackers/format.php \
  php/include/trackers/general.php \
  php/include/trackers_run/add.php \
  php/include/trackers_run/browse.php \
  php/include/trackers_run/digest.php \
  php/include/trackers_run/mod.php \
  php/include/trackers_run/README \
  php/include/trackers_run/search.php \
  php/include/trackers/show.php \
  php/include/trackers/transition.php \
  php/include/trackers/votes.php \
  php/include/trackers/view-dependencies.php \
  php/include/user.php \
  php/include/utils.php \
  php/include/vars.php \
  php/include/vcs/git.php \
  php/include/vcs.php \
  php/index.php \
  php/js/captcha.php \
  php/js/hide-feedback.php \
  php/js/hide-span.php \
  php/js/show-feedback.php \
  php/js/show-hide.php \
  php/mail/admin/index.php \
  php/mail/index.php \
  php/markup-test.php \
  php/my/admin/cc.php \
  php/my/admin/change_notifications.php \
  php/my/admin/change.php \
  php/my/admin/editsshkeys.php \
  php/my/admin/index.php \
  php/my/admin/resume.php \
  php/my/admin/sessions.php \
  php/my/bookmarks.php \
  php/my/groups.php \
  php/my/index.php \
  php/my/items.php \
  php/my/quitproject.php \
  php/my/votes.php \
  php/news/admin/index.php \
  php/news/approve.php \
  php/news/atom.php \
  php/news/index.php \
  php/news/submit.php \
  php/p \
  php/people/admin/index.php \
  php/people/createjob.php \
  php/people/editjob.php \
  php/people/index.php \
  php/people/resume.php \
  php/people/viewgpg.php \
  php/people/viewjob.php \
  php/pr \
  php/project/admin/conf-copy.php \
  php/project/admin/editgroupfeatures.php \
  php/project/admin/editgroupinfo.php \
  php/project/admin/editgroupnotifications.php \
  php/project/admin/history.php \
  php/project/admin/index.php \
  php/project/admin/squadadmin.php \
  php/project/admin/useradmin.php \
  php/project/admin/userperms.php \
  php/project/index.php \
  php/project/memberlist-gpgkeys.php \
  php/project/memberlist.php \
  php/project/release-gpgkeys.php \
  php/project/search.php \
  php/projects \
  php/projects.php \
  php/project/stats/.htaccess \
  php/recipe/index.php \
  php/register/index.php \
  php/register/requirements.php \
  php/register/upload.php \
  php/robots.txt \
  php/search/index.php \
  php/sendmessage.php \
  php/siteadmin/groupedit.php \
  php/siteadmin/grouplist.php \
  php/siteadmin/group_type.php \
  php/siteadmin/index.php \
  php/siteadmin/lastlogins.php \
  php/siteadmin/mailman.php \
  php/siteadmin/retestconfig.php \
  php/siteadmin/spamlist.php \
  php/siteadmin/triggercreation.php \
  php/siteadmin/user_changepw.php \
  php/siteadmin/usergroup.php \
  php/siteadmin/userlist.php \
  php/sr/index.php \
  php/stats/index.php \
  php/testconfig.php \
  php/testing/format_date.php \
  php/testing/index.php \
  php/testing/markup.php \
  php/testing/sane.php \
  php/testing/test.php \
  php/.topdir \
  php/u \
  php/us \
  php/users \
  php/users.php \
  php/vcs/index.php \
  site-specific/gnu/account/editsshkeys.php \
  site-specific/gnu/account/first.php \
  site-specific/gnu/account/index_gpg.php \
  site-specific/gnu/account/index_ssh.php \
  site-specific/gnu/admin/groupedit_grouptype.php \
  site-specific/gnu/admin/groupedit_intro.php \
  site-specific/gnu/admin/proj_email.php \
  site-specific/gnu/arch/index.php \
  site-specific/gnu/bzr/index.php \
  site-specific/gnu/contact.php \
  site-specific/gnu/cvs/index.php \
  site-specific/gnu/fingerprints.php \
  site-specific/gnu/forbidden_group_names.php \
  site-specific/gnu/forbidden_mail_domains.php \
  site-specific/gnu/forbidden_realnames.php \
  site-specific/gnu/forbidden_theme.php \
  site-specific/gnu/git/index.php \
  site-specific/gnu/gpg-sample.php \
  site-specific/gnu/hashes.php \
  site-specific/gnu/hg/index.php \
  site-specific/gnu/homepage.php \
  site-specific/gnu/mail/about_list_creation.php \
  site-specific/gnu/menu.php \
  site-specific/gnu/my/groups.php \
  site-specific/gnu/my/request_for_inclusion.php \
  site-specific/gnu/page_footer.php \
  site-specific/gnu/page_header.php \
  site-specific/gnu/people/createjob.php \
  site-specific/gnu/people/editjob.php \
  site-specific/gnu/people/editresume.php \
  site-specific/gnu/people/index_group.php \
  site-specific/gnu/people/index.php \
  site-specific/gnu/project/admin/index_misc.php \
  site-specific/gnu/register/confirmation_mail.php \
  site-specific/gnu/register/confirmation.php \
  site-specific/gnu/register/index.php \
  site-specific/gnu/register/requirements.php \
  site-specific/gnu/svn/index.php \
  site-specific/nongnu/admin/groupedit_grouptype.php \
  site-specific/nongnu/admin/groupedit_intro.php \
  site-specific/nongnu/admin/proj_email.php \
  site-specific/nongnu/contact.php \
  site-specific/nongnu/fingerprints.php \
  site-specific/nongnu/forbidden_group_names.php \
  site-specific/nongnu/forbidden_mail_domains.php \
  site-specific/nongnu/forbidden_realnames.php \
  site-specific/nongnu/forbidden_theme.php \
  site-specific/nongnu/gpg-sample.php \
  site-specific/nongnu/hashes.php \
  site-specific/nongnu/homepage.php \
  site-specific/nongnu/menu.php \
  site-specific/nongnu/page_footer.php \
  site-specific/nongnu/page_header.php \
  site-specific/nongnu/project/admin/index_misc.php

dir_links = \
  php/arch \
  php/bzr \
  php/cookbook \
  php/hg \
  php/images/Cafe.theme \
  php/images/Cappuccino.theme \
  php/images/CERN.theme \
  php/images/CodeX.theme \
  php/images/DarkSlate.theme/arrows \
  php/images/DarkSlate.theme/bool \
  php/images/DarkSlate.theme/contexts \
  php/images/DarkSlate.theme/misc \
  php/images/DarkSlate.theme/roles \
  php/images/Elessar.theme/arrows \
  php/images/Elessar.theme/bool \
  php/images/Elessar.theme/contexts \
  php/images/Elessar.theme/misc \
  php/images/Elessar.theme/roles \
  php/images/Emeraud.theme/arrows \
  php/images/Emeraud.theme/bool \
  php/images/Emeraud.theme/contexts \
  php/images/Emeraud.theme/misc \
  php/images/Emeraud.theme/roles \
  php/images/Forest.theme/arrows \
  php/images/Forest.theme/bool \
  php/images/Forest.theme/contexts \
  php/images/Forest.theme/misc \
  php/images/Forest.theme/roles \
  php/images/Gna.theme/arrows \
  php/images/Gna.theme/bool \
  php/images/Gna.theme/contexts \
  php/images/Gna.theme/misc \
  php/images/Gna.theme/roles \
  php/images/LCD.theme \
  php/images/Light.theme/arrows \
  php/images/Light.theme/bool \
  php/images/Light.theme/contexts \
  php/images/Light.theme/misc \
  php/images/Light.theme/roles \
  php/images/Light2.theme \
  php/images/LinuxfrPointOrg.theme/arrows \
  php/images/LinuxfrPointOrg.theme/bool \
  php/images/LinuxfrPointOrg.theme/contexts \
  php/images/LinuxfrPointOrg.theme/misc \
  php/images/LinuxfrPointOrg.theme/roles \
  php/images/OldCERN.theme/arrows \
  php/images/OldCERN.theme/bool \
  php/images/OldCERN.theme/contexts \
  php/images/OldCERN.theme/misc \
  php/images/OldCERN.theme/roles \
  php/images/pllx.theme/arrows \
  php/images/pllx.theme/bool \
  php/images/pllx.theme/contexts \
  php/images/pllx.theme/misc \
  php/images/pllx.theme/roles \
  php/images/printer.theme \
  php/images/Reverse.theme/arrows \
  php/images/Reverse.theme/bool \
  php/images/Reverse.theme/contexts \
  php/images/Reverse.theme/misc \
  php/images/Reverse.theme/roles \
  php/images/Savanedu.theme \
  php/images/Savannah.theme/arrows \
  php/images/Savannah.theme/bool \
  php/images/Savannah.theme/contexts \
  php/images/Savannah.theme/misc \
  php/images/Savannah.theme/roles \
  php/images/SlashD.theme/arrows \
  php/images/SlashD.theme/bool \
  php/images/SlashD.theme/contexts \
  php/images/SlashD.theme/misc \
  php/images/SlashD.theme/roles \
  php/images/SoftGreen.theme \
  php/images/UGent.theme \
  php/images/Water.theme/arrows \
  php/images/Water.theme/bool \
  php/images/Water.theme/contexts \
  php/images/Water.theme/misc \
  php/images/Water.theme/roles \
  php/images/www.gnu.org.theme/arrows \
  php/images/www.gnu.org.theme/bool \
  php/images/www.gnu.org.theme/contexts \
  php/images/www.gnu.org.theme/misc \
  php/images/www.gnu.org.theme/roles \
  php/patch \
  php/support \
  php/svn \
  php/task \
  site-specific/gnu-content \
  site-specific/nongnu-content \
  site-specific/nongnu/people \
  site-specific/nongnu/account \
  site-specific/nongnu/arch \
  site-specific/nongnu/bzr \
  site-specific/nongnu/cvs \
  site-specific/nongnu/git \
  site-specific/nongnu/hg \
  site-specific/nongnu/mail \
  site-specific/nongnu/my \
  site-specific/nongnu/register \
  site-specific/nongnu/svn

frontenddir = $(pkgdatadir)/frontend
dest_dir = $(DESTDIR)$(frontenddir)

extra_dist = $(basic_png) $(ordinary_files) $(png_src)

EXTRA_DIST = $(extra_dist)

if HAVE_FRONTEND
confdir = $(sysconfdir)/$(PACKAGE)
$(derived_png): %.png: %.orig.png
	$(MKDIR_P) `echo "$@" | ${DIRNAME}`
	convert -resize 24x24 -background white -antialias $< $@

nobase_nodist_frontend_DATA = php/include/ac_config.php local.php $(derived_png)

nobase_frontend_DATA = $(extra_dist)

CLEANFILES = $(derived_png)
uninstall-hook: ; rm -fr $(dest_dir)

install-data-hook: ; \
  saved_pwd=`pwd`; \
  for l in $(dir_links); do \
    test -h "$(srcdir)/$$l" || continue; \
    source=`readlink "$(srcdir)/$$l"`; \
    dir=`echo "$(dest_dir)/$$l" | ${DIRNAME}`; \
    base=`echo " $$l" | ${SED} 's,.*/,,;s,^ ,,'`; \
    cd "$$dir"; \
    test -e "$$base" && { cd $$saved_pwd; continue; } ; \
    $(LN_S) -f "$$source" $$base || exit 1; \
    cd $$saved_pwd; \
  done

installcheck_local_targets = installcheck-run-tests installcheck-test-symlinks

.PHONY: $(installcheck_local_targets)
installcheck-local: $(installcheck_local_targets)

installcheck-run-tests: ; \
  cd $(dest_dir)/php; \
  for t in markup sane format_date; do \
    res=`$(PHP) -c $(DESTDIR)/$(confdir)/php.ini testing/$$t.php \
      || echo Exit code of \
         \'$(PHP) -c $(DESTDIR)/$(confdir)/php.ini testing/$$t.php\' is $$?`; \
    if test "x$$res" = x; then continue; fi; \
    echo $$t failed: ; echo $$res; exit 1; \
  done

installcheck-test-symlinks: ; \
  cd $(srcdir); \
  find . -type l \
    | while read l; do \
        test -d "$$l" || continue; \
        found=no; \
        for d in $(dir_links); do \
          test ./$$d == "$$l" || continue; \
          found=yes; break; \
        done; \
        test $$found == no || continue; \
        echo link "$$l" not found in the installed tree; \
        exit 1; \
      done

endif # HAVE_FRONTEND
