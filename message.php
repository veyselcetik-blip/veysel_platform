<?php
require 'includes/init.php';

$status = $_GET['status'] ?? 'default';
// message.php dosyasındaki $messages dizisini bununla değiştirin

$messages = [
    'registration_pending' => [
        'title' => 'Kayıt Başarılı!',
        'body' => 'Kaydınız başarıyla alınmıştır. Lütfen e-posta adresinize gönderdiğimiz aktivasyon linkine tıklayarak hesabınızı onaylayın.',
        'type' => 'success'
    ],
    'email_error' => [
        'title' => 'Hata!',
        'body' => 'Aktivasyon e-postası gönderilirken bir sorun oluştu. Lütfen daha sonra tekrar deneyin.',
        'type' => 'error'
    ],
    'activated' => [
        'title' => 'Hesap Aktifleştirildi!',
        'body' => 'Hesabınız başarıyla aktifleştirildi. Artık giriş yapabilirsiniz.',
        'type' => 'success'
    ],
    'already_activated' => [
        'title' => 'Bilgi',
        'body' => 'Bu hesap zaten aktif durumda.',
        'type' => 'info'
    ],
    'invalid_link' => [
        'title' => 'Geçersiz Link!',
        'body' => 'Kullandığınız aktivasyon linki geçersiz veya süresi dolmuş.',
        'type' => 'error'
    ],
    // ================== YENİ EKLENEN BÖLÜM ==================
    'password_reset_success' => [
        'title' => 'Şifre Değiştirildi!',
        'body' => 'Şifreniz başarıyla güncellendi. Artık yeni şifrenizle giriş yapabilirsiniz.',
        'type' => 'success'
    ],
    // =======================================================
    'default' => [
        'title' => 'Bilgi',
        'body' => 'İşleminiz hakkında bir mesaj.',
        'type' => 'info'
    ]
];
$message = $messages[$status];

include 'includes/header.php';
?>
<?php include 'includes/navbar.php'; ?>
<div class="container page-container">
    <div class="message-box <?= $message['type'] ?>">
        <h2><?= $message['title'] ?></h2>
        <p><?= $message['body'] ?></p>
        <a href="index.php" class="btn btn-primary" style="margin-top: 1rem;">Ana Sayfaya Dön</a>
    </div>
</div>
<?php include 'includes/footer.php'; ?>