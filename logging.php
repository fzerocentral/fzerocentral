<?php


function log_entry($filename, $message) {
  $directory = __DIR__ . '/log';
  if (!is_dir($directory)) {
    // Logs may have sensitive info, particularly if logging errors regarding
    // database queries.
    mkdir($directory, 0770);
  }
  file_put_contents(
    "{$directory}/{$filename}",
    $message . "\n",
    FILE_APPEND);
}
