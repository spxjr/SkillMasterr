<?php
require_once __DIR__ . '/includes/portal_header.php';
portalRequireLogin();
$pageTitle = 'Dashboard';
$clientId  = $portalUser['client_id'];
$db        = getDB();
$b         = PORTAL_URL;

// Active machines
$machines = $db->prepare("
    SELECT cg.*, g.game_name, g.manufacturer, g.model,
           COALESCE(SUM(r.net_revenue),0) AS total_rev,
           COALESCE(SUM(r.tsm_share),0)  AS tsm_rev,
           COALESCE(SUM(r.venue_share),0) AS venue_rev,
           COUNT(r.id) AS collections,
           MAX(r.entry_date) AS last_collection
    FROM client_games cg
    JOIN games g ON g.id=cg.game_id
    LEFT JOIN revenue_entries r ON r.client_game_id=cg.id
    WHERE cg.client_id=? AND cg.is_active=1
    GROUP BY cg.id
    ORDER BY total_rev DESC
");
$machines->execute([$clientId]);
$machines = $machines->fetchAll();

// Revenue totals
$totals = $db->prepare("
    SELECT
        COALESCE(SUM(r.cash_in),0)      AS gross_in,
        COALESCE(SUM(r.net_revenue),0)  AS net,
        COALESCE(SUM(r.tsm_share),0)    AS tsm,
        COALESCE(SUM(r.venue_share),0)  AS venue,
        COUNT(r.id) AS entries
    FROM revenue_entries r
    JOIN client_games cg ON cg.id=r.client_game_id
    WHERE cg.client_id=?
");
$totals->execute([$clientId]);
$totals = $totals->fetch();

// This month
$thisMonth = $db->prepare("
    SELECT COALESCE(SUM(r.net_revenue),0) AS net,
           COALESCE(SUM(r.venue_share),0) AS venue
    FROM revenue_entries r
    JOIN client_games cg ON cg.id=r.client_game_id
    WHERE cg.client_id=? AND MONTH(r.entry_date)=MONTH(CURDATE()) AND YEAR(r.entry_date)=YEAR(CURDATE())
");
$thisMonth->execute([$clientId]);
$thisMonth = $thisMonth->fetch();

// Last month
$lastMonth = $db->prepare("
    SELECT COALESCE(SUM(r.venue_share),0) AS venue
    FROM revenue_entries r
    JOIN client_games cg ON cg.id=r.client_game_id
    WHERE cg.client_id=? AND MONTH(r.entry_date)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))
      AND YEAR(r.entry_date)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))
");
$lastMonth->execute([$clientId]);
$lastMonth = $lastMonth->fetch();

