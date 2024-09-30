<?php

// Send a one-off email, from the FZC admin email address, to the email address
// of the user with the passed username.

require_once __DIR__ . '/../account_utils.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../email.php';


$email_template_name = $argv[1];
$subject = $argv[2];
$username = $argv[3];

if ($email_template_name === NULL) {
  die("Pass the email template name as the first argument.");
}
if ($subject === NULL) {
  die("Pass the email subject as the second argument.");
}
if ($username === NULL) {
  die("Pass the target user's username as the third argument.");
}

$user = get_user_by_field('username', $username, true);
if ($user === NULL) {
  die("This user doesn't exist.");
}
$email_address = $user['user_email'];

$server_admin_usernames = $config['app']['server_admin_usernames'];
$server_admin_email_addresses = array_map(
  function($admin_username) {
    $admin_user = get_user_by_field('username', $admin_username, true);
    return $admin_user['user_email'];
  },
  $server_admin_usernames,
);

// For the main recipient.
send_email(
  [$email_address],
  $email_template_name,
  $subject,
  [
    'username' => $username,
  ]
);

// Admin copies.
send_email(
  $server_admin_email_addresses,
  $email_template_name,
  "[FZC one-off email: Admin's copy] " . $subject,
  [
    'username' => $username,
  ]
);

echo "Sent.";
