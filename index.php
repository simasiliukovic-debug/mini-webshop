<?php
/**
 * ModStore — Store / Homepage
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title   = 'Store';
$current_page = 'store';

// ── Filters from query string ─────────────────────────────────
$search   = trim($_GET['q']    ?? '');
$catSlug  = trim($_GET['cat']  ?? '');
$priceFilter = $_GET['price'] ?? '';  // 'free' | 'paid'
$sort        = $_GET['sort']  ?? 'newest'; // newest | popular | price_asc | price_desc

// ── Build query ───────────────────────────────────────────────
$where  = ['p.is_active = 1'];
$params = [];

if ($search !== '') {
    $where[]  = '(p.name LIKE ? OR p.description LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($catSlug !== '') {
    $where[]  = 'c.slug = ?';
    $params[] = $catSlug;
}
if ($priceFilter === 'free') {
    $where[] = 'p.price = 0';
} elseif ($priceFilter === 'paid') {
    $where[] = 'p.price > 0';
}

$sortMap = [
    'newest'     => 'p.release_date DESC',
    'popular'    => 'p.download_count DESC',
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'rating'     => 'p.rating DESC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['newest'];

$whereSQL = implode(' AND ', $where);
$sql = "SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, c.icon AS cat_icon
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE $whereSQL
        ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// ── All categories for filter chips ───────────────────────────
$cats = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-4">

  <!-- Hero banner -->
  <div class="ms-hero fade-in">
    <div class="ms-hero__content">
      <div class="ms-hero__eyebrow"><i class="bi bi-lightning-charge-fill"></i> New Arrivals</div>
      <h1>Premium Game<br>Mods &amp; Assets.</h1>
      <p>High-quality weapons, maps, characters, UI kits, and more — free and paid. Built for Unreal Engine &amp; Unity.</p>
      <div class="ms-hero__actions">
        <a href="#products" class="btn-ms btn-ms-primary btn-lg">
          <i class="bi bi-grid-3x3-gap"></i> Browse Store
        </a>
        <?php if (!isLoggedIn()): ?>
          <a href="/modstore/register.php" class="btn-ms btn-ms-ghost btn-lg">Start Selling</a>
        <?php endif; ?>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <span class="hero-stat__num">100+</span>
          <span class="hero-stat__label">Mods Available</span>
        </div>
        <div class="hero-stat">
          <span class="hero-stat__num">Free</span>
          <span class="hero-stat__label">No Sign-up Fee</span>
        </div>
        <div class="hero-stat">
          <span class="hero-stat__num">UE</span>
          <span class="hero-stat__label">Compatible</span>
        </div>
      </div>
    </div>
    <div class="ms-hero__orb"></div>
    <div class="ms-hero__art"><i class="bi bi-controller"></i></div>
  </div>

  <!-- Filters -->
  <div id="products" class="store-filters">

    <!-- Search -->
    <div class="search-wrap">
      <i class="bi bi-search search-icon"></i>
      <form method="GET" style="display:contents;">
        <input
          type="text" name="q" value="<?= e($search) ?>"
          placeholder="Search mods &amp; assets…" class="ms-input"
          onchange="this.form.submit()"
        >
        <?php if ($catSlug):   ?><input type="hidden" name="cat"   value="<?= e($catSlug) ?>"><?php endif; ?>
        <?php if ($priceFilter):?><input type="hidden" name="price" value="<?= e($priceFilter) ?>"><?php endif; ?>
        <?php if ($sort !== 'newest'):?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>
      </form>
    </div>

    <!-- Category chips -->
    <a href="?<?= http_build_query(array_filter(['q'=>$search,'price'=>$priceFilter,'sort'=>$sort])) ?>"
       class="filter-chip <?= $catSlug === '' ? 'active' : '' ?>">All</a>
    <?php foreach ($cats as $cat): ?>
      <?php $q = http_build_query(array_filter(['q'=>$search,'cat'=>$cat['slug'],'price'=>$priceFilter,'sort'=>$sort])); ?>
      <a href="?<?= $q ?>" class="filter-chip <?= $catSlug === $cat['slug'] ? 'active' : '' ?>">
        <i class="bi <?= e($cat['icon']) ?>"></i>
        <?= e($cat['name']) ?>
      </a>
    <?php endforeach; ?>

    <!-- Price filter -->
    <a href="?<?= http_build_query(array_filter(['q'=>$search,'cat'=>$catSlug,'price'=>'free','sort'=>$sort])) ?>"
       class="filter-chip <?= $priceFilter === 'free' ? 'active' : '' ?>">
      <i class="bi bi-gift"></i> Free
    </a>
    <a href="?<?= http_build_query(array_filter(['q'=>$search,'cat'=>$catSlug,'price'=>'paid','sort'=>$sort])) ?>"
       class="filter-chip <?= $priceFilter === 'paid' ? 'active' : '' ?>">
      <i class="bi bi-tag"></i> Paid
    </a>

    <!-- Sort (rightmost) -->
    <div class="ms-nav__actions ms-auto" style="margin-left:auto;">
      <form method="GET" style="display:flex;align-items:center;gap:.4rem;">
        <?php if ($search):     ?><input type="hidden" name="q"     value="<?= e($search) ?>"><?php endif; ?>
        <?php if ($catSlug):    ?><input type="hidden" name="cat"   value="<?= e($catSlug) ?>"><?php endif; ?>
        <?php if ($priceFilter):?><input type="hidden" name="price" value="<?= e($priceFilter) ?>"><?php endif; ?>
        <label for="sortSelect" style="font-size:.82rem;color:var(--text-muted);white-space:nowrap;">Sort by</label>
        <select name="sort" id="sortSelect" class="ms-select" style="width:auto;padding:.35rem .7rem;font-size:.82rem;" onchange="this.form.submit()">
          <option value="newest"     <?= $sort==='newest'     ?'selected':''?>>Newest</option>
          <option value="popular"    <?= $sort==='popular'    ?'selected':''?>>Most Popular</option>
          <option value="rating"     <?= $sort==='rating'     ?'selected':''?>>Top Rated</option>
          <option value="price_asc"  <?= $sort==='price_asc'  ?'selected':''?>>Price ↑</option>
          <option value="price_desc" <?= $sort==='price_desc' ?'selected':''?>>Price ↓</option>
        </select>
      </form>
    </div>
  </div>

  <!-- Result count -->
  <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:1.25rem;">
    <?= count($products) ?> result<?= count($products) !== 1 ? 's' : '' ?>
    <?= $search ? ' for "' . e($search) . '"' : '' ?>
    <?= $catSlug ? ' in ' . e($catSlug) : '' ?>
  </p>

  <!-- Product grid -->
  <?php if (empty($products)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="bi bi-search"></i></div>
      <h3>No results found</h3>
      <p>Try adjusting your filters or search query.</p>
      <a href="index.php" class="btn-ms btn-ms-ghost">Clear Filters</a>
    </div>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $p): ?>
        <a href="product.php?id=<?= $p['id'] ?>" class="ms-card" style="text-decoration:none;color:inherit;">

          <!-- Thumbnail -->
          <div class="product-thumb" style="background:<?= cardGradient((int)$p['category_id']) ?>">
            <i class="bi <?= e($p['cat_icon'] ?? 'bi-box') ?> product-thumb__icon"></i>
            <span class="product-thumb__badge">
              <?php if ($p['price'] == 0): ?>
                <span class="badge-ms badge-free"><i class="bi bi-gift"></i> Free</span>
              <?php else: ?>
                <span class="badge-ms badge-paid">€<?= number_format($p['price'], 2) ?></span>
              <?php endif; ?>
            </span>
          </div>

          <!-- Body -->
          <div class="product-card__body">
            <span class="product-card__cat"><?= e($p['cat_name'] ?? 'General') ?></span>
            <div class="product-card__name"><?= e($p['name']) ?></div>
            <div class="product-card__meta">
              <?= stars((float)$p['rating']) ?>
              <span class="rating-value"><?= number_format($p['rating'], 1) ?></span>
              <span style="color:var(--text-muted);font-size:.78rem;">
                <i class="bi bi-download"></i> <?= number_format($p['download_count']) ?>
              </span>
            </div>
            <div class="product-card__footer">
              <span class="product-price <?= $p['price'] == 0 ? 'free' : '' ?>">
                <?= fPrice((float)$p['price']) ?>
              </span>
              <span class="btn-ms btn-ms-primary btn-sm" style="pointer-events:none;">
                <?= $p['price'] == 0 ? 'Get Free' : 'View' ?>
              </span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
