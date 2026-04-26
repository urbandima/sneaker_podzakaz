<?php
declare(strict_types=1);

/**
 * МойСклад → БД: верификация импорта одного заказа и одного товара
 *
 * Показывает side-by-side: что пришло из API vs что сохранено в БД.
 *
 * Usage:
 *   php scripts/verify_ms_import.php
 */

const VERIFY_ORDER_ID   = '20d6861a-3a69-11f1-0a80-13160020c92d';
const VERIFY_PRODUCT_ID = 'f1f3695f-6f3e-11ee-0a80-1303000f3b05';

// SECURITY FIX: credentials loaded from .env
$dotenv = parse_ini_file(__DIR__ . '/../.env');
define('MS_LOGIN_VALUE', $dotenv['MS_LOGIN'] ?? getenv('MS_LOGIN') ?: die("MS_LOGIN not set in .env\n"));
define('MS_PASSWORD', $dotenv['MS_PASSWORD'] ?? getenv('MS_PASSWORD') ?: die("MS_PASSWORD not set in .env\n"));
const MS_BASE     = 'https://api.moysklad.ru/api/remap/1.2';

const DB_HOST = '127.0.0.1';
const DB_NAME = 'sneakerhead';
const DB_USER = 'sneaker';
const DB_PASS = 'secret'; // TODO: also move to .env

ini_set('memory_limit', '256M');

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$msAuth = 'Basic ' . base64_encode(MS_LOGIN . ':' . MS_PASSWORD);

// ─── HTTP helper ─────────────────────────────────────────────────────────────

function vmsGet(string $path, array $query = []): array
{
    global $msAuth;
    $url = MS_BASE . $path;
    if ($query) $url .= '?' . http_build_query($query);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_HTTPHEADER     => ['Authorization: ' . $msAuth, 'Accept-Encoding: gzip'],
        CURLOPT_ENCODING       => 'gzip',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($resp === false || $code === 0) throw new RuntimeException('curl: ' . $err);
    $data = json_decode($resp, true) ?? [];
    if ($code >= 400) {
        $msg = $data['errors'][0]['error'] ?? substr($resp, 0, 200);
        throw new RuntimeException("МС {$code}: {$msg}");
    }
    return $data;
}

// ─── Utility ─────────────────────────────────────────────────────────────────

function vmsIdFromHref(?string $href): ?string
{
    if (!$href) return null;
    $parts = explode('/', $href);
    $last  = end($parts);
    return preg_replace('/\?.*/', '', $last) ?: null;
}

function vmsMsDateToDt(?string $d): ?string
{
    if (!$d) return null;
    $ts = strtotime(preg_replace('/\.\d+/', '', $d));
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

function vmsExtractAttrs(array $attributes): array
{
    $result = [];
    foreach ($attributes as $attr) {
        $name = mb_strtolower(trim($attr['name'] ?? ''));
        if (!$name) continue;
        $type  = $attr['type'] ?? 'string';
        $value = $attr['value'] ?? null;
        if ($value === null) continue;
        $result[$name] = match($type) {
            'boolean'      => (bool)$value,
            'time'         => vmsMsDateToDt(is_string($value) ? $value : null),
            'customentity' => is_array($value) ? ($value['name'] ?? null) : null,
            'double'       => is_numeric($value) ? (float)$value : null,
            'long'         => is_numeric($value) ? (int)$value   : null,
            default        => is_scalar($value) ? (string)$value  : null,
        };
    }
    return $result;
}

function vmsExtractPrices(array $salePrices): array
{
    $result = [];
    foreach ($salePrices as $sp) {
        $name = mb_strtolower($sp['priceType']['name'] ?? '');
        if (!$name) continue;
        $result[$name] = isset($sp['value']) ? round((float)$sp['value'] / 100, 2) : 0.0;
    }
    return $result;
}

function vmsNormalizePhone(string $phone): string
{
    $d = preg_replace('/\D/', '', $phone);
    if (!$d) return '';
    if (strlen($d) === 11 && $d[0] === '8') $d = '7' . substr($d, 1);
    return '+' . $d;
}

// ─── Import helpers (same logic as import_from_moysklad.php) ──────────────────

function ensureVerifyColumn(PDO $pdo, string $table, string $col, string $def): void
{
    $rows = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'")->fetchAll();
    if (!empty($rows)) return;
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}");
    echo "  [schema] добавлена колонка {$col} в {$table}\n";
}

function ensureVerifyMoyskladId(PDO $pdo, string $table): void
{
    $rows = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE 'moysklad_id'")->fetchAll();
    if (!empty($rows)) return;
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `moysklad_id` VARCHAR(36) NULL DEFAULT NULL");
    $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `idx_ms_{$table}` (`moysklad_id`)");
    echo "  [schema] добавлена moysklad_id в {$table}\n";
}

