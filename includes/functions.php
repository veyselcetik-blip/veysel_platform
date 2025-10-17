<?php
// Session'ı güvenli bir şekilde başlat
function start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Giriş yapılıp yapılmadığını kontrol et
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Admin olup olmadığını kontrol et
function is_admin() {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Yetkisiz erişimde sayfayı sonlandır
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function require_admin() {
    if (!is_admin()) {
        // İsterseniz bir "yetkiniz yok" sayfasına yönlendirebilirsiniz.
        die("Bu sayfaya erişim yetkiniz bulunmamaktadır.");
    }
}

// CSRF Token oluşturma ve doğrulama
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Güvenli dosya yükleme fonksiyonu
function secure_file_upload($file, $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'], $max_size_mb = 5) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Dosya yüklenirken bir hata oluştu.'];
    }

    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Güvenlik: Uzantı kontrolü
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['error' => 'İzin verilmeyen dosya uzantısı.'];
    }

    // Güvenlik: Dosya boyutu kontrolü
    if ($file['size'] > $max_size_mb * 1024 * 1024) {
        return ['error' => "Dosya boyutu çok büyük (Maks: {$max_size_mb}MB)."];
    }

    // Güvenlik: Benzersiz ve rastgele dosya adı oluşturma
    $new_filename = bin2hex(random_bytes(16)) . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filepath' => $target_file];
    }

    return ['error' => 'Dosya taşınırken bir hata oluştu.'];
}
// includes/functions.php dosyasının en altına eklenecek

/**
 * Yorum metni içindeki #<numara> etiketlerini, ilgili sunuma giden
 * tıklanabilir ve lightbox'ı tetikleyen linklere dönüştürür.
 */
// includes/functions.php dosyasındaki eski fonksiyonu silip bunu yapıştırın

function linkify_submission_tags($comment_text, $project_id) {
    global $db; 

    $linked_text = preg_replace_callback(
        '/#(\d+)/', 
        function($matches) use ($project_id, $db) {
            $entry_number = $matches[1];
            
            $stmt = $db->prepare("SELECT id, file_path FROM submissions WHERE project_id = ? AND entry_number = ?");
            $stmt->execute([$project_id, $entry_number]);
            $submission = $stmt->fetch();
            
            if ($submission) {
                // Sadece <a> etiketi döndürüyoruz, <img> değil.
                return '<a href="'.htmlspecialchars($submission['file_path']).'" class="submission-tag" title="#'.$entry_number.' numaralı sunumu gör">#'.$entry_number.'</a>';
            }
            
            return $matches[0];
        },
        $comment_text
    );
    
    // nl2br fonksiyonunu en sonda, tüm linkler oluşturulduktan sonra çağırıyoruz.
    return nl2br($linked_text);
}
// includes/functions.php dosyasının en altına eklenecek

// PHPMailer sınıflarını dahil et
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';

/**
 * Platform üzerinden SMTP ayarlarını kullanarak e-posta gönderir.
 *
 * @param string $to Alici e-posta adresi.
 * @param string $subject E-posta konusu.
 * @param string $body E-posta içeriği (HTML olabilir).
 * @return bool Gönderim başarılı ise true, değilse false döner.
 */
function send_email($to, $subject, $body) {
    global $site_settings; // init.php'de tanımlanan site ayarları

    // Eğer SMTP ayarları panelden girilmemişse, göndermeyi deneme
    if (empty($site_settings['smtp_host']) || empty($site_settings['smtp_user'])) {
        // Hata günlüğüne yazdırılabilir: error_log("SMTP ayarları eksik, e-posta gönderilemedi.");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        // Sunucu Ayarları
        $mail->isSMTP();
        $mail->Host       = $site_settings['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $site_settings['smtp_user'];
        $mail->Password   = $site_settings['smtp_pass'];
        $mail->SMTPSecure = $site_settings['smtp_secure']; // 'tls' veya 'ssl'
        $mail->Port       = $site_settings['smtp_port'];
        $mail->CharSet    = 'UTF-8';

        // Gönderici ve Alıcılar
        $mail->setFrom($site_settings['site_email'], $site_settings['site_name']);
        $mail->addAddress($to);

        // İçerik
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Hata günlüğüne yazdırılabilir: error_log("PHPMailer hatası: {$mail->ErrorInfo}");
        return false;
    }
}

// includes/functions.php dosyasının en altına eklenecek
function get_user_avatar($user_id, $username) {
    global $db;
    $stmt = $db->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $path = $stmt->fetchColumn();

    if ($path && file_exists($path)) {
        return htmlspecialchars($path);
    } else {
        // Profil resmi yoksa, baş harflerden oluşan bir SVG avatarı oluştur
        $initial = strtoupper(substr($username, 0, 1));
        $svg = '<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><rect width="100" height="100" fill="#'.substr(md5($username), 0, 6).'"></rect><text x="50" y="50" font-family="Arial" font-size="50" fill="white" text-anchor="middle" dy=".3em">'.$initial.'</text></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
?>
