<?php

require_once '../common.php';
require_once '../database.php';


if ($current_user == NULL) {
  die("You have to login to view this page");
}

$template = $twig->load('profile.html');

$user_id = intval($current_user['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // The only POST form on this page is for changing location.

  $location = $_POST['location'];
  $location = trim($location);
  // Filter chars to just letters/spaces (esp. no slashes to avoid
  // image directory traversal)
  $location = preg_replace('/[^A-Za-z\s]/', '', $location);
  // Truncate to fit the DB field
  $location = substr($location, 0, 100);

  db_update_by(
    'phpbb_users', ['user_from' => $location], ['user_id' => $user_id]);
}
else {
  $location = $current_user['user_from'];
}

$render_args = [
  'page_class' => 'page-profile',
  'PAGE_TITLE' => 'User profile',
  'email_address' => $current_user['user_email'],
  'registration_date' => date('Y-m-d', $current_user['user_regdate']),
  'location' => $location,
  'user_id' => $current_user_id,
];
echo render_template($template, $render_args);
