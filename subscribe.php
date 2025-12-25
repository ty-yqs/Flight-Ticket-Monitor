<?php
// Handle subscription form and save to SQLite
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$from = isset($_POST['from']) ? strtoupper(trim($_POST['from'])) : '';
$to = isset($_POST['to']) ? strtoupper(trim($_POST['to'])) : '';
$date = isset($_POST['date']) ? trim($_POST['date']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

$errors = [];
if (!preg_match('/^[A-Z0-9]{2,5}$/', $from)) $errors[] = 'Invalid departure airport code';
if (!preg_match('/^[A-Z0-9]{2,5}$/', $to)) $errors[] = 'Invalid arrival airport code';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $errors[] = 'Invalid date format';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address';

if ($errors) {
    echo '<h2>Submission Error</h2><ul>';
    foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>';
    echo '</ul><p><a href="index.php">Back</a></p>';
    exit;
}

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

$stmt = $db->prepare('INSERT INTO subscriptions(from_airport,to_airport,date,email,created_at) VALUES(:f,:t,:d,:e,:c)');
$stmt->execute([':f'=>$from,':t'=>$to,':d'=>$date,':e'=>$email,':c'=>date('c')]);

?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Subscription Successful</title></head>
<body>
  <h1>Subscription Successful</h1>
  <p>Subscription for <strong><?php echo htmlspecialchars($email) ?></strong> has been created: <?php echo htmlspecialchars($from.' → '.$to.' '.$date) ?></p>
  <p>The server will run the scraper hourly and send the sorted prices to your email.</p>
  <p><a href="index.php">Back</a></p>
</body>
</html>