// Recent collections
$recent = $db->prepare("
    SELECT r.entry_date, r.net_revenue, r.venue_share, r.collected_by,
           g.game_name, cg.machine_number
    FROM revenue_entries r
    JOIN client_games cg ON cg.id=r.client_game_id
    JOIN games g ON g.id=cg.game_id
    WHERE cg.client_id=?
    ORDER BY r.entry_date DESC, r.created_at DESC
    LIMIT 6
");
$recent->execute([$clientId]);
$recent = $recent->fetchAll();

// Recent service
$recentService = $db->prepare("
    SELECT sl.service_date, sl.service_type, sl.description, sl.resolved,
           g.game_name, cg.machine_number
    FROM service_logs sl
    LEFT JOIN client_games cg ON cg.id=sl.client_game_id
    LEFT JOIN games g ON g.id=cg.game_id
    WHERE sl.client_id=?
    ORDER BY sl.service_date DESC
    LIMIT 4
");
$recentService->execute([$clientId]);
$recentService = $recentService->fetchAll();

// Monthly trend (6 months)
$monthly = $db->prepare("
    SELECT DATE_FORMAT(r.entry_date,'%b') AS mo,
           YEAR(r.entry_date) AS yr, MONTH(r.entry_date) AS mon,
           COALESCE(SUM(r.net_revenue),0)  AS net,
           COALESCE(SUM(r.venue_share),0)  AS venue
    FROM revenue_entries r
    JOIN client_games cg ON cg.id=r.client_game_id
    WHERE cg.client_id=? AND r.entry_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY yr, mon
    ORDER BY yr, mon
");
$monthly->execute([$clientId]);
$monthly = $monthly->fetchAll();
$maxMonthly = !empty($monthly) ? max(array_column($monthly,'net')) : 1;
if ($maxMonthly == 0) $maxMonthly = 1;

$maxMachine = !empty($machines) ? max(array_column($machines,'venue_rev')) : 1;
if ($maxMachine == 0) $maxMachine = 1;

// Unread messages
$unread = $db->prepare("SELECT COUNT(*) FROM portal_messages WHERE client_id=? AND reply_body IS NOT NULL AND is_read=0");
$unread->execute([$clientId]);
$unreadCount = $unread->fetchColumn();
?>

<div class="page-header">
  <div>
    <h1><span class="accent">Good <?= date('H')<12?'Morning':(date('H')<17?'Afternoon':'Evening') ?>,</span> <?= sanitize(explode(' ',$portalUser['full_name'])[0]) ?></h1>
    <div class="page-subtitle">Here's how your machines are performing — <?= date('F Y') ?></div>
  </div>
  <?php if ($unreadCount > 0): ?>
  <a href="<?= $b ?>/messages.php" class="btn btn-primary">
    <i class="fa-solid fa-envelope"></i> <?= $unreadCount ?> New Reply
  </a>
  <?php endif; ?>
</div>

<!-- STATS -->
<div class="stats-grid section-gap">
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-gamepad"></i></div>
    <div class="stat-value"><?= count($machines) ?></div>
    <div class="stat-label">Active Machines</div>
    <div class="stat-change neutral"><i class="fa-solid fa-map-pin"></i> At your location</div>
  </div>

  <div class="stat-card green">
    <div class="stat-icon green"><i class="fa-solid fa-hand-holding-dollar"></i></div>
    <div class="stat-value"><?= formatMoney($thisMonth['venue']) ?></div>
    <div class="stat-label">Your Share — This Month</div>
    <?php if ($lastMonth['venue'] > 0): ?>
    <div class="stat-change up"><i class="fa-solid fa-arrow-trend-up"></i> <?= formatMoney($lastMonth['venue']) ?> last month</div>
    <?php endif; ?>
  </div>

  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fa-solid fa-dollar-sign"></i></div>
    <div class="stat-value"><?= formatMoney($totals['venue']) ?></div>
    <div class="stat-label">Total Earnings (All Time)</div>
    <div class="stat-change neutral"><i class="fa-solid fa-receipt"></i> <?= $totals['entries'] ?> collections</div>
  </div>

  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-chart-line"></i></div>
    <div class="stat-value"><?= formatMoney($totals['net']) ?></div>
    <div class="stat-label">Total Net Revenue (All Time)</div>
    <div class="stat-change neutral"><i class="fa-solid fa-coins"></i> Gross: <?= formatMoney($totals['gross_in']) ?></div>
  </div>
</div>

<div class="grid-7-5 section-gap">

  <!-- Monthly Revenue Chart -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-chart-column"></i> Monthly Revenue Trend</div>
      <a href="<?= $b ?>/my_reports.php" class="btn btn-ghost btn-xs">Full Report</a>
    </div>
    <div class="card-body">
      <?php if (empty($monthly)): ?>
        <div class="empty-state"><i class="fa-solid fa-chart-simple"></i><h3>No Data Yet</h3><p>Revenue will appear once collections are logged.</p></div>
      <?php else: ?>
      <div class="bar-chart">
        <?php foreach ($monthly as $m): ?>
        <div class="bar-row">
          <div class="bar-label"><?= $m['mo'] ?></div>
          <div class="bar-track">
            <div class="bar-fill gold" data-target="<?= round(($m['net']/$maxMonthly)*100) ?>" style="width:0"></div>
          </div>
          <div class="bar-val"><?= formatMoney($m['net']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <hr class="divider">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:4px">
        <div>
          <div class="fs-xs text-muted">6-Month Net Total</div>
          <div class="fw-700 font-cond" style="font-size:1.05rem;color:var(--text-dark)"><?= formatMoney(array_sum(array_column($monthly,'net'))) ?></div>
        </div>
        <div>
          <div class="fs-xs text-muted">Your Share (6 Mo.)</div>
          <div class="fw-700 font-cond text-green" style="font-size:1.05rem"><?= formatMoney(array_sum(array_column($monthly,'venue'))) ?></div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Machine Performance -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-gamepad"></i> Machine Earnings</div>
      <a href="<?= $b ?>/my_machines.php" class="btn btn-ghost btn-xs">Details</a>
    </div>
    <div class="card-body">
      <?php if (empty($machines)): ?>
        <div class="empty-state"><i class="fa-solid fa-gamepad"></i><h3>No Machines</h3></div>
      <?php else: ?>
      <div class="bar-chart">
        <?php foreach ($machines as $mc): ?>
        <div class="bar-row">
          <div class="bar-label" title="<?= sanitize($mc['game_name']) ?>"><?= sanitize($mc['machine_number'] ?: $mc['game_name']) ?></div>
          <div class="bar-track">
            <div class="bar-fill green" data-target="<?= round(($mc['venue_rev']/$maxMachine)*100) ?>" style="width:0"></div>
          </div>
          <div class="bar-val"><?= formatMoney($mc['venue_rev']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- BOTTOM ROW -->
<div class="grid-2 section-gap">

  <!-- Recent Collections -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-money-bill-transfer"></i> Recent Collections</div>
      <a href="<?= $b ?>/my_revenue.php" class="btn btn-ghost btn-xs">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Date</th><th>Machine</th><th>Net Revenue</th><th>Your Share</th></tr>
        </thead>
        <tbody>
          <?php if (empty($recent)): ?>
          <tr><td colspan="4"><div class="empty-state" style="padding:24px"><i class="fa-solid fa-receipt"></i><h3>No Collections Yet</h3></div></td></tr>
          <?php else: ?>
          <?php foreach ($recent as $r): ?>
          <tr>
            <td class="td-muted"><?= formatDate($r['entry_date']) ?></td>
            <td class="fs-sm"><?= sanitize($r['machine_number'] ?: $r['game_name']) ?></td>
            <td class="fw-600"><?= formatMoney($r['net_revenue']) ?></td>
            <td class="text-green fw-700"><?= formatMoney($r['venue_share']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Service -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-screwdriver-wrench"></i> Recent Service</div>
      <a href="<?= $b ?>/service_history.php" class="btn btn-ghost btn-xs">View All</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Date</th><th>Type</th><th>Machine</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (empty($recentService)): ?>
          <tr><td colspan="4"><div class="empty-state" style="padding:24px"><i class="fa-solid fa-wrench"></i><h3>No Service Logs</h3></div></td></tr>
          <?php else: ?>
          <?php foreach ($recentService as $s): ?>
          <tr>
            <td class="td-muted"><?= formatDate($s['service_date']) ?></td>
            <td class="fs-sm"><span class="badge badge-blue"><?= sanitize($s['service_type']) ?></span></td>
            <td class="td-muted fs-sm"><?= sanitize($s['machine_number'] ?: '—') ?></td>
            <td><?= $s['resolved'] ? '<span class="badge badge-green">Done</span>' : '<span class="badge badge-gold">Open</span>' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/portal_footer.php'; ?>
