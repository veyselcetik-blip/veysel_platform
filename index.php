<?php
require 'includes/init.php';

// --- ANA SAYFA İÇİN YENİ VERİLERİ ÇEKME ---

// 1. İstatistikler için veriler
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_projects = $db->query("SELECT COUNT(*) FROM projects WHERE status IN ('kazanan_seçildi', 'tamamlandı')")->fetchColumn();
$total_prize_money = 150000; // Bu değeri daha sonra dinamik hale getirebiliriz.

// 2. Öne Çıkan Yarışmalar (En yüksek bütçeli 5 aktif proje)
$featured_projects = $db->query(
    "SELECT p.id, p.title, p.budget, p.category, 
    (SELECT s.file_path FROM submissions s WHERE s.project_id = p.id ORDER BY RAND() LIMIT 1) as random_submission_image
    FROM projects p
    WHERE p.status = 'aktif'
    ORDER BY p.id DESC
    LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);


// 3. Şampiyonlar Ligi (Son kazanan 6 tasarım)
$winning_designs = $db->query(
    "SELECT p.id as project_id, p.title, s.file_path, u.username
     FROM projects p
     JOIN submissions s ON p.winning_submission_id = s.id
     JOIN users u ON s.user_id = u.id
     WHERE p.winning_submission_id IS NOT NULL
     ORDER BY p.id DESC
     LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

// 4. Tasarımcı Radarı (En çok yarışma kazanan 4 tasarımcı - Bu sorgu daha sonra geliştirilebilir)
$spotlight_designers = $db->query(
    "SELECT u.id, u.username, u.title as user_title, COUNT(p.winner_id) as win_count
     FROM users u
     LEFT JOIN projects p ON u.id = p.winner_id
     GROUP BY u.id
     ORDER BY win_count DESC, u.id DESC
     LIMIT 4"
)->fetchAll(PDO::FETCH_ASSOC);

// 5. Popüler Kategoriler (İçinde en çok proje olan 6 kategori)
$popular_categories = $db->query(
    "SELECT category, COUNT(id) as project_count
     FROM projects
     WHERE category IS NOT NULL AND category != ''
     GROUP BY category
     ORDER BY project_count DESC
     LIMIT 6"
)->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<?php include 'includes/navbar.php'; ?>

<header class="hero-reloaded">
    <div class="video-background">
        <div class="video-overlay"></div>
    </div>
    <div class="container hero-content">
        <h1>Tasarım Yarışmalarıyla Fikrinizi Hayata Geçirin</h1>
        <p>İhtiyacınızı anlatın, yüzlerce profesyonel tasarımcıdan onlarca özgün tasarım alın, favorinizi seçin.</p>
        <div class="hero-search-form">
            <form action="browse-projects.php" method="GET">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Ne tür bir tasarıma ihtiyacın var? (örn: logo, web sitesi, broşür...)">
                <button type="submit" class="btn btn-primary">Ara</button>
            </form>
        </div>
    </div>
</header>

<section class="stats-counter-section">
    <div class="container">
        <div class="stats-counter-grid">
            <div class="stat-item">
                <i class="fas fa-users"></i>
                <div class="stat-value" data-target="<?= $total_users ?>">0</div>
                <div class="stat-label">Yetenekli Tasarımcı</div>
            </div>
            <div class="stat-item">
                <i class="fas fa-check-circle"></i>
                <div class="stat-value" data-target="<?= $total_projects ?>">0</div>
                <div class="stat-label">Başarılı Proje</div>
            </div>
            <div class="stat-item">
                <i class="fas fa-trophy"></i>
                <div class="stat-value-prize" data-target="<?= $total_prize_money ?>">0</div>
                <div class="stat-label">Dağıtılan Toplam Ödül</div>
            </div>
        </div>
    </div>
</section>

<section class="how-it-works-timeline">
    <div class="container">
        <div class="section-heading">
            <h2>Sadece 3 Adımda Hayalinizdeki Tasarıma Ulaşın</h2>
            <p>Sürecimiz hızlı, kolay ve garantilidir.</p>
        </div>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-icon"><i class="fas fa-lightbulb"></i></div>
                <div class="timeline-content">
                    <h3>1. Projeni Anlat</h3>
                    <p>Tasarım ihtiyacınızı, beklentilerinizi ve bütçenizi belirten kısa bir brif oluşturun. Süreç tamamen ücretsizdir.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon"><i class="fas fa-palette"></i></div>
                <div class="timeline-content">
                    <h3>2. Tasarımları İncele</h3>
                    <p>Dünyanın dört bir yanından gelen onlarca özgün tasarımı karşılaştırın, tasarımcılarla iletişim kurun ve revizeler isteyin.</p>
                </div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon"><i class="fas fa-crown"></i></div>
                <div class="timeline-content">
                    <h3>3. Kazananı Seç</h3>
                    <p>İçinize en çok sinen tasarımı kazanan olarak seçin. Telif hakları tamamen size ait olsun ve tüm profesyonel dosyaları indirin.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($featured_projects)): ?>
