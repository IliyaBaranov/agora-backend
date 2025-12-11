<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
  echo json_encode(null);
  exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT id, name, email, credits, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  echo json_encode(null);
  exit;
}

// все маркетплейсы
$marketplaces = $pdo->query(
  'SELECT id, name, slug, city, created_at, owner_id FROM marketplaces'
)->fetchAll(PDO::FETCH_ASSOC);

// связи пользователь-маркетплейс
$marketplaceUsers = $pdo->query(
  'SELECT * FROM marketplace_users'
)->fetchAll(PDO::FETCH_ASSOC);

// jobs
$jobs = $pdo->query(
  'SELECT * FROM jobs'
)->fetchAll(PDO::FETCH_ASSOC);

// favorites только текущего
$stmt = $pdo->prepare('SELECT * FROM favorites WHERE user_id = ?');
$stmt->execute([$userId]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// все пользователи (для админа)
$allUsers = $pdo->query(
  'SELECT id, name, email, credits, created_at FROM users'
)->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
  'currentUser' => [
    'id' => (int)$user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'credits' => (int)$user['credits'],
    'createdAt' => date('c', strtotime($user['created_at'])),
  ],
  'marketplaces' => $marketplaces,
  'marketplaceUsers' => $marketplaceUsers,
  'jobs' => $jobs,
  'favorites' => $favorites,
  'allUsers' => $allUsers,
]);
