<?php

use AdityaZanjad\HttpAdapters\Http;
use AdityaZanjad\HttpAdapters\Adapters\Curl;
use AdityaZanjad\HttpAdapters\Adapters\Curl\Request;
use AdityaZanjad\HttpAdapters\Adapters\Curl\Response;

// Hello World Text
test('Sends HTTP GET Request & Obtains 200 OK Response!', function () {
    $adapter    =   Http::adapter('curl');
    $res        =   $adapter->send(['url' => SERVER_BASE_URL . "/hello_world_text.php"]);

    expect($res->code())->toBeInt()->toBe(200);
    expect($res->status())->toBeString()->toBe('OK');
    expect($res->headers())->toBeArray();
    expect($res->header('Content-Type'))->toBeString();
    expect($res->header('content-type'))->toBeString()->toBe('text/plain; charset=UTF-8');
    expect($res->body())->toBeString()->toBe('Hello World!');
})->covers(Http::class, Curl::class, Request::class, Response::class);

// Hello World JSON
test('Sends HTTP GET Request & Obtains 200 OK JSON Response!', function () {
    $http   =   Http::adapter('curl');
    $res    =   $http->send(['url' => SERVER_BASE_URL . '/hello_world_json.php']);

    expect($res->code())->toBeInt()->toBe(200);
    expect($res->status())->toBeString()->toBe('OK');
    expect($res->headers())->toBeArray();
    expect($res->header('Content-Type'))->toBeString();
    expect($res->header('content-type'))->toBeString()->toBe('application/json');
    expect($res->body())->toBeArray()->toBe(['message' => 'Hello World!']);
});

// NOT FOUND Text
test('Sends HTTP GET Request & Gets 404 NOT FOUND Response!', function () {
    $http   =   Http::adapter('curl');
    $res    =   $http->send(['url' => SERVER_BASE_URL . '/not_found.php']);

    expect($res->code())->toBeInt()->toBe(404);
    expect($res->status())->toBeString()->toBe('NOT FOUND');
    expect($res->headers())->toBeArray();
    expect($res->header('content-type'))->toBeString()->toBe('text/plain; charset=UTF-8');
    expect($res->body())->toBeString()->toBe('Not Found!');
});

// Text File
test('Sends HTTP GET Request & Obtains A Text File!', function () {
    $http   =   Http::adapter('curl');
    $res    =   $http->send(['url' => SERVER_BASE_URL . '/text_file.php']);

    expect($res->code())->toBeInt()->toBe(200);
    expect($res->status())->toBeString()->toBe('OK');
    expect($res->headers())->toBeArray();
    expect($res->header('content-type'))->toBeString()->toBe('text/plain; charset=UTF-8');
    expect((new \finfo(FILEINFO_MIME_TYPE))->buffer($res->body()))->toBeString()->toBe('text/plain');
});

// PDF File
test('Sends HTTP GET Request & Obtains A PDF File!', function () {
    $http   =   Http::adapter('curl');
    $res    =   $http->send(['url' => SERVER_BASE_URL . '/pdf_file.php']);

    expect($res->code())->toBeInt()->toBe(200);
    expect($res->status())->toBeString()->toBe('OK');
    expect($res->headers())->toBeArray();
    expect($res->header('content-type'))->toBeString()->toBe('application/pdf');
    expect((new \finfo(FILEINFO_MIME_TYPE))->buffer($res->body()))->toBeString()->toBe('application/pdf');
});


// No Query Parameters Supplied
test('HTTP Request Without Any Query Parameters Fails!', function () {
    $http   =   Http::adapter('curl');
    $res    =   $http->send(['url' => SERVER_BASE_URL . '/query_params.php']);

    expect($res->code())->toBeInt()->toBe(400);
    expect($res->status())->toBeString()->toBe('BAD REQUEST');
    expect($res->headers())->toBeArray();
    expect($res->header('content-type'))->toBeString()->toBe('application/json; charset=utf-8');
    
    $body = $res->body();

    expect($body)->toBeArray()->toBe([
        'status'    =>  false,
        'message'   =>  'No query params received.'
    ]);
});


// Query Parameters Supplied
test('HTTP Request With Query Params Succeeds!', function () {
    $res = Http::adapter('curl')->send([
        'url' => SERVER_BASE_URL . '/query_params.php',

        'query' => [
            'params' => [
                'a' => 1,
                'b' => 2
            ]
        ]
    ]);

    expect($res->code())->toBeInt()->toBe(200);
    expect($res->status())->toBeString()->toBe('OK');
    expect($res->headers())->toBeArray();
    expect($res->header('content-type'))->toBeString()->toBe('application/json; charset=utf-8');

    $body = $res->body();

    expect($body)->toBeArray()->toBe([
        'status'    =>  true,
        'query'     =>  ['a' => 1, 'b' => 2],
        'message'   =>  'Query params successfully received.'
    ]);
});


// POST Request Without JSON Body
test('Sending POST Request Without JSON Body Results In Failure!', function () {
    $res = Http::adapter('curl')->send([
        'url' => SERVER_BASE_URL . '/post_json.php',

        'headers' => [
            'Accept'        =>  'application/json',
            'Content-Type'  =>  'application/json'
        ]
    ]);

    expect($res->code())->toBeInt()->toBe(422);
    expect($res->status())->toBeString()->toBe('UNPROCESSABLE ENTITY');
    expect($res->headers())->toBeArray();
    expect($res->header('Content-Type'))->toBeString()->toBe('application/json; charset=utf-8');
    expect($res->body())->toBeArray()->toBe(['message' => 'No JSON data received.']);
});

