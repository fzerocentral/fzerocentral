<?php

require_once '../common.php';

$template = $twig->load('login.html');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $result = db_query("select * from phpbb_users where username = '" . db_escape_string($username) . "'");
  $user = mysqli_fetch_assoc($result);

  $loginok = password_verify($password, $user['user_password']);

  if ($loginok) {
    if (password_needs_rehash($user['user_password'], PASSWORD_DEFAULT)) {
      db_query("UPDATE phpbb_users SET user_password = '" . db_escape_string(password_hash($password, PASSWORD_DEFAULT)) . "' WHERE user_id = ${user['user_id']}");
    }

    $_SESSION['current_user_id'] = $user['user_id'];

    header('Location: /');

  } else {
    echo $template->render([
      'page_class' => 'page-login',
      'PAGE_TITLE' => 'Login',
      'error' => 'Wrong username or password',
    ]);
  }
} else {
  echo $template->render([
    'page_class' => 'page-login',
    'PAGE_TITLE' => 'Login',
  ]);
}
