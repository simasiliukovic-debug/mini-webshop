<?php
/**
 * ModStore — Global Header
 * Expected vars set by the page before including:
 *   $page_title   — string, shown in <title>
 *   $current_page — string, for nav active state ('store','cart','orders','assets')
 *   $pdo          — PDO instance (already required by the page)
 */
$page_title   = $page_title   ?? 'ModStore';
$current_page = $current_page ?? '';
$cartCount    = cartCount();
$flash        = getFlash();
$loggedIn     = isLoggedIn();
$username     = $loggedIn ? e($_SESSION['user_name'] ?? 'Account') : '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?> — ModStore</title>

  <!-- Google Fonts: Plus Jakarta Sans (display) + Inter (body) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <!-- ModStore custom styles -->
  <link rel="stylesheet" href="/modstore/css/style.css">
</head>
<body>

<!-- ── Navigation ──────────────────────────────────────────── -->
<nav class="ms-nav" id="msNav">
  <div class="container-xl">
    <div class="ms-nav__inner">

      <!-- Logo -->
      <a href="/modstore/index.php" class="ms-nav__logo">
        <span class="logo-icon"><i class="bi bi-controller"></i></span>
        <span class="logo-text">ModStore</span>
      </a>

      <!-- Centre links -->
      <ul class="ms-nav__links">
        <li><a href="/modstore/index.php" class="<?= $current_page === 'store' ? 'active' : '' ?>">
          <i class="bi bi-grid-3x3-gap"></i> Store
        </a></li>
        <?php if ($loggedIn): ?>
        <li><a href="/modstore/my-assets.php" class="<?= $current_page === 'assets' ? 'active' : '' ?>">
          <i class="bi bi-collection-play"></i> My Assets
        </a></li>
        <li><a href="/modstore/orders.php" class="<?= $current_page === 'orders' ? 'active' : '' ?>">
          <i class="bi bi-receipt"></i> Orders
        </a></li>
        <li><a href="/modstore/publish.php" class="<?= $current_page === 'publish' ? 'active' : '' ?>"
               style="color:var(--accent);border:1px solid rgba(47,109,246,.3);border-radius:var(--radius-sm);">
          <i class="bi bi-upload"></i> Publish
        </a></li>
        <?php endif; ?>
      </ul>

      <!-- Right actions -->
      <div class="ms-nav__actions">

        <!-- Theme toggle -->
        <button class="btn-icon" id="themeToggle" title="Toggle theme" aria-label="Toggle theme">
          <i class="bi bi-moon-fill" id="themeIcon"></i>
        </button>

        <!-- Cart -->
        <a href="/modstore/cart.php" class="btn-icon cart-btn <?= $current_page === 'cart' ? 'active' : '' ?>" title="Cart">
          <i class="bi bi-bag"></i>
          <?php if ($cartCount > 0): ?>
            <span class="cart-badge" id="cartBadge"><?= $cartCount ?></span>
          <?php endif; ?>
        </a>

        <?php if ($loggedIn): ?>
          <!-- User dropdown -->
          <div class="dropdown">
            <button class="btn-user dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
              <span class="user-avatar"><?= strtoupper(substr($username, 0, 1)) ?></span>
              <span class="user-name d-none d-md-inline"><?= $username ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end ms-dropdown">
              <li class="dropdown-header">
                <span><?= $username ?></span>
                <small><?= e($_SESSION['user_email'] ?? '') ?></small>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="/modstore/my-assets.php"><i class="bi bi-collection-play"></i> My Assets</a></li>
              <li><a class="dropdown-item" href="/modstore/orders.php"><i class="bi bi-receipt"></i> Order History</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="/modstore/logout.php"><i class="bi bi-box-arrow-right"></i> Sign Out</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="/modstore/login.php" class="btn-ms btn-ms-ghost btn-sm">Sign In</a>
          <a href="/modstore/register.php" class="btn-ms btn-ms-primary btn-sm">Register</a>
        <?php endif; ?>

        <!-- Mobile hamburger -->
        <button class="btn-icon d-xl-none ms-nav__burger" id="navBurger" aria-label="Menu">
          <i class="bi bi-list"></i>
        </button>
      </div>
    </div>

    <!-- Mobile menu -->
    <div class="ms-nav__mobile" id="mobileMenu">
      <a href="/modstore/index.php" class="<?= $current_page==='store'?'active':''?>">
        <i class="bi bi-grid-3x3-gap"></i> Store
      </a>
      <?php if ($loggedIn): ?>
        <a href="/modstore/my-assets.php" class="<?= $current_page==='assets'?'active':''?>">
          <i class="bi bi-collection-play"></i> My Assets
        </a>
        <a href="/modstore/orders.php" class="<?= $current_page==='orders'?'active':''?>">
          <i class="bi bi-receipt"></i> Orders
        </a>
        <a href="/modstore/publish.php" class="<?= $current_page==='publish'?'active':''?>" style="color:var(--accent);">
          <i class="bi bi-upload"></i> Publish Asset
        </a>
        <a href="/modstore/cart.php" class="<?= $current_page==='cart'?'active':''?>">
          <i class="bi bi-bag"></i> Cart <?= $cartCount > 0 ? "($cartCount)" : '' ?>
        </a>
        <div style="height:1px;background:var(--border);margin:.4rem 0;"></div>
        <a href="/modstore/logout.php" style="color:var(--danger);">
          <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
      <?php else: ?>
        <a href="/modstore/cart.php">
          <i class="bi bi-bag"></i> Cart <?= $cartCount > 0 ? "($cartCount)" : '' ?>
        </a>
        <div style="height:1px;background:var(--border);margin:.4rem 0;"></div>
        <a href="/modstore/login.php"><i class="bi bi-box-arrow-in-right"></i> Sign In</a>
        <a href="/modstore/register.php" style="color:var(--accent);"><i class="bi bi-person-plus"></i> Create Account</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- ── Flash Message ────────────────────────────────────────── -->
<?php if ($flash): ?>
<div class="flash-toast flash-<?= e($flash['type']) ?>" id="flashToast" role="alert">
  <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : ($flash['type'] === 'danger' ? 'bi-x-circle-fill' : 'bi-info-circle-fill') ?>"></i>
  <?= e($flash['message']) ?>
  <button class="flash-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
</div>
<script>setTimeout(()=>{const t=document.getElementById('flashToast');if(t){t.classList.add('fade-out');setTimeout(()=>t.remove(),400);}},4000);</script>
<?php endif; ?>

<!-- ── Page Wrapper ─────────────────────────────────────────── -->
<main class="ms-main">
