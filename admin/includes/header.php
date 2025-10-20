<?php
// Bu dosya, tüm admin sayfalarının en başında çağrılacak.
require_once '../includes/init.php'; // Ana sitenin init dosyasını çağırıyoruz
require_admin(); // Sadece adminlerin erişebilmesini sağlıyoruz

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yönetim Paneli</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="admin-container">
    <aside class="admin-sidebar">
        <h2>Yönetim Paneli</h2>
 <ul>
    <li class="nav-heading">PANEL</li>
    <li><a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Anasayfa</a></li>
    <li><a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>"><i class="fas fa-flag"></i> Şikayet Yönetimi</a></li>

    <li class="nav-heading">İÇERİK YÖNETİMİ</li>
    <li><a href="projects.php" class="<?= $current_page == 'projects.php' ? 'active' : '' ?>"><i class="fas fa-lightbulb"></i> Proje Yönetimi</a></li>
    <li><a href="submissions.php" class="<?= $current_page == 'submissions.php' ? 'active' : '' ?>"><i class="fas fa-palette"></i> Sunum Yönetimi</a></li>
    <li><a href="comments.php" class="<?= $current_page == 'comments.php' ? 'active' : '' ?>"><i class="fas fa-comments"></i> Yorum Yönetimi</a></li>
    <li><a href="categories.php" class="<?= $current_page == 'categories.php' ? 'active' : '' ?>"><i class="fas fa-tags"></i> Kategori Yönetimi</a></li>
    <li><a href="files.php" class="<?= $current_page == 'files.php' ? 'active' : '' ?>"><i class="fas fa-folder-open"></i> Dosya Yöneticisi</a></li>

    <li class="nav-heading">KULLANICI YÖNETİMİ</li>
    <li><a href="users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Kullanıcı Yönetimi</a></li>
    <li><a href="ip_blocker.php" class="<?= $current_page == 'ip_blocker.php' ? 'active' : '' ?>"><i class="fas fa-ban"></i> IP Engelleme</a></li>

    <li class="nav-heading">SİTE AYARLARI</li>
    <li><a href="settings.php" class="<?= $current_page == 'settings.php' ? 'active' : '' ?>"><i class="fas fa-cog"></i> Genel Ayarlar</a></li>
    <li><a href="email_settings.php" class="<?= $current_page == 'email_settings.php' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> E-posta Ayarları</a></li>
    <li><a href="security_settings.php" class="<?= $current_page == 'security_settings.php' ? 'active' : '' ?>"><i class="fas fa-shield-alt"></i> Güvenlik Ayarları</a></li>
 </ul>

            
    </aside>
    <main class="admin-main">