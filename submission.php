// submission.php

<?php
session_start();
require 'includes/db.php';

$submission_id = $_GET['id'] ?? null;
if (!$submission_id) {
  echo "Sunum ID eksik.";
  exit;
}

// Sunum bilgisi
$stmt = $db->prepare("SELECT * FROM submissions WHERE id = ?");
$stmt->execute([$submission_id]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

// Ortalama puan
$avg_stmt = $db->prepare("SELECT AVG(rating) as avg_rating FROM ratings WHERE submission_id = ?");
$avg_stmt->execute([$submission_id]);
$avg = round($avg_stmt->fetchColumn(), 1);

// Bildirim sonucu
$report_success = isset($_GET['report']) && $_GET['report'] === 'ok';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Sunum Detayı</title>
</head>
<body>
  <h2><?= htmlspecialchars($submission['title']) ?></h2>
  <p><?= htmlspecialchars($submission['description']) ?></p>
  <p><strong>Ortalama Puan:</strong> <?= $avg ?> ⭐</p>

  <?php if (isset($_SESSION['user_id'])): ?>
    <form method="POST" action="rate.php">
      <input type="hidden" name="submission_id" value="<?= $submission_id ?>">
      <label for="rating">Sunuma puan ver:</label>
      <select name="rating" id="rating" required>
        <option value="1">⭐</option>
        <option value="2">⭐⭐</option>
        <option value="3">⭐⭐⭐</option>
        <option value="4">⭐⭐⭐⭐</option>
        <option value="5">⭐⭐⭐⭐⭐</option>
      </select>
      <button type="submit">Gönder</button>
    </form>

    <hr>

    <form method="POST" action="report.php">
      <input type="hidden" name="submission_id" value="<?= $submission_id ?>">
      <label for="issue">Admine ilet:</label><br>
      <textarea name="issue" placeholder="Sorununuzu yazın..." required></textarea><br>
      <button type="submit">Gönder</button>
    </form>

    <?php if ($report_success): ?>
      <p style="color: green;">Sorununuz iletildi ✅</p>
    <?php endif; ?>
  <?php else: ?>
    <p>Yıldızlama ve bildirim için giriş yapmalısınız.</p>
  <?php endif; ?>
</body>
</html>