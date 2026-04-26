<?php
declare(strict_types=1);

ini_set('memory_limit', '512M');

/**
 * МойСклад → БД: полный импорт всех сущностей
 *
 * Usage:
 *   php scripts/import_from_moysklad.php              # all
 *   php scripts/import_from_moysklad.php customers    # only customers
 *   php scripts/import_from_moysklad.php products     # only products (+ per-product variants)
 *   php scripts/import_from_moysklad.php variants     # bulk variant import (faster for re-sync)
 *   php scripts/import_from_moysklad.php orders       # only orders
 *   php scripts/import_from_moysklad.php supplies     # only supplies/receipts
 *   php scripts/import_from_moysklad.php payments     # only payments
 *   php scripts/import_from_moysklad.php --analyze    # dump all API fields from 1 entity each
 */

// ─── Configuration ──────────────────────────────────────────────────────────

// SECURITY FIX: credentials loaded from .env — never hardcode secrets in source
$dotenv = parse_ini_file(__DIR__ . '/../.env');
const MS_LOGIN    = null; // set below
const MS_BASE     = 'https://api.moysklad.ru/api/remap/1.2';
define('MS_LOGIN_VALUE', $dotenv['MS_LOGIN'] ?? getenv('MS_LOGIN') ?: die("MS_LOGIN not set in .env\n"));
define('MS_PASSWORD', $dotenv['MS_PASSWORD'] ?? getenv('MS_PASSWORD') ?: die("MS_PASSWORD not set in .env\n"));

define('DB_HOST', $dotenv['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', $dotenv['DB_NAME'] ?? getenv('DB_NAME') ?: 'sneakerhead');
define('DB_USER', $dotenv['DB_USER'] ?? getenv('DB_USER') ?: 'sneaker');
define('DB_PASS', $dotenv['DB_PASSWORD'] ?? $dotenv['DB_PASS'] ?? getenv('DB_PASSWORD') ?: die("DB_PASSWORD not set in .env\n"));

const PAGE_SIZE = 100;

// ─── Bootstrap ──────────────────────────────────────────────────────────────

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$msAuth = 'Basic ' . base64_encode(MS_LOGIN . ':' . MS_PASSWORD);

$stats = [
    'customers' => ['created' => 0, 'updated' => 0, 'errors' => 0],
    'products'  => ['created' => 0, 'updated' => 0, 'errors' => 0],
    'variants'  => ['created' => 0, 'updated' => 0, 'errors' => 0],
    'orders'    => ['created' => 0, 'updated' => 0, 'errors' => 0],
    'supplies'  => ['created' => 0, 'updated' => 0, 'errors' => 0],
    'payments'  => ['created' => 0, 'updated' => 0, 'errors' => 0],
];

$errorLog = [];

// ─── HTTP helpers ────────────────────────────────────────────────────────────

function msRequest(string $method, string $path, ?array $body = null, array $query = []): array
{
    global $msAuth;

    $url = MS_BASE . $path;
    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    $method  = strtoupper($method);
    $headers = [
        'Authorization: ' . $msAuth,
        'Accept-Encoding: gzip',
    ];
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_ENCODING       => 'gzip',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
    ]);

    if ($method === 'GET') {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    } elseif ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }
    }

    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $code === 0) {
        throw new RuntimeException('МС сетевая ошибка: ' . $curlErr);
    }

    $data = json_decode($response, true) ?? [];

    if ($code >= 400) {
        $err = $data['errors'][0]['error'] ?? mb_substr($response, 0, 300);
        throw new RuntimeException("МС {$code}: {$err}");
    }

    return $data;
}

function msFetchEach(string $path, array $query, callable $fn): int
{
    $offset = 0;
    $total  = null;

    do {
        $q = array_merge($query, ['limit' => PAGE_SIZE, 'offset' => $offset]);

        // Retry up to 3 times on network timeout
        $data = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $data = msRequest('GET', $path, null, $q);
                break;
            } catch (RuntimeException $e) {
                if ($attempt === 3) throw $e;
                logLine("  ⚠ сетевая ошибка (попытка {$attempt}/3): " . $e->getMessage() . " — повтор через 5с");
                sleep(5);
            }
        }

        $batch = $data['rows'] ?? [];

        if ($total === null) {
            $total = (int)($data['meta']['size'] ?? 0);
        }

        unset($data);

        $fetched = $offset + count($batch);
        echo "    обработано {$fetched} / {$total}\r";

        foreach ($batch as $row) {
            $fn($row);
        }

        unset($batch);

        $offset = $fetched;

        if ($fetched >= $total) {
            break;
        }

        usleep(250_000); // 250 ms — avoid rate limiting
    } while (true);

    echo "\n";
    return (int)$total;
}

// ─── Utility helpers ─────────────────────────────────────────────────────────

function logLine(string $msg): void
{
    echo '[' . date('H:i:s') . '] ' . $msg . "\n";
}

function logErr(string $ctx, string $msg): void
{
    global $errorLog;
    $line = "[{$ctx}] {$msg}";
    echo '[' . date('H:i:s') . '] ERR ' . $line . "\n";
    $errorLog[] = $line;
}

function msDateToTs(?string $msDate): ?int
{
    if (!$msDate) return null;
    $clean = preg_replace('/\.\d+/', '', $msDate);
    $ts    = strtotime($clean);
    return $ts !== false ? $ts : null;
}

