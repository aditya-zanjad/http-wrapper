<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Adapters;

use Exception;
use AdityaZanjad\HttpAdapters\Adapters\Curl\Request;
use AdityaZanjad\HttpAdapters\Interfaces\HttpAdapter;
use AdityaZanjad\HttpAdapters\Adapters\Curl\Response;
use AdityaZanjad\HttpAdapters\Interfaces\HttpResponse;
use AdityaZanjad\HttpAdapters\Adapters\Curl\ResponseHeaders;

class Curl implements HttpAdapter 
{
    public function __construct(protected array $options = [])
    {
        if (!\extension_loaded('curl')) {
            throw new Exception("[Developer][Exception]: The PHP extension is not enabled/installed on this sytem. This HTTP Adapter requires it to work.");
        }
    }
    
    public function send(array $request): HttpResponse
    {        
        $request                            =   new Request($request);
        $options                            =   $request->build();
        $responseHeaders                    =   new ResponseHeaders();
        $options[CURLOPT_HEADERFUNCTION]    =   [$responseHeaders, 'process'];
        
        $req = curl_init();
        curl_setopt_array($req, $options);
        $res = curl_exec($req);

        return new Response($req, $res, $responseHeaders->all());
    }
    
    public function pool(array $requests): array
    {
        $data   =   [];
        $curls  =   curl_multi_init();
        
        foreach ($requests as $index => $request) {
            $data[$index]['request']                                    =   new Request($request);
            $data[$index]['request_options']                            =   $data[$index]['request']->build();
            $data[$index]['header_processor']                           =   new ResponseHeaders();
            $data[$index]['request_options'][CURLOPT_HEADERFUNCTION]    =   [$data[$index]['header_processor'], 'process'];

            $data[$index]['curl'] = curl_init();
            curl_setopt_array($data[$index]['curl'], $data[$index]['request_options']);
            curl_multi_add_handle($curls, $data[$index]['curl']);
        }

        $stillRunning = false;
        
        do {
            curl_multi_exec($curls, $stillRunning);
        } while ($stillRunning);

        $result = [];

        foreach ($data as $index => $d) {
            curl_multi_remove_handle($curls, $d['curl']);
            $result[$index] = new Response($d['curl'], curl_multi_getcontent($d['curl']), $d['header_processor']->all());
        }
        
        return $result;
    }
}
