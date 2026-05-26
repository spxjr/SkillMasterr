<?php
session_start();
require_once __DIR__ . '/sales_config.php';
salesRequireLogin();

$rep         = salesGetRep();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$flash       = getFlash();
$b           = SALES_URL;

// Initials
$parts    = explode(' ', trim($rep['name']));
$initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));

// Greeting
$hour = (int)date('G');
$greet = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

// Unread messages from admin
$db = getDB();
$unreadMsgs = $db->prepare("SELECT COUNT(*) FROM sales_messages WHERE rep_id=? AND direction='admin_to_rep' AND is_read=0");
$unreadMsgs->execute([$rep['id']]);
$unreadMsgs = $unreadMsgs->fetchColumn();

// Follow-ups due today
$followUpsDue = $db->prepare("SELECT COUNT(*) FROM prospects WHERE rep_id=? AND follow_up_date<=CURDATE() AND status NOT IN ('Converted','Not Interested','No Response')");
$followUpsDue->execute([$rep['id']]);
$followUpsDue = $followUpsDue->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TSM Sales <?= isset($pageTitle) ? '— '.htmlspecialchars($pageTitle) : '' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@300;400;600;700&family=Barlow+Condensed:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/sales/assets/css/sales.css">
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-row">
      <div class="logo-star">★</div>
      <div class="logo-text">
        <span class="logo-brand">Skill Masters</span>
        <span class="logo-sub">Sales Portal</span>
      </div>
    </div>
    <div class="rep-chip"><i class="fa-solid fa-briefcase"></i> Sales Rep</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">My Dashboard</div>
    <a href="<?= $b ?>/dashboard.php" class="nav-item <?= $currentPage==='dashboard'?'active':'' ?>">
      <i class="fa-solid fa-gauge-high"></i><span>Dashboard</span>
    </a>
    <a href="<?= $b ?>/leaderboard.php" class="nav-item <?= $currentPage==='leaderboard'?'active':'' ?>">
      <i class="fa-solid fa-trophy"></i><span>Leaderboard</span>
    </a>

    <div class="nav-section-label">My Leads</div>
    <a href="<?= $b ?>/my_prospects.php" class="nav-item <?= $currentPage==='my_prospects'?'active':'' ?>">
      <i class="fa-solid fa-bullseye"></i>
      <span>My Prospects</span>
      <?php if ($followUpsDue > 0): ?>
      <span style="margin-left:auto;background:var(--red);color:#fff;border-radius:12px;padding:1px 7px;font-size:.62rem;font-weight:700"><?= $followUpsDue ?></span>
      <?php endif; ?>
    </a>
    <a href="<?= $b ?>/my_prospects.php?view=pipeline" class="nav-item">
      <i class="fa-solid fa-columns"></i><span>Pipeline</span>
    </a>
    <a href="<?= $b ?>/add_prospect.php" class="nav-item <?= $currentPage==='add_prospect'?'active':'' ?>">
      <i class="fa-solid fa-plus-circle"></i><span>Add Lead</span>
    </a>

    <div class="nav-section-label">My Clients</div>
    <a href="<?= $b ?>/my_clients.php" class="nav-item <?= $currentPage==='my_clients'?'active':'' ?>">
      <i class="fa-solid fa-building-user"></i><span>My Clients</span>
    </a>

    <div class="nav-section-label">Communication</div>
    <a href="<?= $b ?>/messages.php" class="nav-item <?= $currentPage==='messages'?'active':'' ?>">
      <i class="fa-solid fa-envelope"></i>
      <span>Messages</span>
      <?php if ($unreadMsgs > 0): ?>
      <span style="margin-left:auto;background:var(--blue);color:#fff;border-radius:12px;padding:1px 7px;font-size:.62rem;font-weight:700"><?= $unreadMsgs ?></span>
      <?php endif; ?>
    </a>

    <div class="nav-section-label">Account</div>
    <a href="<?= $b ?>/my_account.php" class="nav-item <?= $currentPage==='my_account'?'active':'' ?>">
      <i class="fa-solid fa-user-gear"></i><span>My Account</span>
    </a>
  </nav>

  <div class="sidebar-rep">
    <div class="rep-card">
      <div class="rep-avatar"><?= $initials ?></div>
      <div>
        <div class="rep-name"><?= sanitize($rep['name']) ?></div>
        <div class="rep-role">Sales Rep</div>
      </div>
    </div>
    <div class="rep-actions">
      <a href="<?= $b ?>/logout.php" class="rep-action-btn">
        <i class="fa-solid fa-right-from-bracket"></i> Sign Out
      </a>
    </div>
  </div>
</aside>

<!-- TOPBAR -->
<div class="topbar">
  <button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
    <i class="fa-solid fa-bars"></i>
  </button>
  <div class="topbar-greeting">
    <div class="topbar-greeting-name"><?= $greet ?>, <?= sanitize($rep['firstname']) ?>!</div>
    <div class="topbar-greeting-sub">
      <i class="fa-solid fa-map-pin" style="color:var(--gold-dark)"></i>
      <?= sanitize($rep['territory']) ?: 'Sales Rep' ?>
    </div>
  </div>
  <div class="topbar-right">
    <?php if ($followUpsDue > 0): ?>
    <a href="<?= $b ?>/my_prospects.php?filter=followup" style="display:flex;align-items:center;gap:6px;background:var(--red-pale);border:1px solid rgba(220,38,38,.2);border-radius:var(--radius);padding:6px 12px;text-decoration:none;color:var(--red);font-family:'Barlow Condensed',sans-serif;font-size:.78rem;font-weight:700">
      <i class="fa-solid fa-bell"></i> <?= $followUpsDue ?> Follow-up<?= $followUpsDue!=1?'s':'' ?>
    </a>
    <?php endif; ?>
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
