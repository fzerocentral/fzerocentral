<?php

require_once __DIR__ . '/../common.php';

$migrations = glob(__DIR__ . "/*.sql");
sort($migrations);

db_query("CREATE TABLE IF NOT EXISTS schema_migrations(name TEXT)");

echo "Pending migrations:\n";
foreach ($migrations as $migration) {
  $basename = basename($migration);

  $exists = mysqli_fetch_assoc(db_query("SELECT * FROM schema_migrations WHERE name = '" . db_escape_string($basename) . "'")) != NULL;

  if (!$exists) {
    echo "$basename\n";
  }
}
