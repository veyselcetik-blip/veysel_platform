<?php
require 'includes/auth.php';
require 'includes/db.php';
include 'includes/header.php';

$projectId = $_GET['id'] ?? 0;
$userId = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $vote = $_POST['vote'];

  $check = $db->prepare("SELECT COUNT(*) FROM votes WHERE user_id = ? AND project_id = ?");
  $check->execute([$userId, $projectId]);
  if ($check->fetchColumn()) {
    $error = "Bu projeye zaten oy verdiniz.";
  } else {
    $stmt = $db->prepare("INSERT INTO votes (user_id, project_id, vote_value) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $projectId, $vote]);
    $success = "Oyunuz başarıyla kaydedildi.";
  }
}
?>

<div class="container">
  <h2>Proje <?= $projectId ?> için Oy Ver</h2>
  <?php if ($success): ?><p style="color:green;"><?= htmlspecialchars($success) ?></p><?php endif; ?>
  <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <form method="POST">
    <label>Oyunuz</label>
    <select name="vote" required>
      <option value="">Seçiniz</option>
      <option value="1">1 - Zayıf</option>
      <option value="2">2 - Orta</option>
      <option value="3">3 - İyi</option>
      <option value="4">4 - Çok İyi</option>
      <option value="5">5 - Mükemmel</option>
    </select>
    <button type="submit">Oy Ver</button>
  </form>
</div>

<?php include 'includes/footer.php'; ?>