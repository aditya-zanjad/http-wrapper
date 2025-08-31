<?php

http_response_code(201);
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$data = [
    'message'   =>  'Successfully updated the data!',
    'data'      =>  json_decode(file_get_contents('php://input'), true)
];

echo json_encode($data);