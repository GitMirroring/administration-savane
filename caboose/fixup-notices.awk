# Ad-hoc script to fix and normalize copyright notices.
# Public domain; originally written in 2023 by Ineiev.
# Normalize copyright and license notices.

# This script is invoked from ./notices.

# Two classes of copyright holders are defined as per GNU Maintainers Info,
# "main" (those that are added in every file), and other (their copyright
# notices are only preserved when they show up in a particular file).
# the "main" copyright holders are listed in the 'principals' array assigned
# in BEGIN.
#
# The comments after copyright notices are preserved except listed
# in the 'aliases' array; that array maps a few strings, mostly emails,
# to unify their form for some copyright holders.
#
# The copyright notices are sorted by the last copyrightable year, then
# the name of the copyright holder (in the C locale).

# The input stream is the list of files (lines beginning with "file:")
# and the list of years with the respective copyright holder (lines beginning
# with "years:"; these lists are compiled with the ./parse-files.awk script.

# Variables that modify the behavior:
#
#   skip_principals: when true, the "main" copyright holders
#     are not forcefully added.
#   dont_touch_license: when true, the text of the license isn't updated.

# Configuration variables:
#   notice_start: regex that defines start of the license notice to update.
#   notice_end: regex that defines the end of the license notice to update.
#   notice_line_cnt: number of line to skip after finding notice_start
#     without looking for notice_end.
#   max_line_width (defined in BEGIN): length to try to wrap around copyright
#     notices.

function clean_exit(code)
{
  system("rm -f \"" tmp_file "\"")
  exit code;
}

BEGIN {
  p_i = 1
  max_line_width = 80
  next_line = ""
  # Strings to normalize email format.
  aliases["<ineiev--gnu.org>"] = "<ineiev@gnu.org>"
  aliases["<Yves.Perrin@cern.ch>"] = "<yves.perrin--cern.ch>"
  aliases["<ype--cern.ch>"] = "<yves.perrin--cern.ch>"
  aliases["<yves.perrin--at--cern.ch>"] = "<yves.perrin--cern.ch>"
  aliases["<s.urbanovski@ac-nancy-metz.fr>"] = "<s.urbanovski--ac-nancy-metz.fr>"
  aliases["<zerodeux@gnu.org>"] = "<zerodeux--gnu.org>"
  aliases["<beuc--gnu.org>"] = "<beuc--beuc.net>"
  aliases["<beuc@beuc.net>"] = "<beuc--beuc.net>"
  aliases["<yeupou--gna.org>"] = "<yeupou--gnu.org>"
  aliases["<yeupou--at--gnu.org>"] = "<yeupou--gnu.org>"
  aliases["<yeupou@gnu.org>"] = "<yeupou--gnu.org>"
  aliases["<yeupou---gnu.org>"] = "<yeupou--gnu.org>"
  aliases["<loic@gnu.org>"] = "<loic--gnu.org>"
  aliases["<loic@gnu.org> (sv_backups)"] = "<loic--gnu.org> (sv_backups)"
  if (!skip_principals)
    {
      i = 1
      # "Main" copyright holders that should be listed in every file.
      principals[i++] = "The SourceForge Crew"
      principals[i++] = "Mathieu Roy"
      principals[i++] = "Assaf Gordon"
      principals[i++] = "Sylvain Beucler"
      principals[i++] = "Ineiev"
    }
  copy_mark = "Copyright (C) "
}
function extract_field(field)
{
  if (substr($0, 1, length(field) + 2) != field ":\t")
    return ""
  return substr($0, length(field) + 3)
}

{
  f = extract_field("file")
  if (f != "")
    files[f] = 1
  f = extract_field("years")
  if (f != "")
    {
      split (f, arr, "\t")
      years[arr[1]] = arr[2]
    }
}

function match_copyright()
{
  return match($0, /Copyright.*(19|20)[[:digit:]]{2}\>/)
}
function current_stage(\
  stage_func)
{
  stage_func = "stage_" stage
  return @stage_func()
}
function inc_stage()
{
  accum = ""
  stage++
  print "stage " stage "/" max_stage
}
function ret_inc_stage()
{
  inc_stage()
  return current_stage()
}
function list_missing_principals(\
  i, ret)
{
  ret = ""
  for (i = 1; i in principals; i++)
    if (!(principals[i] in holder_set))
      ret = ret line_indent copy_mark \
        years[principals[i]] " " principals[i] "\n"
  print "<missing principals>\n" ret "</missing_principals>\n"
  return ret
}

