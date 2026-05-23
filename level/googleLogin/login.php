<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

$validUsername = "blog10years";
$validPassword = 'a4548011dd44ca756dd5c15e652d6f3b';

try {
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($postData['username']) || !isset($postData['password']) || !isset($postData['captchaData']) || !isset($postData['fingerprint'])) {
        throw new Exception('请求数据不完整');
    }
    
    $username = $postData['username'];
    $password = $postData['password'];
    $captchaData = $postData['captchaData'];
    $fingerprint = $postData['fingerprint'];
    
    // 验证指纹格式
    if (!preg_match('/^[a-f0-9]{32}$/', $fingerprint)) {
        throw new Exception('指纹数据错误');
    }
    
    // 验证验证码
    if (!isset($captchaData['selectedIndices']) || !isset($captchaData['images']) || !isset($captchaData['index'])) {
        throw new Exception('验证码数据不完整');
    }

    // 获取验证码数据
    $dataPath = '../../data/';
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
    
    // 验证账号密码
    if ($password === $validPassword && $username === $validUsername) {
        // 获取用户IP地址
        $userIP = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $userIP = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $userIP = $_SERVER['HTTP_CLIENT_IP'];
        }
        
        // 设置登录session
        $_SESSION['user_logged_in'] = true;
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        $_SESSION['fingerprint'] = $fingerprint; // 保存浏览器指纹
        // 截取浏览器指纹最后两个连续数字作为密码储存起来
        preg_match('/(\d{2})(?!.*\d{2})/', $fingerprint, $m);
        $result = $m[1] ?? '00';
        $_SESSION['password_fragment'] = $result;
        
        // 保存用户数据到JSON文件
        $usersFile = dirname(__DIR__, 2) . '/users.json';
        $users = [];
        
        // 读取现有用户数据
        if (file_exists($usersFile)) {
            $usersData = file_get_contents($usersFile);
            $users = json_decode($usersData, true);
            if (!is_array($users)) {
                $users = [];
            }
        }
        
        // 检查指纹是否已存在
        $existingUserIndex = -1;
        foreach ($users as $index => $user) {
            if ($user['fingerprint'] === $fingerprint) {
                $existingUserIndex = $index;
                break;
            }
        }
        
        if ($existingUserIndex !== -1) {
            // 修改现有用户
            $users[$existingUserIndex]['username'] = $username;
            $users[$existingUserIndex]['password'] = $result;
            $users[$existingUserIndex]['ip_address'] = $userIP;
            $users[$existingUserIndex]['login_time'] = date('Y-m-d H:i:s');
        } else {
            // 生成新的uid（顺序生成）
            $newUID = count($users) + 1;
            
            // 创建新用户记录
            $newUser = [
                'uid' => $newUID,
                'username' => $username,
                'password' => $result,
                'fingerprint' => $fingerprint,
                'ip_address' => $userIP,
                'login_time' => date('Y-m-d H:i:s'),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // 添加到用户数组
            $users[] = $newUser;
        }
        
        // 保存到JSON文件
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

        echo json_encode([
            'success' => true,
            'message' => '登录成功',
            'uid' => $existingUserIndex !== -1 ? $users[$existingUserIndex]['uid'] : $newUID,
            'password_fragment' => $result,
            'redirect' => '/level/2.5/cloudflare-error.html?code=' . $result
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '用户名或密码错误'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
