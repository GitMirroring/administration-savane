<?php
# Markup functions.
#
# Copyright (C) 2005-2006 Tobias Toedter <t.toedter--gmx.net>
# Copyright (C) 2005-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2017, 2018, 2019, 2020, 2021, 2022, 2023 Ineiev
#
# This file is part of Savane.
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

require_once (dirname (__FILE__) . '/utils.php');
# Make sure the string has no newlines after translation.
function markup_i18n ($str)
{
  return str_replace ("\n", ' ', $str);
}

# Return current markup language documentation in Full Markup format.
function markup_get_reminder ()
{
  return
    "== " . _("Tag Scope") . " ==\n\n"
    . markup_i18n (
      _("Every markup element except 'verbatim' and 'nomarkup' blocks should\n"
        . "fit in a single line.  For example,\n"
        . "this text isn't converted in two lines of italics:"))
    . "\n\n+verbatim+\n"
    . _("_First line\n"
        . "Second line_")
    . "\n-verbatim-\n\n"
    . "== " . _("Basic Markup") . " ==\n\n"
    . _("Basic Markup tags are available almost everywhere.") . ' '
    . markup_i18n (
      _("Multiple subsequent spaces and newlines are collapsed in Basic "
        . "markup\ninto single spaces. In Rich and Full markup, they are "
        . "preserved."))
    . "\n\n"
    . _("*bold* markup is:")
    . "\n+verbatim+\n*"
    . _("bold")
    . "*\n-verbatim-\n\n"
    . _("_italic_ markup is:")
    . "\n+verbatim+\n_"
    . _("italic")
    . "_\n-verbatim-\n\n"
    . _("URLs are transformed to links, additionally you can give them a "
        . "title:")
    . "\n\n"
    . "\n+verbatim+\n"
    . "www.gnu.org\n"
    . "http://www.fsf.org\n"
    . "[http://url " . _('Title') . ']'
    . "\n-verbatim-\n\n"
    . markup_i18n (
    _("Also, these texts are made links to comments\n"
      . "(within the same item), tracker items and files:"))
    . "\n\n+verbatim+\n"
    . "comment #51\n"
    . "bug #1419857\n"
    . "task #289\n"
    . "sr #4913 support #4913\n"
    . "patch #119\n"
    . "file #83521\n"
    . "-verbatim-\n\n"
    . markup_i18n (
      _("Links to files whose names end in '.png', '.jpg', '.jpeg' "
       . "(case-insensitive) are converted to HTML images, the surrounding "
       . "parentheses and commas (if any) are removed:"))
    . "\n\n+verbatim+\n"
    . "(file #47102)\n"
    . "-verbatim-\n\n"
    . _("You can add the 'alt' attribute within the parentheses:")
    . "\n\n+verbatim+\n"
    . "(file #47102 " . _('Flying GNU') . ")\n"
    . "-verbatim-\n\n"
    . "== " . _("Rich Markup") . " ==\n\n"
    . _('Rich Markup tags are available in comments.') . "\n\n"
    . _('Unnumbered list markup is:') . "\n\n"
    . "+verbatim+\n"
    . _("* item 1\n"
        . "* item 2\n"
        . "** item 2 subitem 1\n"
        . "** item 2 subitem 2\n"
        . "* item 3")
    . "\n-verbatim-\n\n"
    . _('Numbered list markup is:') . "\n\n"
    . "+verbatim+\n"
    . _("0 item 1\n"
        . "00 item 1 subitem 1\n"
        . "0 item 2")
    . "\n-verbatim-\n\n"
    . _('Horizontal ruler markup is:') . "\n\n"
    . "+verbatim+\n"
    . "----\n"
    . "-verbatim-\n\n"
    . _('Verbatim markup (useful for code bits) is:') . "\n\n"
    . "+verbatim+\n+verbatim+\n"
    . _("seconds = 3600 * days * 24;\n"
        . "_printf (_(\"Enter something:\"));")
    . "\n-#fnord;verbatim-\n-verbatim-\n\n"
    . markup_i18n (
      _("The starting and ending verbatim marks take whole lines; the rest\n"
        . "text that may be on the same lines is ignored.")) . "\n\n"
    . markup_i18n (
      _('The other tag that disables the markup is:')) . "\n\n"
    . "+verbatim+\n+nomarkup+...-nomarkup-\n-verbatim-\n\n"
    . markup_i18n (
      _("Unlike the verbatim tag, it produces no text block and can apply\n"
        . "to arbitrary parts of texts.")) . "\n\n"
    . _('Lines starting with ">" are highlighted as quotes:')
    . "\n+verbatim+\n> " . _('Quoted line.') . "\n-verbatim-\n\n"
    . "== " . _('Full Markup (Heading Tags)') . " ==\n\n"
    . markup_i18n (
      _("Heading tags are available in rare places like item original\n"
        . "submissions, news items, project description and user's resume."))
    . ' ' . _('First level heading markup is:')
    . "\n\n+verbatim+\n= " . _('Title') . " =\n-verbatim-\n\n"
    . _('Second level heading markup is:')
    . "\n\n+verbatim+\n== " . _('Subtitle') . " ==\n-verbatim-\n\n"
    . _('Third level heading markup is:')
    . "\n\n+verbatim+\n=== " . _('Subsubtitle') . " ===\n-verbatim-\n\n"
    . _('Fourth level heading markup is:')
    . "\n\n+verbatim+\n==== " . _('Subsubsubtitle') . " ====\n-verbatim-\n\n";
}

