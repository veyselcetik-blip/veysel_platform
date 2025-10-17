<?php
session_start();
$theme = $_SESSION['theme'] ?? 'light';
include 'header.php';
?>

<div class="container">
  <h2>Hakkımızda</h2>
  <p>
    Veysel Platformu, dijital projeleri paylaşmak, yarışmalara katılmak ve fikir alışverişi yapmak için kurulmuş bir sistemdir.
    Kullanıcı dostu arayüzü ve güven veren tasarımıyla herkesin katkı sunabileceği bir ortam sağlar.
  </p>
</div>

<?php include 'footer.php'; ?>