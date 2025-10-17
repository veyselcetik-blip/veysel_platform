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
  <h2>Oylar</h2>
  <?php
  $stmt = $db->query("SELECT v.id, u.username, p.title, v.vote_value FROM votes v
                      JOIN users u ON v.user_id = u.id
                      JOIN projects p ON v.project_id = p.id
                      ORDER BY v.id DESC");
  while ($row = $stmt->fetch()):
  ?>
    <div class="card" style="margin-bottom:15px;">
      <strong><?= htmlspecialchars($row['username']) ?></strong> → 
      <em><?= htmlspecialchars($row['title']) ?></em> için oy verdi: 
      <span><?= $row['vote_value'] ?></span>
    </div>
  <?php endwhile; ?>
</div>

<?php include 'includes/footer.php'; ?>