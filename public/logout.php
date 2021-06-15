<?php

require_once '../common.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  session_unset();

  header('Location: /');
}
