<?php
require 'includes/init.php';

// --- 1. FİLTRELEME VE SIRALAMA PARAMETRELERİNİ ALMA ---
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? 'aktif'; // Varsayılan olarak aktif projeleri göster
$sort_order = $_GET['sort'] ?? 'newest';

// --- 2. VERİTABANI SORGUSUNU DİNAMİK OLARAK OLUŞTURMA ---
$params = [];
$sql = "SELECT p.*, 
               u.username,
               (SELECT COUNT(*) FROM submissions s WHERE s.project_id = p.id) AS submission_count,
               (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) AS comment_count
        FROM projects p
        JOIN users u ON p.user_id = u.id";

$where_clauses = [];

if (!empty($category_filter)) {
    $where_clauses[] = "p.category = :category";
    $params[':category'] = $category_filter;
}

// --- DÜZELTME BURADA ---
// 'tamamlandı' filtresi seçildiğinde, hem 'tamamlandı' hem de 'kazanan_seçildi' durumlarını dahil et
if ($status_filter === 'tamamlandi') {
    $where_clauses[] = "p.status IN ('tamamlandı', 'kazanan_seçildi')";
} elseif (!empty($status_filter)) {
    $where_clauses[] = "p.status = :status";
    $params[':status'] = $status_filter;
}
// --- DÜZELTME SONU ---

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Sıralama mantığı
switch ($sort_order) {
    case 'ending_soon':
        $sql .= " ORDER BY p.deadline ASC";
        break;
    case 'prize_desc':
        $sql .= " ORDER BY p.budget DESC";
        break;
    default: // 'newest'
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

// --- 3. GİRİŞ YAPAN KULLANICININ TAKİP ETTİĞİ PROJELERİ ÇEKME ---
$followed_ids = [];
if (is_logged_in()) {
    $follow_stmt = $db->prepare("SELECT project_id FROM followed_projects WHERE user_id = ?");
    $follow_stmt->execute([$_SESSION['user_id']]);
    $followed_ids = $follow_stmt->fetchAll(PDO::FETCH_COLUMN);
}

// --- 4. FİLTRE İÇİN KATEGORİLERİ ÇEKME ---
$categories = $db->query("SELECT DISTINCT category FROM projects WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

include 'includes/header.php';
?>

<?php include 'includes/navbar.php'; ?>

<header class="page-header">
    <div class="container">
        <h1>Projeleri Keşfet</h1>
        <p>Yüzlerce proje arasından yeteneklerinize uygun olanı bulun ve hemen teklif verin.</p>
    </div>
</header>

<main class="container page-container">

    <form method="GET" class="filter-bar">
        <div class="form-group">
            <label>Kategori</label>
            <select name="category" onchange="this.form.submit()">
                <option value="">Tüm Kategoriler</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= ($category_filter == $cat) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Durum</label>
            <select name="status" onchange="this.form.submit()">
                <option value="aktif" <?= ($status_filter == 'aktif') ? 'selected' : '' ?>>Aktif</option>
                <option value="tamamlandi" <?= ($status_filter == 'tamamlandi') ? 'selected' : '' ?>>Tamamlanmış</option>
            </select>
        </div>
        <div class="form-group">
            <label>Sırala</label>
            <select name="sort" onchange="this.form.submit()">
                <option value="newest" <?= ($sort_order == 'newest') ? 'selected' : '' ?>>En Yeni</option>
                <option value="ending_soon" <?= ($sort_order == 'ending_soon') ? 'selected' : '' ?>>Bitiş Tarihine Göre</option>
                <option value="prize_desc" <?= ($sort_order == 'prize_desc') ? 'selected' : '' ?>>Ödüle Göre</option>
            </select>
        </div>
    </form>
    
    <div class="project-list-container">
        <?php if (count($projects) > 0): ?>
            <?php foreach ($projects as $project): ?>
                <div class="rich-project-card">
                    <div class="rpc-thumbnail"><i class="fas fa-layer-group"></i></div>
                    <div class="rpc-main">
                        <div class="rpc-tags">
                            <?php
                                if ($project['deadline'] && $project['status'] == 'aktif') {
                                    $deadline = new DateTime($project['deadline']);
                                    $now = new DateTime();
                                    $interval = $now->diff($deadline);
                                    if ($now < $deadline && $interval->days <= 3) {
                                        echo '<span class="tag urgent">Bitiyor!</span>';
                                    }
                                }
                            ?>
                        </div>
                        <h3 class="rpc-title"><a href="project-detail.php?id=<?= $project['id'] ?>"><?= htmlspecialchars($project['title']) ?></a></h3>
                        <p class="rpc-description"><?= htmlspecialchars(substr($project['description'], 0, 180)) ?>...</p>
                        <div class="rpc-stats">
                            <span class="stat"><strong><?= htmlspecialchars($project['budget']) ?></strong></span>
                            <span class="stat"><i class="fas fa-paint-brush"></i> <strong><?= $project['submission_count'] ?></strong> Sunum</span>
                            <span class="stat"><i class="fas fa-comments"></i> <strong><?= $project['comment_count'] ?></strong> Yorum</span>
                            <span class="stat"><i class="fas fa-user-tie"></i> <a href="profile.php?username=<?= htmlspecialchars($project['username']) ?>"><?= htmlspecialchars($project['username']) ?></a></span>
                        </div>
                    </div>
                    <div class="rpc-actions">
                        <a href="project-detail.php?id=<?= $project['id'] ?>" class="btn btn-primary">Projeyi Gör</a>
                        <?php if (is_logged_in()): 
                            $is_following = in_array($project['id'], $followed_ids);
                        ?>
                            <button class="btn follow-btn <?= $is_following ? 'following' : '' ?>" data-project-id="<?= $project['id'] ?>">
                                <?php if ($is_following): ?>
                                    <i class="fas fa-check"></i> Takip Ediliyor
                                <?php else: ?>
                                    <i class="fas fa-bookmark"></i> İzlemeye Al
                                <?php endif; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-projects-found">
                <h3>Bu Kriterlere Uygun Proje Bulunamadı</h3>
                <p>Lütfen filtrelerinizi değiştirerek tekrar deneyin.</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
<script src="assets/script.js"></script>