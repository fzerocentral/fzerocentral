<?php

require_once '../common.php';

$key = ($_GET['key'] ?? 't') == 't' ? 't' : 'f';
$selected_ladder = intval($_GET['g'] ?? 0);

$add_vars = "&g=$selected_ladder&key=$key";

$result = db_query("
  SELECT count(*)
  FROM phpbb_f0_champs_10
  WHERE champ_type = '$key' AND ladder_id = $selected_ladder
  ORDER BY value
");

$query_data = mysqli_fetch_assoc($result);
$numrows = $query_data[0];

$sql = "
  SELECT user_id, value, username, pf_phpbb_location AS location
  FROM phpbb_f0_champs_10
  JOIN phpbb_users USING (user_id)
  LEFT JOIN phpbb_profile_fields_data USING (user_id)
  WHERE champ_type = '$key' AND ladder_id = $selected_ladder
  ORDER BY value DESC
";

$result = db_query($sql);
if (!$result) die("Cannot get challenges: " . $sql);

$fz = [];
$index = 0;
$compareToPrevious = '';
$fz_value='';
$fz_diff='';
$totaldiff='';
$clmxdiff='';
$gpldiff='';
$mvdiff='';
$xdiff='';
$snsdiff='';
$gxdiff='';

while ($row = mysqli_fetch_assoc($result)) {
  $user_id = $row['user_id'];
  $user_name = $row['username'];
  $user_flag = strtolower($row['location'] != '' ? $row['location'] : 'undefined');

  ## Get the comparison for the currently selected ladder
  $fz_value = $row['value'];

  if ($compareToPrevious != '') {
    $fz_diff = $fz_value - $compareToPrevious;
    if ($fz_diff == '+0') $fz_diff = '';
  }

  $compareToPrevious = $fz_value;

  if ($selected_ladder == 0) { $fz_total = $fz_value; $totaldiff = $fz_diff; }
  if ($selected_ladder == 1) { $fz_snstotal = $fz_value; $snsdiff = $fz_diff; }
  if ($selected_ladder == 2) { $fz_xtotal = $fz_value; $xdiff = $fz_diff; }
  if ($selected_ladder == 3) { $fz_mvtotal = $fz_value; $mvdiff = $fz_diff; }
  if ($selected_ladder == 6) { $fz_gpltotal = $fz_value; $gpldiff = $fz_diff; }
  if ($selected_ladder == 7) { $fz_clmxtotal = $fz_value; $clmxdiff = $fz_diff; }
  if ($selected_ladder == 9) { $fz_gxtotal = $fz_value; $gxdiff = $fz_diff; }

  ## generate scores for the ladders not currently selected

  $ladder_scores = [
    0 => 'fz_total',
    1 => 'fz_snstotal',
    2 => 'fz_xtotal',
    3 => 'fz_mvtotal',
    6 => 'fz_gpltotal',
    7 => 'fz_clmxtotal',
    9 => 'fz_gxtotal',
  ];

  $scores = [];

  foreach ($ladder_scores as $id => $name) {
    $sql = "
      SELECT value FROM phpbb_f0_champs_10
      WHERE champ_type = '$key' AND ladder_id = $id AND user_id = $user_id
    ";

    $result_total = db_query($sql);
    if ($result_total) {
      $row_total = mysqli_fetch_assoc($result_total);
      $scores[$name] = $row_total['value'];
    } else {
      $scores[$name] = 0;
    }
  }

  $index++;

  $fz[] = [
    'id' => $user_id,
    'index' => $index,
    'flag' => $user_flag,
    'name' => $user_name,
    'boards' => [
      ['name' => 'all',  'score' => $scores['fz_total'],     'diff' => $totaldiff],
      ['name' => 'sns',  'score' => $scores['fz_snstotal'],  'diff' => $snsdiff],
      ['name' => 'x',    'score' => $scores['fz_xtotal'],    'diff' => $xdiff],
      ['name' => 'mv',   'score' => $scores['fz_mvtotal'],   'diff' => $mvdiff],
      ['name' => 'gx',   'score' => $scores['fz_gxtotal'],   'diff' => $gxdiff],
      ['name' => 'gpl',  'score' => $scores['fz_gpltotal'],  'diff' => $gpldiff],
      ['name' => 'clmx', 'score' => $scores['fz_clmxtotal'], 'diff' => $clmxdiff],
    ],
  ];
}

$template = $twig->load('overall_ladder.html');
echo $template->render([
  'page_class' => 'page-overall-ladder',
  'PAGE_TITLE' => 'Overall Ladder',
  'entries' => $fz,
  'key' => $key,
  'selected_ladder' => $selected_ladder,
  'links' => [
    'overall_total' => "overall_ladder.php?g=0&key=${key}",
    'overall_sns'   => "overall_ladder.php?g=1&key=${key}",
    'overall_x'     => "overall_ladder.php?g=2&key=${key}",
    'overall_mv'    => "overall_ladder.php?g=3&key=${key}",
    'overall_gx'    => "overall_ladder.php?g=9&key=${key}",
    'overall_gpl'   => "overall_ladder.php?g=6&key=${key}",
    'overall_clmx'  => "overall_ladder.php?g=7&key=${key}",
  ],
]);
?>
