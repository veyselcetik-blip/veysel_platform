<?php
// project-detail.php - Tüm özellikler eklenmiş ve hataları giderilmiş nihai sürüm

require 'includes/init.php'; // $db, session, helper fonksiyonları

// --- 1) Girdi ve Proje Yükleme ---
$project_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($project_id === 0) {
    header('Location: browse-projects.php');
    exit;
}

$stmt = $db->prepare('SELECT p.*, u.username FROM projects p JOIN users u ON p.user_id = u.id WHERE p.id = ?');
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) {
    header('Location: browse-projects.php?error=notfound');
    exit;
}

// --- 2) Durum Güncelleme (deadline geçtiyse değerlendirme) ---
if (($project['status'] ?? '') === 'aktif' && !empty($project['deadline'])) {
    try {
        $deadline_dt = new DateTime($project['deadline'] . ' 23:59:59');
        $now = new DateTime();
        if ($now > $deadline_dt) {
            $update_stmt = $db->prepare("UPDATE projects SET status = 'değerlendirme' WHERE id = ?");
            $update_stmt->execute([$project_id]);
            $project['status'] = 'değerlendirme';
        }
    } catch (Exception $e) {
        error_log('Invalid date format for deadline in project ID: ' . $project_id);
    }
}

// --- 3) Rol & Görünürlük Değişkenleri ---
$current_user_id = $_SESSION['user_id'] ?? 0;
$is_owner   = (is_logged_in() && $current_user_id == ($project['user_id'] ?? 0));
$is_winner  = (is_logged_in() && !empty($project['winner_id']) && $current_user_id == $project['winner_id']);
$can_view_submissions = (($project['is_public'] ?? 0) == 1) || $is_owner;
$winning_submission_id = (int)($project['winning_submission_id'] ?? 0);

// --- 4) KULLANICININ KENDİ SUNUMLARINI ÇEKME ---
$my_submissions = [];
if (is_logged_in()) {
    $my_submissions_stmt = $db->prepare(
        "SELECT id, file_path, entry_number, description FROM submissions WHERE project_id = ? AND user_id = ? ORDER BY entry_number ASC"
    );
    $my_submissions_stmt->execute([$project_id, $current_user_id]);
    $my_submissions = $my_submissions_stmt->fetchAll();
}

// --- 5) Proje değerlendirme (review) durumu ---
$has_review = false;
if (($project['status'] ?? '') === 'tamamlandı' && $is_owner) {
    $review_check_stmt = $db->prepare('SELECT id FROM reviews WHERE project_id = ? AND reviewer_id = ?');
    $review_check_stmt->execute([$project_id, $current_user_id]);
    if ($review_check_stmt->fetch()) { $has_review = true; }
}

// --- 6) İstatistikler ---
$total_submissions = (int)$db->query("SELECT COUNT(*) FROM submissions WHERE project_id = {$project_id}")->fetchColumn();

// --- 7) Sunumlar (kazananı başa al, sonra en yeniye göre sırala) ---
$submissions = [];
if ($can_view_submissions) {
    $sql = "SELECT s.*, u.username AS designer_name
            FROM submissions s
            JOIN users u ON s.user_id = u.id
            WHERE s.project_id = :project_id
            ORDER BY s.id = :winning_submission_id DESC, s.created_at DESC";

    $sub_stmt = $db->prepare($sql);
    $sub_stmt->execute([
        ':project_id' => $project_id,
        ':winning_submission_id' => $winning_submission_id
    ]);
    $submissions = $sub_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- 8) Yorumlar ---
$comments_stmt = $db->prepare('SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.project_id = ? ORDER BY c.created_at DESC');
$comments_stmt->execute([$project_id]);
$comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
include 'includes/navbar.php';
?>

