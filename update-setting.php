<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  echo "Yetkisiz erişim.";
  exit;
}

$welcome = trim($_POST['welcome_message']);
$footer = trim($_POST['footer_note']);

$stmt = $db->prepare("UPDATE site_settings SET welcome_message = ?, footer_note = ? WHERE id = 1");
$stmt->execute([$welcome, $footer]);

header("Location: dashboard.php");
exit;