<?php
// includes/init.php - Nihai Güvenlik Sürümü
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ADIM 1: IP KONTROLÜ (HER ŞEYDEN ÖNCE)
// ---------------------------------------------
// Veritabanı bağlantısını diğer her şeyden önce kuralım.
require_once 'db.php';

// Ziyaretçinin IP'sini al
$current_ip = $_SERVER['REMOTE_ADDR'];

// Bu IP'nin engelli olup olmadığını kontrol et
$stmt_ip_check = $db->prepare("SELECT id FROM banned_ips WHERE ip_address = ?");
$stmt_ip_check->execute([$current_ip]);
if ($stmt_ip_check->fetch()) {
    // Eğer IP engelliyse, hiçbir şey yüklemeden siteyi durdur.
    http_response_code(403);
    die("Erişim Engellendi.");
}
// ---------------------------------------------

// ADIM 2: NORMAL İŞLEMLER
// ✅ CSRF TOKEN OLUŞTUR
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'functions.php';
start_secure_session();
generate_csrf_token();

// Site ayarlarını çek
$settings_raw = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$site_settings = $settings_raw ?: [];

// ADIM 3: GİRİŞ YAPMIŞ KULLANICI İŞLEMLERİ
if (is_logged_in()) {
    // Oturum süresi kontrolü
    $session_timeout = (int)($site_settings['session_timeout'] ?? 1800);
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
        session_unset(); session_destroy();
        header("Location: index.php?status=session_expired");
        exit;
    }
    $_SESSION['last_activity'] = time();

    // YENİ: Giriş yapmış kullanıcının son IP'sini her sayfa ziyaretinde güncelle
    $update_ip_stmt = $db->prepare("UPDATE users SET last_login_ip = ? WHERE id = ?");
    $update_ip_stmt->execute([$current_ip, $_SESSION['user_id']]);
}
?>