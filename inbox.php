<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT m.*, u.username AS sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.receiver_id = ? ORDER BY m.created_at DESC");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'header.php';
?>

<h2>Gelen Mesajlar</h2>
<?php if ($messages): ?>
  <?php foreach ($messages as $msg): ?>
    <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
      <strong><?= htmlspecialchars($msg['subject']) ?></strong><br>
      <p><?= htmlspecialchars($msg['body']) ?></p>
      <p><small>Gönderen: <?= htmlspecialchars($msg['sender_name']) ?> | <?= $msg['created_at'] ?></small></p>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <p>Henüz mesajınız yok.</p>
<?php endif; ?>

<?php include 'footer.php'; ?>