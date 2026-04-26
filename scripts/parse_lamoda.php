<?php

/**
 * Парсер каталога Lamoda.by
 *
 * ИСПОЛЬЗОВАНИЕ:
 *   php scripts/parse_lamoda.php [--url=URL] [--pages=N] [--dry-run]
 *
 * По умолчанию парсит мужскую обувь: https://www.lamoda.by/c/17/shoes-men/
 * Результат: создаёт/обновляет товары в таблицах product и product_size.
 * Дедупликация по полю source_url.
 *
 * ANTI-BLOCKING:
 * - Рандомный User-Agent из пула 15+
 * - Задержки 2-5 сек между запросами
 * - Cookie jar для поддержания сессии
 * - Экспоненциальный backoff на 429/403
 * - Ограничение 10 страниц за запуск
 */

// Подключаем Yii2
$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';
require $basePath . '/vendor/yiisoft/yii2/Yii.php';

$config = require $basePath . '/infrastructure/config/web.php';

// Запускаем как консольное приложение
$config['id'] = 'lamoda-parser';
$config['basePath'] = $basePath;
// Убираем веб-зависимости
unset($config['components']['request'], $config['components']['session'], $config['components']['user']);

$app = new yii\console\Application($config);

// ── Конфигурация ─────────────────────────────────────────

$defaultUrl = 'https://www.lamoda.by/c/17/shoes-men/?sitelink=topmenuM&l=men';
$maxPages   = 10;
$limitItems = 0; // 0 = без ограничения по числу товаров
$dryRun     = false;

// Парсим аргументы CLI (поддержка и флагов и позиционных аргументов)
// Позиционный вызов: php parse_lamoda.php "<url>" <limit>
// Флаговый вызов:   php parse_lamoda.php --url=<url> --pages=<n> --limit=<n> --dry-run
$positional = [];
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--url=') === 0) {
        $defaultUrl = substr($arg, 6);
    } elseif (strpos($arg, '--pages=') === 0) {
        $maxPages = (int)substr($arg, 8);
    } elseif (strpos($arg, '--limit=') === 0) {
        $limitItems = (int)substr($arg, 8);
    } elseif ($arg === '--dry-run') {
        $dryRun = true;
    } elseif (!str_starts_with($arg, '--')) {
        $positional[] = $arg;
    }
}
if (!empty($positional[0])) $defaultUrl  = $positional[0];
if (!empty($positional[1])) $limitItems  = (int)$positional[1];

// ── Пул User-Agent'ов ─────────────────────────────────────

$userAgents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.4; rv:125.0) Gecko/20100101 Firefox/125.0',
    'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:125.0) Gecko/20100101 Firefox/125.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 Edg/123.0.0.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4.1 Safari/605.1.15',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14.3; rv:124.0) Gecko/20100101 Firefox/124.0',
    'Mozilla/5.0 (X11; Linux x86_64; rv:124.0) Gecko/20100101 Firefox/124.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 OPR/110.0.0.0',
];

// ── HTTP-клиент ────────────────────────────────────────────

$cookieFile = sys_get_temp_dir() . '/lamoda_cookies_' . getmypid() . '.txt';
$retryCount = 0;
$maxRetries = 5;

function fetchUrl(string $url): ?string
{
    global $userAgents, $cookieFile, $retryCount, $maxRetries;

    $attempt = 0;
    while ($attempt <= $maxRetries) {
        $ua = $userAgents[array_rand($userAgents)];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_USERAGENT      => $ua,
            CURLOPT_COOKIEJAR      => $cookieFile,
            CURLOPT_COOKIEFILE     => $cookieFile,
            CURLOPT_ENCODING       => 'gzip, deflate',
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: ru-RU,ru;q=0.9,en;q=0.5',
                'Cache-Control: no-cache',
                'Connection: keep-alive',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $html     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && $html) {
            return $html;
        }

        if (in_array($httpCode, [429, 403, 503])) {
            $attempt++;
            $delay = min(pow(2, $attempt) + rand(1, 3), 60);
            logMsg("⚠ HTTP {$httpCode} для {$url} — ждём {$delay}с (попытка {$attempt}/{$maxRetries})");
            sleep($delay);
            continue;
        }

        logMsg("✗ HTTP {$httpCode} для {$url}: {$error}");
        return null;
    }

    logMsg("✗ Все попытки исчерпаны для {$url}");
    return null;
}

