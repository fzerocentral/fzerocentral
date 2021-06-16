<?php

function db_open() {
  global $db, $config;

  $db = mysqli_connect(
    $config['database']['host'],
    $config['database']['username'],
    $config['database']['password'],
  );

  mysqli_select_db($db, $config['database']['name']);
}

function db_query($sql) {
  global $db, $config;
  $result = mysqli_query($db, $sql);

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
