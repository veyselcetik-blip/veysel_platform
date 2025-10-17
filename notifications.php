<?php
require 'includes/init.php';
require_login(); // Sadece giriş yapanlar görebilir

// Kullanıcının tüm bildirimlerini en yeniden eskiye doğru çek
$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Sayfa yüklendiğinde, okunmamış tüm bildirimleri "okundu" olarak işaretle
$update_stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$update_stmt->execute([$user_id]);

include 'includes/header.php';
?>

<?php include 'includes/navbar.php'; ?>

<header class="page-header">
    <div class="container">
        <h1>Bildirimlerim</h1>
        <p>Hesabınızla ve projelerinizle ilgili son güncellemeler.</p>
    </div>
</header>

<main class="container page-container">
    <div class="notification-list">
        <?php if (empty($notifications)): ?>
            <div class="no-notifications">
                <i class="fas fa-bell-slash"></i>
                <p>Henüz hiç bildiriminiz yok.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <a href="<?= htmlspecialchars($notification['link'] ?? '#') ?>" class="notification-item <?= $notification['is_read_before_load'] ?? 'unread' ?>">
                    <div class="notification-icon"><i class="fas fa-flag"></i></div>
                    <div class="notification-content">
                        <p><?= $notification['message'] // Mesaj zaten HTML içeriyor, tekrar escape etme ?></p>
                        <span class="notification-date"><?= date('d M Y, H:i', strtotime($notification['created_at'])) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>