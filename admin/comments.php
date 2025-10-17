<?php 
include 'includes/header.php'; 

// Tüm yorumları, ilişkili proje ve kullanıcı bilgileriyle birlikte en yeniden eskiye doğru çek
$comments = $db->query(
    "SELECT c.id, c.comment, c.created_at, u.username, p.id as project_id, p.title as project_title
     FROM comments c
     JOIN users u ON c.user_id = u.id
     JOIN projects p ON c.project_id = p.id
     ORDER BY c.id DESC"
)->fetchAll();
?>

<div class="page-header">
    <h1>Yorum Yönetimi (<?= count($comments) ?>)</h1>
</div>

<div class="content-box">
    <div class="table-responsive">
        <table class="styled-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Yorum</th>
                    <th>Yazan</th>
                    <th>Proje</th>
                    <th>Tarih</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comments)): ?>
                    <tr><td colspan="6">Sitede hiç yorum bulunmuyor.</td></tr>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td><?= $comment['id'] ?></td>
                            <td style="max-width: 400px;"><?= htmlspecialchars($comment['comment']) ?></td>
                            <td><?= htmlspecialchars($comment['username']) ?></td>
                            <td><a href="../project-detail.php?id=<?= $comment['project_id'] ?>" target="_blank"><?= htmlspecialchars($comment['project_title']) ?></a></td>
                            <td><?= date('d M Y, H:i', strtotime($comment['created_at'])) ?></td>
                            <td>
                                <form action="actions/delete_comment.php" method="POST" onsubmit="return confirm('Bu yorumu kalıcı olarak silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                    <button type="submit" class="btn btn-danger">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>