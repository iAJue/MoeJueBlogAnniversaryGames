<?php
header('Content-Type: application/json');

// 获取 POST 数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 模拟验证过程
// 在实际游戏中，这里会验证数据的正确性
// 但既然是"简单的游戏"，我们假设用户只要能看出来两张图一样，填什么都是对的
// 或者至少填了一些数据

$response = [
    'success' => true,
    'message' => '验证通过！完美匹配！',
    'data' => $data // 将用户提交的数据原样返回，作为确认
];

// 可以添加一些彩蛋逻辑
if (empty($data)) {
    $response['success'] = false;
    $response['message'] = '你似乎什么都没填呢？';
}

echo json_encode($response);
?>
