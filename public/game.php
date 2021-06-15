<?php

require_once '../common.php';

$game = $_GET['id'];

switch ($game) {
case 'gx':
  $ladders = [4, 5, 8, 11, 12];
  break;
case 'x':
  $ladders = [2, 16, 17, 18];
  break;
case 'climax':
  $ladders = [7, 14, 15];
  break;
case 'gpl':
  $ladders = [6, 13];
  break;
case 'mv':
  $ladders = [3];
  break;
case 'snes':
  $ladders = [1];
  break;
default:
  exit;
}


$ladder_types = [
  4 => 'courses_and_laps',
  5 => 'courses_and_laps',
  8 => 'courses_and_laps',
  11 => 'courses_only',
  12 => 'courses_only',
  2 => 'courses_and_laps',
  16 => 'courses_and_laps',
  17 => 'courses_only',
  18 => 'courses_and_laps',
  7 => 'courses_and_laps',
  14 => 'courses_only',
  15 => 'courses_only',
  6 => 'courses_and_laps',
  13 => 'courses_only',
  3 => 'courses_and_laps',
  1 => 'courses_and_laps',
];

$leaderboard = [];
$result = db_query("
  SELECT t.*, phpbb_users.username
  FROM (
    SELECT
      user_id, ladder_id, time,
      RANK() OVER (PARTITION BY ladder_id ORDER BY time ASC) AS rank
    FROM phpbb_f0_totals
    WHERE cup_id = 0
  ) t
  JOIN phpbb_users USING (user_id)
  WHERE rank <= 9
");
while ($row = mysqli_fetch_assoc($result)) {
  $leaderboard['time'][$row['ladder_id']][$row['rank']] = $row;
}

$result = db_query("
  SELECT t.*, phpbb_users.username
  FROM (
    SELECT
      user_id, ladder_id, lap,
      RANK() OVER (PARTITION BY ladder_id ORDER BY lap ASC) AS rank
    FROM phpbb_f0_totals
    WHERE cup_id = 0
  ) t
  JOIN phpbb_users USING (user_id)
  WHERE rank <= 9
");
while ($row = mysqli_fetch_assoc($result)) {
  $leaderboard['lap'][$row['ladder_id']][$row['rank']] = $row;
}

$my_times = [];
$active_players = [];

foreach ($ladders as $ladder) {
  $result = db_query("SELECT lap, time
      FROM phpbb_f0_totals
      WHERE ladder_id = $ladder AND cup_id = 0 AND user_id = $current_user_id");
  $row = mysqli_fetch_assoc($result);

  $my_times[$ladder] = ['time' => format_time($row['time'], ''), 'lap' => format_time($row['lap'], '')];

  $active_players[$ladder] = FserverGetActivePlayers($ladder);
}

$template = $twig->load('game.html');
echo $template->render([
  'page_class' => 'page-game',
  'PAGE_TITLE' => "$game Home",
  'ladders' => $ladders,
  'leaderboard' => $leaderboard,
  'ladder_types' => $ladder_types,
  'my_times' => $my_times,
  'active_players' => $active_players,
  'selected_game' => $game,
]);
