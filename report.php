// report.php

<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$submission_id = $_POST['submission_id'] ?? null;
$issue = trim($_POST['issue'] ?? '');

if (!$submission_id || !$issue) {
  echo "Eksik veri.";
  exit;
}

$stmt = $db->prepare("INSERT INTO reports (user_id, submission_id, issue) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $submission_id, $issue]);

header("Location: submission.php?id=$submission_id&report=ok");
exit;
?>