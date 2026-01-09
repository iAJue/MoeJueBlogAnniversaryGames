<?php
declare(strict_types=1);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

session_start();

function load_challenges(string $path): array {
    if (!is_file($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    if (!is_string($raw)) {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function pick_random_challenge(array $challenges): ?array {
    $valid = [];
    foreach ($challenges as $ch) {
        if (!is_array($ch)) {
            continue;
        }
        if (!isset($ch['id'], $ch['file'], $ch['answers'])) {
            continue;
        }
        if (!is_string($ch['id']) || !is_string($ch['file']) || !is_array($ch['answers'])) {
            continue;
        }
        $valid[] = $ch;
    }
    if (!$valid) {
        return null;
    }
    return $valid[random_int(0, count($valid) - 1)];
}

$challenges = load_challenges(__DIR__ . '/challenges.json');
$selected = pick_random_challenge($challenges);
if (!$selected) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Missing who challenges configuration';
    exit;
}

$_SESSION['who_challenge_id'] = $selected['id'];

$file = basename($selected['file']);
$root = dirname(__DIR__, 2);
$path = $root . '/images/who/' . $file;
if (!is_file($path)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    var_dump($challenges);
    echo 'Missing who image file';
    exit;
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = [
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'webp' => 'image/webp',
];
$type = $types[$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $type);
header('X-Who-Id: ' . $selected['id']);
readfile($path);