# Functions to allow users to format the text in a secure way:
#    markup_basic() for very light formatting;
#    markup_rich() for formatting excepting headers;
#    markup_full() for full formatting, including headers.

# Tell the user what is the level of markup available in a uniform way.
# Takes as argument the level, being full / rich / basic / none.
# To avoid making page looking strange, we will put that only on textarea
# where it is supposed to be the most useful.
function markup_info ($level)
{
  $info = [
    'basic' => [ _("Basic Markup"),
      _("Only basic text tags are available in this input field.")],
    'rich' => [ _("Rich Markup"),
      _("Rich and basic text tags are available in this input field.")],
    'full' => [ _("Full Markup"),
      _("Every tags are available in this input field.")],
    'none' => [ _("No Markup"),
      _("No tags are available in this input field.")]
  ];
  if (empty ($info[$level]))
    $level = 'none';
  $link_head = '<a target="_blank" href="/markup-test.php">';
  $link_tail = '</a>';
  if ($level == 'none')
    $link_head = $link_tail = '';
  $string = $info[$level][0];
  $text = $info[$level][1];

  $img = html_image ('misc/edit.png', ['class' => 'icon']);
  return '<span class="smaller">('
    . utils_help ("$link_head$img$string$link_tail", $text) . ')</span>';
}

# Convert special markup characters in the input text to HTML.
#
# The following syntax is supported:
# * *word* -> <b>word</b>
# * _word_ -> <i>word</i>
# * [http://gna.org/] -> <a href="http://gna.org/">http://gna.org/</a>
# * [http://gna.org/ text] -> <a href="http://gna.org/">text</a>
# * (bug|task|...) #1234 -> link to corresponding page
# * +nomarkup+text-nomarkup- -> text (with unconverted markup)
function markup_basic ($text)
{
  $lines = explode ("\n", $text);
  $result = [];

  foreach ($lines as $line)
    $result[] = markup_inline ($line);
  return join ("\n", $result);
}

# Convert special markup characters in the input text to HTML.
#
# This function does the same markup as markup_basic(), plus
# it supports the following:
# * lists (<ul> and <ol>)
# * horizontal rulers
# * verbatim blocks
function markup_rich ($text)
{
  return markup_full ($text, false);
}

# Transform spaces so that they are hopefully preserved in HTML.
function markup_preserve_spaces ($buf)
{
  $buf = preg_replace ('/  *(\n|$)/', '$1', $buf);
  $buf = str_replace (' ', '&nbsp;', $buf);
  $buf = preg_replace ('/(([&]nbsp;)*)[&]nbsp;/', '$1 ', $buf);
  $buf = preg_replace ('/(\n) /', '$1&nbsp;', $buf);
  $buf = preg_replace ('/^((<p>)?) /', '$1&nbsp;', $buf);
  return $buf;
}

function markup_normalize_spaces ($buf)
{
  # Unify line breaks.
  $buf = str_replace ("\r\n", "\n", $buf);
  $buf = str_replace ("\n\r", "\n", $buf);
  $buf = str_replace ("\r", "\n", $buf);
  # Hopefully preserve spaces in HTML allowing line breaking.
  $buf = str_replace ("\t", "        ", $buf);
  # The leading space will be collapsed in markup_preserve_spaces.
  return ' ' . markup_preserve_spaces ($buf);
}

# Compile HTML text for a verbatim block, append it to $result;
# the function is used further in markup_full ().
function markup_build_verbatim (&$verbatim_buffer, &$context_stack, &$result)
{
  $line = join ("\n", $context_stack);
  array_shift ($context_stack);

  $verbatim_buffer = markup_normalize_spaces ($verbatim_buffer);
  # Preserve line breaks.
  $verbatim_buffer = nl2br ($verbatim_buffer);
  # Take into account unclosed paragraphs of surrounding text.
  $closure = $aperture = $prev_line = "";
  if (count ($result) > 0)
    $prev_line = $result[count($result) - 1];
  $len = strlen ($prev_line);
  if ($len >= 6 && substr ($prev_line, $len - 6) === '<br />')
    {
      $closure = "</p>\n";
      $aperture = "<p>";
    }
  $result [] =
    "$closure<blockquote class='verbatim'>"
    . "<p>$verbatim_buffer</p></blockquote>\n$aperture";
  $verbatim_buffer = '';
}

