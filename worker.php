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

    $body = "<h3>{$from} → {$to} {$date} Flight Prices (ascending)</h3>";
    $body .= "<ol>";
    foreach ($prices as $p) {
        $body .= '<li>¥' . number_format($p, 2) . '</li>';
    }
    $body .= "</ol>";
    $body .= "<p>Data source: <a href='{$url}'>{$url}</a></p>";

    $subject = "Flight Monitor: {$from} → {$to} {$date} - Lowest ¥" . number_format($prices[0],2);
    $ok = send_email($email, $subject, $body);
    echo ($ok ? "Email sent to {$email}\n" : "Email sending failed: {$email}\n");
}