function msDateToDt(?string $msDate): ?string
{
    $ts = msDateToTs($msDate);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

function msIdFromHref(?string $href): ?string
{
    if (!$href) return null;
    $parts = explode('/', $href);
    $last  = end($parts);
    return preg_replace('/\?.*/', '', $last) ?: null;
}

function normalizePhone(string $phone): string
{
    $digits = preg_replace('/\D/', '', $phone);
    if (!$digits) return '';
    if (strlen($digits) === 11 && $digits[0] === '8') {
        $digits = '7' . substr($digits, 1);
    }
    return '+' . $digits;
}

function mapMsStatus(string $msStateName): string
{
    $map = [
        'новый'                    => 'created',
        'оплачен'                  => 'paid',
        'подтвержден'              => 'confirmed',
        'заказано у поставщика'    => 'ordered',
        'в международной доставке' => 'international_delivery',
        'на складе'                => 'at_warehouse',
        'в доставке'               => 'local_delivery',
        'выдан'                    => 'delivered',
        'отменен'                  => 'canceled',
        'возврат'                  => 'return',
    ];
    return $map[mb_strtolower($msStateName)] ?? 'created';
}

function transliterateRu(string $str): string
{
    static $map = [
        'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh',
        'з'=>'z','и'=>'i','й'=>'j','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
        'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'ts',
        'ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu',
        'я'=>'ya',
    ];
    return strtr($str, $map);
}

function makeSlug(string $name): string
{
    $s = mb_strtolower($name);
    $s = transliterateRu($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: ('item-' . substr(md5($name), 0, 8));
}

function uniqueSlug(PDO $pdo, string $table, string $base): string
{
    $slug = $base;
    $i    = 1;
    $st   = $pdo->prepare("SELECT 1 FROM `{$table}` WHERE slug = ?");
    while (true) {
        $st->execute([$slug]);
        if (!$st->fetch()) return $slug;
        $slug = $base . '-' . $i++;
    }
}

// ─── Schema migration helpers ─────────────────────────────────────────────────

function ensureMoyskladId(PDO $pdo, string $table): void
{
    $rows = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'moysklad_id'")->fetchAll();
    if (!empty($rows)) return;
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `moysklad_id` VARCHAR(36) NULL DEFAULT NULL");
    $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `idx_ms_{$table}` (`moysklad_id`)");
    logLine("  → добавлена колонка moysklad_id в {$table}");
}

/**
 * Add column with given full SQL definition if it doesn't exist yet.
 * $definition is the full type + constraints, e.g. "VARCHAR(100) NULL DEFAULT NULL"
 */
function ensureColumn(PDO $pdo, string $table, string $column, string $definition): void
{
    $rows = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'")->fetchAll();
    if (!empty($rows)) return;
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    logLine("  → добавлена колонка {$column} в {$table}");
}

function ensureMoyskladExtra(PDO $pdo, string $table): void
{
    ensureColumn($pdo, $table, 'moysklad_extra', 'JSON NULL DEFAULT NULL');
}

/**
 * Build moysklad_extra JSON from all MoySklad fields NOT in $mapped list.
 * Strips internal/meta keys that are never useful to store.
 */
function buildExtra(array $msData, array $mapped): ?string
{
    $extra = array_diff_key($msData, array_flip($mapped));
    unset($extra['meta'], $extra['accountId'], $extra['owner'], $extra['group'], $extra['shared']);
    if (empty($extra)) return null;
    return json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// ─── Attribute helpers ───────────────────────────────────────────────────────

/**
 * Parse the МС attributes[] array into a flat name→value map.
 * Handles types: boolean, time, customentity, string, text, link, double, long.
 * Keys are lowercased for case-insensitive lookup.
 */
function extractAttrs(array $attributes): array
{
    $result = [];
    foreach ($attributes as $attr) {
        $name  = mb_strtolower(trim($attr['name'] ?? ''));
        if (!$name) continue;
        $type  = $attr['type'] ?? 'string';
        $value = $attr['value'] ?? null;
        if ($value === null) continue;

        $extracted = match($type) {
            'boolean'      => (bool)$value,
            'time'         => msDateToDt(is_string($value) ? $value : null),
            'customentity' => is_array($value) ? ($value['name'] ?? null) : null,
            'double'       => is_numeric($value) ? (float)$value : null,
            'long'         => is_numeric($value) ? (int)$value   : null,
            default        => is_scalar($value) ? (string)$value  : null,
        };

        $result[$name] = $extracted;
    }
    return $result;
}

/**
 * From МС salePrices[] array, return a map of price-type-name (lowercase) → amount in main currency units.
 */
function extractPrices(array $salePrices): array
{
    $result = [];
    foreach ($salePrices as $sp) {
        $typeName = mb_strtolower($sp['priceType']['name'] ?? '');
        if (!$typeName) continue;
        $result[$typeName] = isset($sp['value']) ? round((float)$sp['value'] / 100, 2) : 0.0;
    }
    return $result;
}

// ─── Default brand / category helpers ───────────────────────────────────────

function getOrCreateBrand(PDO $pdo, string $name): int
{
    $st = $pdo->prepare("SELECT id FROM brand WHERE name = ? LIMIT 1");
    $st->execute([$name]);
    $row = $st->fetch();
    if ($row) return (int)$row['id'];
    $slug = uniqueSlug($pdo, 'brand', makeSlug($name));
    $pdo->prepare("INSERT INTO brand (name, slug, is_active) VALUES (?, ?, 1)")->execute([$name, $slug]);
    return (int)$pdo->lastInsertId();
}

function getOrCreateCategory(PDO $pdo, string $name): int
{
    $st = $pdo->prepare("SELECT id FROM category WHERE name = ? LIMIT 1");
    $st->execute([$name]);
    $row = $st->fetch();
    if ($row) return (int)$row['id'];
    $slug = uniqueSlug($pdo, 'category', makeSlug($name));
    $pdo->prepare("INSERT INTO category (name, slug, is_active) VALUES (?, ?, 1)")->execute([$name, $slug]);
    return (int)$pdo->lastInsertId();
}

// ─── Analyze mode ────────────────────────────────────────────────────────────

function runAnalyze(): void
{
    echo "\n";
    echo "╔══════════════════════════════════════════════════════╗\n";
    echo "║   МойСклад: анализ полей API  (--analyze)            ║\n";
    echo "╚══════════════════════════════════════════════════════╝\n\n";

    $entities = [
        'counterparty'  => [
            'path'  => '/entity/counterparty',
            'query' => ['limit' => 1],
            'label' => 'КОНТРАГЕНТ  →  customer',
        ],
        'product'       => [
            'path'  => '/entity/product',
            'query' => ['limit' => 1, 'expand' => 'productFolder,uom,supplier,country'],
            'label' => 'ТОВАР  →  product',
        ],
        'variant'       => [
            'path'  => '/entity/variant',
            'query' => ['limit' => 1],
            'label' => 'ВАРИАНТ ТОВАРА  →  product_size',
        ],
        'customerorder' => [
            'path'  => '/entity/customerorder',
            'query' => ['limit' => 1, 'expand' => 'agent,state,positions.assortment,salesChannel,store'],
            'label' => 'ЗАКАЗ  →  order',
        ],
        'supply'        => [
            'path'  => '/entity/supply',
            'query' => ['limit' => 1, 'expand' => 'agent,state,positions.assortment'],
            'label' => 'ПРИЁМКА  →  purchase_order',
        ],
        'paymentin'     => [
            'path'  => '/entity/paymentin',
            'query' => ['limit' => 1, 'expand' => 'agent,operations'],
            'label' => 'ВХОДЯЩИЙ ПЛАТЁЖ  →  payment',
        ],
    ];

    foreach ($entities as $cfg) {
        $bar = str_repeat('─', 56);
        echo "\n┌{$bar}┐\n";
        echo '│  ' . str_pad($cfg['label'], 54) . "  │\n";
        echo "└{$bar}┘\n";

        try {
            $resp = msRequest('GET', $cfg['path'], null, $cfg['query']);
            $rows = $resp['rows'] ?? [];
            if (empty($rows)) {
                echo "  (нет данных в этой сущности)\n";
                continue;
            }
            dumpFields($rows[0], 0);
        } catch (Throwable $e) {
            echo "  ОШИБКА: " . $e->getMessage() . "\n";
        }
    }

    echo "\n\nАнализ завершён.\n";
}

function dumpFields(array $data, int $depth, string $prefix = ''): void
{
    $indent = str_repeat('    ', $depth);
    foreach ($data as $key => $value) {
        $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
        if (is_array($value)) {
            $isList = array_keys($value) === range(0, count($value) - 1);
            if ($isList && count($value) > 0 && !is_array($value[0])) {
                // scalar list
                $preview = implode(', ', array_slice($value, 0, 5));
                $more    = count($value) > 5 ? ', ...' : '';
                echo "{$indent}  \033[33m{$fullKey}\033[0m = [{$preview}{$more}]\n";
            } elseif ($isList && !empty($value)) {
                echo "{$indent}  \033[33m{$fullKey}\033[0m = [array, count=" . count($value) . "] первый элемент:\n";
                dumpFields($value[0], $depth + 1, "{$fullKey}[0]");
            } elseif (isset($value['meta'])) {
                $type = $value['meta']['type'] ?? '?';
                $href = mb_substr($value['meta']['href'] ?? '', -60);
                echo "{$indent}  \033[36m{$fullKey}\033[0m = <{$type}> ...{$href}\n";
                // show expanded scalar sub-fields
                foreach ($value as $k2 => $v2) {
                    if ($k2 === 'meta' || is_array($v2)) continue;
                    echo "{$indent}      .{$k2} = " . _fmtVal($v2) . "\n";
                }
            } elseif (empty($value)) {
                echo "{$indent}  \033[90m{$fullKey}\033[0m = {}\n";
            } else {
                echo "{$indent}  \033[32m{$fullKey}\033[0m:\n";
                dumpFields($value, $depth + 1, $fullKey);
            }
        } else {
            $color = is_null($value) ? "\033[90m" : "\033[0m";
            echo "{$indent}  {$color}{$fullKey}\033[0m = " . _fmtVal($value) . "\n";
        }
    }
}

function _fmtVal(mixed $v): string
{
    if (is_null($v))   return 'null';
    if (is_bool($v))   return $v ? 'true' : 'false';
    if (is_string($v)) return '"' . mb_substr($v, 0, 120) . '"';
    return (string)$v;
}

// ─── Schema setup ─────────────────────────────────────────────────────────────

function setupSchema(PDO $pdo): void
{
    logLine('Проверка и обновление схемы БД...');

    // moysklad_id for legacy tables
    foreach (['customer', 'product', 'purchase_order', 'payment'] as $t) {
        ensureMoyskladId($pdo, $t);
    }

    // moysklad_extra catch-all JSON column on every imported table
    foreach (['customer', 'product', 'product_size', 'order', 'purchase_order', 'payment'] as $t) {
        ensureMoyskladExtra($pdo, $t);
    }

    // ── customer ────────────────────────────────────────────────────────────
    ensureColumn($pdo, 'customer', 'company_type',          'VARCHAR(20) NULL DEFAULT NULL');
    ensureColumn($pdo, 'customer', 'ms_external_code',      'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'customer', 'fax',                   'VARCHAR(50) NULL DEFAULT NULL');
    ensureColumn($pdo, 'customer', 'notes',                 'TEXT NULL DEFAULT NULL');
    ensureColumn($pdo, 'customer', 'discount_card_number',  'VARCHAR(50) NULL DEFAULT NULL');
    ensureColumn($pdo, 'customer', 'bonus_points',          'INT NULL DEFAULT 0');
    ensureColumn($pdo, 'customer', 'tags_json',             'JSON NULL DEFAULT NULL');
    ensureColumn($pdo, 'customer', 'actual_address',        'TEXT NULL DEFAULT NULL');
    ensureColumn($pdo, 'customer', 'legal_address',         'TEXT NULL DEFAULT NULL');
    ensureColumn($pdo, 'customer', 'legal_title',           'VARCHAR(255) NULL DEFAULT NULL');
    ensureColumn($pdo, 'customer', 'kpp',                   'VARCHAR(20) NULL DEFAULT NULL');
    ensureColumn($pdo, 'customer', 'ogrn',                  'VARCHAR(50) NULL DEFAULT NULL');
    ensureColumn($pdo, 'customer', 'ogrnip',                'VARCHAR(50) NULL DEFAULT NULL');
    ensureColumn($pdo, 'customer', 'okpo',                  'VARCHAR(20) NULL DEFAULT NULL');

    // ── product ─────────────────────────────────────────────────────────────
    ensureColumn($pdo, 'product', 'ms_code',                 'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_external_code',        'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'barcode',                 'VARCHAR(255) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'barcodes_json',           'JSON NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'vat',                     'SMALLINT NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_volume',               'DECIMAL(10,4) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'uom_name',                'VARCHAR(50) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_images_json',          'JSON NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_attributes_json',      'JSON NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_characteristics_json', 'JSON NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_min_price',            'DECIMAL(10,2) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_supplier_name',        'VARCHAR(255) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_archived',             'TINYINT(1) NULL DEFAULT 0');
    // custom attribute columns (дополнительные поля МС)
    ensureColumn($pdo, 'product', 'ms_no_export',            'TINYINT(1) NULL DEFAULT 0');
    ensureColumn($pdo, 'product', 'is_sale',                 'TINYINT(1) NULL DEFAULT 0');
    ensureColumn($pdo, 'product', 'ms_size_grid',            'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_purpose',              'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_sole_height',          'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_sole_color',           'VARCHAR(255) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_inner_material',       'VARCHAR(255) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_site_link',            'VARCHAR(500) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_price_full',           'DECIMAL(10,2) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_price_rub',            'DECIMAL(10,2) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_price_sale',           'DECIMAL(10,2) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_price_competitor',     'DECIMAL(10,2) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product', 'ms_path_name',            'VARCHAR(255) NULL DEFAULT NULL');

    // ── product_size ─────────────────────────────────────────────────────────
    ensureColumn($pdo, 'product_size', 'ms_variant_id',       'VARCHAR(36) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product_size', 'ms_barcode',          'VARCHAR(255) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product_size', 'ms_code',             'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product_size', 'ms_external_code',    'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'product_size', 'characteristics_json','JSON NULL DEFAULT NULL');
    try {
        $pdo->exec("ALTER TABLE product_size ADD INDEX idx_ms_variant (ms_variant_id)");
    } catch (Throwable) {}

    // ── order ────────────────────────────────────────────────────────────────
    ensureColumn($pdo, 'order', 'ms_external_code',         'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_applicable',            'TINYINT(1) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_payed_sum',             'DECIMAL(12,2) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_shipped_sum',           'DECIMAL(12,2) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_invoiced_sum',          'DECIMAL(12,2) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_reserved_sum',          'DECIMAL(12,2) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_vat_sum',               'DECIMAL(12,2) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_rate_value',            'DECIMAL(12,4) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_rate_currency',         'VARCHAR(10) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_sales_channel',         'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_store_name',            'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_shipment_address_json', 'JSON NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_attributes_json',       'JSON NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_updated',               'DATETIME NULL DEFAULT NULL');
    // custom attribute columns (дополнительные поля МС)
    ensureColumn($pdo, 'order', 'ms_lead_source',           'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_delivery_type',         'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_deal_link',             'TEXT NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_amo_created_at',        'DATETIME NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_order_size',            'VARCHAR(50) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_via_widget',            'TINYINT(1) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_passport_transferred',  'TINYINT(1) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_cancel_reason',         'TEXT NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_waybill_link',          'TEXT NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_cancel_date',           'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_pickup_date',           'VARCHAR(100) NULL DEFAULT NULL');
    // new 100%-coverage columns
    ensureColumn($pdo, 'order', 'ms_organization_id',       'VARCHAR(36) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_organization_name',     'VARCHAR(255) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_contract_id',           'VARCHAR(36) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_contract_name',         'VARCHAR(255) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_project_id',            'VARCHAR(36) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_project_name',          'VARCHAR(255) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_delivery_planned_moment','DATETIME NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_vat_enabled',           'TINYINT(1) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_vat_included',          'TINYINT(1) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_created',               'DATETIME NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_printed',               'TINYINT(1) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_published',             'TINYINT(1) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_linked_payments_json',  'JSON NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_demands_json',          'JSON NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_invoices_out_json',     'JSON NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_positions_count',       'INT NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_agent_company_type',    'VARCHAR(20) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_agent_legal_title',     'VARCHAR(255) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order', 'ms_agent_actual_address',  'TEXT NULL DEFAULT NULL');

    // ── order_item ───────────────────────────────────────────────────────────
    ensureColumn($pdo, 'order_item', 'ms_position_id',  'VARCHAR(36) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order_item', 'ms_assortment_id','VARCHAR(36) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order_item', 'discount',        'DECIMAL(5,2) NULL DEFAULT 0');
    ensureColumn($pdo, 'order_item', 'vat',             'SMALLINT NULL DEFAULT NULL');
    ensureColumn($pdo, 'order_item', 'ms_vat_enabled',  'TINYINT(1) NULL DEFAULT NULL');
    ensureColumn($pdo, 'order_item', 'ms_shipped',      'INT NULL DEFAULT 0');
    ensureColumn($pdo, 'order_item', 'ms_reserve',      'INT NULL DEFAULT 0');

    // ── purchase_order ───────────────────────────────────────────────────────
    ensureColumn($pdo, 'purchase_order', 'ms_external_code', 'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'purchase_order', 'ms_applicable',    'TINYINT(1) NULL DEFAULT NULL');
    ensureColumn($pdo, 'purchase_order', 'ms_vat_sum',       'DECIMAL(12,2) NULL DEFAULT NULL');
    ensureColumn($pdo, 'purchase_order', 'ms_rate_value',    'DECIMAL(12,4) NULL DEFAULT NULL');

    // ── payment ──────────────────────────────────────────────────────────────
    ensureColumn($pdo, 'payment', 'ms_external_code', 'VARCHAR(100) NULL DEFAULT NULL');
    ensureColumn($pdo, 'payment', 'ms_applicable',    'TINYINT(1) NULL DEFAULT NULL');
    ensureColumn($pdo, 'payment', 'ms_rate_value',    'DECIMAL(12,4) NULL DEFAULT NULL');
    ensureColumn($pdo, 'payment', 'ms_agent_name',    'VARCHAR(255) NULL DEFAULT NULL');
    ensureColumn($pdo, 'payment', 'ms_in_number',     'VARCHAR(50) NULL DEFAULT NULL');

    logLine('Схема БД готова ✓');
}

// ─── 1. Import customers (контрагенты) ──────────────────────────────────────

function importCustomers(PDO $pdo): void
{
    global $stats;

    logLine('═══ Импорт контрагентов (customers) ═══');

    $now   = time();
    $total = msFetchEach('/entity/counterparty', [], function(array $cp) use ($pdo, &$stats, $now): void {
        $msId = $cp['id'] ?? null;
        if (!$msId) return;

        try {
            $st = $pdo->prepare("SELECT id FROM customer WHERE moysklad_id = ? LIMIT 1");
            $st->execute([$msId]);
            $dbRow = $st->fetch();

            $phone = normalizePhone($cp['phone'] ?? '');
            $email = trim($cp['email'] ?? '');

            if (!$dbRow && $phone) {
                $st = $pdo->prepare("SELECT id FROM customer WHERE phone = ? LIMIT 1");
                $st->execute([$phone]);
                $dbRow = $st->fetch();
            }

            if (!$dbRow && $email) {
                $st = $pdo->prepare("SELECT id FROM customer WHERE email = ? AND email NOT LIKE 'ms_%@import.moysklad' LIMIT 1");
                $st->execute([$email]);
                $dbRow = $st->fetch();
            }

            $fullName   = trim($cp['name'] ?? 'Покупатель');
            $nameParts  = array_filter(explode(' ', $fullName));
            $lastName   = array_shift($nameParts) ?? '';
            $firstName  = array_shift($nameParts) ?? '';
            $middleName = implode(' ', $nameParts);

            if (!$email) {
                $email = 'ms_' . $msId . '@import.moysklad';
            }

            // Birth date
            $birthDate = null;
            if (!empty($cp['birthDate'])) {
                $bd = msDateToTs($cp['birthDate']);
                $birthDate = $bd ? date('Y-m-d', $bd) : null;
            }

            // Gender (МС: MALE / FEMALE)
            $gender = null;
            if (!empty($cp['sex'])) {
                $gender = $cp['sex'] === 'FEMALE' ? 'female' : ($cp['sex'] === 'MALE' ? 'male' : null);
            }

            // Tags
            $tags     = $cp['tags'] ?? [];
            $tagsJson = !empty($tags) ? json_encode($tags, JSON_UNESCAPED_UNICODE) : null;

            // Bonus points
            $bonusPoints = (int)($cp['bonusPoints'] ?? 0);

            // Build moysklad_extra with everything not mapped to a column
            $mappedKeys = [
                'id', 'accountId', 'meta', 'owner', 'group', 'shared', 'updated', 'created',
                'name', 'email', 'phone', 'fax', 'inn', 'kpp', 'ogrn', 'ogrnip', 'okpo',
                'companyType', 'externalCode', 'description', 'discountCardNumber',
                'bonusPoints', 'tags', 'actualAddress', 'legalAddress', 'legalTitle',
                'birthDate', 'sex',
            ];
            $extra = buildExtra($cp, $mappedKeys);

            $data = [
                'moysklad_id'          => $msId,
                'ms_external_code'     => $cp['externalCode'] ?? null,
                'email'                => $email,
                'phone'                => $phone ?: null,
                'last_name'            => $lastName ?: null,
                'first_name'           => $firstName ?: null,
                'middle_name'          => $middleName ?: null,
                'inn'                  => $cp['inn'] ?? null,
                'kpp'                  => $cp['kpp'] ?? null,
                'ogrn'                 => $cp['ogrn'] ?? null,
                'ogrnip'               => $cp['ogrnip'] ?? null,
                'okpo'                 => $cp['okpo'] ?? null,
                'company_type'         => $cp['companyType'] ?? null,
                'fax'                  => $cp['fax'] ?? null,
                'notes'                => $cp['description'] ?? null,
                'discount_card_number' => $cp['discountCardNumber'] ?? null,
                'bonus_points'         => $bonusPoints,
                'tags_json'            => $tagsJson,
                'actual_address'       => $cp['actualAddress'] ?? null,
                'legal_address'        => $cp['legalAddress'] ?? null,
                'legal_title'          => $cp['legalTitle'] ?? null,
                'birth_date'           => $birthDate,
                'gender'               => $gender,
                'status'               => 10,
                'updated_at'           => $now,
                'moysklad_extra'       => $extra,
            ];

            if ($dbRow) {
                $set  = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
                $stmt = $pdo->prepare("UPDATE customer SET {$set} WHERE id = :__id");
                $stmt->execute(array_merge($data, ['__id' => $dbRow['id']]));
                $stats['customers']['updated']++;
            } else {
                $data['password_hash'] = '!'; // locked — no password for imported accounts
                $data['auth_key']      = bin2hex(random_bytes(16));
                $data['created_at']    = $now;
                $data['is_active']     = 1;

                $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
                $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
                $pdo->prepare("INSERT INTO customer ({$cols}) VALUES ({$vals})")->execute($data);
                $stats['customers']['created']++;
            }
        } catch (Throwable $e) {
            logErr("customer:{$msId}", $e->getMessage());
            $stats['customers']['errors']++;
        }
    });

    logLine("Контрагентов в МС: {$total}");
    logLine("Customers: created={$stats['customers']['created']} updated={$stats['customers']['updated']} errors={$stats['customers']['errors']}");
}

// ─── 2. Import products (товары) ─────────────────────────────────────────────

function importProducts(PDO $pdo): void
{
    global $stats;

    logLine('═══ Импорт товаров (products) ═══');

    $defaultBrandId    = getOrCreateBrand($pdo, 'МойСклад');
    $defaultCategoryId = getOrCreateCategory($pdo, 'МойСклад');
    $now               = time();

    $total = msFetchEach('/entity/product', [
        'expand' => 'productFolder,uom,supplier,country',
    ], function(array $p) use ($pdo, &$stats, $now, $defaultBrandId, $defaultCategoryId): void {
        $msId = $p['id'] ?? null;
        if (!$msId) return;

        try {
            $st = $pdo->prepare("SELECT id, slug FROM product WHERE moysklad_id = ? LIMIT 1");
            $st->execute([$msId]);
            $dbRow = $st->fetch();

            if (!$dbRow) {
                $article = $p['article'] ?? $p['code'] ?? null;
                if ($article) {
                    $st = $pdo->prepare("SELECT id, slug FROM product WHERE sku = ? OR vendor_code = ? LIMIT 1");
                    $st->execute([$article, $article]);
                    $dbRow = $st->fetch();
                }
            }

            $name        = trim($p['name'] ?? 'Товар');
            $article     = $p['article'] ?? null;
            $msCode      = $p['code'] ?? null;
            $description = $p['description'] ?? null;
            $weight      = isset($p['weight']) ? (int)round($p['weight'] * 1000) : null; // kg → g
            $volume      = isset($p['volume']) ? (float)$p['volume'] : null;
            $vat         = isset($p['vat']) ? (int)$p['vat'] : null;
            $archived    = !empty($p['archived']) ? 1 : 0;

            // Multiple sale prices by type name
            $prices    = extractPrices($p['salePrices'] ?? []);
            $salePrice = $prices['цена продажи'] ?? $prices[array_key_first($prices)] ?? 0.0;
            $buyPrice  = isset($p['buyPrice']['value']) ? (float)($p['buyPrice']['value'] / 100) : null;
            $minPrice  = isset($p['minPrice']['value']) ? (float)($p['minPrice']['value'] / 100) : null;

            // UOM / supplier / country
            $uomName      = $p['uom']['name'] ?? null;
            $supplierName = $p['supplier']['name'] ?? null;
            $countryName  = $p['country']['name'] ?? null;

            // Full folder path (e.g. "Обувь/Женское/Кроссовки")
            $pathName = $p['pathName'] ?? null;

            // Category from folder leaf name
            $categoryId = $defaultCategoryId;
            $folderName = $p['productFolder']['name'] ?? null;
            if ($folderName) {
                $categoryId = getOrCreateCategory($pdo, $folderName);
            }

            // Barcodes — МС format: [{"ean13":"..."}, {"ean8":"..."}]
            $barcodes     = $p['barcodes'] ?? [];
            $firstBarcode = null;
            foreach ($barcodes as $bc) {
                $vals = array_values($bc);
                if (!empty($vals[0])) { $firstBarcode = (string)$vals[0]; break; }
            }
            $barcodesJson = !empty($barcodes) ? json_encode($barcodes, JSON_UNESCAPED_UNICODE) : null;

            // Images — store meta hrefs; actual binary download requires separate auth'd fetch
            $images  = [];
            $imgMeta = $p['images'] ?? [];
            // images may be a meta-only reference (size > 0 but rows not expanded)
            $imgRows = $imgMeta['rows'] ?? (isset($imgMeta[0]) ? $imgMeta : []);
            foreach ($imgRows as $img) {
                $href = $img['meta']['href'] ?? $img['href'] ?? null;
                if ($href) $images[] = $href;
            }
            // Store the collection href so images can be fetched later
            if (empty($images) && !empty($imgMeta['meta']['href'])) {
                $images = ['__collection__' => $imgMeta['meta']['href']];
            }
            $imagesJson = !empty($images) ? json_encode($images, JSON_UNESCAPED_UNICODE) : null;

            // Raw attributes JSON (kept for full fidelity)
            $rawAttrs  = $p['attributes'] ?? [];
            $attrsJson = !empty($rawAttrs) ? json_encode($rawAttrs, JSON_UNESCAPED_UNICODE) : null;

            // Parse custom attributes (дополнительные поля) by name
            $a = extractAttrs($rawAttrs);

            // Characteristics at product level
            $chars     = $p['characteristics'] ?? [];
            $charsJson = !empty($chars) ? json_encode($chars, JSON_UNESCAPED_UNICODE) : null;

            // Brand: prefer МС custom attr "Бренд", fallback to default
            $brandId   = $defaultBrandId;
            $brandName = $a['бренд'] ?? null;
            if ($brandName) {
                $brandId = getOrCreateBrand($pdo, $brandName);
            }

            // Color: from custom attr first, then characteristics
            $colorDesc = $a['цвет обуви'] ?? null;
            if (!$colorDesc) {
                foreach ($chars as $ch) {
                    $chName = mb_strtolower($ch['name'] ?? '');
                    if (str_contains($chName, 'цвет') || str_contains($chName, 'color')) {
                        $colorDesc = $ch['value'] ?? null;
                        break;
                    }
                }
            }

            $mappedKeys = [
                'id', 'meta', 'accountId', 'owner', 'group', 'shared', 'updated', 'created',
                'name', 'code', 'externalCode', 'article', 'description', 'pathName',
                'productFolder', 'uom', 'supplier', 'country',
                'weight', 'volume', 'vat', 'vatEnabled', 'useParentVat',
                'effectiveVat', 'effectiveVatEnabled',
                'barcodes', 'images', 'salePrices', 'buyPrice', 'minPrice',
                'attributes', 'characteristics', 'archived',
                'paymentItemType', 'discountProhibited', 'variantsCount',
                'isSerialTrackable', 'trackingType',
            ];
            $extra = buildExtra($p, $mappedKeys);

            $data = [
                'moysklad_id'             => $msId,
                'ms_code'                 => $msCode,
                'ms_external_code'        => $p['externalCode'] ?? null,
                'ms_path_name'            => $pathName,
                'name'                    => $name,
                'sku'                     => $article,
                'vendor_code'             => $article,
                'description'             => $description,
                // Prices
                'price'                   => max(0.0, $salePrice),
                'old_price'               => ($prices['полная цена'] ?? 0.0) > 0 ? $prices['полная цена'] : null,
                'purchase_price'          => $buyPrice,
                'ms_min_price'            => $minPrice,
                'ms_price_full'           => $prices['полная цена'] ?? null,
                'ms_price_rub'            => $prices['цена продажи в rub'] ?? null,
                'ms_price_sale'           => $prices['цена распродажи (для своих)'] ?? null,
                'ms_price_competitor'     => $prices['минимальная цена конкурентов'] ?? null,
                // Physical
                'weight'                  => $weight,
                'ms_volume'               => $volume,
                'vat'                     => $vat,
                'uom_name'                => $uomName,
                'ms_supplier_name'        => $supplierName,
                'country_of_origin'       => $countryName,
                // Barcodes & images
                'barcode'                 => $firstBarcode,
                'barcodes_json'           => $barcodesJson,
                'ms_images_json'          => $imagesJson,
                // Raw JSON for full fidelity
                'ms_attributes_json'      => $attrsJson,
                'ms_characteristics_json' => $charsJson,
                // Custom attribute fields
                'brand_id'                => $brandId,
                'brand_name'              => $brandName,
                'model_name'              => $a['модель'] ?? null,
                'gender'                  => $a['пол'] ?? null,
                'season'                  => $a['сезон'] ?? null,
                'height'                  => $a['высота'] ?? null,
                'upper_material'          => $a['материал верха'] ?? null,
                'color_description'       => $colorDesc,
                'ms_size_grid'            => $a['размерная сетка'] ?? null,
                'ms_purpose'              => $a['назначение'] ?? null,
                'ms_sole_height'          => $a['высота подошвы'] ?? null,
                'ms_sole_color'           => $a['цвет подошвы'] ?? null,
                'ms_inner_material'       => $a['материал внутренний'] ?? null,
                'ms_site_link'            => $a['ссылка sneakerhead.by'] ?? null,
                'ms_no_export'            => isset($a['не выгружать на сайт']) ? ($a['не выгружать на сайт'] ? 1 : 0) : null,
                'is_sale'                 => isset($a['распродажа']) ? ($a['распродажа'] ? 1 : 0) : null,
                'is_featured'             => isset($a['новинка']) ? ($a['новинка'] ? 1 : 0) : null,
                // Status
                'ms_archived'             => $archived,
                'is_active'               => $archived ? 0 : 1,
                'category_id'             => $categoryId,
                'moysklad_extra'          => $extra,
                'updated_at'              => $now,
            ];

            if ($dbRow) {
                $set  = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
                $stmt = $pdo->prepare("UPDATE product SET {$set} WHERE id = :__id");
                $stmt->execute(array_merge($data, ['__id' => $dbRow['id']]));
                $stats['products']['updated']++;
            } else {
                $slug         = uniqueSlug($pdo, 'product', makeSlug($name));
                $data['slug'] = $slug;
                $data['created_at'] = $now;

                $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
                $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
                $pdo->prepare("INSERT INTO product ({$cols}) VALUES ({$vals})")->execute($data);
                $stats['products']['created']++;
            }

            $productDbId = $dbRow ? (int)$dbRow['id'] : (int)$pdo->lastInsertId();
            importProductVariants($pdo, $msId, $productDbId);
        } catch (Throwable $e) {
            logErr("product:{$msId}", $e->getMessage());
            $stats['products']['errors']++;
        }
    });

    logLine("Товаров в МС: {$total}");
    logLine("Products: created={$stats['products']['created']} updated={$stats['products']['updated']} errors={$stats['products']['errors']}");
}

function importProductVariants(PDO $pdo, string $msProductId, int $productId): void
{
    try {
        $data     = msRequest('GET', '/entity/variant', null, [
            'filter' => 'product=https://api.moysklad.ru/api/remap/1.2/entity/product/' . $msProductId,
            'limit'  => 100,
        ]);
        $variants = $data['rows'] ?? [];
    } catch (Throwable) {
        return; // not critical
    }

    foreach ($variants as $v) {
        $variantMsId = $v['id'] ?? null;
        $chars = $v['characteristics'] ?? [];

        $size     = null;
        $color    = null;
        $material = null;

        foreach ($chars as $ch) {
            $chName = mb_strtolower($ch['name'] ?? '');
            if (!$size && (str_contains($chName, 'размер') || str_contains($chName, 'size'))) {
                $size = $ch['value'] ?? null;
            } elseif (!$color && (str_contains($chName, 'цвет') || str_contains($chName, 'color'))) {
                $color = $ch['value'] ?? null;
            } elseif (!$material && (str_contains($chName, 'материал') || str_contains($chName, 'material'))) {
                $material = $ch['value'] ?? null;
            }
        }

        if (!$size) continue;
        $size = mb_substr((string)$size, 0, 100);

        $salePrice = isset($v['salePrices'][0]['value']) ? (float)($v['salePrices'][0]['value'] / 100) : null;
        $stock     = (int)($v['quantity'] ?? 0);

        // Barcodes
        $barcodes     = $v['barcodes'] ?? [];
        $firstBarcode = null;
        foreach ($barcodes as $bc) {
            $vals = array_values($bc);
            if (!empty($vals[0])) { $firstBarcode = (string)$vals[0]; break; }
        }

        $charsJson = !empty($chars) ? json_encode($chars, JSON_UNESCAPED_UNICODE) : null;

        $mappedKeys = [
            'id', 'meta', 'accountId', 'product',
            'characteristics', 'barcodes', 'salePrices',
            'code', 'externalCode', 'quantity',
        ];
        $extra = buildExtra($v, $mappedKeys);

        // Lookup: prefer ms_variant_id, fallback to (product_id, size)
        $existing = null;
        if ($variantMsId) {
            $st = $pdo->prepare("SELECT id FROM product_size WHERE ms_variant_id = ? LIMIT 1");
            $st->execute([$variantMsId]);
            $existing = $st->fetch();
        }
        if (!$existing) {
            $st = $pdo->prepare("SELECT id FROM product_size WHERE product_id = ? AND size = ? LIMIT 1");
            $st->execute([$productId, $size]);
            $existing = $st->fetch();
        }

        $updateData = [
            'ms_variant_id'        => $variantMsId,
            'ms_code'              => $v['code'] ?? null,
            'ms_external_code'     => $v['externalCode'] ?? null,
            'ms_barcode'           => $firstBarcode,
            'characteristics_json' => $charsJson,
            'color'                => $color,
            'moysklad_extra'       => $extra,
        ];
        if ($salePrice !== null) $updateData['price'] = $salePrice;
        if ($stock > 0) $updateData['stock'] = $stock;

        if ($existing) {
            $set = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($updateData)));
            $pdo->prepare("UPDATE product_size SET {$set} WHERE id = :__id")
                ->execute(array_merge($updateData, ['__id' => $existing['id']]));
        } else {
            try {
                $insertData = array_merge($updateData, [
                    'product_id'   => $productId,
                    'size'         => $size,
                    'is_available' => 1,
                    'stock'        => $stock,
                ]);
                $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($insertData)));
                $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($insertData)));
                $pdo->prepare("INSERT INTO product_size ({$cols}) VALUES ({$vals})")->execute($insertData);
            } catch (Throwable) {
                // skip if unexpected NOT NULL constraint
            }
        }
    }
}

