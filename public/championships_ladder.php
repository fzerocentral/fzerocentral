<?php

require_once '../common.php';

$result = db_query("
  SELECT
    t.*,
    phpbb_users.username,
    user_from AS location
  FROM (
    SELECT
      user_id, ladder_id, champ_type, value,
      RANK() OVER (PARTITION BY ladder_id, champ_type ORDER BY value DESC) AS rank
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
