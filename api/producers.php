<?php
require 'config.php';
require_auth();

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

/**
 * -------------------------------
 *  REGISTER AS PRODUCER (POST)
 * -------------------------------
 */
if ($method === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);

    $marketplaceId = (int)($data['marketplaceId'] ?? 0);
    $description   = trim($data['description'] ?? '');

    if ($marketplaceId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'marketplaceId is required']);
        exit;
    }

    // Проверяем есть ли запись
    $stmt = $pdo->prepare("
        SELECT role, approval_status
        FROM marketplace_users
        WHERE user_id = ? AND marketplace_id = ?
    ");
    $stmt->execute([$userId, $marketplaceId]);
    $mu = $stmt->fetch(PDO::FETCH_ASSOC);

    // уже продюсер → запрет
    if ($mu && $mu["role"] === "PRODUCER") {
        echo json_encode(["error" => "Already a producer"]);
        exit;
    }

    // уже есть заявка
    if ($mu && $mu["approval_status"] === "PENDING") {
        echo json_encode(["error" => "Application already submitted"]);
        exit;
    }

    // уже одобрен (но был CUSTOMER)
    if ($mu && $mu["approval_status"] === "APPROVED") {
        echo json_encode(["error" => "Already approved"]);
        exit;
    }

    // Создаём или обновляем PENDING
    $stmt = $pdo->prepare("
        INSERT INTO marketplace_users (user_id, marketplace_id, role, status, approval_status, description)
        VALUES (?, ?, 'CUSTOMER', 'OFFLINE', 'PENDING', ?)
        ON DUPLICATE KEY UPDATE
            approval_status = 'PENDING',
            description = VALUES(description)
    ");
    $stmt->execute([$userId, $marketplaceId, $description]);

    echo json_encode(["ok" => true]);
    exit;
}


/**
 * -------------------------------
 *    APPROVE / REJECT (PUT)
 * -------------------------------
 */
if ($method === 'PUT') {

    $data = json_decode(file_get_contents('php://input'), true);

    $marketplaceId = (int)($data['marketplaceId'] ?? 0);
    $targetUserId  = (int)($data['userId'] ?? 0);
    $status        = $data['status'] ?? 'APPROVED';

    if ($marketplaceId <= 0 || $targetUserId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'marketplaceId and userId required']);
        exit;
    }

    // Проверяем что вызывающий — АДМИН
    $stmt = $pdo->prepare("
        SELECT role FROM marketplace_users
        WHERE user_id = ? AND marketplace_id = ?
    ");
    $stmt->execute([$userId, $marketplaceId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || $admin['role'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['error' => 'Only admin can approve/reject']);
        exit;
    }

    // Если APPROVE → назначаем роль PRODUCER
    // Если REJECT → возвращаем роль CUSTOMER
    $newRole = $status === 'APPROVED' ? 'PRODUCER' : 'CUSTOMER';

    $stmt = $pdo->prepare("
        UPDATE marketplace_users
        SET approval_status = ?, role = ?
        WHERE user_id = ? AND marketplace_id = ?
    ");

    $stmt->execute([$status, $newRole, $targetUserId, $marketplaceId]);

    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
