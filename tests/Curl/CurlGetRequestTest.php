<?php

use AdityaZanjad\Http\Builders\CurlRequest;
use AdityaZanjad\Http\Http;
use PHPUnit\Framework\TestCase;
use AdityaZanjad\Http\Clients\Guzzle;
use PHPUnit\Framework\Attributes\UsesClass;
use AdityaZanjad\Http\Builders\GuzzleRequest;
use AdityaZanjad\Http\Clients\Curl;
use PHPUnit\Framework\Attributes\CoversClass;

#[UsesClass(Http::class)]
#[CoversClass(Http::class)]
#[UsesClass(Curl::class)]
#[CoversClass(Curl::class)]
#[UsesClass(CurlRequest::class)]
#[CoversClass(CurlRequest::class)]
final class CurlGetRequestTest extends TestCase
{
    /**
     * @var string $baseUrl
     */
    protected string $baseUrl = 'http://127.0.0.1:8000/api';

    /**
     * Assert that the HTTP GET request is successfully made through the Guzzle HTTP Client.
     * 
     * @return void
     */
    public function testGetRequestSucceeds()
    {
        $res = Http::make([
            'client'    =>  'curl',
            'url'       =>  "{$this->baseUrl}/hello-world-success",
            'method'    =>  'GET',

            'headers' => [
                'Accept' => 'text/plain'
            ]
        ]);

        $this->assertEquals($res->code(), 200);
        $this->assertEquals($res->status(), 'OK');
        $this->assertEquals($res->body(), 'Hello World!');
    }

    /**
     * Assert that the GET request fails. 
     *
     * @return void
     */
    public function testGetRequestFails()
    {
        $res = Http::make([
            'client'    =>  'curl',
            'url'       =>  "{$this->baseUrl}/hello-world-failure",
            'method'    =>  'GET',

            'headers' => [
                'Accept' => 'text/plain'
            ]
        ]);

        $this->assertEquals($res->code(), 500);
        $this->assertEquals(strtoupper($res->status()), 'INTERNAL SERVER ERROR');
        $this->assertEquals($res->body(), 'INTERNAL SERVER ERROR');
    }
}