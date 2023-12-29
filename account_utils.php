<?php

require_once 'database.php';
require_once 'email.php';


function validate_username($username) {
  $min_length = 3;
  $max_length = 20;
  if (strlen($username) < $min_length) {
    return "Usernames must be at least {$min_length} characters.";
  }
  if (strlen($username) > $max_length) {
    return "Usernames must be no more than {$max_length} characters.";
  }

  // This set of allowed characters might be on the conservative side
  // (except for spaces), but can expand it later if we want.
  // One concern is that the activation key has the username,
  // and the activation key goes in the URL.
  $letters_and_numbers = array_merge(
    range('A', 'Z'),
    range('a', 'z'),
    range('0', '9'));
  $accepted_chars = implode('', $letters_and_numbers) . ' ._-';
  $span_length = strspn($username, $accepted_chars);
  if (strlen($username) !== $span_length) {
    $invalid_char = substr($username, $span_length, 1);
    return "Not an accepted character for usernames: {$invalid_char}";
  }

  $invalid_usernames = [
    'admin', 'administrator', 'fzc', 'fzerocentral',
    'moderator', 'owner', 'verifier', 'webmaster'];
  $no_punctuation_lowercase_username = preg_replace(
    '/[^A-Za-z0-9]/', '', strtolower($username));
  if (in_array($no_punctuation_lowercase_username, $invalid_usernames)) {
    return "This username is not allowed.";
  }

  if (preg_replace('/[^A-Za-z0-9]/', '', strtolower($username)) === '') {
    return "Usernames need at least one letter or number.";
  }

  $result = db_query("
    SELECT
      phpbb_users.user_id
    FROM phpbb_users
    WHERE LOWER(username) = '" . db_escape_string(strtolower($username)) . "'
  ");
  $user = mysqli_fetch_assoc($result);
  if ($user) {
    return "This username is already taken.";
  }
}


function get_user_by_field($field_name, $value, $case_sensitive) {
  if ($case_sensitive) {
    $field = $field_name;
    $escaped_value = db_escape_string($value);
  }
  else {
    $field = "LOWER({$field_name})";
    $escaped_value = db_escape_string(strtolower($value));
  }
  $result = db_query("
    SELECT
      phpbb_users.user_id,
      phpbb_users.username,
      phpbb_users.user_active
    FROM phpbb_users
    WHERE {$field} = '{$escaped_value}'
  ");
  return mysqli_fetch_assoc($result);
}


# A pre-PHP-8.1 enum
abstract class EmailValidation {
  const Error = 1;
  const AlreadyInUse = 2;
  const Unique = 3;
}

// This is from the database column size.
$EMAIL_MAX_LENGTH = 255;
class EmailValidationException extends Exception { }


// Returns an error string, an 'already exists' constant, or a 'unique'
// constant.
function validate_email_address($email) {
  global $EMAIL_MAX_LENGTH;

  if (strlen($email) > $EMAIL_MAX_LENGTH) {
    throw new EmailValidationException(
      "Email addresses must be no more than"
      . " {$EMAIL_MAX_LENGTH} characters.");
  }

  if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    throw new EmailValidationException(
      "This email address isn't in an accepted format.");
  }

  $user = get_user_by_field('user_email', $email, false);
  if ($user) {
    return EmailValidation::AlreadyInUse;
  }
  return EmailValidation::Unique;
}


$PASSWORD_MIN_LENGTH = 9;
// PHP's bcrypt implentation truncates past 72 bytes.
// We make the limit not quite 72 to add a tiny bit of obscurity.
// https://www.php.net/manual/en/function.password-hash.php
// Even if it weren't bcrypt-limited, it's useful to have *a* limit so
// that the hashing computation doesn't take an absurdly long time.
$PASSWORD_MAX_LENGTH = 70;

function validate_password(
    $password, $password_confirmation, $username, $email) {
  global $PASSWORD_MIN_LENGTH;
  global $PASSWORD_MAX_LENGTH;

  if (strlen($password) < $PASSWORD_MIN_LENGTH) {
    return "Passwords must be at least {$PASSWORD_MIN_LENGTH} characters.";
  }
  if (strlen($password) > $PASSWORD_MAX_LENGTH) {
    return "Passwords must be no more than {$PASSWORD_MAX_LENGTH} characters.";
  }

  if ($password !== $password_confirmation) {
    return "The two password fields didn't match.";
  }

  // The following checks try to emulate Django's default checks.

  if (preg_replace('/[0-9]/', '', $password) === '') {
    return "Passwords can't be entirely numeric.";
  }

  if (levenshtein($password, $username) <= 2) {
    return "The password is too similar to the username.";
  }
  if (levenshtein($password, $email) <= 2) {
    return "The password is too similar to the email address.";
  }

  $common_passwords = [];
  $stream = gzopen(__DIR__ . '/common-passwords_django-2022.txt.gz', 'r');
  $line = fgets($stream);
  while ($line !== false) {
    array_push($common_passwords, trim($line));
    $line = fgets($stream);
  }
  gzclose($stream);
  if (in_array(strtolower(trim($password)), $common_passwords)) {
    return "This password is too common.";
  }
}


function send_registration_email_in_use_email($email) {
  $user = get_user_by_field('user_email', $email, false);

  send_email(
    [$email],
    'registration_email_in_use_email',
    'Registration request: email address is already in use',
    [
      'username' => $user['username'],
      'reset_url' => url("/password_reset.php"),
    ]
  );
}


// Activation key algorithm is based off of django-registration.
// It does not require the activation key to be stored in the database.
// https://django-registration.readthedocs.io/en/3.4/activation-workflow.html#security-considerations
function create_activation_key($user_id) {
  $timestamp = time();
  $hmac_data = "{$user_id}:{$timestamp}";
  $hmac_signature = hash_hmac(
    'sha256',
    $hmac_data,
    // HMAC key must be kept secret for usage to be secure, so it's a
    // non-committed config var.
    // It's a string, and ideally should be randomly generated and contain
    // around 256 bits of data (given that we're using sha256 as the algo).
    // https://security.stackexchange.com/questions/95972/what-are-requirements-for-hmac-secret-key
    $config['app']['hmac_key']);
  return "{$hmac_data}:{$hmac_signature}";
}


class ActivationException extends Exception { }


function verify_activation_key($activation_key) {
  $parts = explode(':', $activation_key);
  if (count($parts) < 3) {
    throw new ActivationException("Activation key seems to be invalid.");
  }

  $user_id = $parts[0];
  $timestamp = $parts[1];
  $hmac_signature = $parts[2];
  $hmac_data = "{$user_id}:{$timestamp}";

  $user = get_user_by_field('user_id', $user_id, true);

  if (!$user) {
    // Nonexistent user ID, but we won't reveal that detail (it's not a
    // particularly sensitive detail, but still).
    throw new ActivationException("Activation key seems to be invalid.");
  }

  $seconds_in_one_week = 7*24*60*60;
  // Note that intval() may return 0 or 1 for non-numeric strings. We'll just
  // allow that case to throw the following 'expired' exception.
  if (time() > intval($timestamp) + $seconds_in_one_week) {
    throw new ActivationException("Activation key has expired.");
  }

  $correct_hmac_signature = hash_hmac(
      'sha256',
      $hmac_data,
      $config['app']['hmac_key']);
  if (!hash_equals($correct_hmac_signature, $hmac_signature)) {
    throw new ActivationException("Activation key seems to be invalid.");
  }

  // This is the last check, after validating the activation key itself,
  // so that outsiders can't spy on people's 'active' status by inputting
  // bogus activation keys.
  if ($user['user_active']) {
    $username = $user['username'];
    throw new ActivationException(
      "The user {$username} is already active! Try logging in.");
  }

  return $user_id;
}


function send_activation_email($email, $activation_key) {
  $user = get_user_by_field('user_email', $email, false);
  $url_safe_activation_key = urlencode($activation_key);

  send_email(
    [$email],
    'activation_email',
    'Activating your FZC account',
    [
      'username' => $user['username'],
      'activation_url' => url("/activate.php?activation_key={$url_safe_activation_key}"),
    ]
  );
}