// ─── 2b. Import ALL variants in bulk (efficient) ─────────────────────────────

function importVariants(PDO $pdo): void
{
    global $stats;

    if (!isset($stats['variants'])) {
        $stats['variants'] = ['created' => 0, 'updated' => 0, 'errors' => 0];
    }

    logLine('═══ Импорт вариантов товаров (variants, bulk) ═══');

    $total = msFetchEach('/entity/variant', ['limit' => 100], function(array $v) use ($pdo): void {
        global $stats;

        $variantMsId = $v['id'] ?? null;
        if (!$variantMsId) return;

        // Resolve parent product
        $productHref = $v['product']['meta']['href'] ?? null;
        if (!$productHref) return;
        $hrefParts   = explode('/', $productHref);
        $productMsId = end($hrefParts);
        if (!$productMsId) return;

        $st = $pdo->prepare("SELECT id FROM product WHERE moysklad_id = ? LIMIT 1");
        $st->execute([$productMsId]);
        $productRow = $st->fetch();
        if (!$productRow) return;
        $productId = (int)$productRow['id'];

        // Parse characteristics
        $chars    = $v['characteristics'] ?? [];
        $size     = null;
        $color    = null;
        $material = null;
        foreach ($chars as $ch) {
            $chName = mb_strtolower($ch['name'] ?? '');
            if (!$size && (str_contains($chName, 'размер') || str_contains($chName, 'size'))) {
                $size = $ch['value'] ?? null;
            } elseif (!$color && (str_contains($chName, 'цвет') || str_contains($chName, 'color'))) {
                $color = $ch['value'] ?? null;
            } elseif (!$material && (str_contains($chName, 'материал') || str_contains($chName, 'material'))) {
                $material = $ch['value'] ?? null;
            }
        }

        if (!$size) return;
        $size = mb_substr((string)$size, 0, 100);

        $salePrice = isset($v['salePrices'][0]['value']) ? (float)($v['salePrices'][0]['value'] / 100) : null;
        $stock     = (int)($v['quantity'] ?? 0);

        $barcodes     = $v['barcodes'] ?? [];
        $firstBarcode = null;
        foreach ($barcodes as $bc) {
            $vals = array_values($bc);
            if (!empty($vals[0])) { $firstBarcode = (string)$vals[0]; break; }
        }

        $charsJson  = !empty($chars) ? json_encode($chars, JSON_UNESCAPED_UNICODE) : null;
        $mappedKeys = ['id', 'meta', 'accountId', 'product', 'characteristics', 'barcodes', 'salePrices', 'code', 'externalCode', 'quantity'];
        $extra      = buildExtra($v, $mappedKeys);

        // Lookup existing row
        $existing = null;
        $st = $pdo->prepare("SELECT id FROM product_size WHERE ms_variant_id = ? LIMIT 1");
        $st->execute([$variantMsId]);
        $existing = $st->fetch();

        if (!$existing) {
            $st = $pdo->prepare("SELECT id FROM product_size WHERE product_id = ? AND size = ? LIMIT 1");
            $st->execute([$productId, $size]);
            $existing = $st->fetch();
        }

        $updateData = [
            'ms_variant_id'        => $variantMsId,
            'ms_code'              => $v['code']         ?? null,
            'ms_external_code'     => $v['externalCode'] ?? null,
            'ms_barcode'           => $firstBarcode,
            'characteristics_json' => $charsJson,
            'color'                => $color,
            'moysklad_extra'       => $extra,
        ];
        if ($salePrice !== null) $updateData['price'] = $salePrice;
        if ($stock > 0)          $updateData['stock'] = $stock;

        if ($existing) {
            $set = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($updateData)));
            $pdo->prepare("UPDATE product_size SET {$set} WHERE id = :__id")
                ->execute(array_merge($updateData, ['__id' => $existing['id']]));
            $stats['variants']['updated']++;
        } else {
            try {
                $insertData = array_merge($updateData, [
                    'product_id'   => $productId,
                    'size'         => $size,
                    'is_available' => 1,
                    'stock'        => $stock,
                ]);
                $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($insertData)));
                $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($insertData)));
                $pdo->prepare("INSERT INTO product_size ({$cols}) VALUES ({$vals})")->execute($insertData);
                $stats['variants']['created']++;
            } catch (Throwable $e) {
                logErr("variant:{$variantMsId}", $e->getMessage());
                $stats['variants']['errors']++;
            }
        }
    });

    logLine("Вариантов в МС: {$total}");
    logLine("Variants: created={$stats['variants']['created']} updated={$stats['variants']['updated']} errors={$stats['variants']['errors']}");
}

