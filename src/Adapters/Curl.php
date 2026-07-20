<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Adapters;

use Exception;
use AdityaZanjad\HttpAdapters\Adapters\Curl\Request;
use AdityaZanjad\HttpAdapters\Interfaces\HttpAdapter;
use AdityaZanjad\HttpAdapters\Adapters\Curl\Response;
use AdityaZanjad\HttpAdapters\Interfaces\HttpResponse;
use AdityaZanjad\HttpAdapters\Validators\RequestValidator;
use AdityaZanjad\HttpAdapters\Adapters\Curl\ResponseHeaders;

class Curl implements HttpAdapter
{
    public function __construct(protected array $options = [])
    {
        if (!\extension_loaded('curl')) {
            throw new Exception("[Developer][Exception]: The PHP extension is not enabled/installed on this system. This HTTP Adapter requires it to work. Either enable this extension or switch to a different HTTP adapter.");
        }
    }

    public function send(array $data): HttpResponse
    {
        $validator = new RequestValidator($data);
        $validator->validate();

        $request                            =   new Request($data);
        $options                            =   $request->build();
        $responseHeaders                    =   new ResponseHeaders();
        $options[CURLOPT_HEADERFUNCTION]    =   [$responseHeaders, 'process'];

        $req = \curl_init();
        \curl_setopt_array($req, $options);
        $res = \curl_exec($req);

        return new Response($req, $res, $responseHeaders->all());
    }

    public function pool(array $requests): array
    {
        $data               =   [];
        $requestsHandles    =   \curl_multi_init();

        foreach ($requests as $index => $request) {
            $options                            =   (new Request($request))->build();
            $data[$index]['headers']            =   new ResponseHeaders();
            $options[CURLOPT_HEADERFUNCTION]    =   [$data[$index]['headers'], 'process'];

            $data[$index]['curl'] = curl_init();
            \curl_setopt_array($data[$index]['curl'], $options);
            \curl_multi_add_handle($requestsHandles, $data[$index]['curl']);
        }

        $stillRunning = 1;

        while ($stillRunning > 0) {
            \curl_multi_exec($requestsHandles, $stillRunning);
        }

        $result = [];

        foreach ($data as $index => $d) {
            \curl_multi_remove_handle($requestsHandles, $d['curl']);
            $result[$index] = new Response($d['curl'], \curl_multi_getcontent($d['curl']), $d['headers']->all());
        }

        return $result;
    }
}
