<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  echo "Yetkisiz erişim.";
  exit;
}

$user_id = $_POST['user_id'] ?? null;
if ($user_id) {
  $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
}

header("Location: admin-panel.php");
exit;