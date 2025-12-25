<?php
// Initialize SQLite database (run once via browser or CLI)
$dbFile = __DIR__ . '/subscriptions.db';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS subscriptions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  from_airport TEXT NOT NULL,
  to_airport TEXT NOT NULL,
  date TEXT NOT NULL,
  email TEXT NOT NULL,
  created_at TEXT NOT NULL
)");

echo "Initialization complete, database file: " . $dbFile . "\n";
