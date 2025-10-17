<?php
require 'includes/init.php';
require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    // YENİ: Güvenli dosya yükleme fonksiyonunu kullan
    $upload_result = secure_file_upload($_FILES['file']);

    if (isset($upload_result['success'])) {
        $target_file = $upload_result['filepath'];
        $stmt = $db->prepare("INSERT INTO portfolio_items (user_id, title, description, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title, $description, $target_file]);
        header("Location: profile.php");
        exit;
    } else {
        $error = $upload_result['error'];
    }
}

include 'header.php';
?>