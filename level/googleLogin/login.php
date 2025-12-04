<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// 正确的密码//第一关就固定账户和密码减少难度不随机了
$validPassword = 'a4548011dd44ca756dd5c15e652d6f3b';

try {
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($postData['password']) || !isset($postData['captchaData']) || !isset($postData['fingerprint'])) {
        throw new Exception('请求数据不完整');
    }
    
    $password = $postData['password'];
    $captchaData = $postData['captchaData'];
    $fingerprint = $postData['fingerprint'];
    
    // 验证指纹格式
    if (!preg_match('/^[a-f0-9]{32}$/', $fingerprint)) {
        throw new Exception('浏览器指纹格式错误');
    }
    
    // 验证验证码
    if (!isset($captchaData['selectedIndices']) || !isset($captchaData['images']) || !isset($captchaData['index'])) {
        throw new Exception('验证码数据不完整');
    }

    // 获取验证码数据
    $dataPath = '/data/';
    $captchaFiles = ['kumiko.json', 'male.json', 'moejue.json'];
    $index = $captchaData['index'];
    
    if ($index < 0 || $index >= count($captchaFiles)) {
        throw new Exception('无效的验证码索引');
    }
    
    $jsonFile = $dataPath . $captchaFiles[$index];
    if (!file_exists($jsonFile)) {
        throw new Exception('验证码数据文件不存在');
    }
    
    $jsonData = file_get_contents($jsonFile);
    $data = json_decode($jsonData, true);
    
    if (!$data) {
        throw new Exception('验证码数据格式错误');
    }
    
    // 获取正确的图片索引
    $correctIndices = [];
    foreach ($captchaData['images'] as $index => $image) {
        foreach ($data['images'] as $originalImage) {
            if ($image['src'] === $originalImage['src'] && isset($originalImage['correct']) && $originalImage['correct'] === true) {
                $correctIndices[] = $index;
                break;
            }
        }
    }
    
    // 验证用户选择的图片是否正确
    $selectedIndices = $captchaData['selectedIndices'];
    $isValid = count($selectedIndices) === count($correctIndices) &&
              empty(array_diff($selectedIndices, $correctIndices)) &&
              empty(array_diff($correctIndices, $selectedIndices));
    
    if (!$isValid) {
        echo json_encode([
            'success' => false,
            'message' => '验证码验证失败'
        ]);
        exit;
    }
    
    // 验证密码
    if ($password === $validPassword) {
        // 设置登录session
        $_SESSION['user_logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['fingerprint'] = $fingerprint; // 保存浏览器指纹

        echo json_encode([
            'success' => true,
            'message' => '登录成功',
            'redirect' => '/level/three/start.html'
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