<?php

use AdityaZanjad\Http\Http;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use AdityaZanjad\Http\Clients\Guzzle\Guzzle;
use PHPUnit\Framework\Attributes\CoversClass;
use AdityaZanjad\Http\Clients\Guzzle\Request as GuzzleRequest;

#[UsesClass(Http::class)]
#[CoversClass(Http::class)]
#[UsesClass(Guzzle::class)]
#[CoversClass(Guzzle::class)]
#[UsesClass(GuzzleRequest::class)]
#[CoversClass(GuzzleRequest::class)]
final class GuzzlePostRequestTest extends TestCase
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
    public function testPostRequestSucceeds()
    {
        $http = new Http('guzzle');

        $res = $http->send([
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
    public function testPostRequestFails()
    {
        $http = new Http('guzzle');

        $res = $http->send([
            'url'       =>  "{$this->baseUrl}/api/http/hello-world-fails",
            'method'    =>  'GET',

            'headers' => [
                'Accept' => 'text/plain'
            ],
        ]);

        $this->assertContains($res->code(), [404, 401, 403, 500, 501, 502, 503]);
        $this->assertEquals(strtoupper($res->status()), 'INTERNAL SERVER ERROR');

        $body = $res->body();

        $this->assertIsArray($body);
        $this->assertNotEmpty($body);
        $this->assertEquals($body['message'], '!!! Hello World - Failed !!!');
    }
}
