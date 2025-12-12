<?php
header('Content-Type: application/json');

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (str_starts_with($path, '/api/')) {
    $file = __DIR__ . '/../' . ltrim($path, '/');

    if (file_exists($file)) {
        require $file;
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'API route not found']);
    exit;
}

echo json_encode(['status' => 'PHP backend is running']);
