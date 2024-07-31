<?php

require_once '../common.php';
require_once '../account_utils.php';


$template = $twig->load('activation_resend.html');

$render_args = [
  'page_class' => 'page-activation-resend',
  'PAGE_TITLE' => 'Resend activation email',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $render_args['post_request'] = true;

  $email = trim($_POST['email']);
  $user = get_user_by_field('user_email', $email, false);
  if ($user) {
    $activation_key = create_activation_key($user['user_id']);
    send_activation_email($user['user_id'], $activation_key);
  }
  // Else, send no email (to be consistent with reset-password). Another
  // reasonable action here would be to send a notice email saying that
  // there's no account associated with this email address, but if going that
  // route, it might be wise to add an anti-bot quiz to this form.
}

echo render_template($template, $render_args);