function setupVerifySchema(PDO $pdo): void
{
    foreach (['customer','product','purchase_order','payment'] as $t) ensureVerifyMoyskladId($pdo, $t);
    foreach (['customer','product','product_size','order','purchase_order','payment'] as $t) {
        $rows = $pdo->query("SHOW COLUMNS FROM `{$t}` LIKE 'moysklad_extra'")->fetchAll();
        if (empty($rows)) $pdo->exec("ALTER TABLE `{$t}` ADD COLUMN `moysklad_extra` JSON NULL DEFAULT NULL");
    }
    // order columns
    $ordCols = [
        'ms_external_code'         => 'VARCHAR(100) NULL DEFAULT NULL',
        'ms_applicable'            => 'TINYINT(1) NULL DEFAULT NULL',
        'ms_payed_sum'             => 'DECIMAL(12,2) NULL DEFAULT NULL',
        'ms_shipped_sum'           => 'DECIMAL(12,2) NULL DEFAULT NULL',
        'ms_invoiced_sum'          => 'DECIMAL(12,2) NULL DEFAULT NULL',
        'ms_reserved_sum'          => 'DECIMAL(12,2) NULL DEFAULT NULL',
        'ms_vat_sum'               => 'DECIMAL(12,2) NULL DEFAULT NULL',
        'ms_rate_value'            => 'DECIMAL(12,4) NULL DEFAULT NULL',
        'ms_rate_currency'         => 'VARCHAR(10) NULL DEFAULT NULL',
        'ms_sales_channel'         => 'VARCHAR(100) NULL DEFAULT NULL',
        'ms_store_name'            => 'VARCHAR(100) NULL DEFAULT NULL',
        'ms_shipment_address_json' => 'JSON NULL DEFAULT NULL',
        'ms_attributes_json'       => 'JSON NULL DEFAULT NULL',
        'ms_updated'               => 'DATETIME NULL DEFAULT NULL',
        'ms_lead_source'           => 'VARCHAR(100) NULL DEFAULT NULL',
        'ms_delivery_type'         => 'VARCHAR(100) NULL DEFAULT NULL',
        'ms_deal_link'             => 'VARCHAR(500) NULL DEFAULT NULL',
        'ms_amo_created_at'        => 'DATETIME NULL DEFAULT NULL',
        'ms_order_size'            => 'VARCHAR(50) NULL DEFAULT NULL',
        'ms_via_widget'            => 'TINYINT(1) NULL DEFAULT NULL',
        'ms_passport_transferred'  => 'TINYINT(1) NULL DEFAULT NULL',
        'ms_cancel_reason'         => 'TEXT NULL DEFAULT NULL',
        'ms_waybill_link'          => 'VARCHAR(500) NULL DEFAULT NULL',
        'ms_cancel_date'           => 'DATETIME NULL DEFAULT NULL',
        'ms_pickup_date'           => 'DATETIME NULL DEFAULT NULL',
        'ms_number'                => 'VARCHAR(100) NULL DEFAULT NULL',
        'ms_via_widget'            => 'TINYINT(1) NULL DEFAULT NULL',
    ];
    foreach ($ordCols as $col => $def) ensureVerifyColumn($pdo, 'order', $col, $def);
    // product columns
    $prdCols = [
        'ms_code'                 => 'VARCHAR(100) NULL DEFAULT NULL',
        'ms_external_code'        => 'VARCHAR(100) NULL DEFAULT NULL',
        'barcode'                 => 'VARCHAR(255) NULL DEFAULT NULL',
        'barcodes_json'           => 'JSON NULL DEFAULT NULL',
        'vat'                     => 'SMALLINT NULL DEFAULT NULL',
        'ms_volume'               => 'DECIMAL(10,4) NULL DEFAULT NULL',
        'uom_name'                => 'VARCHAR(50) NULL DEFAULT NULL',
        'ms_images_json'          => 'JSON NULL DEFAULT NULL',
        'ms_attributes_json'      => 'JSON NULL DEFAULT NULL',
        'ms_characteristics_json' => 'JSON NULL DEFAULT NULL',
        'ms_min_price'            => 'DECIMAL(10,2) NULL DEFAULT NULL',
        'ms_supplier_name'        => 'VARCHAR(255) NULL DEFAULT NULL',
        'ms_archived'             => 'TINYINT(1) NULL DEFAULT 0',
        'ms_no_export'            => 'TINYINT(1) NULL DEFAULT 0',
        'is_sale'                 => 'TINYINT(1) NULL DEFAULT 0',
        'ms_size_grid'            => 'VARCHAR(100) NULL DEFAULT NULL',
        'ms_purpose'              => 'VARCHAR(100) NULL DEFAULT NULL',
        'ms_sole_height'          => 'VARCHAR(100) NULL DEFAULT NULL',
        'ms_sole_color'           => 'VARCHAR(255) NULL DEFAULT NULL',
        'ms_inner_material'       => 'VARCHAR(255) NULL DEFAULT NULL',
        'ms_site_link'            => 'VARCHAR(500) NULL DEFAULT NULL',
        'ms_price_full'           => 'DECIMAL(10,2) NULL DEFAULT NULL',
        'ms_price_rub'            => 'DECIMAL(10,2) NULL DEFAULT NULL',
        'ms_price_sale'           => 'DECIMAL(10,2) NULL DEFAULT NULL',
        'ms_price_competitor'     => 'DECIMAL(10,2) NULL DEFAULT NULL',
        'ms_path_name'            => 'VARCHAR(255) NULL DEFAULT NULL',
    ];
    foreach ($prdCols as $col => $def) ensureVerifyColumn($pdo, 'product', $col, $def);
}

