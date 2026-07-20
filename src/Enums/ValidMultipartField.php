<?php

declare(strict_types=1);

namespace AdityaZanjad\HttpAdapters\Enums;

enum ValidMultipartField: String
{
    case JSON = 'json';
    case FILE = 'file';
}
