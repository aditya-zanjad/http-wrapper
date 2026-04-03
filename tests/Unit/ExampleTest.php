<?php

use AdityaZanjad\HttpAdapters\Http;
use AdityaZanjad\HttpAdapters\Adapters\Curl;
use AdityaZanjad\HttpAdapters\Adapters\Curl\Request;
use AdityaZanjad\HttpAdapters\Adapters\Curl\Response;


// Initialize The Adapter
test('Initialize CURL Adapter', function () {
    expect(Http::adapter('curl'))->toBeInstanceOf(Curl::class);
})->covers(Http::class, Curl::class);


// Start the HTTP server process.
$host       =   'localhost';
$port       =   8080;
$serverDir  =   \dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'Server';
$process    =   null;
$attempts   =   10;


while ($attempts > 0) {
    $pipes      =  [];
    $command    =  "php -S {$host}:{$port} -t {$serverDir}";
    $process    =  \proc_open($command, [ ['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w'] ], $pipes);

    if ($process !== false) {
        break;
    }

    $port++;
}


$url = "http://{$host}:{$port}";
\usleep(100000);


// Send request & obtain the text "Hello World!".
test('Response returns a plain text "Hello World!"', function () use ($url) {
    $res = Http::adapter('curl')->send([
        'url'       =>  "{$url}/hello_world.php", 
        'method'    =>  'GET'
    ]);

    expect($res->body())->toBeString()->toBe('Hello World!');
    expect($res->code())->toBeInt()->toBe(200);
    expect($res->status())->toBeString()->toBe('OK');
    expect($res->header('Content-Type'))->toBeString()->toBe('text/plain;charset=UTF-8');
})->covers(Http::class, Curl::class, Request::class, Response::class);


// Send request & obtain the JSON response.
test('Response returns a JSON containing the message "Hello World!"', function () use ($url) {
    $res = Http::adapter('curl')->send([
        'url'       =>  "{$url}/hello_world_json.php",
        'method'    =>  'GET'
    ]);

    expect($res->body())->toBeArray()->toHaveKey('message')->toMatchArray(['message' => 'Hello World!']);
    expect($res->code())->toBeInt()->toBe(200);
    expect($res->status())->toBeString()->toBe('OK');
    expect($res->header('Content-Type'))->toBeString()->toBe('application/json');
})->covers(Http::class, Curl::class, Request::class, Response::class);


// Send request & obtain a text file response.
test('Response returns a text file', function () use ($url) {
    $res = Http::adapter('curl')->send([
        'url'       =>  "{$url}/text_file.php",
        'method'    =>  'GET'
    ]);

    $finfo      =   new finfo(FILEINFO_MIME_TYPE);
    $mimeType   =   $finfo->buffer($res->body());

    expect($res->body())->toBeString();
    expect($res->code())->toBeInt()->toBe(200);
    expect($res->status())->toBeString()->toBe('OK');
    expect($mimeType)->toBeString()->toBe('text/plain');
    expect($res->header('Content-Type'))->toBeString()->toBe('text/html; charset=UTF-8');
});


// Send request & obtain a PDF file response.
test('Response returns a PDF file', function () use ($url) {
    $res = Http::adapter('curl')->send([
        'url'       =>  "{$url}/pdf_file.php",
        'method'    =>  'GET'
    ]);

    $expectedMimeType   =   'application/pdf';
    $finfo              =   new finfo(FILEINFO_MIME_TYPE);
    $mimeType           =   $finfo->buffer($res->body());

    expect($res->body())->toBeString();
    expect($res->code())->toBeInt()->toBe(200);
    expect($res->status())->toBeString()->toBe('OK');
    expect($mimeType)->toBeString()->toBe($expectedMimeType);
    expect($res->header('Content-Type'))->toBeString()->toBe($expectedMimeType);
})->covers(Http::class, Curl::class, Request::class, Response::class);


// Close the HTTP server process.
$status = \proc_get_status($process);

if ($status['running']) {
    \proc_terminate($process);
}

\proc_close($process);
