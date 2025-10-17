<?php
session_start();
$theme = $_SESSION['theme'] ?? 'light';
include 'header.php';
?>

<div class="container">
  <h2>İletişim</h2>
  <form method="POST">
    <label>Adınız</label>
    <input type="text" name="name" required>
    <label>Email</label>
    <input type="email" name="email" required>
    <label>Mesajınız</label>
    <textarea name="message" rows="5" required></textarea>
    <button type="submit">Gönder</button>
  </form>
</div>

<?php include 'footer.php'; ?>