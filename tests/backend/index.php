<?php

if (isset($_SERVER['REQUEST_URI'])) {
    require __DIR__ . $_SERVER['REQUEST_URI'];
    exit;
}