function markup_match_nomarkup_item ($match)
{
  if (empty ($match['type'][0]))
    $ret = ['type' => 'verbatim', 'mark' => $match['mv'][0]];
  else
    $ret = ['type' => $match['type'][0], 'mark' => $match['mn'][0]];
  $ret['off'] = $match[0][1];
  $ret['len'] = strlen ($match[0][0]);
  return $ret;
}

function markup_split_nomarkups ($text)
{
  $regexp = '/(?<mn>[+-])(?<type>nomarkup)\1'
    . '|[^\n]*(?<mv>[+-])verbatim\3[^\n]*(\n|$)/';
  preg_match_all ($regexp, $text, $matches,
    PREG_OFFSET_CAPTURE | PREG_SET_ORDER
  );
  return $matches;
}

# Don't allow nomarkup parts that fit in a single line;
# they are handled later in markup_inline_nomarkup ().
# If they weren't, they would break inline markup, e.g.
# nomarkup tags couldn't be used in list items.
function markup_pop_invalid_nomarkup ($item, &$ret, &$off)
{
  if (false !== strpos ($item[1], "\n"))
    return false;
  list ($t, $str, $off) = array_pop ($ret);
  return true;
}

function markup_add_closing_nomarkup (&$ret, $text, $off, $type)
{
  $str = substr ($text, $off);
  if (false === strpos ($str, "\n"))
    $type = '';
  $ret[] = [$type, $str];
}

function markup_nomarkup_text ($text)
{
  $matches = markup_split_nomarkups ($text);
  $off = 0; $type = ''; $ret = [];
  foreach ($matches as $match)
    {
      $m = markup_match_nomarkup_item ($match);
      $item = [$type, substr ($text, $off, $m['off'] - $off), $off];
      if ($m['mark'] === '-' && $m['type'] === $type)
        {
          $type = '';
          if (markup_pop_invalid_nomarkup ($item, $ret, $off))
            continue;
        }
      elseif ($m['mark'] === '+' && $type === '')
        $type = $m['type'];
      else
        continue;
      $ret[] = $item;
      $off = $m['off'] + $m['len'];
    }
  markup_add_closing_nomarkup ($ret, $text, $off, $type);
  return $ret;
}

function markup_mark_nomarkup ($chunks)
{
  $ret = [];
  foreach ($chunks as $ch)
    {
      if (is_array ($ch))
        {
          $ret[] = $ch;
          continue;
        }
      foreach (markup_nomarkup_text ($ch) as $r)
        if (empty ($r[0]))
          $ret[] = $r[1];
        else
          $ret[] = $r;
    }
  return $ret;
}

function markup_heading_text ($text, &$ret)
{
  $lines = explode ("\n", $text);
  $accum = [];
  foreach ($lines as $l)
    {
      if (!preg_match ('/(\n|^)(?<rank>={1,4}) (?<data>.+) \2\s*$/', $l, $m))
        {
          $accum[] = $l;
          continue;
        }
      if (!empty ($accum))
        $ret[] = join ("\n", $accum) . "\n";
      $ret[] = ['heading', [strlen ($m['rank']) + 1, $m['data']]];
      $accum = [];
    }
  if (!empty ($accum))
    $ret[] = join ("\n", $accum);
}

function markup_mark_headings ($chunks)
{
  $ret = [];
  foreach ($chunks as $ch)
    {
      if (is_array ($ch))
        {
          $ret[] = $ch;
          continue;
        }
      markup_heading_text ($ch, $ret);
    }
  return $ret;
}

function markup_hr ($line)
{
  if (preg_match ('/^----\s*$/', $line))
    return ["<hr />\n", true];
  return [$line, false];
}

function markup_markup_line ($line)
{
  list ($line, $return) = markup_hr ($line);
  if ($return)
    return $line;
  return markup_inline (markup_preserve_spaces ($line));
}

function markup_verbatim_chunk ($ch)
{
  $ch = markup_normalize_spaces ($ch);
  # Undocumented feature to allow -verbatim- string in verbatim
  # environment, like -#fnord;verbatim-.
  $ch = str_replace ('#fnord;', '', $ch);
  $ch = nl2br ($ch);
  return "<blockquote class='verbatim'><p>$ch</p></blockquote>\n" ;
}

function markup_nomarkup_chunk ($ch)
{
  return "<p class='nomarkup'>" . nl2br ($ch) . "</p>\n";
}

function markup_li_chunk ($ch)
{
  $ch = markup_markup_line ($ch);
  return "<li>$ch\n";
}

