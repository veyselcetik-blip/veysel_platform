<?php
require 'includes/init.php';

// --- 1. VERİLERİ ÇEKME VE HAZIRLAMA ---
$project_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
if ($project_id === 0) { header("Location: browse-projects.php"); exit; }

$stmt = $db->prepare("SELECT p.*, u.username FROM projects p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) { header("Location: browse-projects.php?error=notfound"); exit; }

// Projenin süresi dolduysa durumunu 'değerlendirme' yap
if ($project['status'] == 'aktif' && !empty($project['deadline'])) {
    $deadline_dt = new DateTime($project['deadline'] . ' 23:59:59');
    $now = new DateTime();
    if ($now > $deadline_dt) {
        $update_stmt = $db->prepare("UPDATE projects SET status = 'değerlendirme' WHERE id = ?");
        $update_stmt->execute([$project_id]);
        $project['status'] = 'değerlendirme';
    }
}

// Gerekli Değişkenler
$is_owner = (is_logged_in() && $_SESSION['user_id'] == $project['user_id']);
$is_winner = (is_logged_in() && $project['winner_id'] && $_SESSION['user_id'] == $project['winner_id']);
$can_view_submissions = ($project['is_public'] == 1 || $is_owner);
$current_user_id = $_SESSION['user_id'] ?? 0;
$winning_submission_id = $project['winning_submission_id'] ?? 0;
// === YENİ EKLENECEK BÖLÜM BAŞLANGICI ===
// Proje için daha önce yorum yapılıp yapılmadığını kontrol et
$has_review = false;
if ($project['status'] === 'tamamlandı' && $is_owner) {
    $review_check_stmt = $db->prepare("SELECT id FROM reviews WHERE project_id = ? AND reviewer_id = ?");
    $review_check_stmt->execute([$project_id, $current_user_id]);
    if ($review_check_stmt->fetch()) {
        $has_review = true;
    }
}
// === YENİ EKLENECEK BÖLÜM SONU ===
// İstatistikler
$total_submissions = $db->query("SELECT COUNT(*) FROM submissions WHERE project_id = {$project_id}")->fetchColumn();

// Sayfalama (Pagination)
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$items_per_page = 9;
$offset = ($page - 1) * $items_per_page;
$total_pages = $total_submissions > 0 ? ceil($total_submissions / $items_per_page) : 1;

// === DÜZELTME: SUNUMLARI ÇEKEN SORGUNUN GÜNCELLENMESİ ===
$submissions = [];
if ($can_view_submissions) {
    // Kazanan sunumu her zaman en başa getirmek için ORDER BY ifadesini güncelliyoruz.
    $sql = "SELECT s.*, u.username AS designer_name, r.rating 
            FROM submissions s 
            JOIN users u ON s.user_id = u.id 
            LEFT JOIN ratings r ON (s.id = r.submission_id AND r.user_id = :current_user_id) 
            WHERE s.project_id = :project_id 
            ORDER BY s.id = :winning_submission_id DESC, s.entry_number ASC 
            LIMIT :limit OFFSET :offset";
            
    $sub_stmt = $db->prepare($sql);
    $sub_stmt->bindValue(':current_user_id', $current_user_id, PDO::PARAM_INT);
    $sub_stmt->bindValue(':project_id', $project_id, PDO::PARAM_INT);
    $sub_stmt->bindValue(':winning_submission_id', $winning_submission_id, PDO::PARAM_INT);
    $sub_stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
    $sub_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $sub_stmt->execute();
    $submissions = $sub_stmt->fetchAll();
}


// Yorumlar
$comments_stmt = $db->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.project_id = ? ORDER BY c.created_at DESC");
$comments_stmt->execute([$project_id]);
$comments = $comments_stmt->fetchAll();

include 'includes/header.php';
?>

<?php include 'includes/navbar.php'; ?>

<main class="container page-container">

    <?php // Kazanan seçildikten sonraki "Final Dosyalarını Yükle/İndir" kutucukları
    if ($is_winner && $project['status'] == 'kazanan_seçildi'): ?>
        <div class="upload-final-box">
            <h3>Tebrikler, bu projeyi kazandınız!</h3>
            <p>Lütfen projenin final dosyalarını (.zip formatında) yükleyerek teslimat sürecini tamamlayın.</p>
            <a href="upload-final.php?project_id=<?= $project_id ?>" class="btn btn-success btn-lg">Final Dosyalarını Yükle</a>
        </div>
    <?php elseif ($is_owner && !empty($project['final_file'])): ?>
         <div class="upload-final-box">
            <h3>Teslimat Tamamlandı!</h3>
            <p>Kazanan tasarımcı final dosyalarını yükledi. Aşağıdaki butondan indirebilirsiniz.</p>
            <a href="<?= htmlspecialchars($project['final_file']) ?>" class="btn btn-success btn-lg" download>Final Dosyalarını İndir</a>
        </div>
     <?php elseif ($is_owner && empty($project['final_file']) && in_array($project['status'], ['kazanan_seçildi', 'tamamlandı'])): ?>
        <div class="upload-final-box info">
            <h3>Kazanan Seçildi</h3>
            <p>Tasarımcıdan final dosyalarını yüklemesi bekleniyor.</p>
        </div>
    <?php endif; ?>

    <div class="project-header-card">
        <div class="header-main">
            <span class="project-category"><?= htmlspecialchars($project['category']) ?></span>
            <h1><?= htmlspecialchars($project['title']) ?></h1>
        </div>
        <div class="header-details">
            <div><i class="fas fa-user-tie"></i> <span>Müşteri</span><strong><?= htmlspecialchars($project['username']) ?></strong></div>
            <div><i class="fas fa-calendar-alt"></i> <span>Başlangıç</span><strong><?= date('d M Y', strtotime($project['created_at'])) ?></strong></div>
            <div><i class="fas fa-calendar-check"></i> <span>Bitiş</span><strong><?= !empty($project['deadline']) ? date('d M Y', strtotime($project['deadline'])) : 'Belirtilmemiş' ?></strong></div>
            <div><i class="fas fa-trophy"></i> <span>Ödül</span><strong><?= htmlspecialchars($project['budget']) ?></strong></div>
        </div>
    </div>
    
    <div class="project-status-panel">
         <div class="status-left">
            <?php if ($project['status'] == 'aktif' && !empty($project['deadline'])):
                 $deadline_dt_for_js = new DateTime($project['deadline'] . ' 23:59:59');
            ?>
                <div id="countdown-timer" data-deadline="<?= $deadline_dt_for_js->format('Y-m-d\TH:i:s') ?>"></div>
            <?php elseif ($project['status'] == 'değerlendirme'): ?>
                <div class="status-message evaluation"><i class="fas fa-hourglass-half"></i> Proje yeni sunumlara kapalı. Kazanan bekleniyor.</div>
            <?php else: ?>
                 <div class="status-message completed"><i class="fas fa-info-circle"></i> Proje durumu: <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $project['status']))) ?></div>
            <?php endif; ?>
        </div>
        <div class="status-right">
             <?php if (is_logged_in() && !$is_owner && $project['status'] == 'aktif'): ?>
                <button id="openProposalModalBtn" class="btn btn-primary btn-submit-now"><i class="fas fa-paper-plane"></i> Sunum Gönder</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="tab-container">
        <div class="tab-buttons">
            <button class="tab-btn active" data-tab="submissions">Sunumlar (<?= $total_submissions ?>)</button>
            <button class="tab-btn" data-tab="summary">Yarışma Özeti</button>
            <button class="tab-btn" data-tab="comments">Yorumlar / S.S.S. (<?= count($comments) ?>)</button>
        </div>

        <div class="tab-content active" id="tab-submissions">
            <?php if ($can_view_submissions): ?>
                <?php if (!empty($submissions)): ?>
                    <div class="submission-grid compact-grid">
                        <?php foreach ($submissions as $submission): ?>
                            <?php // === DÜZELTME: KAZANAN KARTINA ÖZEL CLASS EKLEME ===
                                $winner_class = ($submission['id'] == $winning_submission_id) ? ' is-winner' : '';
                            ?>
                            <div class="submission-card-v2<?= $winner_class ?>">
                            <?php // === DÜZELTME SONU === ?>
                                <div class="submission-entry-number">#<?= htmlspecialchars($submission['entry_number']) ?></div>
                                <a href="<?= htmlspecialchars($submission['file_path']) ?>" target="_blank" class="submission-image-link">
                                    <img src="<?= htmlspecialchars($submission['file_path']) ?>" alt="Tasarım Sunumu #<?= htmlspecialchars($submission['entry_number']) ?>">
                                </a>
                                <div class="submission-info-bar">
                                    <?php if ($is_owner): ?>
                                        <div class="rating-stars" data-submission-id="<?= $submission['id'] ?>">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="<?= ($i <= $submission['rating']) ? 'fas' : 'far' ?> fa-star" data-rating="<?= $i ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_owner && !$project['winner_id'] && in_array($project['status'], ['değerlendirme', 'aktif'])): ?>
                                        <form action="actions/pick_winner.php" method="POST" onsubmit="return confirm('Bu tasarımı kazanan olarak seçmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                            <input type="hidden" name="submission_id" value="<?= $submission['id'] ?>">
                                            <input type="hidden" name="winner_id" value="<?= $submission['user_id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <button type="submit" class="btn-winner"><i class="fas fa-trophy"></i> Seç</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <div class="submission-card-footer">
                                    <span>Tasarımcı: <a href="profile.php?username=<?= htmlspecialchars($submission['designer_name']) ?>"><?= htmlspecialchars($submission['designer_name']) ?></a></span>
                                    <div class="card-actions">
                                        <?php if(is_logged_in() && $_SESSION['user_id'] != $submission['user_id']): ?>
                                        <button class="report-btn" data-submission-id="<?= $submission['id'] ?>"><i class="fas fa-flag"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($total_pages > 1): ?>
                        <nav class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?id=<?= $project_id ?>&page=<?= $i ?>" class="<?= ($page == $i) ? 'active' : '' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="submission-placeholder"><p>Bu proje için henüz bir sunum yapılmadı.</p></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="submission-placeholder locked"><i class="fas fa-lock"></i><p>Bu proje gizlidir. Sunumları sadece proje sahibi görebilir.</p></div>
            <?php endif; ?>
        </div>
        
        <div class="tab-content" id="tab-summary">
    <div class="summary-grid">
        <div class="summary-main">
            <div class="summary-card">
                <h3><i class="fas fa-bullseye"></i> Tasarım Brifi</h3>
                <p class="summary-content"><?= nl2br(htmlspecialchars($project['description'])) ?></p>
            </div>
        </div>

        <div class="summary-sidebar">
            <div class="summary-card">
                <h3><i class="fas fa-info-circle"></i> Proje Kimliği</h3>
                <ul class="summary-meta-list">
                    <li>
                        <i class="fas fa-user-tie"></i>
                        <div>
                            <span>Müşteri</span>
                            <strong><a href="profile.php?username=<?= htmlspecialchars($project['username']) ?>"><?= htmlspecialchars($project['username']) ?></a></strong>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-tags"></i>
                        <div>
                            <span>Kategori</span>
                            <strong><?= htmlspecialchars($project['category']) ?></strong>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-trophy"></i>
                        <div>
                            <span>Ödül</span>
                            <strong><?= htmlspecialchars($project['budget']) ?></strong>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-calendar-check"></i>
                        <div>
                            <span>Bitiş Tarihi</span>
                            <strong><?= !empty($project['deadline']) ? date('d M Y', strtotime($project['deadline'])) : 'Belirtilmemiş' ?></strong>
                        </div>
                    </li>
                </ul>
            </div>

            <?php if ($project['attachment_path']): ?>
            <div class="summary-card attachment-card">
                <h3><i class="fas fa-paperclip"></i> Ek Materyaller</h3>
                <p>Müşteri, tasarıma yardımcı olması için ek dosyalar yükledi.</p>
                <a href="<?= htmlspecialchars($project['attachment_path']) ?>" download class="btn btn-secondary" style="width: 100%; text-align:center;">
                    Dosyaları İndir (.zip)
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

        <div class="tab-content" id="tab-comments">
            <h3>Yorumlar ve Sıkça Sorulan Sorular</h3>
            <?php if (is_logged_in()): ?>
            <div class="comment-form-container">
                <form id="comment-form">
                    <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                    <textarea name="comment" placeholder="Proje sahibi'ne herkese açık bir soru sorun..." required></textarea>
                    <button type="submit" class="btn btn-primary" style="float: right;">Yorum Gönder</button>
                </form>
            </div>
            <?php else: ?>
                <p>Yorum yapmak için <a href="#" id="openLoginModalBtnFromComment">giriş yapmalısınız</a>.</p>
            <?php endif; ?>
            <div class="comment-list" id="comment-list" style="clear:both; padding-top: 1rem;">
                <?php if (empty($comments)): ?>
                    <p>Henüz hiç yorum yapılmadı. İlk yorumu siz yapın!</p>
                <?php else: ?>
                    <?php foreach($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-author"><a href="profile.php?username=<?= htmlspecialchars($comment['username']) ?>"><?= htmlspecialchars($comment['username']) ?></a></div>
                            <div class="comment-body"><?= function_exists('linkify_submission_tags') ? linkify_submission_tags(htmlspecialchars($comment['comment']), $project_id) : nl2br(htmlspecialchars($comment['comment'])) ?></div>
                            <div class="comment-date"><?= date('d M Y, H:i', strtotime($comment['created_at'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<div class="modal-overlay" id="proposalModal">
    <div class="modal-content" style="max-width: 800px;">
        <button class="modal-close">&times;</button>
        <div class="form-container" style="box-shadow: none; padding: 0;">
            <h2 class="section-title">Sunum Gönder</h2>
            <p style="text-align:center; margin-bottom:1rem;">Proje: "<?= htmlspecialchars($project['title']) ?>"</p>
            <div id="drop-zone"><div id="drop-zone-text"><i class="fas fa-cloud-upload-alt"></i><p>Tasarım dosyanızı buraya sürükleyin veya tıklayın</p><span>(JPG, PNG, GIF - Maks. 5MB)</span></div><input type="file" id="design_file_input" style="display: none;"><div id="upload-progress-container" style="display: none;"><div id="upload-progress-bar"></div></div><div id="file-preview"></div></div>
            <form method="POST" id="proposal-form">
                <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                <input type="hidden" name="uploaded_filepath" id="uploaded_filepath">
                <div class="form-group"><label for="description">Açıklamanız (İsteğe Bağlı)</label><textarea id="description" name="description" rows="4" placeholder="Tasarımınızla ilgili notlarınız..."></textarea></div>
                <div class="form-group"><button type="submit" id="submit-proposal-btn" class="btn btn-primary" style="width: 100%;" disabled>Sunumumu Gönder</button><small id="submit-helper-text" style="text-align:center; display:block; margin-top:1rem;">Lütfen önce bir dosya yükleyin.</small></div>
            </form>
        </div>
    </div>
</div>
<div class="modal-overlay" id="reportModal">
   <div class="modal-content" style="max-width: 600px;">
        <button class="modal-close">&times;</button>
        <h2 class="section-title">Sunumu Şikayet Et</h2>
        <form id="report-form">
            <input type="hidden" id="report_submission_id" name="submission_id">
            <div class="form-group">
                <label>Şikayet Sebebi:</label>
                <div class="radio-group report-reasons">
                    <label><input type="radio" name="reason_type" value="Kopya veya Çalıntı Tasarım" required> Kopya veya Çalıntı Tasarım</label>
                    <label><input type="radio" name="reason_type" value="Uygunsuz İçerik" required> Uygunsuz İçerik</label>
                    <label><input type="radio" name="reason_type" value="Diğer" required> Diğer</label>
                </div>
            </div>
            <div class="form-group" id="reason-link-group" style="display:none;">
                <label for="reason_link">Benzer Tasarımın Linki (Varsa):</label>
                <input type="url" id="reason_link" name="reason_link" placeholder="https://ornek.com/benzer-tasarim">
            </div>
            <div class="form-group">
                <label for="reason_details">Ek Detaylar:</label>
                <textarea id="reason_details" name="reason_details" rows="4" placeholder="Ek bilgi..."></textarea>
            </div>
            <div class="form-group"><button type="submit" class="btn btn-primary" style="width:100%;">Şikayeti Gönder</button></div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>