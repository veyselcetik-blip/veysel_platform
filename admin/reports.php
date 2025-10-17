<?php 
include 'includes/header.php'; 

// Çözülmemiş şikayetleri, ilgili sunum ve kullanıcı bilgileriyle birlikte çek
$reports_stmt = $db->query(
    "SELECT r.id, r.reason, r.created_at, s.id as submission_id, s.file_path, u.username as designer_name
     FROM reports r
     JOIN submissions s ON r.submission_id = s.id
     JOIN users u ON s.user_id = u.id
     WHERE r.status = 'beklemede'
     ORDER BY r.created_at ASC"
);
$reports = $reports_stmt->fetchAll();
?>

<div class="page-header">
    <h1>Bekleyen Şikayetler (<?= count($reports) ?>)</h1>
</div>

<div class="content-box">
    <div class="table-responsive">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>Sunum</th>
                    <th>Tasarımcı</th>
                    <th>Şikayet Sebebi</th>
                    <th>Tarih</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr><td colspan="5">Bekleyen şikayet bulunmuyor.</td></tr>
                <?php else: ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><a href="../<?= htmlspecialchars($report['file_path']) ?>" target="_blank"><img src="../<?= htmlspecialchars($report['file_path']) ?>" width="100"></a></td>
                            <td><?= htmlspecialchars($report['designer_name']) ?></td>
                            <td><?= nl2br(htmlspecialchars($report['reason'])) ?></td>
                            <td><?= date('d M Y', strtotime($report['created_at'])) ?></td>
                            <td>
                                <form action="actions/resolve_report.php" method="POST" style="display:inline-block;">
                                    <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                    <button type="submit" class="btn btn-success">Onayla/Kapat</button>
                                </form>
                                <form action="actions/delete_submission.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Bu sunumu kalıcı olarak silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="submission_id" value="<?= $report['submission_id'] ?>">
                                    <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Sunumu Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>