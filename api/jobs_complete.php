<?php
require_once __DIR__ . '/config.php';
require_auth();

$userId = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);
$jobId = (int)$data["jobId"];

if ($jobId <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid job ID"]);
    exit;
}

// Проверка job
$stmt = $pdo->prepare("SELECT marketplace_id, producer_id, price FROM jobs WHERE id = ?");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo json_encode(["error" => "Job not found"]);
    exit;
}

if ((int)$job["producer_id"] !== (int)$userId) {
    echo json_encode(["error" => "Not your job"]);
    exit;
}

// Обновляем статус job
$stmt = $pdo->prepare("UPDATE jobs SET status = 'COMPLETED' WHERE id = ?");
$stmt->execute([$jobId]);

// Добавить +1 выполненное задание
$stmt = $pdo->prepare("
    UPDATE marketplace_users
    SET completed_jobs = COALESCE(completed_jobs, 0) + 1,
        earnings = COALESCE(earnings, 0) + ?
    WHERE user_id = ? AND marketplace_id = ?
");
$stmt->execute([$job["price"], $userId, $job["marketplace_id"]]);

echo json_encode(["ok" => true]);
