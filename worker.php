<?php
// Hourly worker script: use a headless browser to fetch pages and send emails via PHPMailer + SMTP
// Run from CLI: php worker.php

$dbFile = __DIR__ . '/subscriptions.db';
if (!file_exists($dbFile)) {
    echo "Database not found; please run db_init.php first to create the database.\n";
    exit(1);
}

$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$subs = $db->query('SELECT * FROM subscriptions')->fetchAll(PDO::FETCH_ASSOC);
if (!$subs) {
    echo "No subscription records.\n";
    exit(0);
}

// Load configuration from config.php (which itself will load .env if present)
$configFile = __DIR__ . '/config.php';
if (file_exists($configFile)) {
    $cfg = include $configFile;
    $smtpCfg = $cfg['smtp'] ?? [];
    $pathsCfg = $cfg['paths'] ?? [];
} else {
    $smtpCfg = [
        'host' => getenv('SMTP_HOST') ?: 'smtp.example.com',
        'port' => intval(getenv('SMTP_PORT') ?: 587),
        'user' => getenv('SMTP_USER') ?: '',
        'pass' => getenv('SMTP_PASS') ?: '',
        'secure' => getenv('SMTP_SECURE') ?: 'tls',
        'from' => getenv('SMTP_FROM') ?: (getenv('SMTP_USER') ?: 'no-reply@example.com'),
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'Flight Monitor',
    ];
    $pathsCfg = [
        'fetcher_script' => __DIR__ . '/fetcher.js',
        'node_path' => 'node',
        'db_file' => $dbFile,
    ];
}

$SMTP_HOST = $smtpCfg['host'] ?? 'smtp.example.com';
$SMTP_PORT = $smtpCfg['port'] ?? 587;
$SMTP_USER = $smtpCfg['user'] ?? '';
$SMTP_PASS = $smtpCfg['pass'] ?? '';
$SMTP_SECURE = $smtpCfg['secure'] ?? 'tls';
$SMTP_FROM = $smtpCfg['from'] ?? ($SMTP_USER ?: 'no-reply@flight-monitor.local');
$SMTP_FROM_NAME = $smtpCfg['from_name'] ?? 'Flight Monitor';

$FETCHER_SCRIPT = $pathsCfg['fetcher_script'] ?? (__DIR__ . '/fetcher.js');
$NODE_PATH = $pathsCfg['node_path'] ?? 'node';

// Use Node + Puppeteer to fetch rendered HTML (requires node and fetcher.js)
function fetch_html($url) {
    global $FETCHER_SCRIPT, $NODE_PATH;
    $script = $FETCHER_SCRIPT;
    if (!file_exists($script)) return ['error' => 'fetcher.js not found'];
    $cmd = escapeshellcmd($NODE_PATH) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($url) . ' 2>&1';
    exec($cmd, $out, $ret);
    $output = implode("\n", $out);
    if ($ret !== 0) {
        return ['error' => trim($output) ?: 'node fetch error'];
    }
    return ['html' => $output];
}

function parse_prices($html) {
    $prices = [];
    if (preg_match_all('/[¥￥]\s*([\d,]+(?:\.\d+)?)/u', $html, $m)) {
        foreach ($m[1] as $p) $prices[] = floatval(str_replace(',', '', $p));
    }
    if (preg_match_all('/([\d,]+(?:\.\d+)?)\s*(?:CNY|RMB)/i', $html, $m2)) {
        foreach ($m2[1] as $p) $prices[] = floatval(str_replace(',', '', $p));
    }
    if (empty($prices)) {
        if (preg_match_all('/\b(\d{3,6}(?:\.\d+)?)\b/', $html, $m3)) {
            foreach ($m3[1] as $p) $prices[] = floatval(str_replace(',', '', $p));
        }
    }
    $prices = array_filter($prices, function($v){return $v>0;});
    sort($prices, SORT_NUMERIC);
    return array_values(array_unique($prices));
}

