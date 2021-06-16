<?php

function format_time($time, $timeformat) {
  if ($time < 0) {
    return format_time(-1 * $time, $timeformat);
  }

  $time_seconds = floor($time / 1000);
  $minutes = floor($time_seconds / 60);
  $seconds = $time_seconds - ($minutes * 60);
  $thousands = $time - ($time_seconds * 1000);

  if ($timeformat == 'Hundredths') {
    $hundreds = floor($thousands/10);
    return sprintf("%d'%02d\"%02d", $minutes, $seconds, $hundreds);
  }

  return sprintf("%d'%02d\"%03d", $minutes, $seconds, $thousands);
}

function FserverGetActivePlayers($ladder_id) {
  $ladder_id = intval($ladder_id);

  $result = db_query("
    SELECT
      phpbb_users.user_id,
      phpbb_users.username,
      phpbb_users.user_from AS location,
      TO_DAYS(CURDATE()) - TO_DAYS(phpbb_f0_totals.last_change) AS age
    FROM phpbb_f0_totals
    JOIN phpbb_users USING (user_id)
    WHERE phpbb_f0_totals.ladder_id = $ladder_id
      AND phpbb_f0_totals.cup_id = 0
      AND TO_DAYS(CURDATE()) - TO_DAYS(phpbb_f0_totals.last_change) < 190
    ORDER BY age ASC
    LIMIT 6
  ");

  $players = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $players[] = $row;
  }

  return $players;
}

function FserverLadder($ladder_id) {
  $file = __DIR__ . "/data/ladders/ladder$ladder_id.xml";
  $ladder = simplexml_load_file($file);

  return $ladder;
}

function ship_image_url($ship_name) {
{
  $ship_base_url = '/f0/images/ships';
  $ship_base_filepath = $phpbb_root_path . 'f0/images/ships';

  # When matching a ship name to a ship image filename, strip
  # anything besides word characters (A-Za-z0-9_), and ignore case.
  $ship_filename = strtolower(preg_replace('/\W/', '', $ship_name)) . '.gif';

  # Check if ship image exists, otherwise use default.gif.
  if (!file_exists(__DIR__ . "/public/images/ships/$ship_filename") ) {
    return '/images/ships/default.gif';
  }

  return "/images/ships/$ship_filename";
}
}
