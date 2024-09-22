<?php

namespace AdityaZanjad\Http\Enums;

enum ReasonPhrase: int
{
    // HTTP 2XX
    case OK         =   200;
    case CREATED    =   201;
    case ACCEPTED   =   202;
    case NO_DATA    =   204;

    // HTTP 3XX
    case MULTIPLE_CHOICES   =   300;
    case MOVED_PERMANENTLY  =   301;
    case FOUND              =   302;
    
    // HTTP 4XX
    case BAD_REQUEST            = 400;
    case UNAUTHORIZED           = 401;
    case PAYMENT_REQUIRED       = 402;
    case FORBIDDEN              = 403;
    case NOT_FOUND              = 404;
    case METHOD_NOT_ALLOWED     = 405;
    case REQUEST_TIMEOUT        = 408;
    case CONFLICT               = 409;
    case PAYLOAD_TOO_LARGE      = 413;
    case UNPROCESSABLE_CONTENT  = 422;
    case TOO_MANY_REQUESTS      = 429;

    // HTTP 5XX
    case INTERNAL_SERVER_ERROR  =   500;
    case NOT_IMPLEMENTED        =   501;
    case BAD_GATEWAY            =   502;
    case SERVICE_UNAVAILABLE    =   503;
    case GATEWAY_TIMEOUT        =   504;

}