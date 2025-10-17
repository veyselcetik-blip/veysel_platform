<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $receiver_id = $_POST['receiver_id'];
  $subject = trim($_POST['subject']);
  $body = trim($_POST['body']);

  $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, subject, body) VALUES (?, ?, ?, ?)");
  $stmt->execute([$_SESSION['user_id'], $receiver_id, $subject, $body]);

  header("Location: inbox.php");
  exit;
}

include 'header.php';
?>

<h2>Mesaj Gönder</h2>
<form method="POST">
  <label>Alıcı Kullanıcı ID:</label>
  <input type="number" name="receiver_id" required>
  <label>Konu:</label>
  <input type="text" name="subject">
  <label>Mesaj:</label>
  <textarea name="body" required></textarea>
  <button type="submit">Gönder</button>
</form>

<?php include 'footer.php'; ?>