// ─── 3. Import orders (заказы покупателей) ───────────────────────────────────

function importOrders(PDO $pdo): void
{
    global $stats;

    logLine('═══ Импорт заказов покупателей (orders) ═══');

    $now   = time();
    $total = msFetchEach('/entity/customerorder', [
        'expand' => 'agent,state,positions.assortment,salesChannel,store,organization,contract,project',
        'order'  => 'created,desc',
    ], function(array $msOrder) use ($pdo, &$stats, $now): void {
        $msId = $msOrder['id'] ?? null;
        if (!$msId) return;

        try {
            $st = $pdo->prepare("SELECT id FROM `order` WHERE moysklad_id = ? LIMIT 1");
            $st->execute([$msId]);
            $dbRow = $st->fetch();

            if (!$dbRow) {
                $msName = $msOrder['name'] ?? '';
                if ($msName) {
                    $st = $pdo->prepare("SELECT id FROM `order` WHERE order_number = ? OR ms_number = ? LIMIT 1");
                    $st->execute([$msName, $msName]);
                    $dbRow = $st->fetch();
                }
            }

            // Resolve customer via agent moysklad_id
            $customerId  = null;
            $agent       = $msOrder['agent'] ?? null;
            $agentMsId   = $agent['id'] ?? msIdFromHref($agent['meta']['href'] ?? null);
            $clientName  = trim($agent['name'] ?? 'Покупатель');
            $clientPhone = normalizePhone($agent['phone'] ?? '');
            $clientEmail = trim($agent['email'] ?? '');

            if ($agentMsId) {
                $st = $pdo->prepare("SELECT id FROM customer WHERE moysklad_id = ? LIMIT 1");
                $st->execute([$agentMsId]);
                $row        = $st->fetch();
                $customerId = $row ? (int)$row['id'] : null;
            }

            $createdTs = msDateToTs($msOrder['moment'] ?? $msOrder['created'] ?? null) ?: $now;
            $updatedTs = msDateToTs($msOrder['updated'] ?? null) ?: $now;
            $updatedDt = date('Y-m-d H:i:s', $updatedTs);

            $msStateName = $msOrder['state']['name'] ?? '';
            $ourStatus   = $msStateName ? mapMsStatus($msStateName) : 'created';

            // Amounts (МС stores in kopecks)
            $totalAmount  = isset($msOrder['sum'])          ? round($msOrder['sum']          / 100, 2) : 0.0;
            $deliveryCost = isset($msOrder['shippingCost']) ? round($msOrder['shippingCost'] / 100, 2) : 0.0;
            $payedSum     = isset($msOrder['payedSum'])     ? round($msOrder['payedSum']     / 100, 2) : null;
            $shippedSum   = isset($msOrder['shippedSum'])   ? round($msOrder['shippedSum']   / 100, 2) : null;
            $invoicedSum  = isset($msOrder['invoicedSum'])  ? round($msOrder['invoicedSum']  / 100, 2) : null;
            $reservedSum  = isset($msOrder['reservedSum'])  ? round($msOrder['reservedSum']  / 100, 2) : null;
            $vatSum       = isset($msOrder['vatSum'])       ? round($msOrder['vatSum']       / 100, 2) : null;

            $deliveryAddr  = $msOrder['shipmentAddress'] ?? null;
            $shipAddrFull  = $msOrder['shipmentAddressFull'] ?? null;
            $shipAddrJson  = $shipAddrFull ? json_encode($shipAddrFull, JSON_UNESCAPED_UNICODE) : null;
            $comment       = $msOrder['description'] ?? null;
            $orderNumber   = $msOrder['name'] ?? ('MS-' . substr($msId, 0, 8));

            // Currency rate
            $rate    = $msOrder['rate'] ?? null;
            $rateVal = isset($rate['value']) ? (float)$rate['value'] : null;
            $rateCur = $rate['currency']['name'] ?? null;

            // Sales channel & store
            $salesChannel = $msOrder['salesChannel']['name'] ?? null;
            $storeName    = $msOrder['store']['name'] ?? null;

            // Organization
            $orgId   = $msOrder['organization']['id'] ?? null;
            $orgName = $msOrder['organization']['name'] ?? null;

            // Contract
            $contractId   = $msOrder['contract']['id'] ?? null;
            $contractName = $msOrder['contract']['name'] ?? null;

            // Project
            $projectId   = $msOrder['project']['id'] ?? null;
            $projectName = $msOrder['project']['name'] ?? null;

            // Delivery planned moment
            $deliveryPlannedMoment = msDateToDt($msOrder['deliveryPlannedMoment'] ?? null);

            // VAT flags
            $vatEnabled  = isset($msOrder['vatEnabled'])  ? ($msOrder['vatEnabled']  ? 1 : 0) : null;
            $vatIncluded = isset($msOrder['vatIncluded']) ? ($msOrder['vatIncluded'] ? 1 : 0) : null;

            // Created datetime (MS "created" field = order creation time; "moment" = document date)
            $msCreatedDt = msDateToDt($msOrder['created'] ?? null);

            // printed / published
            $printed   = isset($msOrder['printed'])   ? ($msOrder['printed']   ? 1 : 0) : null;
            $published  = isset($msOrder['published']) ? ($msOrder['published'] ? 1 : 0) : null;

            // Linked payments summary JSON
            $linkedPayments = null;
            if (!empty($msOrder['payments'])) {
                $pArr = [];
                foreach ($msOrder['payments'] as $lp) {
                    $pArr[] = [
                        'id'         => $lp['id'] ?? msIdFromHref($lp['meta']['href'] ?? null),
                        'type'       => $lp['meta']['type'] ?? null,
                        'linkedSum'  => isset($lp['linkedSum']) ? round($lp['linkedSum'] / 100, 2) : null,
                    ];
                }
                $linkedPayments = json_encode($pArr, JSON_UNESCAPED_UNICODE);
            }

            // Linked demands JSON
            $demandsJson = null;
            if (!empty($msOrder['demands'])) {
                $dArr = [];
                foreach ($msOrder['demands'] as $d) {
                    $dArr[] = ['id' => $d['id'] ?? msIdFromHref($d['meta']['href'] ?? null), 'type' => $d['meta']['type'] ?? 'demand'];
                }
                $demandsJson = json_encode($dArr, JSON_UNESCAPED_UNICODE);
            }

            // Linked invoicesOut JSON
            $invoicesOutJson = null;
            if (!empty($msOrder['invoicesOut'])) {
                $iArr = [];
                foreach ($msOrder['invoicesOut'] as $inv) {
                    $iArr[] = ['id' => $inv['id'] ?? msIdFromHref($inv['meta']['href'] ?? null), 'type' => $inv['meta']['type'] ?? 'invoiceout'];
                }
                $invoicesOutJson = json_encode($iArr, JSON_UNESCAPED_UNICODE);
            }

            // Positions count
            $positionsCount = (int)($msOrder['positions']['meta']['size'] ?? count($msOrder['positions']['rows'] ?? []));

            // Agent extended fields
            $agentCompanyType   = $agent['companyType'] ?? null;
            $agentLegalTitle    = $agent['legalTitle'] ?? null;
            $agentActualAddress = $agent['actualAddress'] ?? null;

            // Raw attributes JSON + parsed map keyed by lowercased name
            $rawAttrs  = $msOrder['attributes'] ?? [];
            $attrsJson = !empty($rawAttrs) ? json_encode($rawAttrs, JSON_UNESCAPED_UNICODE) : null;
            $a         = extractAttrs($rawAttrs);

            $mappedKeys = [
                'id', 'meta', 'accountId', 'owner', 'group', 'shared',
                'name', 'externalCode', 'moment', 'created', 'updated', 'applicable',
                'agent', 'state', 'positions', 'salesChannel', 'store', 'rate',
                'organization', 'contract', 'project', 'deliveryPlannedMoment',
                'sum', 'shippingCost', 'payedSum', 'shippedSum', 'invoicedSum', 'reservedSum', 'vatSum',
                'shipmentAddress', 'shipmentAddressFull', 'description', 'attributes',
                'vatEnabled', 'vatIncluded', 'printed', 'published',
                'payments', 'demands', 'invoicesOut',
            ];
            $extra = buildExtra($msOrder, $mappedKeys);

            $data = [
                'moysklad_id'              => $msId,
                'ms_external_code'         => $msOrder['externalCode'] ?? null,
                'ms_applicable'            => isset($msOrder['applicable']) ? ($msOrder['applicable'] ? 1 : 0) : null,
                'customer_id'              => $customerId,
                'order_number'             => $orderNumber,
                'ms_number'                => $orderNumber,
                'client_name'              => $clientName ?: 'Покупатель',
                'client_phone'             => $clientPhone ?: null,
                'client_email'             => $clientEmail ?: null,
                'status'                   => $ourStatus,
                'total_amount'             => $totalAmount,
                'delivery_cost'            => $deliveryCost,
                'ms_payed_sum'             => $payedSum,
                'ms_shipped_sum'           => $shippedSum,
                'ms_invoiced_sum'          => $invoicedSum,
                'ms_reserved_sum'          => $reservedSum,
                'ms_vat_sum'               => $vatSum,
                'ms_rate_value'            => $rateVal,
                'ms_rate_currency'         => $rateCur,
                'ms_sales_channel'         => $salesChannel,
                'ms_store_name'            => $storeName,
                'delivery_address'         => $deliveryAddr,
                'ms_shipment_address_json' => $shipAddrJson,
                'ms_attributes_json'       => $attrsJson,
                'ms_updated'               => $updatedDt,
                'comment'                  => $comment,
                'source'                   => 'moysklad',
                'updated_at'               => $updatedTs,
                'moysklad_extra'           => $extra,
                // ── custom attribute fields (дополнительные поля) ────────────
                'ms_organization_id'        => $orgId,
                'ms_organization_name'      => $orgName,
                'ms_contract_id'            => $contractId,
                'ms_contract_name'          => $contractName,
                'ms_project_id'             => $projectId,
                'ms_project_name'           => $projectName,
                'ms_delivery_planned_moment'=> $deliveryPlannedMoment,
                'ms_vat_enabled'            => $vatEnabled,
                'ms_vat_included'           => $vatIncluded,
                'ms_created'                => $msCreatedDt,
                'ms_printed'                => $printed,
                'ms_published'              => $published,
                'ms_linked_payments_json'   => $linkedPayments,
                'ms_demands_json'           => $demandsJson,
                'ms_invoices_out_json'      => $invoicesOutJson,
                'ms_positions_count'        => $positionsCount ?: null,
                'ms_agent_company_type'     => $agentCompanyType,
                'ms_agent_legal_title'      => $agentLegalTitle,
                'ms_agent_actual_address'   => $agentActualAddress,
                // ── custom attribute fields (дополнительные поля) ────────────
                'ms_lead_source'           => $a['источник заявки'] ?? null,
                'ms_delivery_type'         => $a['тип доставки'] ?? null,
                'delivery_method'          => $a['тип доставки'] ?? null, // standard column too
                'ms_deal_link'             => $a['ссылка на сделку'] ?? null,
                'ms_amo_created_at'        => $a['дата создания [amo]'] ?? null,
                'product_link'             => $a['ссылка на товар'] ?? null,
                'ms_order_size'            => $a['размер'] ?? null,
                'ms_via_widget'            => isset($a['заказ создан через виджет']) ? ($a['заказ создан через виджет'] ? 1 : 0) : null,
                'ms_passport_transferred'  => isset($a['перенесено в таблицу паспортов']) ? ($a['перенесено в таблицу паспортов'] ? 1 : 0) : null,
                'ms_cancel_reason'         => $a['причина отказа'] ?? null,
                'ms_waybill_link'          => $a['ссылка на накладную сдэк / европочта'] ?? null,
                'ms_cancel_date'           => $a['дата отмены/возврата'] ?? null,
                'ms_pickup_date'           => $a['дата приезда в магазин за заказом'] ?? null,
            ];

            $isUpdate = (bool)$dbRow;
            $orderId  = null;

            if ($isUpdate) {
                $orderId = (int)$dbRow['id'];
                $set     = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
                $stmt    = $pdo->prepare("UPDATE `order` SET {$set} WHERE id = :__id");
                $stmt->execute(array_merge($data, ['__id' => $orderId]));
                $stats['orders']['updated']++;
            } else {
                $data['token']      = bin2hex(random_bytes(16));
                $data['created_at'] = $createdTs;

                $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
                $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
                $pdo->prepare("INSERT INTO `order` ({$cols}) VALUES ({$vals})")->execute($data);
                $orderId = (int)$pdo->lastInsertId();
                $stats['orders']['created']++;
            }

            importOrderPositions($pdo, $orderId, $msOrder, $isUpdate);
        } catch (Throwable $e) {
            logErr("order:{$msId}", $e->getMessage());
            $stats['orders']['errors']++;
        }
    });

    logLine("Заказов в МС: {$total}");
    logLine("Orders: created={$stats['orders']['created']} updated={$stats['orders']['updated']} errors={$stats['orders']['errors']}");
}

