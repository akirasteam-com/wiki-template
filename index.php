<?php
session_start();
include_once 'config.php';

$siteInfo = json_decode(file_get_contents($siteInfoFile), true);

if (!isset($siteInfo['registration_enabled'])) {
    $siteInfo['registration_enabled'] = true;
}

if (isset($_GET['theme']) && in_array($_GET['theme'], $availableThemes)) {
    $theme = $_GET['theme'];
    setcookie('theme', $theme, time() + (86400 * 30), "/");
} elseif (isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], $availableThemes)) {
    $theme = $_COOKIE['theme'];
} else {
    $theme = $defaultTheme;
}

if (isset($_GET['lang']) && in_array($_GET['lang'], $availableLanguages)) {
    $lang_code = $_GET['lang'];
    setcookie('lang', $lang_code, time() + (86400 * 30), "/");
} elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $availableLanguages)) {
    $lang_code = $_COOKIE['lang'];
} else {
    $lang_code = $defaultLanguage;
}

$lang = [
    'fr' => [
        'site_title'       => $siteInfo['siteName'],
        'home'             => "Accueil",
        'create_article'   => "Créer un article",
        'login'            => "Connexion",
        'register'         => "Inscription",
        'profile'          => "Mon profil",
        'logout'           => "Déconnexion",
        'edit_article'     => "Modifier l'article",
        'rename_article'   => "Renommer l'article",
        'delete_article'   => "Supprimer l'article",
        'search'           => "Recherche",
        'history'          => "Historique",
        'view_revision'    => "Voir la révision",
        'welcome_message'  => "Bienvenue sur Mon Wiki. Utilisez la sidebar pour naviguer ou créez un nouvel article.",
        'markdown_tutorial_title'   => "",
        'markdown_tutorial_content' => ""
    ],
    'en' => [
        'site_title'       => $siteInfo['siteName'],
        'home'             => "Home",
        'create_article'   => "Create Article",
        'login'            => "Login",
        'register'         => "Register",
        'profile'          => "My Profile",
        'logout'           => "Logout",
        'edit_article'     => "Edit Article",
        'rename_article'   => "Rename Article",
        'delete_article'   => "Delete Article",
        'search'           => "Search",
        'history'          => "History",
        'view_revision'    => "View Revision",
        'welcome_message'  => "Welcome to My Wiki. Use the sidebar to navigate or create a new article.",
        'markdown_tutorial_title'   => "",
        'markdown_tutorial_content' => ""
    ]
];
$L = $lang[$lang_code];

$articleSlug = isset($_GET['article']) ? $_GET['article'] : 'Accueil';
$action = isset($_GET['action']) ? $_GET['action'] : 'view';

function getUsersFunc() {
    global $usersFile;
    $json = file_get_contents($usersFile);
    $users = json_decode($json, true);
    return is_array($users) ? $users : [];
}
function saveUsersFunc($users) {
    global $usersFile;
    file_put_contents($usersFile, json_encode($users));
}
function getUserFunc($username) {
    $users = getUsersFunc();
    return isset($users[$username]) ? $users[$username] : null;
}
function isLoggedInFunc() {
    return isset($_SESSION['user']);
}
function requireLoginFunc() {
    if (!isLoggedInFunc()) {
        header("Location: index.php?action=login");
        exit;
    }
}
function parseInternalLinksFunc($content) {
    return preg_replace_callback('/\[\[([^\]]+)\]\]/', function($matches) {
        $pageName = trim($matches[1]);
        return '<a href="index.php?article=' . urlencode($pageName) . '">' . htmlspecialchars($pageName) . '</a>';
    }, $content);
}

