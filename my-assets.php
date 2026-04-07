<?php
/**
 * ModStore — My Assets Library
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$userId = (int) $_SESSION['user_id'];

// Fetch user assets with product details
$stmt = $pdo->prepare(
    'SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, c.icon AS cat_icon,
            ua.acquired_at
     FROM user_assets ua
     JOIN products p ON p.id = ua.product_id
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE ua.user_id = ?
     ORDER BY ua.acquired_at DESC'
);
$stmt->execute([$userId]);
$assets = $stmt->fetchAll();

// Group by category for summary
$cats = [];
foreach ($assets as $a) {
    $cats[$a['cat_name'] ?? 'Other'] = ($cats[$a['cat_name'] ?? 'Other'] ?? 0) + 1;
}

$page_title   = 'My Assets';
$current_page = 'assets';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-4">
  <div class="page-header" style="padding-top:.5rem;">
    <h1>
      <i class="bi bi-collection-play" style="color:var(--accent);font-size:1.5rem;"></i>
      My Assets
    </h1>
    <p><?= count($assets) ?> asset<?= count($assets) !== 1 ? 's' : '' ?> in your library.</p>
  </div>

  <?php if (empty($assets)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="bi bi-collection-play"></i></div>
      <h3>Your library is empty</h3>
      <p>Purchase or claim free assets to build your collection.</p>
      <a href="index.php" class="btn-ms btn-ms-primary">
        <i class="bi bi-grid-3x3-gap"></i> Browse Store
      </a>
    </div>

  <?php else: ?>

    <!-- Stats bar -->
    <div style="display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:2rem;">
      <div class="ms-panel" style="padding:.85rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
        <i class="bi bi-collection" style="font-size:1.3rem;color:var(--accent);"></i>
        <div>
          <div style="font-family:'Syne',sans-serif;font-size:1.3rem;font-weight:800;line-height:1;">
            <?= count($assets) ?>
          </div>
          <div style="font-size:.75rem;color:var(--text-muted);">Total Assets</div>
        </div>
      </div>
      <?php foreach ($cats as $catName => $count): ?>
        <div class="ms-panel" style="padding:.85rem 1.25rem;display:flex;align-items:center;gap:.5rem;">
          <div>
            <div style="font-weight:700;font-size:.9rem;"><?= $count ?></div>
            <div style="font-size:.75rem;color:var(--text-muted);"><?= e($catName) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Asset grid -->
    <div class="asset-grid">
      <?php foreach ($assets as $a): ?>
        <div class="asset-card">
          <div class="asset-thumb"
               style="background:<?= cardGradient((int)$a['category_id']) ?>">
            <i class="bi <?= e($a['cat_icon'] ?? 'bi-box') ?>"
               style="font-size:2rem;color:rgba(255,255,255,.18);"></i>
          </div>
          <div class="asset-body">
            <div style="display:flex;align-items:start;justify-content:space-between;gap:.4rem;margin-bottom:.3rem;">
              <a href="product.php?id=<?= $a['id'] ?>" class="asset-name"
                 style="color:var(--text-primary);text-decoration:none;">
                <?= e($a['name']) ?>
              </a>
              <?php if ($a['price'] == 0): ?>
                <span class="badge-ms badge-free" style="flex-shrink:0;font-size:.65rem;">Free</span>
              <?php endif; ?>
            </div>
            <div class="asset-meta">
              <span><i class="bi <?= e($a['cat_icon'] ?? 'bi-box') ?>"></i> <?= e($a['cat_name'] ?? 'General') ?></span>
              · v<?= e($a['version']) ?>
            </div>
            <div class="asset-meta" style="margin-top:.2rem;">
              <i class="bi bi-calendar3"></i>
              Acquired <?= fDate($a['acquired_at']) ?>
            </div>
            <div style="margin-top:.85rem;display:flex;gap:.4rem;">
              <a href="product.php?id=<?= $a['id'] ?>" class="btn-ms btn-ms-ghost btn-sm" style="flex:1;justify-content:center;">
                <i class="bi bi-eye"></i> View
              </a>
              <span class="badge-ms badge-success" style="font-size:.7rem;padding:.3rem .7rem;align-self:center;">
                <i class="bi bi-check2"></i> Owned
              </span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
