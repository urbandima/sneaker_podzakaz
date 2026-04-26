<?php
declare(strict_types=1);

/**
 * МойСклад → локальные файлы: скачать изображения товаров
 *
 * Usage:
 *   php scripts/download_ms_images.php              # все товары с ms_images_json
 *   php scripts/download_ms_images.php --limit=50   # первые 50 товаров
 *   php scripts/download_ms_images.php --limit=0    # без ограничений (все)
 *   php scripts/download_ms_images.php --force      # перекачать даже если файл есть
 */

ini_set('memory_limit', '256M');

const DB_HOST    = '127.0.0.1';
const DB_NAME    = 'sneakerhead';
const DB_USER    = 'sneaker';
const DB_PASS    = 'secret';
const MS_BASE    = 'https://api.moysklad.ru/api/remap/1.2';
const UPLOAD_DIR = __DIR__ . '/../frontend/web/uploads/products';
const UPLOAD_REL = 'uploads/products';

// CLI args
$limit = 100;
$force = false;
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = (int)$m[1];
    } elseif ($arg === '--force') {
        $force = true;
    }
}

// DB
$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Credentials: .env → env vars → app_setting table
function parseEnvFile(string $path): array
{
    $result = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim(preg_replace('/#.*$/', '', $val), " \t'\"");
        if ($key !== '') $result[$key] = $val;
    }
    return $result;
}
$dotenv     = file_exists(__DIR__ . '/../.env') ? parseEnvFile(__DIR__ . '/../.env') : [];
$msLogin    = $dotenv['MS_LOGIN']    ?? getenv('MS_LOGIN')    ?: null;
$msPassword = $dotenv['MS_PASSWORD'] ?? getenv('MS_PASSWORD') ?: null;
if (!$msLogin || !$msPassword) {
    $creds      = $pdo->query("SELECT `key`, value FROM app_setting WHERE section = 'moysklad' AND `key` IN ('login','password')")->fetchAll(PDO::FETCH_KEY_PAIR);
    $msLogin    = $msLogin    ?? ($creds['login']    ?? null);
    $msPassword = $msPassword ?? ($creds['password'] ?? null);
}
if (!$msLogin || !$msPassword) {
    die("Ошибка: MS_LOGIN и MS_PASSWORD не найдены в .env, переменных окружения или таблице app_setting\n");
}
$msAuth = 'Basic ' . base64_encode($msLogin . ':' . $msPassword);

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0775, true);
}

$stats = ['processed' => 0, 'downloaded' => 0, 'skipped' => 0, 'errors' => 0];

// HTTP helpers

function msGet(string $url): array
{
    global $msAuth;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => ['Authorization: ' . $msAuth, 'Accept-Encoding: gzip'],
        CURLOPT_ENCODING       => 'gzip',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($body === false || $code === 0) throw new RuntimeException("cURL: $err");
    if ($code >= 400) {
        $d   = json_decode($body, true) ?? [];
        $msg = $d['errors'][0]['error'] ?? mb_substr($body, 0, 200);
        throw new RuntimeException("HTTP $code: $msg");
    }
    return json_decode($body, true) ?? [];
}

function fetchBinaryUrl(string $url, bool $withAuth = false): string
{
    global $msAuth;
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 120,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
    ];
    if ($withAuth) {
        $opts[CURLOPT_HTTPHEADER] = ['Authorization: ' . $msAuth];
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($body === false || $code === 0) throw new RuntimeException("cURL: $err");
    if ($code >= 400) throw new RuntimeException("HTTP $code");
    return $body;
}

function msDownloadBinary(string $authUrl, ?string $cdnFallbackUrl = null): string
{
    // Try auth'd endpoint first
    try {
        return fetchBinaryUrl($authUrl, true);
    } catch (RuntimeException $e) {
        if ($cdnFallbackUrl && str_contains($e->getMessage(), '415')) {
            // МойСклад download endpoint blocked (WAF/plan restriction) — use pre-signed CDN URL
            return fetchBinaryUrl($cdnFallbackUrl, false);
        }
        throw $e;
    }
}

function logLine(string $msg): void
{
    echo '[' . date('H:i:s') . '] ' . $msg . "\n";
}

function safeFilename(string $name, string $suffix = ''): string
{
    $name = mb_strtolower($name);
    $name = preg_replace('/[^a-z0-9_\-\.]+/', '-', $name);
    $name = trim($name, '-');
    $name = preg_replace('/-+/', '-', $name);
    if ($suffix) {
        $ext  = pathinfo($name, PATHINFO_EXTENSION);
        $base = $ext ? substr($name, 0, -strlen($ext) - 1) : $name;
        $name = $base . '-' . $suffix . ($ext ? '.' . $ext : '');
    }
    return $name ?: 'image' . ($suffix ? '-' . $suffix : '');
}

function resolveImageRows(array $msImagesJson): array
{
    if (isset($msImagesJson['__collection__'])) {
        $data = msGet($msImagesJson['__collection__']);
        return $data['rows'] ?? [];
    }
    $rows = [];
    foreach ($msImagesJson as $href) {
        if (is_string($href)) {
            $rows[] = ['meta' => ['href' => $href]];
        }
    }
    return $rows;
}

function detectExtension(string $binary, string $fallback = 'jpg'): string
{
    if (str_starts_with($binary, "\xFF\xD8\xFF"))      return 'jpg';
    if (str_starts_with($binary, "\x89PNG\r\n\x1A\n")) return 'png';
    if (str_starts_with($binary, 'GIF8'))              return 'gif';
    $head = substr($binary, 0, 16);
    if (str_starts_with($head, 'RIFF') && str_contains($head, 'WEBP')) return 'webp';
    return $fallback;
}

