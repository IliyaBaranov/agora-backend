<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  // список всех маркетплейсов
  $stmt = $pdo->query('SELECT * FROM marketplaces ORDER BY created_at DESC');
  echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
  exit;
}

if ($method === 'POST') {
  require_auth();
  $data = json_decode(file_get_contents('php://input'), true);

  $name = trim($data['name'] ?? '');
  $slug = trim($data['slug'] ?? '');
  $city = trim($data['city'] ?? '');

  if (!$name || !$slug || !$city) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing fields']);
    exit;
  }

  $stmt = $pdo->prepare('INSERT INTO marketplaces (name, slug, city, created_at, owner_id) VALUES (?, ?, ?, NOW(), ?)');
  $stmt->execute([$name, $slug, $city, $_SESSION['user_id']]);
  $id = $pdo->lastInsertId();

  // добавить создателя как ADMIN
  $stmt = $pdo->prepare(
    'INSERT INTO marketplace_users (user_id, marketplace_id, role, approval_status, status)
     VALUES (?, ?, "ADMIN", "APPROVED", "ONLINE")'
  );
  $stmt->execute([$_SESSION['user_id'], $id]);

  echo json_encode([
    'id' => (int)$id,
    'name' => $name,
    'slug' => $slug,
    'city' => $city,
    'ownerId' => (int)$_SESSION['user_id'],
    'createdAt' => date('c'),
  ]);
  exit;
}

http_response_code(405);
