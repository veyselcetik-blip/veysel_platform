<?php
require 'includes/db.php';

function notifyUser($user_id, $message) {
  global $db;
  $stmt = $db->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
  $stmt->execute([$user_id, $message]);
}