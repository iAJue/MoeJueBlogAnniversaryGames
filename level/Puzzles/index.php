<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function send_json(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function load_solution_from_json(string $path): ?array {
    if (!is_file($path)) {
        return null;
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return null;
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return null;
    }
    return $data;
}

function load_expected_solution(): array {
    $solutionJson = __DIR__ . '/solution.json';
    $solution = load_solution_from_json($solutionJson);
    if (is_array($solution)) {
        return $solution;
    }

    send_json(500, [
        'ok' => false,
        'message' => '服务器未配置拼图答案：请在 level/puzzles/solution.json 配置 rows/cols/hashes。',
    ]);
}

function normalize_expected_solution(array $solution): array {
    $rows = isset($solution['rows']) ? (int)$solution['rows'] : 0;
    $cols = isset($solution['cols']) ? (int)$solution['cols'] : 0;

    $hashesRaw = $solution['hashes'] ?? null;
    if (!is_array($hashesRaw)) {
        send_json(500, ['ok' => false, 'message' => '服务器拼图答案格式错误：hashes 必须为数组']);
    }
    $hashes = [];
    foreach ($hashesRaw as $h) {
        if (!is_string($h)) {
            send_json(500, ['ok' => false, 'message' => '服务器拼图答案格式错误：hashes 元素必须为字符串']);
        }
        $h = strtolower(trim($h));
        if (!preg_match('/^[a-f0-9]{64}$/', $h)) {
            send_json(500, ['ok' => false, 'message' => '服务器拼图答案格式错误：hashes 元素必须为 64 位十六进制 SHA-256']);
        }
        $hashes[] = $h;
    }

    if ($rows <= 0 || $cols <= 0) {
        send_json(500, ['ok' => false, 'message' => '服务器拼图答案格式错误：rows/cols 必须为正整数']);
    }
    if (count($hashes) !== $rows * $cols) {
        send_json(500, ['ok' => false, 'message' => '服务器拼图答案格式错误：hashes 数量必须等于 rows*cols']);
    }

    return [
        'rows' => $rows,
        'cols' => $cols,
        'hashes' => $hashes,
        'redirect' => '../last/safe.html',
        'preview' => '/level/bomb/bg.jpg',
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    send_json(405, ['ok' => false, 'message' => '仅支持 POST(JSON)']);
}

$payload = read_json_body();
if (!$payload) {
    send_json(400, ['ok' => false, 'message' => '请求体必须为 JSON']);
}

$rows = isset($payload['rows']) ? (int)$payload['rows'] : 0;
$cols = isset($payload['cols']) ? (int)$payload['cols'] : 0;
$hashes = $payload['hashes'] ?? null;

if ($rows <= 0 || $cols <= 0) {
    send_json(400, ['ok' => false, 'message' => 'rows/cols 必须为正整数']);
}
if (!is_array($hashes)) {
    send_json(400, ['ok' => false, 'message' => 'hashes 必须为数组']);
}
if (count($hashes) !== $rows * $cols) {
    send_json(400, ['ok' => false, 'message' => 'hashes 数量必须等于 rows*cols']);
}

$normalized = [];
foreach ($hashes as $h) {
    if (!is_string($h)) {
        send_json(400, ['ok' => false, 'message' => 'hashes 元素必须为字符串']);
    }
    $h = strtolower(trim($h));
    if (!preg_match('/^[a-f0-9]{64}$/', $h)) {
        send_json(400, ['ok' => false, 'message' => 'hashes 元素必须为 64 位十六进制 SHA-256']);
    }
    $normalized[] = $h;
}

$expected = normalize_expected_solution(load_expected_solution());

if ($rows !== $expected['rows'] || $cols !== $expected['cols']) {
    send_json(400, ['ok' => false, 'message' => '网格尺寸不正确']);
}

if (!hash_equals(implode("\n", $expected['hashes']), implode("\n", $normalized))) {
    send_json(403, ['ok' => false, 'message' => '拼错了,再试试吧！']);
}

send_json(200, [
    'ok' => true,
    'message' => '点击按钮进入最后一关',
    'redirect' => $expected['redirect'],
    'preview' => $expected['preview'],
]);