function importOrderPositions(PDO $pdo, int $orderId, array $msOrder, bool $isUpdate): void
{
    $positions = $msOrder['positions']['rows'] ?? [];
    if (empty($positions)) return;

    if ($isUpdate) {
        $pdo->prepare("DELETE FROM order_item WHERE order_id = ?")->execute([$orderId]);
    }

    $now = time();

    foreach ($positions as $pos) {
        $positionId  = $pos['id'] ?? null;
        $assortment  = $pos['assortment'] ?? null;
        $productName = $assortment['name'] ?? ($pos['name'] ?? 'Товар');
        $article     = $assortment['article'] ?? $assortment['code'] ?? null;
        $quantity    = (int)($pos['quantity'] ?? 1);
        $price       = isset($pos['price'])    ? round($pos['price']    / 100, 2) : 0.0;
        $discount    = (float)($pos['discount'] ?? 0);
        $priceAfterDiscount = $price * (1 - $discount / 100);
        $total       = round($priceAfterDiscount * $quantity, 2);
        $posVat        = isset($pos['vat']) ? (int)$pos['vat'] : null;
        $posVatEnabled = isset($pos['vatEnabled']) ? ($pos['vatEnabled'] ? 1 : 0) : null;
        $posShipped    = (int)($pos['shipped']  ?? 0);
        $posReserve    = (int)($pos['reserve']  ?? 0);

        // Resolve product by assortment moysklad_id (variant or product)
        $productId  = null;
        $assortMsId = $assortment['id'] ?? msIdFromHref($assortment['meta']['href'] ?? null);
        if ($assortMsId) {
            $st = $pdo->prepare("SELECT id FROM product WHERE moysklad_id = ? LIMIT 1");
            $st->execute([$assortMsId]);
            $row       = $st->fetch();
            $productId = $row ? (int)$row['id'] : null;

            // Variant → try parent product
            if (!$productId && $assortment) {
                $parentHref = $assortment['product']['meta']['href'] ?? null;
                $parentMsId = msIdFromHref($parentHref);
                if ($parentMsId) {
                    $st->execute([$parentMsId]);
                    $row       = $st->fetch();
                    $productId = $row ? (int)$row['id'] : null;
                }
            }
        }

        // Extract size and color from variant characteristics
        $size  = null;
        $color = null;
        if (!empty($assortment['characteristics'])) {
            foreach ($assortment['characteristics'] as $ch) {
                $chName = mb_strtolower($ch['name'] ?? '');
                if (!$size && (str_contains($chName, 'размер') || str_contains($chName, 'size'))) {
                    $size = $ch['value'] ?? null;
                }
                if (!$color && (str_contains($chName, 'цвет') || str_contains($chName, 'color'))) {
                    $color = $ch['value'] ?? null;
                }
            }
        }
        if (!$size && !empty($assortment['code'])) {
            $size = $assortment['code'];
        }

        $pdo->prepare("
            INSERT INTO order_item
                (order_id, product_id, product_name, product_article, size, color,
                 quantity, price, `total`, created_at,
                 ms_position_id, ms_assortment_id, discount, vat, ms_vat_enabled, ms_shipped, ms_reserve)
            VALUES (?, ?, ?, ?, ?, ?,  ?, ?, ?, ?,  ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $orderId, $productId, $productName, $article, $size, $color,
            $quantity, $priceAfterDiscount, $total, $now,
            $positionId, $assortMsId, $discount, $posVat, $posVatEnabled, $posShipped, $posReserve,
        ]);
    }
}

