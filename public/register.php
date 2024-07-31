<?php

require_once '../common.php';
require_once '../database.php';
require_once '../account_utils.php';


$template = $twig->load('register.html');

$render_args = [
  'page_class' => 'page-register',
  'PAGE_TITLE' => 'Register',
  'password_min_length' => $PASSWORD_MIN_LENGTH,
];
$errors = [];

global $db_throw_exceptions;
$db_throw_exceptions = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $username = trim($_POST['username']);
  // Collapse multiple spaces
  $username = preg_replace('/\s+/', ' ', $username);
  // Error checks
  $username_error = validate_username($username);
  if ($username_error) {
    $errors[] = $username_error;
  }

  $email = trim($_POST['email']);
  try {
    $email_validation_result = validate_email_address($email);
  }
  catch (EmailValidationException $exception) {
    $errors[] = $exception->getMessage();
    $email_validation_result = EmailValidation::Error;
  }

  $password_error = validate_password(
    $_POST['password'], $_POST['password_confirmation'], $username, $email);
  if ($password_error) {
    $errors[] = $password_error;
  }

  // The anti-bot quiz has a few ideas in it:
  // - Answer is a single word, and this check isn't case sensitive, so no
  //   confusion on how to type it
  // - Specific to the F-Zero domain, simple for most humans who would want to
  //   use this site, but harder for a generic bot
  // - Image-based question means the question text alone doesn't give a bot
  //   enough info to answer
  // - Negation in the question wording means that if a bot answers what's in
  //   the image, it won't work
  // - Wording doesn't allow extraction of a short phrase like "which F-Zero
  //   pilot" to get a small set of possible answers
  if (strtolower(trim($_POST['quiz'])) !== 'pico') {
    $errors[] = "Wrong quiz answer.";
  }

  if (count($errors) > 0) {
    // There were errors; the form will be redisplayed with previously
    // submitted field values.
    $render_args['previous_values'] = $_POST;
  }
  elseif ($email_validation_result === EmailValidation::AlreadyInUse) {
    send_email_address_in_use_email($email);

    $render_args['sending_email'] = true;
  }
  else {
    try {
      // Add unactivated user to DB
      $user_id = db_insert(
        'phpbb_users',
        [
          'username' => $username,
          'user_email' => $email,
          'user_password' => password_hash(
            $_POST['password'], PASSWORD_DEFAULT),
          'user_active' => 0,
          'moderator' => 0,
          'user_regdate' => time(),
        ],
      );
    }
    catch (DatabaseException $exception) {
      // Ideally this is `echo render_message(...)` instead of `die(...)`
      // because blank pages are ugly, but haven't figured out how to avoid
      // over-escaping characters when trying to do the former.
      die(
        "Error inserting into database: " . $exception->getMessage());
      return;
    }

    // Send activation email
    $activation_key = create_activation_key($user_id);
    send_activation_email($user_id, $activation_key);
    $render_args['sending_email'] = true;
  }
}

$render_args['errors'] = $errors;
echo render_template($template, $render_args);
