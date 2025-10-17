<?php
require 'includes/init.php';
require_login(); // Sadece giriş yapmış kullanıcılar proje oluşturabilir

$error = '';

// Formdan gelen ve form tekrar gösterildiğinde kullanılacak değişkenleri tanımla
$title = $_POST['title'] ?? '';
$category = $_POST['category'] ?? '';
$description = $_POST['description'] ?? '';
$budget = $_POST['budget'] ?? '';
$deadline = $_POST['deadline'] ?? '';
$is_public = $_POST['is_public'] ?? '1'; // Varsayılan olarak 'Herkese Açık' seçili olsun

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Geçersiz istek. Lütfen tekrar deneyin.';
    } else {
        $userId = $_SESSION['user_id'];
        $attachment_path = null;

        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $upload_result = secure_file_upload($_FILES['attachment'], ['jpg', 'jpeg', 'png', 'pdf', 'zip'], 10);
            if (isset($upload_result['success'])) {
                $attachment_path = $upload_result['filepath'];
            } else {
                $error = $upload_result['error'];
            }
        }

        if (empty($error)) {
            $stmt = $db->prepare(
                "INSERT INTO projects (user_id, title, category, description, budget, attachment_path, deadline, status, is_public) VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif', ?)"
            );
            $stmt->execute([$userId, $title, $category, $description, $budget, $attachment_path, $deadline, $is_public]);
            
            $new_project_id = $db->lastInsertId();
            header("Location: project-detail.php?id=" . $new_project_id);
            exit;
        }
    }
}

include 'includes/header.php';
?>

<?php include 'includes/navbar.php'; ?>

<div class="form-wrapper">
    <div class="container">
        <div class="form-container">
            <h2 class="section-title">Yeni Proje Başlat</h2>
            <p style="text-align: center; margin-bottom: 2rem;">Tasarım ihtiyacınızla ilgili detayları paylaşın...</p>

            <?php if ($error): ?>
                <p class="form-message error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                
                <div class="form-group">
                    <label for="title">Proje Başlığı</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" placeholder="Örn: Şirketim için Modern Logo Tasarımı" required>
                </div>

                <div class="form-group">
                    <label for="category">Tasarım Kategorisi</label>
                    <select id="category" name="category" required>
                        <option value="">Kategori Seçiniz...</option>
                        <?php
                        $categories_stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
                        while ($cat = $categories_stmt->fetch()) {
                            $selected = ($category == $cat['name']) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($cat['name']) . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Proje Detayları</label>
                    <textarea id="description" name="description" rows="8" placeholder="Markanız hakkında bilgi..." required><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="form-group">
    <label for="budget">Bütçe Aralığı</label>
    <select id="budget" name="budget">
        <option value="">Bütçe seçin</option>
        <option value="0-500">0 – 500 TL</option>
        <option value="501-1000">501 – 1.000 TL</option>
        <option value="1001-2000">1.001 – 2.000 TL</option>
        <option value="2001-5000">2.001 – 5.000 TL</option>
        <option value="5001-10000">5.001 – 10.000 TL</option>
        <option value="10001+">10.001 TL ve üzeri</option>
    </select>
</div>


                <div class="form-group">
                    <label for="deadline">Proje Bitiş Tarihi</label>
                    <input type="date" id="deadline" name="deadline" value="<?= htmlspecialchars($deadline) ?>" required>
                    <small>Tasarımcıların sunum yapabileceği son günü seçin.</small>
                </div>
                
                <div class="form-group">
                    <label>Proje Görünürlüğü</label>
                    <div class="radio-group">
                         <label><input type="radio" name="is_public" value="1" <?= ($is_public == '1') ? 'checked' : '' ?>> Herkese Açık</label>
                         <label><input type="radio" name="is_public" value="0" <?= ($is_public == '0') ? 'checked' : '' ?>> Gizli</label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="attachment">Referans Dosyalar (İsteğe Bağlı)</label>
                    <input type="file" id="attachment" name="attachment">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Projemi Yayınla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>