<?php
// Generate unsubscribe token for subscription id 1 (or provided via argv)
$root = __DIR__ . '/..';
require_once $root . '/lib/unsubscribe.php';
$dbFile = $root . '/subscriptions.db';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$id = isset($argv[1]) ? intval($argv[1]) : 1;
$stmt = $db->prepare('SELECT * FROM subscriptions WHERE id = :id');
$stmt->execute([':id'=>$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo "No subscription found for id={$id}\n"; exit(1); }
$token = make_unsubscribe_token($row['id'], $row['email'], $row['created_at'] ?? '');
echo "id={$row['id']} email={$row['email']} token={$token}\n";
echo "Unsubscribe URL: unsubscribe.php?id={$row['id']}&email=" . rawurlencode($row['email']) . "&t=" . rawurlencode($token) . "\n";
