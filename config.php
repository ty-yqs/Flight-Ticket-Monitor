<?php
// Configuration loader: load .env from project root first (if present), then return config array

function load_dotenv($path) {
    if (!is_readable($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        // Trim possible surrounding quotes
        $val = trim($val, "\"' ");
        putenv("{$key}={$val}");
        $_ENV[$key] = $val;
        $_SERVER[$key] = $val;
    }
}

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    load_dotenv($envFile);
}

function env($key, $default = null) {
    $v = getenv($key);
    if ($v === false) return $default;
    return $v;
}

$config = [
    'smtp' => [
        'host' => env('SMTP_HOST', 'smtp.example.com'),
        'port' => intval(env('SMTP_PORT', 587)),
        'user' => env('SMTP_USER', ''),
        'pass' => env('SMTP_PASS', ''),
        'secure' => env('SMTP_SECURE', 'tls'),
        'from' => env('SMTP_FROM', env('SMTP_USER', 'no-reply@example.com')),
        'from_name' => env('SMTP_FROM_NAME', 'Flight Monitor'),
    ],
    'paths' => [
        'db_file' => __DIR__ . '/' . env('DB_FILE', 'subscriptions.db'),
        'fetcher_script' => __DIR__ . '/' . env('FETCHER_SCRIPT', 'fetcher.js'),
        'node_path' => env('NODE_PATH', 'node'),
    ],
];

return $config;