<main class="container page-container">

    <?php if (($project['status'] ?? '') === 'tamamlandı' && $is_owner && !$has_review && !empty($project['winner_id'])):
        $winner_username_stmt = $db->prepare('SELECT username FROM users WHERE id = ?');
        $winner_username_stmt->execute([$project['winner_id']]);
        $winner_username = $winner_username_stmt->fetchColumn();
    ?>
        <div class="review-form-container">
            <h3>Değerlendirme Zamanı!</h3>
            <p>Proje tamamlandı. Lütfen kazanan tasarımcı <strong><?= htmlspecialchars($winner_username ?: 'Bilinmeyen Tasarımcı') ?></strong> ile olan deneyiminizi değerlendirin.</p>
            <form action="submit_review.php" method="POST" id="review-form">
                <input type="hidden" name="project_id" value="<?= (int)$project_id ?>"><input type="hidden" name="designer_id" value="<?= (int)$project['winner_id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <div class="form-group"><label>Puanınız</label><div class="star-rating"><input type="radio" id="5-stars" name="rating" value="5" required/><label for="5-stars" class="star">&#9733;</label><input type="radio" id="4-stars" name="rating" value="4" /><label for="4-stars" class="star">&#9733;</label><input type="radio" id="3-stars" name="rating" value="3" /><label for="3-stars" class="star">&#9733;</label><input type="radio" id="2-stars" name="rating" value="2" /><label for="2-stars" class="star">&#9733;</label><input type="radio" id="1-star"  name="rating" value="1" /><label for="1-star"  class="star">&#9733;</label></div></div>
                <div class="form-group"><label for="review_text">Yorumunuz (İsteğe Bağlı)</label><textarea name="review_text" id="review_text" rows="4" placeholder="Tasarımcının iletişiminden, yaratıcılığından ve teslimat hızından bahseden kısa bir yorum bırakabilirsiniz."></textarea></div>
                <button type="submit" class="btn btn-primary">Değerlendirmeyi Gönder</button>
            </form>
        </div>
    <?php elseif ($has_review): ?>
        <div class="review-form-container success"><p><i class="fas fa-check-circle"></i> Bu proje için değerlendirmenizi zaten yaptınız. Teşekkür ederiz!</p></div>
    <?php endif; ?>

    <?php if ($is_winner && ($project['status'] ?? '') === 'kazanan_seçildi'): ?>
        <div class="upload-final-box"><h3>Tebrikler, bu projeyi kazandınız!</h3><p>Lütfen projenin final dosyalarını (.zip formatında) yükleyerek teslimat sürecini tamamlayın.</p><a href="upload-final.php?project_id=<?= (int)$project_id ?>" class="btn btn-success btn-lg">Final Dosyalarını Yükle</a></div>
    <?php elseif ($is_owner && !empty($project['final_file'])): ?>
        <div class="upload-final-box"><h3>Teslimat Tamamlandı!</h3><p>Kazanan tasarımcı final dosyalarını yükledi. Aşağıdaki butondan indirebilirsiniz.</p><a href="<?= htmlspecialchars($project['final_file']) ?>" class="btn btn-success btn-lg" download>Final Dosyalarını İndir</a></div>
    <?php elseif ($is_owner && empty($project['final_file']) && in_array(($project['status'] ?? ''), ['kazanan_seçildi','tamamlandı'])): ?>
        <div class="upload-final-box info"><h3>Kazanan Seçildi</h3><p>Tasarımcıdan final dosyalarını yüklemesi bekleniyor.</p></div>
    <?php endif; ?>

    <div class="project-header-card"><div class="header-main"><span class="project-category"><?= htmlspecialchars($project['category'] ?? '') ?></span><h1><?= htmlspecialchars($project['title'] ?? '') ?></h1></div><div class="header-details"><div><i class="fas fa-user-tie"></i> <span>Müşteri</span><strong><a href="profile.php?username=<?= htmlspecialchars($project['username'] ?? '') ?>"><?= htmlspecialchars($project['username'] ?? '') ?></a></strong></div><div><i class="fas fa-calendar-alt"></i> <span>Başlangıç</span><strong><?= !empty($project['created_at']) ? date('d M Y', strtotime($project['created_at'])) : '-' ?></strong></div><div><i class="fas fa-calendar-check"></i> <span>Bitiş Tarihi</span><strong><?= !empty($project['deadline']) ? date('d M Y', strtotime($project['deadline'])) : 'Belirtilmemiş' ?></strong></div><div><i class="fas fa-trophy"></i> <span>Ödül</span><strong><?= htmlspecialchars($project['budget'] ?? '') ?></strong></div></div></div>
    
    <div class="project-status-panel"><div class="status-left"><?php if (($project['status'] ?? '') === 'aktif' && !empty($project['deadline'])): ?><?php try { $deadline_dt_for_js = new DateTime($project['deadline'] . ' 23:59:59'); } catch (Exception $e) { $deadline_dt_for_js = new DateTime(); } ?><div id="countdown-timer" data-deadline="<?= $deadline_dt_for_js->format('Y-m-d\TH:i:s') ?>"></div><?php elseif (($project['status'] ?? '') === 'değerlendirme'): ?><div class="status-message evaluation"><i class="fas fa-hourglass-half"></i> Proje yeni sunumlara kapalı. Kazanan bekleniyor.</div><?php else: ?><div class="status-message completed"><i class="fas fa-info-circle"></i> Proje durumu: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $project['status'] ?? ''))) ?></div><?php endif; ?></div><div class="status-right"><?php if (is_logged_in() && !$is_owner && ($project['status'] ?? '') === 'aktif'): ?><button id="openProposalModalBtn" class="btn btn-primary btn-submit-now"><i class="fas fa-paper-plane"></i> Sunum Gönder</button><?php elseif ($is_owner && empty($project['winner_id']) && $total_submissions >= 2): ?><a href="create_poll.php?project_id=<?= (int)$project_id ?>" class="btn btn-secondary btn-submit-now"><i class="fas fa-poll"></i> Anket Oluştur</a><?php endif; ?></div></div>

    <div class="tab-container">
        <div class="tab-buttons">
            <button class="tab-btn active" data-tab="submissions">Sunumlar (<?= (int)$total_submissions ?>)</button>
            <button class="tab-btn" data-tab="summary">Yarışma Özeti</button>
            <button class="tab-btn" data-tab="comments">Yorumlar / S.S.S. (<?= count($comments) ?>)</button>
            <?php if (!empty($my_submissions)): ?>
                <button class="tab-btn" data-tab="my-submissions">Sunumlarım (<?= count($my_submissions) ?>)</button>
            <?php endif; ?>
        </div>

        <div class="tab-content active" id="tab-submissions">
            <?php if ($can_view_submissions): ?>
                <?php if (!empty($submissions)): ?>
                    <div class="submission-grid compact-grid">
                        <?php foreach ($submissions as $submission): ?>
                            <?php $winner_class = ((int)($submission['id'] ?? 0) === $winning_submission_id) ? ' is-winner' : ''; ?>
                            <div class="submission-card-v2<?= $winner_class ?>">
                                <div class="submission-entry-number">#<?= htmlspecialchars($submission['entry_number'] ?? '') ?></div>
                                <a href="<?= htmlspecialchars($submission['file_path'] ?? '') ?>" target="_blank" class="submission-image-link">
                                    <img src="<?= htmlspecialchars($submission['file_path'] ?? 'assets/placeholder.png') ?>" alt="Tasarım Sunumu #<?= htmlspecialchars($submission['entry_number'] ?? '') ?>">
                                </a>
                                <div class="submission-info-bar">
                                    <?php if ($is_owner): ?>
                                        <div class="rating-stars" data-submission-id="<?= (int)($submission['id'] ?? 0) ?>">
                                            <?php for($i=1; $i<=5; $i++): ?>
                                                <i class="<?= ($i <= ($submission['rating'] ?? 0)) ? 'fas' : 'far' ?> fa-star" data-rating="<?= $i ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($is_owner && empty($project['winner_id']) && in_array(($project['status'] ?? ''), ['değerlendirme', 'aktif'])): ?>
                                        <form action="actions/pick_winner.php" method="POST" onsubmit="return confirm('Bu tasarımı kazanan olarak seçmek istediğinizden emin misiniz?');">
                                            <input type="hidden" name="project_id" value="<?= (int)($project['id'] ?? 0) ?>">
                                            <input type="hidden" name="submission_id" value="<?= (int)($submission['id'] ?? 0) ?>">
                                            <input type="hidden" name="winner_id" value="<?= (int)($submission['user_id'] ?? 0) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                            <button type="submit" class="btn-winner"><i class="fas fa-trophy"></i> Seç</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div class="submission-card-footer">
                                    <span>Tasarımcı: <a href="profile.php?username=<?= htmlspecialchars($submission['designer_name'] ?? '') ?>"><?= htmlspecialchars($submission['designer_name'] ?? '') ?></a></span>
                                    <div class="card-actions"><?php if (is_logged_in() && $current_user_id != (int)($submission['user_id'] ?? 0)): ?>
                                        <button class="report-btn" data-submission-id="<?= (int)($submission['id'] ?? 0) ?>"><i class="fas fa-flag"></i></button>
                                    <?php endif; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="submission-placeholder"><p>Bu proje için henüz bir sunum yapılmadı.</p></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="submission-placeholder locked"><i class="fas fa-lock"></i><p>Bu proje gizlidir. Sunumları sadece proje sahibi görebilir.</p></div>
            <?php endif; ?>
        </div>

        <div class="tab-content" id="tab-summary">
            <div class="summary-grid"><div class="summary-main"><div class="summary-card"><h3><i class="fas fa-bullseye"></i> Tasarım Brifi</h3><p class="summary-content"><?= nl2br(htmlspecialchars($project['description'] ?? '')) ?></p></div></div><div class="summary-sidebar"><div class="summary-card"><h3><i class="fas fa-info-circle"></i> Proje Kimliği</h3><ul class="summary-meta-list"><li><i class="fas fa-user-tie"></i><div><span>Müşteri</span><strong><a href="profile.php?username=<?= htmlspecialchars($project['username'] ?? '') ?>"><?= htmlspecialchars($project['username'] ?? '') ?></a></strong></div></li><li><i class="fas fa-tags"></i><div><span>Kategori</span><strong><?= htmlspecialchars($project['category'] ?? '') ?></strong></div></li><li><i class="fas fa-trophy"></i><div><span>Ödül</span><strong><?= htmlspecialchars($project['budget'] ?? '') ?></strong></div></li><li><i class="fas fa-calendar-check"></i><div><span>Bitiş Tarihi</span><strong><?= !empty($project['deadline']) ? date('d M Y', strtotime($project['deadline'])) : 'Belirtilmemiş' ?></strong></div></li></ul></div><?php if (!empty($project['attachment_path'])): ?><div class="summary-card attachment-card"><h3><i class="fas fa-paperclip"></i> Ek Materyaller</h3><p>Müşteri, tasarıma yardımcı olması için ek dosyalar yükledi.</p><a href="<?= htmlspecialchars($project['attachment_path']) ?>" download class="btn btn-secondary" style="width: 100%; text-align:center;">Dosyaları İndir (.zip)</a></div><?php endif; ?></div></div>
        </div>

        <div class="tab-content" id="tab-comments">
            <h3>Yorumlar ve Sıkça Sorulan Sorular</h3>
            <?php if (is_logged_in()): ?>
                <div class="comment-form-container">
                    <form id="comment-form">
                        <input type="hidden" name="project_id" value="<?= (int)$project_id ?>">
                        <textarea name="comment" placeholder="Proje sahibi'ne herkese açık bir soru sorun veya bir duyuru yapın..." required></textarea>
                        <button type="submit" class="btn btn-primary" style="float: right;">Yorum Gönder</button>
                    </form>
                </div>
            <?php else: ?>
                <p>Yorum yapmak için <a href="#" class="open-login-modal">giriş yapmalısınız</a>.</p>
            <?php endif; ?>
            <div class="comment-list" id="comment-list" style="clear:both; padding-top: 1rem;">
                <?php if (empty($comments)): ?>
                    <p>Henüz hiç yorum yapılmadı.</p>
                <?php else: ?>
                    <?php foreach($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-author"><a href="profile.php?username=<?= htmlspecialchars($comment['username'] ?? '') ?>"><?= htmlspecialchars($comment['username'] ?? '') ?></a></div>
                            <div class="comment-body"><?= nl2br(htmlspecialchars($comment['comment'] ?? '')) ?></div>
                            <div class="comment-date"><?= !empty($comment['created_at']) ? date('d M Y, H:i', strtotime($comment['created_at'])) : '' ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($my_submissions)): ?>
        <div class="tab-content" id="tab-my-submissions">
            <h3>Bu Projeye Gönderdiğiniz Sunumlar</h3>
            <p>Bu alanda sadece sizin gönderdiğiniz sunumlar listelenir. Sunumunuzu silmek için üzerine gelin.</p>
            <div class="submission-grid compact-grid" style="margin-top: 1.5rem;">
                <?php foreach ($my_submissions as $my_sub): ?>
                <div class="submission-card-v2 my-submission-card">
                    <div class="submission-entry-number">#<?= htmlspecialchars($my_sub['entry_number']) ?></div>
                    <a href="<?= htmlspecialchars($my_sub['file_path']) ?>" target="_blank" class="submission-image-link">
                        <img src="<?= htmlspecialchars($my_sub['file_path']) ?>" alt="Sunumum #<?= htmlspecialchars($my_sub['entry_number']) ?>">
                    </a>
                    <div class="my-submission-actions">
                        <form action="actions/delete_my_submission.php" method="POST" onsubmit="return confirm('Bu sunumu kalıcı olarak silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                            <input type="hidden" name="submission_id" value="<?= $my_sub['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <button type="submit" class="btn-my-sub-delete"><i class="fas fa-trash-alt"></i> Sil</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</main>

<div class="modal-overlay" id="proposalModal"><div class="modal-content" style="max-width: 720px;"><button class="modal-close">&times;</button><h2 class="section-title">Sunum Gönder</h2><p style="text-align:center; margin-bottom:1rem;">Proje: "<?= htmlspecialchars($project['title'] ?? '') ?>"</p><div id="drop-zone"><div id="drop-zone-text"><i class="fas fa-cloud-upload-alt"></i><p>Tasarım dosyanızı buraya sürükleyin veya tıklayın</p><span>(JPG, PNG, GIF - Maks. 5MB)</span></div><input type="file" id="design_file_input" style="display:none;" /><div id="upload-progress-container" style="display:none;"><div id="upload-progress-bar"></div></div><div id="file-preview"></div></div><form method="POST" id="proposal-form"><input type="hidden" name="project_id" value="<?= (int)($project['id'] ?? 0) ?>" /><input type="hidden" name="uploaded_filepath" id="uploaded_filepath" /><div class="form-group"><label for="description">Açıklamanız (İsteğe Bağlı)</label><textarea id="description" name="description" rows="4" placeholder="Tasarımınızla ilgili notlarınız."></textarea></div><div class="form-group"><button type="submit" id="submit-proposal-btn" class="btn btn-primary" style="width:100%;" disabled>Sunumumu Gönder</button><small id="submit-helper-text" style="text-align:center; display:block; margin-top:1rem;">Lütfen önce bir dosya yükleyin.</small></div></form></div></div>

<div class="modal-overlay" id="reportModal"><div class="modal-content" style="max-width: 600px;"><button class="modal-close">&times;</button><h2 class="section-title">Sunumu Şikayet Et</h2><form id="report-form"><input type="hidden" id="report_submission_id" name="submission_id"><div class="form-group"><label>Şikayet Sebebi:</label><div class="radio-group report-reasons"><label><input type="radio" name="reason_type" value="Kopya veya Çalıntı Tasarım" required> Kopya veya Çalıntı Tasarım</label><label><input type="radio" name="reason_type" value="Uygunsuz İçerik" required> Uygunsuz İçerik</label><label><input type="radio" name="reason_type" value="Diğer" required> Diğer</label></div></div><div class="form-group" id="reason-link-group" style="display:none;"><label for="reason_link">Benzer Tasarımın Linki (Varsa):</label><input type="url" id="reason_link" name="reason_link" placeholder="https://ornek.com/benzer-tasarim"></div><div class="form-group"><label for="reason_details">Ek Detaylar:</label><textarea id="reason_details" name="reason_details" rows="4" placeholder="Ek bilgi."></textarea></div><div class="form-group"><button type="submit" class="btn btn-primary" style="width:100%;">Şikayeti Gönder</button></div></form></div></div>

<?php include 'includes/footer.php'; ?>