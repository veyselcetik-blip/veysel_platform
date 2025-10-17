<?php
require 'includes/init.php';

$token = $_GET['token'] ?? '';
$message = '';
$show_form = false;

if (empty($token)) {
    header("Location: message.php?status=invalid_link"); exit;
}

// DÜZELTME: Sadece token'a göre kullanıcıyı bul, zaman kontrolü yapma
$stmt = $db->prepare("SELECT id, reset_token_expires_at FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    // Eğer bu token ile bir kullanıcı hiç yoksa, link geçersizdir.
    $message = "<p class='form-message error'>Bu şifre sıfırlama linki geçersiz.</p>";
} else {
    // Kullanıcı bulundu, şimdi PHP içinde zaman kontrolü yapalım.
    $expires = new DateTime($user['reset_token_expires_at'], new DateTimeZone('UTC'));
    $now = new DateTime('now', new DateTimeZone('UTC'));

    if ($now > $expires) {
        // Eğer linkin süresi dolmuşsa
        $message = "<p class='form-message error'>Bu şifre sıfırlama linkinin süresi dolmuş.</p>";
    } else {
        // Link geçerli ve süresi dolmamış, formu göster.
        $show_form = true;
    }
}

// Yeni şifre formu gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && $show_form) {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if ($password === $password_confirm) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $update_stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?");
        $update_stmt->execute([$hashed_password, $user['id']]);

        header("Location: message.php?status=password_reset_success");
        exit;
    } else {
        $message = "<p class='form-message error'>Girdiğiniz şifreler eşleşmiyor.</p>";
    }
}

include 'includes/header.php';
?>
<?php include 'includes/navbar.php'; ?>

<div class="form-wrapper">
    <div class="container">
        <div class="form-container" style="max-width: 500px;">
            <h2 class="section-title">Yeni Şifre Belirle</h2>
            <?= $message ?>
            <?php if ($show_form): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <div class="form-group"><label for="password">Yeni Şifreniz</label><input type="password" id="password" name="password" required></div>
                <div class="form-group"><label for="password_confirm">Yeni Şifreniz (Tekrar)</label><input type="password" id="password_confirm" name="password_confirm" required></div>
                <div class="form-group"><button type="submit" class="btn btn-primary" style="width: 100%;">Şifreyi Değiştir</button></div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>