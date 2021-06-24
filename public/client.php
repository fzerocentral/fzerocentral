<?php

require_once '../common.php';

if ($current_user == NULL) {
  die("You have to login to edit");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $ladder_id = intval($_POST['ladder']);
  $user_id = intval($_GET['user'] ?? $current_user['user_id']);
  $ladder = FserverLadder($ladder_id);

  if (!$current_user['moderator'] && $user_id != $current_user['user_id']) {
    $twig->load('403.html')->display();
    exit;
  }

  // We cannot moderate ourselves
  $mod_mode = $user_id != $current_user['user_id'];

  function record_changes($fields, $existing_row) {
    if (!$existing_row) {
      return $fields;
    }

    $changes = array_diff_assoc($fields, $existing_row);
    if (empty($changes)) {
      return [];
    }

    // If the value isn't changing, we don't need to change the last_change
    // column. The last changed date is auto-set to the current date unless we
    // specify a date ourselves.
    if (!isset($changes['value'])) {
      $changes['last_change'] = $existing_row['last_change'];
    }

    // if the verified status is not being set explicitly,
    // we need to reset it if the value or the video proof url changed
    if (!isset($fields['verified'])) {
      if (isset($changes['value']) || isset($changes['videourl'])) {
        $changes['verified'] = false;
      }
    }

    return $changes;
  }

  function extract_record_value($ladder, $user, $record) {
    if (isset($record['speed'])) {
      return user_to_ntsc_speed($ladder, $user, intval($record['speed']));
    } else {
      $time_m = intval($record["time@m"]);
      $time_s = intval($record["time@s"]);
      $time_t = intval($record["time@t"]);
      if ($ladder->timeformat == 'Hundredths') {
        $time_t *= 10;
      }
      $time = $time_m * 60*1000 + $time_s * 1000 + $time_t;

      return user_to_ntsc_time($ladder, $user, $time);
    }
  }

  function store_record_changes($ladder, $user_id, $ladder_id, $mod_mode) {
    global $current_user;

    $entries = [];
    $deletions = [];
    foreach ($_POST['records'] as $key => $record) {
      $parts = explode('-', $key);
      $key = [
        'ladder_id' => $ladder_id,
        'user_id' => $user_id,
        'record_type' => $parts[0],
        'cup_id' => $parts[1],
        'course_id' => $parts[2],
      ];

      $values = [
        'ship'          => $record['ship'],
        'platform'      => $record["platform"] ?? '',
        'videourl'      => $record["videourl"],
        'screenshoturl' => $record["screenshoturl"],
        'notes'         => $record["notes"] ?? '',
        'value'         => extract_record_value($ladder, $current_user, $record),
      ];

      if ($mod_mode && isset($record['delete'])) {
        $deletions []= $key;
      } else {
        if ($mod_mode) {
          $values['verified'] = isset($record['verified']);
        }

        if ($values['value'] != 0) {
          $entries []= ['key' => $key, 'values' => $values];
        }
      }
    }

    foreach ($deletions as $deletion) {
      db_delete_by('phpbb_f0_records', $deletion);
    }

    foreach ($entries as $entry) {
      $existing_row = db_find_by('phpbb_f0_records', $entry['key']);

      if ($existing_row == NULL) {
        db_insert(
          'phpbb_f0_records',
          array_merge(
            $entry['key'],
            ['ship' => '', 'platform' => '', 'settings' => '', 'splits' => '', 'notes' => '', 'videourl' => '', 'screenshoturl' => '']
          )
        );
      }

      $fields = record_changes($entry['values'], $existing_row);
      db_update_by('phpbb_f0_records', $fields, $entry['key']);
    }
  }

  store_record_changes($ladder, $user_id, $ladder_id, $mod_mode);
  recalc_ladder_totals($ladder_id);
  recalc_af($ladder_id);
  recalc_srpr($ladder_id);
  recalc_af_totals();
  recalc_srpr_totals();

  header("Location: /ladder.php?id=$ladder_id");
} else {
  $ladder_id = intval($_GET['ladder']);
  $user_id = intval($_GET['user'] ?? $current_user['user_id']);
  $ladder = FserverLadder($ladder_id);

  if (!$current_user['moderator'] && $user_id != $current_user['user_id']) {
    $twig->load('403.html')->display();
    exit;
  }

  // We cannot moderate ourselves
  $mod_mode = $user_id != $current_user['user_id'];

  $submission = FserverGetUserData($ladder_id, $user_id, $current_user, $ladder);

  $template = $twig->load('client.html');
  echo $template->render([
    'page_class' => 'page-client',
    'PAGE_TITLE' => $current_user['username'] . "'s F-Zero " . $ladder->ladder_name . " Times",
    'current_user' => $current_user,
    'ladder' => $ladder,
    'hasspeed' => $ladder->hasspeed == "Yes",
    'haslap' => $ladder->haslap == "Yes",
    'submission' => $submission,
    'showing_pal' => user_prefers_pal($current_user) && $ladder->palpossible == "Yes",
    'ladder_id' => $ladder_id,
    'user_id' => $user_id,
    'mod_mode' => $mod_mode,
  ]);
}
