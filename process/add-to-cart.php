<?php
/**
 * ModStore — Process: Add to Cart
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

verifyCsrf();

$productId = (int) ($_POST['product_id'] ?? 0);
$redirect  = $_POST['redirect'] ?? '../index.php';

// Whitelist redirect to prevent open redirect
$allowedPrefixes = ['index.php', 'product.php', 'cart.php', 'checkout.php'];
$safe = false;
foreach ($allowedPrefixes as $pfx) {
    if (str_starts_with(basename(parse_url($redirect, PHP_URL_PATH)), basename($pfx))) {
        $safe = true; break;
    }
}
if (!$safe) $redirect = '../index.php';

if ($productId < 1) {
    setFlash('danger', 'Invalid product.');
    header("Location: ../$redirect");
    exit;
}

// Fetch product
$stmt = $pdo->prepare('SELECT id, name, price, is_active FROM products WHERE id = ? AND is_active = 1');
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('danger', 'Product not found.');
    header("Location: ../index.php");
    exit;
}

// Check if user is logged in for ownership check
if (isLoggedIn() && userOwns($pdo, (int)$_SESSION['user_id'], $productId)) {
    setFlash('info', '"' . $product['name'] . '" is already in your library.');
    header("Location: ../my-assets.php");
    exit;
}

// Add to cart
cartAdd($productId);
setFlash('success', '"' . $product['name'] . '" added to cart!');

header("Location: ../$redirect");
exit;
