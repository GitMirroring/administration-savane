<?php
# Captcha support.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy
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

# We don't include init.php because we test this feature in testconfig.php,
# so we should only rely on ac_config.php.
require_once ('include/ac_config.php');
require_once ('include/utils.php');
require_once ('include/error.php');
if (!empty ($sys_conf_file) && is_readable ($sys_conf_file))
  require_once $sys_conf_file;

if (empty ($sys_captchadir))
  $sys_captchadir = '/usr/share/php';

if (empty ($sys_captcha_font_path))
 $sys_captcha_font_path = '/usr/share/fonts/truetype/dejavu/';
if (empty ($sys_captcha_font_file))
 $sys_captcha_font_file = 'DejaVuSans-Bold.ttf';

set_include_path ("$sys_captchadir:" . get_include_path ());
require_once "Text/CAPTCHA.php";

function output_image ($img)
{
  header ('Content-Type: image/png');
  header ('Content-Length: ' . strlen ($img));
  print $img;
  exit (0);
}

function random_color ($dark)
{
  $start = $dark? 0: 127;
  $end = $dark? 64: 255;
  $ret = '#';
  for ($i = 0; $i < 3; $i++)
    $ret .= sprintf ("%02X", rand ($start, $end));
  return $ret;
}

function fill_options ()
{
  $bg_dark = rand (0, 255) < 128;
  $chars = 'abcdefghijklmnopqrstuvxyz346789';
  $options = [
    'width' => 215, 'height' => 80, 'output' => 'png',
    'imageOptions' => [
      'font_size' => 24,
      'font_path' => $GLOBALS['sys_captcha_font_path'],
      'font_file' => $GLOBALS['sys_captcha_font_file'],
      'text_color' => random_color (!$bg_dark),
      'background_color' => random_color ($bg_dark)
    ],
    'phraseOptions' => ['unpronounceable', $chars],
  ];
  session_start ();

  if (isset ($_SESSION['captcha_code']))
    $options['phrase'] = $_SESSION['captcha_code'];
  return $options;
}

function run_image ()
{
  $options = fill_options ();
  $captcha_class = 'Image';
  $captcha = Text_CAPTCHA::factory ($captcha_class);
  $captcha->init ($options);
  if (!isset ($_SESSION['captcha_code']))
    $_SESSION['captcha_code'] = $captcha->getPhrase ();
  # FIXME: sound output is missing.
  output_image ($captcha->getCAPTCHA ());
}

function unset_captcha_code ()
{
  unset ($_SESSION['captcha_code']);
}

function validate_captcha ()
{
  global $antispam_is_valid;

  session_start ();
  if ($antispam_is_valid === 'unset')
    {
      unset_captcha_code ();
      return;
    }

  if (
    isset ($_POST['captcha_code']) && isset ($_SESSION['captcha_code'])
    && $_POST['captcha_code'] === $_SESSION['captcha_code']
  )
    $antispam_is_valid = true;
  else
    fb (_("Please correctly answer the antispam captcha!"), 1);

  unset_captcha_code ();
}

if (isset ($antispam_is_valid))
  validate_captcha ();
else
  run_image ();
?>
