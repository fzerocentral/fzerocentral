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

$template = $twig->load('championships_ladder.html');
echo $template->render([
  'page_class' => 'page-championships-ladder',
  'PAGE_TITLE' => 'Championships Ladder',
  'leaderboard' => $leaderboard,
]);
