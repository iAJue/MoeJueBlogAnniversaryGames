<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
if (isset($_SESSION['password_fragment'])) {
    define('CORRECT_PASSWORD', $_SESSION['password_fragment']. '69');
}else {
    echo json_encode([
        'success' => false,
        'message' => '未检测到有效的会话，请按顺序完成关卡'
    ]);
    exit;
}

// 生成优惠券码的函数
function generateCouponCode() {
    // 生成一个随机的优惠券码，格式：SAFE-XXXX-XXXX
    $part1 = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
    $part2 = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
    // 保存一份到本地文件
    $couponCode = "SAFE-{$part1}-{$part2}";
    file_put_contents(__DIR__ . '/coupons.txt', $couponCode . PHP_EOL, FILE_APPEND);
    return $couponCode;
}

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 验证请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => '请求方法不允许'
    ]);
    exit;
}

// 验证密码是否存在
if (!isset($data['password'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => '缺少密码参数'
    ]);
    exit;
}

$password = $data['password'];

// 验证密码
if ($password === CORRECT_PASSWORD) {
    // 密码正确，生成优惠券码
    $couponCode = generateCouponCode();
    
    echo json_encode([
        'success' => true,
        'message' => '密码正确！',
        'couponCode' => $couponCode,
        'url' => '/level/last/note.html'
    ]);
} else {
    // 密码错误
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '密码错误，请重试'
    ]);
}
?>