function markup_oli_chunk ($ch)
{
  return markup_li_chunk ($ch);
}

function markup_uli_chunk ($ch)
{
  return markup_li_chunk ($ch);
}

function markup_heading_chunk ($ch)
{
  return "<h$ch[0]>$ch[1]</h$ch[0]>\n";
}

function markup_quote_chunk ($ch)
{
  $ch = markup_inline_chunk ($ch);
  return "<blockquote class='quote'><p>$ch</p></blockquote>\n";
}

function markup_inline_chunk ($ch)
{
  $ret = '';
  foreach (explode ("\n", preg_replace ('/\n\s*\n/', "\n", $ch)) as $l)
    $ret .= markup_markup_line ($l) . "<br />\n";
  return $ret;
}

# Translate an element of markup to HTML.  $ch is either a simple string
# to be converted using "inline" markup or a list where the first member
# is chunk type, and the second (if present) is chunk data.
function markup_build_chunk ($ch, &$state)
{
  if (!is_array ($ch))
    return markup_inline_chunk ($ch);
  $string_tags = [
    'o-start' => "<ol>\n", 'o-end' => "</ol>\n", 'oli-end' => "</li>\n",
    'u-start' => "<ul>\n", 'u-end' => "</ul>\n", 'uli-end' => "</li>\n",
    'p-start' => "<p>", 'p-end' => "</p>\n"
  ];
  $func_tags = ['verbatim', 'nomarkup', 'oli', 'uli', 'heading', 'quote'];
  $f = $ch[0];
  if (!empty ($string_tags[$f]))
    return $string_tags[$f];
  if (!in_array ($f, $func_tags))
    util_die ("unknown chunk type $f");
  $f = "markup_{$f}_chunk";
  $a = null;
  if (!empty ($ch[1]))
    $a = $ch[1];
  return $f ($a);
}

function markup_u_start_ascii_chunk ($ch, &$state)
{
  $state['length']++;
  return "";
}

function markup_li_ascii_indent (&$state)
{
  return str_repeat ("\t", $state['length'] - 1);
}

function markup_uli_ascii_chunk ($ch, &$state)
{
  $indent = markup_li_ascii_indent ($state);
  return "$indent* $ch\n";
}

function markup_uli_end_ascii_chunk ($ch, &$state)
{
  return "";
}

function markup_u_end_ascii_chunk ($ch, &$state)
{
  $n = $state['length'];
  if ($n >= 1)
    $state['length'] = $n - 1;
  return '';
}

function markup_o_start_ascii_chunk ($ch, &$state)
{
  $state['stack'][] = 1;
  return markup_u_start_ascii_chunk ($ch, $state);
}

function markup_oli_ascii_chunk ($ch, &$state)
{
  $indent = markup_li_ascii_indent ($state);
  $n = array_pop ($state['stack']);
  $state['stack'][] = $n + 1;
  return "$indent$n. $ch\n";
}

function markup_oli_end_ascii_chunk ($ch, &$state)
{
  return "";
}

function markup_o_end_ascii_chunk ($ch, &$state)
{
  array_pop ($state['stack']);
  return markup_u_end_ascii_chunk ($ch, $state);
}

function markup_inline_ascii_chunk ($text)
{
  $lines = explode ("\n", $text);
  $ret = '';
  foreach ($lines as $line)
    {
      foreach (markup_inline_nomarkup ($line) as $ch)
        $ret .= $ch[0];
      $ret .= "\n";
    }
  return $ret;
}

# The version of markup_build_chunk () for ASCII output.  Most things
# are very simple, but ordered lists need extra computations.
function markup_build_ascii ($ch, &$state)
{
  if ($state === null)
    $state = ['stack' => [], 'length' => 0];
  if (!is_array ($ch))
    return markup_inline_ascii_chunk ($ch);
  $pass_tags = ['verbatim', 'nomarkup', 'quote'];
  if (in_array ($ch[0], $pass_tags))
    return "$ch[1]\n";
  $func_tags = [];
  foreach (['o', 'u'] as $lt)
    foreach (['-start', 'li', 'li-end', '-end'] as $stage)
      $func_tags[] = "$lt$stage";
  if (!in_array ($ch[0], $func_tags))
    return '';
  $a = null;
  if (!empty ($ch[1]))
    $a = $ch[1];
  $f = str_replace ('-', '_', $ch[0]);
  $f = "markup_{$f}_ascii_chunk";
  return $f ($a, $state);
}

function markup_chunk_type ($ch)
{
  if (is_array ($ch))
    return $ch[0];
  return 'plain';
}

