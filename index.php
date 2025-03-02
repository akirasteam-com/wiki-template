<?php
session_start();
include_once 'config.php';

// Load site information
$siteInfo = json_decode(file_get_contents($siteInfoFile), true);
if (!isset($siteInfo['registration_enabled'])) {
    $siteInfo['registration_enabled'] = true;
}

// Theme management
if (isset($_GET['theme']) && in_array($_GET['theme'], $availableThemes)) {
    $theme = $_GET['theme'];
    setcookie('theme', $theme, time() + (86400 * 30), "/");
} elseif (isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], $availableThemes)) {
    $theme = $_COOKIE['theme'];
} else {
    $theme = $defaultTheme;
}

// Language management
if (isset($_GET['lang']) && in_array($_GET['lang'], 'en')) {
    $lang_code = $_GET['lang'];
    setcookie('lang', $lang_code, time() + (86400 * 30), "/");
} else {
    $lang_code = $defaultLanguage;
}

// Translation array (English)
$lang = [
    'en' => [
        'site_title'      => $siteInfo['siteName'],
        'home'            => "Home",
        'create_article'  => "Create Article",
        'login'           => "Login",
        'register'        => "Register",
        'profile'         => "My Profile",
        'logout'          => "Logout",
        'edit_article'    => "Edit Article",
        'rename_article'  => "Rename Article",
        'delete_article'  => "Delete Article",
        'export_article'  => "Export Article",
        'search'          => "Search",
        'history'         => "History",
        'view_revision'   => "View Revision",
        'tutorial'        => "Tutorial",
        'welcome_message' => "Welcome to My Wiki. Use the sidebar to navigate or create a new article.",
        'tuto_config'     => "To configure the site, click on 'Site Configuration' in the menu. Here you can update your settings and the homepage content.",
        'tuto_article'    => "To create an article, click on 'Create Article' in the menu. Fill in the title and content (Markdown supported) and save your article.",
        'stats_title'     => "Your Publication Statistics"
    ]
];
$L = $lang[$lang_code];

// Retrieve article slug and action
$articleSlug = isset($_GET['article']) ? $_GET['article'] : 'Home';
$action      = isset($_GET['action'])  ? $_GET['action']  : 'view';

/**
 * -----------------------------------------------------
 * Utility Functions (User & Article Management)
 * -----------------------------------------------------
 */

// User management functions
function getUsers() {
    global $usersFile;
    $users = json_decode(file_get_contents($usersFile), true);
    return is_array($users) ? $users : [];
}
function saveUsers($users) {
    global $usersFile;
    file_put_contents($usersFile, json_encode($users));
}
function getUser($username) {
    $users = getUsers();
    return isset($users[$username]) ? $users[$username] : null;
}
function isLoggedIn() {
    return isset($_SESSION['user']);
}
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php?action=login");
        exit;
    }
}

// Article management functions
function sanitizeSlug($title) {
    $slug = str_replace(' ', '_', $title);
    return preg_replace('/[^A-Za-z0-9_]/', '', $slug);
}
function getArticlePath($slug) {
    global $dataDir;
    return $dataDir . '/' . $slug . '.json';
}
function getArticle($slug) {
    $path = getArticlePath($slug);
    if (file_exists($path)) {
        return json_decode(file_get_contents($path), true);
    }
    return null;
}
function saveArticle($slug, $article) {
    return file_put_contents(getArticlePath($slug), json_encode($article));
}
function deleteArticle($slug) {
    $path = getArticlePath($slug);
    if (file_exists($path)) {
        unlink($path);
    }
}
function listArticles() {
    global $dataDir;
    $articles = [];
    foreach (glob($dataDir . '/*.json') as $file) {
        $article = json_decode(file_get_contents($file), true);
        if ($article) {
            $articles[] = $article;
        }
    }
    return $articles;
}
// Return the 5 most recent archived articles
function listArchivedArticles() {
    $articles = listArticles();
    $archived = array_filter($articles, function($a) {
        return isset($a['archived']) && $a['archived'] == true;
    });
    usort($archived, function($a, $b) {
        return $b['created'] - $a['created'];
    });
    return array_slice($archived, 0, 5);
}
// Return the 5 most recent non-archived articles
function listRecentArticles() {
    $articles = listArticles();
    $recent = array_filter($articles, function($a) {
        return !isset($a['archived']) || $a['archived'] == false;
    });
    usort($recent, function($a, $b) {
        return $b['created'] - $a['created'];
    });
    return array_slice($recent, 0, 5);
}

/**
 * -----------------------------------------------------
 * Display Functions (Header, Sidebar, Footer)
 * -----------------------------------------------------
 */