function randomDelay(): void
{
    $delay = rand(2000000, 5000000); // 2-5 сек в микросекундах
    usleep($delay);
}

// ── Парсинг каталога ──────────────────────────────────────

function parseCatalogPage(string $html): array
{
    $products = [];
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $xpath = new DOMXPath($dom);

    // Lamoda использует карточки товаров с data-атрибутами
    // Ищем JSON-данные в скрипте state
    if (preg_match('/"products"\s*:\s*(\[[\s\S]*?\])\s*,\s*"category/i', $html, $m)) {
        $decoded = json_decode($m[1], true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                $product = [
                    'name'       => $item['brand']['name'] . ' ' . ($item['model_name'] ?? $item['name'] ?? ''),
                    'brand'      => $item['brand']['name'] ?? '',
                    'price'      => (float)($item['price']['current'] ?? $item['price']['original'] ?? 0),
                    'sale_price' => null,
                    'image'      => '',
                    'url'        => 'https://www.lamoda.by' . ($item['link'] ?? ''),
                ];

                if (isset($item['price']['current']) && isset($item['price']['original'])
                    && $item['price']['current'] < $item['price']['original']) {
                    $product['sale_price'] = (float)$item['price']['current'];
                    $product['price']      = (float)$item['price']['original'];
                }

                // Изображение
                if (!empty($item['gallery'][0])) {
                    $product['image'] = 'https://a.lmcdn.ru/img600x866/' . $item['gallery'][0];
                } elseif (!empty($item['image'])) {
                    $product['image'] = 'https://a.lmcdn.ru/img600x866/' . $item['image'];
                }

                if (!empty($product['url']) && $product['url'] !== 'https://www.lamoda.by') {
                    $products[] = $product;
                }
            }
            return $products;
        }
    }

    // Fallback: HTML-парсинг карточек
    $cards = $xpath->query('//div[contains(@class,"x-product-card")]');
    if ($cards->length === 0) {
        $cards = $xpath->query('//a[contains(@class,"products-list-item")]');
    }

    foreach ($cards as $card) {
        $nameNode  = $xpath->query('.//span[contains(@class,"brand-name")]', $card)->item(0);
        $titleNode = $xpath->query('.//span[contains(@class,"product-name")]', $card)->item(0);
        $priceNode = $xpath->query('.//*[contains(@class,"price")]', $card)->item(0);
        $imgNode   = $xpath->query('.//img', $card)->item(0);
        $linkNode  = ($card->nodeName === 'a') ? $card : $xpath->query('.//a', $card)->item(0);

        $brand = $nameNode ? trim($nameNode->textContent) : '';
        $title = $titleNode ? trim($titleNode->textContent) : '';
        $name  = trim($brand . ' ' . $title);

        $priceText = $priceNode ? preg_replace('/[^\d.,]/', '', $priceNode->textContent) : '0';
        $price     = (float)str_replace(',', '.', $priceText);

        $imgSrc = $imgNode ? ($imgNode->getAttribute('src') ?: $imgNode->getAttribute('data-src')) : '';
        $href   = $linkNode ? $linkNode->getAttribute('href') : '';
        if ($href && strpos($href, 'http') !== 0) {
            $href = 'https://www.lamoda.by' . $href;
        }

        if ($name && $href) {
            $products[] = [
                'name'       => $name,
                'brand'      => $brand,
                'price'      => $price,
                'sale_price' => null,
                'image'      => $imgSrc,
                'url'        => $href,
            ];
        }
    }

    return $products;
}

// ── Парсинг страницы товара ────────────────────────────────

