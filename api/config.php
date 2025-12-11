<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost:5173'); // или твой домен
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

$dsn = 'mysql:host=localhost;dbname=agora;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'DB connection failed']);
  exit;
}

session_start();

function require_auth() {
  if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
  }
}