// POST Request With JSON Body
test('Sending POST Request With JSON Body Succeeds!', function () {
    $username = 'TestUser';
    $password = '1234! Get on the dance floor!';

    $res = Http::adapter('curl')->send([
        'url' => SERVER_BASE_URL . '/post_json.php',

        'headers' => [
            'Accept'        =>  'application/json',
            'Content-Type'  =>  'application/json'
        ],

        'body' => [
            'type' => 'json',

            'content' => [
                'username' => $username,
                'password' => $password,
            ]
        ]
    ]);

    expect($res->code())->toBeInt()->toBe(201);
    expect($res->status())->toBeString()->toBe('CREATED');
    expect($res->headers())->toBeArray();
    expect($res->header('Content-Type'))->toBeString()->toBe('application/json; charset=utf-8');

    expect($res->body())->toBeArray()->toBe([
        'message' => 'JSON data is successfully submitted.',

        'json' => [
            'username' => $username,
            'password' => $password
        ]
    ]);
});

// POST request without body as a "application/x-www-form-urlencoded".
test('Sending POST request without Form Params Fails!', function () {
    $res = Http::adapter('curl')->send([
        'url' => SERVER_BASE_URL . '/post_params.php',

        'headers' => [
            'Accept'        =>  'application/json',
            'Content-Type'  =>  'application/x-www-form-urlencoded'
        ],
    ]);

    expect($res->code())->toBeInt()->toBe(422);
    expect($res->status())->toBeString()->toBe('UNPROCESSABLE ENTITY');
    expect($res->headers())->toBeArray();
    expect($res->header('content-type'))->toBeString()->toBe('application/json; charset=utf-8');
    expect($res->body())->toBeArray()->toBe(['message' => 'Both username and password are required.']);
});

// POST request with body as a "application/x-www-form-urlencoded".
test('Sending POST request with Form Params Succeeds!', function () {
    $username   =   'TestUser';
    $password   =   '1234! Get on the dance floor!';

    $res = Http::adapter('curl')->send([
        'url' => SERVER_BASE_URL . '/post_params.php',

        'headers' => [
            'Accept'        =>  'application/json',
            'Content-Type'  =>  'application/x-www-form-urlencoded'
        ],

        'body' => [
            'type' => 'params',

            'content' => [
                'username'  =>  $username,
                'password'  =>  $password
            ]
        ]
    ]);

    expect($res->code())->toBeInt()->toBe(201);
    expect($res->status())->toBeString()->toBe('CREATED');
    expect($res->headers())->toBeArray();
    expect($res->header('content-type'))->toBeString()->toBe('application/json; charset=utf-8');

    expect($res->body())->toBeArray()->toBe([
        'message'   =>  'Form data is successfully submitted.',
        'username'  =>  $username,
        'password'  =>  $password
    ]);
});

// POST request with incomplete multipart form data
test('Sending HTTP POST Request With Incomplete Multipart Form Data Fails!', function () {
    $username   =   'TestUser';
    $password   =   '1234! Get on the dance floor!';

    $res = Http::adapter('curl')->send([
        'url' => SERVER_BASE_URL . '/post_multipart.php',

        'headers' => [
            'Accept'        =>  'application/json',
            'Content-Type'  =>  'multipart/form-data'
        ],

        'body' => [
            'type' => 'multipart',

            'content' => [
                [
                    'name' => 'username',
                    'value' => $username,
                ],
                [
                    'name' => 'password',
                    'value' => $password
                ]
            ]
        ]
    ]);

    expect($res->code())->toBeInt()->toBe(422);
    expect($res->status())->toBeString()->toBe('UNPROCESSABLE ENTITY');
    expect($res->headers())->toBeArray();
    expect($res->header('content-type'))->toBeString()->toBe('application/json; charset=utf-8');
    expect($res->body())->toBeArray()->toBe(['message' => 'Username, password, and file are all required.']);
});


// POST request with multipart form data
test('Sending HTTP POST Request With Multipart Form Data Succeeds!', function () {
    $username   =   'TestUser';
    $password   =   '1234! Get on the dance floor!';
    $file       =   SERVER_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . 'multipart.txt';

    $multipartFile = \fopen($file, 'w');
    \fwrite($multipartFile, 'This is test text file generated for testing multipart form data.');

    $res = Http::adapter('curl')->send([
        'url' => SERVER_BASE_URL . '/post_multipart.php',

        'headers' => [
            'Accept' => 'application/json',
        ],

        'body' => [
            'type' => 'multipart',

            'content' => [
                [
                    'name'  =>  'username',
                    'value' =>  $username,
                ],
                [
                    'name'  =>  'password',
                    'value' =>  $password
                ],
                [
                    'name'  =>  'text_file_01',
                    'value' =>  $multipartFile
                ],
                [
                    'name'      =>  'text_file_02',
                    'type'      =>  'file',
                    'mime'      =>  'text/plain',
                    'value'     =>  $file,
                    'filename'  =>  'Text_File_02.txt'
                ]
            ]
        ]
    ]);

    \fclose($multipartFile);

    expect($res->code())->toBeInt()->toBe(201);
    expect($res->status())->toBeString()->toBe('CREATED');
    expect($res->headers())->toBeArray();
    expect($res->header('content-type'))->toBeString()->toBe('application/json; charset=utf-8');

    expect($res->body())->toBeArray()->toBe([
        'message'   =>  'Form data with file is successfully submitted.',
        'username'  =>  $username,
        'password'  =>  $password,

        'files' => [
            'text_file_01_received' =>  true,
            'text_file_02_received' =>  true,
        ]
    ]);
});
