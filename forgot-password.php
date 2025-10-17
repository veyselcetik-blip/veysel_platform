<?php
require 'includes/init.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $message = "<p class='form-message success'>Eğer bu e-posta adresi sistemimizde kayıtlıysa, size bir şifre sıfırlama linki gönderdik.</p>";

    if ($user) {
        $token = bin2hex(random_bytes(32));
        
        // DÜZELTME: Son kullanma tarihini UTC olarak oluştur
        $expires = new DateTime('now', new DateTimeZone('UTC'));
        $expires->add(new DateInterval('PT1H')); // 1 saat ekle
        $expires_string = $expires->format('Y-m-d H:i:s');

        $update_stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?");
        $update_stmt->execute([$token, $expires_string, $user['id']]);

        $reset_link = "http://localhost/veysel_platform/reset-password.php?token=" . $token;
        
        $subject = "Veysel Platform Şifre Sıfırlama";
        $body = "<p>Şifrenizi sıfırlamak için aşağıdaki linke tıklayın. Bu link 1 saat boyunca geçerlidir.</p><p><a href='{$reset_link}'>{$reset_link}</a></p>";
        
        send_email($email, $subject, $body);
    }
}

include 'includes/header.php';
?>
<?php include 'includes/navbar.php'; ?>

<div class="form-wrapper">
    <div class="container">
        <div class="form-container" style="max-width: 500px;">
            <h2 class="section-title">Şifremi Unuttum</h2>
            <p style="text-align: center; margin-bottom: 2rem;">Hesabınıza ait e-posta adresini girin, size yeni bir şifre oluşturma linki gönderelim.</p>
            <?= $message ?>
            <form method="POST">
                <div class="form-group"><label for="email">E-posta Adresiniz</label><input type="email" id="email" name="email" required></div>
                <div class="form-group"><button type="submit" class="btn btn-primary" style="width: 100%;">Sıfırlama Linki Gönder</button></div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>