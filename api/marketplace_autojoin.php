<?php
require 'config.php';
require_auth();

$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);
$marketplaceId = (int)($data["marketplaceId"] ?? 0);

if ($marketplaceId <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid marketplace ID"]);
    exit;
}

/* -------------------------------------------------------
   Проверяем, есть ли уже связь user ↔ marketplace
------------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT id, role
    FROM marketplace_users
    WHERE user_id = ? AND marketplace_id = ?
");
$stmt->execute([$userId, $marketplaceId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    // ✅ запись уже есть — НИЧЕГО НЕ СОЗДАЁМ
    echo json_encode([
        "status" => "ok",
        "role" => $row["role"]
    ]);
    exit;
}

/* -------------------------------------------------------
   Записи нет → создаём CUSTOMER
------------------------------------------------------- */
$stmt = $pdo->prepare("
    INSERT INTO marketplace_users
    (user_id, marketplace_id, role, status, approval_status)
    VALUES (?, ?, 'CUSTOMER', 'OFFLINE', 'NONE')
");
$stmt->execute([$userId, $marketplaceId]);

echo json_encode([
    "status" => "created",
    "role" => "CUSTOMER"
]);