function sanitizeSlug($title) {
    $slug = str_replace(' ', '_', $title);
    return preg_replace('/[^A-Za-z0-9_]/', '', $slug);
}
function getArticlePathFunc($slug) {
    global $dataDir;
    return $dataDir . '/' . $slug . '.json';
}
function getArticleFunc($slug) {
    $path = getArticlePathFunc($slug);
    if (file_exists($path)) {
        $data = file_get_contents($path);
        return json_decode($data, true);
    }
    return null;
}
function saveArticleFunc($slug, $article) {
    $path = getArticlePathFunc($slug);
    return file_put_contents($path, json_encode($article));
}
function deleteArticleFunc($slug) {
    $path = getArticlePathFunc($slug);
    if (file_exists($path)) {
        unlink($path);
    }
}
function listArticlesFunc() {
    global $dataDir;
    $articles = [];
    foreach (glob($dataDir . '/*.json') as $file) {
        $data = file_get_contents($file);
        $article = json_decode($data, true);
        if ($article) {
            $articles[] = $article;
        }
    }
    return $articles;
}

function renderSidebarFunc() {
    global $dataDir, $L;
    echo '<h3><i class="bi bi-list-ul"></i> ' . htmlspecialchars($L['home']) . '</h3>';
    echo '<ul>';
    foreach (glob($dataDir . '/*.json') as $file) {
        $data = file_get_contents($file);
        $article = json_decode($data, true);
        if ($article) {
            $slug = sanitizeSlug($article['title']);
            echo '<li><a href="index.php?article=' . urlencode($slug) . '">' . htmlspecialchars($article['title']) . '</a></li>';
        }
    }
    echo '</ul>';
}

function renderHeaderFunc($title, $theme) {
    global $availableThemes, $L, $lang_code, $siteInfo;
    ?>
    <!DOCTYPE html>
    <html lang="<?php echo htmlspecialchars($lang_code); ?>">
    <head>
      <meta charset="UTF-8">
      <title><?php echo htmlspecialchars($title); ?> - <?php echo htmlspecialchars($L['site_title']); ?></title>
      <link rel="stylesheet" href="themes/minimal.css">
      <!-- Bootstrap Icons -->
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
      <header>
        <h1><i class="bi bi-journal-text"></i> <?php echo htmlspecialchars($L['site_title']); ?></h1>
        <nav>
          <a class="btn btn-secondary" href="index.php"><?php echo htmlspecialchars($L['home']); ?></a>
          <?php if (isLoggedInFunc()): ?>
              <a class="btn btn-secondary" href="index.php?action=create_article"><?php echo htmlspecialchars($L['create_article']); ?></a>
              <a class="btn btn-secondary" href="index.php?action=profile"><?php echo htmlspecialchars($L['profile']); ?></a>
              <a class="btn btn-secondary" href="index.php?action=config_site">Website config</a>
              <a class="btn btn-secondary" href="index.php?action=logout"><?php echo htmlspecialchars($L['logout']); ?> (<?php echo htmlspecialchars($_SESSION['user']); ?>)</a>
          <?php else: ?>
              <a class="btn btn-secondary" href="index.php?action=login"><?php echo htmlspecialchars($L['login']); ?></a>
              <?php 
              // Afficher le lien d'inscription uniquement si registration_enabled est vrai et si l'utilisateur n'est pas connecté
              if ($siteInfo['registration_enabled']) { ?>
                  <a class="btn btn-secondary" href="index.php?action=register"><?php echo htmlspecialchars($L['register']); ?></a>
              <?php } 
              ?>
          <?php endif; ?>
          <span style="margin-left: auto;"></span>
          <span style="margin-left: 10px;">
            <a class="btn btn-secondary" href="?lang=fr">FR</a>
            <a class="btn btn-secondary" href="?lang=en">EN</a>
          </span>
        </nav>
        <hr>
      </header>
      <div class="container">
        <aside class="sidebar">
          <?php renderSidebarFunc(); ?>
        </aside>
        <section class="content">
    <?php
}
function renderFooterFunc() {
    global $L;
    ?>
    <footer>
      <div class="footer-container">
        <div class="footer-left">
          <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($L['site_title']); ?>. Tous droits réservés.</p>
        </div>
        <div class="footer-right">
          <a href="index.php?article=Mentions_legales">Mentions légales</a>
        </div>
      </div>
    </footer>
    </body>
    </html>
    <?php
}
function errorPageFunc($message) {
    global $theme;
    renderHeaderFunc("Erreur", $theme);
    echo '<p style="color:red;">' . htmlspecialchars($message) . '</p>';
    renderFooterFunc();
    exit;
}

