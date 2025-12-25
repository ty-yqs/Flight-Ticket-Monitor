<?php
// Interactive generator for .env (reads keys/defaults from .env.example)
// Usage: php generate_env.php

function prompt($message, $default = '') {
    if (function_exists('readline')) {
        $line = readline($message . ($default !== '' ? " [{$default}]" : '') . ': ');
    } else {
        echo $message . ($default !== '' ? " [{$default}]" : '') . ': ';
        $line = trim(fgets(STDIN));
    }
    if ($line === '') return $default;
    return $line;
}

$example = __DIR__ . '/.env.example';
if (!file_exists($example)) {
    echo ".env.example not found.\n";
    exit(1);
}

$lines = file($example, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$out = [];
foreach ($lines as $line) {
    $trim = trim($line);
    if ($trim === '' || strpos($trim, '#') === 0) {
        // keep comments and blank lines
        $out[] = $line;
        continue;
    }
    if (strpos($line, '=') === false) { $out[] = $line; continue; }
    list($k, $v) = explode('=', $line, 2);
    $k = trim($k);
    $v = trim($v);
    $v = trim($v, "\"'");
    $answer = prompt("Enter value for {$k}", $v);
    $out[] = $k . '=' . $answer;
}

$envFile = __DIR__ . '/.env';
file_put_contents($envFile, implode("\n", $out) . "\n");
echo ".env generated: {$envFile}\n";
