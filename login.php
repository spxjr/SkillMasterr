<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// Already logged in → go to dashboard
if (!empty($_SESSION['admin_id'])) {
    redirect(BASE_URL . '/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter your username and password.';
    } elseif (!adminLogin($username, $password)) {
        sleep(1); // slow brute force
        $error = 'Invalid username or password.';
    } else {
        redirect(BASE_URL . '/index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Texas Skill Masters — Admin Login</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;600;700&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  :root {
    --gold:      #C9A84C;
    --gold-light:#E8C56A;
    --gold-dark: #8B6914;
    --bg:        #0D0F14;
    --bg-card:   #141720;
    --border:    #252A3A;
    --text:      #F0EDE6;
    --muted:     #7A8099;
    --dim:       #4A5168;
    --red:       #E74C3C;
    --radius:    8px;
  }
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; }

  body {
    font-family: 'Barlow', sans-serif;
    background: var(--bg);
    color: var(--text);
    display: flex;
    min-height: 100vh;
  }

  /* ── Left decorative panel ── */
  .login-left {
    width: 480px;
    flex-shrink: 0;
    background: linear-gradient(160deg, #0a0c10 0%, #111820 50%, #0D1008 100%);
    border-right: 1px solid rgba(201,168,76,0.15);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 48px;
    position: relative;
    overflow: hidden;
  }

  /* Giant faint star watermark */
  .login-left::before {
    content: '★';
    position: absolute;
    font-size: 26rem;
    color: rgba(201,168,76,0.04);
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    line-height: 1;
    pointer-events: none;
    animation: slowSpin 60s linear infinite;
  }

  @keyframes slowSpin {
    from { transform: translate(-50%,-50%) rotate(0deg); }
    to   { transform: translate(-50%,-50%) rotate(360deg); }
  }

  /* Decorative corner lines */
  .login-left::after {
    content: '';
    position: absolute;
    inset: 20px;
    border: 1px solid rgba(201,168,76,0.06);
    border-radius: 4px;
    pointer-events: none;
  }

  .brand { text-align: center; position: relative; z-index: 1; }

  .brand-star {
    font-size: 4rem;
    color: var(--gold);
    text-shadow: 0 0 40px rgba(201,168,76,0.5), 0 0 80px rgba(201,168,76,0.2);
    line-height: 1;
    display: block;
    margin-bottom: 18px;
    animation: glow 4s ease-in-out infinite;
  }

  @keyframes glow {
    0%,100% { text-shadow: 0 0 20px rgba(201,168,76,0.4); }
    50%      { text-shadow: 0 0 50px rgba(201,168,76,0.7), 0 0 100px rgba(201,168,76,0.3); }
  }

  .brand-texas {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 0.85rem;
    letter-spacing: .3em;
    color: var(--muted);
    display: block;
  }

  .brand-name {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 3rem;
    letter-spacing: .1em;
    color: var(--gold);
    line-height: 1;
    display: block;
  }

  .brand-sub {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1rem;
    letter-spacing: .2em;
    color: rgba(255,255,255,0.4);
    display: block;
    margin-top: 2px;
  }

  .brand-divider {
    width: 60px;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
    margin: 24px auto;
  }

  .brand-tagline {
    font-size: 0.82rem;
    color: var(--dim);
    line-height: 1.7;
    text-align: center;
    max-width: 260px;
  }

  .stats-row {
    display: flex;
    gap: 28px;
    margin-top: 36px;
    position: relative;
    z-index: 1;
  }

  .stat-pill {
    text-align: center;
  }

  .stat-pill-val {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.6rem;
    color: var(--gold);
    line-height: 1;
  }

  .stat-pill-label {
    font-size: 0.62rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--dim);
    font-family: 'Barlow Condensed', sans-serif;
    margin-top: 3px;
  }

  /* ── Right login form ── */
  .login-right {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 32px;
    background: var(--bg);
  }

  .login-box {
    width: 100%;
    max-width: 400px;
  }

  .login-box h1 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 2.2rem;
    letter-spacing: .06em;
    color: var(--text);
    margin-bottom: 4px;
  }

  .login-box h1 span { color: var(--gold); }

  .login-desc {
    font-size: 0.85rem;
    color: var(--muted);
    margin-bottom: 32px;
    line-height: 1.5;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
    margin-bottom: 18px;
  }

  .form-group label {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.7rem;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--muted);
  }

  .input-wrap {
    position: relative;
  }

  .input-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--dim);
    font-size: 0.85rem;
    pointer-events: none;
  }

  .input-wrap input {
    width: 100%;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 12px 14px 12px 40px;
    color: var(--text);
    font-family: 'Barlow', sans-serif;
    font-size: 0.95rem;
    transition: border-color .2s, box-shadow .2s;
  }

  .input-wrap input:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201,168,76,0.12);
  }

  .input-wrap input::placeholder { color: var(--dim); }

  /* password toggle */
  .pw-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--dim);
    cursor: pointer;
    font-size: 0.85rem;
    padding: 4px;
    transition: color .15s;
  }
  .pw-toggle:hover { color: var(--gold); }

  .login-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, var(--gold-dark), var(--gold));
    color: #0D0F14;
    border: none;
    border-radius: var(--radius);
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: .1em;
    cursor: pointer;
    transition: all .2s;
    margin-top: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
  }

  .login-btn:hover {
    background: linear-gradient(135deg, var(--gold), var(--gold-light));
    box-shadow: 0 6px 24px rgba(201,168,76,0.35);
    transform: translateY(-1px);
  }

  .login-btn:active { transform: translateY(0); }

  .error-box {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    background: rgba(231,76,60,0.1);
    border: 1px solid rgba(231,76,60,0.3);
    border-radius: var(--radius);
    color: var(--red);
    font-size: 0.85rem;
    margin-bottom: 20px;
    animation: shake .4s ease;
  }

  @keyframes shake {
    0%,100% { transform: translateX(0); }
    25%      { transform: translateX(-6px); }
    75%      { transform: translateX(6px); }
  }

  .login-footer {
    margin-top: 28px;
    padding-top: 20px;
    border-top: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .login-footer-link {
    font-size: 0.78rem;
    color: var(--dim);
    text-decoration: none;
    transition: color .15s;
    display: flex;
    align-items: center;
    gap: 5px;
  }

  .login-footer-link:hover { color: var(--gold); }

  .secure-badge {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    color: var(--dim);
    font-family: 'Barlow Condensed', sans-serif;
    letter-spacing: .04em;
  }

  .secure-badge i { color: var(--gold-dark); font-size: 0.8rem; }

  @media (max-width: 820px) {
    .login-left { display: none; }
    .login-right { padding: 32px 20px; }
  }
</style>
</head>
<body>

<div class="login-left">
  <div class="brand">
    <span class="brand-star">★</span>
    <span class="brand-texas">TEXAS</span>
    <span class="brand-name">Skill Masters</span>
    <span class="brand-sub">Admin Portal</span>
    <div class="brand-divider"></div>
    <p class="brand-tagline">
      Manage your skill game business — clients, machines, revenue, and service — all in one place.
    </p>
  </div>
  <div class="stats-row">
    <div class="stat-pill">
      <div class="stat-pill-val">TSM</div>
      <div class="stat-pill-label">Admin</div>
    </div>
    <div class="stat-pill">
      <div class="stat-pill-val">CRM</div>
      <div class="stat-pill-label">System</div>
    </div>
    <div class="stat-pill">
      <div class="stat-pill-val">v1.0</div>
      <div class="stat-pill-label">Version</div>
    </div>
  </div>
</div>

<div class="login-right">
  <div class="login-box">
    <h1>Admin <span>Sign In</span></h1>
    <p class="login-desc">Enter your credentials to access the Texas Skill Masters CRM dashboard.</p>

    <?php if ($error): ?>
    <div class="error-box">
      <i class="fa-solid fa-circle-exclamation"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">
      <div class="form-group">
        <label>Username</label>
        <div class="input-wrap">
          <i class="fa-solid fa-user"></i>
          <input type="text" name="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 placeholder="admin"
                 autocomplete="username"
                 required autofocus>
        </div>
      </div>

      <div class="form-group">
        <label>Password</label>
        <div class="input-wrap">
          <i class="fa-solid fa-lock"></i>
          <input type="password" name="password" id="pwField"
                 placeholder="••••••••••"
                 autocomplete="current-password"
                 required>
          <button type="button" class="pw-toggle" onclick="togglePw()" id="pwToggle" title="Show/hide password">
            <i class="fa-solid fa-eye" id="pwIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="login-btn">
        <i class="fa-solid fa-right-to-bracket"></i>
        Sign In to Dashboard
      </button>
    </form>

    <div class="login-footer">
      <a href="<?= BASE_URL ?>/portal/login.php" class="login-footer-link">
        <i class="fa-solid fa-arrow-left"></i> Client Portal
      </a>
      <div class="secure-badge">
        <i class="fa-solid fa-shield-halved"></i>
        Secured · TSM Internal
      </div>
    </div>
  </div>
</div>

<script>
function togglePw() {
  const f = document.getElementById('pwField');
  const i = document.getElementById('pwIcon');
  if (f.type === 'password') {
    f.type = 'text';
    i.className = 'fa-solid fa-eye-slash';
  } else {
    f.type = 'password';
    i.className = 'fa-solid fa-eye';
  }
}
</script>

</body>
</html>
