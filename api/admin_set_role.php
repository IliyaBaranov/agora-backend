<?php
require_once __DIR__ . '/config.php';
require_auth();

$data = json_decode(file_get_contents("php://input"), true);

$marketplaceId = (int)($data["marketplaceId"] ?? 0);
$targetUserId  = (int)($data["userId"] ?? 0);

// Может прийти:
// 1) status = APPROVED / REJECTED  → для продюсеров
// 2) role = ADMIN                  → назначить админом
$status = $data["status"] ?? null;
$newRole = $data["role"] ?? null;

// Проверяем ID
if ($marketplaceId <= 0 || $targetUserId <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid IDs"]);
    exit;
}

/* -------------------------------------------------------
   Проверяем: вызывающий должен быть АДМИН маркетплейса
------------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT role FROM marketplace_users
    WHERE user_id = ? AND marketplace_id = ?
");
$stmt->execute([$_SESSION["user_id"], $marketplaceId]);
$caller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$caller || $caller["role"] !== "ADMIN") {
    http_response_code(403);
    echo json_encode(["error" => "Only admin can manage roles"]);
    exit;
}

/* -------------------------------------------------------
   Узнаём владельца (его роль нельзя снять)
------------------------------------------------------- */
$stmt = $pdo->prepare("SELECT owner_id FROM marketplaces WHERE id = ?");
$stmt->execute([$marketplaceId]);
$ownerId = (int)$stmt->fetchColumn();


/* #######################################################
   1) НАЗНАЧЕНИЕ АДМИНА (role: ADMIN)
########################################################*/
if ($newRole === "ADMIN") {

    // Нельзя снять владельца
    if ($targetUserId === $ownerId) {
        echo json_encode(["error" => "Owner must remain admin"]);
        exit;
    }

    // Создать/обновить роль
    $stmt = $pdo->prepare("
        INSERT INTO marketplace_users (user_id, marketplace_id, role, approval_status)
        VALUES (?, ?, 'ADMIN', 'APPROVED')
        ON DUPLICATE KEY UPDATE role = 'ADMIN', approval_status = 'APPROVED'
    ");
    $stmt->execute([$targetUserId, $marketplaceId]);

    echo json_encode(["success" => true, "role" => "ADMIN"]);
    exit;
}


/* #######################################################
   2) APPROVE / REJECT продюсера
########################################################*/

if ($status) {
    if ($status !== "APPROVED" && $status !== "REJECTED") {
        echo json_encode(["error" => "Invalid status"]);
        exit;
    }

    // Выбираем роль для пользователя
    $roleAfter = $status === "APPROVED" ? "PRODUCER" : "CUSTOMER";

    // Обновить запись
    $stmt = $pdo->prepare("
        UPDATE marketplace_users
        SET approval_status = ?, role = ?
        WHERE user_id = ? AND marketplace_id = ?
    ");
    $stmt->execute([$status, $roleAfter, $targetUserId, $marketplaceId]);

    echo json_encode([
        "success" => true,
        "status" => $status,
        "role" => $roleAfter
    ]);
    exit;
}


/* -------------------------------------------------------
   Если ни role, ни status не пришли → ошибка
------------------------------------------------------- */
echo json_encode(["error" => "No valid action"]);
