<?php

require_once '../common.php';

$user_id   = intval($_GET['user'] ?? $_GET['id'] ?? 0);
$ladder_id = intval($_GET['ladder'] ?? 0);

$username = mysqli_fetch_assoc(db_query("SELECT username FROM phpbb_users WHERE user_id = $user_id"))['username'];

$result = db_query("
  SELECT
    cup_id, course_id, record_type, value, ship, platform, notes, videourl, screenshoturl, verified,
    TO_DAYS(curdate()) - TO_DAYS(last_change) as age
  FROM phpbb_f0_records
  WHERE user_id = $user_id AND ladder_id = $ladder_id
  ORDER BY cup_id, course_id
");
$entries = [];
$totals = [];
while ($row = mysqli_fetch_assoc($result)) {
  $entries[$row['cup_id']][$row['course_id']][$row['record_type']]= array_merge(
    $row,
    ['ship_image' => ship_image_url($row['ship'])]
  );

  $totals[$row['cup_id']][$row['record_type']] += $row['value'];
  $totals[0][$row['record_type']] += $row['value'];
}

$ladder = FserverLadder($ladder_id);

$template = $twig->load('viewplayer.html');
echo $template->render([
  'page_class' => 'page-player-ladder',
  'PAGE_TITLE' => 'Player ladder scores',
  'username' => $username,
  'ladder' => $ladder,
  'ladder_id' => $ladder_id,
  'entries' => $entries,
  'totals' => $totals,
]);
