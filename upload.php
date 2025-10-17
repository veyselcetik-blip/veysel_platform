<?php
require 'includes/init.php';
require_login(); // Sadece giriş yapanlar yükleyebilir

$projectId = $_GET['id'] ?? 0;
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
  
    // YENİ: Güvenli dosya yükleme fonksiyonunu kullan
    $upload_result = secure_file_upload($_FILES['file']);

    if (isset($upload_result['success'])) {
        $filepath = $upload_result['filepath'];
        $stmt = $db->prepare("INSERT INTO uploads (user_id, project_id, filename) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $projectId, $filepath]);
        $success = "Dosya başarıyla yüklendi.";
    } else {
        $error = $upload_result['error'];
    }
}

include 'includes/header.php';
?>
<div class="container">
  <h2>Proje <?= (int)$projectId ?> için Dosya Yükle</h2>
  <?php if ($success): ?><p style="color:green;"><?= htmlspecialchars($success) ?></p><?php endif; ?>
  <?php if ($error): ?><p style="color:red;"><?= htmlspecialchars($error) ?></p><?php endif; ?>
  <form method="POST" enctype="multipart/form-data">
    <label>Dosya Seç (JPG, PNG, PDF - Maks 5MB)</label>
    <input type="file" name="file" required>
    <button type="submit">Yükle</button>
  </form>
</div>
<?php include 'includes/footer.php'; ?>