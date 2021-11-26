<?php

require_once '../common.php';

$ladder_id = intval($_GET['ladder'] ?? 0);
$cup_id = intval($_GET['cup'] ?? 0);
$course_id = intval($_GET['course'] ?? 0);

$result = db_query("
  SELECT
    course.user_id,
    phpbb_users.username,
    phpbb_users.user_from AS location,

    course.value AS course_value,
    course.ship AS course_ship,
    course.platform AS course_platform,
    course.notes AS course_notes,
    course.videourl AS course_videourl,
    course.screenshoturl AS course_screenshoturl,
    course.verified AS course_verified,
    TO_DAYS(CURDATE()) - TO_DAYS(course.last_change) AS course_age,

    lap.value AS lap_value,
    lap.ship AS lap_ship,
    lap.platform AS lap_platform,
    lap.notes AS lap_notes,
    lap.videourl AS lap_videourl,
    lap.screenshoturl AS lap_screenshoturl,
    lap.verified AS lap_verified,
    TO_DAYS(CURDATE()) - TO_DAYS(lap.last_change) as lap_age,

    speed.value AS speed_value,
    speed.ship AS speed_ship,
    speed.platform AS speed_platform,
    speed.notes AS speed_notes,
    speed.videourl AS speed_videourl,
    speed.screenshoturl AS speed_screenshoturl,
    speed.verified AS speed_verified,
    TO_DAYS(CURDATE()) - TO_DAYS(lap.last_change) as speed_age

  FROM phpbb_f0_records course
  JOIN phpbb_users USING (user_id)
  LEFT JOIN phpbb_f0_records lap ON (
    course.ladder_id = lap.ladder_id AND
    course.cup_id = lap.cup_id AND
    course.course_id = lap.course_id AND
    course.user_id = lap.user_id AND
    lap.record_type = 'L'
  )
  LEFT JOIN phpbb_f0_records speed ON (
    course.ladder_id = speed.ladder_id AND
    course.cup_id = speed.cup_id AND
    course.course_id = speed.course_id AND
    course.user_id = speed.user_id AND
    speed.record_type = 'S'
  )
  WHERE course.ladder_id = $ladder_id
    AND course.cup_id = $cup_id
    AND course.course_id = $course_id
    AND course.record_type = 'C'
  ORDER BY course.value
");
$entries = [];
$index = 1;
while ($row = mysqli_fetch_assoc($result)) {
  $entries []= array_merge(
    $row,
    [
      'position' => $index,
      'course_ship_image' => ship_image_url($row['course_ship']),
      'lap_ship_image' => ship_image_url($row['lap_ship']),
      'speed_ship_image' => ship_image_url($row['speed_ship']),
      'course_has_proof' => $row['course_videourl'] != '' || $row['course_screenshoturl'] != '',
      'lap_has_proof' => $row['lap_videourl'] != '' || $row['lap_screenshoturl'] != '',
      'speed_has_proof' => $row['speed_videourl'] != '' || $row['speed_screenshoturl'] != '',
    ]
  );
  $index += 1;
}

$ladder = FserverLadder($ladder_id);
foreach ($ladder->cups->cup as $cup) {
  $id = intval($cup['cupid']);
  if ($cup_id == $id) {
    break;
  }
}
foreach ($cup->courses->course as $course) {
  $id = intval($course['courseid']);
  if ($course_id == $id) {
    break;
  }
}

$template = $twig->load('course.html');
echo render_template($template, [
  'page_class' => 'page-player-ladder',
  'PAGE_TITLE' => 'Player ladder scores',
  'username' => $username,
  'ladder' => $ladder,
  'ladder_id' => $ladder_id,
  'cup' => $cup,
  'cup_id' => $cup_id,
  'course' => $course,
  'course_id' => $course_id,
  'entries' => $entries,
  'totals' => $totals,
  'current_user' => $current_user,
]);
