<?php

require_once '../common.php';

$template = $twig->load('password_reset.html');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'];

  $result = db_query("select * from phpbb_users where user_email = '" . db_escape_string($email) . "'");
  $user = mysqli_fetch_assoc($result);

  if ($user != NULL) {
    $reset_token = bin2hex(random_bytes(32));
    $reset_token_expiration = strtotime('+1 day');

    db_query("UPDATE phpbb_users SET reset_token = '" . db_escape_string($reset_token) .  "', reset_token_expiration = " . $reset_token_expiration . " WHERE user_id = " . $user['user_id']);

    send_email(
      [$user['user_email']],
      'password_reset_email',
      'Password reset instructions',
      [
        'username' => $user['username'],
        'url' => url("/password_change.php?user_id=${user['user_id']}&token=$reset_token"),
      ]
    );
  }

  echo $template->render([
    'page_class' => 'page-password-reset',
    'PAGE_TITLE' => 'Reset your password',
    'sent' => true,
  ]);
} else {
  echo $template->render([
    'page_class' => 'page-password-reset',
    'PAGE_TITLE' => 'Reset your password',
  ]);
}
