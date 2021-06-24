<?php

require_once __DIR__ . '/../common.php';

// I messed up some timestamps while testing things locally. This script
// takes the times from records.txt and updates the database with them,
// if the value didn't change.

$contents = trim(file_get_contents(__DIR__ . '/../records.txt'));
$lines = explode("\n", $contents);
$header = explode("\t", $lines[0]);

foreach (array_slice($lines, 1) as $line) {
  $values = explode("\t", $line);
  $obj = array_combine($header, $values);

  $key = [
    'user_id' => $obj['user_id'],
    'ladder_id' => $obj['ladder_id'],
    'cup_id' => $obj['cup_id'],
    'course_id' => $obj['course_id'],
    'record_type' => $obj['record_type'],
  ];

  $existing = db_find_by('phpbb_f0_records', $key);

  if ($existing['value'] != $obj['value']) {
    echo "changed, not updating:\n";
    echo "  " . json_encode($obj) . "\n";
    echo "  " . json_encode($existing) . "\n";
  } else if ($existing['last_change'] != $obj['last_change']) {
    echo "updating " . json_encode($obj) . "\n";
    db_update_by('phpbb_f0_records', ['last_change' => $obj['last_change']], $key);
  }
}
