<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpWrapper\Providers;

use AdityaZanjad\HttpWrapper\Interfaces\HttpClient;
use AdityaZanjad\HttpWrapper\Providers\Curl\Request;
use AdityaZanjad\HttpWrapper\Interfaces\HttpResponse;
use AdityaZanjad\HttpWrappers\Providers\Curl\Response;
use AdityaZanjad\HttpWrapper\Providers\Curl\ResponseHeaders;

class Curl implements HttpClient 
{
    public function __construct(protected array $options = [])
    {
        //
    }
    
    public function send(array $request): HttpResponse
    {        
        $request                            =   new Request($request);
        $options                            =   $request->build();
        $responseHeaders                    =   new ResponseHeaders();
        $options[CURLOPT_HEADERFUNCTION]    =   [$responseHeaders, 'process'];
        
        $req = curl_init();
        curl_setopt_array($req, $request->build());
        $res = curl_exec($req);

        return new Response($req, $res, $responseHeaders->all());
    }
    
    public function pool(array $requests): array
    {
        return [];
    }
}
