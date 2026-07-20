<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Enums;

enum ValidAcceptEncoding: String
{
    case ALL    =   'all';
    case NONE   =   'identity';
}