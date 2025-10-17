<?php 
include 'includes/header.php'; 

// --- WIDGET VERİLERİ ---
$pending_reports = $db->query("SELECT COUNT(*) FROM reports WHERE status = 'beklemede'")->fetchColumn();
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_projects = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();

// --- GRAFİK VERİLERİ (Son 7 Günlük Kullanıcı Kayıtları) ---
$chart_data_stmt = $db->query(
    "SELECT DATE(created_at) as registration_date, COUNT(*) as user_count 
     FROM users 
     WHERE created_at >= CURDATE() - INTERVAL 7 DAY 
     GROUP BY DATE(created_at) 
     ORDER BY registration_date ASC"
);
$chart_raw_data = $chart_data_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$chart_labels = [];
$chart_values = [];
// Son 7 günü döngüye alarak her gün için veri oluştur (veri olmasa bile 0 yaz)
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($date));
    $chart_values[] = $chart_raw_data[$date] ?? 0;
}

// --- SON AKTİVİTELER ---
$recent_users = $db->query("SELECT username, created_at FROM users ORDER BY id DESC LIMIT 5")->fetchAll();
$recent_projects = $db->query("SELECT title, created_at FROM projects ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<div class="page-header">
    <h1>Panel Anasayfa</h1>
</div>

<div class="widget-grid">
    <div class="widget">
        <h3>Bekleyen Şikayetler</h3>
        <div class="count"><?= $pending_reports ?></div>
        <a href="reports.php">Şikayetleri Yönet &rarr;</a>
    </div>
    <div class="widget">
        <h3>Toplam Kullanıcı</h3>
        <div class="count"><?= $total_users ?></div>
        <a href="users.php">Kullanıcıları Yönet &rarr;</a>
    </div>
    <div class="widget">
        <h3>Toplam Proje</h3>
        <div class="count"><?= $total_projects ?></div>
        <a href="projects.php">Projeleri Yönet &rarr;</a>
    </div>
</div>

<div class="dashboard-grid">
    <div class="content-box">
        <h3>Son 7 Günlük Kullanıcı Kayıtları</h3>
        <canvas id="userChart"></canvas>
    </div>

    <div class="content-box">
        <h3>Son Aktiviteler</h3>
        <div class="activity-feed">
            <h4>Yeni Kullanıcılar</h4>
            <ul>
                <?php foreach($recent_users as $user): ?>
                    <li><?= htmlspecialchars($user['username']) ?> - <small><?= date('d M Y', strtotime($user['created_at'])) ?></small></li>
                <?php endforeach; ?>
            </ul>
            <h4 style="margin-top:1.5rem;">Yeni Projeler</h4>
            <ul>
                <?php foreach($recent_projects as $project): ?>
                    <li><?= htmlspecialchars($project['title']) ?> - <small><?= date('d M Y', strtotime($project['created_at'])) ?></small></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>


<script>
    const userChartLabels = <?= json_encode($chart_labels) ?>;
    const userChartData = <?= json_encode($chart_values) ?>;
</script>

<?php include 'includes/footer.php'; ?>