<?php

require_once __DIR__ . '/../common.php';

$migrations = glob(__DIR__ . "/*.sql");
sort($migrations);

db_query("CREATE TABLE IF NOT EXISTS schema_migrations(name TEXT)");

foreach ($migrations as $migration) {
  $basename = basename($migration);

  $exists = mysqli_fetch_assoc(db_query("SELECT 1 FROM schema_migrations WHERE name = '" . db_escape_string($basename) . "'")) != NULL;

  if (!$exists) {
    echo "Running migration $basename\n";

    if (!db_multi_query(file_get_contents($migration))) {
      echo "Error running migration $basename\n";
      exit;
    }

    db_query("INSERT INTO schema_migrations (name) VALUES ('" . db_escape_string($basename) . "')");
  }
}
