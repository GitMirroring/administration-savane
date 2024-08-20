<?php
# Database access wrappers, with quoting/escaping.
#
# Copyright (C) 1999, 2000 The SourceForge Crew
# Copyright (C) 2004, 2005 Elfyn McBratney <elfyn--emcb.co.uk>
# Copyright (C) 2000-2006 John Lim (ADOdb)
# Copyright (C) 2000-2006 Mathieu Roy <yeupou--gnu.org>
# Copyright (C) 2007 Cliss XXI (GCourrier)
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

define ('DB_AUTOQUERY_INSERT', 1);
define ('DB_AUTOQUERY_UPDATE', 2);

require_once (dirname (__FILE__) . '/utils.php');

# Test the presence of php-mysqli - you get a puzzling blank page
# when it's not installed.
function db_check_mysqli ()
{
  if (extension_loaded ('mysqli'))
    return;
  print "<p>Please install the mysqli extension for PHP:
    <code>aptitude install php-mysqli</code> (Debian-based)</p>
    <p>Check the <a href='{$GLOBALS['sys_url_topdir']}/testconfig.php'>
    configuration page</a> and the <a href='https://www.php.net'>PHP
    website</a> for more information.</p>
    <p>Once the extension is installed, restart Apache.</p>\n";
  exit;
}

function db_connect ()
{
  global $sys_dbhost, $sys_dbport, $sys_dbuser, $sys_dbpasswd, $sys_dbname;
  global $sys_dbsocket, $mysql_conn, $db_queries_filed;

  $mysql_conn = null; $db_queries_filed = [];
  db_check_mysqli ();

  mysqli_report (MYSQLI_REPORT_ERROR);
  $conn = mysqli_connect ($sys_dbhost, $sys_dbuser, $sys_dbpasswd, $sys_dbname,
    $sys_dbport, $sys_dbsocket);
  if (!$conn)
    return
      "<p>Failed to connect to database: " . mysqli_connect_error () . "</p>\n";
  mysqli_set_charset ($conn, 'utf8');
  $mysql_conn = $conn;
  return null;
}

function db_real_escape_string ($string)
{
  return mysqli_real_escape_string ($GLOBALS['mysql_conn'], $string);
}

# sprintf-like function to auto-escape SQL strings.
# db_query_escape("SELECT * FROM user WHERE user_name='%s'", $_GET['myuser']);
function db_query_escape ()
{
  $num_args = func_num_args ();
  if ($num_args < 1)
    util_die (_("db_query_escape: Missing parameter"));
  $args = func_get_args ();

  # Escape all params except the query itself.
  for ($i = 1; $i < $num_args; $i++)
    $args[$i] = db_real_escape_string ($args[$i]);

  $query = call_user_func_array ('sprintf', $args);
  return db_query ($query);
}

function db_arg_float ($v)
{
  global $decimal_separator;

  if (isset ($decimal_separator))
    $v = str_replace ($decimal_separator, '.', $v);
  return $v;
}

function db_argval ($v)
{
  # From Ron Baldwin <ron.baldwin#sourceprose.com>.
  # Only quote string types.
  $typ = gettype ($v);
  if ($typ == 'string')
    return "'" . db_real_escape_string ($v) . "'";
  if ($typ == 'boolean')
    return $v ? '1' : '0';
  if ($typ == 'integer')
    return $v;
  if ($v === null)
    return 'NULL';
  if ($typ == 'double') # Do we actually use floating point in queries?
    return db_arg_float ($v);
  util_die ("Don't use db_execute with type $typ.");
}

function db_query_report_string ($sql, $inputarr)
{
  return "Query is: <code>$sql</code>, \$inputarr is <code>"
    . print_r ($inputarr, 1) . "</code>";
}

function db_expand_sql ($sql, $inputarr)
{
  $sql_exploded = explode ('?', $sql);
  $i = 0;
  $sql_expanded = '';

  foreach ($inputarr as $v)
    {
      if (!array_key_exists ($i, $sql_exploded))
        util_die ("db_expand_sql: too long \$inputarr ("
          . count ($inputarr) . " vs. " . count ($sql_exploded)
          . " in \$sql_exploded).\n" . db_query_report_string ($sql, $inputarr)
        );
      $sql_expanded .= $sql_exploded[$i] . db_argval ($v);
      $i += 1;
    }

  if (isset ($sql_exploded[$i]))
    {
      $sql_expanded .= $sql_exploded[$i];
      if ($i + 1 == sizeof ($sql_exploded))
        return $sql_expanded;
    }
  return null;
}

