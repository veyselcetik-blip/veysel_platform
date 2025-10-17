<?php 
include 'includes/header.php'; 

// Tüm sunumları, ilişkili proje ve kullanıcı bilgileriyle çek
$submissions = $db->query(
    "SELECT s.id, s.file_path, s.created_at, p.title as project_title, u.username as designer_name
     FROM submissions s
     JOIN projects p ON s.project_id = p.id
     JOIN users u ON s.user_id = u.id
     ORDER BY s.id DESC"
)->fetchAll();
?>

<div class="page-header">
    <h1>Tüm Sunumlar (<?= count($submissions) ?>)</h1>
</div>

<div class="content-box">
    <div class="table-responsive">
        <table class="styled-table">
             <thead><tr><th>Önizleme</th><th>Tasarımcı</th><th>Proje</th><th>Yüklenme Tarihi</th><th>İşlemler</th></tr></thead>
            <tbody>
                <?php foreach($submissions as $sub): ?>
                <tr>
                    <td><a href="../<?= htmlspecialchars($sub['file_path']) ?>" target="_blank"><img src="../<?= htmlspecialchars($sub['file_path']) ?>" width="100"></a></td>
                    <td><?= htmlspecialchars($sub['designer_name']) ?></td>
                    <td><?= htmlspecialchars($sub['project_title']) ?></td>
                    <td><?= date('d M Y', strtotime($sub['created_at'])) ?></td>
                    <td>
                        <form action="actions/delete_submission.php" method="POST" onsubmit="return confirm('Bu sunumu kalıcı olarak silmek istediğinizden emin misiniz?');">
                            <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
                            <button type="submit" class="btn btn-danger">Sil</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>