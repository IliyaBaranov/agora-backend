<?php
header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Убираем .. и двойные //
$uri = rtrim($uri, '/');

// === API ROUTING ===
if (str_starts_with($uri, '/api/')) {

    // Строим путь к реальному файлу
    $file = __DIR__ . '/../' . ltrim($uri, '/');

    if (file_exists($file)) {
        require $file;
        exit;
    }

    http_response_code(404);
    echo json_encode([
        'error' => 'API endpoint not found',
        'path' => $uri
    ]);
    exit;
}

// === ROOT CHECK ===
echo json_encode([
    'status' => 'PHP backend is running'
]);
