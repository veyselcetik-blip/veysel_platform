<?php
require 'includes/auth.php';
require 'includes/db.php';
include 'includes/header.php';

$userId = $_SESSION['user_id'];
?>

<div class="container">
  <h2>Benim Projelerim</h2>
  <?php
  $stmt = $db->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY id DESC");
  $stmt->execute([$userId]);
  while ($p = $stmt->fetch()):
  ?>
    <div class="card" style="margin-bottom:15px;">
      <h4><?= htmlspecialchars($p['title']) ?></h4>
      <p><?= htmlspecialchars($p['description']) ?></p>
      <a href="project-detail.php?id=<?= $p['id'] ?>"><button>Detay</button></a>
    </div>
  <?php endwhile; ?>
</div>

<?php include 'includes/footer.php'; ?>