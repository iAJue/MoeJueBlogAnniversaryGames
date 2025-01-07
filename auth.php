<?php
session_start();

function checkLogin() {
    // 检查是否登录
    if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => '请先登录',
            'redirect' => 'login.html'
        ]);
        exit;
    }

    // 检查浏览器指纹
    if (isset($_SERVER['HTTP_X_FINGERPRINT'])) {
        $currentFingerprint = $_SERVER['HTTP_X_FINGERPRINT'];
        if (!isset($_SESSION['fingerprint']) || $_SESSION['fingerprint'] !== $currentFingerprint) {
            session_destroy();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => '登录已失效，请重新登录',
                'redirect' => 'login.html'
            ]);
            exit;
        }
    }

    // 检查登录时间是否超过2小时
    if (isset($_SESSION['login_time'])) {
        $currentTime = time();
        $loginTime = $_SESSION['login_time'];
        $timeLimit = 2 * 60 * 60; // 2小时

        if ($currentTime - $loginTime > $timeLimit) {
            // 清除session
            session_destroy();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => '登录已过期，请重新登录',
                'redirect' => 'login.html'
            ]);
            exit;
        }
    }

    // 更新登录时间
    $_SESSION['login_time'] = time();
} 