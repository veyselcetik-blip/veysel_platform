<?php
require_once '../../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
    if ($report_id) {
        $stmt = $db->prepare("UPDATE reports SET status = 'çözüldü' WHERE id = ?");
        $stmt->execute([$report_id]);
    }
}
header("Location: ../reports.php");
exit;
?>