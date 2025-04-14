<?php

use AdityaZanjad\Http\Http;
use PHPUnit\Framework\TestCase;
use AdityaZanjad\Http\Clients\Curl\Curl;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use AdityaZanjad\Http\Clients\Curl\Request as CurlRequest;

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
    protected string $baseUrl = 'http://127.0.0.1:8000';

    /**
     * Assert that the HTTP GET request is successfully made through the Guzzle HTTP Client.
     * 
     * @return void
     */
    public function testGetRequestSucceeds()
    {
        $http = new Http();

        $res = $http->send([
            'client'    =>  'curl',
            'url'       =>  "{$this->baseUrl}/api/http/hello-world",
            'method'    =>  'GET',

            'headers' => [
                'Accept' => 'text/plain'
            ]
        ]);

        $this->assertEquals($res->code(), 200);
        $this->assertEquals($res->status(), 'OK');

        $body = $res->body();

        $this->assertIsArray($body);
        $this->assertNotEmpty($body);
        $this->assertEquals($body['message'], 'Hello World!');
    }

    /**
     * Assert that the GET request fails. 
     *
     * @return void
     */
    public function testGetRequestFails()
    {
        $http = new Http();

        $res = $http->send([
            'client'    =>  'curl',
            'url'       =>  "{$this->baseUrl}/api/http/hello-world-fails",
            'method'    =>  'GET',

            'headers' => [
                'Accept' => 'text/plain'
            ]
        ]);

        $this->assertContains($res->code(), [404, 401, 403, 500, 501, 502, 503]);
        $this->assertEquals(strtoupper($res->status()), 'INTERNAL SERVER ERROR');
        
        $body = $res->body();

        $this->assertIsArray($body);
        $this->assertNotEmpty($body);
        $this->assertEquals($body['message'], '!!! Hello World - Failed !!!');
    }
}