<?php
require_once '../../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
    if ($project_id) {
        // Bir projeyi silerken, ona bağlı olan her şeyi de silmek önemlidir.
        $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$project_id]);
        $db->prepare("DELETE FROM submissions WHERE project_id = ?")->execute([$project_id]);
        $db->prepare("DELETE FROM comments WHERE project_id = ?")->execute([$project_id]);
        // Puanlar ve şikayetler gibi diğer ilişkili tablolar da buradan silinebilir.
    }
}
header("Location: ../projects.php");
exit;
?>