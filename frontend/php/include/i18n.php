<?php
# Configure locale using browser preferences.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2000-2006 Stéphane Urbanovski <s.urbanovski--ac-nancy-metz.fr>
# Copyright (C) 2014, 2016, 2017 Assaf Gordon
# Copyright (C) 2001-2011, 2013, 2017 Sylvain Beucler
# Copyright (C) 2008-2017, 2020 Karl Berry (disable languages)
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

require_once (dirname (__FILE__) . '/utils.php');

# Used for strings that specifically shouldn't be localized.
function no_i18n ($x)
{
  return $x;
}

# Table of supported languages:
# "language variant" => "associated preferred locale"
$locale_list = [];

# Locale names offered for selection in /i18n.php.
$locale_names = [];

# Table of languages supported by Savane: $code => [$locale, $name].
$languages_available = [];

# Add language to arrays:
# $code - language code
# $locale - locale to use
# $name - label for the item in select box; when empty, the language
# isn't offered for selection on /i18n.php.
function register_language ($code, $locale, $name = "")
{
  global $locale_list, $locale_names, $sys_linguas, $languages_available;
  $languages_available[$code] = [$locale, $name];
  if (false === strpos (":$sys_linguas:", ":$code:"))
    return;
  $locale_list[$code] = "$locale.UTF-8";
  if ($name !== "")
    $locale_names[$code] = $name;
}

register_language ("ca", "ca_ES", "català");
register_language ("de", "de_DE", "Deutsch");
register_language ("en", "en_US", "English");
#register_language ("en-gb", "en_GB");
register_language ("es", "es_ES", "español");
register_language ("fr", "fr_FR", "français");
#register_language ("fr-fr", "fr_FR");
register_language ("it", "it_IT", "italiano");
register_language ("ja", "ja_JP", "日本語");
#register_language ("ja-jp", "ja_JP");
register_language ("he", "he_IL", "עברית");
register_language ("pt", "pt_BR", "português do Brasil");
register_language ("pt-br", "pt_BR");
#register_language ("ro", "ro_RO", "română");
register_language ("ru", "ru_RU", "русский");
register_language ("sv", "sv_SE", "svenska");
#register_language ("sv-se", "sv_SE");
register_language ("uk", "uk_UA", "українська");
register_language ("zh", "zh_CN", "简体中文");
#register_language ("zh-cn", "zh_CN");

# Get user's preferred languages from UA headers.
$accept_lang =  str_replace ([' ', "\t"], '', getenv ("HTTP_ACCEPT_LANGUAGE"));
$browser_preferences = explode (",", strtolower ($accept_lang));

# Set the default locale.
$quality = 0;
$best_lang = "en";

if (isset ($sys_default_locale))
  $best_lang = $sys_default_locale;

# Find the best language available.
foreach ($browser_preferences as $lng)
  {
    # Parse language and quality factor.
    $q = 1;
    $arr = explode (';', $lng);
    if (isset ($arr[1]))
      {
        $lng = $arr[0];
        $arr[1] = $arr[1];
        if (substr ($arr[1], 0, 2) === 'q=')
          $q = substr ($arr[1], 2);
        else continue; # The second half doesn't define quality; skip the item.
        if ($q > 1 || $q <= 0)
          continue; # Unusable quality value.
      }
    $cur_lang = $lng;

    # Check language code.
    $lang_len = strpos ($cur_lang, '-');
    if ($lang_len === FALSE)
      $lang_len = strlen ($cur_lang);
    if ($lang_len < 2)
      continue; # Language code must be at least 2 characters long.

    if (empty ($locale_list[$cur_lang]))
      continue; # No such locale; skip the item.

    if ($q <= $quality)
      continue;

    # Best item available so far: select.
    $quality = $q;
    $best_lang = $cur_lang;
  } # foreach ($browser_preferences as $lng)

if (isset ($_COOKIE['LANGUAGE']) && isset ($locale_list[$_COOKIE['LANGUAGE']]))
  $best_lang = $_COOKIE['LANGUAGE'];

$locale = $locale_list[$best_lang];
define ('SV_LANG', $best_lang);

function i18n_setup ($locale)
{
  global $sys_localedir;
  $err = [];
  # The LANGUAGE variable would override our settings, so we unset it.
  putenv ("LANGUAGE=");
  if (setlocale (LC_ALL, $locale) === false)
    $err[] = no_i18n ('Failed to set locale.');
  utils_update_decimal_separator ();
  if (!empty ($sys_localedir))
    if (bindtextdomain ('savane', $sys_localedir) == false)
      $err[] = no_i18n ('Failed to bind text domain.');
  if (textdomain ('savane') != 'savane')
    $err[] = no_i18n ('Failed to set text domain.');
  return $err;
}
i18n_setup  ($locale);
?>
