// start-competition.php

<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $creator_id = $_SESSION['user_id'];

  $stmt = $db->prepare("INSERT INTO competitions (title, description, creator_id) VALUES (?, ?, ?)");
  $stmt->execute([$title, $description, $creator_id]);

  header("Location: index.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>Yarışma Başlat</title>
</head>
<body>
  <h2>Yeni Yarışma Başlat</h2>
  <form method="POST">
    <label>Yarışma Başlığı:</label><br>
    <input type="text" name="title" required><br><br>

    <label>Açıklama:</label><br>
    <textarea name="description" rows="5" cols="40" required></textarea><br><br>

    <button type="submit">Başlat</button>
  </form>

  <hr>
  <h3>Hazır Şablon Önerileri</h3>
  <ul>
    <li>🖌️ Logo Tasarımı Yarışması</li>
    <li>📱 Mobil Uygulama Arayüzü Yarışması</li>
    <li>🎬 Marka Tanıtım Videosu Yarışması</li>
    <li>📷 Ürün Fotoğrafı Yarışması</li>
    <li>🧠 İsim Bulma ve Slogan Yarışması</li>
  </ul>
</body>
</html>