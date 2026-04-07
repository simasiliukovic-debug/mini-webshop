<?php
/**
 * ModStore — Cart
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$page_title   = 'Cart';
$current_page = 'cart';

$products = cartProducts($pdo);
$total    = cartTotal($products);
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-4">
  <div class="page-header" style="padding-top:.5rem;">
    <h1><i class="bi bi-bag" style="color:var(--accent);font-size:1.5rem;"></i> Your Cart</h1>
    <p><?= count($products) ?> item<?= count($products) !== 1 ? 's' : '' ?></p>
  </div>

  <?php if (empty($products)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="bi bi-bag"></i></div>
      <h3>Your cart is empty</h3>
      <p>Browse the store and add some mods or assets.</p>
      <a href="index.php" class="btn-ms btn-ms-primary">
        <i class="bi bi-grid-3x3-gap"></i> Browse Store
      </a>
    </div>

  <?php else: ?>
    <div class="checkout-layout">

      <!-- Cart items -->
      <div>
        <div style="display:flex;flex-direction:column;gap:.75rem;">
          <?php foreach ($products as $p): ?>
            <div class="cart-item">
              <div class="cart-item__thumb"
                   style="background:<?= cardGradient((int)$p['category_id']) ?>;border-radius:var(--radius-sm);">
                <i class="bi <?= e($p['cat_icon'] ?? 'bi-box') ?>"
                   style="font-size:1.5rem;color:rgba(255,255,255,.2);"></i>
              </div>
              <div class="cart-item__info">
                <a href="product.php?id=<?= $p['id'] ?>" class="cart-item__name"
                   style="display:block;color:var(--text-primary);text-decoration:none;">
                  <?= e($p['name']) ?>
                </a>
                <span class="cart-item__cat"><?= e($p['cat_name'] ?? '') ?></span>
              </div>
              <span class="cart-item__price <?= $p['price'] == 0 ? 'free' : '' ?>">
                <?= fPrice((float)$p['price']) ?>
              </span>
              <!-- Remove -->
              <form action="process/remove-from-cart.php" method="POST">
                <?php csrfField() ?>
                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                <input type="hidden" name="redirect" value="cart.php">
                <button type="submit" class="btn-icon" title="Remove"
                  style="color:var(--danger);flex-shrink:0;">
                  <i class="bi bi-x-lg"></i>
                </button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:1rem;">
          <a href="index.php" style="font-size:.85rem;color:var(--text-muted);">
            <i class="bi bi-arrow-left"></i> Continue Shopping
          </a>
        </div>
      </div>

      <!-- Summary -->
      <div>
        <div class="cart-summary">
          <h3 style="font-size:1rem;margin-bottom:1rem;">Order Summary</h3>

          <?php foreach ($products as $p): ?>
            <div class="cart-summary__row">
              <span style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                <?= e($p['name']) ?>
              </span>
              <span style="<?= $p['price']==0 ? 'color:var(--success)' : '' ?>">
                <?= fPrice((float)$p['price']) ?>
              </span>
            </div>
          <?php endforeach; ?>

          <div class="cart-summary__total">
            <span>Total</span>
            <span style="<?= $total==0 ? 'color:var(--success)' : '' ?>">
              <?= fPrice($total) ?>
            </span>
          </div>

          <?php if (!isLoggedIn()): ?>
            <a href="login.php" class="btn-ms btn-ms-primary btn-full" style="margin-top:1.25rem;">
              <i class="bi bi-box-arrow-in-right"></i> Sign In to Checkout
            </a>
          <?php else: ?>
            <a href="checkout.php" class="btn-ms btn-ms-primary btn-full" style="margin-top:1.25rem;">
              <?= $total == 0
                  ? '<i class="bi bi-gift"></i> Claim Free Assets'
                  : '<i class="bi bi-credit-card"></i> Proceed to Checkout'
              ?>
            </a>
          <?php endif; ?>

          <p style="font-size:.75rem;color:var(--text-muted);text-align:center;margin-top:.75rem;">
            <i class="bi bi-shield-check"></i> Secure checkout — payment simulated
          </p>
        </div>
      </div>

    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
