<?php
// Unsubscribe endpoint: expects GET params `id`, `email` and `t` (token)
if (!isset($_GET['id']) || !isset($_GET['email']) || !isset($_GET['t'])) {
    http_response_code(400);
    echo "Missing parameters.";
    exit;
}

$id = intval($_GET['id']);
$email = trim($_GET['email']);
$token = trim($_GET['t']);

$dbFile = __DIR__ . '/subscriptions.db';
if (!file_exists($dbFile)) {
    echo "Database not found.";
    exit;
}

$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require_once __DIR__ . '/lib/unsubscribe.php';

$stmt = $db->prepare('SELECT * FROM subscriptions WHERE id = :id AND email = :email');
$stmt->execute([':id' => $id, ':email' => $email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    ?>
    <!doctype html>
    <html lang="en">
    <head><meta charset="utf-8"><title>Unsubscribe Failed</title></head>
    <body>
      <h1>Unsubscribe Failed</h1>
      <p>No matching subscription was found or it has already been removed.</p>
      <p><a href="index.php">Return to subscription page</a></p>
    </body>
    </html>
    <?php
    exit;
}

// verify token
if (!verify_unsubscribe_token($row['id'], $row['email'], $row['created_at'] ?? '', $token)) {
    ?>
    <!doctype html>
    <html lang="en">
    <head><meta charset="utf-8"><title>Unsubscribe Failed</title></head>
    <body>
      <h1>Unsubscribe Failed</h1>
      <p>The unsubscribe link is invalid or has expired.</p>
      <p><a href="index.php">Return to subscription page</a></p>
    </body>
    </html>
    <?php
    exit;
}

$del = $db->prepare('DELETE FROM subscriptions WHERE id = :id AND email = :email');
$del->execute([':id' => $id, ':email' => $email]);
?>
<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>Unsubscribed</title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f7f7f7;color:#222;padding:24px}
    .card{max-width:620px;margin:40px auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.06)}
    a.button{display:inline-block;padding:10px 16px;background:#1976d2;color:#fff;text-decoration:none;border-radius:6px}
  </style>
</head>
<body>
  <div class="card">
    <h1>Unsubscribed</h1>
    <p>The following address has been removed from our subscription list: <strong><?php echo htmlspecialchars($email) ?></strong></p>
    <p>If this was a mistake, you can subscribe again on the homepage.</p>
    <p><a class="button" href="index.php">Return Home</a></p>
  </div>
</body>
</html>
