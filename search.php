<?php
require 'includes/auth.php';
require 'includes/db.php';
include 'includes/header.php';

$keyword = $_GET['q'] ?? '';
?>

<div class="container">
  <h2>Proje Arama</h2>
  <form method="GET">
    <input type="text" name="q" placeholder="Anahtar kelime..." value="<?= htmlspecialchars($keyword) ?>">
    <button type="submit">Ara</button>
  </form>

  <?php if ($keyword): ?>
    <h3>"<?= htmlspecialchars($keyword) ?>" için sonuçlar:</h3>
    <?php
    $stmt = $db->prepare("SELECT * FROM projects WHERE title LIKE ? OR description LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$keyword%", "%$keyword%"]);
    while ($p = $stmt->fetch()):
    ?>
      <div class="card" style="margin-bottom:15px;">
        <h4><?= htmlspecialchars($p['title']) ?></h4>
        <p><?= htmlspecialchars($p['description']) ?></p>
        <a href="project-detail.php?id=<?= $p['id'] ?>"><button>Detay</button></a>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>