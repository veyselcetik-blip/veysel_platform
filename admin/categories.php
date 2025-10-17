<?php 
include 'includes/header.php'; 

// Form gönderildiyse yeni kategori ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['category_name'])) {
    $category_name = trim($_POST['category_name']);
    $stmt = $db->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$category_name]);
    header("Location: categories.php"); // Sayfanın yenilenmesi için
    exit;
}

// Bir kategori silinmek istenirse
if (isset($_GET['delete'])) {
    $category_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($category_id) {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        header("Location: categories.php");
        exit;
    }
}

// Tüm kategorileri çek
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="page-header">
    <h1>Kategori Yönetimi</h1>
</div>

<div class="dashboard-grid">
    <div class="content-box">
        <h3>Yeni Kategori Ekle</h3>
        <form method="POST">
            <div style="display: flex; gap: 1rem;">
                <input type="text" name="category_name" placeholder="Yeni kategori adı..." required style="flex-grow: 1; padding: 0.5rem;">
                <button type="submit" class="btn" style="background-color: var(--accent-color); color:white;">Ekle</button>
            </div>
        </form>
    </div>

    <div class="content-box">
        <h3>Mevcut Kategoriler (<?= count($categories) ?>)</h3>
        <div class="table-responsive">
            <table class="styled-table">
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?= htmlspecialchars($category['name']) ?></td>
                            <td style="text-align: right;">
                                <a href="?delete=<?= $category['id'] ?>" onclick="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?');" style="color:var(--danger-color);">Sil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>