function drop_copy_mark(l)
{
  sub(/\(c\)/, "(C)", l)
  sub(/^.*Copyright( \(C\))?[[:space:]]*/, "", l)
  return l
}

function extract_years(l)
{
  sub(/ [^[:digit:]]*$/, "", l)
  return l
}

function analyze_indent(\
  copy_line, cur_indent)
{
  copy_line = substr($0, copyright_rstart)
  cur_indent = substr($0, 1, copyright_rstart - 1)
  print "normalizing " copy_line
  if (line_indent != cur_indent)
    print "Note: indent >" line_indent "< vs >" cur_indent "<"
}

function get_years(holder)
{
  if (holder in years)
    return years[holder]
  print current_file ": no holder " holder " found!" > "/dev/stderr"
  clean_exit(1)
}

function rtrim(str)
{
  sub(/[[:space:]]*$/, "", str)
  return str
}

function trim(str)
{
  str = rtrim(str)
  sub(/^[[:space:]]*/, "", str)
  return str
}

function extract_holder(line, yr)
{
  yr = substr(line, length(yr) + 2)
  sub(/[[:space:]]*\(C\)[[:space:]]*/, "", yr)
  return yr
}

function extract_last_year_and_holder(l,\
  y, h)
{
  l = drop_copy_mark(l)
  y = extract_years(l)
  h = extract_holder(l, y)
  sub(/.*[- ]/, "", y)
  return y "\t" h
}

function cmp_copyright_lines(i0, v0, i1, v1, \
  l0, l1, yh0, yh1)
{
  l0 = extract_last_year_and_holder(v0)
  split(l0, yh0, "\t")
  l1 = extract_last_year_and_holder(v1)
  split(l1, yh1, "\t")
  if (yh0[1] < yh1[1])
    return -1
  if (yh0[1] > yh1[1])
    return 1
  if (yh0[2] < yh1[2])
    return -1
  if (yh0[2] > yh1[2])
    return 1
  return 0
}

function rewrap_line(line, \
  arr, out, max, str_indent, n, i, o, ret, ind)
{
  if (max_line_width >= length(line))
    return line
  line = substr(line, length(line_indent) + 1)
  ind = "  "
  str_indent = ""
  sep = ", "
  n = split(line, arr, sep)
  if (n < 2)
    return line_indent line

  ret = ""
  out = ""
  for (i = 1; i <= n; i++)
    {
      o = line_indent str_indent out arr[i] sep
      o = rtrim(o)
      if (length(o) > max_line_width)
        {
          ret = ret "\n" line_indent str_indent rtrim(out)
          str_indent = ind
          out = ""
        }
      out = out arr[i] sep
    }
  out = substr(out, 1, length(out) - length(sep))
  ret = ret "\n" line_indent str_indent rtrim(out)
  ret = rtrim(ret)
  sub(/^\n/, "", ret)
  return ret
}

function sort_copyright_lines(lines, \
  arr, pi_pref, out, i)
{
  pi_prev = PROCINFO["sorted_in"]
  split(lines, arr, "\n")
  out = ""
  PROCINFO["sorted_in"] = "cmp_copyright_lines"
  for (i in arr)
    if (length(arr[i]))
      out = out "\n" rewrap_line(arr[i])
  PROCINFO["sorted_in"] = pi_prev
  out = substr(out, length(line_indent) + 1)
  return line_indent trim(out) "\n"
}

function finalize_copyright_set(\
  out)
{
 out = sort_copyright_lines(accum list_missing_principals())
 next_line = ""
 return out ret_inc_stage()
}

function substitute_holder_notes(holder_notes, \
  trimmed)
{
  trimmed = trim(holder_notes)
  if (trimmed in aliases)
    {
      print "changing notes >" trimmed "< to >" aliases[trimmed] "<"
      holder_notes = " " aliases[trimmed]
    }
  else if (holder_notes != "")
    print "preserving notes >" holder_notes "<"
  return holder_notes
}

function assert_holder_is_new(holder)
{
  if (holder in holder_set)
    {
      print current_file ": Copyright holder " holder " is already listed" \
        >> "/dev/stderr"
      clean_exit(1)
    }
  holder_set[holder] = 1
}

