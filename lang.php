<?php
$lang = $_SESSION['lang'] ?? 'tr';

$texts = [
  'tr' => [
    'welcome' => 'Hoş geldiniz!',
    'projects' => 'Projeler',
    'create' => 'Yeni Proje',
    'logout' => 'Çıkış',
  ],
  'en' => [
    'welcome' => 'Welcome!',
    'projects' => 'Projects',
    'create' => 'New Project',
    'logout' => 'Logout',
  ]
];

function t($key) {
  global $texts, $lang;
  return $texts[$lang][$key] ?? $key;
}