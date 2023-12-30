<?php

require_once 'config.php';
require_once 'logging.php';

db_open();

// Whether to fail loudly, rather than just echoing an error which might
// appear on a dark background where it's hard to read or notice.
// Ideally this is true for every page, but in the interim, individual pages
// which are ready to set this to true can set it to true.
$db_throw_exceptions = false;


class DatabaseException extends Exception { }

function db_open() {
  global $db, $config, $db_num_queries;

  $db = mysqli_connect(
    $config['database']['host'],
    $config['database']['username'],
    $config['database']['password'],
  );

  $db_num_queries = 0;

  mysqli_select_db($db, $config['database']['name']);
}

function db_num_queries() {
  global $db_num_queries;

  $x = $db_num_queries;
  $db_num_queries = 0;
  return $x;
}

function db_handle_possible_error() {
  global $config, $db;

  if (mysqli_error($db)) {
    $error_str = var_export(mysqli_error_list($db), true);
    if ($config['database']['debug']) {
      // Only show the actual error in debug, because it may contain sensitive
      // info from the DB (or trying to go in the DB).
      db_show_error("db_query error: {$error_str}");
    }
    else {
      // Show an error code, and log the error code + error str to file.
      $error_code = rand();
      log_entry(
        'database_errors.log',
        "db_query error: code {$error_code}: {$error_str}");
      db_show_error(
        "db_query error: code {$error_code}. Please report to the admin.");
    }
  }
}

function db_show_error($message) {
  global $db_throw_exceptions;

  if ($db_throw_exceptions) {
    throw new DatabaseException($message);
  }
  else {
    echo "<pre>{$message}";
  }
}

function db_query($sql) {
  global $db, $config, $db_num_queries;

  $result = mysqli_query($db, $sql);

  $db_num_queries++;
  db_handle_possible_error();

  return $result;
}

function db_multi_query($sql) {
  global $db, $config;
  $result = mysqli_multi_query($db, $sql);

  db_handle_possible_error();

  return $result;
}

function db_escape_string($text) {
  global $db;
  return mysqli_real_escape_string($db, $text);
}

function db_encode($value) {
  global $db;

  if (is_string($value)) {
    return "'" . mysqli_real_escape_string($db, $value) . "'";
  } elseif ($value === NULL) {
    return "NULL";
  } elseif (is_array($value)) {
    return "(" . implode(',', array_map('db_encode', $value)) . ")";
  } elseif ($value instanceof DatabaseLiteral) {
    return $value->sql;
  } elseif (is_bool($value)) {
    return $value ? "true" : "false";
  } else {
    return strval($value);
  }
}

function db_where_sql($params) {
  $conditions = [];

  foreach ($params as $key => $value) {
    if ($value === NULL) {
      $conditions []= "$key IS NULL";
    } else if (is_array($value)) {
      $conditions []= "$key IN " . db_encode($value);
    } else {
      $conditions []= "$key = " . db_encode($value);
    }
  }

  return implode(' AND ', $conditions);
}

function db_set_sql($fields) {
  return implode(
    ', ',
    array_map(
      function($field, $value) { return "$field = " . db_encode($value); },
      array_keys($fields),
      $fields
    )
  );
}

function db_delete_by($table, $fields) {
  $where = db_where_sql($fields);
  return db_query("DELETE FROM $table WHERE $where");
}

function db_find_by($table, $fields) {
  $where = db_where_sql($fields);
  return mysqli_fetch_assoc(db_query("SELECT * FROM $table WHERE $where"));
}

function db_update_by($table, $values, $fields) {
  if (empty($values)) return false;
  if (empty($fields)) $fields = [0 => 0];

  $set = db_set_sql($values);
  $where = db_where_sql($fields);
  return db_query("UPDATE $table SET $set WHERE $where");
}

function db_insert($table, $fields) {
  global $db;

  $columns = array_keys($fields);

  $values = array_map(
    function($column) use ($fields) {
      return db_encode($fields[$column]);
    },
    $columns
  );

  $column_definition = implode(",", $columns);
  $values = implode(", ", $values);

  db_query("INSERT INTO $table ($column_definition) VALUES ($values)");

  return mysqli_insert_id($db);
}
