<?php
/**
 * ModStore — Global Helper Functions
 */

// ── Authentication ────────────────────────────────────────────
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function requireAuth(string $redirect = 'login.php'): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Please sign in to continue.');
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: $redirect");
        exit;
    }
}

function getCurrentUser(PDO $pdo): ?array
{
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare('SELECT id, username, email, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// ── Flash Messages ────────────────────────────────────────────
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = compact('type', 'message');
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ── CSRF Protection ───────────────────────────────────────────
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

// ── Cart (stored in session) ──────────────────────────────────
function getCart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cartAdd(int $productId): void
{
    $_SESSION['cart'][$productId] = 1;
}

function cartRemove(int $productId): void
{
    unset($_SESSION['cart'][$productId]);
}

function cartClear(): void
{
    $_SESSION['cart'] = [];
}

function cartCount(): int
{
    return count(getCart());
}

function cartProducts(PDO $pdo): array
{
    $ids = array_keys(getCart());
    if (empty($ids)) return [];
    $ph   = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(
        "SELECT p.*, c.name AS cat_name, c.icon AS cat_icon
         FROM products p
         LEFT JOIN categories c ON c.id = p.category_id
         WHERE p.id IN ($ph) AND p.is_active = 1"
    );
    $stmt->execute($ids);
    return $stmt->fetchAll();
}

function cartTotal(array $products): float
{
    return (float) array_sum(array_column($products, 'price'));
}

// ── Ownership ─────────────────────────────────────────────────
function userOwns(PDO $pdo, int $userId, int $productId): bool
{
    $stmt = $pdo->prepare('SELECT id FROM user_assets WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$userId, $productId]);
    return (bool) $stmt->fetch();
}

function grantAsset(PDO $pdo, int $userId, int $productId): void
{
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO user_assets (user_id, product_id) VALUES (?, ?)'
    );
    $stmt->execute([$userId, $productId]);
}

// ── Formatting ────────────────────────────────────────────────
function fPrice(float $price): string
{
    return $price == 0 ? 'Free' : '€' . number_format($price, 2);
}

function fDate(string $date): string
{
    return date('d M Y', strtotime($date));
}

function stars(float $rating): string
{
    $html  = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $html .= '<i class="bi bi-star-fill"></i>';
        } elseif ($rating >= $i - 0.5) {
            $html .= '<i class="bi bi-star-half"></i>';
        } else {
            $html .= '<i class="bi bi-star"></i>';
        }
    }
    $html .= '</span>';
    return $html;
}

// Category → gradient map (used as card thumbnails)
function cardGradient(int $categoryId): string
{
    $map = [
        1 => 'linear-gradient(135deg,#1e2a45 0%,#0f1115 100%)',   // weapons – blue
        2 => 'linear-gradient(135deg,#1a3028 0%,#0f1115 100%)',   // maps – green
        3 => 'linear-gradient(135deg,#2e1a3a 0%,#0f1115 100%)',   // characters – purple
        4 => 'linear-gradient(135deg,#1a2a3a 0%,#0f1115 100%)',   // ui – cyan-ish
        5 => 'linear-gradient(135deg,#2a1a1a 0%,#0f1115 100%)',   // sound – red
        6 => 'linear-gradient(135deg,#2a2218 0%,#0f1115 100%)',   // vehicles – amber
    ];
    return $map[$categoryId] ?? 'linear-gradient(135deg,#1a1d23 0%,#0f1115 100%)';
}

// Safe HTML output
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Pagination helper
function buildPagination(int $total, int $perPage, int $current): array
{
    $pages = (int) ceil($total / $perPage);
    return compact('total', 'perPage', 'current', 'pages');
}
