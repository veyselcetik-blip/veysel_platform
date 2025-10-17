<?php
include 'includes/header.php';

$message = '';

// Form gönderildiyse ayarları güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings_to_update = ['session_timeout', 'recaptcha_enabled', 'recaptcha_site_key', 'recaptcha_secret_key'];
    foreach ($settings_to_update as $key) {
        if (isset($_POST[$key])) {
            $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")->execute([$_POST[$key], $key]);
        }
    }
    // Ayarlar güncellendikten sonra en güncel halini görmek için sayfayı yeniden yönlendir
    header("Location: security_settings.php?status=success");
    exit;
}

// Başarı mesajını göster
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $message = "<p style='color:green; font-weight:bold;'>Güvenlik ayarları başarıyla kaydedildi!</p>";
}

// === DÜZELTME BURADA ===
// init.php'de zaten çekilmiş olan $site_settings değişkenini doğrudan kullanıyoruz.
// Bu, veritabanına tekrar tekrar aynı sorguyu göndermemizi engeller ve daha verimlidir.
// Bu sayede, hataya neden olan sorguyu da tamamen kaldırmış oluyoruz.

?>

<div class="page-header">
    <h1>Güvenlik Ayarları</h1>
</div>

<?= $message ?>

<div class="content-box">
    <form method="POST">
        <h3>Oturum Süresi</h3>
        <div class="form-group">
            <label for="session_timeout">Otomatik Çıkış Süresi (Saniye)</label>
            <input type="number" id="session_timeout" name="session_timeout" value="<?= htmlspecialchars($site_settings['session_timeout']) ?>">
            <small>Kullanıcı bu süre boyunca işlem yapmazsa otomatik çıkış yapılır. 1800 = 30 dakika.</small>
        </div>
        <hr style="margin: 2rem 0;">
        <h3>Google reCAPTCHA Ayarları</h3>
        <div class="form-group">
            <label for="recaptcha_enabled">reCAPTCHA Aktif mi?</label>
            <select id="recaptcha_enabled" name="recaptcha_enabled">
                <option value="1" <?= ($site_settings['recaptcha_enabled'] == 1) ? 'selected' : '' ?>>Evet, Aktif</option>
                <option value="0" <?= ($site_settings['recaptcha_enabled'] == 0) ? 'selected' : '' ?>>Hayır, Kapalı</option>
            </select>
        </div>
        <div class="form-group">
            <label for="recaptcha_site_key">reCAPTCHA Site Anahtarı (Site Key)</label>
            <input type="text" id="recaptcha_site_key" name="recaptcha_site_key" value="<?= htmlspecialchars($site_settings['recaptcha_site_key']) ?>">
        </div>
        <div class="form-group">
            <label for="recaptcha_secret_key">reCAPTCHA Gizli Anahtar (Secret Key)</label>
            <input type="text" id="recaptcha_secret_key" name="recaptcha_secret_key" value="<?= htmlspecialchars($site_settings['recaptcha_secret_key']) ?>">
        </div>
        <button type="submit" class="btn btn-success">Güvenlik Ayarlarını Kaydet</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>