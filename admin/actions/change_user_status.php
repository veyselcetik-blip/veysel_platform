<?php
require_once '../../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $new_status = $_POST['new_status'];

    // Adminin kendi durumunu değiştirerek kendini kilitlemesini engelle
    if ($user_id && in_array($new_status, ['aktif', 'askıda']) && $user_id != $_SESSION['user_id']) {
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $user_id]);
    }
}
// Kullanıcıyı, geldiği filtre ayarlarıyla birlikte geri yönlendir
$redirect_url = $_SERVER['HTTP_REFERER'] ?? '../users.php';
header("Location: " . $redirect_url);
exit;
?>