<section class="featured-projects-section">
    <div class="container">
        <div class="section-heading">
            <h2>Öne Çıkan Yarışmalar</h2>
            <p>Hemen katılın ve yeteneklerinizi sergileyin.</p>
        </div>
        <div class="project-carousel">
            <?php foreach($featured_projects as $project): ?>
                <a href="project-detail.php?id=<?= $project['id'] ?>" class="project-card-mini">
                    <div class="card-image" style="background-image: url('<?= htmlspecialchars($project['random_submission_image'] ?? 'https://via.placeholder.com/400x300.png?text=Tasarım+Bekleniyor') ?>');"></div>
                    <div class="card-content">
                        <span class="card-category"><?= htmlspecialchars($project['category']) ?></span>
                        <h4><?= htmlspecialchars($project['title']) ?></h4>
                        <div class="card-prize">
                            <i class="fas fa-trophy"></i>
                            <span><?= htmlspecialchars($project['budget']) ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>


<?php if (!empty($winning_designs)): ?>
<section class="hall-of-fame-section">
    <div class="container">
        <div class="section-heading">
            <h2>Şampiyonlar Ligi</h2>
            <p>Platformumuzda hayat bulan başarılı ve ilham verici tasarımlar.</p>
        </div>
        <div class="hall-of-fame-grid">
            <?php foreach($winning_designs as $design): ?>
                <a href="project-detail.php?id=<?= $design['project_id'] ?>" class="winner-card">
                    <img src="<?= htmlspecialchars($design['file_path']) ?>" alt="<?= htmlspecialchars($design['title']) ?>">
                    <div class="winner-overlay">
                        <h4><?= htmlspecialchars($design['title']) ?></h4>
                        <span>Tasarımcı: <?= htmlspecialchars($design['username']) ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="split-section">
    <div class="container">
        <div class="split-grid">
            <div class="split-left">
                <h3>Popüler Kategoriler</h3>
                <div class="popular-categories-list">
                    <?php foreach($popular_categories as $cat): ?>
                        <a href="browse-projects.php?category=<?= urlencode($cat['category']) ?>">
                            <span class="cat-name"><?= htmlspecialchars($cat['category']) ?></span>
                            <span class="cat-count"><?= $cat['project_count'] ?> Proje</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="split-right">
                <div class="video-promo-card">
                    <a href="https://www.youtube.com/watch?v=dQw4w9WgXcQ" class="play-button" target="_blank">
                        <i class="fas fa-play"></i>
                    </a>
                    <h3>Platformumuz Nasıl Çalışır?</h3>
                    <p>Sadece 90 saniyede tüm süreci öğrenin ve ilk projenizi hemen başlatın!</p>
                </div>
            </div>
        </div>
    </div>
</section>


<section class="designer-spotlight-section">
    <div class="container">
        <div class="section-heading">
            <h2>Tasarımcı Radarı</h2>
            <p>Platformumuzun en başarılı ve yaratıcı profesyonelleriyle tanışın.</p>
        </div>
        <div class="spotlight-grid">
            <?php foreach($spotlight_designers as $designer): ?>
                <a href="profile.php?username=<?= htmlspecialchars($designer['username']) ?>" class="designer-card">
                    <img src="<?= get_user_avatar($designer['id'], $designer['username']) ?>" alt="<?= htmlspecialchars($designer['username']) ?>">
                    <h4><?= htmlspecialchars($designer['username']) ?></h4>
                    <span><?= htmlspecialchars($designer['user_title'] ?? 'Grafik Tasarımcı') ?></span>
                    <div class="designer-stats">
                        <span><i class="fas fa-trophy"></i> <?= $designer['win_count'] ?> Galibiyet</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>


<section class="final-features-section">
    <div class="container">
        <div class="final-grid">
            <div class="why-us-panel">
                <h3>Neden Veysel Platform?</h3>
                <div class="feature-item">
                    <i class="fas fa-layer-group"></i>
                    <div>
                        <h4>Onlarca Farklı Seçenek</h4>
                        <p>Tek bir tasarımcıya bağlı kalmayın. Her proje için onlarca farklı bakış açısı ve konsept arasından seçim yapın.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <h4>%100 Para İade Garantisi</h4>
                        <p>Eğer gelen tasarımlardan memnun kalmazsanız, paranızın tamamını sorgusuz sualsiz iade ediyoruz.</p>
                    </div>
                </div>
                <div class="feature-item">
                    <i class="fas fa-handshake"></i>
                    <div>
                        <h4>Tüm Telif Hakları Sizin</h4>
                        <p>Kazanan tasarımı seçtiğiniz anda, tüm yasal telif hakları ve profesyonel kaynak dosyalar size ait olur.</p>
                    </div>
                </div>
            </div>
            <div class="live-feed-panel">
                <h3><i class="fas fa-wave-square"></i> Canlı Aktivite</h3>
                <ul id="live-feed-list">
                    </ul>
            </div>
        </div>
    </div>
</section>

<?php // FİNAL CTA (EYLEM ÇAĞRISI) AYNI KALABİLİR ?>
<section class="final-cta-section" style="padding-bottom: 4rem;">
    <div class="container">
        <div class="final-cta">
            <h2>Hayalindeki Tasarıma Sadece Bir Adım Uzaktasın</h2>
            <p style="font-size: 1.2rem; max-width: 700px; margin: 1rem auto 0 auto; opacity: 0.9;">Hemen projenizi başlatın veya yeteneklerinizi sergileyerek para kazanın. Aramıza katılın!</p>
            <div class="btn-group">
                <?php if (is_logged_in()): ?>
                    <a href="create-project.php" class="btn btn-primary btn-lg">Hemen Projeni Başlat</a>
                <?php else: ?>
                    <button class="open-register-modal btn btn-primary btn-lg">Hemen Projeni Başlat</button>
                <?php endif; ?>
                
                <button class="open-register-modal btn btn-secondary btn-lg">Tasarımcı Olarak Katıl</button>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>