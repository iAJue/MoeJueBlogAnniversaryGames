<?php
header('Content-Type: application/json; charset=utf-8');

define('USERS_FILE', dirname(__DIR__, 2) . '/users.json');

session_start();

function jsonResponse($success, $message, $statusCode = 200, array $extra = [])
{
    http_response_code($statusCode);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

function loadUsers()
{
    if (!file_exists(USERS_FILE)) {
        throw new Exception('用户数据文件不存在');
    }

    $usersData = file_get_contents(USERS_FILE);
    $users = json_decode($usersData, true);

    if (!is_array($users)) {
        throw new Exception('用户数据格式错误');
    }

    return $users;
}

function findUserIndexByFingerprint(array $users, $fingerprint)
{
    foreach ($users as $index => $user) {
        if (($user['fingerprint'] ?? null) === $fingerprint) {
            return $index;
        }
    }

    return -1;
}

function saveUsers(array $users)
{
    $result = file_put_contents(
        USERS_FILE,
        json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );

    if ($result === false) {
        throw new Exception('保存用户数据失败');
    }
}

// 验证用户是否存在且合法的函数
function verifyUserValidity($fingerprint, $passwordFragment)
{
    $users = loadUsers();

    // 根据指纹查找用户
    $userIndex = findUserIndexByFingerprint($users, $fingerprint);
    if ($userIndex === -1) {
        throw new Exception('用户不存在');
    }

    $user = $users[$userIndex];

    return $user;
}

// 生成优惠券码的函数
function generateCouponCode()
{
    // 生成一个随机的优惠券码，格式：SAFE-XXXX-XXXX
    $part1 = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
    $part2 = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));

    return "SAFE-{$part1}-{$part2}";
}

// 保存优惠券到用户数据的函数
function saveCouponToUser($fingerprint, $couponCode)
{
    $users = loadUsers();

    // 根据指纹查找用户
    $userIndex = findUserIndexByFingerprint($users, $fingerprint);
    if ($userIndex === -1) {
        throw new Exception('未找到对应的用户');
    }

    // 保存优惠券码
    $users[$userIndex]['coupon_code'] = $couponCode;
    $users[$userIndex]['coupon_time'] = date('Y-m-d H:i:s');

    // 保存回users.json
    saveUsers($users);
}

if (!isset($_SESSION['password_fragment'])) {
    jsonResponse(false, '未检测到有效的会话，请按顺序完成关卡');
}

if (($_SESSION['puzzle_completed'] ?? false) !== true) {
    jsonResponse(false, '请按顺序完成关卡');
}

// 验证请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, '请求方法不允许', 405);
}

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data)) {
    $data = [];
}

// 验证密码是否存在
if (!isset($data['password'])) {
    jsonResponse(false, '缺少密码参数', 400);
}

// 验证用户合法性
if (!isset($_SESSION['fingerprint'])) {
    jsonResponse(false, '未检测到浏览器指纹', 401);
}

try {
    $user = verifyUserValidity($_SESSION['fingerprint'], $_SESSION['password_fragment']);
} catch (Exception $e) {
    jsonResponse(false, '用户验证失败: ' . $e->getMessage(), 401);
}
$password = (string) $data['password'];


// 验证密码
if ($password !== trim($user['password'] . '69')) {
    // 密码错误
    jsonResponse(false, '密码错误，请重试', 401);
}

// 密码正确，生成优惠券码
$couponCode = generateCouponCode();

// 保存到用户数据
try {
    saveCouponToUser($_SESSION['fingerprint'], $couponCode);
} catch (Exception $e) {
    // 记录错误但不中断流程
    error_log('保存优惠券到用户数据失败: ' . $e->getMessage());
}

jsonResponse(true, '密码正确！', 200, [
    'couponCode' => $couponCode,
    'username' => $user['username'] ?? '陌生人',
    'url' => '/level/last/note.html'
]);
