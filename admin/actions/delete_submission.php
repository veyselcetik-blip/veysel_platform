<?php
require_once '../../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submission_id = filter_input(INPUT_POST, 'submission_id', FILTER_VALIDATE_INT);
    $report_id = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);

    if ($submission_id) {
        // Sunumu sil (dosyayı silmek daha sonra eklenebilir)
        $stmt_del = $db->prepare("DELETE FROM submissions WHERE id = ?");
        $stmt_del->execute([$submission_id]);
    }
    if ($report_id) {
        // Şikayeti çözüldü olarak işaretle
        $stmt_rep = $db->prepare("UPDATE reports SET status = 'çözüldü' WHERE id = ?");
        $stmt_rep->execute([$report_id]);
    }
}
header("Location: ../reports.php");
exit;
?>