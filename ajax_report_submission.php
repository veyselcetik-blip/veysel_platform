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

// DÜZELTME: INSERT sorgusuna 'status' alanı eklendi.
$stmt_report = $db->prepare("INSERT INTO reports (submission_id, reporter_id, reason, status) VALUES (?, ?, ?, 'beklemede')");
$stmt_report->execute([$submission_id, $reporter_id, $full_reason]);


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

   $stmt_info = $db->prepare(
        "SELECT s.user_id, s.entry_number, s.file_path, p.title AS project_title, p.id AS project_id
         FROM submissions s
         JOIN projects p ON s.project_id = p.id
         WHERE s.id = ?"
    );
    $stmt_info->execute([$submission_id]);
    $info = $stmt_info->fetch();


    $notification_message = "
        <p><b>'" . htmlspecialchars($info['project_title']) . "'</b> projesindeki <b>#" . $info['entry_number'] . "</b> numaralı sunumunuz hakkında bir şikayet alındı.</p>
        
        <div style='margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; text-align: center;'>
            <p style='margin-top: 0; font-weight: bold;'>Şikayet Edilen Sunum:</p>
            <img src='" . htmlspecialchars($info['file_path']) . "' style='max-width: 150px; max-height: 150px; border: 1px solid #ccc; border-radius: 4px;'>
        </div>

        <p><b>Şikayet Sebebi:</b> " . htmlspecialchars($reason_type) . "</p>
        <p style='font-weight: bold; color: #c0392b;'>Lütfen Dikkat:</p>
        <p>Platformumuzda çalıntı, kopya veya stok içeriklere izin verilmemektedir. Sizden ricamız, bu sunumu projenizden kaldırarak yerine <b>tamamen size ait özgün bir çalışmayı</b> yüklemenizdir.</p>
        <p>Anlayışınız için teşekkür ederiz.</p>
    ";

    // Bildirim linki, kullanıcının direkt projeye gitmesini sağlar
    $notification_link = "project-detail.php?id=" . $info['project_id'];

    $stmt_notify = $db->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
    $stmt_notify->execute([$submission_owner_id, $notification_message, $notification_link]);
}

echo json_encode(['status' => 'success', 'message' => 'Şikayetiniz başarıyla yönetime iletildi. Gösterdiğiniz hassasiyet için teşekkür ederiz.']);
exit;
?>