function markup_p_mark ($have_p, $prev, $type)
{
  $common_tags = ['heading', 'nomarkup', 'verbatim', 'quote'];
  if ($have_p)
    {
      $mark = 'p-end';
      $tags = array_merge ($common_tags, ['o-start', 'u-start']);
      if ($prev != 'plain' || !in_array ($type, $tags))
        return null;
    }
  else
    {
      $mark = 'p-start';
      $tags = array_merge ($common_tags, ['o-end', 'u-end', 'begin']);
      if ($type != 'plain' || !in_array ($prev, $tags))
        return null;
    }
  return $mark;
}

# Insert p-start and p-end tags to enclose 'plain' chunks.
function markup_insert_p_tags ($chunks)
{
  $have_p = false; $prev = 'begin'; $ret = [];
  foreach ($chunks as $ch)
    {
      $type = markup_chunk_type ($ch);
      $mark = markup_p_mark ($have_p, $prev, $type);
      if ($mark !== null)
        {
          $ret[] = [$mark];
          $have_p = !$have_p;
        }
      $prev = $type;
      $ret[] = $ch;
    }
  if ($have_p)
    $ret[] = ['p-end'];
  return $ret;
}

# Pop elements from the stack to close as many nested lists as necessary
# to reduce $current to $next; return the list of resulting tockens;
# update both $stack and $current.
function markup_close_lists (&$stack, &$current, $next = '')
{
  $deepest_level = true;
  $ret = [];
  for ($cnt = count ($stack) - strlen ($next);
    $cnt > 0 && null !== ($l = array_shift ($stack)); $cnt--
  )
    {
      if ($deepest_level)
        $ret[] = [$l[0] . 'li-end'];
      $deepest_level = false;
      $ret[] = [$l[0] . '-end'];
      if (!empty ($stack))
        $ret[] = [$l[0] . 'li-end'];
    }
  $current = $next;
  return $ret;
}

# Compare previous to next list markup; return "start", "end", "li" or false
# depending on the next stage of the markup.
function markup_match_lists ($prev, $next, &$stack)
{
  if ($prev === $next)
    return 'li';
  $p = strlen ($prev); $n = strlen ($next);
  $m = $p > $n? $n: $p;
  if ($m < $n - 1)
    return false; # Only allow one level increase at once.
  if (substr ($prev, 0, $m) !== substr ($next, 0, $m))
    return false; # List nesting doesn't match (like '0*0' and '*0*').
  if ($p > $n)
    return 'end';
  if ($p + 1 == $n)
    {
      $c = substr ($next, $p, 1);
      array_unshift ($stack, $c);
      return "start";
    }
  return false;
}

function markup_parse_list ($ch, &$stack, &$prev)
{
  if (!preg_match ('/^\s?([*0]+) (.+)$/', $ch, $matches))
    return array_merge (markup_close_lists ($stack, $prev), [$ch]);
  $next = str_replace (['0', '*'], ['o', 'u'], $matches[1]);
  $token = markup_match_lists ($prev, $next, $stack);
  if ($token === false)
    return array_merge (markup_close_lists ($stack, $prev), [$ch]);
  $c = substr ($next, -1, 1);
  $item = ["${c}li", $matches[2]];
  $ret = [$item];
  if ($token == 'end')
    return array_merge (markup_close_lists ($stack, $prev, $next), $ret);
  $pr = $prev;
  $prev = $next;
  if ($token == 'start')
    array_unshift ($ret, ["$c-start"]);
  elseif ($pr == $next)
    array_unshift ($ret, ["${c}li-end"]);
  return $ret;
}

function markup_mark_lists ($chunks)
{
  $ret = $stack = []; $prev = '';
  foreach ($chunks as $ch)
    {
      if (is_array ($ch))
        {
          $ret = array_merge ($ret, markup_close_lists ($stack, $prev));
          $ret[] = $ch;
          continue;
        }
      $lines = explode ("\n", $ch);
      foreach ($lines as $l)
        $ret = array_merge ($ret, markup_parse_list ($l, $stack, $prev));
    }
  return array_merge ($ret, markup_close_lists ($stack, $prev));
}

function markup_add_quoted_item ($mark, &$accum, &$ret)
{
  $item = join ("\n", $accum);
  if ($mark)
    $item = ['quote', $item];
  $ret[] = $item;
}

function markup_quoted_text ($text, &$ret)
{
  $lines = explode ("\n", $text);
  $accum = []; $mark = false;
  foreach ($lines as $l)
    {
      $next_mark = (substr ($l, 0, 4) == '&gt;');
      if ($next_mark != $mark)
        {
          markup_add_quoted_item ($mark, $accum, $ret);
          $accum = [];
          $mark = $next_mark;
        }
      $accum[] = $l;
    }
  markup_add_quoted_item ($mark, $accum, $ret);
}

function markup_mark_quoted ($chunks)
{
  $ret = [];
  foreach ($chunks as $ch)
    if (is_array ($ch))
      $ret[] = $ch;
    else
      markup_quoted_text ($ch, $ret);
  return $ret;
}

