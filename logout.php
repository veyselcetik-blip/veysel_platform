<?php
// Session'ı başlatarak mevcut session'a erişim sağlıyoruz
session_start();

// Tüm session değişkenlerini siliyoruz
$_SESSION = array();

// Session'ı sonlandırıyoruz
session_destroy();

// Kullanıcıyı ana sayfaya yönlendiriyoruz
header("Location: index.php");
exit;
?>