<?php
session_start();
require_once __DIR__ . '/includes/portal_config.php';

// Already logged in
if (isset($_SESSION['portal_user_id'])) {
    redirect(PORTAL_URL . '/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (empty($user) || empty($pass)) {
        $error = 'Please enter your username and password.';
    } elseif (!portalLogin($user, $pass)) {
        $error = 'Invalid username or password. Please try again.';
        // Small delay to slow brute force
        sleep(1);
    } else {
        redirect(PORTAL_URL . '/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TSM Client Portal — Sign In</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;600;700&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/portal/assets/css/portal.css">
</head>
<body>

<div class="login-page">
  <!-- LEFT PANEL -->
  <div class="login-left">
    <div class="login-logo">
      <span class="login-star">★</span>
      <span class="login-name">Texas Skill Masters</span>
      <span class="login-sub">Client Portal</span>
    </div>
    <p class="login-tagline">
      Access your venue's performance data,<br>
      revenue reports, and service history<br>
      — all in one place.
    </p>
  </div>

  <!-- RIGHT PANEL -->
  <div class="login-right">
    <div class="login-box">
      <h2>Welcome Back</h2>
      <p>Sign in to your venue account to view your machine performance and earnings.</p>

      <?php if ($error): ?>
      <div class="login-error">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="login-form">
        <div class="form-group">
          <label>Username</label>
          <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 placeholder="Your portal username" autocomplete="username" required autofocus>
        </div>
        <div class="form-group" style="margin-bottom:20px">
          <label>Password</label>
          <input type="password" name="password" placeholder="••••••••" autocomplete="current-password" required>
        </div>
        <button type="submit" class="login-btn">
          <i class="fa-solid fa-right-to-bracket"></i> Sign In to Portal
        </button>
      </form>

      <div class="demo-box">
        <strong>Demo Credentials</strong><br>
        Username: <code>luckystar</code> &nbsp;|&nbsp; Password: <code>demo1234</code><br>
        <span style="font-size:0.75rem;color:var(--text-muted)">Try any demo account from the portal_setup.sql</span>
      </div>

      <div style="margin-top:24px;text-align:center;font-size:0.78rem;color:var(--text-muted)">
        <i class="fa-solid fa-shield-halved" style="color:var(--gold-dark)"></i>
        Need access? Contact Texas Skill Masters at your next visit.
      </div>
    </div>
  </div>
</div>

</body>
</html>
