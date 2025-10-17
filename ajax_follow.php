<?php
require 'includes/init.php';

// Sadece giriş yapmış kullanıcılar işlem yapabilir ve sadece POST isteği ile
if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit;
}

$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$project_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz Proje ID.']);
    exit;
}

// Kullanıcı bu projeyi zaten takip ediyor mu?
$stmt_check = $db->prepare("SELECT id FROM followed_projects WHERE user_id = ? AND project_id = ?");
$stmt_check->execute([$user_id, $project_id]);
$is_following = $stmt_check->fetch();

header('Content-Type: application/json');

if ($is_following) {
    // Takip ediyorsa, takibi bırak
    $stmt_unfollow = $db->prepare("DELETE FROM followed_projects WHERE id = ?");
    $stmt_unfollow->execute([$is_following['id']]);
    echo json_encode(['status' => 'success', 'action' => 'unfollowed']);
} else {
    // Takip etmiyorsa, takibe al
    $stmt_follow = $db->prepare("INSERT INTO followed_projects (user_id, project_id) VALUES (?, ?)");
    $stmt_follow->execute([$user_id, $project_id]);
    echo json_encode(['status' => 'success', 'action' => 'followed']);
}
exit;
?>