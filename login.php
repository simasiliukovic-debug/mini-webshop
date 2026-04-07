<?php
/**
 * ModStore — Login
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Already logged in → go to store
if (isLoggedIn()) { header('Location: index.php'); exit; }

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '') {
        $errors[] = 'Email is required.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, username, email, password FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['username'];
            $_SESSION['user_email'] = $user['email'];

            setFlash('success', 'Welcome back, ' . $user['username'] . '!');
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header("Location: $redirect");
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}

$page_title   = 'Sign In';
$current_page = '';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-box fade-in">
    <h1>Welcome back</h1>
    <p class="auth-sub">Sign in to your ModStore account.</p>

    <?php if ($errors): ?>
      <div class="flash-toast flash-danger" style="position:static;animation:none;margin-bottom:1rem;">
        <i class="bi bi-x-circle-fill"></i>
        <?= e($errors[0]) ?>
      </div>
    <?php endif; ?>

    <form action="login.php" method="POST" class="ms-form" novalidate>
      <?php csrfField() ?>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input
          type="email" id="email" name="email"
          class="ms-input" value="<?= e($email) ?>"
          placeholder="you@example.com" required autocomplete="email"
        >
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div style="position:relative;">
          <input
            type="password" id="password" name="password"
            class="ms-input" placeholder="Your password"
            required autocomplete="current-password"
            style="padding-right:2.8rem;"
          >
          <button type="button" onclick="togglePw('password','eyeIcon')"
            style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1rem;">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-ms btn-ms-primary btn-full" style="margin-top:.25rem;">
        Sign In
      </button>
    </form>

    <div class="auth-divider">or</div>

    <p style="text-align:center;font-size:.88rem;color:var(--text-secondary);">
      Don't have an account?
      <a href="register.php" style="font-weight:600;">Create one</a>
    </p>
  </div>
</div>

<script>
function togglePw(inputId, iconId) {
  const inp  = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'bi bi-eye';
  }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
