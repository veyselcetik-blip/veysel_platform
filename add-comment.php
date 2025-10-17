<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$project_id = $_GET['project_id'] ?? null;
if (!$project_id) exit("Proje ID eksik.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $comment = trim($_POST['comment']);
  $stmt = $db->prepare("INSERT INTO comments (project_id, user_id, comment) VALUES (?, ?, ?)");
  $stmt->execute([$project_id, $_SESSION['user_id'], $comment]);
  header("Location: browse-projects.php");
  exit;
}

include 'header.php';
?>

<h2>Yorum Yap</h2>
<form method="POST">
  <label>Yorumunuz:</label>
  <textarea name="comment" required></textarea>
  <button type="submit">Gönder</button>
</form>

<?php include 'footer.php'; ?>