// ─── 4. Import supplies / приёмки ────────────────────────────────────────────

function importSupplies(PDO $pdo): void
{
    global $stats;

    logLine('═══ Импорт приёмок (supplies → purchase_orders) ═══');

    $total = msFetchEach('/entity/supply', [
        'expand' => 'agent,state,positions.assortment',
        'order'  => 'created,desc',
    ], function(array $supply) use ($pdo, &$stats): void {
        $msId = $supply['id'] ?? null;
        if (!$msId) return;

        try {
            $st = $pdo->prepare("SELECT id FROM purchase_order WHERE moysklad_id = ? LIMIT 1");
            $st->execute([$msId]);
            $dbRow = $st->fetch();

            if (!$dbRow) {
                $msName = $supply['name'] ?? '';
                if ($msName) {
                    $st = $pdo->prepare("SELECT id FROM purchase_order WHERE purchase_number = ? LIMIT 1");
                    $st->execute([$msName]);
                    $dbRow = $st->fetch();
                }
            }

            $agent      = $supply['agent'] ?? null;
            $supplierId = findOrCreateSupplier($pdo, $agent);

            $orderedAt  = msDateToDt($supply['moment'] ?? $supply['created'] ?? null);
            $receivedAt = msDateToDt($supply['updated'] ?? null);
            $totalByn   = isset($supply['sum'])    ? round($supply['sum']    / 100, 2) : 0.0;
            $vatSum     = isset($supply['vatSum']) ? round($supply['vatSum'] / 100, 2) : null;

            $msState   = mb_strtolower($supply['state']['name'] ?? '');
            $ourStatus = match(true) {
                str_contains($msState, 'принят') || str_contains($msState, 'получ') => 'received',
                str_contains($msState, 'отмен')                                     => 'canceled',
                default                                                               => 'ordered',
            };

            $purchaseNumber = $supply['name'] ?? ('PO-MS-' . substr($msId, 0, 8));

            $rate    = $supply['rate'] ?? null;
            $rateVal = isset($rate['value']) ? (float)$rate['value'] : null;

            $mappedKeys = [
                'id', 'meta', 'accountId', 'owner', 'group', 'shared',
                'name', 'externalCode', 'moment', 'created', 'updated', 'applicable',
                'agent', 'state', 'positions', 'organization',
                'sum', 'vatSum', 'rate', 'description',
            ];
            $extra = buildExtra($supply, $mappedKeys);

            $data = [
                'moysklad_id'      => $msId,
                'ms_external_code' => $supply['externalCode'] ?? null,
                'ms_applicable'    => isset($supply['applicable']) ? ($supply['applicable'] ? 1 : 0) : null,
                'ms_vat_sum'       => $vatSum,
                'ms_rate_value'    => $rateVal,
                'purchase_number'  => $purchaseNumber,
                'supplier_id'      => $supplierId,
                'status'           => $ourStatus,
                'total_amount_byn' => $totalByn,
                'notes'            => $supply['description'] ?? null,
                'ordered_at'       => $orderedAt,
                'received_at'      => $receivedAt,
                'moysklad_extra'   => $extra,
            ];

            $isUpdate = (bool)$dbRow;
            $poId     = null;

            if ($isUpdate) {
                $poId = (int)$dbRow['id'];
                $set  = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
                $stmt = $pdo->prepare("UPDATE purchase_order SET {$set} WHERE id = :__id");
                $stmt->execute(array_merge($data, ['__id' => $poId]));
                $stats['supplies']['updated']++;
            } else {
                $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
                $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
                $pdo->prepare("INSERT INTO purchase_order ({$cols}) VALUES ({$vals})")->execute($data);
                $poId = (int)$pdo->lastInsertId();
                $stats['supplies']['created']++;
            }

            importSupplyPositions($pdo, $poId, $supply, $isUpdate);
        } catch (Throwable $e) {
            logErr("supply:{$msId}", $e->getMessage());
            $stats['supplies']['errors']++;
        }
    });

    logLine("Приёмок в МС: {$total}");
    logLine("Supplies: created={$stats['supplies']['created']} updated={$stats['supplies']['updated']} errors={$stats['supplies']['errors']}");
}

