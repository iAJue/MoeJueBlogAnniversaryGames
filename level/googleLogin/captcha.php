<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

$dataPath = './data/';
$captchaFiles = ['kumiko.json', 'male.json', 'moejue.json'];

/**
 * 获取验证码数据
 */
function getCaptchaData() {
    global $dataPath, $captchaFiles;
    
    try {
        $index = isset($_GET['index']) ? intval($_GET['index']) : -1;
        
        if ($index >= 0 && $index < count($captchaFiles)) {
            $jsonFile = $dataPath . $captchaFiles[$index];
        } else {
            $randomFile = $captchaFiles[array_rand($captchaFiles)];
            $jsonFile = $dataPath . $randomFile;
        }
        
        if (!file_exists($jsonFile)) {
            throw new Exception('验证码数据文件不存在');
        }
        
        $jsonData = file_get_contents($jsonFile);
        $data = json_decode($jsonData, true);
        
        if (!$data) {
            throw new Exception('验证码数据格式错误');
        }
        
        // 分离正确和错误的图片
        $correctImages = array_filter($data['images'], function($img) {
            return isset($img['correct']) && $img['correct'] === true;
        });
        $wrongImages = array_filter($data['images'], function($img) {
            return !isset($img['correct']) || $img['correct'] !== true;
        });
        
        // 随机决定正确图片的数量（1-4张）
        $correctCount = rand(1, 5);
        $wrongCount = 9 - $correctCount;
        
        // 打乱正确和错误图片数组
        shuffle($correctImages);
        shuffle($wrongImages);
        
        // 选择随机数量的正确和错误图片
        $selectedCorrect = array_slice($correctImages, 0, $correctCount);
        $selectedWrong = array_slice($wrongImages, 0, $wrongCount);
        
        // 合并并打乱所有选中的图片
        $selectedImages = array_merge($selectedCorrect, $selectedWrong);
        shuffle($selectedImages);
        
        // 记录正确答案的索引
        $correctIndices = [];
        foreach ($selectedImages as $index => $image) {
            if (isset($image['correct']) && $image['correct'] === true) {
                $correctIndices[] = $index;
            }
        }
        $_SESSION['correct_indices'] = $correctIndices;
        
        // 移除图片数据中的correct标记
        $cleanImages = array_map(function($img) {
            return ['src' => $img['src']];
        }, $selectedImages);
        
        // 构建返回数据
        $response = [
            'success' => true,
            'data' => [
                'title' => $data['title'],
                'cover' => $data['cover'],
                'images' => $cleanImages,
                'rightTip' => $data['rightTip'],
                'wrongTip' => $data['wrongTip']
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * 验证用户选择的答案
 */
function verifyCaptcha() {
    try {
        $postData = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($postData['selectedIndices'])) {
            throw new Exception('请求数据不完整');
        }
        
        if (!isset($_SESSION['correct_indices'])) {
            throw new Exception('验证码会话已过期');
        }
        
        $selectedIndices = $postData['selectedIndices'];
        $correctIndices = $_SESSION['correct_indices'];
        $isValid = count($selectedIndices) === count($correctIndices) &&
                  empty(array_diff($selectedIndices, $correctIndices)) &&
                  empty(array_diff($correctIndices, $selectedIndices));
        
        echo json_encode([
            'success' => true,
            'valid' => $isValid
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'get':
        getCaptchaData();
        break;
    case 'verify':
        verifyCaptcha();
        break;
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => '无效的请求'
        ]);
} 