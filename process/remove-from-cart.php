<?php
/**
 * ModStore — Process: Remove from Cart
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../cart.php');
    exit;
}

verifyCsrf();

$productId = (int) ($_POST['product_id'] ?? 0);
$redirect  = $_POST['redirect'] ?? '../cart.php';

if ($productId > 0) {
    cartRemove($productId);
    setFlash('info', 'Item removed from cart.');
}

header("Location: ../$redirect");
exit;
