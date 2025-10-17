<?php
require 'includes/init.php'; // Bu dosya functions.php'yi zaten içeriyor
header('Content-Type: application/json');

if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit(json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']));
}

$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$comment_text = trim($_POST['comment']);
$user_id = $_SESSION['user_id'];

if (!$project_id || empty($comment_text)) {
    exit(json_encode(['status' => 'error', 'message' => 'Eksik bilgi.']));
}

$stmt = $db->prepare("INSERT INTO comments (project_id, user_id, comment) VALUES (?, ?, ?)");
$stmt->execute([$project_id, $user_id, $comment_text]);
$new_comment_id = $db->lastInsertId();

// ================== DEĞİŞİKLİK BURADA ==================
// Ham metni göndermek yerine, linke dönüştürülmüş halini gönderiyoruz.
$linked_comment_html = linkify_submission_tags(htmlspecialchars($comment_text), $project_id);
// =======================================================

// Yeni yorumun bilgilerini geri döndür
$response = [
    'status' => 'success',
    'comment' => [
        'id' => $new_comment_id,
        'username' => $_SESSION['username'],
        'comment_html' => $linked_comment_html, // Artık 'comment_html' olarak gönderiyoruz
        'created_at' => date('d M Y, H:i')
    ]
];

echo json_encode($response);
exit;
?>