// Improved Sidebar design
function renderSidebar() {
    global $dataDir;
    ?>
    <div class="sidebar card p-3 mb-4">
        <h5 class="card-title mb-3"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Navigation</h5>
        <div class="list-group mb-3">
            <a href="index.php" class="list-group-item list-group-item-action">
                <i class="bi bi-house-door-fill me-2"></i>Home
            </a>
            <a href="index.php?action=tutorial" class="list-group-item list-group-item-action">
                <i class="bi bi-info-circle-fill me-2"></i>Tutorial
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="index.php?action=import" class="list-group-item list-group-item-action">
                    <i class="bi bi-upload me-2"></i>Import Article
                </a>
            <?php endif; ?>
        </div>
        <h5 class="card-title mb-3"><i class="bi bi-clock-history me-2"></i>Recent Articles</h5>
        <div class="list-group mb-3">
            <?php 
            $recentArticles = listRecentArticles();
            if (!empty($recentArticles)):
                foreach ($recentArticles as $art):
                    $slug = sanitizeSlug($art['title']);
                    ?>
                    <a href="index.php?article=<?= urlencode($slug) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-file-earmark-text me-2"></i><?= htmlspecialchars($art['title']) ?>
                    </a>
                <?php 
                endforeach;
            else:
                echo '<p class="text-muted small">No recent articles.</p>';
            endif;
            ?>
        </div>
        <h5 class="card-title mb-3"><i class="bi bi-clock-history me-2"></i>Articles</h5>
        <div class="list-group mb-3">
            <?php 
            $recentArticles = listArticles();
            if (!empty($recentArticles)):
                foreach ($recentArticles as $art):
                    $slug = sanitizeSlug($art['title']);
                    ?>
                    <a href="index.php?article=<?= urlencode($slug) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-file-earmark-text me-2"></i><?= htmlspecialchars($art['title']) ?>
                    </a>
                <?php 
                endforeach;
            else:
                echo '<p class="text-muted small">No recent articles.</p>';
            endif;
            ?>
        </div>
        <h5 class="card-title mb-3"><i class="bi bi-archive me-2"></i>Archived Articles</h5>
        <div class="list-group">
            <?php 
            $archivedArticles = listArchivedArticles();
            if (!empty($archivedArticles)):
                foreach ($archivedArticles as $art):
                    $slug = sanitizeSlug($art['title']);
                    ?>
                    <a href="index.php?article=<?= urlencode($slug) ?>" class="list-group-item list-group-item-action">
                        <i class="bi bi-archive me-2"></i><?= htmlspecialchars($art['title']) ?>
                    </a>
                <?php 
                endforeach;
            else:
                echo '<p class="text-muted small">No archived articles.</p>';
            endif;
            ?>
        </div>
    </div>
    <?php
}

