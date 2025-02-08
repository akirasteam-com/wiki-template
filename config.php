<?php
// config.php

// Répertoire de stockage des articles
$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

// Répertoire pour l'historique des révisions
$historyDir = __DIR__ . '/data/history';
if (!is_dir($historyDir)) {
    mkdir($historyDir, 0755, true);
}

// Fichier des utilisateurs
$usersFile = __DIR__ . '/users.json';
if (!file_exists($usersFile)) {
    file_put_contents($usersFile, json_encode([]));
}

// Fichier de configuration du site
$siteInfoFile = __DIR__ . '/siteinfo.json';
$defaultSiteInfo = [
    'siteName'        => 'Your wiki name',
    'tagline'         => 'Your wiki, which allows you to post articles.....',
    'about'           => 'This is an easy to use wiki!',
    'legal_notice'=>'','privacy_policy'  => '','contact'=> 'Contact us : contact@akirasteam.com'
];
if (!file_exists($siteInfoFile)) {
    file_put_contents($siteInfoFile, json_encode($defaultSiteInfo));
}

// Thèmes disponibles et thème par défaut
$availableThemes = ['light', 'dark'];
$defaultTheme = 'light';

// Langues disponibles et langue par défaut (pour l’interface)
$availableLanguages = ['fr', 'en'];
$defaultLanguage = 'fr';

// Chemin local pour l'avatar par défaut
$defaultAvatar = 'themes/default-avatar.png';
?>
