# Ad-hoc script to fix and normalize copyright notices.
# Public domain; originally written in 2023 by Ineiev.
# Filter copyright notices.

# This script is invoked from ./notices.

function output_accum(file_name)
{
  if (accum ~ /[^[:space:]]/)
    print file_name ": " accum
  accum = ""
}

!in_seq && /Copyright.*(19|20)[[:digit:]]{2}\>/ { in_seq = 1; accum = "" }
in_seq {
  gsub(/^[[:space:]]*[*][[:space:]]*/, "")
  gsub(/^[;#] */, "")
  gsub(/^-- */, "")
  gsub(/^\/\/ */, "")
  sub(/\(c\)/, "(C)")
  if ($0 ~ /^[[:space:]]*$/)
    {
      output_accum(file_name)
      exit
    }
  s = gensub(/([[:digit:]][[:digit:][:space:],-]*)[(]C[)]/, "(C) \\1", "1")
  s = gensub(/Copyright ([[:digit:]])/, "Copyright (C) \\1", "1", s)
  if ($0 !~ /\(C\)/)
    sub(/Copyright/, "& (C)")
  gsub(/  */, " ", s)
  if (s ~ /Copyright/)
    output_accum(file_name)
  accum = accum s
}
