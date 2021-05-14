<?php

require_once '../common.php';

$ladder_id = intval($_GET['id'] ?? 0);
$ladder = FserverLadder($ladder_id);

if (isset($_GET['country'])) {
  $country = $_GET['country'];
  $country_filter = "pf_phpbb_location = '". mysqli_real_escape_string($db, $country) . "'";
} else {
  $country = '';
  $country_filter = '1=1';
}


$entries = [];
// AF
$result = db_query("
  SELECT
    phpbb_f0_totals.user_id,
    username,
    pf_phpbb_location AS location,
    phpbb_f0_totals.time,
    phpbb_f0_totals.lap,
    phpbb_f0_totals.last_change,
    af_score.value AS af,
    srpr_score.value AS srpr
  FROM phpbb_f0_totals
  JOIN phpbb_users USING (user_id)
  LEFT JOIN phpbb_profile_fields_data USING (user_id)
  LEFT JOIN phpbb_f0_champs_10 af_score ON (
    phpbb_f0_totals.user_id = af_score.user_id
    AND phpbb_f0_totals.ladder_id = af_score.ladder_id
    AND af_score.champ_type = 'f'
  )
  LEFT JOIN phpbb_f0_champs_10 srpr_score ON (
    phpbb_f0_totals.user_id = srpr_score.user_id
    AND phpbb_f0_totals.ladder_id = srpr_score.ladder_id
    AND srpr_score.champ_type = 't'
  )
  WHERE phpbb_f0_totals.ladder_id = $ladder_id AND cup_id = 0 AND $country_filter
  ORDER BY af DESC
");
// phpbb_f0_champs_10

$index = 1;
while ($row = mysqli_fetch_assoc($result)) {
  $entries []= array_merge($row, [
    'position' => $index,
    'flag' => strtolower($row['location'] == '' ? 'undefined' : $row['location']),
  ]);
  $index++;
}

$template = $twig->load('ladder.html');
echo $template->render([
  'page_class' => 'page-ladder',
  'PAGE_TITLE' => $ladder->ladder_name . " Ladder",
  'entries' => $entries,
  'ladder' => $ladder,
  'ladder_id' => $ladder_id,
]);
