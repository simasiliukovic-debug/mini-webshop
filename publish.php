<?php
/**
 * ModStore — Publish / Edit Product
 * Inspired by Unity Asset Store Publisher Portal
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

requireAuth('login.php');

// Edit mode?
$editId  = (int) ($_GET['edit'] ?? 0);
$product = null;

if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND is_active = 1');
    $stmt->execute([$editId]);
    $product = $stmt->fetch();
    if (!$product) {
        setFlash('danger', 'Product not found.');
        header('Location: index.php');
        exit;
    }
}

// Flash back old values on validation error
$old    = $_SESSION['publish_old']    ?? [];
$errors = $_SESSION['publish_errors'] ?? [];
unset($_SESSION['publish_old'], $_SESSION['publish_errors']);

// Helper: get field value (old > product > default)
function fieldVal(string $key, $default, array $old, ?array $product): mixed {
    if (isset($old[$key])) return $old[$key];
    if ($product && isset($product[$key])) return $product[$key];
    return $default;
}

// Categories
$cats = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

$isEdit     = $product !== null;
$page_title = $isEdit ? 'Edit Product' : 'Publish Asset';
$current_page = '';
require_once __DIR__ . '/includes/header.php';

$isFreeChecked = isset($old['is_free'])
    ? true
    : ($isEdit ? (float)$product['price'] === 0.0 : false);
?>

<style>
/* Publisher Portal extra styles */
.pub-layout {
  display: grid;
  grid-template-columns: 1fr 320px;
  gap: 1.75rem;
  align-items: start;
}
@media (max-width: 900px) { .pub-layout { grid-template-columns: 1fr; } }

.pub-section {
  background: var(--bg-secondary);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  margin-bottom: 1.25rem;
}
.pub-section__head {
  display: flex;
  align-items: center;
  gap: .65rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border);
  background: var(--bg-hover);
}
.pub-section__head .step-num {
  width: 26px; height: 26px;
  background: var(--accent);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .75rem;
  font-weight: 800;
  color: #fff;
  flex-shrink: 0;
}
.pub-section__head h3 {
  font-size: .95rem;
  font-weight: 700;
  margin: 0;
}
.pub-section__head p {
  font-size: .78rem;
  color: var(--text-muted);
  margin: 0;
}
.pub-section__body {
  padding: 1.5rem;
}

/* Price toggle */
.price-toggle {
  display: flex;
  gap: .5rem;
  margin-bottom: .85rem;
}
.price-toggle label {
  flex: 1;
  display: flex;
  align-items: center;
  gap: .5rem;
  padding: .65rem 1rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all var(--t-fast);
  font-size: .88rem;
  font-weight: 500;
}
.price-toggle input[type="radio"] { display: none; }
.price-toggle input:checked + label {
  border-color: var(--accent);
  background: var(--accent-subtle);
  color: var(--accent);
}
.price-toggle label:hover { border-color: var(--border-hover); }

/* Category cards */
.cat-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: .5rem;
}
@media (max-width: 576px) { .cat-grid { grid-template-columns: repeat(2, 1fr); } }

.cat-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: .3rem;
  padding: .75rem .5rem;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all var(--t-fast);
  font-size: .78rem;
  font-weight: 500;
  color: var(--text-secondary);
  text-align: center;
}
.cat-card input[type="radio"] { display: none; }
.cat-card:hover {
  border-color: var(--border-hover);
  color: var(--text-primary);
  background: var(--bg-hover);
}
.cat-card.selected {
  border-color: var(--accent);
  background: var(--accent-subtle);
  color: var(--accent);
}
.cat-card i { font-size: 1.3rem; }

