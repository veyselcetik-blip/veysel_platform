<?php 
include 'includes/header.php'; 

$upload_dir = '../uploads/';
$message = '';

// --- Dosya Silme İşlemi (Aynı kalacak) ---
if (isset($_GET['delete'])) {
    $file_to_delete = basename($_GET['delete']);
    $file_path = $upload_dir . $file_to_delete;
    if (file_exists($file_path) && is_file($file_path)) {
        if (unlink($file_path)) {
            $message = "<p style='color:green; font-weight:bold;'>'" . htmlspecialchars($file_to_delete) . "' dosyası başarıyla silindi.</p>";
        } else {
            $message = "<p style='color:red; font-weight:bold;'>" . htmlspecialchars($file_to_delete) . "' dosyası silinirken bir hata oluştu.</p>";
        }
    } else {
        $message = "<p style='color:red; font-weight:bold;'>Silinmek istenen dosya bulunamadı veya geçersiz.</p>";
    }
}

// --- YENİ ARAMA MANTIĞI ---
$search_query = $_GET['search'] ?? '';
$files = []; // Başlangıçta dosya listesi boş

// Eğer bir arama yapıldıysa, dosyaları filtrele
if (!empty($search_query)) {
    $all_files = array_diff(scandir($upload_dir), ['.', '..']); // Tüm dosyaları al
    
    // array_filter ile sadece arama terimini içeren dosyaları seç
    $files = array_filter($all_files, function($file) use ($search_query) {
        // stristr büyük/küçük harf duyarsız arama yapar
        return stristr($file, $search_query) !== false;
    });
}
?>

<div class="page-header">
    <h1>Dosya Yöneticisi</h1>
</div>

<?= $message ?>

<div class="content-box">
    <h3>Dosya Ara</h3>
    <form method="GET">
        <div style="display: flex; gap: 1rem;">
            <input type="text" name="search" placeholder="Dosya adı..." value="<?= htmlspecialchars($search_query) ?>" style="flex-grow: 1; padding: 0.5rem;">
            <button type="submit" class="btn" style="background-color: var(--accent-color); color:white;">Ara</button>
        </div>
    </form>
</div>
<div class="content-box" style="margin-top: 2rem;">
    <div class="table-responsive">
        <table class="styled-table">
            <thead>
                <tr><th>Dosya Adı</th><th>Boyut</th><th>Oluşturma Tarihi</th><th>İşlemler</th></tr>
            </thead>
            <tbody>
                <?php if (!empty($files)): ?>
                    <?php foreach($files as $file): ?>
                    <tr>
                        <td><a href="../<?= $upload_dir . htmlspecialchars($file) ?>" target="_blank"><?= htmlspecialchars($file) ?></a></td>
                        <td><?= round(filesize($upload_dir . $file) / 1024, 2) ?> KB</td>
                        <td><?= date('d M Y, H:i', filemtime($upload_dir . $file)) ?></td>
                        <td>
                            <a href="?delete=<?= urlencode($file) ?>&search=<?= urlencode($search_query) // Arama sorgusunu koru ?>" onclick="return confirm('Bu dosyayı sunucudan kalıcı olarak silmek istediğinizden emin misiniz?');" class="btn btn-danger">Sil</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php elseif (!empty($search_query)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">"<?= htmlspecialchars($search_query) ?>" ile eşleşen dosya bulunamadı.</td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">Bir dosyayı bulmak için yukarıdaki arama kutusunu kullanın.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>