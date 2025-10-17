<?php
require 'includes/init.php';
require_login();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 1. Kullanıcının AKTİF projelerini çek (Durumu 'aktif' olanlar)
$active_projects_stmt = $db->prepare(
    "SELECT p.*, COUNT(s.id) AS submission_count 
     FROM projects p 
     LEFT JOIN submissions s ON p.id = s.project_id 
     WHERE p.user_id = ? AND p.status = 'aktif'
     GROUP BY p.id
     ORDER BY p.created_at DESC"
);
$active_projects_stmt->execute([$user_id]);
$active_projects = $active_projects_stmt->fetchAll();

// 2. Kullanıcının TAMAMLANMIŞ projelerini çek (Durumu 'kazanan_seçildi' veya 'tamamlandı' olanlar)
$completed_projects_stmt = $db->prepare(
    "SELECT p.*, COUNT(s.id) AS submission_count 
     FROM projects p 
     LEFT JOIN submissions s ON p.id = s.project_id 
     WHERE p.user_id = ? AND p.status IN ('kazanan_seçildi', 'tamamlandı')
     GROUP BY p.id
     ORDER BY p.created_at DESC"
);
$completed_projects_stmt->execute([$user_id]);
$completed_projects = $completed_projects_stmt->fetchAll();

include 'includes/header.php';
?>

<?php include 'includes/navbar.php'; ?>

<main class="dashboard-container">
    <div class="container">
        <h1 class="dashboard-welcome">Hoş Geldin, <?= htmlspecialchars($username) ?>!</h1>
        
        <section class="dashboard-section">
            <h2>Aktif Projelerim</h2>
            <div class="dashboard-project-list">
                <?php if (count($active_projects) > 0): ?>
                    <?php foreach ($active_projects as $project): ?>
                        <?php
                            // Kalan Süreyi Hesaplama
                            $days_remaining_text = "Süre Doldu";
                            if ($project['deadline']) {
                                $deadline = new DateTime($project['deadline']);
                                $now = new DateTime();
                                if ($now <= $deadline) {
                                    $interval = $now->diff($deadline);
                                    if ($interval->days == 0) {
                                        $days_remaining_text = "Bugün Son Gün!";
                                    } else {
                                        $days_remaining_text = $interval->format('%a gün %h saat kaldı');
                                    }
                                }
                            }
                        ?>
                        <div class="d-project-card">
                            <div class="d-card-main">
                                <h3 class="d-card-title"><a href="project-detail.php?id=<?= $project['id'] ?>"><?= htmlspecialchars($project['title']) ?></a></h3>
                                <p class="d-card-description"><?= htmlspecialchars(substr($project['description'], 0, 150)) ?>...</p>
                                <div class="d-card-stats">
                                    <span><i class="fas fa-trophy"></i> Ödül: <strong><?= htmlspecialchars($project['budget']) ?></strong></span>
                                    <span><i class="fas fa-paint-brush"></i> <strong><?= $project['submission_count'] ?></strong> sunum</span>
                                    <span><i class="fas fa-clock"></i> <?= $days_remaining_text ?></span>
                                </div>
                                <div class="d-card-meta">
                                    <span><i class="fas fa-tags"></i> <?= htmlspecialchars($project['category']) ?></span>
                                    <span><i class="fas fa-calendar-alt"></i> Başlangıç: <?= date('d.m.Y', strtotime($project['created_at'])) ?></span>
                                </div>
                            </div>
                            <div class="d-card-actions">
                                <a href="project-detail.php?id=<?= $project['id'] ?>" class="btn-view">Görüntüle</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Henüz aktif bir projeniz bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="dashboard-section">
            <h2>Tamamlanmış Projelerim</h2>
            <div class="dashboard-project-list">
                 <?php if (count($completed_projects) > 0): ?>
                    <?php foreach ($completed_projects as $project): ?>
                         <div class="d-project-card completed">
                            <div class="d-card-main">
                                <h3 class="d-card-title"><a href="project-detail.php?id=<?= $project['id'] ?>"><?= htmlspecialchars($project['title']) ?></a></h3>
                                <div class="d-card-stats">
                                    <span><i class="fas fa-trophy"></i> Ödül: <strong><?= htmlspecialchars($project['budget']) ?></strong></span>
                                    <span><i class="fas fa-paint-brush"></i> <strong><?= $project['submission_count'] ?></strong> sunum</span>
                                    <span><i class="fas fa-check-circle"></i> Tamamlandı</span>
                                </div>
                            </div>
                            <div class="d-card-actions">
                                <a href="project-detail.php?id=<?= $project['id'] ?>" class="btn-view">Görüntüle</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Henüz tamamlanmış bir projeniz bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php include 'includes/footer.php'; ?>