<?php
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$md5 = '5520fac317548ed609483e001c6834c9';

if (empty($data)) {
    $response['success'] = false;
    $response['message'] = '你似乎什么都没填呢？';
}

if($data['answer'] == $md5){
    $response = [
        'success' => true,
        'message' => '验证通过！完美匹配！',
        'data' => $data,
        'redirect' => '/level/six/artwork.php'
    ];
}else{
    $response = [
        'success' => false,
        'message' => '验证失败！',
        'data' => $data
    ];
}

echo json_encode($response);
?>