// ─── Import single order ──────────────────────────────────────────────────────

function importSingleOrder(PDO $pdo, array $msOrder): int
{
    $msId = $msOrder['id'];

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

    $agent       = $msOrder['agent'] ?? [];
    $clientName  = trim($agent['name'] ?? 'Покупатель');
    $clientPhone = vmsNormalizePhone($agent['phone'] ?? '');
    $clientEmail = trim($agent['email'] ?? '');

    $agentMsId  = $agent['id'] ?? vmsIdFromHref($agent['meta']['href'] ?? null);
    $customerId = null;
    if ($agentMsId) {
        $st = $pdo->prepare("SELECT id FROM customer WHERE moysklad_id = ? LIMIT 1");
        $st->execute([$agentMsId]);
        $row = $st->fetch();
        $customerId = $row ? (int)$row['id'] : null;
    }

    $now       = time();
    $createdTs = ($t = strtotime(preg_replace('/\.\d+/', '', $msOrder['moment'] ?? $msOrder['created'] ?? ''))) ? $t : $now;
    $updatedTs = ($t = strtotime(preg_replace('/\.\d+/', '', $msOrder['updated'] ?? ''))) ? $t : $now;
    $updatedDt = date('Y-m-d H:i:s', $updatedTs);

    $stateNameMap = [
        'новый'=>'created','оплачен'=>'paid','подтвержден'=>'confirmed',
        'заказано у поставщика'=>'ordered','в международной доставке'=>'international_delivery',
        'на складе'=>'at_warehouse','в доставке'=>'local_delivery',
        'выдан'=>'delivered','отменен'=>'canceled','возврат'=>'return',
    ];
    $msStateName = $msOrder['state']['name'] ?? '';
    $ourStatus   = $stateNameMap[mb_strtolower($msStateName)] ?? 'created';

    $totalAmount  = isset($msOrder['sum'])          ? round($msOrder['sum']          / 100, 2) : 0.0;
    $deliveryCost = isset($msOrder['shippingCost']) ? round($msOrder['shippingCost'] / 100, 2) : 0.0;
    $payedSum     = isset($msOrder['payedSum'])     ? round($msOrder['payedSum']     / 100, 2) : null;
    $shippedSum   = isset($msOrder['shippedSum'])   ? round($msOrder['shippedSum']   / 100, 2) : null;
    $invoicedSum  = isset($msOrder['invoicedSum'])  ? round($msOrder['invoicedSum']  / 100, 2) : null;
    $reservedSum  = isset($msOrder['reservedSum'])  ? round($msOrder['reservedSum']  / 100, 2) : null;
    $vatSum       = isset($msOrder['vatSum'])       ? round($msOrder['vatSum']       / 100, 2) : null;

    $rate    = $msOrder['rate'] ?? null;
    $rateVal = isset($rate['value']) ? (float)$rate['value'] : null;
    $rateCur = $rate['currency']['name'] ?? null;

    $shipAddr     = $msOrder['shipmentAddress'] ?? null;
    $shipAddrFull = $msOrder['shipmentAddressFull'] ?? null;
    $shipAddrJson = $shipAddrFull ? json_encode($shipAddrFull, JSON_UNESCAPED_UNICODE) : null;

    $salesChannel = $msOrder['salesChannel']['name'] ?? null;
    $storeName    = $msOrder['store']['name'] ?? null;
    $orderNumber  = $msOrder['name'] ?? ('MS-' . substr($msId, 0, 8));
    $comment      = $msOrder['description'] ?? null;

    $rawAttrs  = $msOrder['attributes'] ?? [];
    $attrsJson = !empty($rawAttrs) ? json_encode($rawAttrs, JSON_UNESCAPED_UNICODE) : null;
    $a         = vmsExtractAttrs($rawAttrs);

    // moysklad_extra: everything not explicitly mapped
    $mappedKeys = [
        'id','meta','accountId','owner','group','shared',
        'name','externalCode','moment','created','updated','applicable',
        'agent','state','positions','salesChannel','store','rate','organization',
        'sum','shippingCost','payedSum','shippedSum','invoicedSum','reservedSum','vatSum',
        'shipmentAddress','shipmentAddressFull','description','attributes',
        'vatEnabled','vatIncluded','printed','published','payments',
    ];
    $extra = array_diff_key($msOrder, array_flip($mappedKeys));
    unset($extra['meta'], $extra['accountId'], $extra['owner'], $extra['group'], $extra['shared']);
    $extraJson = !empty($extra) ? json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

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
        'delivery_address'         => $shipAddr,
        'ms_shipment_address_json' => $shipAddrJson,
        'ms_attributes_json'       => $attrsJson,
        'ms_updated'               => $updatedDt,
        'comment'                  => $comment,
        'source'                   => 'moysklad',
        'updated_at'               => $updatedTs,
        'moysklad_extra'           => $extraJson,
        'ms_lead_source'           => $a['источник заявки'] ?? null,
        'ms_delivery_type'         => $a['тип доставки'] ?? null,
        'delivery_method'          => $a['тип доставки'] ?? null,
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

    if ($dbRow) {
        $ordId = (int)$dbRow['id'];
        $set   = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
        $pdo->prepare("UPDATE `order` SET {$set} WHERE id = :__id")->execute(array_merge($data, ['__id' => $ordId]));
        echo "  [order] обновлён (DB id={$ordId})\n";
    } else {
        $data['token']      = bin2hex(random_bytes(16));
        $data['created_at'] = $createdTs;
        $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
        $pdo->prepare("INSERT INTO `order` ({$cols}) VALUES ({$vals})")->execute($data);
        $ordId = (int)$pdo->lastInsertId();
        echo "  [order] создан (DB id={$ordId})\n";
    }
    return $ordId;
}

