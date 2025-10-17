<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$proposal_id = $_GET['proposal_id'] ?? null;
if (!$proposal_id) exit("Sunum ID eksik.");

$stmt = $db->prepare("SELECT * FROM likes WHERE proposal_id = ? AND user_id = ?");
$stmt->execute([$proposal_id, $_SESSION['user_id']]);
$alreadyLiked = $stmt->fetch();

if (!$alreadyLiked) {
  $insert = $db->prepare("INSERT INTO likes (proposal_id, user_id) VALUES (?, ?)");
  $insert->execute([$proposal_id, $_SESSION['user_id']]);
}

header("Location: browse-projects.php");
exit;