# Substitute '?' with one of the values in the $inputarr array,
# properly escaped for inclusion in an SQL query.
function db_variable_binding ($sql, $inputarr = null)
{
  $sql_expanded = $sql;

  if (!$inputarr)
    return $sql_expanded;

  if (!is_array ($inputarr))
    util_die (
      "db_variable_binding: \$inputarr is not an array.\n"
      . db_query_report_string ($sql, $inputarr)
    );


  $ret = db_expand_sql ($sql, $inputarr);
  if ($ret !== null)
    return $ret;
  util_die (
    "db_variable_binding: input array does not match query: <pre>$sql"
    . "<br />" . print_r ($inputarr, true)
  );
}

# Like ADOConnection->AutoExecute, without ignoring non-existing
# fields (you'll get a nice mysql_error() instead) and with a modified
# argument list to allow variable binding in the where clause.
#
# This allows hopefully more reable lengthy INSERT and UPDATE queries.
#
# Check http://phplens.com/adodb/reference.functions.getupdatesql.html ,
# http://phplens.com/adodb/tutorial.generating.update.and.insert.sql.html
# and adodb.inc.php.
#
# E.g.
# $success = db_autoexecute ('user', array('realname' => $newvalue),
#   DB_AUTOQUERY_UPDATE, "user_id=?", array(user_getid())
# );
function db_autoexecute ($table, $dict, $mode = DB_AUTOQUERY_INSERT,
  $where_condition = false, $where_inputarr = null
)
{
  # Table name validation and quoting.
  $tables = preg_split ('/[\s,]+/', $table);
  $tables_string = [];
  foreach ($tables as $table)
    {
      if (!preg_match ('/^[a-zA-Z_][a-zA-Z0-9_]+$/', $table))
        util_die ("db_autoexecute: invalid table name: $table");
      $tables_string[] = "`$table`";
    }
  $tables_string = join (', ', $tables_string);

  switch ((string) $mode)
    {
    case 'INSERT':
    case '1':
      # Quote fields to avoid problem with reserved words (bug #8898@gna).
      # TODO: do connections with ANSI_QUOTES mode and use the standard
      # "'" field delimiter.
      $fields = [];
      foreach (array_keys ($dict) as $field)
        $fields[] = "`$field`";
      $fields = join (', ', $fields);
      $question_marks = utils_placeholders ($dict);
      return db_execute ("
        INSERT INTO $tables_string ($fields) VALUES ($question_marks)",
        array_values ($dict)
      );
      break;
    case 'UPDATE':
    case '2':
      $sql_fields = $values = [];
      foreach ($dict as $field => $value)
        {
          $sql_fields[] = "`$field` = ?";
          $values[] = $value;
        }
      $sql_fields = join (', ', $sql_fields);
      $values = array_merge ($values, $where_inputarr);
      $where_sql = $where_condition? "WHERE $where_condition": '';
      return db_execute (
        "UPDATE $tables_string SET $sql_fields $where_sql", $values
      );
      break;
    default:
    }
  util_die ("db_autoexecute: unknown mode=$mode");
}

# Like ADOConnection->Execute, with variables binding emulation for
# MySQL, but simpler (not 2D-array, namely). Example:
#
# db_execute("SELECT * FROM utilisateur WHERE name=?", array("Gogol d'Algol"));
#
# 'db_autoexecute' replaces '?' with the matching parameter, taking its
# type into account (int -> int, string -> quoted string, float ->
# canonical representation, etc.)
#
# Check http://phplens.com/adodb/reference.functions.execute.html and
# adodb.inc.php.
function db_execute ($sql, $inputarr = null, $multi_query = 0)
{
  if (empty ($GLOBALS['mysql_conn']))
    return null;
  $expanded_sql = db_variable_binding ($sql, $inputarr);
  return db_query ($expanded_sql, $multi_query);
}

function db_query_prevent_die ($disable = null)
{
  static $die_disabled = false;
  $prev = $die_disabled;
  if ($disable !== null)
    $die_disabled = !empty ($disable);
  return $prev;
}

function db_query_die ($qstring, $errors = null)
{
  $str = "db_query: SQL query error in [$qstring]";
  if (empty ($errors))
    $str .= ' <i>' . db_error () . '</i>';
  else
    foreach ($errors as $idx => $err)
      $str .= "<br />\n<b>query $idx:</b> <i>$err</i>";
  if (!db_query_prevent_die ())
    util_die ($str);
  return false;
}

