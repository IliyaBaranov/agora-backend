<?php
require_once __DIR__ . '/config.php';

$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$name || !$email || !$password) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing fields']);
  exit;
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
  http_response_code(400);
  echo json_encode(['error' => 'Email already exists']);
  exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
  'INSERT INTO users (name, email, password_hash, credits, created_at)
   VALUES (?, ?, ?, 100, NOW())'
);
$stmt->execute([$name, $email, $hash]);

$userId = $pdo->lastInsertId();

$_SESSION['user_id'] = $userId;

echo json_encode([
  'id' => (int)$userId,
  'name' => $name,
  'email' => $email,
  'credits' => 100,
  'createdAt' => date('c'),
]);
