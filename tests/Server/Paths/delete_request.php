<?php

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Method Not Allowed. Only DELETE requests are accepted.']);
    exit;
}

http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['message' => 'DELETE request received successfully.']);