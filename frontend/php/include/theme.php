<?php
# Theme functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
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

# theme value is fetched by getting the cookie. But we keep in the
# database the setting, so someone using another computer can easily
# remember the theme he previously chose.

require_once (dirname (__FILE__) . '/utils.php');

# Jump to the next theme available.
function theme_rotate_jump ($user_theme)
{
  if (!user_isloggedin ())
    return;
  if ($user_theme === 'rotate')
    theme_get_rotated (true);
  elseif ($user_theme === 'random')
    theme_get_random (true);
}

function theme_filter_css ($path, $file)
{
  global $forbid_theme_regexp;
  # Ignore symlinks.
  if (is_link ("$path$file"))
    return null;

  # Take only css files.
  if (!preg_match ("/^(.*)\.css$/", $file, $matches))
    return null;

  # Forbidden themes are ignored.
  if (preg_match ($forbid_theme_regexp, strtolower ($matches[1])))
    return null;
  return $matches[1];
}

# Return an array with all the themes except the special cases "rotate"
# and "random".
function theme_list ()
{
  utils_get_content ("forbidden_theme");
  $theme = [];
  $path = $GLOBALS['sys_www_topdir'] . '/css/';
  $dir = opendir ($path);
  while ($file = readdir ($dir))
    {
      $entry = theme_filter_css ($path, $file);
      if ($entry !== null)
        $theme[] = $entry;
    }
  closedir ($dir);
  natcasesort ($theme); # Sort themes - case insensitive.
  # No result?  Return only the default theme.
  # (If there were no result, there is a problem anyway somewhere in the
  # installation.)
  if (!count ($theme))
    $theme[] = $GLOBALS['sys_themedefault'];
  return $theme;
}

# Check whether a theme follows latest GUIDELINES.
function theme_guidelines_check ($theme)
{
  # Get from the README the latest GUIDELINES number.
  preg_match ("/VERSION: (.*)/",
    utils_read_file ($GLOBALS['sys_www_topdir'] . "/css/README"), $latest
   );
  # Get from the css the current GUIDELINES number.
  preg_match ("/\/\* GUIDELINES VERSION FOLLOWED: (.*) \*\//",
    utils_read_file ($GLOBALS['sys_www_topdir'] . "/css/$theme.css"), $current
  );
  return $latest[1] == $current[1];
}

# If the theme is valid, return $user_theme; else return default theme.
function theme_validate ($user_theme)
{
  if ($user_theme === null)
    return $GLOBALS['sys_themedefault'];
  utils_get_content ("forbidden_theme");

  # Disallow going towards filesystem root and other queer paths.
  $forbidden = preg_match (',(/[.]*/|^/|/$|\s),', $user_theme);

  if (
    isset ($GLOBALS['forbid_theme_regexp'])
    && preg_match ($GLOBALS['forbid_theme_regexp'], $user_theme)
  )
    $forbidden = true;

  if ($forbidden)
    return $GLOBALS['sys_themedefault'];

  if (file_exists ($GLOBALS['sys_www_topdir'] . "/css/$user_theme.css"))
    return $user_theme;
  return $GLOBALS['sys_themedefault'];
}

# Get next random theme; set the cookie.
function theme_next_random ()
{
  $theme = theme_list ();
  $num = utils_mt_rand (count ($theme) - 1);
  $random_theme = theme_validate ($theme[$num]);
  $expire = time () + 60 * 60 * 24;
  return $random_theme;
}

# Return random theme value once a day, depending on user preferences.
function theme_get_random ($force_rotation = false)
{
  $random_pref = user_get_preference ('random_theme');
  if ($random_pref !== false)
    {
      $pref_array = explode(":", $random_pref);
      if (time () < $pref_array[1] && !$force_rotation)
        return theme_validate ($pref_array[0]);
    }

  # Select next random theme.
  $random_theme = theme_next_random ();
  $expire = time () + 60 * 60 * 24;
  user_set_preference ('random_theme', $random_theme . ":" . $expire);
  return $random_theme;
}

# Return theme value rotated once a day.
function theme_get_rotated ($force_rotation = false)
{
  $num = 0;
  $rot_pref = user_get_preference ('rotated_theme');
  $theme = theme_list ();
  if ($rot_pref !== false)
    {
      $pref_array = explode (":", $rot_pref);
      $num = $pref_array[0];
      if (time () < $pref_array[1] && !$force_rotation)
        return $theme[$pref_array[0]];
    }
  $num++;
  if ($num >= count ($theme))
    $num = 0;
  $rotate_theme = $theme[$num];
  $expire = time () + 60 * 60 * 24;
  user_set_preference ('rotated_theme', $num . ":" . $expire);
  return $rotate_theme;
}

