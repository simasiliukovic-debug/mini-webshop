<?php
/**
 * ModStore — Process: Save Product (Create / Edit)
 * Any logged-in user can publish assets (like Unity Asset Store publishers).
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../publish.php');
    exit;
}

requireAuth('../login.php');
verifyCsrf();

$productId   = (int) ($_POST['product_id']   ?? 0);  // 0 = create new
$name        = trim(strip_tags($_POST['name']        ?? ''));
$description = trim(strip_tags($_POST['description'] ?? ''));
$categoryId  = (int) ($_POST['category_id']  ?? 0);
$isFree      = isset($_POST['is_free']);
$priceRaw    = str_replace(',', '.', $_POST['price'] ?? '0');
$price       = $isFree ? 0.00 : round((float) $priceRaw, 2);
$version     = trim(strip_tags($_POST['version']     ?? '1.0.0'));
$fileSize    = trim(strip_tags($_POST['file_size']   ?? '—'));
$releaseDate = $_POST['release_date'] ?? date('Y-m-d');

// ── Validate ──────────────────────────────────────────────────
$errors = [];

if ($name === '' || strlen($name) > 100) {
    $errors[] = 'Product name is required (max 100 characters).';
}
if (strlen($description) < 20) {
    $errors[] = 'Description must be at least 20 characters.';
}
if ($categoryId < 1) {
    $errors[] = 'Please select a category.';
}
if (!$isFree && ($price < 0.01 || $price > 999.99)) {
    $errors[] = 'Price must be between €0.01 and €999.99 for paid products.';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $releaseDate)) {
    $errors[] = 'Invalid release date.';
}

// Check category exists
if (empty($errors)) {
    $catCheck = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
    $catCheck->execute([$categoryId]);
    if (!$catCheck->fetch()) $errors[] = 'Selected category does not exist.';
}

if (!empty($errors)) {
    $_SESSION['publish_errors'] = $errors;
    $_SESSION['publish_old']    = $_POST;
    header('Location: ../publish.php' . ($productId ? "?edit=$productId" : ''));
    exit;
}

// ── Generate unique slug ──────────────────────────────────────
function makeSlug(string $str): string {
    $s = strtolower(trim($str));
    $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
    $s = preg_replace('/[\s-]+/', '-', $s);
    return trim($s, '-');
}

$baseSlug = makeSlug($name);
$slug     = $baseSlug;
$i        = 1;
while (true) {
    $chk = $pdo->prepare('SELECT id FROM products WHERE slug = ? AND id != ?');
    $chk->execute([$slug, $productId]);
    if (!$chk->fetch()) break;
    $slug = $baseSlug . '-' . $i++;
}

try {
    if ($productId > 0) {
        // Update existing
        $stmt = $pdo->prepare(
            'UPDATE products
             SET name=?, slug=?, description=?, price=?, category_id=?,
                 version=?, file_size=?, release_date=?
             WHERE id=?'
        );
        $stmt->execute([
            $name, $slug, $description, $price, $categoryId,
            $version, $fileSize, $releaseDate, $productId
        ]);
        setFlash('success', '"' . $name . '" has been updated!');
        header("Location: ../product.php?id=$productId");
    } else {
        // Insert new
        $stmt = $pdo->prepare(
            'INSERT INTO products
               (name, slug, description, price, category_id, version, file_size, release_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name, $slug, $description, $price, $categoryId,
            $version, $fileSize, $releaseDate
        ]);
        $newId = (int) $pdo->lastInsertId();
        setFlash('success', '"' . $name . '" published successfully!');
        header("Location: ../product.php?id=$newId");
    }
} catch (Throwable $e) {
    error_log('ModStore save-product error: ' . $e->getMessage());
    setFlash('danger', 'Could not save product. Please try again.');
    header('Location: ../publish.php' . ($productId ? "?edit=$productId" : ''));
}
exit;
