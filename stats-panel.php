<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  echo "Yetkisiz erişim.";
  exit;
}

// Toplam kullanıcı
$stmt1 = $db->query("SELECT COUNT(*) FROM users");
$total_users = $stmt1->fetchColumn();

// Toplam proje
$stmt2 = $db->query("SELECT COUNT(*) FROM projects");
$total_projects = $stmt2->fetchColumn();

// Toplam sunum
$stmt3 = $db->query("SELECT COUNT(*) FROM veri_platform_proposal");
$total_proposals = $stmt3->fetchColumn();

// En çok beğenilen sunum
$stmt4 = $db->query("
  SELECT p.id, p.message, COUNT(l.id) AS like_count
  FROM veri_platform_proposal p
  LEFT JOIN likes l ON p.id = l.proposal_id
  GROUP BY p.id
  ORDER BY like_count DESC
  LIMIT 1
");
$top_proposal = $stmt4->fetch(PDO::FETCH_ASSOC);

include 'header.php';
?>

<h2>İstatistik Paneli</h2>
<ul>
  <li><strong>Toplam Kullanıcı:</strong> <?= $total_users ?></li>
  <li><strong>Toplam Proje:</strong> <?= $total_projects ?></li>
  <li><strong>Toplam Sunum:</strong> <?= $total_proposals ?></li>
  <?php if ($top_proposal): ?>
    <li><strong>En Çok Beğenilen Sunum:</strong> <?= htmlspecialchars($top_proposal['message']) ?> (<?= $top_proposal['like_count'] ?> ❤️)</li>
  <?php endif; ?>
</ul>

<?php include 'footer.php'; ?>