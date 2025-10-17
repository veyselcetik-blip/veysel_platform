<?php
include 'includes/header.php';

$message = '';

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Eğer "AYARLARI KAYDET" butonuna basıldıysa
    if (isset($_POST['save_settings'])) {
        $settings_to_update = ['site_email', 'smtp_host', 'smtp_user', 'smtp_port', 'smtp_secure'];
        foreach ($settings_to_update as $key) {
            if (isset($_POST[$key])) {
                $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?")->execute([$_POST[$key], $key]);
            }
        }
        // Şifre alanı boş bırakılmadıysa güncelle
        if (!empty($_POST['smtp_pass'])) {
            $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'smtp_pass'")->execute([$_POST['smtp_pass']]);
        }
        $message = "<p style='color:green; font-weight:bold;'>Ayarlar başarıyla güncellendi!</p>";
    }

    // Eğer "TEST E-POSTASI GÖNDER" butonuna basıldıysa
    if (isset($_POST['send_test_email'])) {
        $test_email_address = filter_input(INPUT_POST, 'test_email', FILTER_VALIDATE_EMAIL);
        if ($test_email_address) {
            $subject = "Veysel Platform - SMTP Test E-postası";
            $body = "Tebrikler! Bu, e-posta ayarlarınızın doğru çalıştığını gösteren bir test mesajıdır.";
            
            // Daha önce yazdığımız merkezi e-posta fonksiyonunu çağırıyoruz
            if (send_email($test_email_address, $subject, $body)) {
                $message = "<p style='color:green; font-weight:bold;'>" . htmlspecialchars($test_email_address) . " adresine test e-postası başarıyla gönderildi!</p>";
            } else {
                $message = "<p style='color:red; font-weight:bold;'>Test e-postası gönderilemedi. Lütfen ayarlarınızı (özellikle şifreyi) ve sunucu hata günlüklerini kontrol edin.</p>";
            }
        } else {
            $message = "<p style='color:red; font-weight:bold;'>Geçerli bir test e-posta adresi girmediniz.</p>";
        }
    }
}

// Ayarları veritabanından tekrar çek (güncel hallerini görmek için)
$settings_raw = $db->query("SELECT * FROM settings")->fetchAll();
$settings = [];
foreach ($settings_raw as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}
?>

<div class="page-header">
    <h1>E-posta Ayarları (SMTP)</h1>
</div>

<?= $message ?>

<div class="dashboard-grid">
    <div class="content-box">
        <h3>SMTP Ayarları</h3>
        <form method="POST">
            <div class="form-group"><label for="site_email">Gönderen E-posta Adresi</label><input type="email" id="site_email" name="site_email" value="<?= htmlspecialchars($settings['site_email']) ?>" class="form-control" required></div>
            <div class="form-group"><label for="smtp_host">SMTP Sunucusu (Host)</label><input type="text" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host']) ?>" class="form-control" placeholder="örn: smtp.gmail.com" required></div>
            <div class="form-group"><label for="smtp_port">SMTP Port</label><input type="number" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port']) ?>" class="form-control" placeholder="örn: 587" required></div>
            <div class="form-group"><label for="smtp_secure">Güvenlik</label><select id="smtp_secure" name="smtp_secure" class="form-control"><option value="tls" <?= $settings['smtp_secure'] == 'tls' ? 'selected' : '' ?>>TLS</option><option value="ssl" <?= $settings['smtp_secure'] == 'ssl' ? 'selected' : '' ?>>SSL</option></select></div>
            <div class="form-group"><label for="smtp_user">SMTP Kullanıcı Adı</label><input type="text" id="smtp_user" name="smtp_user" value="<?= htmlspecialchars($settings['smtp_user']) ?>" class="form-control" placeholder="e-posta adresiniz" required></div>
            <div class="form-group"><label for="smtp_pass">SMTP Şifresi</label><input type="password" id="smtp_pass" name="smtp_pass" class="form-control" placeholder="Mevcut şifreyi korumak için boş bırakın"><small>Yeni bir şifre girdiğinizde mevcut şifre üzerine yazılacaktır.</small></div>
            <button type="submit" name="save_settings" class="btn btn-success">Ayarları Kaydet</button>
        </form>
    </div>

    <div class="content-box">
        <h3>Ayarları Test Et</h3>
        <p>Mevcut kayıtlı ayarların doğru çalıştığını kontrol etmek için kendinize bir test e-postası gönderin.</p>
        <form method="POST">
            <div class="form-group">
                <label for="test_email">Alıcı E-posta Adresi</label>
                <input type="email" id="test_email" name="test_email" class="form-control" placeholder="Kontrol edeceğiniz e-posta adresi" required>
            </div>
            <button type="submit" name="send_test_email" class="btn" style="background-color: var(--accent-color); color:white;">Test E-postası Gönder</button>
        </form>
    </div>
</div>

</div>
<?php include 'includes/footer.php'; ?>