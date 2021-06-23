<?php

require_once __DIR__ . '/../../database.php';

/**
 * This file contains functions that recalculate cached tables
 * with AF, SRPR, and total times for every ladder.
 *
 * recalc_ladder_totals($ladder_id):
 * computes phpbb_f0_totals
 * so that players without records in certain courses don't get better total times,
 * this uses standin times when there are missing records. These are called
 * "Ferris Beuller" times, and they're stored in the user with $ferris_beuller_id.
 *
 * recalc_af($ladder_id):
 * computes AF, stored in phpbb_f0_champs_10 with champ_type = 'f'
 *
 * recalc_srpr($ladder_id):
 * computes SRPR, stored in phpbb_f0_champs_10 with champ_type = 't'
 */

$ferris_beuller_id = 222;

function recalc_ladder_totals($ladder_id) {
  global $ferris_beuller_id;

  db_delete_by('phpbb_f0_totals', ['ladder_id' => $ladder_id]);

  db_query("
    INSERT INTO phpbb_f0_totals
    (user_id, ladder_id, cup_id, time, lap, speed, last_change, new_ladder_id)
    SELECT
      players.user_id,
      $ladder_id,
      courses.cup_id,
      SUM(IF(phpbb_f0_records.record_type = 'C', COALESCE(phpbb_f0_records.value, beuller.value), 0)) AS time,
      SUM(IF(phpbb_f0_records.record_type = 'L', COALESCE(phpbb_f0_records.value, beuller.value), 0)) AS lap,
      SUM(IF(phpbb_f0_records.record_type = 'S', COALESCE(phpbb_f0_records.value, beuller.value), 0)) AS speed,
      MAX(phpbb_f0_records.last_change),
      0
    FROM (SELECT DISTINCT user_id FROM phpbb_f0_records WHERE ladder_id = $ladder_id) players
    CROSS JOIN (SELECT DISTINCT cup_id, course_id, record_type FROM phpbb_f0_records WHERE ladder_id = $ladder_id) AS courses
    LEFT JOIN phpbb_f0_records ON (
      $ladder_id = phpbb_f0_records.ladder_id AND
      players.user_id = phpbb_f0_records.user_id AND
      courses.cup_id = phpbb_f0_records.cup_id AND
      courses.course_id = phpbb_f0_records.course_id AND
      courses.record_type = phpbb_f0_records.record_type
    )
    LEFT JOIN phpbb_f0_records beuller ON (
      $ladder_id = beuller.ladder_id AND
      players.user_id = beuller.user_id AND
      courses.cup_id = beuller.cup_id AND
      courses.course_id = beuller.course_id AND
      courses.record_type = beuller.record_type AND
      beuller.user_id = $ferris_beuller_id
    )
    GROUP BY courses.cup_id, phpbb_f0_records.user_id
  ");

  db_query("
    INSERT INTO phpbb_f0_totals
    (user_id, ladder_id, cup_id, time, lap, speed, last_change, new_ladder_id)
    SELECT user_id, ladder_id, 0, SUM(time), SUM(lap), SUM(speed), MAX(last_change), 0
    FROM phpbb_f0_totals
    WHERE ladder_id = $ladder_id
    GROUP BY user_id
  ");
}

function recalc_af($ladder_id) {
  // grab all possible cup_id/course_id/record_type combinations for this ladder
  $result = db_query("
    SELECT DISTINCT ladder_id, cup_id, course_id, record_type
    FROM phpbb_f0_records
    WHERE ladder_id = $ladder_id AND record_type != 'S'
    ORDER BY ladder_id, cup_id, course_id
  ");

  $fzaf = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $fzaf []= $row;
  }

  // load every record for this ladder into memory
  $player_records_result = db_query("
      SELECT ladder_id, cup_id, course_id, record_type, user_id, value
      FROM phpbb_f0_records
      WHERE ladder_id = $ladder_id
      ORDER BY ladder_id, cup_id, course_id, record_type, value DESC, user_id
  ");

  $player_records = [];
  $values = [];
  while ($row = mysqli_fetch_assoc($player_records_result)) {
    $player_records[$row['user_id']][$row['cup_id']][$row['course_id']][$row['record_type']] = intval($row['value']);
    $values[$row['cup_id']][$row['course_id']][$row['record_type']] []= intval($row['value']);
  }

  $result_totals = db_query("
    SELECT DISTINCT user_id
    FROM phpbb_f0_totals
    WHERE ladder_id = $ladder_id
    ORDER BY user_id
  ");

  $entries = [];
  while ($row = mysqli_fetch_assoc($result_totals)) {
    $af = recalc_af_user($fzaf, count($player_records), $player_records[$row['user_id']], $values);

    $entries []= [
      'user_id' => $row['user_id'],
      'champ_type' => 'f',
      'ladder_id' => $ladder_id,
      'value' => $af,
    ];
  }

  usort($entries, function($a, $b) { return $a['value'] - $b['value']; });

  db_delete_by('phpbb_f0_champs_10', ['ladder_id' => $ladder_id, 'champ_type' => 'f']);
  $rank = 1;
  foreach ($entries as $entry) {
    db_insert('phpbb_f0_champs_10', array_merge($entry, ['rank' => $rank]));
    $rank++;
  }
}

function recalc_af_user($fzaf, $number_players, $player_records, $values) {
  $counts = [];
  $total_ranks = [];
  foreach ($fzaf as $entry) {
    $result = $player_records[$entry['cup_id']][$entry['course_id']][$entry['record_type']];
    $counts[$entry['record_type']]++;

    if ($result < 1) {
      $total_ranks[$entry['record_type']] += $number_players;
    } else {
      $betters = count(
        array_filter(
          $values[$entry['cup_id']][$entry['course_id']][$entry['record_type']],
          function($value) use($result) { return $value <= $result; }
        )
      );
      $total_ranks[$entry['record_type']] += $betters;
    }
  }

  $af = 0;
  foreach (array_keys($counts) as $type) {
    $af += $total_ranks[$type] / $counts[$type];
  }

  $combined_af = round($af / count($counts), 3);
  $af_score = round(log10($number_players / $combined_af) * 1000);

  if ($af_score < 1) $af_score = 0;
  return $af_score;
}

// TODO: this feels like it's splitting the score
// in half if there are splits, and putting all the
// weight into 'C' when there aren't. This can probably
// be achieved in the formula instead of keeping these
// weights around.
// Alternatively, it could live in the ladder files.
$ladder_srpr_weights = [
  1  => ['C' =>  5000, 'L' => 5000, 'S' => 0],
  2  => ['C' =>  5000, 'L' => 5000, 'S' => 0],
  3  => ['C' =>  5000, 'L' => 5000, 'S' => 0],
  4  => ['C' =>  5000, 'L' => 5000, 'S' => 0],
  5  => ['C' =>  5000, 'L' => 5000, 'S' => 0],
  6  => ['C' =>  5000, 'L' => 5000, 'S' => 0],
  7  => ['C' =>  5000, 'L' => 5000, 'S' => 0],
  8  => ['C' =>  5000, 'L' => 5000, 'S' => 0],
  11 => ['C' => 10000, 'L' =>    0, 'S' => 0],
  12 => ['C' => 10000, 'L' =>    0, 'S' => 0],
  13 => ['C' => 10000, 'L' =>    0, 'S' => 0],
  14 => ['C' => 10000, 'L' =>    0, 'S' => 0],
  15 => ['C' => 10000, 'L' =>    0, 'S' => 0],
  16 => ['C' =>  5000, 'L' => 5000, 'S' => 0],
  17 => ['C' => 10000, 'L' =>    0, 'S' => 0],
  18 => ['C' =>  5000, 'L' => 5000, 'S' => 0],
];

function recalc_srpr($ladder_id) {
  global $ladder_srpr_weights;
  $weights = $ladder_srpr_weights[$ladder_id];

  $best_results = db_query("
    SELECT cup_id, course_id, record_type, MIN(value) AS value
    FROM phpbb_f0_records
    WHERE ladder_id = $ladder_id AND value != 0
    GROUP BY cup_id, course_id, record_type
  ");

  $best = [];
  $records = [];
  while ($row = mysqli_fetch_assoc($best_results)) {
    $best[$row['cup_id']][$row['course_id']][$row['record_type']] = intval($row['value']);
    $records[$row['record_type']]++;
  }

  $results = db_query("
    SELECT user_id, cup_id, course_id, record_type, value
    FROM phpbb_f0_records
    WHERE ladder_id = $ladder_id AND value != 0
  ");

  $entries = [];
  $srprs = [];
  while ($row = mysqli_fetch_assoc($results)) {
    $asrpr = $best[$row['cup_id']][$row['course_id']][$row['record_type']] / $row['value'];
    $srprs[$row['user_id']] += $asrpr * $weights[$row['record_type']] / $records[$row['record_type']];
  }

  $entries = [];
  foreach ($srprs as $user_id => $srpr) {
    $entries []= [
      'ladder_id' => $ladder_id,
      'user_id' => $user_id,
      'value' => round($srpr),
      'champ_type' => 't',
    ];
  }

  usort($srprs, function($a, $b) { return $a['value'] - $b['value']; });

  db_delete_by('phpbb_f0_champs_10', ['ladder_id' => $ladder_id, 'champ_type' => 't']);
  $rank = 1;
  foreach ($entries as $entry) {
    db_insert('phpbb_f0_champs_10', array_merge($entry, ['rank' => $rank]));
    $rank++;
  }
}
