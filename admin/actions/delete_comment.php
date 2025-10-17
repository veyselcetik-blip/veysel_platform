<?php
require_once '../../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
    if ($comment_id) {
        $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
    }
}
header("Location: ../comments.php");
exit;
?>