function start_copyright_line()
{
  analyze_indent()
  next_line = drop_copy_mark($0)
}

function accum_copyright_line(\
  ind, addendum)
{
  print "appending to copyright notice: " $0
  ind = substr($0, 1, length(line_indent))
  addendum = substr($0, length(line_indent) + 1)
  sub(/^  /, "", addendum)
  if (ind != line_indent)
    print "Note: subsequent indent >" ind "< vs >" line_indent "<"
  next_line = next_line " " addendum
}

function add_copyright_notice(\
  holder, holder_notes, yr, arr)
{
  if (next_line == "")
    return
  yr = extract_years(next_line)
  holder = extract_holder(next_line, yr)
  holder_notes = ""
  if (match(holder, /( ((<[^<]*>|[(][^()]*[)])([[:space:]]+|$))+)$/, arr))
    {
      holder_notes = arr[1]
      holder = substr(holder, 1, RSTART - 1)
    }
  holder = trim(holder)
  assert_holder_is_new(holder)
  holder_notes = substitute_holder_notes(holder_notes)
  yr = get_years(holder)
  out = line_indent copy_mark yr " " holder holder_notes
  if (out != line_indent copy_mark next_line)
    print "  change to " substr(out, length(line_indent) + 1)
  accum = accum out RT
  next_line = ""
}

function compile_copyright_line()
{
  if (match_copyright())
    {
      copyright_rstart = RSTART;
      add_copyright_notice()
      start_copyright_line()
      return 0
    }
  if (substr($0, length(line_indent) + 1) ~ /^[[:space:]]*$/)
    {
      add_copyright_notice()
      return 1
    }
  accum_copyright_line()
  return 0
}

function normalize_copyright(\
  line, holder, holder_notes, yr, arr)
{
  if (compile_copyright_line())
    return finalize_copyright_set()
  return ""
}
function wait_for_copyright()
{
  if (!match_copyright())
    return $0 RT
  line_indent = substr($0, 1, RSTART - 1)
  return ret_inc_stage()
}
function wait_for_license()
{
  if (!match($0, notice_start "[[:space:]]*$"))
    return $0 RT
  line_indent = substr($0, 1, RSTART - 1)
  line_cnt = 0
  return ret_inc_stage()
}
function skip_license(\
  line_tail, out)
{
  if (line_cnt++ < notice_line_cnt)
    return ""
  if (!match($0, notice_end))
    return ""
  line_tail = substr($0, RSTART + RLENGTH)
  print "tail >" line_tail "<"
  out = notice
  if (line_indent != "")
    gsub(/\n/, "\n" line_indent, out)
  sub(/^[[:space:]]*\n/, "", out)
  gsub(/[ \t]*\n/, "\n", out)
  out = line_indent trim(out) line_tail RT
  inc_stage()
  return out
}
function stage_0()
{
  return wait_for_copyright()
}
function stage_1()
{
  return normalize_copyright()
}
function stage_2()
{
  return wait_for_license()
}
function stage_3()
{
  return skip_license()
}
function convert_line(\
  out)
{
  if (stage > max_stage)
    return $0 RT
  out = current_stage()
  if (dry_run)
    out = $0 RT
  return out
}
function process_file(f, \
  p)
{
  current_file = f
  print "processing " f
  p = PROCINFO["sorted_in"]
  PROCINFO["sorted_in"] = p_in
  system("rm -f \"" tmp_file "\"")
  stage = 0
  delete holder_set
  while ((getline < f) > 0)
    printf "%s", convert_line() > tmp_file
  close(tmp_file)
  system("cat \"" tmp_file "\" > \"" f "\"")
  PROCINFO["sorted_in"] = p
  if (stage < max_stage)
    {
      print current_file ": some parts are missing; current stage is " stage \
        >> "/dev/stderr"
      clean_exit(1)
    }
}
END {
  max_stage = 3
  if (dont_touch_license)
    max_stage = 1
  p_in = PROCINFO["sorted_in"]
  PROCINFO["sorted_in"] = "@ind_str_asc"
  if (dry_run)
    print "Note: dry run"
  for (i in files)
    print "file:\t" i
  for (i in years)
    print "years:\t" i "\t" years[i]
  for (i in aliases)
    print "alias:\t" i "\t" aliases[i]
  for (i = 1; i in principals; i++)
    print "principal:\t" principals[i]
  for (i in files)
    process_file(i)
  clean_exit(0)
}
