<?php

require_once '../common.php';
require_once '../account_utils.php';


$template = $twig->load('password_change.html');

class PasswordResetException extends Exception { }

function validate_password_reset($user_id, $token) {
  if (!$token) {
    throw new PasswordResetException(
      "Password reset token seems to be invalid.");
  }

  $result = db_query("
    SELECT
      phpbb_users.username,
      phpbb_users.user_email,
      phpbb_users.reset_token,
      phpbb_users.reset_token_expiration
    FROM phpbb_users
    WHERE phpbb_users.user_id = $user_id
  ");
  $user = mysqli_fetch_assoc($result);

  if (!$user) {
    // Nonexistent user ID, but we won't reveal that detail (it's not a
    // particularly sensitive detail, but still).
    throw new PasswordResetException(
      "Password reset token seems to be invalid.");
  }

  if (!hash_equals($user['reset_token'] ?? '', $token)) {
    throw new PasswordResetException(
      "Password reset token seems to be invalid.");
  }

  $now = time();
  if (intval($user['reset_token_expiration'] ?? $now) <= $now) {
    throw new PasswordResetException("Password reset token has expired.");
  }

  return $user;
}

$token_error = NULL;
$password_error = NULL;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $user_id = intval($_POST['user_id']);
  $token = $_POST['token'];

  try {
    $user = validate_password_reset($user_id, $token);
  }
  catch (PasswordResetException $exception) {
    $user = NULL;
    $token_error = $exception->getMessage();
  }

  if ($user) {
    $password = $_POST['password'];
    $password_confirmation = $_POST['password_confirmation'];

    $password_error = validate_password(
      $password, $password_confirmation,
      $user['username'], $user['user_email']);

    if (!$password_error) {
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
  }
}
else {
  $user_id = intval($_GET['user_id']);
  $token = $_GET['token'];

  try {
    validate_password_reset($user_id, $token);
  }
  catch (PasswordResetException $exception) {
    $token_error = $exception->getMessage();
  }
}

echo render_template($template, [
  'page_class' => 'page-password-change',
  'PAGE_TITLE' => 'Change your password',
  'password_min_length' => $PASSWORD_MIN_LENGTH,
  'token_error' => $token_error,
  'password_error' => $password_error,
  'user_id' => $user_id,
  'token' => $token,
  'password_changed' => $password_changed,
]);
