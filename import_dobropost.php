<?php
/**
 * DobroPost mass import — creates orders for all 377 DP shipments not yet in DB.
 * Run: php import_dobropost.php
 */

$dsn = 'mysql:host=127.0.0.1;dbname=sneakerhead;charset=utf8mb4';
$pdo = new PDO($dsn, 'sneaker', 'secret', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// ── DobroPost auth ────────────────────────────────────────────────────────────
$dpBase = 'https://api.dobropost.com';
$dpLogin = 'sneakerkultura@gmail.com';
$dpPass  = 'Moloko1moloko';

function dpRequest(string $method, string $path, array $payload = [], string $token = ''): array
{
    global $dpBase;
    $ch = curl_init($dpBase . $path);
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    if ($payload) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $resp    = curl_exec($ch);
    $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err     = curl_error($ch);
    curl_close($ch);

    if ($err) throw new RuntimeException("cURL error: $err");
    $data = json_decode($resp, true);
    if ($code >= 400) {
        throw new RuntimeException("DP API HTTP $code: " . ($data['message'] ?? $resp));
    }
    return $data ?? [];
}

// Authenticate
echo "Authenticating with DobroPost...\n";
$auth = dpRequest('POST', '/api/shipment/sign-in', ['email' => $dpLogin, 'password' => $dpPass]);
$token = $auth['token'] ?? ($auth['accessToken'] ?? null);
if (!$token) {
    die("Auth failed — no token in response: " . json_encode($auth) . "\n");
}
echo "Auth OK. Token: " . substr($token, 0, 20) . "...\n\n";

// ── Iterate pages ─────────────────────────────────────────────────────────────
$page       = 1;
$totalPages = 1;
$imported   = 0;
$skipped    = 0;
$errors     = 0;

// Prepare insert statement
$insert = $pdo->prepare("
    INSERT INTO `order`
        (order_number, token, client_name, client_phone, client_email,
         total_amount, status, source,
         china_track_number, dp_shipment_id, dp_track_number, dp_status,
         dp_status_date, dp_sent_at, dp_response,
         created_at, updated_at)
    VALUES
        (:order_number, :token, :client_name, :client_phone, :client_email,
         :total_amount, :status, :source,
         :china_track_number, :dp_shipment_id, :dp_track_number, :dp_status,
         :dp_status_date, :dp_sent_at, :dp_response,
         :created_at, :updated_at)
");

do {
    echo "Fetching page $page / $totalPages ...\n";
    $result     = dpRequest('GET', '/api/shipment?page=' . $page, [], $token);
    $items      = $result['content'] ?? [];
    $totalPages = (int)($result['totalPages'] ?? 1);

    foreach ($items as $s) {
        $dpId = $s['id'] ?? null;

        // Skip already imported
        if ($dpId) {
            $check = $pdo->prepare("SELECT id FROM `order` WHERE dp_shipment_id = ?");
            $check->execute([$dpId]);
            if ($check->fetchColumn()) {
                $skipped++;
                continue;
            }
        }

        // Build name from consignee fields
        $lastName  = trim($s['consigneeFamilyName'] ?? '');
        $firstName = trim($s['consigneeName'] ?? '');
        $clientName = trim("$lastName $firstName") ?: 'DP Import';

        $dpTrack   = $s['dptrackNumber'] ?? null;
        $chinaTrk  = $s['incomingDeclaration'] ?? null;
        $dpStatus  = isset($s['status']['id']) ? (string)$s['status']['id'] : null;
        $statusDate = !empty($s['statusDate'])
            ? date('Y-m-d H:i:s', strtotime($s['statusDate'])) : null;
        $sentAt = !empty($s['created']) ? strtotime($s['created']) : time();
        $total  = (float)($s['totalAmount'] ?? 0);

        // Unique order number
        $orderNumber = 'DP-' . str_pad($dpId ?? (time() . rand(100,999)), 6, '0', STR_PAD_LEFT);
        $tokenVal    = bin2hex(random_bytes(16));
        $now         = time();

        try {
            $insert->execute([
                ':order_number'       => $orderNumber,
                ':token'              => $tokenVal,
                ':client_name'        => $clientName,
                ':client_phone'       => $s['consigneePhoneNumber'] ?? null,
                ':client_email'       => $s['consigneeEmail'] ?? null,
                ':total_amount'       => $total,
                ':status'             => 'imported',
                ':source'             => 'dobropost',
                ':china_track_number' => $chinaTrk,
                ':dp_shipment_id'     => $dpId,
                ':dp_track_number'    => $dpTrack,
                ':dp_status'          => $dpStatus,
                ':dp_status_date'     => $statusDate,
                ':dp_sent_at'         => $sentAt,
                ':dp_response'        => json_encode($s, JSON_UNESCAPED_UNICODE),
                ':created_at'         => $now,
                ':updated_at'         => $now,
            ]);
            $imported++;
            echo "  + Imported: $orderNumber | $clientName | dp_id=$dpId\n";
        } catch (PDOException $e) {
            $errors++;
            echo "  ! ERROR for dp_id=$dpId: " . $e->getMessage() . "\n";
        }
    }

    $page++;
} while ($page <= $totalPages);

echo "\n=== Done ===\n";
echo "Imported : $imported\n";
echo "Skipped  : $skipped (already in DB)\n";
echo "Errors   : $errors\n";
