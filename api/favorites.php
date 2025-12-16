<?php
require 'config.php';
require_auth();

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);
  $marketplaceId = (int)($data['marketplaceId'] ?? 0);

  $stmt = $pdo->prepare(
    'INSERT IGNORE INTO favorites (user_id, marketplace_id) VALUES (?, ?)'
  );
  $stmt->execute([$userId, $marketplaceId]);
  echo json_encode(['ok' => true]);
  exit;
}

if ($method === 'DELETE') {
  $data = json_decode(file_get_contents('php://input'), true);
  $marketplaceId = (int)($data['marketplaceId'] ?? 0);

  $stmt = $pdo->prepare(
    'DELETE FROM favorites WHERE user_id = ? AND marketplace_id = ?'
  );
  $stmt->execute([$userId, $marketplaceId]);
  echo json_encode(['ok' => true]);
  exit;
}

http_response_code(405);