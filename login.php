<?php
// login.php - Nihai, Birleştirilmiş ve Düzeltilmiş Sürüm
require 'includes/init.php';

// Sadece POST metodu ile gelen istekleri işle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Adım: Formdan gelen verileri al
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // 2. Adım (Eğer aktifse): reCAPTCHA'yı doğrula
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

    // 3. Adım: Veritabanından kullanıcıyı kontrol et
    $stmt = $db->prepare("SELECT id, username, email, password, role, status FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // 4. Adım: Kullanıcı var mı ve şifre doğru mu?
    if ($user && password_verify($password, $user['password'])) {
        
        // 5. Adım: Kullanıcının durumunu kontrol et (askıda, onay bekliyor vb.)
        if ($user['status'] === 'askıda') {
            header("Location: index.php?login_error=suspended");
            exit;
        }
        if ($user['status'] === 'onay_bekliyor') {
            header("Location: index.php?login_error=not_activated");
            exit;
        }

        // 6. Adım: Giriş başarılı, session'ı başlat ve panele yönlendir
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time(); // Oturum süresi için başlangıç zamanını ayarla
        
        header("Location: dashboard.php");
        exit;

    } else {
        // Kullanıcı bulunamadı veya şifre yanlışsa
        header("Location: index.php?login_error=1");
        exit;
    }
}

// Bu sayfaya doğrudan (GET metoduyla) erişilmeye çalışılırsa, ana sayfaya yönlendir.
header("Location: index.php");
exit;
?>