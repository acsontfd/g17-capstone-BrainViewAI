<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['image'])) {
        throw new Exception('No image data received from front-end');
    }

    $flaskUrl = 'http://127.0.0.1:5000/predict';
    $postData = ['image_base64' => $data['image']];

    $ch = curl_init($flaskUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $flaskResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Flask API returned HTTP ' . $httpCode . ': ' . $flaskResponse);
    }

    $respData = json_decode($flaskResponse, true);
    if (!$respData['success']) {
        throw new Exception('Flask error: ' . ($respData['error'] ?? 'Unknown error'));
    }

    // Return results including accuracy
    echo json_encode([
        'success'    => true,
        'analysis'   => $respData['analysis'],
        'confidence' => $respData['confidence'],
        'accuracy'   => $respData['accuracy']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
?>