<?php
/**
 * ModStore — Product Detail
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) { header('Location: index.php'); exit; }

// Fetch product with category
$stmt = $pdo->prepare(
    'SELECT p.*, c.name AS cat_name, c.slug AS cat_slug, c.icon AS cat_icon
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.id = ? AND p.is_active = 1'
);
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { header('Location: index.php'); exit; }

// Reviews
$revStmt = $pdo->prepare(
    'SELECT r.*, u.username FROM reviews r
     JOIN users u ON u.id = r.user_id
     WHERE r.product_id = ?
     ORDER BY r.created_at DESC'
);
$revStmt->execute([$id]);
$reviews = $revStmt->fetchAll();

// Does current user own it?
$owns = false;
$inCart = false;
$userReviewed = false;
if (isLoggedIn()) {
    $owns = userOwns($pdo, (int)$_SESSION['user_id'], $id);
    $inCart = isset(getCart()[$id]);
    $revCheck = $pdo->prepare('SELECT id FROM reviews WHERE user_id = ? AND product_id = ?');
    $revCheck->execute([$_SESSION['user_id'], $id]);
    $userReviewed = (bool)$revCheck->fetch();
}

$page_title   = e($product['name']);
$current_page = 'store';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-xl py-4">

  <!-- Breadcrumb -->
  <nav style="margin-bottom:1.5rem;font-size:.85rem;color:var(--text-muted);">
    <a href="index.php" style="color:var(--text-muted);">Store</a>
    <span style="margin:0 .4rem;">/</span>
    <a href="index.php?cat=<?= e($product['cat_slug'] ?? '') ?>" style="color:var(--text-muted);"><?= e($product['cat_name'] ?? 'General') ?></a>
    <span style="margin:0 .4rem;">/</span>
    <span style="color:var(--text-primary);"><?= e($product['name']) ?></span>
  </nav>

  <div class="product-detail-layout">

    <!-- Left: main content -->
    <div>
      <!-- Thumbnail -->
      <div class="product-detail-thumb"
           style="background:<?= cardGradient((int)$product['category_id']) ?>">
        <i class="bi <?= e($product['cat_icon'] ?? 'bi-box') ?>"
           style="font-size:5rem;color:rgba(255,255,255,.18);"></i>
      </div>

      <!-- Title & meta -->
      <div style="margin-bottom:1.5rem;">
        <span class="product-card__cat" style="font-size:.78rem;">
          <i class="bi <?= e($product['cat_icon'] ?? 'bi-box') ?>"></i>
          <?= e($product['cat_name'] ?? 'General') ?>
        </span>
        <h1 style="font-size:1.9rem;margin:.4rem 0 .6rem;"><?= e($product['name']) ?></h1>
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
          <div style="display:flex;align-items:center;gap:.4rem;">
            <?= stars((float)$product['rating']) ?>
            <span style="font-weight:700;"><?= number_format($product['rating'], 1) ?></span>
            <span style="color:var(--text-muted);font-size:.85rem;">(<?= $product['review_count'] ?> reviews)</span>
          </div>
          <span style="color:var(--text-muted);font-size:.85rem;">
            <i class="bi bi-download"></i> <?= number_format($product['download_count']) ?> downloads
          </span>
          <span style="color:var(--text-muted);font-size:.85rem;">
            <i class="bi bi-calendar3"></i> <?= fDate($product['release_date']) ?>
          </span>
        </div>
      </div>

      <!-- Description -->
      <div class="section">
        <h2 class="section-title" style="font-size:1.05rem;">Description</h2>
        <p style="color:var(--text-secondary);line-height:1.75;">
          <?= nl2br(e($product['description'])) ?>
        </p>
      </div>

      <!-- Metadata grid -->
      <div class="section">
        <h2 class="section-title" style="font-size:1.05rem;">Details</h2>
        <div class="detail-meta-grid">
          <div class="detail-meta-item">
            <small>Version</small>
            <span><?= e($product['version']) ?></span>
          </div>
          <div class="detail-meta-item">
            <small>File Size</small>
            <span><?= e($product['file_size']) ?></span>
          </div>
          <div class="detail-meta-item">
            <small>Category</small>
            <span><?= e($product['cat_name'] ?? '—') ?></span>
          </div>
          <div class="detail-meta-item">
            <small>Release Date</small>
            <span><?= fDate($product['release_date']) ?></span>
          </div>
          <div class="detail-meta-item">
            <small>Downloads</small>
            <span><?= number_format($product['download_count']) ?></span>
          </div>
          <div class="detail-meta-item">
            <small>Rating</small>
            <span><?= number_format($product['rating'], 2) ?> / 5.00</span>
          </div>
        </div>
      </div>

      <!-- Reviews section -->
      <div class="section">
        <h2 class="section-title" style="font-size:1.05rem;">
          Reviews <span style="color:var(--text-muted);font-size:.85rem;font-weight:400;">(<?= count($reviews) ?>)</span>
        </h2>

        <?php if (empty($reviews)): ?>
          <p style="color:var(--text-muted);font-size:.88rem;">No reviews yet. Be the first!</p>
        <?php else: ?>
          <?php foreach ($reviews as $rev): ?>
            <div class="review-item">
              <div class="review-header">
                <span class="review-author-avatar"><?= strtoupper(substr($rev['username'], 0, 1)) ?></span>
                <span class="review-author-name"><?= e($rev['username']) ?></span>
                <?= stars((float)$rev['rating']) ?>
                <span class="review-date"><?= fDate($rev['created_at']) ?></span>
              </div>
              <?php if ($rev['comment']): ?>
                <p class="review-body"><?= e($rev['comment']) ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <!-- Leave a review (logged in + owns product + not reviewed yet) -->
        <?php if (isLoggedIn() && $owns && !$userReviewed): ?>
          <div id="reviewForm" style="margin-top:1.5rem;padding-top:1.5rem;border-top:1px solid var(--border);">
            <h3 style="font-size:1rem;margin-bottom:1rem;">
              <i class="bi bi-pencil-square" style="color:var(--accent);"></i>
              Leave a Review
            </h3>
            <form action="process/add-review.php" method="POST" class="ms-form">
              <?php csrfField() ?>
              <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

              <!-- Star Rating — visual picker -->
              <div class="form-group">
                <label>Your Rating</label>
                <div style="display:flex;gap:.35rem;align-items:center;" id="starPicker">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="bi bi-star star-pick"
                       data-val="<?= $s ?>"
                       style="font-size:1.6rem;cursor:pointer;color:var(--bg-hover);transition:color .12s;"
                       onclick="setStar(<?= $s ?>)"
                       onmouseover="hoverStar(<?= $s ?>)"
                       onmouseout="resetStarHover()">
                    </i>
                  <?php endfor; ?>
                  <span id="ratingLabel" style="font-size:.85rem;color:var(--text-muted);margin-left:.4rem;">Select rating</span>
                </div>
                <!-- Hidden radio for form submission -->
                <div style="display:none;" id="hiddenStars">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <input type="radio" name="rating" id="ratingVal<?= $s ?>" value="<?= $s ?>" <?= $s===5?'checked':''?>>
                  <?php endfor; ?>
                </div>
              </div>

              <div class="form-group">
                <label for="reviewComment">Comment <span style="color:var(--text-muted);font-weight:400;">(optional)</span></label>
                <textarea name="comment" id="reviewComment" class="ms-textarea"
                  placeholder="What did you like or dislike? How did you use this asset?"
                  maxlength="1000" style="min-height:90px;"></textarea>
                <div style="font-size:.73rem;color:var(--text-muted);text-align:right;" id="reviewCharCount">0 / 1000</div>
              </div>

              <button type="submit" class="btn-ms btn-ms-primary btn-sm">
                <i class="bi bi-send"></i> Submit Review
              </button>
            </form>
          </div>

          <script>
          let selectedRating = 5;
          const ratingLabels = {1:'Poor',2:'Fair',3:'Good',4:'Very Good',5:'Excellent'};
          function setStar(val) {
            selectedRating = val;
            document.getElementById('ratingVal' + val).checked = true;
            document.getElementById('ratingLabel').textContent = ratingLabels[val];
            document.getElementById('ratingLabel').style.color = 'var(--warning)';
            paintStars(val, true);
          }
          function hoverStar(val) { paintStars(val, false); }
          function resetStarHover() { paintStars(selectedRating, true); }
          function paintStars(upTo, fill) {
            document.querySelectorAll('.star-pick').forEach(s => {
              const v = parseInt(s.dataset.val);
              s.className = v <= upTo
                ? 'bi bi-star-fill star-pick'
                : 'bi bi-star star-pick';
              s.style.color = v <= upTo ? 'var(--warning)' : 'var(--bg-hover)';
            });
          }
          // Init with 5 stars pre-selected
          paintStars(5, true);
          document.getElementById('ratingLabel').textContent = 'Excellent';
          document.getElementById('ratingLabel').style.color = 'var(--warning)';

          document.getElementById('reviewComment').addEventListener('input', function() {
            document.getElementById('reviewCharCount').textContent = this.value.length + ' / 1000';
          });
          </script>

        <?php elseif (isLoggedIn() && $owns && $userReviewed): ?>
          <div style="margin-top:1rem;padding:.75rem 1rem;background:var(--success-bg);border-radius:var(--radius-sm);font-size:.85rem;color:var(--success);">
            <i class="bi bi-check-circle-fill"></i> You have already reviewed this asset.
          </div>
        <?php elseif (!isLoggedIn()): ?>
          <div style="margin-top:1rem;padding:.75rem 1rem;background:var(--bg-hover);border-radius:var(--radius-sm);font-size:.85rem;color:var(--text-secondary);">
            <i class="bi bi-lock"></i>
            <a href="login.php" style="font-weight:600;">Sign in</a> and own this asset to leave a review.
          </div>
        <?php elseif (isLoggedIn() && !$owns): ?>
          <div style="margin-top:1rem;padding:.75rem 1rem;background:var(--bg-hover);border-radius:var(--radius-sm);font-size:.85rem;color:var(--text-secondary);">
            <i class="bi bi-bag"></i> Purchase or claim this asset to leave a review.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right: purchase panel -->
    <div>
      <div class="purchase-panel">
        <!-- Price -->
        <p style="color:var(--text-muted);font-size:.8rem;text-transform:uppercase;letter-spacing:.08em;font-weight:700;">
          <?= $product['price'] == 0 ? 'Free Asset' : 'Price' ?>
        </p>
        <div class="price-big <?= $product['price'] == 0 ? 'free' : '' ?>">
          <?= fPrice((float)$product['price']) ?>
        </div>

        <?php if ($owns): ?>
          <!-- Already owned -->
          <div class="badge-ms badge-success" style="margin-bottom:1rem;font-size:.8rem;padding:.4rem .9rem;">
            <i class="bi bi-check-circle-fill"></i> In Your Library
          </div>
          <a href="my-assets.php" class="btn-ms btn-ms-ghost btn-full" style="margin-bottom:.6rem;">
            <i class="bi bi-collection-play"></i> View My Assets
          </a>

        <?php elseif (!isLoggedIn()): ?>
          <a href="login.php" class="btn-ms btn-ms-primary btn-full" style="margin-bottom:.6rem;">
            <i class="bi bi-box-arrow-in-right"></i>
            <?= $product['price'] == 0 ? 'Sign In to Get Free' : 'Sign In to Buy' ?>
          </a>
          <a href="register.php" class="btn-ms btn-ms-ghost btn-full">
            Create Account
          </a>

        <?php elseif ($inCart): ?>
          <a href="cart.php" class="btn-ms btn-ms-success btn-full" style="margin-bottom:.6rem;">
            <i class="bi bi-bag-check"></i> View in Cart
          </a>
          <a href="checkout.php" class="btn-ms btn-ms-primary btn-full">
            <i class="bi bi-credit-card"></i> Checkout
          </a>

        <?php else: ?>
          <!-- Add to cart -->
          <form action="process/add-to-cart.php" method="POST" style="display:contents;">
            <?php csrfField() ?>
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <input type="hidden" name="redirect" value="product.php?id=<?= $product['id'] ?>">
            <button type="submit" class="btn-ms btn-ms-primary btn-full" style="margin-bottom:.6rem;">
              <i class="bi bi-bag-plus"></i>
              <?= $product['price'] == 0 ? 'Add to Library' : 'Add to Cart' ?>
            </button>
          </form>
          <!-- Buy now -->
          <form action="process/add-to-cart.php" method="POST" style="display:contents;">
            <?php csrfField() ?>
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
            <input type="hidden" name="redirect" value="checkout.php">
            <button type="submit" class="btn-ms btn-ms-ghost btn-full">
              <i class="bi bi-lightning-fill"></i> Buy Now
            </button>
          </form>
        <?php endif; ?>

        <div class="divider"></div>

        <!-- Quick meta -->
        <div style="display:flex;flex-direction:column;gap:.6rem;font-size:.83rem;">
          <div style="display:flex;justify-content:space-between;">
            <span style="color:var(--text-muted);">Version</span>
            <span><?= e($product['version']) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span style="color:var(--text-muted);">Size</span>
            <span><?= e($product['file_size']) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span style="color:var(--text-muted);">Released</span>
            <span><?= fDate($product['release_date']) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span style="color:var(--text-muted);">Downloads</span>
            <span><?= number_format($product['download_count']) ?></span>
          </div>
        </div>

        <?php if (isLoggedIn()): ?>
        <div class="divider"></div>
        <a href="publish.php?edit=<?= $product['id'] ?>" class="btn-ms btn-ms-ghost btn-full btn-sm">
          <i class="bi bi-pencil"></i> Edit This Asset
        </a>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- end .product-detail-layout -->
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
