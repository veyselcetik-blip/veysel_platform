<?php

// Bu dosyanın en başındaki PHP kodları aynı kalacak
$unread_count = 0;
$user_avatar = '';
if (is_logged_in()) {
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $count_stmt->execute([$_SESSION['user_id']]);
    $unread_count = $count_stmt->fetchColumn();
    $user_avatar = get_user_avatar($_SESSION['user_id'], $_SESSION['username']);
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
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="profile-dropdown">
                        <button class="profile-btn" id="profileDropdownBtn">
                            <img src="<?= $user_avatar ?>" alt="Profil Resmi">
                        </button>
                        <div class="dropdown-content" id="profileDropdownContent">
                            <div class="dropdown-header"><strong><?= htmlspecialchars($_SESSION['username']) ?></strong><span>Kullanıcı</span></div>
                            <a href="dashboard.php"><i class="fas fa-th-large"></i> Panelim</a>
                            <a href="edit-profile.php"><i class="fas fa-user-edit"></i> Profili Düzenle</a>
                            <?php if (is_admin()): ?>
                                <a href="admin/" class="admin-link"><i class="fas fa-cogs"></i> Yönetim Paneli</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
                        </div>
                    </div>
                <?php else: ?>
                    <button class="nav-button-secondary" id="openLoginModalBtn">Giriş Yap</button>
                    <button class="nav-button" id="openRegisterModalBtnNav">Proje Başlat</button>
                <?php endif; ?>
            </div>
        </div>
        </div>
</nav>