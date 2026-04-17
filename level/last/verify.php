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

// 验证用户是否存在且合法的函数
function verifyUserValidity($fingerprint, $passwordFragment) {
    $usersFile = __DIR__ . '/../../users.json';
    if (!file_exists($usersFile)) {
        throw new Exception('用户数据文件不存在');
    }
    
    $usersData = file_get_contents($usersFile);
    $users = json_decode($usersData, true);
    
    if (!is_array($users)) {
        throw new Exception('用户数据格式错误');
    }
    
    // 根据指纹查找用户
    foreach ($users as $user) {
        if ($user['fingerprint'] === $fingerprint) {
            // 检查密码字段的开头两位是否等于password_fragment
            $passwordStart = substr($user['password'], 0, 2);
            if ($passwordStart !== $passwordFragment) {
                throw new Exception('用户数据验证失败');
            }
            return $user;
        }
    }
    
    throw new Exception('用户不存在');
}

// 生成优惠券码的函数
function generateCouponCode() {
    // 生成一个随机的优惠券码，格式：SAFE-XXXX-XXXX
    $part1 = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
    $part2 = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
    $couponCode = "SAFE-{$part1}-{$part2}";
    return $couponCode;
}

// 保存优惠券到用户数据的函数
function saveCouponToUser($fingerprint, $couponCode) {
    $usersFile = __DIR__ . '/../../users.json';
    if (!file_exists($usersFile)) {
        throw new Exception('用户数据文件不存在');
    }
    
    $usersData = file_get_contents($usersFile);
    $users = json_decode($usersData, true);
    
    if (!is_array($users)) {
        throw new Exception('用户数据格式错误');
    }
    
    // 根据指纹查找用户
    $userFound = false;
    foreach ($users as &$user) {
        if ($user['fingerprint'] === $fingerprint) {
            // 保存优惠券码
            $user['coupon_code'] = $couponCode;
            $user['coupon_time'] = date('Y-m-d H:i:s');
            $userFound = true;
            break;
        }
    }
    
    if (!$userFound) {
        throw new Exception('未找到对应的用户');
    }
    
    // 保存回users.json
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
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

// 验证用户合法性
if (!isset($_SESSION['fingerprint'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '未检测到浏览器指纹'
    ]);
    exit;
}

try {
    $user = verifyUserValidity($_SESSION['fingerprint'], $_SESSION['password_fragment']);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => '用户验证失败: ' . $e->getMessage()
    ]);
    exit;
}

// 验证密码
if ($password === CORRECT_PASSWORD) {
    // 密码正确，生成优惠券码
    $couponCode = generateCouponCode();
    
    // 保存到用户数据
    if (isset($_SESSION['fingerprint'])) {
        try {
            saveCouponToUser($_SESSION['fingerprint'], $couponCode);
        } catch (Exception $e) {
            // 记录错误但不中断流程
            error_log('保存优惠券到用户数据失败: ' . $e->getMessage());
        }
    }
    
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
