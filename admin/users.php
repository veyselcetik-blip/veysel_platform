<?php 
include 'includes/header.php'; 

// --- 1. FİLTRELEME VE ARAMA PARAMETRELERİNİ ALMA ---
$search_query = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$sort_order = $_GET['sort'] ?? 'id_desc';

// --- 2. VERİTABANI SORGUSUNU DİNAMİK OLARAK OLUŞTURMA ---
$params = [];
$sql = "SELECT id, username, email, role, status, created_at FROM users";
$where_clauses = [];

if (!empty($search_query)) {
    $where_clauses[] = "(username LIKE :search OR email LIKE :search)";
    $params[':search'] = "%" . $search_query . "%";
}
if (!empty($role_filter)) {
    $where_clauses[] = "role = :role";
    $params[':role'] = $role_filter;
}
if (!empty($status_filter)) {
    $where_clauses[] = "status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Sıralama mantığı
switch ($sort_order) {
    case 'date_asc': $sql .= " ORDER BY created_at ASC"; break;
    case 'name_asc': $sql .= " ORDER BY username ASC"; break;
    default: $sql .= " ORDER BY id DESC"; break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Kullanıcı Yönetimi (<?= count($users) ?> sonuç bulundu)</h1>
</div>

<div class="content-box">
    <h3>Kullanıcı Filtrele ve Ara</h3>
    <form method="GET" class="filter-bar">
        <div class="form-group">
            <label for="search">Arama</label>
            <input type="text" id="search" name="search" placeholder="İsim veya e-posta..." value="<?= htmlspecialchars($search_query) ?>">
        </div>
        <div class="form-group">
            <label for="role">Rol</label>
            <select id="role" name="role">
                <option value="">Tümü</option>
                <option value="user" <?= $role_filter == 'user' ? 'selected' : '' ?>>User</option>
                <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>
        <div class="form-group">
            <label for="status">Durum</label>
            <select id="status" name="status">
                <option value="">Tümü</option>
                <option value="aktif" <?= $status_filter == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="askıda" <?= $status_filter == 'askıda' ? 'selected' : '' ?>>Askıda</option>
            </select>
        </div>
        <div class="form-group">
            <label for="sort">Sırala</label>
            <select id="sort" name="sort">
                <option value="id_desc" <?= $sort_order == 'id_desc' ? 'selected' : '' ?>>En Yeni</option>
                <option value="date_asc" <?= $sort_order == 'date_asc' ? 'selected' : '' ?>>En Eski</option>
                <option value="name_asc" <?= $sort_order == 'name_asc' ? 'selected' : '' ?>>İsme Göre (A-Z)</option>
            </select>
        </div>
        <button type="submit" class="btn">Filtrele</button>
    </form>
</div>

<div class="content-box" style="margin-top: 2rem;">
    <div class="table-responsive">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th><th>Kullanıcı Adı</th><th>Email</th><th>Rol</th><th>Durum</th><th>Kayıt Tarihi</th><th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" style="text-align: center;">Bu kriterlere uygun kullanıcı bulunamadı.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                            <td>
                                <span class="status-badge-admin status-<?= htmlspecialchars($user['status']) ?>">
                                    <?= htmlspecialchars(ucfirst($user['status'])) ?>
                                </span>
                            </td>
                            <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form action="actions/change_user_status.php" method="POST">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <select name="new_status" onchange="this.form.submit()">
                                        <option value="aktif" <?= $user['status'] == 'aktif' ? 'selected' : '' ?>>Aktif Yap</option>
                                        <option value="askıda" <?= $user['status'] == 'askıda' ? 'selected' : '' ?>>Askıya Al</option>
                                    </select>
                                </form>
                                <?php else: ?>
                                    <small>(Kendi durumunuzu değiştiremezsiniz)</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>