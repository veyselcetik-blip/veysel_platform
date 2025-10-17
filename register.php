<?php
require 'includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
if (($site_settings['recaptcha_enabled'] ?? 0) == 1) {
        $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
        if (empty($recaptcha_response)) {
             header("Location: index.php?login_error=recaptcha_failed");
             exit;
        }
        $secret_key = $site_settings['recaptcha_secret_key'];
        $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$recaptcha_response}";
        $response_data = json_decode(file_get_contents($verify_url));
        if (!$response_data->success) {
            header("Location: index.php?login_error=recaptcha_failed");
            exit;
        }
    }
    // Email'in zaten kayıtlı olup olmadığını kontrol et
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        // Hata durumunda ana sayfaya hata parametresiyle dön
        header("Location: index.php?registration_error=email_exists");
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $activation_code = bin2hex(random_bytes(16)); // Benzersiz bir aktivasyon kodu oluştur

    // register.php içinde
// ...
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$activation_code = bin2hex(random_bytes(16));
$registration_ip = $_SERVER['REMOTE_ADDR']; // Kullanıcının IP'sini al

// INSERT sorgusunu güncelle
$stmt = $db->prepare(
    "INSERT INTO users (username, email, password, role, status, activation_code, registration_ip) VALUES (?, ?, ?, 'user', 'onay_bekliyor', ?, ?)"
);
$stmt->execute([$username, $email, $hashed_password, $activation_code, $registration_ip]);
// ...
    // Aktivasyon linkini oluştur
    $activation_link = "http://localhost/veysel_platform/activate.php?email=" . urlencode($email) . "&code=" . $activation_code;

    // E-posta içeriğini hazırla
    $subject = "Veysel Platform Hesap Aktivasyonu";
    $body = "
        <h2>Hesabınızı Aktifleştirin</h2>
        <p>Merhaba " . htmlspecialchars($username) . ",</p>
        <p>Platformumuza kaydolduğunuz için teşekkür ederiz. Hesabınızı aktifleştirmek için lütfen aşağıdaki linke tıklayın:</p>
        <p><a href='" . $activation_link . "'>" . $activation_link . "</a></p>
        <p>Teşekkürler,<br>Veysel Platform Ekibi</p>
    ";

    // E-postayı gönder
    if (send_email($email, $subject, $body)) {
        // Başarılı olursa kullanıcıyı bilgilendir
        header("Location: message.php?status=registration_pending");
        exit;
    } else {
        // E-posta gönderilemezse hata ver
        header("Location: message.php?status=email_error");
        exit;
    }
}
// POST değilse ana sayfaya yönlendir
header("Location: index.php");
exit;
?>

