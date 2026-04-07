<?php


header('Content-Type: application/json; charset=utf-8');

if (empty($_GET) || !isset($_GET['a']) || !isset($_GET['b'])) {
    http_response_code(400);

    echo json_encode([
        'status'    =>  false,
        'message'   =>  'No query params received.'
    ]);

    return;
}

http_response_code(200);

echo json_encode([
    'status'    =>  true,
    'query'     =>  \array_map(fn ($el) => (int) $el, $_GET),
    'message'   =>  'Query params successfully received.'
]);