function parseProductPage(string $html, string $url): array
{
    $details = [
        'sizes'       => [],
        'description' => '',
        'article'     => '',
        'photos'      => [],
    ];

    // JSON-LD данные
    if (preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/si', $html, $ldMatches)) {
        foreach ($ldMatches[1] as $ldJson) {
            $ld = json_decode($ldJson, true);
            if (isset($ld['@type']) && $ld['@type'] === 'Product') {
                $details['description'] = $ld['description'] ?? '';
                $details['article']     = $ld['sku'] ?? '';
                if (isset($ld['image']) && is_array($ld['image'])) {
                    $details['photos'] = array_slice($ld['image'], 0, 10);
                } elseif (isset($ld['image'])) {
                    $details['photos'][] = $ld['image'];
                }
            }
        }
    }

    // Размеры из JSON state
    if (preg_match('/"sizes"\s*:\s*(\[[\s\S]*?\])/i', $html, $szMatch)) {
        $sizes = json_decode($szMatch[1], true);
        if (is_array($sizes)) {
            foreach ($sizes as $sz) {
                if (isset($sz['size']) || isset($sz['title'])) {
                    $details['sizes'][] = [
                        'size'         => (string)($sz['size'] ?? $sz['title'] ?? ''),
                        'eu_size'      => (string)($sz['size_eu'] ?? $sz['title'] ?? ''),
                        'us_size'      => (string)($sz['size_us'] ?? ''),
                        'uk_size'      => (string)($sz['size_uk'] ?? ''),
                        'is_available' => isset($sz['is_available']) ? (bool)$sz['is_available'] : true,
                    ];
                }
            }
        }
    }

    // Fallback: размеры из HTML
    if (empty($details['sizes'])) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);

        $sizeNodes = $xpath->query('//*[contains(@class,"sizes-list")]//span[contains(@class,"size")]');
        foreach ($sizeNodes as $node) {
            $sizeText = trim($node->textContent);
            if ($sizeText) {
                $details['sizes'][] = [
                    'size'         => $sizeText,
                    'eu_size'      => $sizeText,
                    'us_size'      => '',
                    'uk_size'      => '',
                    'is_available' => !$node->parentNode || !preg_match('/disabled|sold.?out/i', $node->parentNode->getAttribute('class')),
                ];
            }
        }
    }

    // Артикул из HTML
    if (empty($details['article'])) {
        if (preg_match('/(?:Артикул|SKU|Код товара)\s*[:：]?\s*([A-Za-z0-9\-_]+)/ui', $html, $artMatch)) {
            $details['article'] = trim($artMatch[1]);
        }
    }

    // Фото из галереи
    if (empty($details['photos'])) {
        if (preg_match_all('/img600x866\/([a-zA-Z0-9_\-\/]+\.(jpg|png|webp))/i', $html, $imgMatches)) {
            foreach (array_unique($imgMatches[0]) as $img) {
                $details['photos'][] = 'https://a.lmcdn.ru/' . $img;
                if (count($details['photos']) >= 10) break;
            }
        }
    }

    return $details;
}

// ── Импорт в БД ───────────────────────────────────────────

