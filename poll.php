<?php
require 'includes/init.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    die('Geçersiz anket linki.');
}

// Anket ve proje bilgilerini çek
$poll_stmt = $db->prepare("SELECT p.*, pr.title as project_title, pr.id as project_id FROM polls p JOIN projects pr ON p.project_id = pr.id WHERE p.token = ?");
$poll_stmt->execute([$token]);
$poll = $poll_stmt->fetch();

if (!$poll) {
    die('Anket bulunamadı.');
}

// Ziyaretçinin IP adresi ile daha önce oy kullanıp kullanmadığını kontrol et
$ip_address = $_SERVER['REMOTE_ADDR'];
$vote_check_stmt = $db->prepare("SELECT id FROM poll_votes WHERE poll_id = ? AND ip_address = ?");
$vote_check_stmt->execute([$poll['id'], $ip_address]);
$has_voted = $vote_check_stmt->fetch();

// Eğer oy kullanma formu gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_submission_id']) && !$has_voted) {
    $submission_id_to_vote = filter_input(INPUT_POST, 'vote_submission_id', FILTER_VALIDATE_INT);

    // Oyu veritabanına kaydet
    $vote_stmt = $db->prepare("INSERT INTO poll_votes (poll_id, submission_id, ip_address) VALUES (?, ?, ?)");
    $vote_stmt->execute([$poll['id'], $submission_id_to_vote, $ip_address]);

    // Sayfayı yenileyerek sonucu göster ve çifte gönderimi engelle
    header("Location: poll.php?token=" . $token);
    exit;
}

// Anketteki tasarımları ve oy sayılarını çek
$items_stmt = $db->prepare("
    SELECT s.id, s.file_path, s.entry_number, COUNT(v.id) as vote_count
    FROM poll_items pi
    JOIN submissions s ON pi.submission_id = s.id
    LEFT JOIN poll_votes v ON s.id = v.submission_id AND v.poll_id = pi.poll_id
    WHERE pi.poll_id = ?
    GROUP BY s.id
    ORDER BY s.entry_number ASC
");
$items_stmt->execute([$poll['id']]);
$poll_items = $items_stmt->fetchAll();

$total_votes = array_sum(array_column($poll_items, 'vote_count'));

include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container page-container">
    <div class="poll-container">
        <div class="poll-header">
            <h1>Oylama: "<?= htmlspecialchars($poll['project_title']) ?>"</h1>
            <p>Aşağıdaki tasarımları inceleyin ve favorinizi seçerek proje sahibine yardımcı olun.</p>
            <?php if ($has_voted): ?>
                <div class="poll-message success">Bu anket için zaten oy kullandınız. Teşekkür ederiz!</div>
            <?php endif; ?>
        </div>
        
        <form method="POST" class="poll-grid">
            <?php foreach ($poll_items as $item): ?>
                <div class="poll-item">
                    <label>
                        <div class="submission-card-v2">
                            <div class="submission-entry-number">#<?= $item['entry_number'] ?></div>
                            <div class="submission-image-link">
                                <img src="<?= htmlspecialchars($item['file_path']) ?>" alt="Sunum #<?= $item['entry_number'] ?>">
                            </div>
                        </div>
                        <div class="poll-vote-action">
                            <input type="radio" name="vote_submission_id" value="<?= $item['id'] ?>" <?= $has_voted ? 'disabled' : '' ?> required>
                            <span>Bu Tasarımı Seç</span>
                        </div>
                    </label>
                    <?php if ($total_votes > 0): 
                        $percentage = round(($item['vote_count'] / $total_votes) * 100);
                    ?>
                    <div class="poll-result-bar">
                        <div class="bar-fill" style="width: <?= $percentage ?>%;"></div>
                        <span class="bar-label"><?= $item['vote_count'] ?> Oy (%<?= $percentage ?>)</span>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <?php if (!$has_voted): ?>
            <div class="poll-submit-area">
                <button type="submit" class="btn btn-primary btn-lg">Oyumu Gönder</button>
            </div>
            <?php endif; ?>
        </form>

         <div class="poll-share-box">
            <h3>Anketi Paylaş</h3>
            <p>Bu linki kopyalayarak başkalarından da oy isteyebilirsiniz.</p>
            <input type="text" value="<?= "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>" readonly onclick="this.select()">
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>