// ─── Import single product ────────────────────────────────────────────────────

function importSingleProduct(PDO $pdo, array $p): int
{
    $msId = $p['id'];

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

    $name     = trim($p['name'] ?? 'Товар');
    $article  = $p['article'] ?? null;
    $msCode   = $p['code'] ?? null;
    $desc     = $p['description'] ?? null;
    $weight   = isset($p['weight']) ? (int)round($p['weight'] * 1000) : null;
    $volume   = isset($p['volume']) ? (float)$p['volume'] : null;
    $vat      = isset($p['vat']) ? (int)$p['vat'] : null;
    $archived = !empty($p['archived']) ? 1 : 0;

    $prices    = vmsExtractPrices($p['salePrices'] ?? []);
    $salePrice = $prices['цена продажи'] ?? $prices[array_key_first($prices)] ?? 0.0;
    $buyPrice  = isset($p['buyPrice']['value']) ? (float)($p['buyPrice']['value'] / 100) : null;
    $minPrice  = isset($p['minPrice']['value']) ? (float)($p['minPrice']['value'] / 100) : null;

    $uomName      = $p['uom']['name'] ?? null;
    $supplierName = $p['supplier']['name'] ?? null;
    $countryName  = $p['country']['name'] ?? null;
    $pathName     = $p['pathName'] ?? null;

    $folderName = $p['productFolder']['name'] ?? null;

    // Category / brand defaults
    $defaultBrandId = 1;
    $row = $pdo->query("SELECT id FROM brand LIMIT 1")->fetch();
    if ($row) $defaultBrandId = (int)$row['id'];

    $categoryId = 1;
    $row = $pdo->query("SELECT id FROM category LIMIT 1")->fetch();
    if ($row) $categoryId = (int)$row['id'];

    if ($folderName) {
        $st = $pdo->prepare("SELECT id FROM category WHERE name = ? LIMIT 1");
        $st->execute([$folderName]);
        $row = $st->fetch();
        if ($row) $categoryId = (int)$row['id'];
    }

    $barcodes     = $p['barcodes'] ?? [];
    $firstBarcode = null;
    foreach ($barcodes as $bc) {
        $vals = array_values($bc);
        if (!empty($vals[0])) { $firstBarcode = (string)$vals[0]; break; }
    }
    $barcodesJson = !empty($barcodes) ? json_encode($barcodes, JSON_UNESCAPED_UNICODE) : null;

    $rawAttrs  = $p['attributes'] ?? [];
    $attrsJson = !empty($rawAttrs) ? json_encode($rawAttrs, JSON_UNESCAPED_UNICODE) : null;
    $a         = vmsExtractAttrs($rawAttrs);

    $chars     = $p['characteristics'] ?? [];
    $charsJson = !empty($chars) ? json_encode($chars, JSON_UNESCAPED_UNICODE) : null;

    $brandId   = $defaultBrandId;
    $brandName = $a['бренд'] ?? null;
    if ($brandName) {
        $st = $pdo->prepare("SELECT id FROM brand WHERE name = ? LIMIT 1");
        $st->execute([$brandName]);
        $row     = $st->fetch();
        $brandId = $row ? (int)$row['id'] : $defaultBrandId;
    }

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

    $imgMeta    = $p['images'] ?? [];
    $imgRows    = $imgMeta['rows'] ?? (isset($imgMeta[0]) ? $imgMeta : []);
    $images     = [];
    foreach ($imgRows as $img) {
        $href = $img['meta']['href'] ?? $img['href'] ?? null;
        if ($href) $images[] = $href;
    }
    if (empty($images) && !empty($imgMeta['meta']['href'])) {
        $images = ['__collection__' => $imgMeta['meta']['href']];
    }
    $imagesJson = !empty($images) ? json_encode($images, JSON_UNESCAPED_UNICODE) : null;

    $mappedKeys = [
        'id','meta','accountId','owner','group','shared','updated','created',
        'name','code','externalCode','article','description','pathName',
        'productFolder','uom','supplier','country',
        'weight','volume','vat','vatEnabled','useParentVat',
        'effectiveVat','effectiveVatEnabled',
        'barcodes','images','salePrices','buyPrice','minPrice',
        'attributes','characteristics','archived',
        'paymentItemType','discountProhibited','variantsCount',
        'isSerialTrackable','trackingType',
    ];
    $extra = array_diff_key($p, array_flip($mappedKeys));
    unset($extra['meta'], $extra['accountId'], $extra['owner'], $extra['group'], $extra['shared']);
    $extraJson = !empty($extra) ? json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

    $now = time();
    $data = [
        'moysklad_id'             => $msId,
        'ms_code'                 => $msCode,
        'ms_external_code'        => $p['externalCode'] ?? null,
        'ms_path_name'            => $pathName,
        'name'                    => $name,
        'sku'                     => $article,
        'vendor_code'             => $article,
        'description'             => $desc,
        'price'                   => max(0.0, $salePrice),
        'old_price'               => ($prices['полная цена'] ?? 0.0) > 0 ? $prices['полная цена'] : null,
        'purchase_price'          => $buyPrice,
        'ms_min_price'            => $minPrice,
        'ms_price_full'           => $prices['полная цена'] ?? null,
        'ms_price_rub'            => $prices['цена продажи в rub'] ?? null,
        'ms_price_sale'           => $prices['цена распродажи (для своих)'] ?? null,
        'ms_price_competitor'     => $prices['минимальная цена конкурентов'] ?? null,
        'weight'                  => $weight,
        'ms_volume'               => $volume,
        'vat'                     => $vat,
        'uom_name'                => $uomName,
        'ms_supplier_name'        => $supplierName,
        'country_of_origin'       => $countryName,
        'barcode'                 => $firstBarcode,
        'barcodes_json'           => $barcodesJson,
        'ms_images_json'          => $imagesJson,
        'ms_attributes_json'      => $attrsJson,
        'ms_characteristics_json' => $charsJson,
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
        'ms_archived'             => $archived,
        'is_active'               => $archived ? 0 : 1,
        'category_id'             => $categoryId,
        'moysklad_extra'          => $extraJson,
        'updated_at'              => $now,
    ];

    if ($dbRow) {
        $prodId = (int)$dbRow['id'];
        $set    = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
        $pdo->prepare("UPDATE product SET {$set} WHERE id = :__id")->execute(array_merge($data, ['__id' => $prodId]));
        echo "  [product] обновлён (DB id={$prodId})\n";
    } else {
        // Simple slug from name
        $base = mb_strtolower($name);
        $base = preg_replace('/[^a-z0-9а-яё]+/ui', '-', $base);
        $base = trim($base, '-') ?: ('item-' . substr(md5($name), 0, 8));
        $slug = $base;
        $i    = 1;
        $st   = $pdo->prepare("SELECT 1 FROM product WHERE slug = ?");
        while (true) {
            $st->execute([$slug]);
            if (!$st->fetch()) break;
            $slug = $base . '-' . $i++;
        }
        $data['slug']       = $slug;
        $data['created_at'] = $now;
        $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $vals = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
        $pdo->prepare("INSERT INTO product ({$cols}) VALUES ({$vals})")->execute($data);
        $prodId = (int)$pdo->lastInsertId();
        echo "  [product] создан (DB id={$prodId})\n";
    }
    return $prodId;
}