# Calculate current theme value from its setting.
function theme_value ($theme_setting)
{
  if ($theme_setting === 'random')
    return theme_get_random ();
  if ($theme_setting === 'rotate')
    return theme_get_rotated ();
  return theme_validate ($theme_setting);
}

# Set theme cookies consistent with user's account settings.
function theme_set_cookies ($user_theme)
{
  if ($user_theme === 'random')
    {
      if (!isset ($_COOKIE['SV_THEME_RANDOM'])
          || $_COOKIE['SV_THEME_RANDOM'] !== SV_THEME)
        utils_setcookie ('SV_THEME_RANDOM', SV_THEME, time () + 60 * 60 * 24);
    }

  if ($user_theme === 'rotate')
    {
      if (!isset ($_COOKIE['SV_THEME_ROTATE'])
          || $_COOKIE['SV_THEME_ROTATE'] !== SV_THEME)
        utils_setcookie ('SV_THEME_ROTATE', SV_THEME, time () + 60 * 60 * 24);
      $rot_pref = user_get_preference ('rotated_theme');
      $num = 0;
      if ($rot_pref !== false)
        {
          $pref_array = explode (":", $rot_pref);
          $num = $pref_array[0];
        }
      if (!isset ($_COOKIE['SV_THEME_ROTATE_NUMERIC'])
          || $_COOKIE['SV_THEME_ROTATE_NUMERIC'] != $num)
        utils_setcookie ('SV_THEME_ROTATE_NUMERIC', $num,
                         time () + 60 * 60 * 24 * 365);
    }

  if (!isset ($_COOKIE['SV_THEME']) || $_COOKIE['SV_THEME'] !== $user_theme)
    {
      $expire = time () + 60 * 60 * 24;
      if ($user_theme === 'random' || $user_theme === 'rotate')
        $expire +=  60 * 60 * 24 * 364;
      utils_setcookie ('SV_THEME', $user_theme, $expire);
    }
}

# Guess theme from cookies (for anonymous users).
function theme_guess ()
{
  if (!isset($_COOKIE['SV_THEME']))
    {
      # No theme was selected, we use the default one.
      define('SV_THEME', $GLOBALS['sys_themedefault']);
      return;
    }

  if ($_COOKIE['SV_THEME'] === 'random')
    {
      # The user selected random theme.
      # We set randomly a theme and a cookie for a day.
      if (isset($_COOKIE['SV_THEME_RANDOM']))
        {
          define('SV_THEME',
                 theme_validate ($_COOKIE['SV_THEME_RANDOM']));
          return;
        }
      $next_theme = theme_next_random ();
      define('SV_THEME', $next_theme);
      utils_setcookie('SV_THEME_RANDOM', $next_theme, time() + 60 * 60 * 24);
      return;
    } # if ($_COOKIE['SV_THEME'] == 'random')

  if ($_COOKIE['SV_THEME'] === 'rotate')
    {
      if (isset($_COOKIE['SV_THEME_ROTATE']))
        {
          define('SV_THEME', theme_validate($_COOKIE['SV_THEME_ROTATE']));
          return;
        }
      $theme = theme_list ();
      $num = 0;
      if (isset($_COOKIE['SV_THEME_ROTATE_NUMERIC']))
        {
          $num = $_COOKIE['SV_THEME_ROTATE_NUMERIC'] + 1;
          if ($num >= count ($theme))
            $num = 0;
        }
      utils_setcookie('SV_THEME_ROTATE_NUMERIC', $num,
                      time() + 60 * 60 * 24 * 365);
      # We associate this number with a theme.
      $rotate_theme = $theme[$num];
      utils_setcookie('SV_THEME_ROTATE', $rotate_theme, time() + 60 * 60 * 24);
      define('SV_THEME', $rotate_theme);
      return;
    } # if ($_COOKIE['SV_THEME'] == 'rotate')
  define('SV_THEME', theme_validate($_COOKIE['SV_THEME']));
}

function theme_actualise_theme ($user_theme)
{
  if ($user_theme == "Default")
    return "";
  if ($user_theme !== 'rotate' && $user_theme !== 'random')
    return theme_validate ($user_theme);
  return $user_theme;
}

# Select theme.
function theme_select ()
{
  # The user requested updating theme: make the changes
  # before selecting the theme.
  if (function_exists ('update_theme'))
    update_theme ();

  if (!user_isloggedin ())
    {
      # Anonymous user: guess the theme from cookies.
      theme_guess ();
      return;
    }

  # When the user is logged in, the theme comes from user's settings.
  $user_theme = theme_value (user_get_field (0, 'theme'));
  define ('SV_THEME', $user_theme);
  theme_set_cookies ($user_theme);
}
?>
