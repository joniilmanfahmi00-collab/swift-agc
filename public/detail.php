<?php
// ALARM ERROR (Bisa dipaéhan pami tos leres-leres aman)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ambil slug tina URL
$request_uri = $_SERVER['REQUEST_URI'];
$slug = trim(parse_url($request_uri, PHP_URL_PATH), '/');

// --- AUTODETECT MODE: DEV VS PROD ---
$domain = $_SERVER['HTTP_HOST'] ?? '';
$is_local = (strpos($domain, 'localhost') !== false || strpos($domain, '127.0.0.1') !== false);

if ($is_local) {
    // Mode Lokal (Docker): Jalur mundur ka folder utama
    $configPath = __DIR__ . '/../engine/config.json';
    $dbPathPrefix = __DIR__ . '/../';
} else {
    // Mode Live (InfinityFree): Jalur langsung sajajar di htdocs
    $configPath = __DIR__ . '/config.json';
    $dbPathPrefix = __DIR__ . '/';
}
// ------------------------------------

// Muat Konfigurasi
$config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$siteName = $config['site_name'] ?? 'Portal Berita';
$author = $config['author'] ?? 'Admin';

// Nyaluyukeun path database
$dbPathRelative = $config['db_name'] ?? 'data/database_agc.sqlite';
$dbPath = $dbPathPrefix . $dbPathRelative;

if (empty($slug) || $slug === 'index.php' || $slug === 'detail.php') {
    include 'index.php';
    exit;
}

if ($slug === 'sitemap.xml') {
    include 'sitemap.php';
    exit;
}

try {
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $db->prepare('SELECT * FROM articles WHERE slug = :slug LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$article) {
        header("HTTP/1.0 404 Not Found");
        $error_msg = "Maaf, artikel yang Anda cari tidak ditemukan.";
    }
} catch (Exception $e) {
    die("Kesalahan Database: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $article ? htmlspecialchars($article['title']) . " - " . htmlspecialchars($siteName) : "404 Tidak Ditemukan" ?></title>
    <?php if($article): ?>
        <meta name="description" content="<?= htmlspecialchars(substr(strip_tags($article['content']), 0, 160)) ?>...">
        <meta name="author" content="<?= htmlspecialchars($config['author'] ?? 'Admin') ?>">
    <?php endif; ?>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    
    <style>
        /* Styling améh hasilna rapih jiga maca di blog profésional */
        .prose h1, .prose h2, .prose h3 { font-weight: bold; margin-top: 2rem; margin-bottom: 1rem; color: #1f2937; }
        .prose h2 { font-size: 1.5rem; }
        .prose h3 { font-size: 1.25rem; }
        .prose p { margin-bottom: 1.25rem; line-height: 1.8; color: #4b5563; }
        .prose ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1.25rem; color: #4b5563; }
        .prose strong { color: #111827; font-weight: 700; }
        .prose img { max-width: 100%; border-radius: 0.5rem; margin: 1.5rem 0; }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">
    <nav class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-10">
        <div class="max-w-4xl mx-auto px-4 py-4">
            <a href="/" class="text-md font-semibold text-indigo-600 flex items-center gap-2 hover:text-indigo-800 transition group">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <?= htmlspecialchars($siteName) ?>
            </a>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <!-- Breadcrumbs -->
        <nav class="flex mb-8 text-sm text-gray-500" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="/" class="hover:text-indigo-600 transition-colors">Beranda</a>
                </li>
                <?php if ($article): ?>
                <li>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-gray-400 mx-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
                        <span class="ml-1 md:ml-2 font-medium text-gray-900 truncate max-w-[200px] md:max-w-md"><?= htmlspecialchars($article['title']) ?></span>
                    </div>
                </li>
                <?php endif; ?>
            </ol>
        </nav>
        
        <!-- Artikel Detail -->
        <?php if ($article): ?>
            <article class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ scrolled: false }" @scroll.window="scrolled = (window.pageYOffset > 20)">
                <div class="w-full h-64 md:h-[400px] bg-gray-200">
                    <img src="<?= htmlspecialchars($article['image_url']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" class="w-full h-full object-cover">
                </div>
                
                <div class="p-6 md:p-12">
                    <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 mb-6 leading-tight"><?= htmlspecialchars($article['title']) ?></h1>
                    
                    <div class="flex items-center text-sm text-gray-500 mb-10 border-b border-gray-100 pb-6">
                        <span>🗓️ <?= date('d M Y', strtotime($article['created_at'])) ?></span>
                        <span class="mx-3">•</span>
                        <!-- Panggil author dihandap -->
                        <span>✍️ <?= htmlspecialchars($config['author'] ?? 'Admin') ?></span>
                    </div>
                    
                    <div class="prose max-w-none" id="content-area"></div>
                </div>
            </article>
        <?php else: ?>
            <div class="text-center py-20 bg-white rounded-2xl shadow-sm border border-gray-100">
                <h1 class="text-6xl mb-4">🥲</h1>
                <h2 class="text-2xl font-bold text-gray-800"><?= $error_msg ?></h2>
                <a href="/" class="mt-6 inline-block bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">Kembali ke Beranda</a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Related Articles -->
    <?php
    if ($article) {
        try {
            $stmtRelated = $db->prepare('SELECT title, slug, image_url, created_at FROM articles WHERE slug != :slug ORDER BY RANDOM() LIMIT 3');
            $stmtRelated->execute([':slug' => $slug]);
            $relatedArticles = $stmtRelated->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $relatedArticles = [];
        }
    }
    ?>

    <?php if (!empty($relatedArticles)): ?>
    <section class="max-w-4xl mx-auto px-4 py-12">
        <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
            Related Post
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($relatedArticles as $rel): ?>
                <a href="/<?= htmlspecialchars($rel['slug']) ?>" class="group bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all">
                    <div class="h-32 overflow-hidden">
                        <img src="<?= htmlspecialchars($rel['image_url']) ?>" alt="<?= htmlspecialchars($rel['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="p-4">
                        <h4 class="text-sm font-bold text-gray-800 group-hover:text-indigo-600 line-clamp-2"><?= htmlspecialchars($rel['title']) ?></h4>
                        <p class="text-xs text-gray-400 mt-2"><?= date('d M Y', strtotime($rel['created_at'])) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="max-w-4xl mx-auto px-4 py-8 text-center text-gray-500 text-sm border-t border-gray-200 mt-12">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved.</p>
    </footer>

    <?php if ($article): ?>
    <script>
        // Tarik tulisan ti database nganggo fungsi json_encode améh aman ti error kutip
        var rawMarkdown = <?= json_encode($article['content']) ?>;
        
        // Sulap Markdown janten HTML, teras pasang di jero div #content-area
        document.getElementById('content-area').innerHTML = marked.parse(rawMarkdown);
    </script>
    <?php endif; ?>
</body>
</html>