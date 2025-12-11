<?php
require 'config.php';
require_auth();

$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);
$marketplaceId = (int)($data["marketplaceId"] ?? 0);

if ($marketplaceId <= 0) {
    echo json_encode(["error" => "Invalid marketplace ID"]);
    exit;
}

// Проверяем, есть ли уже запись
$stmt = $pdo->prepare("
    SELECT id FROM marketplace_users 
    WHERE user_id = ? AND marketplace_id = ?
");
$stmt->execute([$userId, $marketplaceId]);

if ($stmt->fetch()) {
    echo json_encode(["status" => "exists"]);
    exit;
}

// Добавляем пользователя как CUSTOMER
$stmt = $pdo->prepare("
    INSERT INTO marketplace_users 
    (user_id, marketplace_id, role, status, approval_status)
    VALUES (?, ?, 'CUSTOMER', 'OFFLINE', 'NONE')
");
$stmt->execute([$userId, $marketplaceId]);

echo json_encode(["status" => "created"]);
