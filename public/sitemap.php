<?php
header("Content-Type: application/xml; charset=utf-8");

// Deteksi URL otomatis (http/https sareng domain)
$isSecure = false;
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $isSecure = true;
} elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $isSecure = true;
}
$protocol = $isSecure ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$baseUrl = "$protocol://$host";

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= $baseUrl ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

<?php
// --- AUTODETECT MODE: DEV VS PROD ---
$domain = $_SERVER['HTTP_HOST'] ?? '';
$is_local = (strpos($domain, 'localhost') !== false || strpos($domain, '127.0.0.1') !== false);

if ($is_local) {
    // Mode Lokal (Docker): Mundur ka folder utama
    $configPath = __DIR__ . '/../engine/config.json';
    $dbPathPrefix = __DIR__ . '/../';
} else {
    // Mode Live (InfinityFree): Jalur langsung sajajar
    $configPath = __DIR__ . '/config.json';
    $dbPathPrefix = __DIR__ . '/';
}
// ------------------------------------

// 1. Muat Konfigurasi améh jalurna dinamis
$config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
$dbPathRelative = $config['db_name'] ?? 'data/database_agc.sqlite';
$dbPath = $dbPathPrefix . $dbPathRelative;

try {
    // 2. Sambungkeun ka Database nganggo jalur dinamis
    $db = new PDO("sqlite:" . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 3. Candak sadaya slug
    $stmt = $db->query("SELECT slug, created_at FROM articles ORDER BY created_at DESC");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $date = date('Y-m-d', strtotime($row['created_at']));
        echo "    <url>\n";
        echo "        <loc>{$baseUrl}/" . htmlspecialchars($row['slug']) . "</loc>\n";
        echo "        <lastmod>{$date}</lastmod>\n";
        echo "        <changefreq>weekly</changefreq>\n";
        echo "        <priority>0.8</priority>\n";
        echo "    </url>\n";
    }
} catch (Exception $e) { 
    // Améh pami database error, urang tiasa terang alesanna ku cara ningali View Source di browser
    echo "    \n";
}
?>
</urlset>