// ─── Comparison reporter ──────────────────────────────────────────────────────

/**
 * Flatten an MS API response into dot-notation key→value pairs for display.
 */
function flattenMs(array $data, string $prefix = ''): array
{
    $result = [];
    foreach ($data as $k => $v) {
        $key = $prefix ? "{$prefix}.{$k}" : $k;
        if (is_array($v)) {
            if (array_keys($v) === range(0, count($v) - 1)) {
                // Indexed array — store as JSON preview
                $result[$key] = '[' . count($v) . ' items]';
            } else {
                // Object — recurse but cap depth
                if (strlen($prefix) < 40) {
                    foreach (flattenMs($v, $key) as $kk => $vv) {
                        $result[$kk] = $vv;
                    }
                } else {
                    $result[$key] = '{object}';
                }
            }
        } else {
            $result[$key] = $v;
        }
    }
    return $result;
}

/**
 * Print a side-by-side comparison table.
 * $msFlat : dot-key → ms_value
 * $dbRow  : column → db_value
 * $fieldMap: dot-key → db_column(s) (string or array of strings)
 */
function printComparison(string $title, array $msFlat, array $dbRow, array $fieldMap): void
{
    $bar = str_repeat('═', 100);
    echo "\n{$bar}\n";
    echo "  {$title}\n";
    echo "{$bar}\n";
    printf("  %-42s  %-28s  %-22s  %s\n", 'МС поле', 'МС значение', 'DB колонка', 'DB значение / статус');
    echo str_repeat('─', 100) . "\n";

    $captured = 0;
    $missed   = 0;
    $total    = 0;

    // Fields from the map
    foreach ($fieldMap as $msKey => $dbCols) {
        $dbCols   = (array)$dbCols;
        $msVal    = $msFlat[$msKey] ?? null;
        $msValStr = is_null($msVal) ? "\033[90m(нет в API)\033[0m" : (is_bool($msVal) ? ($msVal ? 'true' : 'false') : mb_substr((string)$msVal, 0, 50));
        $total++;

        foreach ($dbCols as $dbCol) {
            $dbVal    = $dbRow[$dbCol] ?? null;
            $dbValStr = is_null($dbVal) ? "\033[90mNULL\033[0m" : mb_substr((string)$dbVal, 0, 40);
            $status   = is_null($msVal) ? "\033[90m—\033[0m" : (!is_null($dbVal) ? "\033[32m✓ CAPTURED\033[0m" : "\033[33m△ NULL\033[0m");
            if (!is_null($msVal) && !is_null($dbVal)) $captured++;
            elseif (!is_null($msVal)) $missed++;

            printf("  %-42s  %-28s  %-22s  %s\n",
                mb_substr($msKey, 0, 42),
                mb_substr(strip_tags($msValStr), 0, 28),
                mb_substr($dbCol, 0, 22),
                $status . '  ' . mb_substr(strip_tags($dbValStr), 0, 35)
            );
        }
    }

    // MS fields NOT in the map (unmapped / in moysklad_extra)
    $mappedMsKeys = array_keys($fieldMap);
    $unmapped = [];
    foreach ($msFlat as $k => $v) {
        if (!in_array($k, $mappedMsKeys, true) && !str_starts_with($k, 'meta.') && !str_starts_with($k, 'owner.') && !str_starts_with($k, 'group.')) {
            $unmapped[$k] = $v;
        }
    }
    if ($unmapped) {
        echo str_repeat('─', 100) . "\n";
        echo "  Не в маппинге (сохранены в moysklad_extra JSON):\n";
        foreach ($unmapped as $k => $v) {
            $vStr = is_null($v) ? 'null' : mb_substr((string)$v, 0, 50);
            printf("  %-42s  %-28s  %-22s  \033[36m→ extra\033[0m\n",
                mb_substr($k, 0, 42), mb_substr($vStr, 0, 28), 'moysklad_extra');
        }
    }

    echo str_repeat('─', 100) . "\n";
    $pct = $total > 0 ? round($captured / $total * 100) : 0;
    echo "  Итого: \033[32m{$captured} captured\033[0m, \033[33m{$missed} NULL\033[0m, " . count($unmapped) . " → extra  ({$pct}% from mapped fields)\n";
    echo "{$bar}\n";
}