# Parse markup and post-process it with the $build_chunk function.
function markup_run_markup ($text, $allow_headings, $build_chunk)
{
  $marked = markup_mark_nomarkup ([$text]);
  $marked = markup_mark_quoted ($marked);
  $marked = markup_mark_lists ($marked);
  if ($allow_headings)
    $marked = markup_mark_headings ($marked);
  $marked = markup_insert_p_tags ($marked);
  $out = ''; $state = null;
  foreach ($marked as $ch)
    $out .= $build_chunk ($ch, $state);
  return $out;
}

# Convert special markup characters in the input text to real HTML.
#
# This function does exactly the same markup as markup_rich()
# when !$allow_headings, plus it converts headings to <h2> ... <h5>
# when $allow_headings.
function markup_full ($text, $allow_headings = true)
{
  return markup_run_markup ($text, $allow_headings, 'markup_build_chunk');
}

function markup_substitute_highlighted ($line, $selector, $tag)
{
  return preg_replace (
    # Allow for the pattern to start at the beginning of a line.
    # if it doesn't start there, the character before the slash
    # must be either whitespace or the closing brace '>', to
    # allow for nested html tags (e.g. <p>_markup_</p>).
    # Additionally, the opening brace may appear.
    '/(^|\s+|>|\()'
    . $selector
    # Match any character (non-greedy).
    . '(.+?)'
    # Match the ending underscore and either end of line or
    # a non-word character.
    . $selector . '(\W|$)/', "\\1<$tag>\\2</$tag>\\3", $line);
}

# *word* -> <b>word</b>
function markup_substitute_asterized ($line)
{
  return markup_substitute_highlighted ($line, '\*', 'b');
}

# _word_ -> <i>word</i>
function markup_substitute_underscored ($line)
{
  return markup_substitute_highlighted ($line, '_', 'i');
}

function markup_substitute_img_file_domain ($line)
{
  global $sys_file_domain;
  if ($GLOBALS['sys_default_domain'] == $sys_file_domain)
    return $line;
  $http = session_issecure ()? 'https': 'http';
  return preg_replace (
    '/<img src="\/file/', "<img src=\"$http://$sys_file_domain/file", $line
  );
}

# Return array [ file_id => filename ] for the given list of file_ids.
function markup_fetch_file_list ($file_ids)
{
  if (empty ($file_ids))
    return [];
  $in_ph = utils_in_placeholders ($file_ids);
  $result =  db_execute ("
    SELECT file_id, filename FROM trackers_file WHERE file_id $in_ph",
    $file_ids
  );
  $ret = [];
  while ($row = db_fetch_array ($result))
    $ret[$row['file_id']] = $row['filename'];
  return $ret;
}

function markup_expand_img ($line, $file_id, $file_name, $comment)
{
  if (!(preg_match ('/\.(jpe?g|png)$/', strtolower ($file_name))))
    return $line;
  $alt = $comment;
  if (substr ($alt, 0, 1) === ' ')
    $alt = substr ($alt, 1);
  if ($alt !== '')
    $alt = 'alt="' . utils_specialchars ($alt) . '" ';
  $file_name = utils_specialchars ($file_name);
  return preg_replace ("/\(?((files? ))#{$file_id}[^),]*((\)|, )?)/",
    "<img src=\"/file/$file_name?file_id=$file_id\" $alt/> ", $line
  );
}

# Replace references to image files with <img>.
function markup_expand_img_files ($line)
{
  preg_match_all ('/\(?((files? ))#(?P<file_id>\d+)'
    . '(?P<comment>[^),]*)((\)|, )?)/',
    $line, $matches
  );
  $file_list = markup_fetch_file_list ($matches['file_id']);
  foreach ($matches['file_id'] as $key => $file_id)
    {
      if (empty ($file_list[$file_id]))
        continue;
      $line = markup_expand_img ($line, $file_id,
        $file_list[$file_id], $matches['comment'][$key]
      );
    }
  return markup_substitute_img_file_domain ($line);
}

# Prepare usual links: prefix "www." with "$protocol_relative://"
# if it is preceded by [ or whitespace or at the beginning of line
# (don't want to prepend in cases like "//www.." or "ngwww...").
function markup_insert_prot_rel ($line, $protocol_relative)
{
  return preg_replace (
    '/(^|\s|\[)(www\.)/i', "\\1$protocol_relative://\\2", $line
  );
}

function markup_convert_standalone_URLs ($line, $protocols)
{
  # Prepare the markup for normal links, e.g. http://test.org, by
  # surrounding them with braces []
  # (& = begin of html entities, it means a end of string unless
  # it is &amp; which itself is the entity for &)
  $line = preg_replace (
    '/(^|[^;\[\/])((' . $protocols . '):\/\/(&amp;|[^\s&]+[a-z0-9\/^])+)/i',
    '$1[$2]', $line
  );
  # Remove spaces added in markup_prevent_nested_links ()
  # and process links with preceding ';'.
  $line = preg_replace ('/&#32;/', '', $line);
  $line = preg_replace (
    '/(;)(((' . $protocols . '):)?\/\/(&amp;|[^\s&]+[a-z0-9\/^])+)/i',
    '$1[$2]', $line
  );
  return $line;
}

