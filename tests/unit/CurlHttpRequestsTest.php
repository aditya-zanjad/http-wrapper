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
final class CurlHttpRequestsTest extends TestCase
{
    /**
     * @var string $baseUrl
     */
    protected string $baseUrl = 'http://127.0.0.1:8000';

    /**
     * @var string $tempDir
     */
    protected string $tempDir = __DIR__ . '/files';

    /**
     * @var array $validFiles
     */
    protected array $validFiles = [
        'file_001' => null
    ];

    /**
     * @return void
     */
    public function setUp(): void
    {
        if (!\is_dir($this->tempDir)) {
            \mkdir($this->tempDir);
        }

        \chmod($this->tempDir, 0775);
        $this->validFiles['file_001'] = "{$this->tempDir}/test.json";
        \file_put_contents($this->validFiles['file_001'], '{"name": "Aditya Zanjad"}');
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        \unlink($this->validFiles['file_001']);
        \rmdir($this->tempDir);
    }

    /**
     * @return void
     */
    public function testHttpHeadRequest(): void
    {
        $http = new Http('curl');

        $res = $http->send([
            'url'       =>  "{$this->baseUrl}/api/head.php",
            'method'    =>  'HEAD'
        ]);

        $this->assertEquals($res->code(), 200);
        $this->assertEquals($res->status(), 'OK');
    }

    /**
     * @return void
     */
    public function testHttpGetRequestForJsonData(): void
    {
        $http = new Http('curl');

        $res = $http->send([
            'url'       =>  "{$this->baseUrl}/api/get.php",
            'method'    =>  'GET',

            'headers' => [
                'Accept' => 'application/json'
            ]
        ]);

        $this->assertEquals($res->code(), 200);
        $this->assertEquals($res->status(), 'OK');

        $body = $res->body();

        $this->assertIsArray($body);
        $this->assertNotEmpty($body);
        $this->assertEquals($body['message'], 'Successfully fetched the user data!');
        $this->assertNotEmpty($body);
        $this->assertNotNull($body);
        $this->assertIsArray($body['data']);
        $this->assertEquals($body['data']['first_name'], 'Aditya');
        $this->assertEquals($body['data']['last_name'], 'Zanjad');
        $this->assertEquals($body['data']['email'], 'aditya@email.com');
        $this->assertEquals($body['data']['gender'], 'male');
        $this->assertEquals($body['data']['phone_number'], '911234567890');
    }

    /**
     * @return void
     */
    public function testHttpPostRequestWithJsonBody(): void
    {
        $http = new Http('curl');

        $res = $http->send([
            'url'       =>  "{$this->baseUrl}/api/post.php",
            'method'    =>  'POST',

            'headers' => [
                'Content-Type'  =>  'application/json',
                'Accept'        =>  'application/json'
            ],

            'body' => [
                'data' => [
                    [
                        'field' =>  'first_name',
                        'value' =>  'Aditya'
                    ],
                    [
                        'field' =>  'last_name',
                        'value' =>  'Zanjad'
                    ],
                    [
                        'field' =>  'email',
                        'value' =>  'aditya@email.com'
                    ],
                    [
                        'field' =>  'gender',
                        'value' =>  'male'
                    ],
                    [
                        'field' =>  'phone_number',
                        'value' =>  '911234567890'
                    ],
                ]
            ]
        ]);

        $this->assertEquals($res->code(), 201);
        $this->assertEquals($res->status(), 'CREATED');

        $body = $res->body();

        $this->assertIsArray($body);
        $this->assertNotEmpty($body);
        $this->assertEquals($body['message'], 'Successfully updated the data!');
    }

    /**
     * @return void
     */
    public function testHttpPutRequestWithUrlEncodedFormBody(): void
    {
        $http = new Http('curl');

        $res = $http->send([
            'url'       =>  "{$this->baseUrl}/api/put.php",
            'method'    =>  'PUT',

            'headers' => [
                'Content-Type'  =>  'application/x-www-form-urlencoded',
                'Accept'        =>  'application/json'
            ],

            'body' => [
                'data' => [
                    [
                        'field' =>  'first_name',
                        'value' =>  'Aditya'
                    ],
                    [
                        'field' =>  'last_name',
                        'value' =>  'Zanjad'
                    ],
                    [
                        'field' =>  'email',
                        'value' =>  'aditya@email.com'
                    ],
                    [
                        'field' =>  'gender',
                        'value' =>  'male'
                    ],
                    [
                        'field' =>  'phone_number',
                        'value' =>  '911234567890'
                    ],
                ]
            ]
        ]);

        $this->assertEquals($res->code(), 204);
        $this->assertEquals($res->status(), 'NO CONTENT');

        $body = $res->body();

        $this->assertEmpty($body);
        $this->assertNull($body);
    }

    /**
     * @return void
     */
    public function testHttpPatchRequestWithMultipartFormData(): void
    {
        $http = new Http('curl');

        $res = $http->send([
            'url'       =>  "{$this->baseUrl}/api/put.php",
            'method'    =>  'PATCH',

            'headers' => [
                'Content-Type'  =>  'multipart/form-data',
                'Accept'        =>  'application/json'
            ],

            'body' => [
                'data' => [
                    [
                        'field' =>  'first_name',
                        'value' =>  'Aditya'
                    ],
                    [
                        'field' =>  'last_name',
                        'value' =>  'Zanjad'
                    ],
                    [
                        'field' =>  'email',
                        'value' =>  'aditya@email.com'
                    ],
                    [
                        'field' =>  'gender',
                        'value' =>  'male'
                    ],
                    [
                        'field' =>  'phone_number',
                        'value' =>  '911234567890'
                    ],
                    [
                        'field' =>  'file',
                        'value' =>  $this->validFiles['file_001'],
                    ]
                ]
            ]
        ]);

        $this->assertEquals($res->code(), 204);
        $this->assertEquals($res->status(), 'NO CONTENT');

        $body = $res->body();

        $this->assertEmpty($body);
        $this->assertNull($body);
    }

    /**
     * @return void
     */
    public function testHttpDeleteRequest(): void
    {
        $http = new Http('curl');

        $res = $http->send([
            'url'       =>  "{$this->baseUrl}/api/delete.php",
            'method'    =>  'DELETE',
            'query'     =>  ['id' => 123]
        ]);

        $this->assertEquals($res->code(), 204);
        $this->assertEquals($res->status(), 'NO CONTENT');

        $body = $res->body();

        $this->assertEmpty($body);
        $this->assertNull($body);
    }

    /**
     * @return void
     */
    public function testHttpOptionsRequest(): void
    {
        $http = new Http('curl');

        $res = $http->send([
            'url'       =>  "{$this->baseUrl}/api/options.php",
            'method'    =>  'OPTIONS',
        ]);

        $this->assertEquals($res->code(), 204);
        $this->assertEquals($res->status(), 'NO CONTENT');
        $this->assertEquals($res->header('Access-Control-Allow-Methods'), 'GET, POST, PUT, PATCH, DELETE, OPTIONS');

        $body = $res->body();

        $this->assertEmpty($body);
        $this->assertNull($body);
    }
}
