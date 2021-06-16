<?php

require_once '../common.php';

$template = $twig->load('password_change.html');

function validate_password_reset($user_id, $token) {
  $result = db_query("
    SELECT
      phpbb_users.reset_token,
      phpbb_users.reset_token_expiration
    FROM phpbb_users
    WHERE phpbb_users.user_id = $user_id
  ");
  $user = mysqli_fetch_assoc($result);

  $now = time();
  $user_exists = $user != NULL;
  $same_token = hash_equals($user['reset_token'] ?? '', $token);
  $token_time_ok = intval($user['reset_token_expiration'] ?? $now) > $now;

  return $user_exists && $same_token && $token_time_ok;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $user_id = intval($_POST['user_id']);
  $token = $_POST['token'];

  if (validate_password_reset($user_id, $token)) {
    $password = $_POST['password'];
    $password_confirmation = $_POST['password_confirmation'];

    $password_mismatch = $password != $password_confirmation;
    $password_too_short = strlen($password) < 8;

    if (!$password_mismatch && !$password_too_short) {
      db_query("
        UPDATE phpbb_users
        SET
          reset_token = '',
          reset_token_expiration = 0,
          user_password = '" . db_escape_string(password_hash($password, PASSWORD_DEFAULT)) . "'
        WHERE user_id = $user_id
      ");
      $password_changed = true;
    }

  } else {
    $error = true;
  }
} else {
  $user_id = intval($_GET['user_id']);
  $token = $_GET['token'];

  if (validate_password_reset($user_id, $token)) {
    $error = false;
  } else {
    $error = true;
  }
}

$template->display([
  'page_class' => 'page-password-change',
  'PAGE_TITLE' => 'Change your password',
  'error' => $error,
  'user_id' => $user_id,
  'token' => $token,
  'password_changed' => $password_changed,
  'password_mismatch' => $password_mismatch,
  'password_too_short' => $password_too_short,
]);
