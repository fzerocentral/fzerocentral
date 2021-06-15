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
  global $db;
  return mysqli_query($db, $sql);
}

function db_escape_string($text) {
  global $db;
  return mysqli_real_escape_string($db, $text);
}
