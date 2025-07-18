<?php

require_once '../common.php';

$ladder_id = intval($_GET['id'] ?? 0);
$ladder = FserverLadder($ladder_id);

$page_number = intval($_GET['page'] ?? 1);
$page_number = max($page_number, 1);
$offset = ($page_number - 1) * 100;

$entries = [];

$result = db_query("
  SELECT
    phpbb_f0_records.*,
    phpbb_users.username,
    phpbb_users.user_from AS location
  FROM phpbb_f0_records
  JOIN phpbb_users USING (user_id)
  WHERE ladder_id = $ladder_id
  ORDER BY last_change DESC
  LIMIT 100 OFFSET $offset
");

$index = 1;
while ($row = mysqli_fetch_assoc($result)) {
  $cup = $ladder->cups->cup[$row['cup_id'] - 1];
  $entries []= array_merge($row, [
    'position' => $index,
    'flag' => strtolower($row['location'] == '' ? 'undefined' : $row['location']),
    'cup' => $cup,
    'course' => $cup->courses->course[$row['course_id'] - 1],
    'ship_image' => ship_image_url($row['ship']),
    'has_proof' => $row['videourl'] != '' || $row['screenshoturl'] != '',
  ]);
  $index++;
}

$template = $twig->load('ladder_latest.html');
echo render_template($template, [
  'page_class' => 'page-ladder-latest',
  'PAGE_TITLE' => $ladder->ladder_name . " Ladder",
  'entries' => $entries,
  'ladder' => $ladder,
  'ladder_id' => $ladder_id,
  'page_number' => $page_number,
  'selected_game' => ladder_game($ladder_id),
]);
