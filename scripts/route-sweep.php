<?php

/**
 * CMP-413: живой HTTP-прогон всех маршрутов из docs/route-audit/CMP-413-inventory.csv.
 *
 * Делает GET-запрос по каждому маршруту (админ-сессия для admin/*, покупательская
 * сессия для account/*, анонимно для остального) на локально поднятый сервер и
 * фиксирует статус-код. Это НЕ замена ручному POST/форм-тестированию — только
 * быстрый прогон "код вообще не падает при загрузке", как в CMP-410.
 *
 * Использование:
 *   php -S 127.0.0.1:8765 -t frontend/web router.php &
 *   php scripts/route-sweep.php http://127.0.0.1:8765 > docs/route-audit/CMP-413-sweep-results.csv
 *
 * Опциональные env: SWEEP_ADMIN_USER, SWEEP_ADMIN_PASS (по умолчанию dev-креды).
 */

$base = $argv[1] ?? 'http://127.0.0.1:8765';
$csvPath = dirname(__DIR__) . '/docs/route-audit/CMP-413-inventory.csv';

if (!is_file($csvPath)) {
    fwrite(STDERR, "Нет файла реестра: $csvPath. Сначала запустите scripts/route-inventory.php\n");
    exit(1);
}

function httpGet(string $url, string $cookieJar = ''): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    return [$code, $body ?: '', $err];
}

function loginAdmin(string $base, string $cookieJar, string $user, string $pass): bool
{
    [, $html] = httpGet($base . '/admin/login', $cookieJar);
    if (!preg_match('/csrf-token"\s+content="([^"]+)"/', $html, $m)) {
        return false;
    }
    $token = $m[1];
    $ch = curl_init($base . '/admin/login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            '_csrf' => $token,
            'LoginForm[username]' => $user,
            'LoginForm[password]' => $pass,
        ]),
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 302;
}

// --- sample IDs pulled once via a lightweight DB read, used to fill <id:\d+> placeholders ---
function sampleIds(): array
{
    $envFile = dirname(__DIR__) . '/.env';
    $env = [];
    if (is_file($envFile)) {
        foreach (file($envFile) as $line) {
            if (preg_match('/^([A-Z_]+)=(.*)$/', trim($line), $m)) {
                $env[$m[1]] = trim($m[2]);
            }
        }
    }
    $dsn = $env['DB_DSN'] ?? 'mysql:host=127.0.0.1;dbname=cmp410_e2e_clean;charset=utf8mb4';
    $user = $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASSWORD'] ?? '';
    $ids = ['id' => 1, 'productId' => 1, 'sourceId' => 1, 'gridId' => 1, 'purchaseOrderId' => 1, 'buyoutId' => 1];
    try {
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_TIMEOUT => 3]);
        $map = [
            'id' => 'SELECT MIN(id) FROM `order`',
            'productId' => 'SELECT MIN(id) FROM product',
        ];
        foreach ($map as $key => $sql) {
            $v = $pdo->query($sql)->fetchColumn();
            if ($v) {
                $ids[$key] = (int) $v;
            }
        }
    } catch (\Throwable $e) {
        fwrite(STDERR, "sampleIds(): DB недоступна, использую ID=1 для всех плейсхолдеров: {$e->getMessage()}\n");
    }
    return $ids;
}

function fillPlaceholders(string $url, array $ids): string
{
    return preg_replace_callback('/<([a-zA-Z]+):[^>]+>/', function ($m) use ($ids) {
        return (string) ($ids[$m[1]] ?? 1);
    }, $url);
}

$ids = sampleIds();

$adminJar = tempnam(sys_get_temp_dir(), 'cmp413_admin_');
$accountJar = tempnam(sys_get_temp_dir(), 'cmp413_account_');
$anonJar = tempnam(sys_get_temp_dir(), 'cmp413_anon_');

$adminUser = getenv('SWEEP_ADMIN_USER') ?: 'admin';
$adminPass = getenv('SWEEP_ADMIN_PASS') ?: 'admin123';
$adminOk = loginAdmin($base, $adminJar, $adminUser, $adminPass);
fwrite(STDERR, $adminOk ? "Админ-сессия установлена ($adminUser)\n" : "ВНИМАНИЕ: не удалось залогиниться в админку, admin/* пойдут анонимно (ожидаем redirect на login)\n");

$rows = array_map(fn ($line) => str_getcsv($line, ',', '"', '\\'), file($csvPath));
$header = array_shift($rows);

$out = fopen('php://stdout', 'w');
fputcsv($out, ['module', 'controller_file', 'controller_id', 'action', 'internal_route', 'url', 'url_source', 'abstract_base', 'method', 'http_status', 'session', 'curl_error'], ',', '"', '\\');

$counts = [];
foreach ($rows as $r) {
    $row = array_combine($header, $r);
    $url = $row['url'];
    $url = fillPlaceholders($url, $ids);
    if (!str_starts_with($url, '/')) {
        $url = '/' . $url;
    }

    if (str_starts_with($row['module'], 'admin') || str_starts_with($row['internal_route'], 'admin/')) {
        $jar = $adminJar;
        $session = 'admin';
    } elseif ($row['module'] === 'account') {
        $jar = $accountJar;
        $session = 'anon'; // без предварительной регистрации тестового покупателя — ожидаем redirect на login
    } else {
        $jar = $anonJar;
        $session = 'anon';
    }

    [$code, , $err] = httpGet($base . $url, $jar);
    $counts[$code] = ($counts[$code] ?? 0) + 1;

    fputcsv($out, [
        $row['module'], $row['controller_file'], $row['controller_id'], $row['action'],
        $row['internal_route'], $row['url'], $row['url_source'], $row['abstract_base'],
        'GET', $code, $session, $err,
    ], ',', '"', '\\');
}
fclose($out);

fwrite(STDERR, "\nИтоги по статус-кодам:\n");
ksort($counts);
foreach ($counts as $code => $n) {
    fwrite(STDERR, "  $code: $n\n");
}
