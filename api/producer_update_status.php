<?php
require_once __DIR__ . '/config.php';
require_auth();

$data = json_decode(file_get_contents("php://input"), true);

$marketplaceId = (int)($data["marketplaceId"] ?? 0);
$status = $data["status"] ?? "";
$userId = $_SESSION["user_id"];

$allowed = ["ONLINE", "OFFLINE", "WORKING"];
if (!in_array($status, $allowed)) {
    echo json_encode(["error" => "Invalid status"]);
    exit;
}

$stmt = $pdo->prepare("
  UPDATE marketplace_users 
  SET status = ? 
  WHERE marketplace_id = ? AND user_id = ?
");
$stmt->execute([$status, $marketplaceId, $userId]);

echo json_encode(["success" => true, "status" => $status]);
