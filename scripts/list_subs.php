<?php
$db=new PDO('sqlite:' . __DIR__ . '/../subscriptions.db');
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$stmt = $db->query('SELECT id,email,from_airport,to_airport,date,created_at FROM subscriptions');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) { echo "No subscriptions found.\n"; exit; }
foreach ($rows as $r) {
    echo "id={$r['id']} | email={$r['email']} | route={$r['from_airport']}->{$r['to_airport']} | date={$r['date']} | created_at={$r['created_at']}\n";
}
