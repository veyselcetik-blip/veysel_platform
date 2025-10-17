<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
  exit;
}

$id = $_POST['id'] ?? null;
if ($id) {
  $stmt = $db->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
  $stmt->execute([$id, $_SESSION['user_id']]);
}

header("Location: notifications.php");
exit;