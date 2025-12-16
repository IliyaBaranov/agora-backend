<?php
require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

/* -------------------------------------------------------
   GET — список всех маркетплейсов
------------------------------------------------------- */
if ($method === 'GET') {
    $stmt = $pdo->query('
        SELECT *
        FROM marketplaces
        ORDER BY created_at DESC
    ');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

/* -------------------------------------------------------
   POST — создание маркетплейса
------------------------------------------------------- */
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

    // создаём маркетплейс
    $stmt = $pdo->prepare('
        INSERT INTO marketplaces (name, slug, city, created_at, owner_id)
        VALUES (?, ?, ?, NOW(), ?)
    ');
    $stmt->execute([$name, $slug, $city, $_SESSION['user_id']]);

    $marketplaceId = (int)$pdo->lastInsertId();

    // добавляем создателя как ADMIN
    $stmt = $pdo->prepare('
        INSERT INTO marketplace_users
        (user_id, marketplace_id, role, approval_status, status)
        VALUES (?, ?, "ADMIN", "APPROVED", "ONLINE")
    ');
    $stmt->execute([$_SESSION['user_id'], $marketplaceId]);

    echo json_encode([
        'id' => $marketplaceId,
        'name' => $name,
        'slug' => $slug,
        'city' => $city,
        'ownerId' => (int)$_SESSION['user_id'],
        'createdAt' => date('c'),
    ]);
    exit;
}

http_response_code(405);
