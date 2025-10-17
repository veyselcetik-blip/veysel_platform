<?php
require 'includes/auth.php';
require 'includes/db.php';
include 'includes/header.php';

$userId = $_SESSION['user_id'];
$isAdmin = ($userId === 1);

if (!$isAdmin) {
  echo "<div class='container'><p style='color:red;'>Bu sayfaya erişim yetkiniz yok.</p></div>";
  include 'includes/footer.php';
  exit;
}
?>

<div class="container">
  <h2>Yüklenen Dosyalar</h2>
  <?php
  $stmt = $db->query("SELECT u.username, p.title, up.filename FROM uploads up
                      JOIN users u ON up.user_id = u.id
                      JOIN projects p ON up.project_id = p.id
                      ORDER BY up.id DESC");
  while ($row = $stmt->fetch()):
  ?>
    <div class="card" style="margin-bottom:15px;">
      <strong><?= htmlspecialchars($row['username']) ?></strong> → 
      <em><?= htmlspecialchars($row['title']) ?></em> için dosya yükledi: 
      <a href="<?= htmlspecialchars($row['filename']) ?>" target="_blank">Dosyayı Gör</a>
    </div>
  <?php endwhile; ?>
</div>

<?php include 'includes/footer.php'; ?>