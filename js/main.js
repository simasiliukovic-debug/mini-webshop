/**
 * ModStore — Main JavaScript
 */

// ── Theme Toggle ──────────────────────────────────────────────
(function () {
  const THEME_KEY = 'ms_theme';
  const html  = document.documentElement;
  const icon  = document.getElementById('themeIcon');
  const btn   = document.getElementById('themeToggle');

  const saved = localStorage.getItem(THEME_KEY) || 'dark';
  applyTheme(saved);

  if (btn) btn.addEventListener('click', () => {
    const next = html.dataset.theme === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    localStorage.setItem(THEME_KEY, next);
  });

  function applyTheme(theme) {
    html.dataset.theme = theme;
    if (icon) {
      icon.className = theme === 'dark' ? 'bi bi-moon-fill' : 'bi bi-sun-fill';
    }
  }
})();

// ── Mobile nav ────────────────────────────────────────────────
(function () {
  const burger = document.getElementById('navBurger');
  const menu   = document.getElementById('mobileMenu');
  if (!burger || !menu) return;
  burger.addEventListener('click', () => {
    menu.classList.toggle('open');
    burger.querySelector('i').className =
      menu.classList.contains('open') ? 'bi bi-x-lg' : 'bi bi-list';
  });
})();

// ── Scroll nav shadow ────────────────────────────────────────
(function () {
  const nav = document.getElementById('msNav');
  if (!nav) return;
  window.addEventListener('scroll', () => {
    nav.style.boxShadow = window.scrollY > 20
      ? '0 4px 24px rgba(0,0,0,.35)'
      : 'none';
  }, { passive: true });
})();

// ── Star-rating input (reverse order fix) ─────────────────────
(function () {
  const wrap = document.querySelector('.star-input');
  if (!wrap) return;
  wrap.addEventListener('mouseleave', () => {
    // restore to checked state
  });
})();

// ── Animate product cards on load ────────────────────────────
(function () {
  const cards = document.querySelectorAll('.ms-card, .asset-card');
  cards.forEach((card, i) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(12px)';
    card.style.transition = 'opacity .35s ease, transform .35s ease';
    setTimeout(() => {
      card.style.opacity = '1';
      card.style.transform = 'translateY(0)';
    }, 60 + i * 40);
  });
})();
