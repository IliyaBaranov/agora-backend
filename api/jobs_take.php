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

// Убедиться что job существует и не занят
$stmt = $pdo->prepare("SELECT marketplace_id, status FROM jobs WHERE id = ?");
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo json_encode(["error" => "Job not found"]);
    exit;
}

if ($job["status"] !== "OPEN") {
    echo json_encode(["error" => "Job already taken"]);
    exit;
}

// Назначаем продюсера и ставим статус TAKEN
$stmt = $pdo->prepare("
    UPDATE jobs
    SET producer_id = ?, status = 'TAKEN'
    WHERE id = ?
");
$stmt->execute([$userId, $jobId]);

echo json_encode(["ok" => true]);
