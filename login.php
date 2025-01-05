<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 正确的密码//第一关就固定账户和密码减少难度不随机了
$validPassword = 'a4548011dd44ca756dd5c15e652d6f3b';

try {
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($postData['password'])) {
        throw new Exception('请求数据不完整');
    }
    
    $password = $postData['password'];
    
    if ($password === $validPassword) {
        echo json_encode([
            'success' => true,
            'message' => '登录成功',
            'redirect' => 'top2.php'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '密码错误'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 