function send_email($to, $subject, $htmlBody) {
    global $SMTP_HOST, $SMTP_PORT, $SMTP_USER, $SMTP_PASS, $SMTP_SECURE, $SMTP_FROM, $SMTP_FROM_NAME;
    $composerAutoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($composerAutoload)) {
        error_log('PHPMailer autoload not found. Please run `composer install` in project root.');
        return false;
    }
        require_once $composerAutoload;

        /*
         PHPMailer + SMTP example notes:

         - Typical ports and encryption:
             * 587 + STARTTLS (`SMTPSecure = 'tls'`) for most SMTP providers
             * 465 + SSL (`SMTPSecure = 'ssl'`) for SMTPS
             * 25 sometimes without encryption (not recommended)

         - For Gmail: enable "App passwords" or use OAuth2. Plain username/password may be blocked.

         - Example usage (already used below):
                 $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                 $mail->isSMTP();
                 $mail->Host = $SMTP_HOST;            // SMTP server
                 $mail->SMTPAuth = true;              // enable SMTP auth
                 $mail->Username = $SMTP_USER;        // SMTP username
                 $mail->Password = $SMTP_PASS;        // SMTP password or app password
                 $mail->SMTPSecure = $SMTP_SECURE;    // 'tls' or 'ssl'
                 $mail->Port = (int)$SMTP_PORT;       // TCP port to connect to

         - Debugging: set `$mail->SMTPDebug = 2;` for verbose output (remove in production).

         - If you encounter certificate verification errors on internal SMTP servers,
             you can set `$mail->SMTPOptions` to allow self-signed certs (use with caution):

                 $mail->SMTPOptions = [
                     'ssl' => [
                         'verify_peer' => false,
                         'verify_peer_name' => false,
                         'allow_self_signed' => true
                     ]
                 ];

         - Timeout can be adjusted with `$mail->Timeout = 30;` (seconds).
        */

        try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = $SMTP_USER;
        $mail->Password = $SMTP_PASS;
        if (!empty($SMTP_SECURE)) $mail->SMTPSecure = $SMTP_SECURE;
        $mail->Port = (int)$SMTP_PORT;
        $mail->setFrom($SMTP_FROM, $SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->CharSet = 'UTF-8';
        return $mail->send();
    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $e->getMessage());
        return false;
    }
}

// --- Unsubscribe token helpers ---
// Use centralized helpers
require_once __DIR__ . '/lib/unsubscribe.php';

foreach ($subs as $s) {
    $from = $s['from_airport'];
    $to = $s['to_airport'];
    $date = $s['date'];
    $email = $s['email'];

    $url = sprintf('https://flights.ctrip.com/itinerary/oneway/%s-%s?date=%s', rawurlencode($from), rawurlencode($to), rawurlencode($date));
    echo "Fetching: $url -> Recipient: $email\n";
    $res = fetch_html($url);
    if (isset($res['error'])) {
        echo "Request failed: " . $res['error'] . "\n";
        continue;
    }
    $html = $res['html'];
    $prices = parse_prices($html);

    if (empty($prices)) {
        $body = "<p>Could not parse prices from the page; the page may have changed or require more advanced parsing. Please check URL: <a href='{$url}'>{$url}</a></p>";
        $subject = "Flight Monitor: {$from} → {$to} {$date} (no prices parsed)";
        send_email($email, $subject, $body);
        echo "No prices parsed; notification email sent to $email\n";
        continue;
    }

    $token = make_unsubscribe_token($s['id'], $email, $s['created_at'] ?? '');
    $unsubscribeUrl = 'unsubscribe.php?id=' . intval($s['id']) . '&email=' . rawurlencode($email) . '&t=' . rawurlencode($token);

    $body = "<!doctype html><html><head><meta charset=\"utf-8\"><title>Flight Prices</title>";
    $body .= "<style>body{font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;color:#222;margin:0;padding:24px} .card{max-width:680px;margin:20px auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 6px 24px rgba(15,23,42,.08)} h3{margin:0 0 12px 0;font-size:18px;color:#111} table{width:100%;border-collapse:collapse;margin-top:12px} td,th{padding:10px;border-bottom:1px solid #eef2f7;text-align:left} .price{font-weight:600;color:#0b6efd} .footer{font-size:13px;color:#666;margin-top:14px} .btn{display:inline-block;background:#e53935;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none}</style>";
    $body .= "</head><body><div class=\"card\">";
    $body .= "<h3>" . htmlspecialchars("{$from} → {$to} {$date}") . "</h3>";
    $body .= "<table><thead><tr><th>Rank</th><th>Price</th></tr></thead><tbody>";
    $rank = 1;
    foreach ($prices as $p) {
        $body .= '<tr><td>' . $rank++ . '</td><td class="price">¥' . number_format($p, 2) . '</td></tr>';
    }
    $body .= "</tbody></table>";
    $body .= "<p class=\"footer\">Data source: <a href='" . htmlspecialchars($url) . "'>" . htmlspecialchars($url) . "</a></p>";
    $body .= "<p class=\"footer\">If you no longer want these emails, you can <a class=\"btn\" href='" . htmlspecialchars($unsubscribeUrl) . "'>Unsubscribe</a></p>";
    $body .= "</div></body></html>";

    $subject = "Flight Monitor: {$from} → {$to} {$date} - Lowest ¥" . number_format($prices[0],2);
    $ok = send_email($email, $subject, $body);
    echo ($ok ? "Email sent to {$email}\n" : "Email sending failed: {$email}\n");
}