function findOrCreateSupplier(PDO $pdo, ?array $agent): int
{
    $name  = trim($agent['name'] ?? '');
    $phone = normalizePhone($agent['phone'] ?? '');
    $email = trim($agent['email'] ?? '');
    if (!$name) $name = 'МойСклад (импорт)';

    $st = $pdo->prepare("SELECT id FROM supplier WHERE name = ? LIMIT 1");
    $st->execute([$name]);
    $row = $st->fetch();
    if ($row) return (int)$row['id'];

    $pdo->prepare("INSERT INTO supplier (name, phone, email, is_active, created_at) VALUES (?, ?, ?, 1, NOW())")
        ->execute([$name, $phone ?: null, $email ?: null]);
    return (int)$pdo->lastInsertId();
}

function importSupplyPositions(PDO $pdo, int $poId, array $supply, bool $isUpdate): void
{
    $positions = $supply['positions']['rows'] ?? [];
    if (empty($positions)) return;

    if ($isUpdate) {
        $pdo->prepare("DELETE FROM purchase_order_item WHERE purchase_order_id = ?")->execute([$poId]);
    }

    foreach ($positions as $pos) {
        $assortment  = $pos['assortment'] ?? null;
        $productName = $assortment['name'] ?? 'Товар';
        $quantity    = (int)($pos['quantity'] ?? 1);
        $priceByn    = isset($pos['price']) ? round($pos['price'] / 100, 2) : 0.0;

        $productId  = null;
        $assortMsId = msIdFromHref($assortment['meta']['href'] ?? null);
        if ($assortMsId) {
            $st = $pdo->prepare("SELECT id FROM product WHERE moysklad_id = ? LIMIT 1");
            $st->execute([$assortMsId]);
            $row       = $st->fetch();
            $productId = $row ? (int)$row['id'] : null;
        }

        $size = null;
        if (!empty($assortment['characteristics'])) {
            foreach ($assortment['characteristics'] as $ch) {
                $chName = mb_strtolower($ch['name'] ?? '');
                if (str_contains($chName, 'размер') || str_contains($chName, 'size')) {
                    $size = $ch['value'] ?? null;
                    break;
                }
            }
        }

        $pdo->prepare("
            INSERT INTO purchase_order_item
                (purchase_order_id, product_id, product_name, size, quantity, price_byn)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$poId, $productId, $productName, $size !== null ? mb_substr($size, 0, 50) : null, $quantity, $priceByn]);
    }
}

