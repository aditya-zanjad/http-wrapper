<?php

function presentUnprocessableEntityResponse()
{
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['message' => 'Username, password, and file are all required.']);
}

if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_FILES['text_file_01']) || !isset($_FILES['text_file_02'])) {
    presentUnprocessableEntityResponse();
    return;
}

if (!isset($_FILES['text_file_02']['full_path']) || $_FILES['text_file_02']['full_path'] !== "Text_File_02.txt" || $_FILES['text_file_02']['type'] !== 'text/plain') {
    presentUnprocessableEntityResponse();
    return;
}

http_response_code(201);
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'message'   =>  'Form data with file is successfully submitted.',
    'username'  =>  $_POST['username'],
    'password'  =>  $_POST['password'],

    'files' => [
        'text_file_01_received' => isset($_FILES['text_file_01']),
        'text_file_02_received' => isset($_FILES['text_file_02'])
    ]
]);
