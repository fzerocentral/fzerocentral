<?php

require_once '../common.php';
require_once '../account_utils.php';


$template = $twig->load('activate.html');

$render_args = [
  'page_class' => 'page-activate',
  'PAGE_TITLE' => 'Account activation',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Do activation.
  // This requires POST so that email clients which crawl links can't
  // invalidate activation links before the user gets to use them.
  // https://stackoverflow.com/questions/41699071/bingpreview-invalidates-one-time-links-in-email
  $render_args['post_request'] = true;

  try {
    $user_id = verify_activation_key($_POST['activation_key']);
  }
  catch (ActivationException $exception) {
    $user_id = NULL;
    $render_args['error'] = $exception->getMessage();
  }

  if ($user_id !== NULL) {
    // Mark as active
    db_query("
      UPDATE phpbb_users
      SET
        user_active = 1
      WHERE user_id = {$user_id}
    ");
  }
}
else {
  $render_args['activation_key'] = $_GET['activation_key'];
}

echo render_template($template, $render_args);