// Query products
$sql = 'SELECT id, name, ms_images_json, main_image FROM product WHERE ms_images_json IS NOT NULL';
if ($limit > 0) {
    $sql .= ' LIMIT ' . $limit;
}
$products = $pdo->query($sql)->fetchAll();
$total    = count($products);

logLine("Найдено товаров с ms_images_json: {$total}" . ($limit > 0 ? " (лимит {$limit})" : ' (без лимита)'));
if ($force) logLine("Режим --force: перезапись существующих файлов");
echo "\n";

$existingStmt  = $pdo->prepare('SELECT COUNT(*) FROM product_image WHERE product_id = ?');
$insertImgStmt = $pdo->prepare(
    'INSERT INTO product_image (product_id, image, is_main, sort_order, created_at)
     VALUES (?, ?, ?, ?, UNIX_TIMESTAMP())'
);
$updateMainStmt = $pdo->prepare('UPDATE product SET main_image = ? WHERE id = ?');

foreach ($products as $i => $product) {
    $pid  = (int)$product['id'];
    $name = $product['name'] ?? "product-{$pid}";
    $num  = $i + 1;
    echo "[{$num}/{$total}] #{$pid} {$name}\n";
    $stats['processed']++;

    if (!$force) {
        $existingStmt->execute([$pid]);
        $existingCount = (int)$existingStmt->fetchColumn();
        if ($existingCount > 0) {
            echo "        → уже есть {$existingCount} изображений, пропускаем\n";
            $stats['skipped']++;
            continue;
        }
    }

    $msImagesJson = json_decode($product['ms_images_json'], true);
    if (!$msImagesJson) {
        echo "        → пустой ms_images_json, пропускаем\n";
        $stats['skipped']++;
        continue;
    }

    try {
        $rows = resolveImageRows($msImagesJson);
    } catch (RuntimeException $e) {
        echo "        → ошибка коллекции: " . $e->getMessage() . "\n";
        $stats['errors']++;
        continue;
    }

    if (empty($rows)) {
        echo "        → нет изображений в МС\n";
        $stats['skipped']++;
        continue;
    }

    $sortOrder  = 0;
    $firstSaved = null;

    foreach ($rows as $row) {
        $dlHref   = $row['meta']['downloadHref'] ?? null;
        $metaHref = $row['meta']['href'] ?? null;
        // Pre-signed CDN URL for miniature — no auth needed, used as fallback
        $miniCdn  = $row['miniature']['downloadHref'] ?? null;

        if (!$dlHref && $metaHref) {
            try {
                $imgMeta  = msGet($metaHref);
                $dlHref   = $imgMeta['meta']['downloadHref'] ?? null;
                $miniCdn  = $miniCdn ?? ($imgMeta['miniature']['downloadHref'] ?? null);
                $row      = array_merge($row, $imgMeta);
            } catch (RuntimeException $e) {
                echo "        → ошибка мета: " . $e->getMessage() . "\n";
                $stats['errors']++;
                continue;
            }
        }

        if (!$dlHref && !$miniCdn) {
            echo "        → нет downloadHref и miniature CDN, пропускаем\n";
            $stats['errors']++;
            continue;
        }

        $rawFilename = $row['filename'] ?? $row['title'] ?? ('image-' . ($sortOrder + 1));
        // Strip query string from filename (МС sometimes stores URLs as filenames)
        $rawFilename = preg_replace('/\?.*$/', '', $rawFilename);
        $urlForHash  = $dlHref ?? $miniCdn;
        $safeBase    = safeFilename(pathinfo($rawFilename, PATHINFO_FILENAME), substr(md5($urlForHash), 0, 8));
        $ext         = strtolower(pathinfo($rawFilename, PATHINFO_EXTENSION)) ?: null;
        // Sanitize ext too (should be simple: jpg, png, etc.)
        $ext         = $ext ? preg_replace('/[^a-z0-9]/', '', $ext) : null;

        $binary = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $binary = msDownloadBinary($dlHref ?? $miniCdn, $miniCdn);
                break;
            } catch (RuntimeException $e) {
                if ($attempt === 3) {
                    echo "        → ошибка скачивания: " . $e->getMessage() . "\n";
                    $stats['errors']++;
                }
                sleep(2);
            }
        }
        if ($binary === null) continue;

        if (!$ext) {
            $ext = detectExtension($binary);
        }

        $filename  = $safeBase . '.' . $ext;
        $localPath = UPLOAD_DIR . '/' . $filename;

        if (!$force && file_exists($localPath)) {
            $filename  = $safeBase . '-' . substr(md5($urlForHash), 0, 6) . '.' . $ext;
            $localPath = UPLOAD_DIR . '/' . $filename;
        }

        file_put_contents($localPath, $binary);
        $relPath = UPLOAD_REL . '/' . $filename;

        $isMain = ($sortOrder === 0) ? 1 : 0;
        $insertImgStmt->execute([$pid, $relPath, $isMain, $sortOrder]);

        if ($sortOrder === 0) {
            $firstSaved = $relPath;
        }

        echo "        [{$sortOrder}] {$filename} (" . round(strlen($binary) / 1024) . " KB)\n";
        $stats['downloaded']++;
        $sortOrder++;
        usleep(100_000);
    }

    if ($firstSaved && empty($product['main_image'])) {
        $updateMainStmt->execute([$firstSaved, $pid]);
    }

    usleep(300_000);
}

echo "\n";
echo "═══════════════════════════════════════════\n";
echo " Итого:\n";
echo "   Обработано:  {$stats['processed']}\n";
echo "   Скачано:     {$stats['downloaded']}\n";
echo "   Пропущено:   {$stats['skipped']}\n";
echo "   Ошибок:      {$stats['errors']}\n";
echo "═══════════════════════════════════════════\n";
