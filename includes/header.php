<?php
session_start();
require_once __DIR__ . '/config.php';
adminRequireLogin();
$admin       = adminGetUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash       = getFlash();
$b           = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= APP_NAME ?> <?= isset($pageTitle) ? '— '.$pageTitle : '' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= $b ?>/assets/css/style.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-star">★</div>
    <div class="logo-text">
      <span class="logo-top">TEXAS</span>
      <span class="logo-mid">SKILL</span>
      <span class="logo-bot">MASTERS</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">OVERVIEW</div>
    <a href="<?= $b ?>/index.php" class="nav-item <?= $currentPage==='index'?'active':'' ?>">
      <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
    </a>

    <div class="nav-section-label">OPERATIONS</div>
    <a href="<?= $b ?>/pages/clients.php" class="nav-item <?= $currentPage==='clients'?'active':'' ?>">
      <i class="fa-solid fa-building-user"></i><span>Clients</span>
    </a>
    <a href="<?= $b ?>/pages/prospects.php" class="nav-item <?= $currentPage==='prospects'||$currentPage==='prospect_detail'?'active':'' ?>">
      <i class="fa-solid fa-bullseye"></i><span>Prospects</span>
    </a>
    <a href="<?= $b ?>/pages/games.php" class="nav-item <?= $currentPage==='games'?'active':'' ?>">
      <i class="fa-solid fa-gamepad"></i><span>Games</span>
    </a>
    <a href="<?= $b ?>/pages/placements.php" class="nav-item <?= $currentPage==='placements'?'active':'' ?>">
      <i class="fa-solid fa-map-pin"></i><span>Placements</span>
    </a>
    <a href="<?= $b ?>/pages/revenue.php" class="nav-item <?= $currentPage==='revenue'?'active':'' ?>">
      <i class="fa-solid fa-dollar-sign"></i><span>Revenue</span>
    </a>
    <a href="<?= $b ?>/pages/service.php" class="nav-item <?= $currentPage==='service'?'active':'' ?>">
      <i class="fa-solid fa-screwdriver-wrench"></i><span>Service Logs</span>
    </a>

    <div class="nav-section-label">ANALYTICS</div>
    <a href="<?= $b ?>/pages/reports.php" class="nav-item <?= $currentPage==='reports'?'active':'' ?>">
      <i class="fa-solid fa-chart-bar"></i><span>Reports</span>
    </a>

    <div class="nav-section-label">CLIENT PORTAL</div>
    <a href="<?= $b ?>/pages/portal_users.php" class="nav-item <?= $currentPage==='portal_users'?'active':'' ?>">
      <i class="fa-solid fa-users-gear"></i><span>Portal Users</span>
    </a>
    <a href="<?= $b ?>/portal/login.php" target="_blank" class="nav-item">
      <i class="fa-solid fa-arrow-up-right-from-square"></i><span>View Portal</span>
    </a>

    <div class="nav-section-label">SALES TEAM</div>
    <a href="<?= $b ?>/pages/sales_admin.php" class="nav-item <?= $currentPage==='sales_admin'?'active':'' ?>">
      <i class="fa-solid fa-briefcase"></i><span>Sales Reps</span>
    </a>
    <a href="<?= $b ?>/sales/login.php" target="_blank" class="nav-item">
      <i class="fa-solid fa-arrow-up-right-from-square"></i><span>View Sales Portal</span>
    </a>
  </nav>

  <!-- Admin user block at bottom of sidebar -->
  <div class="sidebar-footer" style="flex-direction:column;align-items:stretch;gap:10px;padding:14px 16px">
    <div style="display:flex;align-items:center;gap:10px">
      <div style="width:34px;height:34px;background:linear-gradient(135deg,var(--gold-dark),var(--gold));border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Barlow Condensed',sans-serif;font-size:0.75rem;font-weight:700;color:#0D0F14;flex-shrink:0">
        <?= strtoupper(substr($admin['name'],0,1)) ?>
      </div>
      <div style="min-width:0;flex:1">
        <div style="font-size:0.8rem;color:var(--text-white);font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($admin['name']) ?></div>
        <div style="font-size:0.65rem;color:var(--text-dim);font-family:'Barlow Condensed',sans-serif;letter-spacing:.04em"><?= ucfirst($admin['role']) ?></div>
      </div>
    </div>
    <div style="display:flex;gap:6px">
      <a href="<?= $b ?>/pages/admin_settings.php" style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:6px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:var(--radius);color:var(--text-muted);text-decoration:none;font-family:'Barlow Condensed',sans-serif;font-size:0.72rem;letter-spacing:.04em;transition:all .15s" onmouseover="this.style.background='rgba(201,168,76,0.1)';this.style.color='var(--gold)'" onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='var(--text-muted)'">
        <i class="fa-solid fa-gear"></i> Settings
      </a>
      <a href="<?= $b ?>/logout.php" style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:6px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:var(--radius);color:var(--text-muted);text-decoration:none;font-family:'Barlow Condensed',sans-serif;font-size:0.72rem;letter-spacing:.04em;transition:all .15s" onmouseover="this.style.background='rgba(192,57,43,0.15)';this.style.color='#E74C3C'" onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='var(--text-muted)'">
        <i class="fa-solid fa-right-from-bracket"></i> Sign Out
      </a>
    </div>
  </div>
</aside>

<!-- TOP BAR -->
<div class="topbar">
  <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
    <i class="fa-solid fa-bars"></i>
  </button>
  <div class="topbar-title"><?= $pageTitle ?? 'Dashboard' ?></div>
  <div class="topbar-right">
    <div class="topbar-date">
      <i class="fa-regular fa-calendar"></i>
      <?= date('l, F j, Y') ?>
    </div>
    <div class="admin-avatar"><?= strtoupper(substr($admin['name'],0,1)) ?></div>
  </div>
</div>

<!-- MAIN CONTENT WRAPPER -->
<main class="main-content">

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?>" id="flashAlert">
  <i class="fa-solid fa-<?= $flash['type']==='success'?'circle-check':'circle-exclamation' ?>"></i>
  <?= sanitize($flash['message']) ?>
  <button onclick="this.parentElement.remove()" class="alert-close">×</button>
</div>
<?php endif; ?>
