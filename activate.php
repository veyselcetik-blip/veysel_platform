<?php
require 'includes/init.php';

$email = $_GET['email'] ?? '';
$code = $_GET['code'] ?? '';

if (!empty($email) && !empty($code)) {
    $stmt = $db->prepare("SELECT id, status FROM users WHERE email = ? AND activation_code = ?");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['status'] === 'aktif') {
            header("Location: message.php?status=already_activated");
            exit;
        }
        // Kullanıcıyı 'aktif' yap ve aktivasyon kodunu temizle
        $update_stmt = $db->prepare("UPDATE users SET status = 'aktif', activation_code = NULL WHERE id = ?");
        $update_stmt->execute([$user['id']]);
        
        header("Location: message.php?status=activated");
        exit;
    }
}

// Kod veya e-posta geçersizse
header("Location: message.php?status=invalid_link");
exit;
?>