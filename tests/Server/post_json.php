<?php

$body = \json_decode(\file_get_contents('php://input'), true);
header('Content-Type: application/json; charset=utf-8');

if (!isset($body['username']) || !isset($body['password'])) {
    http_response_code(422);
    echo json_encode([
        'message' => 'No JSON data received.'
    ]);

    return;
}

http_response_code(201);
echo json_encode([
    'message' => 'JSON data is successfully submitted.'
]);