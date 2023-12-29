<?php

require_once __DIR__ . '/../account_utils.php';


// On your development server, serve this file's directory and then navigate
// to this file in your browser to run the following tests.

$username_tests = [
  ['aa', "Usernames must be at least 3 characters."],
  ['aaa', NULL],
  ['abcdefghijklmnopqrstu', "Usernames must be no more than 20 characters."],
  ['abcdefghijklmnopqrst', NULL],
  ['aaa@', "Not an accepted character for usernames: @"],
  ['aaa:aaa', "Not an accepted character for usernames: :"],
  ['aaa*', "Not an accepted character for usernames: *"],
  ['aaa^', "Not an accepted character for usernames: ^"],
  ['aaa!', "Not an accepted character for usernames: !"],
  ['Aa0 .-_', NULL],
  ['admin', "This username is not allowed."],
  ['Administrator', "This username is not allowed."],
  ['F-Zero Central', "This username is not allowed."],
  ['.-_', "Usernames need at least one letter or number."]];
foreach ($username_tests as $username_test) {
  $username = $username_test[0];
  $expected_error = $username_test[1];
  $actual_error = validate_username($username);
  if ($expected_error !== $actual_error) {
    die("Username `{$username}` got error `{$actual_error}` instead of `{$expected_error}`");
  }
}

$email_tests = [
  ['abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijkl@123456789012345678901234567890123456789012345678901234567890123.123456789012345678901234567890123456789012345678901234567890123.c23456789012345678901234567890123456789012345678901234567890123', "Email addresses must be no more than 255 characters."],
  // PHP validation seems to limit to 254 characters
  ['abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijkl@123456789012345678901234567890123456789012345678901234567890123.123456789012345678901234567890123456789012345678901234567890123.c2345678901234567890123456789012345678901234567890123456789012', "This email address isn't in an accepted format."],
  ['abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijkl@123456789012345678901234567890123456789012345678901234567890123.12345678901234567890123456789012345678901234567890123456789012.c2345678901234567890123456789012345678901234567890123456789012', NULL],
  ['a@b', "This email address isn't in an accepted format."],
  ['a@b.c', NULL],
  ['a+b@c.d', NULL]];
foreach ($email_tests as $email_test) {
  $email = $email_test[0];
  $expected_error = $email_test[1];
  try {
    validate_email_address($email);
    $actual_error = NULL;
  }
  catch (EmailValidationException $exception) {
    $actual_error = $exception->getMessage();
  }
  if ($expected_error !== $actual_error) {
    die("Email `{$email}` got error `{$actual_error}` instead of `{$expected_error}`");
  }
}

$password_tests = [
  ['abcdefgh', "Passwords must be at least 9 characters."],
  ['abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrs',
   "Passwords must be no more than 70 characters."],
  ['rstuvwxyz', NULL],
  ['abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqr',
    NULL],
  ['12345678901234567890', "Passwords can't be entirely numeric."],
  ['12345678y', NULL],
  ['ausername', "The password is too similar to the username."],
  ['ausername51', "The password is too similar to the username."],
  ['ausername512', NULL],
  ['anemail@adomain.org', "The password is too similar to the email address."],
  ['anemail+adomainorg', "The password is too similar to the email address."],
  ['anemail+adomainorg+', NULL],
  ['qwertyuiop', "This password is too common."],
  ['QWERTYuiop', "This password is too common."],
  ['qwertyuiop22', NULL]];
foreach ($password_tests as $password_test) {
  $password = $password_test[0];
  $expected_error = $password_test[1];
  $actual_error = validate_password(
    $password, $password, 'ausername', 'anemail@adomain.org');
  if ($expected_error !== $actual_error) {
    die("Password `{$password}` got error `{$actual_error}` instead of `{$expected_error}`");
  }
}
$actual_error = validate_password(
  'aaaaaaaaaa', 'aaaaaaaaab', 'ausername', 'anemail@adomain.org');
$expected_error = "The two password fields didn't match.";
if ($actual_error !== $expected_error) {
  die("Got error `{$actual_error}` instead of `{$expected_error}`");
}

echo "All tests passed.";