function importProduct(array $catalogData, array $details): ?int
{
    global $dryRun;

    $url = $catalogData['url'];

    // Дедупликация
    $existing = \app\backend\modules\catalog\models\Product::findOne(['source_url' => $url]);

    if ($existing) {
        logMsg("  ↻ Уже есть: " . $existing->name . " (id={$existing->id})");
        // Обновляем цены если изменились
        $updated = false;
        if ($catalogData['price'] && $existing->price != $catalogData['price']) {
            $existing->old_price = $catalogData['sale_price'] ? $catalogData['price'] : null;
            $existing->price     = $catalogData['sale_price'] ?: $catalogData['price'];
            $updated = true;
        }
        if ($updated && !$dryRun) {
            $existing->save(false);
        }
        // Обновляем размеры
        if (!empty($details['sizes']) && !$dryRun) {
            importSizes($existing->id, $details['sizes']);
        }
        return $existing->id;
    }

    if ($dryRun) {
        logMsg("  [DRY-RUN] Создал бы: " . $catalogData['name']);
        return null;
    }

    // Ищем или создаём бренд
    $brandId = findOrCreateBrand($catalogData['brand']);

    // Ищем категорию «Кроссовки» (id=1 по умолчанию) или первую активную
    $categoryId = 1;
    $cat = \app\backend\modules\catalog\models\Category::find()->where(['is_active' => 1])->orderBy(['id' => SORT_ASC])->one();
    if ($cat) $categoryId = $cat->id;

    $product = new \app\backend\modules\catalog\models\Product();
    $product->name         = $catalogData['name'];
    $product->brand_id     = $brandId;
    $product->category_id  = $categoryId;
    $product->price        = $catalogData['sale_price'] ?: $catalogData['price'];
    $product->old_price    = $catalogData['sale_price'] ? $catalogData['price'] : null;
    $product->description  = $details['description'];
    $product->sku          = $details['article'] ?: null;
    $product->vendor_code  = $details['article'] ?: null;
    $product->source_url   = $url;
    $product->source       = 'lamoda';
    $product->is_active    = true;
    $product->stock_status = 'in_stock';
    $product->main_image   = $catalogData['image'] ?: null;

    if (!$product->save(false)) {
        logMsg("  ✗ Ошибка сохранения: " . json_encode($product->errors));
        return null;
    }

    logMsg("  ✓ Создан: {$product->name} (id={$product->id})");

    // Импорт размеров
    if (!empty($details['sizes'])) {
        importSizes($product->id, $details['sizes']);
    }

    // Скачивание изображений
    $allPhotos = array_merge(
        $catalogData['image'] ? [$catalogData['image']] : [],
        $details['photos']
    );
    importImages($product->id, array_unique($allPhotos));

    return $product->id;
}

function importSizes(int $productId, array $sizes): void
{
    foreach ($sizes as $i => $sz) {
        $sizeValue = $sz['eu_size'] ?: $sz['size'];
        if (empty($sizeValue)) continue;

        // Проверяем существование
        $existing = \app\backend\modules\catalog\models\ProductSize::findOne([
            'product_id' => $productId,
            'size'        => $sizeValue,
        ]);
        if ($existing) continue;

        $model = new \app\backend\modules\catalog\models\ProductSize();
        $model->product_id   = $productId;
        $model->size         = $sizeValue;
        $model->eu_size      = $sz['eu_size'] ?? '';
        $model->us_size      = $sz['us_size'] ?? '';
        $model->uk_size      = $sz['uk_size'] ?? '';
        $model->is_available = $sz['is_available'] ?? true;
        $model->stock        = $model->is_available ? 1 : 0;
        $model->sort_order   = $i;
        $model->save(false);
    }
}

function importImages(int $productId, array $urls): void
{
    $uploadDir = Yii::getAlias('@webroot') . '/uploads/products/' . $productId;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $isFirst = true;
    foreach (array_slice($urls, 0, 10) as $imgUrl) {
        if (empty($imgUrl)) continue;

        try {
            $imgData = fetchUrl($imgUrl);
            if (!$imgData) continue;

            $ext      = pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
            $filename = uniqid('lamoda_') . '.' . $ext;
            $filepath = $uploadDir . '/' . $filename;
            file_put_contents($filepath, $imgData);

            $relPath = '/uploads/products/' . $productId . '/' . $filename;

            // Сохраняем в product_image
            $img = new \app\backend\modules\catalog\models\ProductImage();
            $img->product_id = $productId;
            $img->url        = $relPath;
            $img->is_main    = $isFirst ? 1 : 0;
            $img->sort_order = $isFirst ? 0 : 10;
            $img->save(false);

            if ($isFirst) {
                // Обновляем main_image у продукта
                \app\backend\modules\catalog\models\Product::updateAll(
                    ['main_image' => $relPath, 'main_image_url' => $relPath],
                    ['id' => $productId]
                );
                $isFirst = false;
            }

            randomDelay();
        } catch (\Exception $e) {
            logMsg("  ⚠ Ошибка скачивания изображения: " . $e->getMessage());
        }
    }
}

