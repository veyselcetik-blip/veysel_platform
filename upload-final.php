<?php
require 'includes/init.php';
require_login(); // Sadece giriş yapmış kullanıcılar erişebilir

// URL'den proje ID'sini al
$project_id = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT);
if (!$project_id) {
    // Proje ID'si yoksa veya geçersizse, kullanıcıyı paneline yönlendir.
    header("Location: dashboard.php");
    exit;
}

// Proje bilgilerini çek
$stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

// Güvenlik Kontrolleri:
// 1. Proje var mı?
// 2. Projenin bir kazananı var mı?
// 3. Bu sayfaya giren kişi, o projenin kazananı mı?
if (!$project || !$project['winner_id'] || $project['winner_id'] != $_SESSION['user_id']) {
    // Yetkisi yoksa, özel bir hata mesajı göster.
    header("Location: message.php?status=unauthorized_final_upload");
    exit;
}

// Proje zaten tamamlanmışsa, tekrar dosya yüklemesine izin verme.
if ($project['status'] === 'tamamlandı') {
    header("Location: message.php?status=project_already_completed");
    exit;
}

$error = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['final_file'])) {
    // Güvenli dosya yükleme fonksiyonumuzu kullanıyoruz (özellikle .zip gibi arşiv dosyalarına izin ver)
    $upload_result = secure_file_upload(
        $_FILES['final_file'],
        ['zip', 'rar', '7z'],
        50 // Maksimum 50MB
    );

    if (isset($upload_result['success'])) {
        $final_file_path = $upload_result['filepath'];

        // 1. Projenin final dosya yolunu ve durumunu güncelle
        $update_stmt = $db->prepare("UPDATE projects SET final_file = ?, status = 'tamamlandı' WHERE id = ?");
        $update_stmt->execute([$final_file_path, $project_id]);
        
        // 2. Proje sahibine bildirim gönder
        $notification_message = "<b>" . htmlspecialchars($project['title']) . "</b> projesinin kazananı final dosyalarını yükledi. Şimdi indirebilirsiniz.";
        $notification_link = "project-detail.php?id=" . $project_id;
        $notify_stmt = $db->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $notify_stmt->execute([$project['user_id'], $notification_message, $notification_link]);
        
        // Başarıyla tamamlandıktan sonra proje detay sayfasına yönlendir
        header("Location: project-detail.php?id=" . $project_id . "&status=final_uploaded");
        exit;

    } else {
        $error = $upload_result['error'];
    }
}

include 'includes/header.php';
?>

<?php include 'includes/navbar.php'; ?>

<div class="form-wrapper">
    <div class="container">
        <div class="form-container" style="max-width: 700px;">
            <h2 class="section-title">Final Dosyalarını Yükle</h2>
            <p style="text-align: center; margin-bottom: 2rem;">
                Tebrikler! <strong>"<?= htmlspecialchars($project['title']) ?>"</strong> projesini kazandınız. Lütfen tüm proje dosyalarını (.zip formatında) tek bir dosya halinde yükleyerek teslimatı tamamlayın.
            </p>

            <?php if ($error): ?>
                <p class="form-message error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="final_file">Teslimat Dosyası (.zip, .rar)</label>
                    <input type="file" id="final_file" name="final_file" required>
                    <small>Tüm kaynak dosyaları (AI, PSD, SVG vb.), fontları ve önizleme görsellerini içeren tek bir .zip dosyası yüklemeniz önerilir.</small>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-success btn-lg" style="width: 100%;">Teslimatı Tamamla ve Gönder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>