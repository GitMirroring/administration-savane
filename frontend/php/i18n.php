<?php
# Set preferred language (overriding possible browser preferences).
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

require_once ('./include/init.php');
require_once ('./include/sane.php');

utils_disable_cache ();
extract (sane_import('request',
  [
    'preg' => [['language', '/^(([a-z]{2}((-[a-z]{2})?))|100)$/']],
    'internal_uri' => 'lang_uri',
    'true' => ['cookie_test', 'cookie_for_a_year', 'update']
  ]
));
form_check ('update');

# Check cookie support.
if (!isset ($_COOKIE["cookie_probe"]))
  {
    if (!$cookie_test)
      {
        # Attempt to set a cookie to go to a new page to see
        # if the client will indeed send that cookie.
        session_cookie ('cookie_probe', 1);
        session_redirect ('i18n.php?lang_uri='
          . utils_urlencode ($lang_uri) . '&cookie_test=1'
        );
      }
    # TRANSLATORS: the first argument is a domain (like "savannah.gnu.org"
    # vs. "savannah.nongnu.org"); the second argument
    # is a URL ("[URL label]" transforms to a link).
    $msg = sprintf (_("Savane thinks your cookies are not activated for %s.\n"
      . "Please activate cookies in your web browser for this website\n"
      . "and [%s try again]."), $sys_default_domain,
      "$sys_https_url$sys_home/i18n.php?lang_uri=$lang_uri");
    fb ($msg, 1);
  }

if (!empty ($language)
    && ($language == 100 || isset ($locale_names[$language])))
  {
    $period = 0; # Clear cookie at the end of the session.
    if ($language == 100)
      $period = time () - 3600 * 24; # Request to reset - clear cookie.
    elseif ($cookie_for_a_year)
      $period = time () + 60 * 60 * 24 * 365;
    utils_setcookie ('LANGUAGE', $language, $period);
    header ("Location: $lang_uri");
    exit;
  }

if (!empty ($language))
  fb (sprintf (_("Requested language code '%s' is unknown."), $language), 1);

$checked_language = "en";
if (isset ($_COOKIE['LANGUAGE']))
  $checked_language = $_COOKIE['LANGUAGE'];

site_header (['title' => _("Set language")]);

print "<p>";
print
  _("Savane uses language negotiation to automatically\nselect "
    . "the translation the visitor prefers, and this is what we\ngenerally "
    . "recommend. In order to use this feature, you should\nconfigure your "
    . "preferred languages in your browser. However, this page\noffers "
    . "a way to override the mechanism of language negotiation for the\n"
    . "cases where configuring browser is hard or impossible. Note that each\n"
    . "domain (like savannah.gnu.org vs. savannah.nongnu.org) has its own\n"
    . "setting.");
print "</p>\n";

print form_tag ();
print form_hidden (['lang_uri' => utils_specialchars ($lang_uri)]);

print '<p>'
  . form_checkbox ('cookie_for_a_year', $cookie_for_a_year, ['tabindex' => 1])
  . '<span class="preinput"><label for="cookie_for_a_year">'
  . _("Keep for a year") . "</label></span><br />\n";
print '<span class="text">'
  . _("Your language choice will be stored in a cookie for a year.\n"
      . "When unchecked, it will be cleared at the end of browser session.")
  . "</span></p>\n";

print "<p>\n&nbsp;&nbsp;<label for=\"language\">" . ("Language:") . "</label>";
print html_build_select_box_from_arrays (
  array_keys ($locale_names), array_values ($locale_names),
  "language", $checked_language, true, _("Reset")
);
print "</p>\n<p><span class=\"text\">"
  . _("Use the topmost item (&ldquo;Reset&rdquo;) to clear the cookie\n"
      . "immediately.")
  . "</span></p>\n";

print form_footer (_("Set language"));
$HTML->footer ([]);
?>
