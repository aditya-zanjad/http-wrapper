<?php

$formData = $_POST;

if (!isset($formData['username']) || !isset($formData['password']) || !isset($_FILES['file'])) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Username, password, and file are all required.']);
    exit;
}

http_response_code(201);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => 'Form data with file is successfully submitted.']);