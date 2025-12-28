<?php
// Helper functions for unsubscribe token generation and verification
// Usage: include_once __DIR__ . '/lib/unsubscribe.php';

function get_unsubscribe_secret() {
    // priority: env var UNSUBSCRIBE_SECRET, then persisted file in project root, else generate and persist
    $env = getenv('UNSUBSCRIBE_SECRET');
    if ($env && $env !== false) return $env;

    $root = dirname(__DIR__);
    $keyFile = $root . '/.unsubscribe_secret';
    if (file_exists($keyFile)) return trim(@file_get_contents($keyFile));

    try {
        $s = bin2hex(random_bytes(32));
        @file_put_contents($keyFile, $s);
        return $s;
    } catch (Exception $e) {
        return 'fallback_secret_change_me';
    }
}

function make_unsubscribe_token($id, $email, $created_at) {
    $secret = get_unsubscribe_secret();
    $data = $id . '|' . $email . '|' . $created_at;
    $raw = hash_hmac('sha256', $data, $secret, true);
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}

function verify_unsubscribe_token($id, $email, $created_at, $token) {
    $expected = make_unsubscribe_token($id, $email, $created_at);
    return hash_equals($expected, $token);
}
