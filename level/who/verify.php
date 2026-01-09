<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_start();

function send_json(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (is_array($data)) {
        return $data;
    }

    $enc = mb_detect_encoding($raw, ['UTF-8', 'UTF-16LE', 'UTF-16BE'], true);
    if ($enc && $enc !== 'UTF-8') {
        $converted = mb_convert_encoding($raw, 'UTF-8', $enc);
        $data = json_decode($converted, true);
        if (is_array($data)) {
            return $data;
        }
    }

    return [];
}

function normalize_answer(string $value): string {
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    $value = mb_strtolower($value, 'UTF-8');
    $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';
    return $value;
}

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

function normalize_challenges(array $challenges): array {
    $byId = [];
    foreach ($challenges as $ch) {
        if (!is_array($ch)) {
            continue;
        }
        $id = $ch['id'] ?? null;
        $answers = $ch['answers'] ?? null;
        if (!is_string($id) || !is_array($answers)) {
            continue;
        }
        $norm = [];
        foreach ($answers as $a) {
            if (!is_string($a)) {
                continue;
            }
            $na = normalize_answer($a);
            if ($na !== '') {
                $norm[] = $na;
            }
        }
        if ($norm) {
            $byId[$id] = $norm;
        }
    }
    return $byId;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    send_json(405, ['success' => false, 'message' => '仅支持 POST']);
}

$answerRaw = '';
$body = read_json_body();
if (isset($body['answer']) && is_string($body['answer'])) {
    $answerRaw = $body['answer'];
} elseif (isset($_POST['answer']) && is_string($_POST['answer'])) {
    $answerRaw = $_POST['answer'];
}

$answer = normalize_answer($answerRaw);
if ($answer === '') {
    send_json(400, ['success' => false, 'message' => '请输入答案']);
}

$challengeId = $_SESSION['who_challenge_id'] ?? '';
$challengeId = is_string($challengeId) ? $challengeId : '';

$challenges = normalize_challenges(load_challenges(__DIR__ . '/challenges.json'));
if (!$challenges) {
    send_json(500, ['success' => false, 'message' => '服务器未配置题库']);
}

$accepted = [];
if ($challengeId !== '' && isset($challenges[$challengeId])) {
    $accepted = $challenges[$challengeId];
} else {
    send_json(400, ['success' => false, 'message' => '请先刷新题目图片']);
}

$isCorrect = false;
foreach ($accepted as $candidate) {
    if ($candidate !== '' && hash_equals($candidate, $answer)) {
        $isCorrect = true;
        break;
    }
}

if (!$isCorrect) {
    send_json(403, ['success' => false, 'message' => '不对哦，再想想']);
}

send_json(200, [
    'success' => true,
    'message' => '回答正确！',
    'redirect' => '/level/puzzles/index.html',
]);
