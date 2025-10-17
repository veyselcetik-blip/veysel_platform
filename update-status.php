<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  echo "Yetkisiz erişim.";
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $project_id = $_POST['project_id'];
  $new_status = $_POST['status'];
  $stmt = $db->prepare("UPDATE projects SET status = ? WHERE id = ?");
  $stmt->execute([$new_status, $project_id]);
  header("Location: browse-projects.php");
  exit;
}

$stmt = $db->query("SELECT * FROM projects ORDER BY created_at DESC");
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
include 'header.php';
?>

<h2>Proje Durumu Güncelle</h2>
<?php foreach ($projects as $proj): ?>
  <form method="POST" style="margin-bottom:20px;">
    <h3><?= htmlspecialchars($proj['title']) ?></h3>
    <input type="hidden" name="project_id" value="<?= $proj['id'] ?>">
    <select name="status">
      <option value="aktif" <?= $proj['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
      <option value="kazanan_seçildi" <?= $proj['status'] === 'kazanan_seçildi' ? 'selected' : '' ?>>Kazanan Seçildi</option>
      <option value="tamamlandı" <?= $proj['status'] === 'tamamlandı' ? 'selected' : '' ?>>Tamamlandı</option>
    </select>
    <button type="submit">Güncelle</button>
  </form>
<?php endforeach; ?>

<?php include 'footer.php'; ?>