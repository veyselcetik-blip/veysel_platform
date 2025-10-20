<?php 
include 'includes/header.php'; 

// --- 1. ARAMA PARAMETRESİNİ ALMA ---
$search_query = $_GET['search'] ?? '';

// --- 2. VERİTABANI SORGUSUNU DİNAMİK OLARAK OLUŞTURMA ---
$params = [];
$sql = "SELECT s.id, s.file_path, s.created_at, s.entry_number, -- Entry_number'ı da seçiyoruz
               p.title as project_title, p.id as project_id, 
               u.username as designer_name
        FROM submissions s
        JOIN projects p ON s.project_id = p.id
        JOIN users u ON s.user_id = u.id";

if (!empty($search_query)) {
    // Arama sorgusunu kullanıcı adı, proje başlığı, sunum ID'si ve dosya adına uygula
    $sql .= " WHERE (u.username LIKE :search_user 
                  OR p.title LIKE :search_project 
                  OR s.file_path LIKE :search_file 
                  OR s.id = :search_id)"; // ID'ye göre tam eşleşme
                  
    $params[':search_user'] = "%" . $search_query . "%";
    $params[':search_project'] = "%" . $search_query . "%";
    $params[':search_file'] = "%" . $search_query . "%";
    $params[':search_id'] = $search_query; // ID için tam eşleşme
}

$sql .= " ORDER BY s.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$submissions = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Sunum Yönetimi (<?= count($submissions) ?> sonuç bulundu)</h1>
</div>

<div class="content-box">
    <h3>Sunum Ara</h3>
    <form method="GET" class="filter-bar">
        <div class="form-group" style="grid-column: 1 / -1; display: flex; gap: 1rem;">
            <input type="text" id="search" name="search" placeholder="Sunum ID, dosya adı, tasarımcı veya proje adı ile ara..." value="<?= htmlspecialchars($search_query) ?>" style="flex-grow: 1;">
            <button type="submit" class="btn" style="background-color: var(--accent-color); color:white;">Ara</button>
        </div>
    </form>
</div>

<div class="content-box" style="margin-top: 2rem;">
    <div class="table-responsive">
        <table class="styled-table">
             <thead>
                <tr>
                    <th>Önizleme</th>
                    <th>Sunum Bilgileri</th>
                    <th>Tasarımcı</th>
                    <th>Proje</th>
                    <th>Yüklenme Tarihi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($submissions)): ?>
                    <tr><td colspan="6" style="text-align: center;">Bu kriterlere uygun sunum bulunamadı.</td></tr>
                <?php else: ?>
                    <?php foreach($submissions as $sub): ?>
                    <tr>
                        <td>
                            <a href="../<?= htmlspecialchars($sub['file_path']) ?>" target="_blank">
                                <img src="../<?= htmlspecialchars($sub['file_path']) ?>" width="100" style="border-radius: var(--border-radius); border: 1px solid var(--border-color);">
                            </a>
                        </td>
                        <td>
                            <strong>ID:</strong> <?= $sub['id'] ?><br>
                            <strong>Yarışma No:</strong> #<?= $sub['entry_number'] ?><br>
                            <small style="word-break: break-all;"><?= htmlspecialchars(basename($sub['file_path'])) ?></small>
                        </td>
                        <td><?= htmlspecialchars($sub['designer_name']) ?></td>
                        <td>
                            <a href="../project-detail.php?id=<?= $sub['project_id'] ?>" target="_blank">
                                <?= htmlspecialchars($sub['project_title']) ?>
                            </a>
                        </td>
                        <td><?= date('d M Y', strtotime($sub['created_at'])) ?></td>
                        <td>
                            <form action="actions/delete_submission.php" method="POST" onsubmit="return confirm('Bu sunumu kalıcı olarak silmek istediğinizden emin misiniz?');">
                                <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
                                <button type="submit" class="btn btn-danger">Sil</button>
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