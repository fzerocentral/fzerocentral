<?php

require_once '../common.php';

function get_all_records_for_cup($ladder_id, $cup_id) {
  $records = db_query("
    SELECT
      phpbb_f0_records.course_id,
      phpbb_f0_records.record_type,
      phpbb_f0_records.value
    FROM phpbb_f0_records
    WHERE phpbb_f0_records.cup_id = $cup_id
      AND phpbb_f0_records.ladder_id = $ladder_id
      AND phpbb_f0_records.record_type <> 'S'
    ORDER BY phpbb_f0_records.course_id,
             phpbb_f0_records.record_type,
             phpbb_f0_records.value
  ");
  return $records;
}

$user_id   = intval($_GET['user'] ?? $_GET['id'] ?? 0);
$ladder_id = intval($_GET['ladder'] ?? 0);

$username = mysqli_fetch_assoc(db_query("SELECT phpbb_users.username FROM phpbb_users WHERE phpbb_users.user_id = $user_id"))['username'];

$player_records = db_query("
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
    phpbb_f0_records.last_change,
    DATE(phpbb_f0_records.last_change) as date,
    TO_DAYS(CURDATE()) - TO_DAYS(phpbb_f0_records.last_change) as age
  FROM phpbb_f0_records
  WHERE phpbb_f0_records.user_id = $user_id
    AND phpbb_f0_records.ladder_id = $ladder_id
  ORDER BY phpbb_f0_records.cup_id, phpbb_f0_records.course_id
");
$entries = [];
$totals = [];
while ($row = mysqli_fetch_assoc($player_records)) {
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

// Compute ranks for each record.
// We do so in such a way that 1) avoids doing one DB query per
// record, since that could be slow, and 2) avoids reading all
// players' records of the entire ladder at once, since that
// could be memory intensive.
// Basically, we're closer to 2) except we do one cup at a time.

$all_ranks = [];

foreach ($entries as $cup_id => $cup_records) {
  $all_records_for_cup = get_all_records_for_cup($ladder_id, $cup_id);
  $better_counts = [];
  $player_counts = [];

  foreach ($all_records_for_cup as $record) {
    $course_id = $record['course_id'];
    $record_type = $record['record_type'];

    // Note that speeds would use >, but we're not bothering
    // with speed ranks for now.
    if ($record['value'] < $entries[$cup_id][$course_id][$record_type]['value']) {
      $better_counts[$course_id][$record_type] += 1;
    }
    $player_counts[$course_id][$record_type] += 1;
  }

  foreach ($cup_records as $course_id => $course_records) {
    foreach ($course_records as $record_type => $row) {
      if ($record_type == 'S') {
        continue;
      }

      $rank = $better_counts[$course_id][$record_type] + 1;

      $entries[$cup_id][$course_id][$record_type] = array_merge(
        $row,
        [
          'rank' => $rank,
          'player_count' => $player_counts[$course_id][$record_type],
        ]
      );

      if (!array_key_exists($record_type, $all_ranks)) {
        $all_ranks[$record_type] = [];
      }
      array_push($all_ranks[$record_type], $rank);
    }
  }
}

// Average rank for each applicable record type.
$average_ranks = [];
foreach ($all_ranks as $record_type => $ranks_for_type) {
  $average_ranks[$record_type] = round(
    array_sum($ranks_for_type) / count($ranks_for_type), 3);
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
  'average_ranks' => $average_ranks,
]);
