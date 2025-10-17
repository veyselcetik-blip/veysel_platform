<?php
require '../includes/init.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Bilinmeyen bir hata oluştu.'];

if (!is_logged_in()) {
    $response['message'] = 'Dosya yüklemek için giriş yapmalısınız.';
    echo json_encode($response);
    exit;
}

if (isset($_FILES['design_file']) && $_FILES['design_file']['error'] == 0) {
    $file = $_FILES['design_file'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file['type'], $allowed_types)) {
        $response['message'] = 'Geçersiz dosya türü. Sadece JPG, PNG, GIF kabul edilir.';
    } elseif ($file['size'] > $max_size) {
        $response['message'] = 'Dosya boyutu çok büyük (Maksimum 5MB).';
    } else {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'submission_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
        $upload_dir = '../uploads/submissions/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $upload_path = $upload_dir . $new_filename;
        $db_path = 'uploads/submissions/' . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $response['status'] = 'success';
            $response['message'] = 'Dosya başarıyla yüklendi.';
            $response['filepath'] = $db_path;
        } else {
            $response['message'] = 'Dosya sunucuya taşınamadı. Klasör izinlerini kontrol edin.';
        }
    }
} else {
    $response['message'] = 'Dosya yüklenmedi veya bir hata oluştu. Hata Kodu: ' . ($_FILES['design_file']['error'] ?? 'N/A');
}

echo json_encode($response);
exit;