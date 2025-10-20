<?php
require 'includes/init.php';

// --- FİLTRELEME VE SIRALAMA ---
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? 'aktif';
$sort_order = $_GET['sort'] ?? 'newest';

// --- ULTRA EXTREM: HAFTANIN PROJESİNİ ÇEKME ---
// Son 10 günde açılmış, en yüksek ödüllü ve en çok sunum alan aktif projeyi bul
$featured_project_stmt = $db->query(
    "SELECT p.*, u.username, u.id as user_id,
           (SELECT COUNT(*) FROM submissions s WHERE s.project_id = p.id) AS submission_count
     FROM projects p
     JOIN users u ON p.user_id = u.id
     WHERE p.status = 'aktif' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 10 DAY)
     ORDER BY CAST(REPLACE(SUBSTRING_INDEX(p.budget, ' ', 1), '.', '') AS UNSIGNED) DESC, submission_count DESC
     LIMIT 1"
);
$featured_project = $featured_project_stmt->fetch(PDO::FETCH_ASSOC);

// --- Standart Projeleri Çekme ---
$params = [];
$sql = "SELECT p.*, u.username, u.id as user_id,
               (SELECT COUNT(*) FROM submissions s WHERE s.project_id = p.id) AS submission_count,
               (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) AS comment_count
        FROM projects p JOIN users u ON p.user_id = u.id";

$where_clauses = [];
if ($featured_project) {
    // Haftanın projesini standart listeden çıkar
    $where_clauses[] = "p.id != :featured_id";
    $params[':featured_id'] = $featured_project['id'];
}
// ... (Mevcut filtreleme kodlarınız buraya gelecek)
if (!empty($category_filter)) { $where_clauses[] = "p.category = :category"; $params[':category'] = $category_filter; }
if ($status_filter === 'tamamlandi') { $where_clauses[] = "p.status IN ('tamamlandı', 'kazanan_seçildi')"; }
elseif (!empty($status_filter)) { $where_clauses[] = "p.status = :status"; $params[':status'] = $status_filter; }

if (!empty($where_clauses)) { $sql .= " WHERE " . implode(" AND ", $where_clauses); }
// ... (Sıralama kodlarınız buraya gelecek)
switch ($sort_order) { /* ... */ }
$stmt = $db->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();
$categories = $db->query("SELECT DISTINCT category FROM projects WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
include 'includes/navbar.php';
?>

<header class="page-header">
    <div class="container">
        <h1>Projeleri Keşfet</h1>
        <p>Yeteneklerinize uygun yarışmayı bulun ve yaratıcılığınızı sergileyin.</p>
    </div>
</header>

<main class="container page-container">
    <form method="GET" class="filter-bar">
        <?php /* Filtreleme formunuz burada aynı kalabilir */ ?>
    </form>
    
    <?php if ($featured_project && $status_filter == 'aktif'): ?>
    <section class="featured-project-section">
        <div class="featured-project-card">
            <div class="featured-tag"><i class="fas fa-star"></i> HAFTANIN PROJESİ</div>
            <div class="featured-body">
                <div class="featured-info">
                    <h2><a href="project-detail.php?id=<?= $featured_project['id'] ?>"><?= htmlspecialchars($featured_project['title']) ?></a></h2>
                    <p>Bu proje, yüksek ödülü ve popülerliği ile bu hafta öne çıkıyor. Hemen katılın!</p>
                    <div class="featured-owner">
                        <img src="<?= get_user_avatar($featured_project['user_id'], $featured_project['username']) ?>" alt="">
                        <span><?= htmlspecialchars($featured_project['username']) ?> tarafından başlatıldı.</span>
                    </div>
                </div>
                <div class="featured-stats">
                    <div class="stat-item prize">
                        <span>Ödül</span>
                        <strong><?= htmlspecialchars($featured_project['budget']) ?></strong>
                    </div>
                    <div class="stat-item">
                        <span>Sunum</span>
                        <strong><?= $featured_project['submission_count'] ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <div class="project-list-container-v3">
        <?php if (count($projects) > 0): ?>
            <?php foreach ($projects as $project): ?>
                <?php
                    // Aktif veya Süresi Dolmuş durumunu belirle
                    $card_status_class = 'status-active';
                    if ($project['status'] != 'aktif') {
                        $card_status_class = 'status-expired';
                    }
                ?>
                <div class="project-card-v3 <?= $card_status_class ?>">
                    <?php /* Önceki adımdaki kart yapısı burada */ ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-projects-found"> /* ... */ </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>