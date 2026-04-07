<?php
/**
 * ModStore — Order History
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth();

$userId = (int) $_SESSION['user_id'];

// Fetch orders with item count
$orderStmt = $pdo->prepare(
    'SELECT o.*,
       (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
     FROM orders o
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC'
);
$orderStmt->execute([$userId]);
$orders = $orderStmt->fetchAll();

// If a specific order is requested, fetch its items
$detail   = null;
$detItems = [];
$orderId  = (int)($_GET['order'] ?? 0);
if ($orderId > 0) {
    $detStmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
    $detStmt->execute([$orderId, $userId]);
    $detail = $detStmt->fetch();

    if ($detail) {
        $itemStmt = $pdo->prepare(
            'SELECT oi.*, p.name, p.slug, c.name AS cat_name, c.icon AS cat_icon, c.id AS cat_id
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE oi.order_id = ?'
        );
        $itemStmt->execute([$orderId]);
        $detItems = $itemStmt->fetchAll();
    }
}

$page_title   = 'Orders';
$current_page = 'orders';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-4">
  <div class="page-header" style="padding-top:.5rem;">
    <h1><i class="bi bi-receipt" style="color:var(--accent);font-size:1.5rem;"></i> Order History</h1>
    <p>All your past orders and purchases.</p>
  </div>

  <?php if ($detail): ?>
    <!-- ── Order Detail ────────────────────────── -->
    <div style="margin-bottom:1.5rem;">
      <a href="orders.php" class="btn-ms btn-ms-ghost btn-sm">
        <i class="bi bi-arrow-left"></i> All Orders
      </a>
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start;">
      <div>
        <div class="ms-panel" style="margin-bottom:1.25rem;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.5rem;">
            <div>
              <h2 style="font-size:1.1rem;">Order #<?= $detail['id'] ?></h2>
              <p style="color:var(--text-muted);font-size:.83rem;margin-top:.15rem;">
                Placed on <?= fDate($detail['created_at']) ?>
              </p>
            </div>
            <span class="badge-ms status-<?= e($detail['status']) ?>" style="font-size:.78rem;padding:.35rem .85rem;">
              <?= ucfirst($detail['status']) ?>
            </span>
          </div>

          <div style="display:flex;flex-direction:column;gap:.6rem;">
            <?php foreach ($detItems as $item): ?>
              <div style="display:flex;align-items:center;gap:.85rem;padding:.75rem;background:var(--bg-hover);border-radius:var(--radius-sm);">
                <div style="
                  width:44px;height:36px;border-radius:6px;flex-shrink:0;
                  background:<?= cardGradient((int)$item['cat_id']) ?>;
                  display:flex;align-items:center;justify-content:center;
                  font-size:1rem;color:rgba(255,255,255,.2);
                "><i class="bi <?= e($item['cat_icon'] ?? 'bi-box') ?>"></i></div>
                <div style="flex:1;min-width:0;">
                  <a href="product.php?id=<?= $item['product_id'] ?>"
                     style="font-weight:600;font-size:.9rem;color:var(--text-primary);">
                    <?= e($item['name']) ?>
                  </a>
                  <div style="font-size:.77rem;color:var(--text-muted);"><?= e($item['cat_name'] ?? '') ?></div>
                </div>
                <span style="font-family:'Syne',sans-serif;font-weight:700;flex-shrink:0;<?= $item['price']==0?'color:var(--success)':''?>">
                  <?= fPrice((float)$item['price']) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div>
        <div class="cart-summary">
          <h3 style="font-size:.95rem;margin-bottom:1rem;">Summary</h3>
          <div class="cart-summary__row">
            <span>Items</span>
            <span><?= count($detItems) ?></span>
          </div>
          <div class="cart-summary__row">
            <span>Status</span>
            <span class="badge-ms status-<?= e($detail['status']) ?>" style="padding:.2rem .6rem;font-size:.72rem;"><?= ucfirst($detail['status']) ?></span>
          </div>
          <div class="cart-summary__total">
            <span>Total</span>
            <span><?= fPrice((float)$detail['total_price']) ?></span>
          </div>
        </div>

        <div class="ms-panel" style="margin-top:1rem;font-size:.83rem;">
          <h4 style="font-size:.85rem;font-weight:700;margin-bottom:.75rem;color:var(--text-secondary);">Billing Info</h4>
          <div style="display:flex;flex-direction:column;gap:.3rem;color:var(--text-secondary);">
            <span><strong style="color:var(--text-primary);"><?= e($detail['full_name']) ?></strong></span>
            <span><?= e($detail['email']) ?></span>
            <?php if ($detail['address'] !== 'N/A'): ?>
              <span><?= e($detail['address']) ?></span>
              <span><?= e($detail['postal_code']) ?> <?= e($detail['city']) ?></span>
              <span><?= e($detail['country']) ?></span>
            <?php endif; ?>
          </div>
        </div>

        <a href="my-assets.php" class="btn-ms btn-ms-primary btn-full" style="margin-top:1rem;">
          <i class="bi bi-collection-play"></i> View My Assets
        </a>
      </div>
    </div>

  <?php elseif (empty($orders)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="bi bi-receipt"></i></div>
      <h3>No orders yet</h3>
      <p>You haven't placed any orders. Start browsing the store!</p>
      <a href="index.php" class="btn-ms btn-ms-primary">
        <i class="bi bi-grid-3x3-gap"></i> Browse Store
      </a>
    </div>

  <?php else: ?>
    <!-- ── Orders Table ─────────────────────────── -->
    <div class="ms-table-wrap">
      <table class="ms-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Date</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><span style="font-weight:700;color:var(--accent);">#<?= $o['id'] ?></span></td>
              <td style="color:var(--text-secondary);"><?= fDate($o['created_at']) ?></td>
              <td style="color:var(--text-secondary);"><?= $o['item_count'] ?> item<?= $o['item_count'] !== 1 ? 's' : '' ?></td>
              <td><span style="font-family:'Syne',sans-serif;font-weight:700;"><?= fPrice((float)$o['total_price']) ?></span></td>
              <td>
                <span class="badge-ms status-<?= e($o['status']) ?>" style="font-size:.72rem;">
                  <?= ucfirst($o['status']) ?>
                </span>
              </td>
              <td>
                <a href="orders.php?order=<?= $o['id'] ?>" class="btn-ms btn-ms-ghost btn-sm">
                  View <i class="bi bi-arrow-right"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
