<?php
/**
 * ModStore — Process: Place Order
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../checkout.php');
    exit;
}

requireAuth('../login.php');
verifyCsrf();

$userId   = (int) $_SESSION['user_id'];
$products = cartProducts($pdo);

if (empty($products)) {
    setFlash('warning', 'Your cart is empty.');
    header('Location: ../cart.php');
    exit;
}

// Sanitise & validate inputs
$fullName   = trim(strip_tags($_POST['full_name']   ?? ''));
$email      = strtolower(trim(strip_tags($_POST['email']      ?? '')));
$address    = trim(strip_tags($_POST['address']    ?? 'N/A'));
$city       = trim(strip_tags($_POST['city']       ?? 'N/A'));
$postalCode = trim(strip_tags($_POST['postal_code'] ?? 'N/A'));
$country    = trim(strip_tags($_POST['country']    ?? 'N/A'));

$errors = [];
if ($fullName === '' || strlen($fullName) > 100) $errors[] = 'Full name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'Valid email is required.';

if (!empty($errors)) {
    setFlash('danger', implode(' ', $errors));
    header('Location: ../checkout.php');
    exit;
}

$total = cartTotal($products);

try {
    $pdo->beginTransaction();

    // 1. Insert order
    $stmt = $pdo->prepare(
        'INSERT INTO orders (user_id, full_name, email, address, city, postal_code, country, total_price, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId, $fullName, $email, $address, $city, $postalCode, $country,
        $total, 'completed'
    ]);
    $orderId = (int) $pdo->lastInsertId();

    // 2. Insert order items + grant assets + increment download_count
    $insItem  = $pdo->prepare('INSERT INTO order_items (order_id, product_id, price) VALUES (?, ?, ?)');
    $incrDown = $pdo->prepare('UPDATE products SET download_count = download_count + 1 WHERE id = ?');

    foreach ($products as $p) {
        $insItem->execute([$orderId, $p['id'], $p['price']]);
        grantAsset($pdo, $userId, (int)$p['id']);
        $incrDown->execute([$p['id']]);   // ← only on purchase
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('ModStore order error: ' . $e->getMessage());
    setFlash('danger', 'Something went wrong placing your order. Please try again.');
    header('Location: ../checkout.php');
    exit;
}

// Clear cart
cartClear();

setFlash('success', 'Order #' . $orderId . ' placed! Assets have been added to your library.');
header('Location: ../orders.php?order=' . $orderId);
exit;
