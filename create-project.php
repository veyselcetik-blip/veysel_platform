<?php
require 'includes/init.php';
require_login(); // Sadece giriş yapmış kullanıcılar proje oluşturabilir

$error = '';

// Formdan gelen ve form tekrar gösterildiğinde kullanılacak değişkenleri tanımla
$title = $_POST['title'] ?? '';
$category_id = $_POST['category_id'] ?? '';
$description = $_POST['description'] ?? '';
$budget = $_POST['budget'] ?? '';
$deadline = $_POST['deadline'] ?? '';
$is_public = $_POST['is_public'] ?? '1';

// Kategorileri veritabanından çekelim
$categories_stmt = $db->query("SELECT id, name, description FROM categories ORDER BY name ASC");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
// JavaScript'te kullanmak için kategorileri JSON formatına çevirelim
$categories_json = json_encode(array_column($categories, null, 'id'));


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Geçersiz istek. Lütfen tekrar deneyin.';
    } else {
        // Formdan gelen verileri al
        $userId = $_SESSION['user_id'];
        $attachment_path = null;
        
        // Kategori adını ID'den bul
        $category_name = '';
        foreach ($categories as $cat) {
            if ($cat['id'] == $category_id) {
                $category_name = $cat['name'];
                break;
            }
        }

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
            $stmt->execute([$userId, $title, $category_name, $description, $budget, $attachment_path, $deadline, $is_public]);
            
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
            <div class="form-header">
                <h2 class="section-title">Harika Bir Tasarım Brifi Oluşturalım</h2>
                <p>Ne kadar detaylı bilgi verirseniz, o kadar harika sonuçlar alırsınız.</p>
            </div>

            <?php if ($error): ?>
                <p class="form-message error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="create-project-form">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <div class="form-step">
                    <div class="form-group">
                        <label for="title">1. Projenize Akılda Kalıcı Bir İsim Verin</label>
                        <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" placeholder="Örn: 'Fırın Expres' için Modern ve Lezzetli Logo Tasarımı" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id">2. Hangi Kategoride Tasarıma İhtiyacınız Var?</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Kategori Seçiniz...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($category_id == $cat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="category-description" class="category-description-box">
                            <i class="fas fa-info-circle"></i>
                            <span>Lütfen bir kategori seçerek başlayın.</span>
                        </div>
                    </div>
                </div>

                <div class="form-step">
                    <label>3. Tasarımcıları Yönlendirin (En Önemli Adım!)</label>
                    <div class="brief-helper">
                        <div class="form-group">
                            <label for="description">Markanız / Projeniz Ne Hakkında?</label>
                            <textarea id="description" name="description" rows="6" placeholder="Ne iş yapıyorsunuz, hedef kitleniz kim, rakiplerinizden farkınız ne? Tasarımcılara ilham verecek hikayenizi anlatın..."><?= htmlspecialchars($description) ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="style_preferences">Beğendiğiniz / Beğenmediğiniz Stiller Neler?</label>
                            <textarea id="style_preferences" name="style_preferences" rows="4" placeholder="Örn: 'Minimalist ve modern tasarımları seviyorum. Çok fazla renk ve karmaşık şekillerden kaçınalım.' Rakip logoları veya beğendiğiniz tasarımların linklerini ekleyebilirsiniz."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="color_preferences">Renk Tercihleriniz Var mı?</label>
                            <input type="text" id="color_preferences" name="color_preferences" placeholder="Örn: 'Sıcak ve samimi bir his için turuncu ve kahve tonları' veya 'Güven veren mavi tonları' gibi.">
                        </div>
                    </div>
                </div>

                <div class="form-step">
                    <div class="form-group">
                        <label for="budget">4. Tasarım İçin Ayırdığınız Bütçe Nedir?</label>
                        <select id="budget" name="budget" required>
                            <option value="" disabled <?= empty($budget) ? 'selected' : '' ?>>Ödül miktarını seçin...</option>
                            <option value="500 - 1.000 TL" <?= ($budget == '500 - 1.000 TL') ? 'selected' : '' ?>>₺500 - ₺1.000 (Başlangıç)</option>
                            <option value="1.000 - 2.500 TL" <?= ($budget == '1.000 - 2.500 TL') ? 'selected' : '' ?>>₺1.000 - ₺2.500 (Orta Seviye)</option>
                            <option value="2.500 - 5.000 TL" <?= ($budget == '2.500 - 5.000 TL') ? 'selected' : '' ?>>₺2.500 - ₺5.000 (Deneyimli)</option>
                            <option value="5.000+ TL" <?= ($budget == '5.000+ TL') ? 'selected' : '' ?>>₺5.000+ (Profesyonel)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="deadline">5. Yarışma Ne Kadar Sürecek?</label>
                        <input type="date" id="deadline" name="deadline" value="<?= htmlspecialchars($deadline) ?>" required>
                        <small>Tasarımcıların sunum yapabileceği son günü seçin. Genellikle 7-10 gün idealdir.</small>
                    </div>
                </div>
                
                <div class="form-step">
                    <div class="form-group">
                        <label for="attachment">6. Referans Dosyalarınız Var mı? (Logo, renk paleti, ilham görselleri vb.)</label>
                        <input type="file" id="attachment" name="attachment">
                        <small>Birden fazla dosya varsa, lütfen hepsini bir .zip dosyası yapın.</small>
                    </div>

                    <div class="form-group">
                        <label>7. Proje Görünürlüğü</label>
                        <div class="radio-group">
                             <label><input type="radio" name="is_public" value="1" <?= ($is_public == '1') ? 'checked' : '' ?>> <strong>Herkese Açık:</strong> Tüm ziyaretçiler ve tasarımcılar proje detaylarını ve sunumları görebilir.</label>
                             <label><input type="radio" name="is_public" value="0" <?= ($is_public == '0') ? 'checked' : '' ?>> <strong>Gizli Proje:</strong> Proje detaylarını ve sunumları sadece giriş yapmış tasarımcılar görebilir.</label>
                        </div>
                    </div>
                </div>

                <div class="form-group form-submit-group">
                    <button type="submit" class="btn btn-primary btn-lg">Projemi Yayınla ve Tasarımları Almaya Başla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Kategorilerin tanım bilgilerini PHP'den alıyoruz
    const categoriesData = <?= $categories_json ?>;
</script>

<?php include 'includes/footer.php'; ?>