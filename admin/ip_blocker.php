<?php
include 'includes/header.php'; 

$message = '';

// Yeni bir IP engelleme veya engeli kaldırma isteği varsa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // YENİ BİR IP ENGELLEME İSTEĞİ
    if (isset($_POST['ban_ip']) && !empty($_POST['ip_to_ban'])) {
        $ip_to_ban = trim($_POST['ip_to_ban']);
        
        // DÜZELTME: 'reason' alanı varsa al, yoksa boş bir metin ata.
        $reason = trim($_POST['reason'] ?? ''); 
        
        $db->prepare("INSERT INTO banned_ips (ip_address, reason) VALUES (?, ?) ON DUPLICATE KEY UPDATE reason=VALUES(reason)")->execute([$ip_to_ban, $reason]);
        $message = "<p style='color:green; font-weight:bold;'>'".htmlspecialchars($ip_to_ban)."' IP adresi başarıyla engellendi.</p>";
    }
    
    // BİR ENGELİ KALDIRMA İSTEĞİ
    if (isset($_POST['unban_ip'])) {
        $id_to_unban = $_POST['unban_ip'];
        $db->prepare("DELETE FROM banned_ips WHERE id = ?")->execute([$id_to_unban]);
        $message = "<p style='color:green;'>Engel başarıyla kaldırıldı.</p>";
    }
}

// Engellenmiş IP'leri ve son giriş yapan kullanıcıların IP'lerini çek
$banned_ips = $db->query("SELECT * FROM banned_ips ORDER BY id DESC")->fetchAll();
$recent_ips = $db->query("SELECT DISTINCT last_login_ip, username FROM users WHERE last_login_ip IS NOT NULL ORDER BY id DESC LIMIT 15")->fetchAll();
?>

<div class="page-header"><h1>IP Engelleme Yönetimi</h1></div>

<?= $message ?>

<div class="dashboard-grid">
    <div class="content-box">
        <h3>Yeni IP Engelle</h3>
        <form method="POST">
            <div class="form-group"><label for="ip_to_ban">Engellenecek IP Adresi</label><input type="text" id="ip_to_ban" name="ip_to_ban" required></div>
            <div class="form-group"><label for="reason">Sebep (Opsiyonel)</label><input type="text" id="reason" name="reason"></div>
            <button type="submit" name="ban_ip" class="btn btn-danger">Bu IP'yi Engelle</button>
        </form>
    </div>
    <div class="content-box">
        <h3>Son Kullanıcı IP'leri</h3>
        <div class="table-responsive" style="max-height: 300px; overflow-y:auto;">
            <table class="styled-table">
                <tbody>
                <?php foreach($recent_ips as $ip): ?>
                    <tr>
                        <td><b><?= htmlspecialchars($ip['last_login_ip']) ?></b><br><small>(Kullanıcı: <?= htmlspecialchars($ip['username']) ?>)</small></td>
                        <td style="text-align: right;">
                            <form method="POST"><input type="hidden" name="ip_to_ban" value="<?= $ip['last_login_ip'] ?>"><button type="submit" name="ban_ip" class="btn btn-danger">Engelle</button></form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="content-box" style="margin-top: 2rem;">
    <h3>Engellenmiş IP Adresleri (<?= count($banned_ips) ?>)</h3>
    <div class="table-responsive">
         <table class="styled-table">
            <thead><tr><th>IP Adresi</th><th>Sebep</th><th>Tarih</th><th>Kaldır</th></tr></thead>
            <tbody>
                <?php foreach($banned_ips as $ip): ?>
                <tr>
                    <td><?= htmlspecialchars($ip['ip_address']) ?></td>
                    <td><?= htmlspecialchars($ip['reason']) ?></td>
                    <td><?= date('d M Y', strtotime($ip['created_at'])) ?></td>
                    <td>
                        <form method="POST"><button type="submit" name="unban_ip" value="<?= $ip['id'] ?>" class="btn btn-success">Engeli Kaldır</button></form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>