<?php
require 'includes/init.php';
header('Content-Type: application/json');

if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']));
}

$submission_id = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
$reason_type = trim($_POST['reason_type']);
$reason_link = trim($_POST['reason_link'] ?? '');
$reason_details = trim($_POST['reason_details'] ?? '');
$reporter_id = $_SESSION['user_id'];

if (!$submission_id || empty($reason_type)) {
    exit(json_encode(['status' => 'error', 'message' => 'Lütfen bir şikayet sebebi seçin.']));
}

// 1. Şikayeti `reports` tablosuna kaydet
$full_reason = "Sebep: " . $reason_type . "\n";
if (!empty($reason_link)) { $full_reason .= "Benzer Tasarım Linki: " . $reason_link . "\n"; }
if (!empty($reason_details)) { $full_reason .= "Ek Detaylar: " . $reason_details; }

$stmt_report = $db->prepare("INSERT INTO reports (submission_id, reporter_id, reason) VALUES (?, ?, ?)");
$stmt_report->execute([$submission_id, $reporter_id, $full_reason]);

// 2. Sunumun sahibini ve proje bilgilerini bul
$stmt_info = $db->prepare(
    "SELECT s.user_id, s.entry_number, p.title AS project_title 
     FROM submissions s
     JOIN projects p ON s.project_id = p.id
     WHERE s.id = ?"
);
$stmt_info->execute([$submission_id]);
$info = $stmt_info->fetch();

if ($info) {
    $submission_owner_id = $info['user_id'];
    
    // Güvenlik: Kullanıcı kendi sunumunu şikayet edemez
    if ($reporter_id == $submission_owner_id) {
        exit(json_encode(['status' => 'error', 'message' => 'Kendi sunumunuzu şikayet edemezsiniz.']));
    }

    // 3. Sunum sahibine ANONİM bir bildirim oluştur
    $notification_message = "<b>" . htmlspecialchars($info['project_title']) . "</b> projesindeki <b>#" . $info['entry_number'] . "</b> numaralı sunumunuz hakkında bir şikayet alındı. Sebep: <b>" . htmlspecialchars($reason_type) . "</b>. Yönetimimiz en kısa sürede inceleyecektir.";
    $notification_link = "project-detail.php?id=" . $db->query("SELECT project_id FROM submissions WHERE id=$submission_id")->fetchColumn();

    $stmt_notify = $db->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $stmt_notify->execute([$submission_owner_id, $notification_message, $notification_link]);
}

echo json_encode(['status' => 'success', 'message' => 'Şikayetiniz başarıyla yönetime iletildi. Gösterdiğiniz hassasiyet için teşekkür ederiz.']);
exit;
?>