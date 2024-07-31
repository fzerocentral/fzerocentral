<?php

require_once '../common.php';
require_once '../account_utils.php';


if ($current_user == NULL) {
  die("You have to login to view this page");
}

$template = $twig->load('email_change.html');

$user_id = intval($current_user['user_id']);
$current_email = $current_user['user_email'];

$render_args = [
  'page_class' => 'page-email-change',
  'PAGE_TITLE' => 'Change email address',
  'current_email' => $current_email,
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $desired_email = trim($_POST['email']);
  try {
    $email_validation_result = validate_email_address($desired_email);
  }
  catch (EmailValidationException $exception) {
    $errors[] = $exception->getMessage();
    $email_validation_result = EmailValidation::Error;
  }

  if (count($errors) > 0) {
    // There were errors; the form will be redisplayed with previously
    // submitted field values.
    $render_args['previous_values'] = $_POST;
  }
  elseif ($email_validation_result === EmailValidation::AlreadyInUse) {
    send_email_address_in_use_email($desired_email);

    $render_args['is_sending_email'] = true;
    $render_args['desired_email'] = $desired_email;
  }
  else {
    // Set new email address in the database
    db_update_by(
      'phpbb_users', ['user_email' => $desired_email], ['user_id' => $user_id]);

    // Deactivate account
    db_update_by(
      'phpbb_users', ['user_active' => 0], ['user_id' => $user_id]);

    // Send activation email to new email address
    $activation_key = create_activation_key($user_id);
    send_activation_email($user_id, $activation_key);

    // Ideally send a notice email to the old email address, but maybe
    // saving that effort for the upcoming FZC instead.

    // Probably makes sense to force-logout here, but not
    // strictly necessary.

    // And the displayed page should say what's going on and what
    // to do next.
    $render_args['is_sending_email'] = true;
    $render_args['desired_email'] = $desired_email;
  }
}

$render_args['errors'] = $errors;
echo render_template($template, $render_args);
