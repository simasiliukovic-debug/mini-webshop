<?php
/**
 * ModStore — Register
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$errors   = [];
$username = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username        = trim($_POST['username']         ?? '');
    $email           = strtolower(trim($_POST['email'] ?? ''));
    $password        = $_POST['password']              ?? '';
    $passwordConfirm = $_POST['password_confirm']      ?? '';

    // Validate username
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Username must be 3–50 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username may only contain letters, numbers, and underscores.';
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate password
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        // Check uniqueness
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $errors[] = 'That email or username is already in use.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $hash]);
        $newId = (int) $pdo->lastInsertId();

        session_regenerate_id(true);
        $_SESSION['user_id']    = $newId;
        $_SESSION['user_name']  = $username;
        $_SESSION['user_email'] = $email;

        setFlash('success', 'Account created! Welcome to ModStore, ' . $username . '.');
        header('Location: index.php');
        exit;
    }
}

$page_title   = 'Create Account';
$current_page = '';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-box fade-in">
    <h1>Create Account</h1>
    <p class="auth-sub">Join ModStore and access premium game assets.</p>

    <?php if ($errors): ?>
      <div class="flash-toast flash-danger" style="position:static;animation:none;margin-bottom:1rem;">
        <i class="bi bi-x-circle-fill"></i>
        <div>
          <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <form action="register.php" method="POST" class="ms-form" novalidate>
      <?php csrfField() ?>

      <div class="form-group">
        <label for="username">Username</label>
        <input
          type="text" id="username" name="username"
          class="ms-input" value="<?= e($username) ?>"
          placeholder="coolmodder42" required
          minlength="3" maxlength="50"
          autocomplete="username"
        >
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input
          type="email" id="email" name="email"
          class="ms-input" value="<?= e($email) ?>"
          placeholder="you@example.com" required
          autocomplete="email"
        >
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="password">Password</label>
          <div style="position:relative;">
            <input
              type="password" id="password" name="password"
              class="ms-input" placeholder="Min 8 characters"
              required minlength="8"
              style="padding-right:2.8rem;"
              autocomplete="new-password"
            >
            <button type="button" onclick="togglePw('password','eye1')"
              style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
              <i class="bi bi-eye" id="eye1"></i>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label for="password_confirm">Confirm Password</label>
          <div style="position:relative;">
            <input
              type="password" id="password_confirm" name="password_confirm"
              class="ms-input" placeholder="Repeat password"
              required style="padding-right:2.8rem;"
              autocomplete="new-password"
            >
            <button type="button" onclick="togglePw('password_confirm','eye2')"
              style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;">
              <i class="bi bi-eye" id="eye2"></i>
            </button>
          </div>
        </div>
      </div>

      <p style="font-size:.78rem;color:var(--text-muted);line-height:1.5;">
        By creating an account you agree to our Terms of Service.
        We handle personal data in accordance with GDPR guidelines.
      </p>

      <button type="submit" class="btn-ms btn-ms-primary btn-full" style="margin-top:.25rem;">
        <i class="bi bi-person-plus"></i> Create Account
      </button>
    </form>

    <div class="auth-divider">or</div>

    <p style="text-align:center;font-size:.88rem;color:var(--text-secondary);">
      Already have an account?
      <a href="login.php" style="font-weight:600;">Sign in</a>
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
