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
    'legal_notice' => '**Site owner:**  \r\nAkirasTeam.com  \r\n[akirasteam.com](https://akirasteam.com)  \r\nEmail: [contact@akirasteam.com](mailto:contact@akirasteam.com)\r\n\r\n**Accommodation:**  \r\nThe site is hosted by AkirasTeam.com.\r\n\r\n**Responsibility :**  \r\nAkirasTeam.com cannot be held responsible for direct or indirect damage caused to the user\'s equipment when accessing the site.\r\n\r\n**Intellectual property:**  \r\nAll content of the AkirasTeam.com site, including, without limitation, graphics, images, texts, videos, animations, sounds, logos, gifs and icons as well as their formatting are the exclusive property of the company with the exception of brands, logos or content belonging to other partner companies or authors.\r\n\r\n**Personal data:**  \r\nThe information collected on the AkirasTeam.com site is used only within the legal framework provided in France for the respect of private life. AkirasTeam.com is the sole recipient of the data.\r\n\r\nFor any questions, you can contact us at the following address: [contact@akirasteam.com](mailto:contact@akirasteam.com).',
    'contact'=> 'contact@exemple.com'
];
if (!file_exists($siteInfoFile)) {
    file_put_contents($siteInfoFile, json_encode($defaultSiteInfo));
}

// Thèmes disponibles et thème par défaut
$availableThemes = ['light', 'dark'];
$defaultTheme = 'light';

// Langues disponibles et langue par défaut (pour l’interface)
$defaultLanguage = 'en';

// Chemin local pour l'avatar par défaut
$defaultAvatar = 'themes/default-avatar.png';
?>
