<?php
$unread_notifications_count = 0;
$unread_messages_count = 0; // Yeni mesaj sayacı için değişken
$user_avatar = '';

if (is_logged_in()) {
    $current_user_id = $_SESSION['user_id'];
    
    // 1. Okunmamış BİLDİRİM sayısını al
    $count_stmt_notifications = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $count_stmt_notifications->execute([$current_user_id]);
    $unread_notifications_count = $count_stmt_notifications->fetchColumn();

    // === YENİ EKLENEN BÖLÜM: Okunmamış MESAJ sayısını al ===
    $count_stmt_messages = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $count_stmt_messages->execute([$current_user_id]);
    $unread_messages_count = $count_stmt_messages->fetchColumn();
    // === YENİ BÖLÜM SONU ===

    $user_avatar = get_user_avatar($current_user_id, $_SESSION['username']);
}
?>
<nav class="navbar">
    <div class="container">
        <a href="index.php" class="nav-logo"><?= htmlspecialchars($site_settings['site_name']) ?></a>
        
        <div class="nav-menu">
            <div class="nav-main-links">
                <a href="browse-projects.php">Projeleri İncele</a>
                <a href="about.php">Hakkımızda</a>
                <a href="contact.php">İletişim</a>
            </div>

            <div class="nav-user-actions">
                <?php if (is_logged_in()): ?>
                    <a href="create-project.php" class="nav-button">Proje Başlat</a>
                    
                    <a href="notifications.php" class="notification-bell">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications_count > 0): ?>
                            <span class="notification-badge"><?= $unread_notifications_count ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <div class="profile-dropdown">
                        <button class="profile-btn" id="profileDropdownBtn">
                            <img src="<?= $user_avatar ?>" alt="Profil Resmi">
                            <?php // === YENİ EKLENEN BÖLÜM: Ana profil ikonuna mesaj uyarısı === ?>
                            <?php if ($unread_messages_count > 0): ?>
                                <span class="notification-badge" style="top: -2px; right: -5px; background-color: var(--secondary-color);"><i class="fas fa-envelope" style="font-size: 0.6rem;"></i></span>
                            <?php endif; ?>
                            <?php // === YENİ BÖLÜM SONU === ?>
                        </button>
                        <div class="dropdown-content" id="profileDropdownContent">
                            <div class="dropdown-header"><strong><?= htmlspecialchars($_SESSION['username']) ?></strong><span>Kullanıcı</span></div>
                            <a href="dashboard.php"><i class="fas fa-th-large"></i> Panelim</a>
                            
                            <?php // === YENİ EKLENEN BÖLÜM: Mesajlar linki ve sayacı === ?>
                            <a href="mesajlar.php">
                                <i class="fas fa-envelope"></i> Mesajlarım
                                <?php if ($unread_messages_count > 0): ?>
                                    <span class="notification-badge-menu"><?= $unread_messages_count ?></span>
                                <?php endif; ?>
                            </a>
                            <?php // === YENİ BÖLÜM SONU === ?>
                            
                            <a href="edit-profile.php"><i class="fas fa-user-edit"></i> Profili Düzenle</a>
                            <?php if (is_admin()): ?>
                                <a href="admin/" class="admin-link"><i class="fas fa-cogs"></i> Yönetim Paneli</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
                        </div>
                    </div>
                <?php else: ?>
                    <button class="nav-button-secondary open-login-modal">Giriş Yap</button>
                    <button class="nav-button open-register-modal">Proje Başlat</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>