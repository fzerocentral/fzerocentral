<?php

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

function db_query($sql) {
  global $db, $config, $db_num_queries;
  $result = mysqli_query($db, $sql);

  $db_num_queries++;

  if ($config['database']['debug'] && $error = mysqli_error($db)) {
    echo "<pre>db_query error: ";
    var_dump(mysqli_error_list($db));
  }

  return $result;
}

function db_multi_query($sql) {
  global $db, $config;
  $result = mysqli_multi_query($db, $sql);

  if ($config['database']['debug'] && $error = mysqli_error($db)) {
    echo "<pre>db_query error: ";
    var_dump(mysqli_error_list($db));
  }

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
  } else {
    return $value;
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
