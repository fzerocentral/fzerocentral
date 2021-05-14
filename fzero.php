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
      user_id, username, pf_phpbb_location AS location,
      TO_DAYS(curdate()) - TO_DAYS(last_change) as age
    FROM phpbb_f0_totals
    JOIN phpbb_users USING (user_id)
    LEFT JOIN phpbb_profile_fields_data USING (user_id)
    WHERE ladder_id = $ladder_id AND cup_id = 0 AND TO_DAYS(curdate()) - TO_DAYS(last_change) < 190
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
