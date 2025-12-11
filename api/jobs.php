<?php
require 'config.php';
require_auth();

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
  // создать job
  $data = json_decode(file_get_contents('php://input'), true);
  $marketplaceId = (int)($data['marketplaceId'] ?? 0);
  $title = trim($data['title'] ?? '');
  $description = trim($data['description'] ?? '');
  $address = trim($data['address'] ?? '');
  $preferredTime = trim($data['preferredTime'] ?? '');
  $price = (int)($data['price'] ?? 0);
  $lat = isset($data['lat']) ? (float)$data['lat'] : null;
  $lng = isset($data['lng']) ? (float)$data['lng'] : null;

  $stmt = $pdo->prepare(
    'INSERT INTO jobs
     (marketplace_id, customer_id, title, description, address, preferred_time, price, is_paid, status, lat, lng, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, 0, "OPEN", ?, ?, NOW())'
  );
  $stmt->execute([$marketplaceId, $userId, $title, $description, $address, $preferredTime, $price, $lat, $lng]);
  $id = $pdo->lastInsertId();

  echo json_encode(['id' => (int)$id]);
  exit;
}

if ($method === 'PUT') {
  $data = json_decode(file_get_contents('php://input'), true);
  $action = $data['action'] ?? '';
  $jobId = (int)($data['jobId'] ?? 0);

  if ($action === 'PAY') {

      // Получаем задание
      $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
      $stmt->execute([$jobId]);
      $job = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$job) {
          echo json_encode(["error" => "Job not found"]);
          exit;
      }

      if ($job['is_paid']) {
          echo json_encode(["error" => "Already paid"]);
          exit;
      }

      // Проверяем баланс покупателя
      $stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
      $stmt->execute([$job['customer_id']]);
      $credits = (int)$stmt->fetchColumn();

      if ($credits < $job['price']) {
          echo json_encode(["error" => "Not enough credits"]);
          exit;
      }

      // Списываем кредиты у клиента
      $stmt = $pdo->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
      $stmt->execute([$job['price'], $job['customer_id']]);

      // Оплачиваем продюсеру
      $stmt = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
      $stmt->execute([$job['price'], $job['producer_id']]);

      // Записываем статистику продюсера
      $stmt = $pdo->prepare("
          UPDATE marketplace_users
          SET 
              earnings = earnings + ?,
              completed_jobs = completed_jobs + 1
          WHERE user_id = ? AND marketplace_id = ?
      ");
      $stmt->execute([$job['price'], $job['producer_id'], $job['marketplace_id']]);

      // Обновляем задание
      $stmt = $pdo->prepare("UPDATE jobs SET is_paid = 1, status = 'COMPLETED' WHERE id = ?");
      $stmt->execute([$jobId]);

      echo json_encode(["success" => true]);
      exit;
  }
}


// и т.д. – takeJob, completeJob, payForJob можно реализовать по аналогии
