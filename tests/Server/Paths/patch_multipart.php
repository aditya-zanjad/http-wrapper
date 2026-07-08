<?php

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Method Not Allowed. Only POST requests are accepted.']);
    exit;
}

$formData = $_POST;

if (!isset($formData['username']) || !isset($formData['password']) || !isset($_FILES['test_file'])) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Username, password, and file are all required.']);
    exit;
}

http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => 'PATCH request received successfully.']);