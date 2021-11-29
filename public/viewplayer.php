<?php

require_once '../common.php';

$user_id   = intval($_GET['user'] ?? $_GET['id'] ?? 0);
$ladder_id = intval($_GET['ladder'] ?? 0);

$username = mysqli_fetch_assoc(db_query("SELECT phpbb_users.username FROM phpbb_users WHERE phpbb_users.user_id = $user_id"))['username'];

$result = db_query("
  SELECT
    phpbb_f0_records.cup_id,
    phpbb_f0_records.course_id,
    phpbb_f0_records.record_type,
    phpbb_f0_records.value,
    phpbb_f0_records.ship,
    phpbb_f0_records.platform,
    phpbb_f0_records.notes,
    phpbb_f0_records.videourl,
    phpbb_f0_records.screenshoturl,
    phpbb_f0_records.verified,
    TO_DAYS(CURDATE()) - TO_DAYS(phpbb_f0_records.last_change) as age
  FROM phpbb_f0_records
  WHERE phpbb_f0_records.user_id = $user_id
    AND phpbb_f0_records.ladder_id = $ladder_id
  ORDER BY phpbb_f0_records.cup_id, phpbb_f0_records.course_id
");
$entries = [];
$totals = [];
while ($row = mysqli_fetch_assoc($result)) {
  $entries[$row['cup_id']][$row['course_id']][$row['record_type']]= array_merge(
    $row,
    [
      'ship_image' => ship_image_url($row['ship']),
      'has_proof' => $row['videourl'] != '' || $row['screenshoturl'] != '',
    ]
  );

  $totals[$row['cup_id']][$row['record_type']] += $row['value'];
  $totals[0][$row['record_type']] += $row['value'];
}

$ladder = FserverLadder($ladder_id);

$template = $twig->load('viewplayer.html');
echo render_template($template, [
  'page_class' => 'page-player-ladder',
  'PAGE_TITLE' => 'Player ladder scores',
  'username' => $username,
  'ladder' => $ladder,
  'ladder_id' => $ladder_id,
  'entries' => $entries,
  'totals' => $totals,
  'current_user' => $current_user,
]);
