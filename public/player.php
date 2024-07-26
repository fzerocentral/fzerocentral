<?php

require_once '../common.php';

$user_id = intval($_GET['id'] ?? 0);

$username = mysqli_fetch_assoc(db_query("
  SELECT phpbb_users.username
  FROM phpbb_users
  WHERE phpbb_users.user_id = $user_id
"))['username'];

// SRPR Scores
$result = db_query("
  SELECT
    phpbb_f0_champs_10.ladder_id,
    phpbb_f0_champs_10.value
  FROM phpbb_f0_champs_10
  WHERE phpbb_f0_champs_10.champ_type = 't'
    AND phpbb_f0_champs_10.user_id = $user_id
");
$score_t = [];
while ($row = mysqli_fetch_assoc($result)) {
  $score_t[$row['ladder_id']] = $row['value'];
}

// AF Scores
$result = db_query("
  SELECT phpbb_f0_champs_10.ladder_id, phpbb_f0_champs_10.value
  FROM phpbb_f0_champs_10
  WHERE phpbb_f0_champs_10.champ_type = 'f'
    AND phpbb_f0_champs_10.user_id = $user_id
");
$score_f = [];
while ($row = mysqli_fetch_assoc($result)) {
  $score_f[$row['ladder_id']] = $row['value'];
}

// SRPR Ranks
$result = db_query("
  SELECT * FROM (
    SELECT
      phpbb_f0_champs_10.user_id,
      phpbb_f0_champs_10.ladder_id,
      phpbb_f0_champs_10.value,
      RANK() OVER (PARTITION BY phpbb_f0_champs_10.ladder_id ORDER BY phpbb_f0_champs_10.value DESC) AS rank
    FROM phpbb_f0_champs_10
    WHERE phpbb_f0_champs_10.champ_type = 't') x
  WHERE x.user_id = $user_id
");

$rank_t = [];
while ($row = mysqli_fetch_assoc($result)) {
  $rank_t[$row['ladder_id']] = $row['rank'];
}

// AF Ranks
$result = db_query("
  SELECT * FROM (
    SELECT
      phpbb_f0_champs_10.user_id,
      phpbb_f0_champs_10.ladder_id,
      phpbb_f0_champs_10.value,
      RANK() OVER (PARTITION BY phpbb_f0_champs_10.ladder_id ORDER BY phpbb_f0_champs_10.value DESC) AS rank
    FROM phpbb_f0_champs_10
    WHERE phpbb_f0_champs_10.champ_type = 'f') x
  WHERE x.user_id = $user_id
");

$rank_f = [];
while ($row = mysqli_fetch_assoc($result)) {
  $rank_f[$row['ladder_id']] = $row['rank'];
}

// Course Totals
$result = db_query("
  SELECT phpbb_f0_totals.ladder_id, phpbb_f0_totals.time
  FROM phpbb_f0_totals
  WHERE phpbb_f0_totals.cup_id = 0 AND phpbb_f0_totals.user_id = $user_id
");

$total_c = [];
while ($row = mysqli_fetch_assoc($result)) {
  $ladder = FserverLadder($row['ladder_id']);
  $total_c[$row['ladder_id']] = format_time($row['time'], $ladder->timeformat);
}

// Lap Totals
$result = db_query("
  SELECT phpbb_f0_totals.ladder_id, phpbb_f0_totals.lap
  FROM phpbb_f0_totals
  WHERE phpbb_f0_totals.cup_id = 0 AND phpbb_f0_totals.user_id = $user_id
");

$total_l = [];
while ($row = mysqli_fetch_assoc($result)) {
  $ladder = FserverLadder($row['ladder_id']);
  $total_l[$row['ladder_id']] = format_time($row['lap'], $ladder->timeformat);
}

// Course Ranks
$result = db_query("
  SELECT * FROM (
    SELECT
      phpbb_f0_totals.user_id,
      phpbb_f0_totals.ladder_id,
      phpbb_f0_totals.time,
      RANK() OVER (PARTITION BY phpbb_f0_totals.ladder_id ORDER BY phpbb_f0_totals.time ASC) AS rank
    FROM phpbb_f0_totals
    WHERE phpbb_f0_totals.cup_id = 0) x
  WHERE x.user_id = $user_id
");

$rank_c = [];
while ($row = mysqli_fetch_assoc($result)) {
  $rank_c[$row['ladder_id']] = $row['rank'];
}

//Lap Ranks
$result = db_query("
  SELECT * FROM (
    SELECT
      phpbb_f0_totals.user_id,
      phpbb_f0_totals.ladder_id,
      phpbb_f0_totals.lap,
      RANK() OVER (PARTITION BY phpbb_f0_totals.ladder_id ORDER BY phpbb_f0_totals.lap ASC) AS rank
    FROM phpbb_f0_totals
    WHERE phpbb_f0_totals.cup_id = 0) x
  WHERE x.user_id = $user_id
");

$rank_l = [];
while ($row = mysqli_fetch_assoc($result)) {
  $rank_l[$row['ladder_id']] = $row['rank'];
}

$template = $twig->load('player.html');
echo render_template($template, [
  'page_class' => 'page-player-summary',
  'PAGE_TITLE' => 'Player summary',
  'username' => $username,
  'performance' => [
    'score_t' => $score_t,
    'score_f' => $score_f,
    'rank_t' => $rank_t,
    'rank_f' => $rank_f,

    'total_c' => $total_c,
    'total_l' => $total_l,
    'rank_c' => $rank_c,
    'rank_l' => $rank_l,

    'ladders' => [
      1, 2, 18, 16, 17, 3, 4, 5, 8, 11, 12, 6, 13, 7, 14, 15,
    ],
    'ladder_titles' => [
      1  => 'SNES',
      2  => 'X Open',
      18 => 'X Jumper',
      16 => 'X EK',
      17 => 'X DR',
      3  => 'MV',
      4  => 'GX Open',
      5  => 'GX Max',
      8  => 'GX Snake',
      11 => 'GX Story Max',
      12 => 'GX Story Snake',
      6  => 'GPL',
      13 => 'GPL ZT',
      7  => 'CLMX',
      14 => 'CLMX ZT',
      15 => 'CLMX SR',
    ],

    'championships' => [
      0, 1, 2, 3, 9, 6, 7,
    ],
    'championship_titles' => [
      0 => 'Total',
      1 => 'SNES',
      2 => 'X',
      3 => 'MV',
      9 => 'GX',
      6 => 'GPL',
      7 => 'CLMX',
    ],
  ],
]);
