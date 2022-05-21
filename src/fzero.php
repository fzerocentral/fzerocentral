<?php

require_once __DIR__ . '/fzero/recalc.php';

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

function format_time_part($value, $part_name, $timeformat) {
  if ($value == '') {
    // Missing times should have their fields blank
    return '';
  }

  if ($part_name == 'Seconds') {
    // 1'02"345, not 1'2"345
    return sprintf("%02d", $value);
  }
  if ($part_name == 'Subseconds') {
    if ($timeformat == 'Hundredths') {
      // 1'23"04, not 1'23"4
      return sprintf("%02d", $value);
    }
    else {
      // 1'23"045, not 1'23"45
      return sprintf("%03d", $value);
    }
  }
  return $value;
}

function ladder_game($ladder_id) {
  return [
    1 => 'snes',
    2 => 'x',
    3 => 'mv',
    4 => 'gx',
    5 => 'gx',
    6 => 'gpl',
    7 => 'climax',
    8 => 'gx',
    11 => 'gx',
    12 => 'gx',
    13 => 'gpl',
    14 => 'climax',
    15 => 'climax',
    16 => 'x',
    17 => 'x',
    18 => 'x',
  ][$ladder_id];
}

function ladder_pal_ratio($ladder) {
  if ($ladder->palpossible == "Yes") {
    return $ladder->pal_numerator / $ladder->pal_denominator;
  } else {
    return 1;
  }
}

function user_prefers_pal($user) {
  preg_match('/F0Pal/', $user['user_interests']);
}

function ntsc_to_user_time($ladder, $user, $time) {
  if (user_prefers_pal($user)) {
    $time = round($time * ladder_pal_ratio($ladder));
    if ($ladder->timeformat == 'Hundredths') {
      $time = round($time / 10) * 10;
    }
  }
  return $time;
}

function user_to_ntsc_time($ladder, $user, $time) {
  if (user_prefers_pal($user)) {
    $time = round($time / ladder_pal_ratio($ladder));
    if ($ladder->timeformat == 'Hundredths') {
      $time = round($time / 10) * 10;
    }
  }
  return $time;
}

// speed is 1/time, so we have to invert the logic here
// Also, there's no time format to calculate
function ntsc_to_user_speed($ladder, $user, $value) {
  if (user_prefers_pal($user)) {
    $value = round($value / ladder_pal_ratio($ladder));
  }
  return $value;
}

function user_to_ntsc_speed($ladder, $user, $value) {
  if (user_prefers_pal($user)) {
    $value = round($value * ladder_pal_ratio($ladder));
  }
  return $time;
}

function ship_image_url($ship_name) {
  # When matching a ship name to a ship image filename, strip
  # anything besides word characters (A-Za-z0-9_), and ignore case.
  $ship_filename = strtolower(preg_replace('/\W/', '', $ship_name)) . '.gif';

  # Check if ship image exists, otherwise use default.gif.
  if (!file_exists(__DIR__ . "/../public/images/ships/$ship_filename") ) {
    return '/images/ships/default.gif';
  }

  return "/images/ships/$ship_filename";
}

function FserverGame($game_shortcode) {
  $file = __DIR__ . "/../data/games/$game_shortcode.xml";
  $game = simplexml_load_file($file);

  return $game;
}

function FserverLadder($ladder_id) {
  $file = __DIR__ . "/../data/ladders/ladder$ladder_id.xml";
  $ladder = simplexml_load_file($file);

  return $ladder;
}

function FserverGetActivePlayers($ladder_id) {
  $ladder_id = intval($ladder_id);
  $days = 190;

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
      AND TO_DAYS(CURDATE()) - TO_DAYS(phpbb_f0_totals.last_change) < $days
    ORDER BY age ASC
    LIMIT 6
  ");

  $players = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $players[] = $row;
  }

  return $players;
}

function FserverGetUserData($ladder_id, $user_id, $current_user, $ladder) {
  global $db;

  $output = [
    'user_id' => $user_id,
    'ladder_id' => $ladder_id,
    'cups' => [],
  ];

  // loop through cups user has
  $result = db_query("
    SELECT
      phpbb_f0_records.cup_id,
      phpbb_f0_records.course_id,
      phpbb_f0_records.record_type,
      phpbb_f0_records.value,
      phpbb_f0_records.ship,
      phpbb_f0_records.platform,
      phpbb_f0_records.notes,
      phpbb_f0_records.last_change,
      phpbb_f0_records.videourl,
      phpbb_f0_records.screenshoturl,
      phpbb_f0_records.verified,
      TO_DAYS(curdate()) - TO_DAYS(last_change) as age
    FROM phpbb_f0_records
    WHERE phpbb_f0_records.user_id = $user_id
      AND phpbb_f0_records.ladder_id = $ladder_id
    ORDER BY phpbb_f0_records.cup_id, phpbb_f0_records.course_id
  ");

  while ($row = mysqli_fetch_assoc($result)) {
    if ($row['record_type'] == 'S') {
      $row['speed'] = ntsc_to_user_speed($ladder, $current_user, $row['value']);
    } else {
      $row['value'] = ntsc_to_user_time($ladder, $current_user, $row['value']);

      $row['time_m'] = floor($row['value'] / 60000);
      $row['time_s'] = floor($row['value'] % 60000 / 1000);
      $row['time_t'] = $row['value'] % 1000;

      if ($ladder->timeformat == 'Hundredths') {
        $row['time_t'] /= 10;
      }
    }

    $output['cups'][$row['cup_id']][$row['course_id']][$row['record_type']] = $row;
  }

  // add totals
  $result = db_query("
    SELECT phpbb_f0_totals.time
    FROM phpbb_f0_totals
    WHERE phpbb_f0_totals.user_id = $user_id
      AND phpbb_f0_totals.ladder_id = $ladder_id
      AND phpbb_f0_totals.cup_id = 0
  ");
  $result = mysqli_fetch_assoc($result);

  $output['total_time'] = $result['time'];

  return $output;
}
