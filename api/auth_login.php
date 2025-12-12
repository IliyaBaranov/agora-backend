<?php
require_once __DIR__ . '/config.php';

$data = json_decode(file_get_contents('php://input'), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Invalid credentials']);
  exit;
}

$_SESSION['user_id'] = $user['id'];

echo json_encode([
  'id' => (int)$user['id'],
  'name' => $user['name'],
  'email' => $user['email'],
  'credits' => (int)$user['credits'],
  'createdAt' => date('c', strtotime($user['created_at'])),
]);