function markup_encode_prot_rel ($line, $protocol_relative, $revert)
{
  $pr_esc = "p-&#83521;-r";
  if ($revert)
    {
      $line = str_replace ($protocol_relative . "://", "//", $line);
      return str_replace ($pr_esc, $protocol_relative, $line);
    }
  # Make sure $line doesn't contain $protocol_relative.
  $line = str_replace ($protocol_relative, $pr_esc, $line);
  # Reword "//" as artificial "protocol".
  return preg_replace ('#(^|\s|\[)//#', "\\1$protocol_relative://", $line);
}

function markup_convert_mail_links ($line, $protocols)
{
  # Replace the @ sign with an HTML entity, if it is used within
  # an URL (e.g. for pointers to mailing lists).  This way, the
  # @ sign doesn't get mangled in the email markup code
  # below.
  $line = preg_replace ("#(($protocols)://[^<>[:space:]]+)@#i",
    "$1&#64;", $line
  );

  # Do a markup for mail links, e.g. info@support.org (do not use utils_emails,
  # this does extensive database search on the string and replace addresses
  # in several fashion. Here we just want to make a link).  Make sure that
  # 'cvs -d:pserver:anonymous@cvs.sv.gnu.org:/...' is NOT replaced.
  $email = utils_email_basic ('\2');
  $line = preg_replace (
    "/(^|\s)([a-z0-9_+-.]+@([a-z0-9_+-]+\.)+[a-z]+)(\s|$)/i",
    "\\1$email\\4", $line
  );

  # Unreplace the @ sign.
  return preg_replace (
    "%(($protocols)://[^<>[:space:]]+)[&]#64;%i", "$1@", $line
  );
}

function markup_expand_tracker_links ($line, $regexp, $link)
{
  global $sys_home;
  # Allow only two white spaces between the string and the numeric id
  # to avoid having too time consuming regexp. People just have to pay
  # attention.

  # Handle named links like [bug #4913 text of the link].
  $line = preg_replace (
    "/(^|\s|\W)\[($regexp)\s{0,2}#([0-9]+)\s+(.+?)\]/i",
    "\\1<i><a href=\"$sys_home$link\\3\">\\4</a></i>", $line
  );

  # Now process "usual" links like bug #4913.
  return preg_replace ("/(^|\s|\W)($regexp)\s{0,2}#([0-9]+)/i",
    "\\1<i><a href=\"$sys_home$link\\3\">\\2&nbsp;#\\3</a></i>",
    $line);
}

function markup_expand_comment_links ($line)
{
  $line = preg_replace ("/(^|\s|\W)\[(comments?)\s{0,2}#([0-9]+)\s+(.+?)\]/i",
    '$1<i><a href="#comment$3">$4</a></i>', $line);
  return preg_replace ('/(comments?)\s{0,2}#([0-9]+)/i',
    '<i><a href="#comment$2">$1&nbsp;#$2</a></i>', $line);
}

# Modify link texts to disable interpreting them as nested links.
function markup_prevent_nested_links ($line, $protocols, $artifact_regex)
{
  return preg_replace_callback ('/(\[((' . $protocols . '|www\.)[^\s]+'
    . '|((' . $artifact_regex . ')\s{0,2}#[0-9]+))\s+)(.*?)\]/',
    function ($matches)
      {
        # Replace '#' in link texts with HTML references;
        # if we don't, we may get links like
        # [bug #3 bug #1] ->
        # <i><a href="/bugs/?3"><i><a href="/bugs/?1">bug #1</a></i></a></i>
        $tail = preg_replace ('/(^|[^&])#/', '$1&#35;', $matches[6]);
        # Add '&#32;' before each word to disable interpreting it as
        # a link in texts like
        # [https://www.gnu.org/home.html home page for www.gnu.org]
        $tail = preg_replace ('/(^|\s)([^\s])/', '$1&#32;$2', $tail);
        return $matches[1] . "$tail]";
      },
     $line
  );
}

# Expand named hyperlinks, e.g.
# [http://gna.org/ Text] -> <a href="http://gna.org/">Text</a>
function markup_expand_named_links ($line, $protocols)
{
  return preg_replace (
    # Find the opening brace '['
    '/\['
    # followed by the protocol
    . '(((' . $protocols . '):)?\/\/'
    # match any character except whitespace or the closing
    # brace ']' for the actual link
    .'[^\s\]]+)'
    # followed by at least one whitespace
    .'\s+'
    # followed by any character (non-greedy) and the
    # next closing brace ']'.
    .'(.+?)\]/', '<a href="$1">$4</a>', $line);
}

