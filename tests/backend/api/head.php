<?php

if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    echo 'Invalid Request';
    exit;
}

http_response_code(200);