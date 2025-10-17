// rate.php

<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$submission_id = $_POST['submission_id'] ?? null;
$rating = $_POST['rating'] ?? null;

if (!$submission_id || !$rating) {
  echo "Eksik veri.";
  exit;
}

// Puanı kaydet (varsa güncelle)
$stmt = $db->prepare("INSERT INTO ratings (user_id, submission_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?");
$stmt->execute([$user_id, $submission_id, $rating, $rating]);

// Bildirim gönder
$msg = "Sunumuna $rating yıldız verildi.";
$notify = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
$notify->execute([$user_id, $msg]);

header("Location: submission.php?id=$submission_id");
exit;
?>