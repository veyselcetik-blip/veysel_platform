<?php
require 'includes/init.php';

// URL'den kullanıcı adını al. Eğer yoksa ve giriş yapılmışsa, mevcut kullanıcıyı göster.
$username_to_view = $_GET['username'] ?? (is_logged_in() ? $_SESSION['username'] : '');

if (empty($username_to_view)) {
    require_login(); // Eğer hiç kullanıcı adı yoksa ve giriş yapılmamışsa, giriş sayfasına yönlendir.
}

// Profil sahibinin tüm bilgilerini çek
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username_to_view]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    // Daha şık bir 404 sayfasına yönlendirme yapılabilir.
    die("Hata: Kullanıcı bulunamadı.");
}

// İstatistikleri Hesapla
$won_projects_count = $db->query("SELECT COUNT(*) FROM projects WHERE winner_id = {$profile_user['id']}")->fetchColumn();
$submission_count = $db->query("SELECT COUNT(*) FROM submissions WHERE user_id = {$profile_user['id']}")->fetchColumn();

// Kullanıcının yeteneklerini çek
$skills_stmt = $db->prepare(
    "SELECT s.name FROM skills s JOIN user_skills us ON s.id = us.skill_id WHERE us.user_id = ?"
);
$skills_stmt->execute([$profile_user['id']]);
$skills = $skills_stmt->fetchAll(PDO::FETCH_COLUMN);

// Portfolyo (kullanıcının tüm sunumları)
$portfolio_items = $db->prepare("SELECT * FROM submissions WHERE user_id = ? ORDER BY id DESC LIMIT 20"); // Performans için limit
$portfolio_items->execute([$profile_user['id']]);
$portfolio = $portfolio_items->fetchAll();

// Kullanıcının başlattığı projeler
$user_projects_stmt = $db->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY id DESC LIMIT 10");
$user_projects_stmt->execute([$profile_user['id']]);
$user_projects = $user_projects_stmt->fetchAll();

include 'includes/header.php';
?>

<?php include 'includes/navbar.php'; ?>

<div class="profile-page-container">
    <header class="profile-hero" style="background-image: url('<?= !empty($profile_user['cover_photo']) ? htmlspecialchars($profile_user['cover_photo']) : 'assets/default_cover.jpg' ?>');">
        <div class="profile-header-content">
            <div class="profile-avatar-large">
                <img src="<?= get_user_avatar($profile_user['id'], $profile_user['username']) ?>" alt="<?= htmlspecialchars($profile_user['username']) ?>">
            </div>
            <div class="profile-info-main">
                <h1><?= htmlspecialchars($profile_user['username']) ?></h1>
                <p class="profile-title"><?= htmlspecialchars($profile_user['title'] ?? 'Tasarımcı') ?></p>
                <div class="profile-socials">
                    <?php if(!empty($profile_user['website_url'])): ?><a href="<?= htmlspecialchars($profile_user['website_url']) ?>" target="_blank" title="Web Sitesi"><i class="fas fa-globe"></i></a><?php endif; ?>
                    <?php if(!empty($profile_user['dribbble_url'])): ?><a href="<?= htmlspecialchars($profile_user['dribbble_url']) ?>" target="_blank" title="Dribbble"><i class="fab fa-dribbble"></i></a><?php endif; ?>
                    <?php if(!empty($profile_user['twitter_url'])): ?><a href="<?= htmlspecialchars($profile_user['twitter_url']) ?>" target="_blank" title="Twitter"><i class="fab fa-twitter"></i></a><?php endif; ?>
                </div>
            </div>
            <div class="profile-actions">
                <?php if(is_logged_in() && $_SESSION['user_id'] == $profile_user['id']): ?>
                    <a href="edit-profile.php" class="btn btn-secondary">Profili Düzenle</a>
                <?php else: ?>
                    <a href="#" class="btn btn-primary">Mesaj Gönder</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container page-container">
        <div class="profile-secondary-bar">
            <div class="profile-stats">
                <div class="stat-item"><span>Kazanılan Proje</span><strong><?= $won_projects_count ?></strong></div>
                <div class="stat-item"><span>Toplam Sunum</span><strong><?= $submission_count ?></strong></div>
                <div class="stat-item"><span>Kayıt Tarihi</span><strong><?= date('M Y', strtotime($profile_user['created_at'])) ?></strong></div>
            </div>
            <div class="profile-skills">
                <h3>Yetenekler</h3>
                <div class="skills-list">
                    <?php if(empty($skills)): ?>
                        <span>Henüz yetenek eklenmemiş.</span>
                    <?php else: ?>
                        <?php foreach($skills as $skill): ?>
                            <span class="skill-tag"><?= htmlspecialchars($skill) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-btn active" data-tab="portfolio">Portfolyo (<?= count($portfolio) ?>)</button>
                <button class="tab-btn" data-tab="projects">Başlatılan Projeler (<?= count($user_projects) ?>)</button>
            </div>
            
            <div class="tab-content active" id="tab-portfolio">
                <?php if(empty($portfolio)): ?>
                    <p>Bu kullanıcının henüz sergilenecek bir çalışması yok.</p>
                <?php else: ?>
                    <div class="submission-grid compact-grid">
                        <?php foreach($portfolio as $item): ?>
                            <div class="submission-card-v2">
                                <a href="project-detail.php?id=<?= $item['project_id'] ?>" class="submission-image-link">
                                    <img src="<?= htmlspecialchars($item['file_path']) ?>" alt="Portfolyo çalışması">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="tab-content" id="tab-projects">
                <?php if(empty($user_projects)): ?>
                    <p>Bu kullanıcı henüz bir proje başlatmamış.</p>
                <?php else: ?>
                    <div class="project-list-container">
                        <?php foreach($user_projects as $project): ?>
                            <div class="rich-project-card">
                                <div class="rpc-main">
                                    <h3 class="rpc-title"><a href="project-detail.php?id=<?= $project['id'] ?>"><?= htmlspecialchars($project['title']) ?></a></h3>
                                    <p class="rpc-description"><?= htmlspecialchars(substr($project['description'], 0, 150)) ?>...</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>