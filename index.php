<?php
require_once 'includes/config.php';
$pageTitle = 'Dashboard';

$db = getDB();

// Key metrics
$totalClients    = $db->query("SELECT COUNT(*) FROM clients WHERE status='Active'")->fetchColumn();
$totalGames      = $db->query("SELECT COUNT(*) FROM client_games WHERE is_active=1")->fetchColumn();
$totalRevThisMonth = $db->query("SELECT COALESCE(SUM(net_revenue),0) FROM revenue_entries WHERE MONTH(entry_date)=MONTH(CURDATE()) AND YEAR(entry_date)=YEAR(CURDATE())")->fetchColumn();
$tsmShareMonth   = $db->query("SELECT COALESCE(SUM(tsm_share),0) FROM revenue_entries WHERE MONTH(entry_date)=MONTH(CURDATE()) AND YEAR(entry_date)=YEAR(CURDATE())")->fetchColumn();
$totalClients6   = $db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$pendingService  = $db->query("SELECT COUNT(*) FROM service_logs WHERE resolved=0")->fetchColumn();

// Top clients by revenue (all time)
$topClients = $db->query("
    SELECT c.business_name, c.city, c.venue_type,
           COALESCE(SUM(r.net_revenue),0) AS total_rev,
           COALESCE(SUM(r.tsm_share),0)  AS tsm_rev,
           COUNT(DISTINCT cg.id) AS game_count
    FROM clients c
    LEFT JOIN client_games cg ON cg.client_id = c.id
    LEFT JOIN revenue_entries r ON r.client_game_id = cg.id
    WHERE c.status='Active'
    GROUP BY c.id
    ORDER BY total_rev DESC
    LIMIT 8
")->fetchAll();

$maxRev = !empty($topClients) ? max(array_column($topClients, 'total_rev')) : 1;
if ($maxRev == 0) $maxRev = 1;

// Recent revenue entries
$recentRevenue = $db->query("
    SELECT r.entry_date, r.cash_in, r.cash_out, r.net_revenue, r.tsm_share, r.collected_by,
           c.business_name, g.game_name
    FROM revenue_entries r
    JOIN client_games cg ON cg.id = r.client_game_id
    JOIN clients c ON c.id = cg.client_id
    JOIN games g ON g.id = cg.game_id
    ORDER BY r.entry_date DESC, r.created_at DESC
    LIMIT 8
")->fetchAll();

// Recent service logs
$recentService = $db->query("
    SELECT sl.service_date, sl.service_type, sl.technician, sl.description, sl.resolved,
           c.business_name
    FROM service_logs sl
    JOIN clients c ON c.id = sl.client_id
    ORDER BY sl.service_date DESC
    LIMIT 5
")->fetchAll();

// Monthly revenue last 6 months
$monthlyTrend = $db->query("
    SELECT DATE_FORMAT(entry_date,'%b %Y') AS month_label,
           YEAR(entry_date) AS yr, MONTH(entry_date) AS mo,
           COALESCE(SUM(net_revenue),0) AS total,
           COALESCE(SUM(tsm_share),0)  AS tsm
    FROM revenue_entries
    WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY yr, mo
    ORDER BY yr, mo
")->fetchAll();

$maxMonthly = !empty($monthlyTrend) ? max(array_column($monthlyTrend, 'total')) : 1;
if ($maxMonthly == 0) $maxMonthly = 1;

require_once 'includes/header.php';
?>

<!-- STAT CARDS -->
<div class="stats-grid">
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-building-user"></i></div>
    <div class="stat-value" data-val="<?= $totalClients ?>"><?= $totalClients ?></div>
    <div class="stat-label">Active Clients</div>
    <div class="stat-sub"><i class="fa-solid fa-circle-check"></i> <?= $totalClients6 ?> total accounts</div>
  </div>

  <div class="stat-card green">
    <div class="stat-icon green"><i class="fa-solid fa-gamepad"></i></div>
    <div class="stat-value" data-val="<?= $totalGames ?>"><?= $totalGames ?></div>
    <div class="stat-label">Active Machines</div>
    <div class="stat-sub"><i class="fa-solid fa-map-pin"></i> Across all locations</div>
  </div>

  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-dollar-sign"></i></div>
    <div class="stat-value" data-val="$<?= number_format($totalRevThisMonth,2) ?>">$<?= number_format($totalRevThisMonth,2) ?></div>
    <div class="stat-label">Gross Revenue (This Month)</div>
    <div class="stat-sub"><i class="fa-solid fa-chart-line"></i> Net after payouts</div>
  </div>

  <div class="stat-card green">
    <div class="stat-icon green"><i class="fa-solid fa-star"></i></div>
    <div class="stat-value" data-val="$<?= number_format($tsmShareMonth,2) ?>">$<?= number_format($tsmShareMonth,2) ?></div>
    <div class="stat-label">TSM Earnings (This Month)</div>
    <div class="stat-sub"><i class="fa-solid fa-arrow-trend-up"></i> Company share</div>
  </div>

  <?php if ($pendingService > 0): ?>
  <div class="stat-card red">
    <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div class="stat-value" data-val="<?= $pendingService ?>"><?= $pendingService ?></div>
    <div class="stat-label">Open Service Tickets</div>
    <div class="stat-sub"><i class="fa-solid fa-clock"></i> Needs attention</div>
  </div>
  <?php endif; ?>
</div>

<!-- CHARTS ROW -->
<div class="grid-7-5 section-gap">

  <!-- Top Clients Bar Chart -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-ranking-star"></i> Top Clients by Revenue</div>
      <a href="<?= BASE_URL ?>/pages/clients.php" class="btn btn-outline btn-xs">View All</a>
    </div>
    <div class="card-body">
      <?php if (empty($topClients)): ?>
        <div class="empty-state"><i class="fa-solid fa-chart-bar"></i><h3>No Revenue Data</h3><p>Add revenue entries to see rankings.</p></div>
      <?php else: ?>
      <div class="bar-chart">
        <?php foreach ($topClients as $tc): ?>
        <div class="bar-row">
          <div class="bar-label" title="<?= sanitize($tc['business_name']) ?>"><?= sanitize($tc['business_name']) ?></div>
          <div class="bar-track">
            <div class="bar-fill gold" data-w="0" style="width:0%" data-target="<?= round(($tc['total_rev']/$maxRev)*100) ?>"></div>
          </div>
          <div class="bar-val"><?= formatMoney($tc['total_rev']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Monthly Trend -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-chart-column"></i> Monthly Trend</div>
    </div>
    <div class="card-body">
      <?php if (empty($monthlyTrend)): ?>
        <div class="empty-state"><i class="fa-solid fa-chart-simple"></i><h3>No Data Yet</h3></div>
      <?php else: ?>
      <div class="bar-chart">
        <?php foreach ($monthlyTrend as $mt): ?>
        <div class="bar-row">
          <div class="bar-label"><?= sanitize($mt['month_label']) ?></div>
          <div class="bar-track">
            <div class="bar-fill green" data-w="0" style="width:0%" data-target="<?= round(($mt['total']/$maxMonthly)*100) ?>"></div>
          </div>
          <div class="bar-val"><?= formatMoney($mt['total']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <hr class="gold-line">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
        <?php
        $grandTotal = array_sum(array_column($monthlyTrend, 'total'));
        $grandTSM   = array_sum(array_column($monthlyTrend, 'tsm'));
        ?>
        <div>
          <div class="td-muted fs-sm">6-Month Gross</div>
          <div class="money-positive fw-600 font-cond" style="font-size:1.05rem"><?= formatMoney($grandTotal) ?></div>
        </div>
        <div>
          <div class="td-muted fs-sm">TSM Earnings</div>
          <div class="money-positive fw-600 font-cond" style="font-size:1.05rem"><?= formatMoney($grandTSM) ?></div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- TABLES ROW -->
<div class="grid-2 section-gap">

  <!-- Recent Collections -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-money-bill-transfer"></i> Recent Collections</div>
      <a href="<?= BASE_URL ?>/pages/revenue.php" class="btn btn-outline btn-xs">All Entries</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Client</th>
            <th>Game</th>
            <th>Net Rev</th>
            <th>TSM</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentRevenue)): ?>
          <tr><td colspan="5" class="text-center td-muted" style="padding:30px">No entries yet</td></tr>
          <?php else: ?>
          <?php foreach ($recentRevenue as $r): ?>
          <tr>
            <td class="td-muted"><?= formatDate($r['entry_date']) ?></td>
            <td><?= sanitize($r['business_name']) ?></td>
            <td class="td-muted fs-sm"><?= sanitize($r['game_name']) ?></td>
            <td class="money-positive"><?= formatMoney($r['net_revenue']) ?></td>
            <td class="text-gold fw-600"><?= formatMoney($r['tsm_share']) ?></td>
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
      <a href="<?= BASE_URL ?>/pages/service.php" class="btn btn-outline btn-xs">All Logs</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Client</th>
            <th>Type</th>
            <th>Tech</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentService)): ?>
          <tr><td colspan="5" class="text-center td-muted" style="padding:30px">No service logs</td></tr>
          <?php else: ?>
          <?php foreach ($recentService as $s): ?>
          <tr>
            <td class="td-muted"><?= formatDate($s['service_date']) ?></td>
            <td><?= sanitize($s['business_name']) ?></td>
            <td class="td-muted fs-sm"><?= sanitize($s['service_type']) ?></td>
            <td class="td-muted"><?= sanitize($s['technician']) ?></td>
            <td>
              <?php if ($s['resolved']): ?>
                <span class="badge badge-green">Done</span>
              <?php else: ?>
                <span class="badge badge-red">Open</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
// Animate bar chart fills on load
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    document.querySelectorAll('.bar-fill[data-target]').forEach(el => {
      el.style.transition = 'width .7s ease';
      el.style.width = el.dataset.target + '%';
    });
  }, 200);
});
</script>

<?php require_once 'includes/footer.php'; ?>
