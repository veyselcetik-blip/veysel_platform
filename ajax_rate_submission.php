<?php
require 'includes/init.php';
header('Content-Type: application/json');

if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') { exit; }

$submission_id = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$submission_id || !$rating || $rating < 1 || $rating > 5) { exit; }

// Güvenlik: Puan veren kişinin, bu sunumun ait olduğu projenin sahibi olduğundan emin ol.
$stmt = $db->prepare("SELECT p.user_id FROM projects p JOIN submissions s ON p.id = s.project_id WHERE s.id = ?");
$stmt->execute([$submission_id]);
$project_owner_id = $stmt->fetchColumn();

if ($project_owner_id != $user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Sadece proje sahibi puanlama yapabilir.']);
    exit;
}

// Puanı veritabanına ekle veya güncelle
$stmt_rate = $db->prepare("INSERT INTO ratings (submission_id, user_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating)");
$stmt_rate->execute([$submission_id, $user_id, $rating]);

echo json_encode(['status' => 'success']);
exit;
?>