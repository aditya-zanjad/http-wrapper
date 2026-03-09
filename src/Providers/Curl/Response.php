<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpWrappers\Providers\Curl;

use CurlHandle;
use AdityaZanjad\HttpWrapper\Interfaces\HttpResponse;
use Exception;

class Response implements HttpResponse
{
    public function __construct(protected CurlHandle $req, protected bool|string $res, protected array $headers)
    {
        //
    }

    public function code(): int
    {
        return curl_getinfo($this->req, CURLINFO_HTTP_CODE);
    }

    public function status(): null|string
    {
        return match ($this->code()) {
            100     =>  'CONTINUE',
            101     =>  'SWITCHING PROTOCOLS',
            102     =>  'PROCESSING',
            103     =>  'EARLY HINTS',
            200     =>  'OK',
            201     =>  'CREATED',
            202     =>  'ACCEPTED',
            203     =>  'NON-AUTHORITATIVE INFORMATION',
            204     =>  'NO CONTENT',
            205     =>  'RESET CONTENT',
            206     =>  'PARTIAL CONTENT',
            207     =>  'MULTI-STATUS',
            208     =>  'ALREADY REPORTED',
            226     =>  'IM USED',
            300     =>  'MULTIPLE CHOICES',
            301     =>  'MOVED PERMANENTLY',
            302     =>  'FOUND',
            303     =>  'SEE OTHER',
            304     =>  'NOT MODIFIED',
            305     =>  'USE PROXY',
            306     =>  'UNUSED',
            307     =>  'TEMPORARY REDIRECT',
            308     =>  'PERMANENT REDIRECT',
            400     =>  'BAD REQUEST',
            401     =>  'UNAUTHORIZED',
            402     =>  'PAYMENT REQUIRED',
            403     =>  'FORBIDDEN',
            404     =>  'NOT FOUND',
            405     =>  'METHOD NOT ALLOWED',
            406     =>  'NOT ACCEPTABLE',
            407     =>  'PROXY AUTHENTICATION REQUIRED',
            408     =>  'REQUEST TIMEOUT',
            409     =>  'CONFLICT',
            410     =>  'GONE',
            411     =>  'LENGTH REQUIRED',
            412     =>  'PRECONDITION FAILED',
            413     =>  'CONTENT TOO LARGE',
            414     =>  'URI TOO LONG',
            415     =>  'UNSUPPORTED MEDIA TYPE',
            416     =>  'RANGE NOT SATISFIABLE',
            417     =>  'EXPECTATION FAILED',
            418     =>  "I'M A TEAPOT",
            421     =>  'MISDIRECT REQUEST',
            422     =>  'UNPROCESSABLE ENTITY',
            423     =>  'LOCKED',
            424     =>  'FAILED DEPENDENCY',
            425     =>  'TOO EARLY',
            426     =>  'UPGRADE REQUIRED',
            428     =>  'PRECONDITION REQUIRED',
            429     =>  'TOO MANY REQUESTS',
            431     =>  'REQUEST HEADERS FIELDS TOO LARGE',
            451     =>  'UNAVAILABLE FOR LEGAL REASONS',
            500     =>  'INTERNAL SERVER ERROR',
            501     =>  'NOT IMPLEMENTED',
            502     =>  'BAD GATEWAY',
            503     =>  'SERVICE UNAVAILABLE',
            504     =>  'GATEWAY TIMEOUT',
            505     =>  'HTTP VERSION NOT SUPPORTED',
            506     =>  'VARIANT ALSO NEGOTIATES',
            507     =>  'INSUFFICENT STORAGE',
            508     =>  'LOOP DETECTED',
            510     =>  'NOT EXTENED',
            511     =>  'NETWORK AUTHENTICATION REQUIRED',
            default =>  null,
        };
    }

    public function header(string $name): null|string|array
    {
        $value          =   null;
        $loweredName    =   \strtolower($name);

        foreach ($this->headers as $headerName => $headerValue) {
            if (\strtolower($headerName) === $loweredName) {
                $value = $headerValue;
                break;
            }
        }

        return $value;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function body(): mixed
    {
        if (\is_string($this->res) && \json_validate($this->res)) {
            return \json_decode($this->res);
        }

        return (string) $this->res;
    }
}