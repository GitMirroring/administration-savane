# Ad-hoc script to fix and normalize copyright notices.
# Public domain; originally written in 2023 by Ineiev.
# Parse files, compile lists of copyright holders with respective
# copyright years.

# This script is invoked from ./notices.

# The copyright years from all files are merged for every copyright holder,
# the result is output as list of years including ranges when more than
# two subsequent years follow; the output line format is,
#
# years:	copyright-holder	copyrightable-years
#
# The fields are separated with tabs.

BEGIN { i = 0 }
function split_years(years, i, ret, j)
{
  n = split(years, arr)
  ret = ""
  for (i = 1; i <= n; i++)
    {
      sub(/,$/, "", arr[i])
      m = split(arr[i], halves, "-")
      if (m == 1)
        halves[2] = halves[1]
      for (j = halves[1]; j <= halves[2]; j++)
         ret = ret "\t" j
    }
  sub(/\t/, "", ret)
  return ret
}
{
  file = $1; sub(/:$/, "", file)
  sub(/^[^:]*: Copyright \(C\) /, "")
  file_list[file] = 1
  years = $0
  sub(/ [^[:digit:]]*$/, "", years)
  holder = substr($0, length(years) + 2)
  holder_notes = ""
  if (match(holder, / (((<[^<]*>|[(][^()]*[)])([[:space:]]+|$))+)$/, arr))
    {
      holder_notes = arr[1]
      holder = substr(holder, 1, RSTART - 1)
    }
  if (holder_notes != "")
    note_arr[holder] = note_arr[holder] "\t" holder_notes
  next_years = split_years(years)
  year_arr[holder] = year_arr[holder] "\t" next_years
  print "line: " file "; years: " next_years \
    "; name: " holder "; notes: >" holder_notes "<"
}
function merge_values(input, \
  i, sp_arr, arr, idx)
{
  sub(/^\t/, "", input)
  split(input, sp_arr, "\t")
  for (i in sp_arr)
    {
      idx = sp_arr[i]
      if (idx in aliases)
        idx = aliases[idx]
      arr[idx] = 1
    }
  input = ""
  for (i in arr)
    input = input "\t" i
  sub(/^\t/, "", input)
  return input
}
function inc_year(y0, yp, \
  dy)
{
  if (yp == y0)
    dy = y0
  else if (y0 == yp - 1)
    dy = y0 ", " yp
  else
    dy = y0 "-" yp
  return ", " dy
}
function merge_years(y, \
  arr, n, i, out, y0, yp, yn, dy)
{
  n = split(y, arr, "\t")
  out = ""
  y0 = yp = arr[1]
  for (i = 2; i <= n; i++)
    {
      yn = arr[i]
      if (yn == yp + 1)
        {
          yp = yn
          continue
        }
      out = out inc_year(y0, yp)
      y0 = yp = yn
    }
  out = out inc_year(y0, yp)
  sub(/^, /, "", out)
  return out
}
END {
  for (h in note_arr)
    note_arr[h] = merge_values(note_arr[h])
  for (h in year_arr)
    year_arr[h] = merge_years(merge_values(year_arr[h]))
  if (verbose)
    {
      for (h in year_arr)
        print "name: " h "; years: " year_arr[h] "; notes: " note_arr[h]
      for (f in file_list)
        print "file:\t" f
    }
  for (h in year_arr)
    print "years:\t" h "\t" year_arr[h]
}
