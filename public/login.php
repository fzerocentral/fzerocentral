<?php

require_once '../common.php';

$template = $twig->load('login.html');

$render_args = [
  'page_class' => 'page-login',
  'PAGE_TITLE' => 'Login',
];

class LoginException extends Exception { }

function validate_login($username, $password) {
  $result = db_query("
    SELECT
      phpbb_users.user_id,
      phpbb_users.user_password,
      phpbb_users.user_active
    FROM phpbb_users
    WHERE username = '" . db_escape_string($username) . "'
  ");
  $user = mysqli_fetch_assoc($result);

  // Note that $user may be null/false, but that simply leads to
  // password_verify() returning false.
  if (!password_verify($password, $user['user_password'])) {
    throw new LoginException("Wrong username or password");
  }

  if (!$user['user_active']) {
    throw new LoginException(
      "You must activate your account before you can log in.");
  }

  return $user;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  try {
    $user = validate_login($username, $password);
  }
  catch (LoginException $exception) {
    $user = NULL;
    $render_args['error'] = $exception->getMessage();
  }

  if ($user) {
    if (password_needs_rehash($user['user_password'], PASSWORD_DEFAULT)) {
      db_query("
        UPDATE phpbb_users
        SET user_password = '" . db_escape_string(password_hash($password, PASSWORD_DEFAULT)) . "'
        WHERE user_id = ${user['user_id']}
      ");
    }

    $_SESSION['current_user_id'] = $user['user_id'];

    header('Location: /');
  }
}

echo render_template($template, $render_args);
