<?php
include 'includes/header.php';

$message = '';

// Form gönderildiyse ayarları güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Metin tabanlı ayarları güncelle
    foreach ($_POST as $key => $value) {
        if ($key == 'site_name') {
            $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([trim($value), $key]);
        }
    }

    // Logo yükleme
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
        // secure_file_upload fonksiyonunu burada da kullanabiliriz ama ana dizine göre yol ayarlamalıyız
        $target_dir = "../assets/";
        $filename = "logo_" . time() . "_" . basename($_FILES["site_logo"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["site_logo"]["tmp_name"], $target_file)) {
            $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_logo'")->execute(['assets/' . $filename]);
        }
    }
    
    // Favicon yükleme
    if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] == 0) {
        $target_dir = "../assets/";
        $filename = "favicon_" . time() . "_" . basename($_FILES["site_favicon"]["name"]);
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($_FILES["site_favicon"]["tmp_name"], $target_file)) {
             $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_favicon'")->execute(['assets/' . $filename]);
        }
    }

    $message = "<p style='color:green; font-weight:bold;'>Ayarlar başarıyla güncellendi!</p>";
}

// Tüm ayarları veritabanından çek
$settings_raw = $db->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($settings_raw as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}
?>

<div class="page-header">
    <h1>Genel Site Ayarları</h1>
</div>

<?= $message ?>

<div class="content-box">
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="site_name">Site Adı</label>
            <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name']) ?>" class="form-control">
        </div>
        
        <div class="form-group">
            <label for="site_logo">Site Logosu</label>
            <br>
            <?php if (!empty($settings['site_logo']) && file_exists('../' . $settings['site_logo'])): ?>
                <img src="../<?= htmlspecialchars($settings['site_logo']) ?>" alt="Mevcut Logo" height="50" style="background:#ddd; padding:5px; border-radius:5px; margin-bottom:10px;">
            <?php endif; ?>
            <input type="file" id="site_logo" name="site_logo" class="form-control">
            <small>Yeni bir logo yükleyerek mevcut olanı değiştirin. (Önerilen: PNG)</small>
        </div>

        <div class="form-group">
            <label for="site_favicon">Site Favicon</label>
            <br>
             <?php if (!empty($settings['site_favicon']) && file_exists('../' . $settings['site_favicon'])): ?>
                <img src="../<?= htmlspecialchars($settings['site_favicon']) ?>" alt="Mevcut Favicon" height="32" style="margin-bottom:10px;">
            <?php endif; ?>
            <input type="file" id="site_favicon" name="site_favicon" class="form-control">
            <small>Yeni bir favicon yükleyerek mevcut olanı değiştirin. (Önerilen: .ico veya 32x32 PNG)</small>
        </div>
        
        <button type="submit" class="btn btn-success">Ayarları Kaydet</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>