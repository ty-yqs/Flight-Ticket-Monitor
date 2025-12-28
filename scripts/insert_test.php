<?php
// Insert a test subscription into subscriptions.db
$dbFile = __DIR__ . '/../subscriptions.db';
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $db->prepare('INSERT INTO subscriptions(from_airport,to_airport,date,email,created_at) VALUES(:f,:t,:d,:e,:c)');
$stmt->execute([':f'=>'PEK',':t'=>'PVG',':d'=>'2026-01-15',':e'=>'test@example.com',':c'=>date('c')]);
echo "Inserted id=" . $db->lastInsertId() . "\n";