// Modern, futuristic navbar
function renderHeader($title, $theme) {
    global $lang_code, $L, $siteInfo;
    ?>
    <!DOCTYPE html>
    <html lang="<?= htmlspecialchars($lang_code) ?>">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($title) ?> - <?= htmlspecialchars($L['site_title']) ?></title>
        <!-- Bootstrap 5 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            /* Futuristic design for navbar and page */
            body {
                background-color: #f8f9fa;
                color: #343a40;
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            }
            .navbar {
                background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            .navbar-brand, .nav-link, footer a {
                color: #f7931e !important;
                font-weight: bold;
            }
            .nav-link:hover, footer a:hover {
                color: #ffffff !important;
            }
            .sidebar {
                border-radius: 0.25rem;
                padding: 1rem;
            }
            .list-group-item {
                border: none;
                border-bottom: 1px solid #eee;
            }
            .list-group-item:last-child {
                border-bottom: none;
            }
            .card {
                border: none;
                border-radius: 0.5rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .btn-primary {
                background-color: #f7931e;
                border-color: #f7931e;
            }
            .btn-primary:hover {
                background-color: #e67e22;
                border-color: #e67e22;
            }
            /* Dynamic search suggestions styling */
            #searchSuggestions {
                position: absolute;
                top: 100%;
                left: 0;
                z-index: 1050;
                width: 100%;
                background: #ffffff;
                border: 1px solid #ddd;
                border-top: none;
                max-height: 300px;
                overflow-y: auto;
                box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            }
            #searchSuggestions a {
                display: block;
                padding: 0.5rem 0.75rem;
                color: #343a40;
                text-decoration: none;
            }
            #searchSuggestions a:hover {
                background-color: #f1f1f1;
            }
            footer {
                background-color: #0f2027;
                color: #f7931e;
            }
        </style>
    </head>
    <body class="d-flex flex-column min-vh-100">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="index.php"><i class="bi bi-house-door-fill me-2"></i><?= htmlspecialchars($L['site_title']) ?></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <?php if (isLoggedIn()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?action=create_article"><i class="bi bi-pencil-square me-1"></i><?= htmlspecialchars($L['create_article']) ?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?action=profile"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($L['profile']) ?></a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?action=config_site"><i class="bi bi-gear-fill me-1"></i>Config</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <!-- Dynamic Search Bar -->
                    <form class="d-flex position-relative me-3">
                        <input class="form-control me-2" type="search" placeholder="<?= htmlspecialchars($L['search']) ?>" aria-label="Search" id="searchInput" autocomplete="off">
                        <div id="searchSuggestions" class="list-group"></div>
                    </form>
                    <ul class="navbar-nav">
                        <?php if (isLoggedIn()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?action=logout"><i class="bi bi-box-arrow-right me-1"></i><?= htmlspecialchars($L['logout']) ?> (<?= htmlspecialchars($_SESSION['user']) ?>)</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="index.php?action=login"><i class="bi bi-box-arrow-in-right me-1"></i><?= htmlspecialchars($L['login']) ?></a>
                            </li>
                            <?php if ($siteInfo['registration_enabled']) : ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="index.php?action=register"><i class="bi bi-person-plus me-1"></i><?= htmlspecialchars($L['register']) ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
        <!-- Main Content -->
        <main class="container my-4 flex-grow-1">
            <div class="row">
                <aside class="col-lg-3 mb-4">
                    <div class="sidebar">
                        <?php renderSidebar(); ?>
                    </div>
                </aside>
                <section class="col-lg-9">
    <?php
}
function renderFooter() {
    global $L;
    ?>
                </section>
            </div>
        </main>
        <footer class="py-3">
            <div class="container d-flex justify-content-between align-items-center">
                <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($L['site_title']) ?>. All rights reserved.</span>
                <a href="index.php?action=legal" class="text-decoration-none"><i class="bi bi-file-earmark-text me-1"></i>Legal Notice</a>
            </div>
        </footer>
        <!-- Bootstrap Bundle -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
        <!-- Dynamic Search Script -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('searchInput');
                const suggestions = document.getElementById('searchSuggestions');

                searchInput.addEventListener('input', function() {
                    const query = this.value.trim();
                    if(query.length === 0) {
                        suggestions.innerHTML = '';
                        return;
                    }
                    fetch(`index.php?action=suggest&query=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            suggestions.innerHTML = '';
                            if(data.length > 0) {
                                data.forEach(item => {
                                    const a = document.createElement('a');
                                    a.href = `index.php?article=${encodeURIComponent(item.slug)}`;
                                    a.textContent = item.title;
                                    a.classList.add('list-group-item');
                                    suggestions.appendChild(a);
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching search suggestions:', error);
                        });
                });

                document.addEventListener('click', function(e) {
                    if (!searchInput.contains(e.target)) {
                        suggestions.innerHTML = '';
                    }
                });
            });
        </script>
    </body>
    </html>
    <?php
}

// Error page
function errorPage($message) {
    global $theme;
    renderHeader("Error", $theme);
    echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($message) . '</div>';
    renderFooter();
    exit;
}

/**
 * -----------------------------------------------------
 * Action Handling
 * -----------------------------------------------------
 */

switch ($action) :

    // --- JSON Search Suggestions ---
    case 'suggest':
        header('Content-Type: application/json');
        $query = trim($_GET['query'] ?? '');
        $results = [];
        if (!empty($query)) {
            foreach (glob($dataDir . '/*.json') as $file) {
                $article = json_decode(file_get_contents($file), true);
                if ($article && stripos($article['title'], $query) !== false) {
                    $results[] = [
                        'title' => $article['title'],
                        'slug'  => sanitizeSlug($article['title'])
                    ];
                }
            }
        }
        echo json_encode($results);
        exit;
        break;

    // --- Site Configuration (for logged in users) ---
    case 'config_site':
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $siteInfo['siteName']             = trim($_POST['siteName'] ?? $siteInfo['siteName']);
            $siteInfo['tagline']              = trim($_POST['tagline'] ?? $siteInfo['tagline']);
            $siteInfo['about']                = trim($_POST['about'] ?? $siteInfo['about']);
            $siteInfo['legal_notice']         = trim($_POST['legal_notice'] ?? $siteInfo['legal_notice']);
            $siteInfo['privacy_policy']       = trim($_POST['privacy_policy'] ?? $siteInfo['privacy_policy']);
            $siteInfo['contact']              = trim($_POST['contact'] ?? $siteInfo['contact']);
            $siteInfo['accueil_content']      = trim($_POST['accueil_content'] ?? $siteInfo['accueil_content']);
            $siteInfo['registration_enabled'] = isset($_POST['registration_enabled']);
            file_put_contents($siteInfoFile, json_encode($siteInfo));
            header("Location: index.php");
            exit;
        }
        renderHeader("Site Configuration", $theme);
        ?>
        <div class="card mb-3">
            <div class="card-body">
                <form method="post" action="index.php?action=config_site">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-gear-fill me-1"></i> Site Name:</label>
                        <input type="text" name="siteName" class="form-control" value="<?= htmlspecialchars($siteInfo['siteName']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-card-text me-1"></i> Tagline:</label>
                        <input type="text" name="tagline" class="form-control" value="<?= htmlspecialchars($siteInfo['tagline']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-info-circle me-1"></i> About:</label>
                        <textarea name="about" class="form-control" rows="5"><?= htmlspecialchars($siteInfo['about']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-file-earmark-text me-1"></i> Legal Notice:</label>
                        <textarea name="legal_notice" class="form-control" rows="5"><?= htmlspecialchars($siteInfo['legal_notice']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-shield-lock-fill me-1"></i> Privacy Policy:</label>
                        <textarea name="privacy_policy" class="form-control" rows="5"><?= htmlspecialchars($siteInfo['privacy_policy']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope me-1"></i> Contact:</label>
                        <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($siteInfo['contact']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-house-door-fill me-1"></i> Home Page Content (Markdown supported):</label>
                        <textarea name="accueil_content" class="form-control" rows="7"><?= htmlspecialchars($siteInfo['accueil_content'] ?? '') ?></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="registration_enabled" class="form-check-input" <?= $siteInfo['registration_enabled'] ? 'checked' : '' ?>>
                        <label class="form-check-label">Enable Registration</label>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
                </form>
            </div>
        </div>
        <?php
        renderFooter();
        break;
    
    // --- Login ---
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $users    = getUsers();
            if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
                $_SESSION['user'] = $username;
                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid credentials.";
            }
        }
        renderHeader("Login", $theme);
        if (!empty($error)) {
            echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($error) . '</div>';
        }
        ?>
        <div class="card mb-3">
            <div class="card-body">
                <form method="post" action="index.php?action=login">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person-fill me-1"></i> Username:</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-key-fill me-1"></i> Password:</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-1"></i>Login</button>
                </form>
            </div>
        </div>
        <?php
        renderFooter();
        break;
    
    // --- Logout ---
    case 'logout':
        session_destroy();
        header("Location: index.php");
        exit;
        break;
    
    // --- Register ---
    case 'register':
        if (!$siteInfo['registration_enabled']) {
            errorPage("Registration is currently disabled.");
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirm  = $_POST['confirm'] ?? '';
            if ($password !== $confirm) {
                $error = "Passwords do not match.";
            } elseif (empty($username) || empty($password)) {
                $error = "All fields are required.";
            } else {
                $users = getUsers();
                if (isset($users[$username])) {
                    $error = "Username already exists.";
                } else {
                    $users[$username] = [
                        'password' => password_hash($password, PASSWORD_DEFAULT),
                        'avatar'   => ''
                    ];
                    saveUsers($users);
                    $_SESSION['user'] = $username;
                    header("Location: index.php");
                    exit;
                }
            }
        }
        renderHeader("Register", $theme);
        if (!empty($error)) {
            echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($error) . '</div>';
        }
        ?>
        <div class="card mb-3">
            <div class="card-body">
                <form method="post" action="index.php?action=register">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person-plus-fill me-1"></i> Username:</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-key-fill me-1"></i> Password:</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-key-fill me-1"></i> Confirm Password:</label>
                        <input type="password" name="confirm" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-person-plus-fill me-1"></i>Register</button>
                </form>
            </div>
        </div>
        <?php
        renderFooter();
        break;
    
    // --- Create Article (requires login) ---
    case 'create_article':
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title   = trim($_POST['title'] ?? '');
            $content = $_POST['content'] ?? '';
            if (empty($title) || empty($content)) {
                $error = "All fields are required.";
            } else {
                $slug = sanitizeSlug($title);
                if (file_exists(getArticlePath($slug))) {
                    $error = "An article with that title already exists.";
                } else {
                    $timestamp = time();
                    $article = [
                        'title'    => $title,
                        'slug'     => $slug,
                        'content'  => $content,
                        'author'   => $_SESSION['user'],
                        'created'  => $timestamp,
                        'modified' => $timestamp,
                        'archived' => false
                    ];
                    saveArticle($slug, $article);
                    header("Location: index.php?article=" . urlencode($slug));
                    exit;
                }
            }
        }
        renderHeader("Create Article", $theme);
        if (!empty($error)) {
            echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($error) . '</div>';
        }
        ?>
        <div class="card mb-3">
            <div class="card-body">
                <form method="post" action="index.php?action=create_article">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-card-heading me-1"></i> Title:</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-pencil-fill me-1"></i> Content (Markdown supported):</label>
                        <textarea name="content" class="form-control" rows="10" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle-fill me-1"></i>Create</button>
                </form>
            </div>
        </div>
        <?php
        renderFooter();
        break;
    
    // --- Edit Article ---
    case 'edit_article':
        requireLogin();
        if (empty($articleSlug)) {
            header("Location: index.php");
            exit;
        }
        $article = getArticle($articleSlug);
        if (!$article) {
            renderHeader("Error", $theme);
            echo '<div class="alert alert-danger" role="alert">Article not found.</div>';
            renderFooter();
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Save article history
            $timestamp = date('YmdHis');
            global $historyDir;
            $backupArticle = $article;
            $backupArticle['modified_by'] = $_SESSION['user'];
            $backupFile = $historyDir . '/' . $articleSlug . '_' . $timestamp . '.json';
            file_put_contents($backupFile, json_encode($backupArticle));
            
            $content = $_POST['content'] ?? '';
            if (empty($content)) {
                $error = "Content cannot be empty.";
            } else {
                $article['content']  = $content;
                $article['modified'] = time();
                saveArticle($articleSlug, $article);
                header("Location: index.php?article=" . urlencode($articleSlug));
                exit;
            }
        }
        renderHeader("Edit Article: " . $article['title'], $theme);
        if (!empty($error)) {
            echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($error) . '</div>';
        }
        ?>
        <div class="card mb-3">
            <div class="card-body">
                <form method="post" action="index.php?action=edit_article&article=<?= urlencode($articleSlug) ?>">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-pencil-square me-1"></i> Content (Markdown supported):</label>
                        <textarea name="content" class="form-control" rows="10" required><?= htmlspecialchars($article['content']) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
                </form>
            </div>
        </div>
        <?php
        renderFooter();
        break;
    
    // --- Rename Article ---
    case 'rename_article':
        requireLogin();
        if (empty($articleSlug)) {
            header("Location: index.php");
            exit;
        }
        $article = getArticle($articleSlug);
        if (!$article) {
            renderHeader("Error", $theme);
            echo '<div class="alert alert-danger" role="alert">Article not found.</div>';
            renderFooter();
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newTitle = trim($_POST['title'] ?? '');
            if (empty($newTitle)) {
                $error = "Title cannot be empty.";
            } else {
                $newSlug = sanitizeSlug($newTitle);
                if (file_exists(getArticlePath($newSlug)) && $newSlug != $articleSlug) {
                    $error = "An article with that title already exists.";
                } else {
                    $article['title'] = $newTitle;
                    $article['slug']  = $newSlug;
                    if ($newSlug != $articleSlug) {
                        saveArticle($newSlug, $article);
                        deleteArticle($articleSlug);
                        header("Location: index.php?article=" . urlencode($newSlug));
                        exit;
                    } else {
                        saveArticle($articleSlug, $article);
                        header("Location: index.php?article=" . urlencode($articleSlug));
                        exit;
                    }
                }
            }
        }
        renderHeader("Rename Article: " . $article['title'], $theme);
        if (!empty($error)) {
            echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($error) . '</div>';
        }
        ?>
        <div class="card mb-3">
            <div class="card-body">
                <form method="post" action="index.php?action=rename_article&article=<?= urlencode($articleSlug) ?>">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-pencil-square me-1"></i> New Title:</label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($article['title']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Rename</button>
                </form>
            </div>
        </div>
        <?php
        renderFooter();
        break;
    
    // --- Article History ---
    case 'history':
        if (empty($articleSlug)) {
            errorPage("No article specified for history.");
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
        renderHeader("Article History: " . $articleSlug, $theme);
        if (!empty($revisions)) {
            echo '<ul class="list-group">';
            foreach ($revisions as $file => $ts) {
                $displayTime = date('m/d/Y H:i:s', $ts);
                echo '<li class="list-group-item"><a href="index.php?action=view_revision&rev=' . urlencode(basename($file)) . '&article=' . urlencode($articleSlug) . '"><i class="bi bi-clock-history me-1"></i>' . htmlspecialchars($displayTime) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="alert alert-info" role="alert">No history available for this article.</div>';
        }
        renderFooter();
        break;
    
    // --- Delete Article ---
    case 'delete_article':
        requireLogin();
        if (empty($articleSlug)) {
            header("Location: index.php");
            exit;
        }
        $article = getArticle($articleSlug);
        if (!$article) {
            renderHeader("Error", $theme);
            echo '<div class="alert alert-danger" role="alert">Article not found.</div>';
            renderFooter();
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            deleteArticle($articleSlug);
            header("Location: index.php");
            exit;
        }
        renderHeader("Delete Article: " . $article['title'], $theme);
        ?>
        <div class="alert alert-warning" role="alert">
            Are you sure you want to delete this article? This action cannot be undone.
        </div>
        <form method="post" action="index.php?action=delete_article&article=<?= urlencode($articleSlug) ?>">
            <button type="submit" class="btn btn-danger"><i class="bi bi-trash-fill me-1"></i>Confirm Deletion</button>
        </form>
        <?php
        renderFooter();
        break;
    
    // --- Archive/Unarchive Article ---
    case 'archive_article':
        requireLogin();
        if (empty($articleSlug)) {
            header("Location: index.php");
            exit;
        }
        $article = getArticle($articleSlug);
        if (!$article) {
            errorPage("Article not found.");
        }
        // Toggle archive flag
        $article['archived'] = isset($article['archived']) && $article['archived'] == true ? false : true;
        saveArticle($articleSlug, $article);
        header("Location: index.php?article=" . urlencode($articleSlug));
        exit;
        break;
    
    // --- Export Article ---
    case 'export':
        if (empty($articleSlug)) {
            errorPage("No article specified for export.");
        }
        $article = getArticle($articleSlug);
        if (!$article) {
            errorPage("Article not found.");
        }
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . sanitizeSlug($article['title']) . '.md"');
        echo "# " . $article['title'] . "\n\n";
        echo $article['content'];
        exit;
        break;
    
    // --- Import Article (only for logged in users) ---
    case 'import':
        requireLogin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_FILES['importFile']) && $_FILES['importFile']['error'] == 0) {
                $fileContent = file_get_contents($_FILES['importFile']['tmp_name']);
                $data = json_decode($fileContent, true);
                if (!$data) {
                    $error = "Invalid JSON file.";
                } elseif (!isset($data['title']) || !isset($data['content'])) {
                    $error = "The file does not conform to the required structure.";
                } else {
                    if (!isset($data['author']) || empty($data['author'])) {
                        $data['author'] = $_SESSION['user'];
                    }
                    if (!isset($data['created'])) { $data['created'] = time(); }
                    if (!isset($data['modified'])) { $data['modified'] = time(); }
                    $data['archived'] = false;
                    $slug = sanitizeSlug($data['title']);
                    if (file_exists(getArticlePath($slug))) {
                        $error = "An article with that title already exists.";
                    } else {
                        saveArticle($slug, $data);
                        header("Location: index.php?article=" . urlencode($slug));
                        exit;
                    }
                }
            } else {
                $error = "Please upload a valid JSON file.";
            }
        }
        renderHeader("Import Article", $theme);
        if (!empty($error)) {
            echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($error) . '</div>';
        }
        ?>
        <div class="card mb-3">
            <div class="card-body">
                <h3><i class="bi bi-upload me-1"></i>Import Article (JSON file)</h3>
                <form method="post" action="index.php?action=import" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Select JSON File:</label>
                        <input type="file" name="importFile" class="form-control" accept=".json" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Import</button>
                </form>
            </div>
        </div>
        <?php
        renderFooter();
        break;
    
    // --- View Article Revision ---
    case 'view_revision':
        if (!isset($_GET['rev'])) {
            errorPage("No revision specified.");
        }
        $revFile = $historyDir . '/' . basename($_GET['rev']);
        if (!file_exists($revFile)) {
            errorPage("The requested revision does not exist.");
        }
        $revision = json_decode(file_get_contents($revFile), true);
        renderHeader("Article Revision: " . $articleSlug, $theme);
        if ($revision) {
            echo '<article>';
            echo '<h2>' . htmlspecialchars($revision['title']) . ' <small>(Revised)</small></h2>';
            require_once 'Parsedown.php';
            $Parsedown = new Parsedown();
            echo $Parsedown->text($revision['content']);
            echo '<p class="text-muted"><em>Revised by ' . (isset($revision['modified_by']) ? htmlspecialchars($revision['modified_by']) : "Unknown") . ' on ' . date('m/d/Y H:i:s', $revision['modified']) . '</em></p>';
            echo '</article>';
        } else {
            echo '<div class="alert alert-danger" role="alert">Unable to read the revision.</div>';
        }
        echo '<a href="index.php?article=' . urlencode($articleSlug) . '" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left me-1"></i>Back to Article</a>';
        renderFooter();
        break;
    
    // --- User Profile (with real statistics and year selection) ---
    case 'profile':
        $profileUser = isset($_GET['user']) ? $_GET['user'] : (isLoggedIn() ? $_SESSION['user'] : '');
        if (empty($profileUser)) {
            renderHeader("Profile", $theme);
            echo '<div class="alert alert-info" role="alert">No profile to display.</div>';
            renderFooter();
            exit;
        }
        $userData = getUser($profileUser);
        if (!$userData) {
            renderHeader("Profile", $theme);
            echo '<div class="alert alert-info" role="alert">User not found.</div>';
            renderFooter();
            exit;
        }
        // Retrieve distinct years from user's articles
        $allArticles = listArticles();
        $userArticles = array_filter($allArticles, function($a) use ($profileUser) {
            return ($a['author'] === $profileUser);
        });
        $years = [];
        foreach ($userArticles as $art) {
            $yr = date('Y', $art['created']);
            if (!in_array($yr, $years)) {
                $years[] = $yr;
            }
        }
        rsort($years);
        $selectedYear = isset($_GET['year']) ? intval($_GET['year']) : (count($years) > 0 ? $years[0] : date('Y'));
        // Calculate articles created in the selected year
        $monthlyCounts = array_fill(1, 12, 0);
        foreach ($userArticles as $art) {
            if(date('Y', $art['created']) == $selectedYear){
                $month = date('n', $art['created']);
                $monthlyCounts[$month]++;
            }
        }
        // Calculate modifications: count an article as modified if its modified date is greater than created date
        $modificationCounts = array_fill(1, 12, 0);
        foreach ($userArticles as $art) {
            if ($art['modified'] > $art['created'] && date('Y', $art['modified']) == $selectedYear) {
                $month = date('n', $art['modified']);
                $modificationCounts[$month]++;
            }
        }
        $chartLabels = json_encode(["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]);
        $chartDataCreated = json_encode(array_values($monthlyCounts));
        $chartDataModified = json_encode(array_values($modificationCounts));
        renderHeader("Profile of " . htmlspecialchars($profileUser), $theme);
        ?>
        <div class="card mb-3">
            <div class="row g-0">
                <div class="col-md-4">
                    <img src="<?= htmlspecialchars($userData['avatar'] ? $userData['avatar'] : $defaultAvatar) ?>" class="img-fluid rounded-start" alt="Avatar">
                </div>
                <div class="col-md-8">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($profileUser) ?></h5>
                        <p class="card-text">Articles Published: <?= count($userArticles) ?></p>
                        <form method="get" action="index.php" class="mb-0">
                            <input type="hidden" name="action" value="profile">
                            <input type="hidden" name="user" value="<?= htmlspecialchars($profileUser) ?>">
                            <label for="yearSelect" class="form-label">Select Year:</label>
                            <select name="year" id="yearSelect" class="form-select" onchange="this.form.submit()">
                                <?php foreach ($years as $yr): ?>
                                    <option value="<?= $yr ?>" <?= ($yr == $selectedYear) ? 'selected' : '' ?>><?= $yr ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <h4 class="card-title"><?= htmlspecialchars($L['stats_title']) ?></h4>
                <canvas id="statsChart" width="400" height="200"></canvas>
            </div>
        </div>
        <section>
            <h3><i class="bi bi-journal-text me-1"></i>User Publications</h3>
            <?php
            if (!empty($userArticles)) {
                echo '<ul class="list-group">';
                foreach ($userArticles as $pub) {
                    $slug = sanitizeSlug($pub['title']);
                    echo '<li class="list-group-item"><a href="index.php?article=' . urlencode($slug) . '"><i class="bi bi-file-earmark-text me-1"></i>' . htmlspecialchars($pub['title']) . '</a></li>';
                }
                echo '</ul>';
            } else {
                echo '<div class="alert alert-info" role="alert">No publications found.</div>';
            }
            ?>
        </section>
        <!-- Chart.js Library -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const ctx = document.getElementById('statsChart').getContext('2d');
            const statsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= $chartLabels ?>,
                    datasets: [{
                        label: 'Articles Created',
                        data: <?= $chartDataCreated ?>,
                        backgroundColor: 'rgba(247, 147, 30, 0.5)',
                        borderColor: 'rgba(247, 147, 30, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Articles Modified',
                        data: <?= $chartDataModified ?>,
                        backgroundColor: 'rgba(52, 152, 219, 0.5)',
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        </script>
        <?php
        renderFooter();
        break;
    
    // --- Edit User Profile ---
    case 'edit_profile':
        requireLogin();
        $currentUser = $_SESSION['user'];
        $userData = getUser($currentUser);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $avatar = trim($_POST['avatar'] ?? '');
            $users = getUsers();
            $users[$currentUser]['avatar'] = $avatar;
            saveUsers($users);
            header("Location: index.php?action=profile");
            exit;
        }
        renderHeader("Edit Profile", $theme);
        ?>
        <div class="card mb-3">
            <div class="card-body">
                <form method="post" action="index.php?action=edit_profile">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-image me-1"></i> Avatar (local path):</label>
                        <input type="text" name="avatar" class="form-control" value="<?= htmlspecialchars($userData['avatar']) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
                </form>
            </div>
        </div>
        <?php
        renderFooter();
        break;
    
    // --- Classic Article Search ---
    case 'search':
        renderHeader("Search", $theme);
        $query = trim($_GET['query'] ?? '');
        ?>
        <form method="get" action="index.php" class="mb-3">
            <input type="hidden" name="action" value="search">
            <div class="input-group">
                <input type="text" name="query" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($query) ?>" required>
                <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i>Search</button>
            </div>
        </form>
        <?php
        if (!empty($query)) {
            echo '<h2>Results for: ' . htmlspecialchars($query) . '</h2>';
            $results = [];
            foreach (glob($dataDir . '/*.json') as $file) {
                $article = json_decode(file_get_contents($file), true);
                if ($article && (stripos($article['title'], $query) !== false || stripos($article['content'], $query) !== false)) {
                    $results[] = $article;
                }
            }
            if (count($results) > 0) {
                echo '<ul class="list-group">';
                foreach ($results as $art) {
                    $slug = sanitizeSlug($art['title']);
                    echo '<li class="list-group-item"><a href="index.php?article=' . urlencode($slug) . '"><i class="bi bi-file-earmark-text me-1"></i>' . htmlspecialchars($art['title']) . '</a></li>';
                }
                echo '</ul>';
            } else {
                echo '<div class="alert alert-info" role="alert">No results found.</div>';
            }
        }
        renderFooter();
        break;
    
    // --- Legal Notice Page ---
    case 'legal':
        renderHeader("Legal Notice", $theme);
        echo '<h2>Legal Notice</h2>';
        if (!empty($siteInfo['legal_notice'])) {
            require_once 'Parsedown.php';
            $Parsedown = new Parsedown();
            echo $Parsedown->text($siteInfo['legal_notice']);
        } else {
            echo '<div class="alert alert-info" role="alert">No legal notice defined.</div>';
        }
        renderFooter();
        break;
    
    // --- Tutorial Page ---
    case 'tutorial':
        renderHeader("Tutorial", $theme);
        ?>
        <div class="card mb-3">
            <div class="card-body">
                <h3><i class="bi bi-info-circle-fill me-1"></i>Site Configuration Tutorial</h3>
                <p><?= htmlspecialchars($L['tuto_config']) ?></p>
                <hr>
                <h3 class="mt-4"><i class="bi bi-pencil-square me-1"></i>Article Creation Tutorial</h3>
                <p><?= htmlspecialchars($L['tuto_article']) ?></p>
            </div>
        </div>
        <?php
        renderFooter();
        break;

    default:
        // --- Display Home or Article ---
        if ($articleSlug === 'Home') {
            renderHeader("Home", $theme);
            ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h2><?= htmlspecialchars($siteInfo['siteName']) ?></h2>
                    <p class="lead"><?= htmlspecialchars($siteInfo['tagline']) ?></p>
                    <hr>
                    <h4>Tutorial</h4>
                    <p><strong>Site Configuration:</strong> <?= htmlspecialchars($L['tuto_config']) ?></p>
                    <p><strong>Article Creation:</strong> <?= htmlspecialchars($L['tuto_article']) ?></p>
                </div>
            </div>
            <?php
            if (!empty($siteInfo['accueil_content'])) {
                require_once 'Parsedown.php';
                $Parsedown = new Parsedown();
                echo '<article>' . $Parsedown->text($siteInfo['accueil_content']) . '</article>';
            } else {
                echo '<section>';
                echo '<h3>About</h3>';
                echo '<p>' . nl2br(htmlspecialchars($siteInfo['about'])) . '</p>';
                echo '</section>';
            }
            // Display the 5 most recent archived articles below the main content
            $archived = listArchivedArticles();
            if (!empty($archived)) {
                echo '<div class="mt-5"><h4><i class="bi bi-archive me-1"></i>Archived Articles</h4><div class="list-group">';
                foreach ($archived as $art) {
                    $slug = sanitizeSlug($art['title']);
                    echo '<a href="index.php?article=' . urlencode($slug) . '" class="list-group-item list-group-item-action"><i class="bi bi-archive me-2"></i>' . htmlspecialchars($art['title']) . '</a>';
                }
                echo '</div></div>';
            }
        } else {
            if (file_exists(getArticlePath($articleSlug))) {
                $article = getArticle($articleSlug);
                renderHeader($article['title'], $theme);
                require_once 'Parsedown.php';
                $Parsedown = new Parsedown();
                echo '<article>';
                echo '<h2>' . htmlspecialchars($article['title']) . '</h2>';
                echo $Parsedown->text($article['content']);
                echo '<div class="d-flex align-items-center mt-3">';
                $author = getUser($article['author']);
                $avatar = ($author && !empty($author['avatar'])) ? $author['avatar'] : $defaultAvatar;
                echo '<img src="' . htmlspecialchars($avatar) . '" alt="Author Avatar" class="rounded-circle me-2" style="width:50px; height:50px;">';
                echo '<div><p class="mb-0">Published by: <a href="index.php?action=profile&user=' . urlencode($article['author']) . '">' . htmlspecialchars($article['author']) . '</a></p></div>';
                echo '</div>';
                echo '</article>';
                echo '<div class="mt-3">';
                echo '<a class="btn btn-secondary" href="index.php?action=history&article=' . urlencode($articleSlug) . '"><i class="bi bi-clock-history me-1"></i>' . htmlspecialchars($L['history']) . '</a> ';
                if (isLoggedIn() && $_SESSION['user'] === $article['author']) {
                    echo '<a class="btn btn-info" href="index.php?action=export&article=' . urlencode($articleSlug) . '"><i class="bi bi-download me-1"></i>' . htmlspecialchars($L['export_article'] ?? "Export") . '</a> ';
                    echo ' <a class="btn btn-warning" href="index.php?action=archive_article&article=' . urlencode($articleSlug) . '"><i class="bi ' . ((isset($article['archived']) && $article['archived']) ? "bi-box-arrow-in-down" : "bi-box-arrow-in-up") . ' me-1"></i>' . ((isset($article['archived']) && $article['archived']) ? "Unarchive" : "Archive") . '</a>';
                    echo ' <a class="btn btn-primary" href="index.php?action=edit_article&article=' . urlencode($articleSlug) . '"><i class="bi bi-pencil me-1"></i>' . htmlspecialchars($L['edit_article']) . '</a>';
                    echo ' <a class="btn btn-secondary" href="index.php?action=rename_article&article=' . urlencode($articleSlug) . '"><i class="bi bi-pencil-square me-1"></i>' . htmlspecialchars($L['rename_article']) . '</a>';
                    echo ' <a class="btn btn-danger" href="index.php?action=delete_article&article=' . urlencode($articleSlug) . '"><i class="bi bi-trash-fill me-1"></i>' . htmlspecialchars($L['delete_article']) . '</a>';
                }
                echo '</div>';
            } else {
                renderHeader("Home", $theme);
                echo '<div class="alert alert-info" role="alert">' . htmlspecialchars($L['welcome_message']) . '</div>';
            }
        }
        renderFooter();
        break;
endswitch;
?>
