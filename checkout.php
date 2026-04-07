<?php
/**
 * ModStore — Checkout
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth('login.php');

$products = cartProducts($pdo);
if (empty($products)) {
    setFlash('warning', 'Your cart is empty.');
    header('Location: cart.php');
    exit;
}
$total    = cartTotal($products);
$isFree   = $total == 0;

// Pre-fill from session user
$user = getCurrentUser($pdo);
$prefill = [
    'full_name'   => $user['username'] ?? '',
    'email'       => $user['email']    ?? '',
    'address'     => '',
    'city'        => '',
    'postal_code' => '',
    'country'     => 'Netherlands',
];
$errors = [];

$page_title   = 'Checkout';
$current_page = '';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-4">
  <div class="page-header" style="padding-top:.5rem;">
    <h1><i class="bi bi-credit-card" style="color:var(--accent);font-size:1.5rem;"></i>
      <?= $isFree ? 'Claim Free Assets' : 'Checkout' ?>
    </h1>
    <p><?= $isFree ? 'No payment needed — claim your free assets.' : 'Review your order and complete payment.' ?></p>
  </div>

  <form action="process/place-order.php" method="POST" novalidate>
    <?php csrfField() ?>
    <div class="checkout-layout">

      <!-- Left: form -->
      <div>
        <div class="ms-panel" style="margin-bottom:1.5rem;">
          <h3 style="font-size:1rem;margin-bottom:1.25rem;">
            <i class="bi bi-person" style="color:var(--accent);margin-right:.4rem;"></i>
            Contact Information
          </h3>
          <div class="ms-form">
            <div class="form-row">
              <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name"
                  class="ms-input" value="<?= e($prefill['full_name']) ?>"
                  placeholder="Jane Doe" required maxlength="100">
              </div>
              <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email"
                  class="ms-input" value="<?= e($prefill['email']) ?>"
                  placeholder="you@example.com" required maxlength="100">
              </div>
            </div>
          </div>
        </div>

        <?php if (!$isFree): ?>
        <div class="ms-panel" style="margin-bottom:1.5rem;">
          <h3 style="font-size:1rem;margin-bottom:1.25rem;">
            <i class="bi bi-geo-alt" style="color:var(--accent);margin-right:.4rem;"></i>
            Billing Address
          </h3>
          <div class="ms-form">
            <div class="form-group">
              <label for="address">Street Address *</label>
              <input type="text" id="address" name="address"
                class="ms-input" value="<?= e($prefill['address']) ?>"
                placeholder="Stationsplein 1" required maxlength="255">
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="city">City *</label>
                <input type="text" id="city" name="city"
                  class="ms-input" value="<?= e($prefill['city']) ?>"
                  placeholder="Amsterdam" required maxlength="100">
              </div>
              <div class="form-group">
                <label for="postal_code">Postal Code *</label>
                <input type="text" id="postal_code" name="postal_code"
                  class="ms-input" value="<?= e($prefill['postal_code']) ?>"
                  placeholder="1012 AB" required maxlength="20">
              </div>
            </div>
            <div class="form-group">
              <label for="country">Country *</label>
              <select id="country" name="country" class="ms-select" required>
                <?php foreach ([
                  'Netherlands','Belgium','Germany','France','United Kingdom',
                  'Spain','Italy','Sweden','Denmark','Poland','United States','Other'
                ] as $c): ?>
                  <option value="<?= e($c) ?>" <?= $prefill['country']===$c?'selected':''?>><?= e($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="ms-panel" style="margin-bottom:1.5rem;">
          <h3 style="font-size:1rem;margin-bottom:1.25rem;">
            <i class="bi bi-credit-card" style="color:var(--accent);margin-right:.4rem;"></i>
            Payment
          </h3>
          <div style="
            background:var(--bg-hover);border-radius:var(--radius-sm);
            padding:1rem 1.25rem;display:flex;align-items:center;gap:.75rem;
            border:1px dashed var(--border-hover);
          ">
            <i class="bi bi-info-circle" style="color:var(--accent);font-size:1.2rem;flex-shrink:0;"></i>
            <div>
              <div style="font-weight:600;font-size:.88rem;margin-bottom:.2rem;">Simulated Payment</div>
              <div style="font-size:.8rem;color:var(--text-muted);">
                This is a demo store. No real payment is processed. Click "Complete Order" to simulate a successful payment.
              </div>
            </div>
          </div>
        </div>
        <?php else: ?>
          <!-- Hidden fields for free checkout (address not needed but form requires them) -->
          <input type="hidden" name="address"     value="N/A">
          <input type="hidden" name="city"        value="N/A">
          <input type="hidden" name="postal_code" value="N/A">
          <input type="hidden" name="country"     value="N/A">
        <?php endif; ?>
      </div>

      <!-- Right: order summary -->
      <div>
        <div class="cart-summary">
          <h3 style="font-size:1rem;margin-bottom:1rem;">
            <i class="bi bi-receipt" style="color:var(--accent);margin-right:.4rem;"></i>
            Order Summary
          </h3>

          <?php foreach ($products as $p): ?>
            <div class="cart-summary__row">
              <div style="display:flex;align-items:center;gap:.6rem;min-width:0;">
                <div style="
                  width:28px;height:28px;border-radius:6px;flex-shrink:0;
                  background:<?= cardGradient((int)$p['category_id']) ?>;
                  display:flex;align-items:center;justify-content:center;
                  font-size:.75rem;color:rgba(255,255,255,.3);
                ">
                  <i class="bi <?= e($p['cat_icon'] ?? 'bi-box') ?>"></i>
                </div>
                <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:150px;">
                  <?= e($p['name']) ?>
                </span>
              </div>
              <span style="flex-shrink:0;<?= $p['price']==0?'color:var(--success)':''?>">
                <?= fPrice((float)$p['price']) ?>
              </span>
            </div>
          <?php endforeach; ?>

          <div class="cart-summary__total">
            <span>Total</span>
            <span style="<?= $isFree ? 'color:var(--success)' : '' ?>">
              <?= fPrice($total) ?>
            </span>
          </div>

          <button type="submit" class="btn-ms btn-ms-primary btn-full" style="margin-top:1.25rem;font-size:1rem;padding:.75rem;">
            <?= $isFree
              ? '<i class="bi bi-gift"></i> Claim Free Assets'
              : '<i class="bi bi-lock-fill"></i> Complete Order'
            ?>
          </button>

          <a href="cart.php" class="btn-ms btn-ms-ghost btn-full" style="margin-top:.6rem;">
            <i class="bi bi-arrow-left"></i> Back to Cart
          </a>

          <p style="font-size:.73rem;color:var(--text-muted);text-align:center;margin-top:.75rem;line-height:1.5;">
            <i class="bi bi-shield-lock"></i> Secure checkout.
            Your data is handled in accordance with GDPR.
          </p>
        </div>
      </div>

    </div>
  </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
