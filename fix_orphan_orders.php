<?php
/**
 * Backfill: link all orders with customer_id IS NULL to existing or new Customer records.
 * Run inside Docker: docker exec sneakerhead-app php /var/www/fix_orphan_orders.php
 */

$pdo = new PDO('mysql:host=db;port=3306;dbname=sneakerhead;charset=utf8mb4', 'sneaker', 'secret', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// 1. Count orphans
$countBefore = $pdo->query("SELECT COUNT(*) as cnt FROM `order` WHERE customer_id IS NULL")->fetch()['cnt'];
echo "Orders with customer_id IS NULL before: $countBefore\n";

if ($countBefore == 0) {
    echo "Nothing to fix.\n";
    exit(0);
}

// 2. Fetch all orphan orders
$orphans = $pdo->query("SELECT id, client_name, client_email, client_phone FROM `order` WHERE customer_id IS NULL")->fetchAll();

// Prepared statements
$findByEmail = $pdo->prepare("SELECT id FROM customer WHERE email = :email AND email != '' LIMIT 1");
$findByPhone = $pdo->prepare("SELECT id FROM customer WHERE phone = :phone AND phone != '' LIMIT 1");
$insertCustomer = $pdo->prepare(
    "INSERT INTO customer (email, phone, first_name, last_name, is_active, created_at, updated_at)
     VALUES (:email, :phone, :first_name, :last_name, 1, :now, :now)"
);
$linkOrder = $pdo->prepare("UPDATE `order` SET customer_id = :cid WHERE id = :oid");

$linked = 0;
$created = 0;
$errors = 0;

foreach ($orphans as $order) {
    $email = trim($order['client_email'] ?? '');
    $phone = trim($order['client_phone'] ?? '');
    $name  = trim($order['client_name'] ?? '');
    $oid   = $order['id'];

    $customerId = null;

    // Try to find existing customer by email first, then by phone
    if ($email !== '') {
        $findByEmail->execute([':email' => $email]);
        $row = $findByEmail->fetch();
        if ($row) $customerId = $row['id'];
    }

    if (!$customerId && $phone !== '') {
        $findByPhone->execute([':phone' => $phone]);
        $row = $findByPhone->fetch();
        if ($row) $customerId = $row['id'];
    }

    if ($customerId) {
        // Link to existing customer
        try {
            $linkOrder->execute([':cid' => $customerId, ':oid' => $oid]);
            $linked++;
        } catch (Exception $e) {
            echo "ERROR linking order #$oid to customer #$customerId: {$e->getMessage()}\n";
            $errors++;
        }
    } else {
        // Create new customer
        $parts = $name ? explode(' ', $name, 3) : ['Unknown'];
        $lastName  = $parts[0] ?? '';
        $firstName = $parts[1] ?? '';
        $now = time();

        try {
            $insertCustomer->execute([
                ':email'      => $email,
                ':phone'      => $phone,
                ':first_name' => $firstName,
                ':last_name'  => $lastName,
                ':now'        => $now,
            ]);
            $customerId = $pdo->lastInsertId();
            $linkOrder->execute([':cid' => $customerId, ':oid' => $oid]);
            $created++;
            $linked++;
        } catch (Exception $e) {
            echo "ERROR creating customer for order #$oid: {$e->getMessage()}\n";
            $errors++;
        }
    }
}

// 3. Final count
$countAfter = $pdo->query("SELECT COUNT(*) as cnt FROM `order` WHERE customer_id IS NULL")->fetch()['cnt'];

echo "\n=== RESULTS ===\n";
echo "Orders processed: " . count($orphans) . "\n";
echo "Linked to existing: " . ($linked - $created) . "\n";
echo "New customers created: $created\n";
echo "Total linked: $linked\n";
echo "Errors: $errors\n";
echo "Orders with customer_id IS NULL after: $countAfter\n";
