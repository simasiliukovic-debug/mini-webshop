<?php
/**
 * ModStore — Process: Add Review
 * Only users who own the product can review it (once).
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

requireAuth('../login.php');
verifyCsrf();

$userId    = (int) $_SESSION['user_id'];
$productId = (int) ($_POST['product_id'] ?? 0);
$rating    = (int) ($_POST['rating']     ?? 5);
$comment   = trim(strip_tags($_POST['comment'] ?? ''));

// Clamp rating 1–5
$rating = max(1, min(5, $rating));

if ($productId < 1) {
    setFlash('danger', 'Invalid product.');
    header('Location: ../index.php');
    exit;
}

// Must own product
if (!userOwns($pdo, $userId, $productId)) {
    setFlash('warning', 'You must own this asset before reviewing it.');
    header("Location: ../product.php?id=$productId");
    exit;
}

// Already reviewed?
$check = $pdo->prepare('SELECT id FROM reviews WHERE user_id = ? AND product_id = ?');
$check->execute([$userId, $productId]);
if ($check->fetch()) {
    setFlash('info', 'You have already reviewed this asset.');
    header("Location: ../product.php?id=$productId");
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert review
    $ins = $pdo->prepare(
        'INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)'
    );
    $ins->execute([$userId, $productId, $rating, $comment ?: null]);

    // Recalculate product rating & review_count (rounded to 2 decimal places)
    $upd = $pdo->prepare(
        'UPDATE products
         SET rating       = (SELECT ROUND(AVG(r.rating), 2) FROM reviews r WHERE r.product_id = ?),
             review_count = (SELECT COUNT(*)              FROM reviews r WHERE r.product_id = ?)
         WHERE id = ?'
    );
    $upd->execute([$productId, $productId, $productId]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('ModStore review error: ' . $e->getMessage());
    setFlash('danger', 'Could not submit your review. Please try again.');
    header("Location: ../product.php?id=$productId");
    exit;
}

setFlash('success', 'Your review has been submitted!');
header("Location: ../product.php?id=$productId#reviews");
exit;
