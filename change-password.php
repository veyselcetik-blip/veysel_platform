<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $current = $_POST['current_password'];
  $new = $_POST['new_password'];

  $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
  $stmt->execute([$user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (password_verify($current, $user['password'])) {
    $new_hash = password_hash($new, PASSWORD_DEFAULT);
    $update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $update->execute([$new_hash, $user_id]);
    $success = true;
  } else {
    $error = "Mevcut şifre yanlış.";
  }
}

include 'header.php';
?>

<h2>Şifre Değiştir</h2>
<?php if (isset($success)): ?>
  <p style="color:green;">Şifre başarıyla değiştirildi ✅</p>
<?php elseif (isset($error)): ?>
  <p style="color:red;"><?= $error ?></p>
<?php endif; ?>
<form method="POST">
  <label>Mevcut Şifre:</label>
  <input type="password" name="current_password" required>

  <label>Yeni Şifre:</label>
  <input type="password" name="new_password" required>

  <button type="submit">Değiştir</button>
</form>

<?php include 'footer.php'; ?>