/* Preview card */
.preview-product-card {
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  background: var(--bg-primary);
}
.preview-thumb {
  aspect-ratio: 16/9;
  display: flex; align-items: center; justify-content: center;
  font-size: 2.5rem;
  color: rgba(255,255,255,.12);
  background: linear-gradient(135deg, #1e2a45 0%, #0f1115 100%);
  position: relative;
}
.preview-body { padding: .85rem 1rem; }
.preview-name { font-family: 'Syne', sans-serif; font-weight: 700; font-size: .95rem; }
.preview-cat  { font-size: .72rem; color: var(--accent); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
.preview-price{ font-family: 'Syne', sans-serif; font-size: 1.05rem; font-weight: 800; }
.preview-price.free { color: var(--success); }

/* Char counter */
.char-count { font-size: .73rem; color: var(--text-muted); text-align: right; }

/* Tip box */
.tip-box {
  display: flex;
  gap: .6rem;
  padding: .75rem 1rem;
  background: var(--accent-subtle);
  border: 1px solid rgba(47,109,246,.2);
  border-radius: var(--radius-sm);
  font-size: .8rem;
  color: var(--text-secondary);
  line-height: 1.5;
}
.tip-box i { color: var(--accent); flex-shrink: 0; margin-top: .1rem; }
</style>

<div class="container-xl py-4">

  <!-- Page header -->
  <div class="page-header" style="padding-top:.5rem;margin-bottom:2rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
      <div>
        <h1>
          <i class="bi bi-upload" style="color:var(--accent);font-size:1.4rem;"></i>
          <?= $isEdit ? 'Edit Asset' : 'Publish New Asset' ?>
        </h1>
        <p><?= $isEdit
            ? 'Update the details of "' . e($product['name']) . '".'
            : 'Fill in your asset details to publish it to the store.' ?>
        </p>
      </div>
      <?php if ($isEdit): ?>
        <div style="display:flex;gap:.5rem;">
          <a href="product.php?id=<?= $editId ?>" class="btn-ms btn-ms-ghost btn-sm">
            <i class="bi bi-eye"></i> View Live
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Validation errors -->
  <?php if (!empty($errors)): ?>
    <div class="flash-toast flash-danger" style="position:static;animation:none;margin-bottom:1.5rem;display:flex;flex-direction:column;gap:.25rem;align-items:flex-start;">
      <div style="display:flex;align-items:center;gap:.5rem;font-weight:600;">
        <i class="bi bi-x-circle-fill"></i> Please fix the following errors:
      </div>
      <?php foreach ($errors as $err): ?>
        <div style="font-size:.85rem;">— <?= e($err) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form action="process/save-product.php" method="POST" id="publishForm" novalidate>
    <?php csrfField() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="product_id" value="<?= $editId ?>">
    <?php endif; ?>

    <div class="pub-layout">

      <!-- ── Left column ───────────────────────────────── -->
      <div>

        <!-- Section 1: Basic Info -->
        <div class="pub-section">
          <div class="pub-section__head">
            <div class="step-num">1</div>
            <div>
              <h3>Basic Information</h3>
              <p>Name and description of your asset</p>
            </div>
          </div>
          <div class="pub-section__body">
            <div class="ms-form">

              <div class="form-group">
                <label for="name">Asset Name <span style="color:var(--danger);">*</span></label>
                <input type="text" id="name" name="name"
                  class="ms-input"
                  value="<?= e(fieldVal('name', '', $old, $product)) ?>"
                  placeholder="e.g. Neon City Map Pack"
                  maxlength="100" required
                  oninput="updatePreview()">
                <div class="char-count" id="nameCount">0 / 100</div>
              </div>

              <div class="form-group">
                <label for="description">
                  Description <span style="color:var(--danger);">*</span>
                  <span style="font-weight:400;color:var(--text-muted);font-size:.78rem;">(min 20 chars)</span>
                </label>
                <textarea id="description" name="description"
                  class="ms-textarea" style="min-height:160px;"
                  placeholder="Describe what your asset includes, features, compatible engines, technical specs…"
                  maxlength="3000" required
                  oninput="updateDescCount()"><?= e(fieldVal('description', '', $old, $product)) ?></textarea>
                <div class="char-count" id="descCount">0 / 3000</div>
              </div>

              <div class="tip-box">
                <i class="bi bi-lightbulb-fill"></i>
                <div>
                  <strong>Pro tip:</strong> Good descriptions include what's in the pack, compatible engines/versions, polygon counts, and any technical requirements.
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- Section 2: Category -->
        <div class="pub-section">
          <div class="pub-section__head">
            <div class="step-num">2</div>
            <div>
              <h3>Category</h3>
              <p>Choose the best matching category</p>
            </div>
          </div>
          <div class="pub-section__body">
            <div class="cat-grid" id="catGrid">
              <?php
                $selectedCat = (int) fieldVal('category_id', 0, $old, $product);
              ?>
              <?php foreach ($cats as $cat): ?>
                <?php $checked = $selectedCat === (int)$cat['id']; ?>
                <label class="cat-card <?= $checked ? 'selected' : '' ?>"
                       for="cat_<?= $cat['id'] ?>"
                       onclick="selectCat(this, '<?= e($cat['name']) ?>', '<?= e($cat['icon']) ?>')">
                  <input type="radio" name="category_id" id="cat_<?= $cat['id'] ?>"
                    value="<?= $cat['id'] ?>" <?= $checked ? 'checked' : '' ?> required>
                  <i class="bi <?= e($cat['icon']) ?>"></i>
                  <span><?= e($cat['name']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
            <p id="catError" style="display:none;" class="form-error" style="margin-top:.5rem;">
              <i class="bi bi-exclamation-circle"></i> Please select a category.
            </p>
          </div>
        </div>

        <!-- Section 3: Pricing -->
        <div class="pub-section">
          <div class="pub-section__head">
            <div class="step-num">3</div>
            <div>
              <h3>Pricing</h3>
              <p>Set your price or offer it for free</p>
            </div>
          </div>
          <div class="pub-section__body">

            <div class="price-toggle">
              <input type="radio" name="price_type" id="ptFree" value="free"
                <?= $isFreeChecked ? 'checked' : '' ?> onchange="togglePrice()">
              <label for="ptFree">
                <i class="bi bi-gift" style="color:var(--success);"></i>
                <div>
                  <div style="font-weight:600;">Free</div>
                  <div style="font-size:.75rem;color:var(--text-muted);">Available to everyone</div>
                </div>
              </label>

              <input type="radio" name="price_type" id="ptPaid" value="paid"
                <?= !$isFreeChecked ? 'checked' : '' ?> onchange="togglePrice()">
              <label for="ptPaid">
                <i class="bi bi-tag" style="color:var(--accent);"></i>
                <div>
                  <div style="font-weight:600;">Paid</div>
                  <div style="font-size:.75rem;color:var(--text-muted);">Set your price in €</div>
                </div>
              </label>
            </div>

            <!-- Hidden field for is_free -->
            <input type="hidden" name="is_free" id="isFreeHidden" value="<?= $isFreeChecked ? '1' : '' ?>">

            <div id="priceInput" style="display:<?= $isFreeChecked ? 'none' : 'block' ?>;">
              <div class="form-group">
                <label for="price">Price (€)</label>
                <div style="position:relative;">
                  <span style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-weight:600;">€</span>
                  <input type="number" id="price" name="price"
                    class="ms-input" style="padding-left:2rem;"
                    value="<?= e(fieldVal('price', '9.99', $old, $product)) ?>"
                    placeholder="9.99" min="0.01" max="999.99" step="0.01"
                    oninput="updatePreview()">
                </div>
              </div>
            </div>

          </div>
        </div>

        <!-- Section 4: Technical Details -->
        <div class="pub-section">
          <div class="pub-section__head">
            <div class="step-num">4</div>
            <div>
              <h3>Technical Details</h3>
              <p>Version, file size, and release date</p>
            </div>
          </div>
          <div class="pub-section__body">
            <div class="ms-form">
              <div class="form-row">
                <div class="form-group">
                  <label for="version">Version</label>
                  <input type="text" id="version" name="version"
                    class="ms-input"
                    value="<?= e(fieldVal('version', '1.0.0', $old, $product)) ?>"
                    placeholder="1.0.0" maxlength="20">
                </div>
                <div class="form-group">
                  <label for="file_size">File Size</label>
                  <input type="text" id="file_size" name="file_size"
                    class="ms-input"
                    value="<?= e(fieldVal('file_size', '—', $old, $product)) ?>"
                    placeholder="250 MB" maxlength="20">
                </div>
              </div>
              <div class="form-group">
                <label for="release_date">Release Date <span style="color:var(--danger);">*</span></label>
                <input type="date" id="release_date" name="release_date"
                  class="ms-input"
                  value="<?= e(fieldVal('release_date', date('Y-m-d'), $old, $product)) ?>"
                  required>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- ── Right column (sticky panel) ───────────────── -->
      <div>

        <!-- Live preview card -->
        <div class="pub-section" style="margin-bottom:1.25rem;">
          <div class="pub-section__head">
            <i class="bi bi-eye" style="color:var(--accent);"></i>
            <div>
              <h3>Store Preview</h3>
              <p>How buyers will see your asset</p>
            </div>
          </div>
          <div class="pub-section__body" style="padding:1rem;">
            <div class="preview-product-card">
              <div class="preview-thumb" id="previewThumb">
                <i class="bi bi-box" id="previewIcon" style="font-size:2.5rem;color:rgba(255,255,255,.15);"></i>
                <span style="position:absolute;top:.55rem;right:.55rem;" id="previewBadge">
                  <span class="badge-ms badge-free"><i class="bi bi-gift"></i> Free</span>
                </span>
              </div>
              <div class="preview-body">
                <div class="preview-cat" id="previewCat">Select a category</div>
                <div class="preview-name" id="previewName" style="min-height:1.3em;color:var(--text-muted);">
                  Your asset name…
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:.65rem;padding-top:.65rem;border-top:1px solid var(--border);">
                  <span class="preview-price free" id="previewPrice">Free</span>
                  <span class="btn-ms btn-ms-primary btn-sm" style="pointer-events:none;font-size:.75rem;">View</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Guidelines -->
        <div class="ms-panel" style="margin-bottom:1.25rem;font-size:.8rem;">
          <h4 style="font-size:.85rem;font-weight:700;margin-bottom:.75rem;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-shield-check" style="color:var(--success);"></i>
            Publishing Guidelines
          </h4>
          <ul style="list-style:none;padding:0;display:flex;flex-direction:column;gap:.45rem;color:var(--text-secondary);">
            <li><i class="bi bi-check2" style="color:var(--success);margin-right:.35rem;"></i>Assets must be your original work</li>
            <li><i class="bi bi-check2" style="color:var(--success);margin-right:.35rem;"></i>No AI-generated or stolen content</li>
            <li><i class="bi bi-check2" style="color:var(--success);margin-right:.35rem;"></i>Descriptions must be accurate</li>
            <li><i class="bi bi-check2" style="color:var(--success);margin-right:.35rem;"></i>Pricing must reflect quality</li>
          </ul>
        </div>

        <!-- Submit button -->
        <button type="submit" class="btn-ms btn-ms-primary btn-full btn-lg" id="submitBtn">
          <i class="bi bi-<?= $isEdit ? 'check-circle' : 'upload' ?>"></i>
          <?= $isEdit ? 'Save Changes' : 'Publish to Store' ?>
        </button>

        <?php if ($isEdit): ?>
          <a href="product.php?id=<?= $editId ?>" class="btn-ms btn-ms-ghost btn-full" style="margin-top:.6rem;">
            <i class="bi bi-x-circle"></i> Cancel
          </a>
        <?php else: ?>
          <a href="index.php" class="btn-ms btn-ms-ghost btn-full" style="margin-top:.6rem;">
            <i class="bi bi-arrow-left"></i> Back to Store
          </a>
        <?php endif; ?>

        <p style="font-size:.72rem;color:var(--text-muted);text-align:center;margin-top:.75rem;line-height:1.5;">
          Your asset will be visible in the store immediately after publishing.
        </p>
      </div>

    </div><!-- end pub-layout -->
  </form>
</div>

<script>
// ── Category gradients (mirrors PHP cardGradient) ─────────────
const catGradients = {
  1: 'linear-gradient(135deg,#1e2a45 0%,#0f1115 100%)',
  2: 'linear-gradient(135deg,#1a3028 0%,#0f1115 100%)',
  3: 'linear-gradient(135deg,#2e1a3a 0%,#0f1115 100%)',
  4: 'linear-gradient(135deg,#1a2a3a 0%,#0f1115 100%)',
  5: 'linear-gradient(135deg,#2a1a1a 0%,#0f1115 100%)',
  6: 'linear-gradient(135deg,#2a2218 0%,#0f1115 100%)',
};
let selectedCatId = <?= (int)fieldVal('category_id', 0, $old, $product) ?>;
let selectedCatName = '<?= e(array_values(array_filter($cats, fn($c) => $c['id'] == fieldVal('category_id', 0, $old, $product)))[0]['name'] ?? 'Select a category') ?>';
let selectedCatIcon = '<?= e(array_values(array_filter($cats, fn($c) => $c['id'] == fieldVal('category_id', 0, $old, $product)))[0]['icon'] ?? 'bi-box') ?>';

function selectCat(el, name, icon) {
  document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('selected'));
  el.classList.add('selected');
  selectedCatName = name;
  selectedCatIcon = icon;
  const radio = el.querySelector('input[type="radio"]');
  if (radio) { selectedCatId = parseInt(radio.value); }
  document.getElementById('catError').style.display = 'none';
  updatePreview();
}

function togglePrice() {
  const isFree = document.getElementById('ptFree').checked;
  document.getElementById('priceInput').style.display = isFree ? 'none' : 'block';
  document.getElementById('isFreeHidden').value = isFree ? '1' : '';
  if (isFree) document.querySelector('[name="price"]').removeAttribute('required');
  else document.querySelector('[name="price"]').setAttribute('required', '');
  updatePreview();
}

function updatePreview() {
  const name    = document.getElementById('name').value.trim();
  const isFree  = document.getElementById('ptFree').checked;
  const price   = parseFloat(document.getElementById('price')?.value || 0);

  // Name
  const nameEl = document.getElementById('previewName');
  nameEl.textContent = name || 'Your asset name…';
  nameEl.style.color = name ? 'var(--text-primary)' : 'var(--text-muted)';

  // Category
  document.getElementById('previewCat').textContent = selectedCatName;
  document.getElementById('previewIcon').className   = 'bi ' + selectedCatIcon;
  if (selectedCatId && catGradients[selectedCatId]) {
    document.getElementById('previewThumb').style.background = catGradients[selectedCatId];
  }

  // Price
  const priceEl = document.getElementById('previewPrice');
  const badgeEl = document.getElementById('previewBadge');
  if (isFree) {
    priceEl.textContent = 'Free';
    priceEl.className = 'preview-price free';
    badgeEl.innerHTML = '<span class="badge-ms badge-free"><i class="bi bi-gift"></i> Free</span>';
  } else {
    const p = isNaN(price) || price <= 0 ? '—' : '€' + price.toFixed(2);
    priceEl.textContent = p;
    priceEl.className = 'preview-price';
    badgeEl.innerHTML = `<span class="badge-ms badge-paid">${p}</span>`;
  }
}

// Char counters
function updateNameCount() {
  const len = document.getElementById('name').value.length;
  document.getElementById('nameCount').textContent = len + ' / 100';
}
function updateDescCount() {
  const len = document.getElementById('description').value.length;
  document.getElementById('descCount').textContent = len + ' / 3000';
}

document.getElementById('name').addEventListener('input', () => { updateNameCount(); updatePreview(); });
document.getElementById('description').addEventListener('input', updateDescCount);

// Form validation
document.getElementById('publishForm').addEventListener('submit', function(e) {
  const cat = document.querySelector('input[name="category_id"]:checked');
  if (!cat) {
    e.preventDefault();
    document.getElementById('catError').style.display = 'flex';
    document.getElementById('catGrid').scrollIntoView({ behavior: 'smooth' });
  }
});

// Init
updateNameCount();
updateDescCount();
updatePreview();
togglePrice();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
