<?php
session_start();
require_once __DIR__ . '/portal_config.php';

$portalUser  = portalGetUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash       = getFlash();
$b           = PORTAL_URL;

if (!$portalUser && $currentPage !== 'login') {
    redirect(PORTAL_URL . '/login.php');
}

$initials = '';
if ($portalUser) {
    $parts    = explode(' ', trim($portalUser['full_name']));
    $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TSM Client Portal <?= isset($pageTitle) ? '— '.htmlspecialchars($pageTitle) : '' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;600;700&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/portal/assets/css/portal.css">
</head>
<body>

<?php if ($portalUser): ?>
<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-lockup">
      <div class="logo-star">★</div>
      <div class="logo-text">
        <span class="logo-top">Texas</span>
        <span class="logo-mid">Skill Masters</span>
        <span class="logo-bot">Client Portal</span>
      </div>
    </div>
    <div class="portal-badge"><i class="fa-solid fa-lock-open"></i> Venue Access</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">My Account</div>
    <a href="<?= $b ?>/dashboard.php" class="nav-item <?= $currentPage==='dashboard'?'active':'' ?>">
      <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
    </a>
    <a href="<?= $b ?>/my_machines.php" class="nav-item <?= $currentPage==='my_machines'?'active':'' ?>">
      <i class="fa-solid fa-gamepad"></i><span>My Machines</span>
    </a>

    <div class="nav-section-label">Financials</div>
    <a href="<?= $b ?>/my_revenue.php" class="nav-item <?= $currentPage==='my_revenue'?'active':'' ?>">
      <i class="fa-solid fa-dollar-sign"></i><span>Revenue</span>
    </a>
    <a href="<?= $b ?>/my_reports.php" class="nav-item <?= $currentPage==='my_reports'?'active':'' ?>">
      <i class="fa-solid fa-chart-bar"></i><span>Reports</span>
    </a>

    <div class="nav-section-label">Support</div>
    <a href="<?= $b ?>/service_history.php" class="nav-item <?= $currentPage==='service_history'?'active':'' ?>">
      <i class="fa-solid fa-screwdriver-wrench"></i><span>Service History</span>
    </a>
    <a href="<?= $b ?>/messages.php" class="nav-item <?= $currentPage==='messages'?'active':'' ?>">
      <i class="fa-solid fa-envelope"></i><span>Messages</span>
    </a>

    <div class="nav-section-label">Settings</div>
    <a href="<?= $b ?>/my_account.php" class="nav-item <?= $currentPage==='my_account'?'active':'' ?>">
      <i class="fa-solid fa-user-gear"></i><span>My Account</span>
    </a>
  </nav>

  <div class="sidebar-user">
    <div class="user-card">
      <div class="user-avatar"><?= $initials ?></div>
      <div class="user-info">
        <div class="user-name"><?= sanitize($portalUser['full_name']) ?></div>
        <div class="user-role"><?= ucfirst($portalUser['role']) ?> · <?= sanitize($portalUser['business_name']) ?></div>
      </div>
    </div>
    <a href="<?= $b ?>/logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
  </div>
</aside>

<!-- TOPBAR -->
<div class="topbar">
  <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
    <i class="fa-solid fa-bars"></i>
  </button>
  <div class="topbar-venue">
    <div class="topbar-venue-name"><?= sanitize($portalUser['business_name']) ?></div>
    <div class="topbar-venue-sub">
      <span class="status-dot"></span>
      <?= sanitize($portalUser['city']) ?>, <?= sanitize($portalUser['state']) ?> &nbsp;·&nbsp;
      <?= sanitize($portalUser['venue_type']) ?>
    </div>
  </div>
  <div class="topbar-right">
    <div class="topbar-date">
      <i class="fa-regular fa-calendar" style="color:var(--gold-dark)"></i>
      <?= date('M j, Y') ?>
    </div>
  </div>
</div>

<!-- MAIN -->
<main class="main-content">

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>" id="flashAlert">
  <i class="fa-solid fa-<?= $flash['type']==='success'?'circle-check':'circle-exclamation' ?>"></i>
  <?= sanitize($flash['message']) ?>
  <button onclick="this.parentElement.remove()" class="alert-close">×</button>
</div>
<?php endif; ?>

<?php endif; // portalUser ?>
