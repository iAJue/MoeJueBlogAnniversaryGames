<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

try {
    // 检查是否登录
    if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
        throw new Exception('用户未登录');
    }

    // 获取浏览器指纹
    if (!isset($_SESSION['fingerprint'])) {
        throw new Exception('未找到浏览器指纹');
    }
    
    $fingerprint = $_SESSION['fingerprint'];

    // 获取POST数据
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($postData['number'])) {
        throw new Exception('数字参数缺失');
    }
    
    $number = $postData['number'];
    
    // 读取users.json
    $usersFile = dirname(__DIR__, 2) . '/users.json';

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
            // 将数字追加到password字段后面
            $user['password'] .= $number;
            $userFound = true;
            break;
        }
    }
    
    if (!$userFound) {
        throw new Exception('未找到对应的用户');
    }
    
    // 保存回users.json
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    
    echo json_encode([
        'success' => true,
        'message' => '保存成功',
        'number' => $number
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
