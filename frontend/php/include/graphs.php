<?php
# Attempt to replace HTML_graphs.php with something more spartian, efficient
# and w3c-compliant.
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

function graphs_get_percent ($k, $v, &$total)
{
  if ($total[$k] <= 0)
    {
      $total[$k] = 0;
      return [0, _("n/a")];
    }
  $width = round (($v / $total[$k]) * 100);
  # TRANSLATORS: printing percentage.
  $print = sprintf (_("%s%%"), $width);
  return [$width, $print];
}

function graphs_entry_title ($k, $user_field, $localize)
{
  if ($user_field)
    return utils_user_link ($k);
  if ($localize)
    return gettext ($k);
  return $k;
}

function graphs_build_entry (
  $k, $v, &$total, &$widths, $user_field, $localize_keys, $id
)
{
  list ($percent_width, $percent_print) = graphs_get_percent ($k, $v, $total);
  $title = graphs_entry_title ($k, $user_field, $localize_keys);

  if ($percent_width > 25)
    $class = '';
  else
    $class = 'closed';

  $output = "<tr class='half-width'>\n<td class='first'>$title</td>\n"
    . "<td class='second'>";
  # TRANSLATORS: the arguments mean "%1$s of (total) %2$s".
  $output .= sprintf (_('%1$s/%2$s'), $v, $total[$k]);
  $output .= "</td>\n<td class='second'>$percent_print</td>\n"
    . "<td class='third'><div class='prioraclosed'>"
    . "<div class='priori$class' id='graph-bar$id'>&nbsp;</div>"
    . "</div></td>\n</tr>\n";
  $widths = "$widths,$percent_width";
  return $output;
}

# It can accept db result directly or an array.
# Total must be an array, if provided.
function graphs_build (
  $result, $field = 0, $dbdirect = 1, $total = 0, $id0 = 0, $localize_keys = 0
)
{
  if (!$result)
    {
      fb (_("No data to work on, no graph will be built"), 1);
      return [$id0, '', ''];
    }

  if ($dbdirect)
    {
      $data = [];
      for ($i = 0; $i < db_numrows ($result) ; $i++)
        $data[db_result ($result, $i, 0)] = db_result ($result, $i, 1);
    }
  else
    $data = $result;

  # Get the total number of items.
  # Total should not be passed as argument, normally.
  if (!$total)
    {
      $totalvar = 0;
      foreach ($data as $k => $v)
        $totalvar += $v;

      $total = [];
      foreach ($data as $k => $v)
        $total[$k] = $totalvar;
    }
  else
    # If total was passed as argument, no crosscheck, assume it is accurate.
    $totalvar = 1;

  $id = $id0;
  $widths = "";
  $output = "";
  # Print the stats, unless $total is nul.
  # If total was passed as argument, strange result may be printed.
  if (!$totalvar)
    {
      $output .= '<p class="warn">';
      $output .= _("The total number of results is zero.");
      $output .= '</p>';
      return [$id, substr ($widths, 1), $output];
    }
  $output .= "\n\n<table class='graphs'>\n";
  $ass_to = $field === "assigned_to";
  foreach ($data as $k => $v)
    {
      $output .=
        graphs_build_entry (
          $k, $v, $total, $widths, $ass_to, $localize_keys, $id);
      $id++;
    } # foreach ($data as $k => $v)
  $output .= "\n</table>\n\n";
  return [$id, substr ($widths, 1), $output];
}
?>