switch ($action) {

    case 'config_site':
        requireLoginFunc();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $siteInfo['siteName'] = trim($_POST['siteName'] ?? $siteInfo['siteName']);
            $siteInfo['tagline'] = trim($_POST['tagline'] ?? $siteInfo['tagline']);
            $siteInfo['about'] = trim($_POST['about'] ?? $siteInfo['about']);
            $siteInfo['legal_notice'] = trim($_POST['legal_notice'] ?? $siteInfo['legal_notice']);
            $siteInfo['privacy_policy'] = trim($_POST['privacy_policy'] ?? $siteInfo['privacy_policy']);
            $siteInfo['contact'] = trim($_POST['contact'] ?? $siteInfo['contact']);
            // Nouveau champ pour configurer le contenu de la page d'accueil
            $siteInfo['accueil_content'] = trim($_POST['accueil_content'] ?? $siteInfo['accueil_content']);
            // Option d'inscription
            $siteInfo['registration_enabled'] = isset($_POST['registration_enabled']) ? true : false;
            file_put_contents($siteInfoFile, json_encode($siteInfo));
            header("Location: index.php");
            exit;
        }
        renderHeaderFunc("Configuration du site", $theme);
        ?>
        <form method="post" action="index.php?action=config_site">
          <label>Nom du site: <input type="text" name="siteName" value="<?php echo htmlspecialchars($siteInfo['siteName']); ?>"></label><br><br>
          <label>Slogan: <input type="text" name="tagline" value="<?php echo htmlspecialchars($siteInfo['tagline']); ?>"></label><br><br>
          <label>À propos:<br><textarea name="about" rows="5" style="width:100%;"><?php echo htmlspecialchars($siteInfo['about']); ?></textarea></label><br><br>
          <label>Mentions légales:<br><textarea name="legal_notice" rows="5" style="width:100%;"><?php echo htmlspecialchars($siteInfo['legal_notice']); ?></textarea></label><br><br>
          <label>Politique de confidentialité:<br><textarea name="privacy_policy" rows="5" style="width:100%;"><?php echo htmlspecialchars($siteInfo['privacy_policy']); ?></textarea></label><br><br>
          <label>Contact: <input type="text" name="contact" value="<?php echo htmlspecialchars($siteInfo['contact']); ?>"></label><br><br>

          <label>Contenu de la page d'accueil (Markdown supporté):<br>
            <textarea name="accueil_content" rows="7" style="width:100%;"><?php echo isset($siteInfo['accueil_content']) ? htmlspecialchars($siteInfo['accueil_content']) : ''; ?></textarea>
          </label><br><br>
          <label>
            <input type="checkbox" name="registration_enabled" <?php echo ($siteInfo['registration_enabled'] ? 'checked' : ''); ?>> Autoriser l'inscription
          </label><br><br>
          <input class="btn btn-primary" type="submit" value="Enregistrer">
        </form>
        <?php
        renderFooterFunc();
        break;
    

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $users = getUsersFunc();
            if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
                $_SESSION['user'] = $username;
                header("Location: index.php");
                exit;
            } else {
                $error = "Identifiants incorrects.";
            }
        }
        renderHeaderFunc("Connexion", $theme);
        if (!empty($error)) {
            echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
        }
        ?>
        <form method="post" action="index.php?action=login">
          <label><i class="bi bi-person"></i> Nom d'utilisateur: <input type="text" name="username" required></label><br><br>
          <label><i class="bi bi-key"></i> Mot de passe: <input type="password" name="password" required></label><br><br>
          <input class="btn btn-primary" type="submit" value="Connexion">
        </form>
        <?php
        renderFooterFunc();
        break;

    case 'logout':
        session_destroy();
        header("Location: index.php");
        exit;
        break;

    case 'register':
        if (!$siteInfo['registration_enabled']) {
            errorPageFunc("L'inscription est actuellement désactivée.");
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm'] ?? '';
            if ($password !== $confirm) {
                $error = "Les mots de passe ne correspondent pas.";
            } elseif (empty($username) || empty($password)) {
                $error = "Tous les champs sont obligatoires.";
            } else {
                $users = getUsersFunc();
                if (isset($users[$username])) {
                    $error = "Ce nom d'utilisateur existe déjà.";
                } else {
                    $users[$username] = [
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'avatar' => ''
                    ];
                    saveUsersFunc($users);
                    $_SESSION['user'] = $username;
                    header("Location: index.php");
                    exit;
                }
            }
        }
        renderHeaderFunc("Inscription", $theme);
        if (!empty($error)) {
            echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
        }
        ?>
        <form method="post" action="index.php?action=register">
          <label><i class="bi bi-person-plus"></i> Nom d'utilisateur: <input type="text" name="username" required></label><br><br>
          <label><i class="bi bi-key"></i> Mot de passe: <input type="password" name="password" required></label><br><br>
          <label><i class="bi bi-key"></i> Confirmer le mot de passe: <input type="password" name="confirm" required></label><br><br>
          <input class="btn btn-primary" type="submit" value="S'inscrire">
        </form>
        <?php
        renderFooterFunc();
        break;

    case 'create_article':
        requireLoginFunc();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $content = $_POST['content'] ?? '';
            if (empty($title) || empty($content)) {
                $error = "Tous les champs sont obligatoires.";
            } else {
                $slug = sanitizeSlug($title);
                if (file_exists(getArticlePathFunc($slug))) {
                    $error = "Un article avec ce titre existe déjà.";
                } else {
                    $timestamp = time();
                    $article = [
                        'title'    => $title,
                        'slug'     => $slug,
                        'content'  => $content,
                        'author'   => $_SESSION['user'],
                        'created'  => $timestamp,
                        'modified' => $timestamp
                    ];
                    saveArticleFunc($slug, $article);
                    header("Location: index.php?article=" . urlencode($slug));
                    exit;
                }
            }
        }
        renderHeaderFunc("Créer un article", $theme);
        if (!empty($error)) {
            echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
        }
        ?>
        <form method="post" action="index.php?action=create_article">
          <label><i class="bi bi-card-heading"></i> Titre: <input type="text" name="title" required></label><br><br>
          <label><i class="bi bi-pencil"></i> Contenu (Markdown supporté):</label><br>
          <textarea name="content" rows="15" style="width:100%;" required></textarea><br><br>
          <input class="btn btn-primary" type="submit" value="Créer">
        </form>
        <?php
        renderFooterFunc();
        break;

    case 'edit_article':
        requireLoginFunc();
        if (empty($articleSlug)) {
            header("Location: index.php");
            exit;
        }
        $article = getArticleFunc($articleSlug);
        if (!$article) {
            renderHeaderFunc("Erreur", $theme);
            echo "<p>Article non trouvé.</p>";
            renderFooterFunc();
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $timestamp = date('YmdHis');
            global $historyDir;
            $backupArticle = $article;
            $backupArticle['modified_by'] = $_SESSION['user'];
            $backupFile = $historyDir . '/' . $articleSlug . '_' . $timestamp . '.json';
            file_put_contents($backupFile, json_encode($backupArticle));
            
            $content = $_POST['content'] ?? '';
            if (empty($content)) {
                $error = "Le contenu ne peut pas être vide.";
            } else {
                $article['content'] = $content;
                $article['modified'] = time();
                saveArticleFunc($articleSlug, $article);
                header("Location: index.php?article=" . urlencode($articleSlug));
                exit;
            }
        }
        renderHeaderFunc("Modifier l'article: " . $article['title'], $theme);
        if (!empty($error)) {
            echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
        }
        ?>
        <form method="post" action="index.php?action=edit_article&article=<?php echo urlencode($articleSlug); ?>">
          <label><i class="bi bi-pencil-square"></i> Contenu (Markdown supporté):</label><br>
          <textarea name="content" rows="15" style="width:100%;" required><?php echo htmlspecialchars($article['content']); ?></textarea><br>
          <input class="btn btn-primary" type="submit" value="Enregistrer">
        </form>
        <?php
        renderFooterFunc();
        break;
    
    case 'rename_article':
        requireLoginFunc();
        if (empty($articleSlug)) {
            header("Location: index.php");
            exit;
        }
        $article = getArticleFunc($articleSlug);
        if (!$article) {
            renderHeaderFunc("Erreur", $theme);
            echo "<p>Article non trouvé.</p>";
            renderFooterFunc();
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newTitle = trim($_POST['title'] ?? '');
            if (empty($newTitle)) {
                $error = "Le titre ne peut pas être vide.";
            } else {
                $newSlug = sanitizeSlug($newTitle);
                if (file_exists(getArticlePathFunc($newSlug)) && $newSlug != $articleSlug) {
                    $error = "Un article avec ce titre existe déjà.";
                } else {
                    $article['title'] = $newTitle;
                    $article['slug'] = $newSlug;
                    if ($newSlug != $articleSlug) {
                        saveArticleFunc($newSlug, $article);
                        deleteArticleFunc($articleSlug);
                        header("Location: index.php?article=" . urlencode($newSlug));
                        exit;
                    } else {
                        saveArticleFunc($articleSlug, $article);
                        header("Location: index.php?article=" . urlencode($articleSlug));
                        exit;
                    }
                }
            }
        }
        renderHeaderFunc("Renommer l'article: " . $article['title'], $theme);
        if (!empty($error)) {
            echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
        }
        ?>
        <form method="post" action="index.php?action=rename_article&article=<?php echo urlencode($articleSlug); ?>">
          <label><i class="bi bi-card-heading"></i> Nouveau titre: <input type="text" name="title" value="<?php echo htmlspecialchars($article['title']); ?>" required></label><br><br>
          <input class="btn btn-primary" type="submit" value="Renommer">
        </form>
        <?php
        renderFooterFunc();
        break;

    case 'delete_article':
        requireLoginFunc();
        if (empty($articleSlug)) {
            header("Location: index.php");
            exit;
        }
        $article = getArticleFunc($articleSlug);
        if (!$article) {
            renderHeaderFunc("Erreur", $theme);
            echo "<p>Article non trouvé.</p>";
            renderFooterFunc();
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            deleteArticleFunc($articleSlug);
            header("Location: index.php");
            exit;
        }
        renderHeaderFunc("Supprimer l'article: " . $article['title'], $theme);
        ?>
        <p>Êtes-vous sûr de vouloir supprimer cet article ? Cette action est irréversible.</p>
        <form method="post" action="index.php?action=delete_article&article=<?php echo urlencode($articleSlug); ?>">
          <input class="btn btn-danger" type="submit" value="Confirmer la suppression">
        </form>
        <?php
        renderFooterFunc();
        break;

    case 'history':
        if (empty($articleSlug)) {
            errorPageFunc("Aucun article spécifié pour l'historique.");
        }
        global $historyDir;
        $pattern = $historyDir . '/' . $articleSlug . '_*.json';
        $historyFiles = glob($pattern);
        $revisions = [];
        if ($historyFiles !== false) {
            foreach ($historyFiles as $file) {
                $base = basename($file, '.json');
                if (strlen($base) > 15) {
                    $timestampStr = substr($base, -14);
                    $ts = strtotime(
                        substr($timestampStr, 0, 4) . '-' .
                        substr($timestampStr, 4, 2) . '-' .
                        substr($timestampStr, 6, 2) . ' ' .
                        substr($timestampStr, 8, 2) . ':' .
                        substr($timestampStr, 10, 2) . ':' .
                        substr($timestampStr, 12, 2)
                    );
                    if ($ts !== false) {
                        $revisions[$file] = $ts;
                    }
                }
            }
            arsort($revisions);
        }
        renderHeaderFunc("Historique de l'article: " . $articleSlug, $theme);
        if (!empty($revisions)) {
            echo "<ul>";
            foreach ($revisions as $file => $ts) {
                $displayTime = date('d/m/Y H:i:s', $ts);
                echo '<li><a href="index.php?action=view_revision&rev=' . urlencode(basename($file)) . '&article=' . urlencode($articleSlug) . '">' . htmlspecialchars($displayTime) . '</a></li>';
            }
            echo "</ul>";
        } else {
            echo "<p>Aucun historique disponible pour cet article.</p>";
        }
        renderFooterFunc();
        break;

    case 'view_revision':
        if (!isset($_GET['rev'])) {
            errorPageFunc("Aucune révision spécifiée.");
        }
        $revFile = $historyDir . '/' . basename($_GET['rev']);
        if (!file_exists($revFile)) {
            errorPageFunc("La révision demandée n'existe pas.");
        }
        $revision = json_decode(file_get_contents($revFile), true);
        renderHeaderFunc("Révision de l'article: " . $articleSlug, $theme);
        if ($revision) {
            echo "<article>";
            echo "<h2>" . htmlspecialchars($revision['title']) . " <small>(Révisé)</small></h2>";
            require_once 'Parsedown.php';
            $Parsedown = new Parsedown();
            echo $Parsedown->text($revision['content']);
            echo "<p><em>Révisé par " . (isset($revision['modified_by']) ? htmlspecialchars($revision['modified_by']) : "Inconnu") . " le " . date('d/m/Y H:i:s', $revision['modified']) . "</em></p>";
            echo "</article>";
        } else {
            echo "<p>Impossible de lire la révision.</p>";
        }
        echo '<p><a class="btn btn-secondary" href="index.php?article=' . urlencode($articleSlug) . '">Retour à l\'article</a></p>';
        renderFooterFunc();
        break;

    case 'profile':
        $profileUser = isset($_GET['user']) ? $_GET['user'] : (isLoggedInFunc() ? $_SESSION['user'] : '');
        if (empty($profileUser)) {
            renderHeaderFunc("Profil", $theme);
            echo "<p>Aucun profil à afficher.</p>";
            renderFooterFunc();
            exit;
        }
        $userData = getUserFunc($profileUser);
        if (!$userData) {
            renderHeaderFunc("Profil", $theme);
            echo "<p>Utilisateur non trouvé.</p>";
            renderFooterFunc();
            exit;
        }
        renderHeaderFunc("Profil de " . htmlspecialchars($profileUser), $theme);
        ?>
        <div class="author-info">
          <img src="<?php echo htmlspecialchars($userData['avatar'] ? $userData['avatar'] : $defaultAvatar); ?>" alt="Avatar" class="author-avatar">
          <div class="author-details">
            <p class="author-name"><?php echo htmlspecialchars($profileUser); ?></p>
          </div>
        </div>
        <?php
        echo "<section class='user-publications'><h3>Publications de " . htmlspecialchars($profileUser) . "</h3>";
        $allArticles = listArticlesFunc();
        $userPublications = [];
        foreach ($allArticles as $a) {
            if ($a['author'] === $profileUser) {
                $userPublications[] = $a;
            }
        }
        if (!empty($userPublications)) {
            echo "<ul>";
            foreach ($userPublications as $pub) {
                $slug = sanitizeSlug($pub['title']);
                echo "<li><a href='index.php?article=" . urlencode($slug) . "'>" . htmlspecialchars($pub['title']) . "</a></li>";
            }
            echo "</ul>";
        } else {
            echo "<p>Aucune publication trouvée.</p>";
        }
        echo "</section>";
        
        renderFooterFunc();
        break;
    

    case 'edit_profile':
        requireLoginFunc();
        $currentUser = $_SESSION['user'];
        $userData = getUserFunc($currentUser);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $avatar = trim($_POST['avatar'] ?? '');
            $users = getUsersFunc();
            $users[$currentUser]['avatar'] = $avatar;
            saveUsersFunc($users);
            header("Location: index.php?action=profile");
            exit;
        }
        renderHeaderFunc("Modifier mon profil", $theme);
        ?>
        <form method="post" action="index.php?action=edit_profile">
            <label><i class="bi bi-image"></i> Avatar (chemin local): <input type="text" name="avatar" value="<?php echo htmlspecialchars($userData['avatar']); ?>"></label><br>
            <input class="btn btn-primary" type="submit" value="Enregistrer">
        </form>
        <?php
        renderFooterFunc();
        break;

    case 'search':
        renderHeaderFunc("Recherche", $theme);
        $query = trim($_GET['query'] ?? '');
        ?>
        <form method="get" action="index.php">
            <input type="hidden" name="action" value="search">
            <input type="text" name="query" placeholder="Rechercher..." value="<?php echo htmlspecialchars($query); ?>" required>
            <input class="btn btn-primary" type="submit" value="Rechercher">
        </form>
        <?php
        if (!empty($query)) {
            echo "<h2>Résultats pour : " . htmlspecialchars($query) . "</h2>";
            $results = [];
            foreach (glob($dataDir . '/*.json') as $file) {
                $data = file_get_contents($file);
                $article = json_decode($data, true);
                if ($article) {
                    if (stripos($article['title'], $query) !== false || stripos($article['content'], $query) !== false) {
                        $results[] = $article;
                    }
                }
            }
            if (count($results) > 0) {
                echo "<ul>";
                foreach ($results as $art) {
                    $slug = sanitizeSlug($art['title']);
                    echo '<li><a href="index.php?article=' . urlencode($slug) . '">' . htmlspecialchars($art['title']) . '</a></li>';
                }
                echo "</ul>";
            } else {
                echo "<p>Aucun résultat trouvé.</p>";
            }
        }
        renderFooterFunc();
        break;

    case 'view':
        default:
            if ($articleSlug === 'Accueil') {
                renderHeaderFunc("Accueil", $theme);
                echo "<h2>" . htmlspecialchars($siteInfo['siteName']) . "</h2>";
                echo "<p><em>" . htmlspecialchars($siteInfo['tagline']) . "</em></p>";

                if (!empty($siteInfo['accueil_content'])) {
                    require_once 'Parsedown.php';
                    $Parsedown = new Parsedown();
                    echo "<article>";
                    echo $Parsedown->text($siteInfo['accueil_content']);
                    echo "</article>";
                } else {
                    echo "<section class='about'>";
                    echo "<h3>À propos</h3>";
                    echo "<p>" . nl2br(htmlspecialchars($siteInfo['about'])) . "</p>";
                    echo "</section>";
                }
            }
            else {
                if (file_exists(getArticlePathFunc($articleSlug))) {
                    $article = getArticleFunc($articleSlug);
                    renderHeaderFunc($article['title'], $theme);
                    require_once 'Parsedown.php';
                    $Parsedown = new Parsedown();
                    echo "<article>";
                    echo "<h2>" . htmlspecialchars($article['title']) . "</h2>";
                    echo $Parsedown->text($article['content']);
                    echo "<div class='author-info'>";
                    echo "<img src='" . htmlspecialchars((getUserFunc($article['author']) && !empty(getUserFunc($article['author'])['avatar'])) ? getUserFunc($article['author'])['avatar'] : $defaultAvatar) . "' alt='Avatar de l'auteur' class='author-avatar'>";
                    echo "<div class='author-details'><p class='author-name'>Publié par : <a href='index.php?action=profile&user=" . urlencode($article['author']) . "'>" . htmlspecialchars($article['author']) . "</a></p></div>";
                    echo "</div>";
                    echo "</article>";
                    echo "<p><a class='btn btn-secondary' href='index.php?action=history&article=" . urlencode($articleSlug) . "'><i class='bi bi-clock-history'></i> " . htmlspecialchars($L['history']) . "</a></p>";
                    if (isLoggedInFunc()) {
                        echo "<p>";
                        echo "<a class='btn btn-primary' href='index.php?action=edit_article&article=" . urlencode($articleSlug) . "'><i class='bi bi-pencil'></i> " . htmlspecialchars($L['edit_article']) . "</a> ";
                        echo "<a class='btn btn-secondary' href='index.php?action=rename_article&article=" . urlencode($articleSlug) . "'><i class='bi bi-card-text'></i> " . htmlspecialchars($L['rename_article']) . "</a> ";
                        echo "<a class='btn btn-danger' href='index.php?action=delete_article&article=" . urlencode($articleSlug) . "'><i class='bi bi-trash'></i> " . htmlspecialchars($L['delete_article']) . "</a>";
                        echo "</p>";
                    }
                } else {
                    renderHeaderFunc("Accueil", $theme);
                    echo "<p>" . htmlspecialchars($L['welcome_message']) . "</p>";
                }
            }
            renderFooterFunc();
            break;
        

}
?>
