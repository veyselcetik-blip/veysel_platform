<?php 
include 'includes/header.php'; 

// Tüm projeleri, sahiplerinin kullanıcı adlarıyla birlikte çek
$projects = $db->query(
    "SELECT p.id, p.title, p.status, p.created_at, u.username 
     FROM projects p 
     JOIN users u ON p.user_id = u.id 
     ORDER BY p.id DESC"
)->fetchAll();
?>

<div class="page-header">
    <h1>Proje Yönetimi (<?= count($projects) ?>)</h1>
</div>

<div class="content-box">
    <div class="table-responsive">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Proje Başlığı</th>
                    <th>Sahibi</th>
                    <th>Durum</th>
                    <th>Oluşturma Tarihi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?= $project['id'] ?></td>
                    <td><a href="../project-detail.php?id=<?= $project['id'] ?>" target="_blank"><?= htmlspecialchars($project['title']) ?></a></td>
                    <td><?= htmlspecialchars($project['username']) ?></td>
                    <td><?= htmlspecialchars($project['status']) ?></td>
                    <td><?= date('d M Y', strtotime($project['created_at'])) ?></td>
                    <td>
                        <form action="actions/delete_project.php" method="POST" onsubmit="return confirm('Bu projeyi ve içindeki tüm sunumları/yorumları kalıcı olarak silmek istediğinizden emin misiniz?');">
                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
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