<?php

use AdityaZanjad\HttpAdapters\Http;
use AdityaZanjad\HttpAdapters\Adapters\Curl;


test('Server Base URL successfully obtained!', function () {
    expect(SERVER_BASE_URL)->toBeString();
});

test('CURL Adapter Initializes Successfully.', function () {
    expect(Http::adapter('curl'))->toBeInstanceOf(Curl::class);
})->covers(Http::class, Curl::class);

test('Wrong Adapter Name Fails Initialization.', function () {
    expect(fn () => Http::adapter('invalid'))->toThrow(Exception::class, "[Developer][Exception]: The HTTP adapter [invalid] is either invalid or not supported yet.");
})->covers(Http::class);