// ─── 5. Import payments (входящие платежи) ───────────────────────────────────

function importPayments(PDO $pdo): void
{
    global $stats;

    logLine('═══ Импорт входящих платежей (paymentin) ═══');

    $total = msFetchEach('/entity/paymentin', [
        'expand' => 'agent,operations',
        'order'  => 'created,desc',
    ], function(array $payment) use ($pdo, &$stats): void {
        $msId = $payment['id'] ?? null;
        if (!$msId) return;

        try {
            $st = $pdo->prepare("SELECT id FROM payment WHERE moysklad_id = ? LIMIT 1");
            $st->execute([$msId]);
            $dbRow = $st->fetch();

            // Resolve linked order and customer via operations list
            $orderId    = null;
            $customerId = null;

            $ops = $payment['operations']['rows'] ?? (is_array($payment['operations']) ? $payment['operations'] : []);
            foreach ($ops as $op) {
                $opType  = $op['meta']['type'] ?? '';
                $opMsId  = msIdFromHref($op['meta']['href'] ?? null);
                if ($opType === 'customerorder' && $opMsId) {
                    $st2 = $pdo->prepare("SELECT id, customer_id FROM `order` WHERE moysklad_id = ? LIMIT 1");
                    $st2->execute([$opMsId]);
                    $orderRow = $st2->fetch();
                    if ($orderRow) {
                        $orderId    = (int)$orderRow['id'];
                        $customerId = $orderRow['customer_id'] ? (int)$orderRow['customer_id'] : null;
                        break;
                    }
                }
            }

            // Fallback: customer from agent
            if (!$customerId) {
                $agent     = $payment['agent'] ?? null;
                $agentMsId = $agent['id'] ?? msIdFromHref($agent['meta']['href'] ?? null);
                if ($agentMsId) {
                    $st2 = $pdo->prepare("SELECT id FROM customer WHERE moysklad_id = ? LIMIT 1");
                    $st2->execute([$agentMsId]);
                    $custRow    = $st2->fetch();
                    $customerId = $custRow ? (int)$custRow['id'] : null;
                }
            }

            $amount    = isset($payment['sum']) ? round($payment['sum'] / 100, 2) : 0.0;
            $purpose   = $payment['purpose'] ?? $payment['name'] ?? $payment['description'] ?? null;
            $createdAt = msDateToDt($payment['moment'] ?? $payment['created'] ?? null) ?: date('Y-m-d H:i:s');
            $agentName = $payment['agent']['name'] ?? null;

            $rate    = $payment['rate'] ?? null;
            $rateVal = isset($rate['value']) ? (float)$rate['value'] : null;

            $mappedKeys = [
                'id', 'meta', 'accountId', 'owner', 'group', 'shared',
                'name', 'externalCode', 'moment', 'created', 'updated', 'applicable',
                'agent', 'operations', 'organization',
                'sum', 'purpose', 'description', 'rate',
            ];
            $extra = buildExtra($payment, $mappedKeys);

            $data = [
                'moysklad_id'      => $msId,
                'ms_external_code' => $payment['externalCode'] ?? null,
                'ms_applicable'    => isset($payment['applicable']) ? ($payment['applicable'] ? 1 : 0) : null,
                'ms_rate_value'    => $rateVal,
                'ms_agent_name'    => $agentName,
                'ms_in_number'     => $payment['name'] ?? null,
                'order_id'         => $orderId,
                'customer_id'      => $customerId,
                'amount'           => $amount,
                'currency'         => 'BYN',
                'payment_method'   => 'bank_transfer',
                'status'           => 'confirmed',
                'description'      => $purpose,
                'updated_at'       => date('Y-m-d H:i:s'),
                'moysklad_extra'   => $extra,
            ];

            if ($dbRow) {
                $set  = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
                $stmt = $pdo->prepare("UPDATE payment SET {$set} WHERE id = :__id");
                $stmt->execute(array_merge($data, ['__id' => $dbRow['id']]));
                $stats['payments']['updated']++;
            } else {
                $data['created_at'] = $createdAt;
                $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
                $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
                $pdo->prepare("INSERT INTO payment ({$cols}) VALUES ({$vals})")->execute($data);
                $stats['payments']['created']++;
            }
        } catch (Throwable $e) {
            logErr("payment:{$msId}", $e->getMessage());
            $stats['payments']['errors']++;
        }
    });

    logLine("Платежей в МС: {$total}");
    logLine("Payments: created={$stats['payments']['created']} updated={$stats['payments']['updated']} errors={$stats['payments']['errors']}");
}

// ─── Main ────────────────────────────────────────────────────────────────────

$what = $argv[1] ?? 'all';
$all  = ($what === 'all');

echo "\n";
echo "╔══════════════════════════════════════════════╗\n";
echo "║   МойСклад → БД: полный импорт               ║\n";
echo "║   " . date('Y-m-d H:i:s') . "                          ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

// Test MoySklad connection
try {
    logLine('Проверка подключения к МойСклад...');
    $connTest = msRequest('GET', '/entity/organization', null, ['limit' => 1]);
    $orgName  = $connTest['rows'][0]['name'] ?? '?';
    logLine("Подключено ✓  Организация: {$orgName}");
} catch (Throwable $e) {
    echo "\nОШИБКА подключения к МойСклад: " . $e->getMessage() . "\n";
    echo "Проверьте логин/пароль и доступность api.moysklad.ru\n";
    exit(1);
}

echo "\n";

// Analyze mode — dump full API field structure and exit
if ($what === '--analyze') {
    runAnalyze();
    exit(0);
}

// Apply all schema migrations before import
setupSchema($pdo);
echo "\n";

// Run selected imports
if ($all || $what === 'customers') { importCustomers($pdo); echo "\n"; }
if ($all || $what === 'products')  { importProducts($pdo);  echo "\n"; }
if ($all || $what === 'variants')  { importVariants($pdo);  echo "\n"; }
if ($all || $what === 'orders')    { importOrders($pdo);    echo "\n"; }
if ($all || $what === 'supplies')  { importSupplies($pdo);  echo "\n"; }
if ($all || $what === 'payments')  { importPayments($pdo);  echo "\n"; }

// ─── Summary ─────────────────────────────────────────────────────────────────

echo str_repeat('═', 54) . "\n";
echo "ИТОГО ИМПОРТ\n";
echo str_repeat('─', 54) . "\n";
printf("  %-12s  %8s  %8s  %8s\n", 'Сущность', 'Создано', 'Обновлено', 'Ошибок');
echo str_repeat('─', 54) . "\n";

$runEntities = $all
    ? array_keys($stats)
    : (array_key_exists($what, $stats) ? [$what] : []);

foreach ($runEntities as $entity) {
    $s = $stats[$entity];
    printf("  %-12s  %8d  %8d  %8d\n", $entity, $s['created'], $s['updated'], $s['errors']);
}
echo str_repeat('═', 54) . "\n";

if (!empty($errorLog)) {
    echo "\nОШИБКИ (" . count($errorLog) . "):\n";
    foreach (array_slice($errorLog, 0, 30) as $e) {
        echo "  $e\n";
    }
    if (count($errorLog) > 30) {
        echo '  ... и ещё ' . (count($errorLog) - 30) . " ошибок\n";
    }
}

echo "\nГотово.\n";
