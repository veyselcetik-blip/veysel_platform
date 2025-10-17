<?php
require 'includes/init.php';
require_login(); // Sadece giriş yapmış kullanıcılar bu sayfayı görebilir

$user_id = $_SESSION['user_id'];
$message = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Metin tabanlı verileri al ve güncelle
    $username = trim($_POST['username']);
    $title = trim($_POST['title']);
    $bio = trim($_POST['bio']);
    $website_url = trim($_POST['website_url']);
    $dribbble_url = trim($_POST['dribbble_url']);
    $twitter_url = trim($_POST['twitter_url']);

    $stmt = $db->prepare(
        "UPDATE users SET username = ?, title = ?, bio = ?, website_url = ?, dribbble_url = ?, twitter_url = ? WHERE id = ?"
    );
    $stmt->execute([$username, $title, $bio, $website_url, $dribbble_url, $twitter_url, $user_id]);
    
    // Session'daki kullanıcı adını da anında güncelle
    $_SESSION['username'] = $username;
    $message = "<p class='form-message success'>Genel bilgileriniz güncellendi.</p>";

    // 2. Profil resmini güncelle (eğer yeni bir resim yüklendiyse)
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $upload_result = secure_file_upload($_FILES['profile_picture'], ['jpg', 'jpeg', 'png', 'gif'], 2); // Maks 2MB
        if (isset($upload_result['success'])) {
            $new_path = $upload_result['filepath'];
            $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?")->execute([$new_path, $user_id]);
            $message = "<p class='form-message success'>Profil resminiz başarıyla güncellendi.</p>";
        } else {
            $message = "<p class='form-message error'>" . htmlspecialchars($upload_result['error']) . "</p>";
        }
    }

    // 3. Kapak fotoğrafını güncelle (eğer yeni bir resim yüklendiyse)
    if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] == 0) {
        $upload_result = secure_file_upload($_FILES['cover_photo'], ['jpg', 'jpeg', 'png'], 4); // Maks 4MB
        if (isset($upload_result['success'])) {
            $new_path = $upload_result['filepath'];
            $db->prepare("UPDATE users SET cover_photo = ? WHERE id = ?")->execute([$new_path, $user_id]);
            $message = "<p class='form-message success'>Kapak fotoğrafınız başarıyla güncellendi.</p>";
        } else {
            $message = "<p class='form-message error'>" . htmlspecialchars($upload_result['error']) . "</p>";
        }
    }
}

// En güncel kullanıcı bilgilerini veritabanından çek
$stmt_user = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

include 'includes/header.php';
?>

<?php include 'includes/navbar.php'; ?>

<main class="container page-container">
    <div class="page-header">
        <h1>Profili Düzenle</h1>
    </div>

    <?= $message ?>

    <div class="edit-profile-grid">
        <div class="edit-profile-nav">
            <a href="#genel" class="active">Genel Bilgiler</a>
            <a href="change-password.php">Şifre Değiştir</a>
            </div>

        <div class="edit-profile-content">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="content-box" id="genel">
                    <h3>Genel Bilgiler</h3>
                    <div class="form-group">
                        <label>Profil Resmi</label><br>
                        <img src="<?= get_user_avatar($user_id, $user['username']) ?>" class="profile-preview">
                        <input type="file" name="profile_picture">
                         <small>En iyi görünüm için kare (1:1 oranında) bir resim yükleyin.</small>
                    </div>
                    <div class="form-group">
                        <label>Kapak Fotoğrafı</label><br>
                        <input type="file" name="cover_photo">
                        <small>Profilinizin en üstünde görünecek geniş bir resim. (Önerilen oran 3:1)</small>
                    </div>
                    <div class="form-group"><label for="username">Kullanıcı Adı</label><input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']) ?>" required></div>
                    <div class="form-group"><label for="title">Unvan</label><input type="text" id="title" name="title" value="<?= htmlspecialchars($user['title'] ?? '') ?>" placeholder="örn: Logo & Marka Kimliği Uzmanı"></div>
                    <div class="form-group"><label for="bio">Hakkında</label><textarea id="bio" name="bio" rows="5" placeholder="Kendinizden, yeteneklerinizden ve tecrübelerinizden bahsedin..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea></div>
                    
                    <h3>Sosyal Medya Linkleri</h3>
                    <div class="form-group"><label>Web Sitesi</label><input type="url" name="website_url" value="<?= htmlspecialchars($user['website_url'] ?? '') ?>" placeholder="https://"></div>
                    <div class="form-group"><label>Dribbble URL</label><input type="url" name="dribbble_url" value="<?= htmlspecialchars($user['dribbble_url'] ?? '') ?>" placeholder="https://dribbble.com/kullaniciadi"></div>
                    <div class="form-group"><label>Twitter URL</label><input type="url" name="twitter_url" value="<?= htmlspecialchars($user['twitter_url'] ?? '') ?>" placeholder="https://twitter.com/kullaniciadi"></div>
                </div>

                <div class="form-group" style="margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary btn-lg">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>