// ─── Field maps ──────────────────────────────────────────────────────────────

$orderFieldMap = [
    'id'                             => 'moysklad_id',
    'name'                           => ['order_number', 'ms_number'],
    'externalCode'                   => 'ms_external_code',
    'moment'                         => 'created_at',
    'updated'                        => 'ms_updated',
    'applicable'                     => 'ms_applicable',
    'agent.id'                       => 'customer_id',
    'agent.name'                     => 'client_name',
    'agent.phone'                    => 'client_phone',
    'agent.email'                    => 'client_email',
    'state.name'                     => 'status',
    'sum'                            => 'total_amount',
    'shippingCost'                   => 'delivery_cost',
    'payedSum'                       => 'ms_payed_sum',
    'shippedSum'                     => 'ms_shipped_sum',
    'invoicedSum'                    => 'ms_invoiced_sum',
    'reservedSum'                    => 'ms_reserved_sum',
    'vatSum'                         => 'ms_vat_sum',
    'rate.value'                     => 'ms_rate_value',
    'rate.currency.name'             => 'ms_rate_currency',
    'salesChannel.name'              => 'ms_sales_channel',
    'store.name'                     => 'ms_store_name',
    'shipmentAddress'                => 'delivery_address',
    'shipmentAddressFull'            => 'ms_shipment_address_json',
    'description'                    => 'comment',
    'attributes'                     => 'ms_attributes_json',
    // custom attrs (extracted from attributes[])
    'attr:источник заявки'           => 'ms_lead_source',
    'attr:тип доставки'              => 'ms_delivery_type',
    'attr:ссылка на сделку'          => 'ms_deal_link',
    'attr:дата создания [amo]'       => 'ms_amo_created_at',
    'attr:ссылка на товар'           => 'product_link',
    'attr:размер'                    => 'ms_order_size',
    'attr:заказ создан через виджет' => 'ms_via_widget',
    'attr:перенесено в таблицу паспортов' => 'ms_passport_transferred',
    'attr:причина отказа'            => 'ms_cancel_reason',
    'attr:ссылка на накладную сдэк / европочта' => 'ms_waybill_link',
    'attr:дата отмены/возврата'      => 'ms_cancel_date',
    'attr:дата приезда в магазин за заказом' => 'ms_pickup_date',
];

