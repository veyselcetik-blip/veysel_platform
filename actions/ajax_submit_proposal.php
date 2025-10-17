<?php
require '../includes/init.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Bilinmeyen bir hata oluştu.'];

if (!is_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Geçersiz istek veya yetki.';
    echo json_encode($response);
    exit;
}

$project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
$filepath = $_POST['uploaded_filepath'] ?? '';
$description = trim($_POST['description'] ?? '');
$current_user_id = $_SESSION['user_id'];

if (!$project_id || empty($filepath)) {
    $response['message'] = 'Proje ID veya dosya yolu eksik.';
} elseif (strpos($filepath, 'uploads/submissions/') !== 0) {
    $response['message'] = 'Güvenlik hatası: Geçersiz dosya yolu.';
} else {
    try {
        $stmt_entry = $db->prepare("SELECT MAX(entry_number) FROM submissions WHERE project_id = :project_id");
        $stmt_entry->execute([':project_id' => $project_id]);
        $new_entry_number = ($stmt_entry->fetchColumn() ?: 0) + 1;

        $stmt_insert = $db->prepare("INSERT INTO submissions (project_id, user_id, file_path, description, entry_number, created_at) VALUES (:project_id, :user_id, :file_path, :description, :entry_number, NOW())");
        $stmt_insert->execute([
            ':project_id' => $project_id,
            ':user_id' => $current_user_id,
            ':file_path' => $filepath,
            ':description' => $description,
            ':entry_number' => $new_entry_number
        ]);
        
        $response['status'] = 'success';
        $response['message'] = 'Sunumunuz başarıyla veritabanına eklendi!';
    } catch (PDOException $e) {
        // Gerçek bir projede hatayı loglamak daha iyidir.
        // error_log('Veritabanı Hatası: ' . $e->getMessage());
        $response['message'] = 'Veritabanına kaydedilirken bir sorun oluştu.';
    }
}

echo json_encode($response);
exit;