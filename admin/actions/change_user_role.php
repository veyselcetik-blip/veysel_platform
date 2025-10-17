<?php
require_once '../../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $new_role = $_POST['new_role'];

    // Güvenlik: Adminin kendi rolünü değiştirerek kendini kilitlemesini engelle
    if ($user_id && in_array($new_role, ['user', 'admin']) && $user_id != $_SESSION['user_id']) {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);
    }
}

$redirect_url = $_SERVER['HTTP_REFERER'] ?? '../users.php';
header("Location: " . $redirect_url);
exit;
?>