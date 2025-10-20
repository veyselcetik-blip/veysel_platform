<?php
require_once '../../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
    $report_id = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT); // Bu değer sadece şikayet sayfasından gelir.

    if ($submission_id) {
        // GÜNCELLEME: Silmeden önce fiziksel dosyayı da sunucudan kaldıralım.
        $stmt_path = $db->prepare("SELECT file_path FROM submissions WHERE id = ?");
        $stmt_path->execute([$submission_id]);
        $file_path = $stmt_path->fetchColumn();

        if ($file_path && file_exists('../../' . $file_path)) {
            unlink('../../' . $file_path);
        }
        
        // Veritabanından kaydı sil
        $stmt_del = $db->prepare("DELETE FROM submissions WHERE id = ?");
        $stmt_del->execute([$submission_id]);
    }
    
    // Eğer bu işlem şikayet sayfasından başlatıldıysa, ilgili raporu da kapat.
    if ($report_id) {
        $stmt_rep = $db->prepare("UPDATE reports SET status = 'çözüldü' WHERE id = ?");
        $stmt_rep->execute([$report_id]);
    }
}

// === ÇÖZÜM BURADA: Kullanıcıyı geldiği sayfaya geri yönlendir. ===
$redirect_url = $_SERVER['HTTP_REFERER'] ?? '../submissions.php'; // Eğer referans sayfa bilgisi yoksa varsayılan olarak sunumlar sayfasına git.
header("Location: " . $redirect_url);
exit;
?>