function findOrCreateBrand(string $brandName): int
{
    if (empty($brandName)) $brandName = 'Без бренда';

    $brand = \app\backend\modules\catalog\models\Brand::findOne(['name' => $brandName]);
    if ($brand) return $brand->id;

    $brand = new \app\backend\modules\catalog\models\Brand();
    $brand->name = $brandName;
    // slug генерируется автоматически через SluggableBehavior если есть
    if ($brand->hasAttribute('is_active')) {
        $brand->is_active = true;
    }
    $brand->save(false);
    logMsg("  + Создан бренд: {$brandName} (id={$brand->id})");
    return $brand->id;
}

// ── Логирование ──────────────────────────────────────────

function logMsg(string $msg): void
{
    $time = date('H:i:s');
    echo "[{$time}] {$msg}\n";
    Yii::info($msg, 'lamoda-parser');
}

// ── Основной процесс ─────────────────────────────────────

logMsg("═══════════════════════════════════════════════");
logMsg("Парсер Lamoda.by запущен");
logMsg("URL: {$defaultUrl}");
logMsg("Макс. страниц: {$maxPages}");
logMsg($dryRun ? "Режим: DRY-RUN (без записи в БД)" : "Режим: LIVE (запись в БД)");
logMsg("═══════════════════════════════════════════════");

$totalProducts = 0;
$totalCreated  = 0;
$totalUpdated  = 0;
$totalErrors   = 0;

for ($page = 1; $page <= $maxPages; $page++) {
    $separator = strpos($defaultUrl, '?') !== false ? '&' : '?';
    $pageUrl   = $defaultUrl . ($page > 1 ? $separator . 'page=' . $page : '');

    logMsg("📄 Страница {$page}/{$maxPages}: {$pageUrl}");

    $html = fetchUrl($pageUrl);
    if (!$html) {
        logMsg("✗ Не удалось загрузить страницу {$page}");
        $totalErrors++;
        continue;
    }

    $products = parseCatalogPage($html);
    logMsg("  Найдено товаров на странице: " . count($products));

    if (empty($products)) {
        logMsg("  Товары закончились — останавливаемся");
        break;
    }

    foreach ($products as $product) {
        $totalProducts++;
        logMsg("  [{$totalProducts}] {$product['name']} — {$product['price']} BYN");

        randomDelay();

        // Загружаем страницу товара для деталей
        $detailHtml = fetchUrl($product['url']);
        $details    = $detailHtml ? parseProductPage($detailHtml, $product['url']) : [
            'sizes' => [], 'description' => '', 'article' => '', 'photos' => [],
        ];

        logMsg("    Размеров: " . count($details['sizes']) . " | Фото: " . count($details['photos']) . " | Артикул: " . ($details['article'] ?: '-'));

        $result = importProduct($product, $details);
        if ($result) {
            $totalCreated++;
        } else {
            $totalErrors++;
        }

        randomDelay();
    }

    randomDelay();
}

// Очищаем cookie файл
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

logMsg("═══════════════════════════════════════════════");
logMsg("ИТОГО:");
logMsg("  Просмотрено товаров: {$totalProducts}");
logMsg("  Создано/обновлено: {$totalCreated}");
logMsg("  Ошибок: {$totalErrors}");
logMsg("═══════════════════════════════════════════════");

// Сохраняем результат для отображения в админке
$resultData = json_encode([
    'date'        => date('Y-m-d H:i:s'),
    'url'         => $defaultUrl,
    'pages'       => $maxPages,
    'total'       => $totalProducts,
    'created'     => $totalCreated,
    'new'         => $totalCreated,
    'updated'     => 0,
    'skipped'     => $totalProducts - $totalCreated - $totalErrors,
    'errors'      => $totalErrors,
    'dry_run'     => $dryRun,
    'finished_at' => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE);

try {
    Yii::$app->settings->set('lamoda', 'last_parse', $resultData);
    Yii::$app->settings->set('lamoda', 'last_parse_result', $resultData);
    Yii::$app->settings->set('lamoda', 'parse_status', 'idle');
} catch (\Exception $e) {
    // Не критично
}
