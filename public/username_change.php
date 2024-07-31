<?php

require_once '../common.php';
require_once '../account_utils.php';


$utc_timezone = new DateTimeZone('UTC');


function can_change_username($user) {
  $last_change = $user['last_username_change'];
  if (!$last_change) {
    // Never changed before.
    return true;
  }

  $change_date = date_create($last_change, $utc_timezone);
  $now = date_create('now', $utc_timezone);
  // Can change if the last change was over 14 days ago.
  return ($now > date_add($change_date, new DateInterval('P14D')));
}

if ($current_user == NULL) {
  die("You have to login to view this page");
}

$template = $twig->load('username_change.html');

$user_id = intval($current_user['user_id']);

$render_args = [
  'page_class' => 'page-username-change',
  'PAGE_TITLE' => 'Change username',
];
$errors = [];

if (!can_change_username($current_user)) {

  $render_args['cant_change'] = true;
}
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $desired_username = trim($_POST['username']);
  // Collapse multiple spaces
  $desired_username = preg_replace('/\s+/', ' ', $desired_username);
  // Error checks
  $username_error = validate_username($desired_username);
  if ($username_error) {
    $errors[] = $username_error;
  }

  if (count($errors) > 0) {
    // There were errors; the form will be redisplayed with previously
    // submitted field values.
    $render_args['previous_values'] = $_POST;
  }
  else {
    // Set new username in the database.
    db_update_by(
      'phpbb_users', ['username' => $desired_username], ['user_id' => $user_id]);

    // Set 'last username change' date.
    $now_str = date_create('now', $utc_timezone)->format('Y-m-d H:i:s');
    db_update_by(
      'phpbb_users', ['last_username_change' => $now_str], ['user_id' => $user_id]);

    // Ensure the 'current user' vars are refreshed for the template, including
    // for the base template's usages.
    get_current_user_from_db();

    $render_args['success'] = true;
  }
}

$render_args['errors'] = $errors;
echo render_template($template, $render_args);
