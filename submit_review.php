<?php
require 'includes/init.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Güvenlik: CSRF token kontrolü
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    die('Geçersiz CSRF token!');
}

$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$designer_id = filter_input(INPUT_POST, 'designer_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$review_text = trim($_POST['review_text'] ?? '');
$reviewer_id = $_SESSION['user_id']; // Değerlendirmeyi yapan kişi (proje sahibi)

// Doğrulama ve Yetkilendirme
if (!$project_id || !$designer_id || !$rating || $rating < 1 || $rating > 5) {
    die('Eksik veya geçersiz bilgi.');
}

// Proje bilgilerini çek ve bu işlemi yapmaya yetkili mi diye kontrol et
$stmt = $db->prepare("SELECT user_id, winner_id FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

// 1. Proje var mı? 
// 2. Bu işlemi yapan kişi projenin sahibi mi? 
// 3. Değerlendirilen tasarımcı, projenin kazananı mı?
if (!$project || $project['user_id'] != $reviewer_id || $project['winner_id'] != $designer_id) {
    die('Bu işlemi yapmaya yetkiniz yok.');
}

// Bu proje için daha önce yorum yapılmış mı? (Çifte gönderimi engelle)
$check_stmt = $db->prepare("SELECT id FROM reviews WHERE project_id = ? AND reviewer_id = ?");
$check_stmt->execute([$project_id, $reviewer_id]);
if ($check_stmt->fetch()) {
    die('Bu proje için zaten bir değerlendirme yaptınız.');
}

// Her şey yolundaysa, değerlendirmeyi veritabanına kaydet
$insert_stmt = $db->prepare("INSERT INTO reviews (project_id, reviewer_id, designer_id, rating, review_text) VALUES (?, ?, ?, ?, ?)");
$insert_stmt->execute([$project_id, $reviewer_id, $designer_id, $rating, $review_text]);

// Başarılı bir şekilde kaydedildikten sonra kullanıcıyı proje sayfasına geri yönlendir.
header("Location: project-detail.php?id=" . $project_id . "&review=success");
exit;
?>