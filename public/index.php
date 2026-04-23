<?php
// ALARM ERROR (Tiasa dipaéhan/dihapus pami webna tos live leres-leres lancar)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- AUTODETECT MODE: DEV VS PROD (Radar Néng Gemini) ---
$domain = $_SERVER['HTTP_HOST'] ?? '';
$is_local = (strpos($domain, 'localhost') !== false || strpos($domain, '127.0.0.1') !== false);

if ($is_local) {
    // Mode Lokal (Docker): Mundur ka folder utama (../)
    $configPath = __DIR__ . '/../config.json';
    $dbPathRelative = 'data/database_agc.sqlite'; // Sasuai config
    $dbPath = __DIR__ . '/../' . $dbPathRelative;
} else {
    // Mode Live (InfinityFree / VPS): Jalur langsung sajajar di htdocs
    $configPath = __DIR__ . '/config.json';
    $dbPathRelative = 'data/database_agc.sqlite'; // Sasuai config
    $dbPath = __DIR__ . '/' . $dbPathRelative;
}
// --------------------------------------------------------

// 1. Muat Konfigurasi tina JSON (Supados teu hardcoded)
$config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$siteName = $config['site_name'] ?? 'Portal Berita';

try {
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Logika Pencarian
    $searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
    $whereClause = '';
    $params = [];

    if (!empty($searchQuery)) {
        $whereClause = "WHERE title LIKE :q OR content LIKE :q";
        $params[':q'] = "%$searchQuery%";
    }

    // Konfigurasi Pagination
    $limit = 6; // Jumlah artikel per halaman (Gentos angkana didieu)
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    // Etang total artikel (nyaluyukeun sareng pencarian)
    $countStmt = $db->prepare("SELECT COUNT(*) FROM articles $whereClause");
    $countStmt->execute($params);
    $total_articles = $countStmt->fetchColumn();
    $total_pages = ceil($total_articles / $limit);

    // Candak data artikel sesuai halaman
    $sql = "SELECT * FROM articles $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?></title>
    <meta name="description" content="<?= htmlspecialchars($config['meta_description'] ?? '') ?>">
    <!-- Favicon 32 format png -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32x32.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">
    <nav class="bg-white shadow-sm border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/" class="text-2xl font-bold text-indigo-600 flex items-center gap-2">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                <?= htmlspecialchars($siteName) ?>
            </a>
            <!-- Search Bar Mini di Navigasi -->
            <form action="/" method="GET" class="hidden md:flex">
                <input type="text" name="q" placeholder="Search..." value="<?= htmlspecialchars($searchQuery) ?>" class="px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-lg hover:bg-indigo-700 transition">Search</button>
            </form>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex justify-between items-end mb-8 border-b pb-2 border-gray-200">
            <h1 class="text-3xl font-bold text-gray-900">
                <?= !empty($searchQuery) ? "Search Result: '" . htmlspecialchars($searchQuery) . "'" : "Last Post" ?>
            </h1>
        </div>

        <!-- Mobile Search Bar -->
        <form action="/" method="GET" class="md:hidden mb-8 flex">
            <input type="text" name="q" placeholder="Cari artikel..." value="<?= htmlspecialchars($searchQuery) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-1 focus:ring-indigo-500">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-r-lg hover:bg-indigo-700 transition">Go</button>
        </form>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if(count($articles) > 0): ?>
                <?php foreach($articles as $row): ?>
                    <a href="/<?= htmlspecialchars($row['slug']) ?>" class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden border border-gray-100 flex flex-col group">
                        <div class="h-48 overflow-hidden bg-gray-200">
                            <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        </div>
                        <div class="p-5 flex-1 flex flex-col">
                            <h2 class="text-xl font-semibold mb-2 text-gray-800 group-hover:text-indigo-600 line-clamp-2"><?= htmlspecialchars($row['title']) ?></h2>
                            <p class="text-sm text-gray-500 mt-auto"><?= date('d M Y', strtotime($row['created_at'])) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-16 bg-white rounded-xl border border-dashed border-gray-300">
                    <p class="text-gray-500 text-lg">None of article. Please make sure you run swift.py before!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="mt-12 flex justify-center items-center gap-3">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>&q=<?= urlencode($searchQuery) ?>" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition shadow-sm">← Sebelumnya</a>
            <?php endif; ?>

            <span class="text-gray-500 font-medium bg-white px-4 py-2 rounded-xl border border-dashed border-gray-300">Hal <?= $page ?> / <?= $total_pages ?></span>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>&q=<?= urlencode($searchQuery) ?>" class="px-5 py-2.5 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 text-gray-700 font-medium transition shadow-sm">Selanjutnya →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-100 mt-12">
        <div class="max-w-6xl mx-auto px-4 py-8 text-center text-gray-500 text-sm">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>