$productFieldMap = [
    'id'                    => 'moysklad_id',
    'name'                  => 'name',
    'code'                  => 'ms_code',
    'externalCode'          => 'ms_external_code',
    'article'               => ['sku', 'vendor_code'],
    'description'           => 'description',
    'pathName'              => 'ms_path_name',
    'productFolder.name'    => 'category_id',
    'uom.name'              => 'uom_name',
    'supplier.name'         => 'ms_supplier_name',
    'country.name'          => 'country_of_origin',
    'weight'                => 'weight',
    'volume'                => 'ms_volume',
    'vat'                   => 'vat',
    'archived'              => 'ms_archived',
    'salePrices'            => 'price',
    'buyPrice.value'        => 'purchase_price',
    'minPrice.value'        => 'ms_min_price',
    'barcodes'              => ['barcode', 'barcodes_json'],
    'images'                => 'ms_images_json',
    'attributes'            => 'ms_attributes_json',
    'characteristics'       => 'ms_characteristics_json',
    'attr:бренд'            => 'brand_name',
    'attr:модель'           => 'model_name',
    'attr:пол'              => 'gender',
    'attr:сезон'            => 'season',
    'attr:высота'           => 'height',
    'attr:материал верха'   => 'upper_material',
    'attr:цвет обуви'       => 'color_description',
    'attr:размерная сетка'  => 'ms_size_grid',
    'attr:назначение'       => 'ms_purpose',
    'attr:высота подошвы'   => 'ms_sole_height',
    'attr:цвет подошвы'     => 'ms_sole_color',
    'attr:материал внутренний' => 'ms_inner_material',
    'attr:ссылка sneakerhead.by' => 'ms_site_link',
    'attr:не выгружать на сайт' => 'ms_no_export',
    'attr:распродажа'       => 'is_sale',
    'attr:новинка'          => 'is_featured',
    'salePrices:полная цена'          => 'ms_price_full',
    'salePrices:цена продажи в rub'   => 'ms_price_rub',
    'salePrices:цена распродажи (для своих)' => 'ms_price_sale',
    'salePrices:минимальная цена конкурентов' => 'ms_price_competitor',
];

// ─── MAIN ─────────────────────────────────────────────────────────────────────

$bar = str_repeat('═', 60);
echo "\n{$bar}\n";
echo "  МойСклад → БД: верификация импорта\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo "{$bar}\n\n";

