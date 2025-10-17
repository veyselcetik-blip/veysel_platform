<?php
// Gerekli başlangıç dosyasını dahil et
require_once '../includes/init.php';

// 1. GÜVENLİK KONTROLLERİ
// Kullanıcının giriş yapıp yapmadığını ve isteğin POST metodu ile gelip gelmediğini kontrol et
if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Eğer şartlar sağlanmıyorsa, ana sayfaya yönlendir ve işlemi sonlandır
    header("Location: ../index.php");
    exit;
}

// 2. FORM VERİLERİNİ GÜVENLİ BİR ŞEKİLDE AL
// filter_input kullanarak formdan gelen verileri al ve tam sayıya çevir
$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$submission_id = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
$winner_id = filter_input(INPUT_POST, 'winner_id', FILTER_VALIDATE_INT);

// Oturumdan mevcut kullanıcı ID'sini al
$current_user_id = $_SESSION['user_id'];

// Verilerin eksik veya geçersiz olup olmadığını kontrol et
if (!$project_id || !$submission_id || !$winner_id) {
    // Eğer veriler eksikse, hata mesajı göster ve işlemi durdur
    die('Eksik veya geçersiz bilgi gönderildi. Lütfen tekrar deneyin.');
}

// 3. YETKİ KONTROLÜ
// Projenin gerçekten bu kullanıcıya ait olup olmadığını veritabanından kontrol et
try {
    $stmt_check = $db->prepare("SELECT user_id FROM projects WHERE id = :project_id");
    $stmt_check->execute([':project_id' => $project_id]);
    $project_owner_id = $stmt_check->fetchColumn();

    // Eğer proje sahibi mevcut kullanıcı değilse, yetkisiz işlem hatası ver
    if ($project_owner_id != $current_user_id) {
        die('Bu işlemi yapmaya yetkiniz yok.');
    }
} catch (PDOException $e) {
    // Veritabanı hatası olursa logla (opsiyonel) ve genel bir hata mesajı göster
    // error_log($e->getMessage());
    die('Veritabanı sorgulama hatası oluştu.');
}

// 4. VERİTABANINI GÜNCELLE
// Proje tablosunu kazanan bilgileri (winner_id, winning_submission_id) ve durumu ile güncelle
try {
    $update_project = $db->prepare(
        "UPDATE projects SET winner_id = :winner_id, winning_submission_id = :submission_id, status = 'kazanan_secildi' WHERE id = :project_id"
    );
    $update_project->execute([
        ':winner_id' => $winner_id,
        ':submission_id' => $submission_id,
        ':project_id' => $project_id
    ]);
} catch (PDOException $e) {
    die('Proje güncellenirken bir hata oluştu.');
}


// 5. KAZANANA BİLDİRİM GÖNDER
try {
    // Bildirim mesajı için proje başlığını al
    $project_title_stmt = $db->prepare("SELECT title FROM projects WHERE id = :project_id");
    $project_title_stmt->execute([':project_id' => $project_id]);
    $project_title = $project_title_stmt->fetchColumn();

    // Bildirim mesajını ve linkini oluştur
    // htmlspecialchars() kullanarak olası XSS saldırılarını engelle
    $notification_message = "Tebrikler! <b>" . htmlspecialchars($project_title) . "</b> projesini kazandınız. Lütfen final dosyalarınızı yükleyin.";
    $notification_link = "project-detail.php?id=" . $project_id;

    // Bildirimi 'notifications' tablosuna ekle
    $stmt_notify = $db->prepare("INSERT INTO notifications (user_id, message, link, is_read) VALUES (:user_id, :message, :link, 0)");
    $stmt_notify->execute([
        ':user_id' => $winner_id,
        ':message' => $notification_message,
        ':link' => $notification_link
    ]);
} catch (PDOException $e) {
    // Bildirim gönderilirken hata olursa işlemi durdurma, sadece logla (opsiyonel)
    // Bu sayede ana işlem (kazanan seçme) başarılı olur ama bildirim gitmezse site çökmez.
    // error_log('Bildirim gönderilemedi: ' . $e->getMessage());
}


// 6. KULLANICIYI YÖNLENDİR
// İşlem başarıyla tamamlandıktan sonra kullanıcıyı proje detay sayfasına geri yönlendir
header("Location: ../project-detail.php?id=" . $project_id . "&status=winner_picked");
exit;

?>