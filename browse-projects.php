<?php
require 'includes/init.php';

// --- FİLTRELEME VE SIRALAMA PARAMETRELERİ ---
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? 'aktif';
$sort_order = $_GET['sort'] ?? 'newest';

// --- ULTRA EXTREM: HAFTANIN PROJESİNİ ÇEKME ---
$featured_project = null;
// Haftanın projesi sadece filtresiz ve "aktif" sekmesindeyken görünsün
if ($status_filter == 'aktif' && empty($category_filter)) {
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
}

// --- Standart Projeleri Çekme (TÜM FİLTRELERİ İÇEREN DÜZELTİLMİŞ SORGULAMA) ---
$params = [];
$sql = "SELECT p.*, 
               u.username, u.id as user_id,
               (SELECT COUNT(*) FROM submissions s WHERE s.project_id = p.id) AS submission_count,
               (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) AS comment_count,
               s_winner.file_path as winner_image_path,
               u_winner.username as winner_username
        FROM projects p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN submissions s_winner ON p.winning_submission_id = s_winner.id
        LEFT JOIN users u_winner ON p.winner_id = u_winner.id";

$where_clauses = [];

// Haftanın projesi varsa, standart listeden çıkar
if ($featured_project) {
    $where_clauses[] = "p.id != :featured_id";
    $params[':featured_id'] = $featured_project['id'];
}

// Kategori ve Durum Filtrelerini uygula
if (!empty($category_filter)) {
    $where_clauses[] = "p.category = :category";
    $params[':category'] = $category_filter;
}
if ($status_filter === 'tamamlandi') {
    $where_clauses[] = "p.status IN ('tamamlandı', 'kazanan_seçildi')";
} elseif (!empty($status_filter)) {
    $where_clauses[] = "p.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Sıralama Mantığı
switch ($sort_order) {
    case 'ending_soon':
        $sql .= " ORDER BY p.deadline ASC";
        break;
    case 'prize_desc':
        $sql .= " ORDER BY CAST(REPLACE(SUBSTRING_INDEX(p.budget, ' ', 1), '.', '') AS UNSIGNED) DESC";
        break;
    default: // 'newest'
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

$categories = $db->query("SELECT DISTINCT category FROM projects WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="page-background"></div> <header class="page-header" style="background: transparent; border: none;">
    <div class="container">
        <h1>Projeleri Keşfet</h1>
        <p>Yeteneklerinize uygun yarışmayı bulun ve yaratıcılığınızı sergileyin.</p>
    </div>
</header>

<main class="container page-container">
    <form method="GET" class="filter-bar">
        <div class="form-group"><label>Kategori</label><select name="category" onchange="this.form.submit()"><option value="">Tüm Kategoriler</option><?php foreach ($categories as $cat): ?><option value="<?= htmlspecialchars($cat) ?>" <?= ($category_filter == $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Durum</label><select name="status" onchange="this.form.submit()"><option value="aktif" <?= ($status_filter == 'aktif') ? 'selected' : '' ?>>Aktif</option><option value="tamamlandi" <?= ($status_filter == 'tamamlandi') ? 'selected' : '' ?>>Tamamlanmış</option></select></div>
        <div class="form-group"><label>Sırala</label><select name="sort" onchange="this.form.submit()"><option value="newest" <?= ($sort_order == 'newest') ? 'selected' : '' ?>>En Yeni</option><option value="prize_desc" <?= ($sort_order == 'prize_desc') ? 'selected' : '' ?>>Ödüle Göre</option></select></div>
    </form>
    
    <?php if ($featured_project): ?>
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


    <div class="project-list-container-v4">
        <?php if (count($projects) > 0): ?>
            <?php foreach ($projects as $project): ?>
                
                <?php if ($project['status'] != 'aktif' && !empty($project['winner_image_path'])): ?>
                    <a href="project-detail.php?id=<?= $project['id'] ?>" class="project-card-v4-completed" style="background-image: url('<?= htmlspecialchars($project['winner_image_path']) ?>');">
                        <div class="completed-overlay">
                            <span class="completed-badge"><i class="fas fa-check-circle"></i> Tamamlandı</span>
                            <div class="completed-content">
                                <h3><?= htmlspecialchars($project['title']) ?></h3>
                                <div class="winner-info">
                                    Kazanan: <strong><?= htmlspecialchars($project['winner_username']) ?></strong>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php else: ?>
                    <?php
                        $days_remaining_text = "Süre Doldu"; $days_remaining_class = "expired";
                        if ($project['status'] == 'aktif' && !empty($project['deadline'])) {
                            try {
                                $deadline = new DateTime($project['deadline'] . ' 23:59:59'); $now = new DateTime();
                                if ($now < $deadline) {
                                    $interval = $now->diff($deadline); $days_left = $interval->days;
                                    $days_remaining_text = $days_left > 0 ? "$days_left gün kaldı" : "Bugün Bitiyor";
                                    $days_remaining_class = $days_left <= 3 ? "urgent" : "normal";
                                }
                            } catch(Exception $e) {}
                        }
                    ?>
                    <a href="project-detail.php?id=<?= $project['id'] ?>" class="project-card-v4-active">
                        <div class="active-header">
                            <div class="owner-info">
                                <img src="<?= get_user_avatar($project['user_id'], $project['username']) ?>" alt="<?= htmlspecialchars($project['username']) ?>">
                                <span><?= htmlspecialchars($project['username']) ?></span>
                            </div>
                            <span class="countdown <?= $days_remaining_class ?>"><?= $days_remaining_text ?></span>
                        </div>
                        <div class="active-body">
                            <span class="category-tag"><?= htmlspecialchars($project['category']) ?></span>
                            <h3><?= htmlspecialchars($project['title']) ?></h3>
                        </div>
                        <div class="active-footer">
                            <div class="prize-info">
                                <i class="fas fa-trophy"></i>
                                <span><?= htmlspecialchars($project['budget']) ?></span>
                            </div>
                            <div class="stats-info">
                                <span><i class="fas fa-paint-brush"></i> <?= $project['submission_count'] ?></span>
                                <span><i class="fas fa-comments"></i> <?= $project['comment_count'] ?></span>
                            </div>
                        </div>
                    </a>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-projects-found"><h3>Bu Kriterlere Uygun Proje Bulunamadı</h3><p>Lütfen filtrelerinizi değiştirerek tekrar deneyin.</p></div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>