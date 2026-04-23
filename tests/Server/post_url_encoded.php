<?php

parse_str(file_get_contents('php://input'), $formData);

if (!isset($formData['username']) || !isset($formData['password'])) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Both username and password are required.']);
    exit;
}

http_response_code(201);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => 'Form data is successfully submitted.']);