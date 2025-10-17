<?php
require 'includes/init.php';
require_admin(); // YENİ: Sadece adminler erişebilir

include 'includes/header.php';
?>
<div class="container">
  <h2>Yönetim Paneli</h2>
  <a href="view-votes.php"><button>Oyları Görüntüle</button></a>
  <a href="view-uploads.php"><button>Yüklenen Dosyaları Görüntüle</button></a>
  <a href="delete-project.php"><button>Proje Sil</button></a>
  </div>
<?php include 'includes/footer.php'; ?>