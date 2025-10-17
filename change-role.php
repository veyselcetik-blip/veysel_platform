<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  echo "Yetkisiz erişim.";
  exit;
}

$user_id = $_POST['user_id'] ?? null;
$new_role = $_POST['new_role'] ?? null;

if ($user_id && in_array($new_role, ['user', 'admin'])) {
  $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
  $stmt->execute([$new_role, $user_id]);
}

header("Location: admin-panel.php");
exit;