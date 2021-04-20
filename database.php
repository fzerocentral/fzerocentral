<?php

function db_open() {
  global $db;

  $db = mysqli_connect('database', 'mfo', 'mfo');
  mysqli_select_db($db, 'phpbb');
}

function db_query($sql) {
  global $db;
  return mysqli_query($db, $sql);
}
