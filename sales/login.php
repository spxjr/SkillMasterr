<?php
session_start();
require_once __DIR__ . '/includes/sales_config.php';

if (!empty($_SESSION['rep_id'])) redirect(SALES_URL . '/dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    if (!$u || !$p) {
        $error = 'Please enter your username and password.';
    } elseif (!salesLogin($u, $p)) {
        sleep(1);
        $error = 'Invalid username or password.';
    } else {
        redirect(SALES_URL . '/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TSM Sales Portal — Sign In</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;600;700&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/sales/assets/css/sales.css">
</head>
<body>

<div class="login-page">
  <!-- LEFT -->
  <div class="login-left">
    <div class="login-brand">
      <span class="login-star">★</span>
      <span class="login-brand-name">Skill Masters</span>
      <span class="login-brand-sub">Sales Rep Portal</span>
    </div>
    <p class="login-tagline">
      Track your leads, manage your pipeline,<br>
      and close more deals.<br><br>
      <strong style="color:rgba(201,168,76,.6)">Let's get after it.</strong>
    </p>
  </div>

  <!-- RIGHT -->
  <div class="login-right">
    <div class="login-box">
      <h2>Sales Rep Sign In</h2>
      <p>Enter your credentials to access your sales dashboard.</p>

      <?php if ($error): ?>
      <div class="login-error"><i class="fa-solid fa-circle-exclamation"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group" style="margin-bottom:14px">
          <label>Username</label>
          <div class="input-wrap">
            <i class="fa-solid fa-user"></i>
            <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   placeholder="danny or steven" autocomplete="username" required autofocus>
          </div>
        </div>
        <div class="form-group" style="margin-bottom:22px">
          <label>Password</label>
          <div class="input-wrap">
            <i class="fa-solid fa-lock"></i>
            <input type="password" name="password" id="pwField" placeholder="••••••••••" autocomplete="current-password" required>
            <button type="button" class="pw-toggle" onclick="togglePw('pwField','pwIcon')"><i class="fa-solid fa-eye" id="pwIcon"></i></button>
          </div>
        </div>
        <button type="submit" class="login-btn">
          <i class="fa-solid fa-right-to-bracket"></i> Sign In
        </button>
      </form>

      <div style="margin-top:24px;padding-top:18px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
        <a href="<?= BASE_URL ?>/login.php" style="font-size:.78rem;color:var(--text-muted);text-decoration:none;display:flex;align-items:center;gap:5px">
          <i class="fa-solid fa-arrow-left"></i> Admin Login
        </a>
        <div style="font-size:.72rem;color:var(--text-muted);display:flex;align-items:center;gap:5px">
          <i class="fa-solid fa-shield-halved" style="color:var(--gold-dark)"></i> TSM Internal
        </div>
      </div>

      <div style="margin-top:16px;padding:12px 14px;background:var(--gold-pale);border:1px solid rgba(201,168,76,.3);border-radius:var(--radius);font-size:.78rem;color:var(--text-mid)">
        <strong style="color:var(--gold-dark)">Default credentials</strong><br>
        Danny: <code>danny</code> / <code>TsmSales2024!</code><br>
        Steven: <code>steven</code> / <code>TsmSales2024!</code>
      </div>
    </div>
  </div>
</div>

<script src="<?= BASE_URL ?>/sales/assets/js/sales.js"></script>
</body>
</html>
