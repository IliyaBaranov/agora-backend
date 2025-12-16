<?php
require 'config.php';
require_auth();

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

$jobId = (int)($data["jobId"] ?? 0);
if ($jobId <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid job ID"]);
    exit;
}

// Загружаем job
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    http_response_code(404);
    echo json_encode(["error" => "Job not found"]);
    exit;
}

// Проверяем, что платит именно владелец заказа
if ((int)$job["customer_id"] !== $userId) {
    http_response_code(403);
    echo json_encode(["error" => "Not your job"]);
    exit;
}

$price = (int)$job["price"];

// Проверяем баланс
$stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
$stmt->execute([$userId]);
$credits = (int)$stmt->fetchColumn();

if ($credits < $price) {
    http_response_code(400);
    echo json_encode(["error" => "Not enough credits"]);
    exit;
}

// Списываем кредиты у покупателя
$stmt = $pdo->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
$stmt->execute([$price, $userId]);

// Помечаем job как оплаченный
$stmt = $pdo->prepare("UPDATE jobs SET is_paid = 1 WHERE id = ?");
$stmt->execute([$jobId]);

echo json_encode(["success" => true]);