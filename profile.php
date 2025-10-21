<?php
require 'includes/init.php';

// ... (Mevcut kullanıcı bilgisi çekme kodlarınız aynı kalabilir)
$username_to_view = $_GET['username'] ?? (is_logged_in() ? $_SESSION['username'] : '');
if (empty($username_to_view)) { require_login(); }
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username_to_view]);
$profile_user = $stmt->fetch();
if (!$profile_user) { die("Hata: Kullanıcı bulunamadı."); }

// --- ZAMAN ÇİZELGESİ İÇİN VERİ ÇEKME ---
// Kazanılan projeler ve alınan 5 yıldızlı yorumları birleştirip tarihe göre sırala
$timeline_items = [];
// Kazanılan projeler
$won_projects_stmt = $db->prepare("SELECT id, title, 'project_win' as type, created_at FROM projects WHERE winner_id = ? ORDER BY created_at DESC");
$won_projects_stmt->execute([$profile_user['id']]);
$timeline_items = array_merge($timeline_items, $won_projects_stmt->fetchAll());

// Alınan 5 yıldızlı yorumlar
$reviews_stmt = $db->prepare("SELECT r.id, p.title, 'five_star' as type, r.created_at FROM reviews r JOIN projects p ON r.project_id = p.id WHERE r.designer_id = ? AND r.rating = 5 ORDER BY r.created_at DESC");
$reviews_stmt->execute([$profile_user['id']]);
$timeline_items = array_merge($timeline_items, $reviews_stmt->fetchAll());

// Tüm olayları tarihe göre yeniden sırala
usort($timeline_items, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});


// Diğer istatistikler ve portfolyo
$won_projects_count = count(array_filter($timeline_items, function($item) { return $item['type'] == 'project_win'; }));
$submission_count = $db->query("SELECT COUNT(*) FROM submissions WHERE user_id = {$profile_user['id']}")->fetchColumn();
$portfolio_items = $db->prepare("SELECT * FROM submissions WHERE user_id = ? ORDER BY id DESC");
$portfolio_items->execute([$profile_user['id']]);
$portfolio = $portfolio_items->fetchAll();

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="profile-v5-container">
    <aside class="profile-v5-sidebar">
        <div class="sidebar-content">
            <div class="profile-v5-avatar">
                <img src="<?= get_user_avatar($profile_user['id'], $profile_user['username']) ?>" alt="<?= htmlspecialchars($profile_user['username']) ?>">
            </div>
            <h1><?= htmlspecialchars($profile_user['username']) ?></h1>
            <p class="profile-v5-title"><?= htmlspecialchars($profile_user['title'] ?? 'Tasarımcı') ?></p>
            <div class="profile-v5-actions">
                <?php if(is_logged_in() && $_SESSION['user_id'] == $profile_user['id']): ?>
                    <a href="edit-profile.php" class="btn btn-secondary">Profili Düzenle</a>
                <?php else: ?>
                    <a href="mesajlar.php?yeni=<?= htmlspecialchars($profile_user['username']) ?>" class="btn btn-primary">Mesaj Gönder</a>
                <?php endif; ?>
            </div>
            <div class="profile-v5-stats">
                <div class="stat-item"><strong><?= $won_projects_count ?></strong><span>Kazanılan Proje</span></div>
                <div class="stat-item"><strong><?= $submission_count ?></strong><span>Toplam Sunum</span></div>
                <div class="stat-item"><strong>%<?= $submission_count > 0 ? round(($won_projects_count / $submission_count) * 100) : 0 ?></strong><span>Başarı Oranı</span></div>
            </div>

            <div class="profile-v5-skills">
                <h3>Yetenek Radarı</h3>
                <canvas id="skillsRadarChart"></canvas>
            </div>
        </div>
    </aside>

    <main class="profile-v5-main">
        <section class="profile-v5-section">
            <h2><i class="fas fa-star"></i> İmza Tasarımlar</h2>
            <div class="signature-work-grid">
                <?php foreach(array_slice($portfolio, 0, 2) as $item): // İlk 2 sunumu imza olarak alalım ?>
                <a href="<?= htmlspecialchars($item['file_path']) ?>" class="signature-item" target="_blank">
                    <img src="<?= htmlspecialchars($item['file_path']) ?>" alt="İmza Tasarım">
                </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="profile-v5-section">
            <h2><i class="fas fa-chart-line"></i> Başarı Zaman Çizelgesi</h2>
            <div class="timeline-v5">
                <?php foreach(array_slice($timeline_items, 0, 5) as $item): // Son 5 olayı gösterelim ?>
                <div class="timeline-v5-item">
                    <div class="timeline-v5-icon <?= $item['type'] ?>">
                        <i class="fas <?= $item['type'] == 'project_win' ? 'fa-trophy' : 'fa-star' ?>"></i>
                    </div>
                    <div class="timeline-v5-content">
                        <strong><?= $item['type'] == 'project_win' ? 'Proje Kazandı' : '5 Yıldızlı Yorum Aldı' ?></strong>
                        <p><?= htmlspecialchars($item['title']) ?></p>
                        <small><?= date('d M Y', strtotime($item['created_at'])) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="profile-v5-section">
            <h2><i class="fas fa-palette"></i> Tüm İşler</h2>
            <div class="portfolio-grid-v5">
                 <?php foreach($portfolio as $item): ?>
                    <a href="<?= htmlspecialchars($item['file_path']) ?>" class="portfolio-item-v5" target="_blank">
                        <img src="<?= htmlspecialchars($item['file_path']) ?>" alt="Portfolyo">
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
</div>

<?php include 'includes/footer.php'; ?>