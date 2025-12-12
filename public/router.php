<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// если файл реально существует в public — отдать его
if ($path !== '/' && file_exists($file)) {
    return false;
}

// иначе — всё через index.php
require __DIR__ . '/index.php';