// Test connection
try {
    $org = vmsGet('/entity/organization', ['limit' => 1]);
    echo "Подключение: OK  Организация: " . ($org['rows'][0]['name'] ?? '?') . "\n";
} catch (Throwable $e) {
    echo "ОШИБКА подключения: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nОбновление схемы БД...\n";
setupVerifySchema($pdo);
echo "Схема готова ✓\n";

// ════════════════════════════════════════════════════
// PART 1 — ORDER
// ════════════════════════════════════════════════════

echo "\n" . str_repeat('─', 60) . "\n";
echo "Шаг 1: Загружаем заказ из МойСклад...\n";
echo "  ID: " . VERIFY_ORDER_ID . "\n";

try {
    $msOrder = vmsGet('/entity/customerorder/' . VERIFY_ORDER_ID, [
        'expand' => 'agent,state,positions.assortment,salesChannel,store',
    ]);
    echo "  Заказ: " . ($msOrder['name'] ?? '?') . "  Статус: " . ($msOrder['state']['name'] ?? '?') . "\n";
} catch (Throwable $e) {
    echo "  ОШИБКА: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nШаг 2: Импортируем в БД...\n";
$ordDbId = importSingleOrder($pdo, $msOrder);

echo "\nШаг 3: Читаем все колонки из БД...\n";
$ordDbRow = $pdo->prepare("SELECT * FROM `order` WHERE id = ?")->execute([$ordDbId])
    ? $pdo->query("SELECT * FROM `order` WHERE id = {$ordDbId}")->fetch()
    : [];
echo "  Колонок в БД: " . count($ordDbRow) . "\n";

// Build MS flat with attr:* virtual keys
$msOrderFlat = flattenMs($msOrder);
$ordAttrs    = vmsExtractAttrs($msOrder['attributes'] ?? []);
foreach ($ordAttrs as $k => $v) {
    $msOrderFlat['attr:' . $k] = $v;
}

printComparison('ЗАКАЗ #' . VERIFY_ORDER_ID, $msOrderFlat, $ordDbRow, $orderFieldMap);

// ════════════════════════════════════════════════════
// PART 2 — PRODUCT
// ════════════════════════════════════════════════════

echo "\n" . str_repeat('─', 60) . "\n";
echo "Шаг 1: Загружаем товар из МойСклад...\n";
echo "  ID: " . VERIFY_PRODUCT_ID . "\n";

try {
    $msProduct = vmsGet('/entity/product/' . VERIFY_PRODUCT_ID, [
        'expand' => 'productFolder,uom,supplier,country',
    ]);
    echo "  Товар: " . ($msProduct['name'] ?? '?') . "\n";
} catch (Throwable $e) {
    echo "  ОШИБКА: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nШаг 2: Импортируем в БД...\n";
$prodDbId = importSingleProduct($pdo, $msProduct);

echo "\nШаг 3: Читаем все колонки из БД...\n";
$prodDbRow = $pdo->query("SELECT * FROM product WHERE id = {$prodDbId}")->fetch();
echo "  Колонок в БД: " . count($prodDbRow) . "\n";

// Build MS flat with attr:* and salePrices:* virtual keys
$msProdFlat = flattenMs($msProduct);
$prodAttrs  = vmsExtractAttrs($msProduct['attributes'] ?? []);
foreach ($prodAttrs as $k => $v) {
    $msProdFlat['attr:' . $k] = $v;
}
$prodPrices = vmsExtractPrices($msProduct['salePrices'] ?? []);
foreach ($prodPrices as $k => $v) {
    $msProdFlat['salePrices:' . $k] = $v;
}

printComparison('ТОВАР #' . VERIFY_PRODUCT_ID, $msProdFlat, $prodDbRow, $productFieldMap);

// ════════════════════════════════════════════════════
// PART 3 — DB column dump
// ════════════════════════════════════════════════════

echo "\n\033[1mВСЕ КОЛОНКИ ЗАКАЗА (из БД):\033[0m\n";
echo str_repeat('─', 70) . "\n";
foreach ($ordDbRow as $col => $val) {
    $display = is_null($val) ? "\033[90mNULL\033[0m" : mb_substr((string)$val, 0, 80);
    printf("  %-40s  %s\n", $col, $display);
}

echo "\n\033[1mВСЕ КОЛОНКИ ТОВАРА (из БД):\033[0m\n";
echo str_repeat('─', 70) . "\n";
foreach ($prodDbRow as $col => $val) {
    $display = is_null($val) ? "\033[90mNULL\033[0m" : mb_substr((string)$val, 0, 80);
    printf("  %-40s  %s\n", $col, $display);
}

echo "\n\033[32mВерификация завершена.\033[0m\n";