function db_multi_query ($qstring)
{
  global $mysql_conn, $db_qhandle;
  mysqli_multi_query ($mysql_conn, $qstring);
  $db_qhandle = $errors = [];
  $i = 0;
  while (true)
    {
      $res = mysqli_store_result ($mysql_conn);
      if (!$res && mysqli_errno ($mysql_conn))
        $errors[$i] = db_error ();
      $db_qhandle[$i++] = $res;
      if (!mysqli_more_results ($mysql_conn))
        break;
      mysqli_next_result ($mysql_conn);
    }
  if (count ($errors))
    return db_query_die ($qstring, $errors);
  return $db_qhandle;
}

function db_format_backtrace ()
{
  return error_format_backtrace (false);
}

function db_query_ ($qstring, $multi_query)
{
  global $mysql_conn, $db_qhandle;
  if ($multi_query)
    return db_multi_query ($qstring);
  $db_qhandle = mysqli_query ($mysql_conn, $qstring);
  if ($db_qhandle)
    return $db_qhandle;
  return db_query_die ($qstring);
}

function db_query ($qstring, $multi_query = 0)
{
  global $sys_debug_footer, $db_queries_filed;
  if (!empty ($sys_debug_footer))
    $t = error_timestamp ();
  $ret = db_query_ ($qstring, $multi_query);
  if (empty ($sys_debug_footer))
    return $ret;
  $db_queries_filed[] = [
    $qstring, $multi_query, db_format_backtrace (), error_timestamp () - $t
  ];
  return $ret;
}

function db_numrows ($qhandle)
{
  if (!$qhandle)
    return 0;

  return mysqli_num_rows ($qhandle);
}

function db_free_result ($qhandle)
{
  return mysqli_free_result ($qhandle);
}

function db_result ($qhandle, $row, $field)
{
  if (!mysqli_data_seek ($qhandle, $row))
    return NULL;

  $row_data = mysqli_fetch_row ($qhandle);
  if ($row_data === false)
    return NULL;

  $field_num = mysqli_num_fields ($qhandle);
  if (gettype ($field) == 'integer')
    {
      if ($field >= $field_num)
        return NULL;
      return $row_data [$field];
    }

  $fields = mysqli_fetch_fields ($qhandle);
  if ($fields === false)
    return NULL;
  for ($i = 0; $i < $field_num; $i++)
    if ($fields[$i]->name == $field)
      return $row_data[$i];

  return NULL;
}

function db_numfields ($lhandle)
{
  return mysqli_num_fields ($lhandle);
}

function db_fieldname ($lhandle, $fnumber)
{
  return mysqli_fetch_field_direct ($lhandle, $fnumber)->name;
}

function db_affected_rows ($qhandle)
{
  return mysqli_affected_rows ($GLOBALS['mysql_conn']);
}

function db_data_seek ($qhandle, $row = 0)
{
  return mysqli_data_seek ($qhandle, $row);
}

function db_fetch_array ($qhandle = 0)
{
  if ($qhandle)
    return mysqli_fetch_array ($qhandle);
  if (isset ($GLOBALS['db_qhandle']))
    return mysqli_fetch_array ($GLOBALS['db_qhandle']);
  return [];
}

function db_insertid ($qhandle)
{
  return mysqli_insert_id ($GLOBALS['mysql_conn']);
}

function db_error ()
{
  global $mysql_conn;
  return mysqli_error ($mysql_conn);
}

# Return an sql insert command taking in input a qhandle:
# it is supposed to ease copy a a row into another, ignoring the autoincrement
# field + replacing another field value (like group_id).
function db_createinsertinto (
  $result, $table, $row, $autoincrement_fieldname, $replace_fieldname = 'zxry',
  $replace_value = 'axa'
)
{
  $fields = [];
  for ($i = 0; $i < db_numfields ($result); $i++)
    {
      $fieldname = db_fieldname ($result, $i);
      # Create the SQL by ignoring the autoincremental id.
      if ($fieldname == $autoincrement_fieldname)
        continue;
      if (db_result ($result, $row, $fieldname) == NULL)
        continue;
      # Replace another field.
      if ($fieldname == $replace_fieldname)
        $fields[$fieldname] = $replace_value;
      else
        $fields[$fieldname] = db_result ($result, $row, $fieldname);
    }
  # No fields? Ignore.
  if (count ($fields) == 0)
    return 0;
  return db_autoexecute ($table, $fields, DB_AUTOQUERY_INSERT);
}
?>