# Expand unnamed hyperlinks, e.g.
# [http://gna.org/] -> <a href="http://gna.org/">http://gna.org/</a>
# We make sure the string is not too long, otherwise we cut it.
function markup_expand_unnamed_links ($line, $protocols, $protocol_relative)
{
  return preg_replace_callback (
    # Find the opening brace '['
    '/\['
    # followed by the protocol;
    . '(((' . $protocols . '):)?\/\/'
    # match any character except whitespace (non-greedy) for
    # the actual link, followed by the closing brace ']'.
    . '([^\s]+?))\]/',
    function ($match_arr) use ($protocol_relative)
    {
      $url = $match_arr[1];
      $string = $url;
      if ($match_arr[3] == $protocol_relative)
        $string = $match_arr[4];
      return "<a href=\"$url\">$string</a>";
    },
    $line);
}

function markup_tracker_list ()
{
  global $group_id;

  $comingfrom = '';
  if ($group_id)
    $comingfrom = "&amp;comingfrom=$group_id";

  return [
    "bugs?" => "bugs/?",
    "support|sr" => "support/?",
    "tasks?" => "task/?",
    "recipes?|rcp" => "cookbook/?func=detailitem$comingfrom&amp;item_id=",
    "patch" => "patch/?",
    # In this case, we make the link pointing to support, it won't matter,
    # the download page is in every tracker and does not check if the tracker
    # is actually used.
    "files?" => "support/download.php?file_id=",
  ];
}

function markup_protocol_regex ()
{
  # Regexp of protocols supported in hyperlinks (should be protocols
  # web browsers are expected to support).
  $protocols = "https?|s?ftp|file|afs|nfs";

  # Artificial protocol for protocol-relative links.
  $protocol_relative = "p-r";
  $protocols .= "|$protocol_relative";

  return [$protocols, $protocol_relative];
}

function markup_expand_links ($line)
{
  $trackers = markup_tracker_list ();
  $artifact_regex = join ('|', array_keys ($trackers)) . '|comments?';
  list ($protocols, $protocol_relative) = markup_protocol_regex ();

  $line = markup_encode_prot_rel ($line, $protocol_relative, false);

  $line = markup_prevent_nested_links ($line, $protocols, $artifact_regex);
  $line = markup_insert_prot_rel ($line, $protocol_relative);
  $line = markup_convert_standalone_URLs ($line, $protocols);
  $line = markup_convert_mail_links ($line, $protocols);

  foreach ($trackers as $regexp => $link)
    $line = markup_expand_tracker_links ($line, $regexp, $link);

  $line = markup_expand_comment_links ($line);
  $line = markup_expand_named_links ($line, $protocols);
  $line = markup_expand_unnamed_links ($line, $protocols, $protocol_relative);

  return markup_encode_prot_rel ($line, $protocol_relative, true);
}

# Process nomarkup parts that fit in a single line; nomarkup blocks
# that include a newline are handled earlier in markup_mark_nomarkup ().
function markup_inline_nomarkup ($line)
{
  $tag = 'nomarkup';
  $chunks = preg_split ("/(([+-])$tag\\2)/", $line, -1,
    PREG_SPLIT_DELIM_CAPTURE
  );
  $ret = []; $mark = false; $chunk_is_mark = false;
  foreach ($chunks as $ch)
    {
      if ($chunk_is_mark)
        {
          $chunk_is_mark = false;
          $mark = ($ch === '+');
          continue;
        }
      if (in_array ($ch, ["+$tag+", "-$tag-"]))
        $chunk_is_mark = true;
      else
        $ret[] = [$ch, $mark];
    }
  return $ret;
}

function markup_inline_string ($line)
{
  if (strlen ($line) == 0)
    return "";

  $line = markup_expand_img_files ($line);
  $line = markup_expand_links ($line);
  $line = markup_substitute_asterized ($line);
  $line = markup_substitute_underscored ($line);
  return $line;
}

# Internal function for converting inline tags and links.
function markup_inline ($line)
{
  $chunks = markup_inline_nomarkup ($line);
  $ret = '';
  foreach ($chunks as $ch)
    if ($ch[1])
      $ret .= "<span class='nomarkup'>$ch[0]</span>";
    else
      $ret .= markup_inline_string ($ch[0]);
  return $ret;
}

# Implement applicable parts of tracker comment in ASCII, which currently
# amounts to enumerations in ordered lists, Savannah sr #110621.
function markup_ascii ($text)
{
  $text = markup_run_markup ($text, false, 'markup_build_ascii');
  return utils_specialchars_decode ($text, ENT_QUOTES);
}
?>
