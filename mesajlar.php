<?php
require 'includes/init.php';
require_login();

$current_user_id = $_SESSION['user_id'];
$other_user_id = null;
$other_user = null;

// URL'den belirli bir kullanıcıyla konuşma başlatma veya devam etme
if (isset($_GET['user'])) {
    $other_user_id = (int)$_GET['user'];
} elseif (isset($_GET['yeni'])) { // Profil sayfasından yeni mesaj için gelindiyse
    $username_to_start = $_GET['yeni'];
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username_to_start]);
    $user_to_start = $stmt->fetch();
    if ($user_to_start) {
        $other_user_id = $user_to_start['id'];
    }
}

// 1. Sol Panel: Konuşma Listesini Çekme (Daha Önce Düzeltilmiş Hali)
$sql = "
    SELECT
        u.id as user_id,
        u.username,
        m.body as last_message,
        m.created_at as last_message_date,
        (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM messages m
    JOIN users u ON u.id = IF(m.sender_id = ?, m.receiver_id, m.sender_id)
    WHERE m.id IN (
        SELECT MAX(id)
        FROM messages
        WHERE sender_id = ? OR receiver_id = ?
        GROUP BY IF(sender_id = ?, receiver_id, sender_id)
    )
    ORDER BY m.created_at DESC
";
$conversations_stmt = $db->prepare($sql);
$params = [
    $current_user_id,
    $current_user_id,
    $current_user_id,
    $current_user_id,
    $current_user_id
];
$conversations_stmt->execute($params);
$conversations = $conversations_stmt->fetchAll();


// 2. Sağ Panel: Eğer bir konuşma seçildiyse mesajları çekme
$messages = [];
if ($other_user_id && $other_user_id != $current_user_id) {
    // Diğer kullanıcının bilgilerini al
    $user_stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
    $user_stmt->execute([$other_user_id]);
    $other_user = $user_stmt->fetch();

    if ($other_user) {
        // Form gönderildiyse yeni mesajı kaydet
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_body'])) {
            $message_body = trim($_POST['message_body']);
            if (!empty($message_body)) {
                $insert_stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, body, is_read) VALUES (?, ?, ?, 0)");
                $insert_stmt->execute([$current_user_id, $other_user_id, $message_body]);
                header("Location: mesajlar.php?user=" . $other_user_id);
                exit;
            }
        }

        // Seçili konuşmadaki okunmamış mesajları "okundu" olarak işaretle
        $update_read_stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $update_read_stmt->execute([$other_user_id, $current_user_id]);

        // === YENİ DÜZELTME BAŞLANGICI: Sorgu ve parametreler güncellendi ===
        $messages_stmt = $db->prepare("
            SELECT * FROM messages
            WHERE (sender_id = ? AND receiver_id = ?)
               OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at ASC
        ");
        $messages_stmt->execute([$current_user_id, $other_user_id, $other_user_id, $current_user_id]);
        $messages = $messages_stmt->fetchAll();
        // === YENİ DÜZELTME SONU ===
    }
}

include 'includes/header.php';
?>
<?php include 'includes/navbar.php'; ?>

<div class="container page-container">
    <div class="messaging-container">
        <div class="conversation-list">
            <div class="conversation-header">
                <h3>Görüşmeler</h3>
            </div>
            <?php if (empty($conversations)): ?>
                <p style="padding: 1rem;">Henüz bir görüşmeniz yok.</p>
            <?php else: ?>
                <?php foreach ($conversations as $convo): ?>
                    <a href="mesajlar.php?user=<?= $convo['user_id'] ?>" class="conversation-item <?= ($other_user_id == $convo['user_id']) ? 'active' : '' ?>">
                        <img src="<?= get_user_avatar($convo['user_id'], $convo['username']) ?>" alt="<?= htmlspecialchars($convo['username']) ?>">
                        <div class="convo-info">
                            <div class="convo-header">
                                <strong><?= htmlspecialchars($convo['username']) ?></strong>
                                <small><?= date('H:i', strtotime($convo['last_message_date'])) ?></small>
                            </div>
                            <p><?= htmlspecialchars(substr($convo['last_message'], 0, 30)) ?>...</p>
                        </div>
                        <?php if ($convo['unread_count'] > 0): ?>
                            <span class="unread-badge"><?= $convo['unread_count'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="message-view">
            <?php if ($other_user): ?>
                <div class="message-header">
                    <h3><?= htmlspecialchars($other_user['username']) ?></h3>
                    <a href="profile.php?username=<?= htmlspecialchars($other_user['username']) ?>" class="view-profile-btn">Profili Gör</a>
                </div>
                <div class="message-list">
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-bubble <?= ($msg['sender_id'] == $current_user_id) ? 'sent' : 'received' ?>">
                            <p><?= nl2br(htmlspecialchars($msg['body'])) ?></p>
                            <span class="message-time"><?= date('d M, H:i', strtotime($msg['created_at'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="message-reply-form">
                    <form method="POST" action="mesajlar.php?user=<?= $other_user_id ?>">
                        <textarea name="message_body" placeholder="Mesajınızı yazın..." required></textarea>
                        <button type="submit" class="btn btn-primary">Gönder <i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            <?php else: ?>
                <div class="no-conversation-selected">
                    <i class="fas fa-comments"></i>
                    <h3>Görüşme Seçilmedi</h3>
                    <p>Mesajları görüntülemek için sol taraftan bir görüşme seçin veya bir kullanıcının profilinden yeni bir görüşme başlatın.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>