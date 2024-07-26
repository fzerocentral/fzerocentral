<?php

require_once '../common.php';

$result = db_query("
  SELECT
    t.*,
    phpbb_users.username,
    phpbb_users.user_from AS location
  FROM (
    SELECT
      phpbb_f0_champs_10.user_id, phpbb_f0_champs_10.ladder_id, phpbb_f0_champs_10.champ_type, phpbb_f0_champs_10.value,
      RANK() OVER (PARTITION BY phpbb_f0_champs_10.ladder_id, phpbb_f0_champs_10.champ_type ORDER BY phpbb_f0_champs_10.value DESC) AS rank
    FROM phpbb_f0_champs_10
  ) t
  JOIN phpbb_users USING (user_id)
  WHERE rank <= 3
");
$leaderboard = [];
while ($row = mysqli_fetch_assoc($result)) {
  $leaderboard[$row['ladder_id']][$row['champ_type']][$row['rank']] = $row;
}

$ladders = [];
foreach ([1, 2, 3, 4, 5, 6, 7, 8, 11, 12, 13, 14, 15, 16, 17, 18] as $ladder_id) {
  $ladders[$ladder_id] = FserverLadder($ladder_id);
}

$template = $twig->load('championships_ladder.html');
echo render_template($template, [
  'page_class' => 'page-championships-ladder',
  'PAGE_TITLE' => 'Championships Ladder',
  'leaderboard' => $leaderboard,
  'ladders' => $ladders,
]);
