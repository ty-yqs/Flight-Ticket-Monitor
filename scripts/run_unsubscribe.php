<?php
// CLI helper to call unsubscribe.php by setting $_GET
if ($argc < 4) {
    echo "Usage: php run_unsubscribe.php <id> <email> <token>\n";
    exit(1);
}
parse_str('id=' . intval($argv[1]) . '&email=' . urlencode($argv[2]) . '&t=' . $argv[3], $_GET);
include __DIR__ . '/../unsubscribe.php';
