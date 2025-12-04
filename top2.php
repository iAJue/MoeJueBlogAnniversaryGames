<?php
session_start();

// 检查用户是否已登录
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // 未登录，重定向到登录页
    header('Location: login.html');
    exit;
}

// 已登录，重定向到第三关
header('Location: start.html');
exit;
?>
