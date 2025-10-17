<?php
require 'includes/init.php';

// --- ANA SAYFA VERİLERİNİ ÇEKME ---

// 1. Canlı İstatistikler
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_projects = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
// Not: Gerçek bir ödül sütunu olmadığından, bütçe metinlerinden birini sembolik olarak alıyoruz.
$total_prize_money = "150.000+ TL"; 

// 2. Vitrin: En İyi Tasarımcılar (Örnek olarak son 4 üyeyi alıyoruz)
$top_designers = $db->query("SELECT username FROM users ORDER BY id DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);

// 3. Dinamik Proje Akışı
$ongoing_projects = $db->query("SELECT p.*, u.username FROM projects p JOIN users u ON p.user_id = u.id WHERE p.status = 'aktif' ORDER BY p.created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
$completed_projects = $db->query("SELECT p.*, u.username FROM projects p JOIN users u ON p.user_id = u.id WHERE p.status IN ('kazanan_seçildi', 'tamamlandı') ORDER BY p.created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<?php include 'includes/navbar.php'; ?>

<header class="hero-xtreme">
    <div class="container">
        <h1>Hayalindeki Tasarıma Ulaşmanın En Kolay Yolu</h1>
        <p>Projenizi yayınlayın, dünyanın dört bir yanından yüzlerce yetenekli tasarımcıdan özgün tasarımlar alın.</p>
        
        <div class="hero-buttons">
            <?php if (is_logged_in()): ?>
                <a href="create-project.php" class="btn btn-primary btn-lg">Projeni Hemen Başlat</a>
            <?php else: ?>
                <button id="openRegisterModalBtnHero" class="btn btn-primary btn-lg">Ücretsiz Proje Başlat</button>
            <?php endif; ?>
            <a href="browse-projects.php" class="btn btn-secondary btn-lg">Projeleri Keşfet</a>
        </div>
    </div>
</header>

<section class="live-stats">
    <div class="container">
        <div class="live-stat-item">
            <i class="fas fa-users"></i>
            <div class="stat-value" data-target="<?= $total_users ?>">0</div>
            <div class="stat-label">Toplam Tasarımcı</div>
        </div>
        <div class="live-stat-item">
            <i class="fas fa-layer-group"></i>
            <div class="stat-value" data-target="<?= $total_projects ?>">0</div>
            <div class="stat-label">Toplam Proje</div>
        </div>
        <div class="live-stat-item">
            <i class="fas fa-trophy"></i>
            <div class="stat-value-text"><?= $total_prize_money ?></div>
            <div class="stat-label">Dağıtılan Ödül</div>
        </div>
    </div>
</section>


<main class="container page-container">

    <section class="category-section">
        <div class="section-heading">
            <h2>Her İhtiyaca Yönelik Tasarım</h2>
            <p>İster bir logo, ister bir web sitesi arıyor olun, doğru kategorideki binlerce yetenekli tasarımcıya ulaşın.</p>
        </div>
        <div class="category-browser">
            <a href="#" class="category-card"><i class="fas fa-pencil-ruler"></i><h4>Logo & Markalaşma</h4></a>
            <a href="#" class="category-card"><i class="fas fa-desktop"></i><h4>Web & Mobil Arayüz</h4></a>
            <a href="#" class="category-card"><i class="fas fa-tshirt"></i><h4>T-Shirt & Ürün</h4></a>
            <a href="#" class="category-card"><i class="fas fa-paint-brush"></i><h4>İllüstrasyon & Sanat</h4></a>
            <a href="#" class="category-card"><i class="fas fa-box-open"></i><h4>Ambalaj & Etiket</h4></a>
            <a href="#" class="category-card"><i class="fas fa-book-open"></i><h4>Kitap & Dergi Kapağı</h4></a>
        </div>
    </section>

    <section class="active-projects-section">
        <div class="section-heading">
            <h2>Yeni Başlayan Projeler</h2>
        </div>
        <?php foreach($ongoing_projects as $project): ?>
            <?php endforeach; ?>
    </section>

    <section class="designers-section" style="background:var(--light-gray); padding: 4rem 0;">
        <div class="container">
            <div class="section-heading">
                <h2>Yetenek Havuzumuzla Tanışın</h2>
                <p>Platformumuzda fark yaratan, en yaratıcı tasarımcılarımızdan sadece birkaçı.</p>
            </div>
            <div class="designers-showcase">
                <?php foreach($top_designers as $designer): ?>
                <div class="designer-card">
                    <img src="https://i.pravatar.cc/150?u=<?= htmlspecialchars($designer['username']) ?>" alt="Tasarımcı">
                    <h4><?= htmlspecialchars($designer['username']) ?></h4>
                    <span>Logo & Web Tasarım Uzmanı</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="testimonials-section">
        <div class="section-heading">
            <h2>Müşterilerimiz Ne Diyor?</h2>
        </div>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <blockquote>"Sadece 3 gün içinde 100'den fazla logo tasarımı aldım. Seçim yapmak zordu ama sonuç harika oldu. Kesinlikle tekrar kullanacağım."</blockquote>
                <div class="testimonial-author">
                    <img src="https://i.pravatar.cc/150?u=ahmet" alt="Ahmet Y.">
                    <div><strong>Ahmet Y.</strong><br><span>Kahve Dükkanı Sahibi</span></div>
                </div>
            </div>
            </div>
    </section>
    
    <section class="final-cta-section">
    <div class="container">
        <div class="final-cta">
            <h2>Hayalindeki Tasarıma Sadece Bir Adım Uzaktasın</h2>
            <h2>Sen de Aramıza Katıl Tasarımından Para Kazan</h2>
            <div class="btn-group">
                <?php if (is_logged_in()): ?>
                    <a href="create-project.php" class="btn btn-primary btn-lg">Hemen Projeni Başlat</a>
                <?php else: ?>
                    <button id="openRegisterModalBtnCta" class="btn btn-primary btn-lg">Hemen Projeni Başlat</button>
                <?php endif; ?>
                
                <button id="openDesignerRegisterModalBtn" class="btn btn-secondary btn-lg">Tasarımcı Olarak Katıl</button>
            </div>
        </div>
    </div>
</section>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>