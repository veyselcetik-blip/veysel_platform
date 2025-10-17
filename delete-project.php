<?php
require 'includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // YENİ: CSRF Token kontrolü
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        die('Geçersiz CSRF token!');
    }

    $pid = $_POST['project_id'];
    $stmt = $db->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$pid]);
    echo "<div class='container'><p style='color:green;'>Proje silindi.</p></div>";
}

include 'includes/header.php';
?>
<div class="container">
  <h2>Proje Sil</h2>
  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
    
    <label>Proje ID</label>
    <input type="number" name="project_id" required>
    <button type="submit">Sil</button>
  </form>
</div>
<?php include 'includes/footer.php'; ?>