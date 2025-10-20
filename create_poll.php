<?php
require 'includes/init.php';
require_login();

$project_id = filter_input(INPUT_GET, 'project_id', FILTER_VALIDATE_INT);
if (!$project_id) {
    header("Location: dashboard.php");
    exit;
}

// Proje bilgilerini ve proje sahibinin mevcut kullanıcı olup olmadığını kontrol et
$stmt = $db->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
$stmt->execute([$project_id, $_SESSION['user_id']]);
$project = $stmt->fetch();

if (!$project || $project['winner_id']) {
    // Proje bulunamadıysa, kullanıcıya ait değilse veya zaten bir kazananı varsa anket oluşturulamaz.
    die('Anket oluşturmak için yetkiniz yok veya bu proje zaten sonuçlanmış.');
}

// Projenin tüm sunumlarını çek
$submissions_stmt = $db->prepare("SELECT id, file_path, entry_number FROM submissions WHERE project_id = ? ORDER BY entry_number ASC");
$submissions_stmt->execute([$project_id]);
$submissions = $submissions_stmt->fetchAll();

if (count($submissions) < 2) {
    die('Anket oluşturabilmek için en az 2 sunum olmalıdır.');
}

// Form gönderildiyse...
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_ids'])) {
    $selected_ids = $_POST['submission_ids'];

    if (count($selected_ids) < 2) {
        $error = "Lütfen anket için en az 2 tasarım seçin.";
    } else {
        // Yeni bir anket oluştur
        $token = bin2hex(random_bytes(16));
        $insert_poll_stmt = $db->prepare("INSERT INTO polls (project_id, user_id, token) VALUES (?, ?, ?)");
        $insert_poll_stmt->execute([$project_id, $_SESSION['user_id'], $token]);
        $poll_id = $db->lastInsertId();

        // Seçilen tasarımları ankete ekle
        $insert_item_stmt = $db->prepare("INSERT INTO poll_items (poll_id, submission_id) VALUES (?, ?)");
        foreach ($selected_ids as $submission_id) {
            $insert_item_stmt->execute([$poll_id, $submission_id]);
        }

        // Kullanıcıyı oluşturulan anket sayfasına yönlendir
        header("Location: poll.php?token=" . $token);
        exit;
    }
}


include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container page-container">
    <div class="form-wrapper">
        <div class="form-container" style="max-width: 900px;">
            <h2 class="section-title">Anket Oluştur</h2>
            <p style="text-align: center; margin-bottom: 2rem;">"<?= htmlspecialchars($project['title']) ?>" projeniz için favori tasarımlarınızı seçerek bir anket oluşturun ve çevrenizden geri bildirim toplayın.</p>

            <?php if (isset($error)): ?>
                <p class="form-message error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Ankete Dahil Etmek İstediğiniz Tasarımları Seçin (En az 2 tane)</label>
                </div>
                <div class="poll-submission-selector">
                    <?php foreach ($submissions as $sub): ?>
                        <label class="poll-submission-item">
                            <input type="checkbox" name="submission_ids[]" value="<?= $sub['id'] ?>">
                            <div class="submission-card-v2">
                                <div class="submission-entry-number">#<?= $sub['entry_number'] ?></div>
                                <div class="submission-image-link">
                                    <img src="<?= htmlspecialchars($sub['file_path']) ?>" alt="Sunum #<?= $sub['entry_number'] ?>">
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-group" style="text-align: center; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary btn-lg">Anketi Oluştur ve Paylaş</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>