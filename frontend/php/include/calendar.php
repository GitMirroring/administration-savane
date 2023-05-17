<?php
# Calendar functions.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
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

function calendar_month_name ($month)
{
  # TRANSLATORS: names of months are used in selection boxes
  # like '%1$s %2$s %3$s'.
  $months = [
    '1' =>  _("January"), '2' =>  _("February"), '3' =>  _("March"),
    '4' =>  _("April"), '5' =>  _("May"), '6' =>  _("June"),
    '7' =>  _("July"), '8' =>  _("August"), '9' =>  _("September"),
    '10' =>  _("October"), '11' =>  _("November"), '12' =>  _("December")
  ];
  if ($month < 1 || $month > 12)
    return null;
  return $months[$month];
}

function calendar_selectbox ($level, $checked_val = 'xxaz', $inputname = false)
{
  if (!$inputname)
    $inputname = $level;

  $text = $number = [];
  $title = "";

  if ($level == 'day')
    {
      for ($day = 1; $day <= 31; $day++)
        {
          $number[] = $day;
          $text[] = $day;
        }
      $title = _("day of month");
    }
  elseif ($level == 'month')
    {
      for ($month = 1; $month <= 12; $month++)
        {
          $number[] = $month;
          $text[] = calendar_month_name ($month);
        }
      $title = _("month");
    }

  return html_build_select_box_from_arrays ($number, $text, $inputname,
    $checked_val, false, 'None', false, 'Any', false, $title
  );
}
function calendar_select_date ($day, $month, $year, $field_names)
{
  # TRANSLATORS: Arrange the arguments to make up the date in your language.
  return sprintf (_('<!-- Date: day, month, year --> %1$s %2$s %3$s'),
    calendar_selectbox ("day", $day, $field_names[0]),
    calendar_selectbox ("month", $month, $field_names[1]),
    ' <input type="text" title="' . _('year') . '" name="'
      . $field_names[2] . "\" size='4' maxlength='4' value='$year' />"
  );
}
?>
