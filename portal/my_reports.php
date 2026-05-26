<?php
require_once __DIR__ . '/includes/portal_header.php';
portalRequireLogin();
$pageTitle = 'Reports';
$clientId  = $portalUser['client_id'];
$db        = getDB();
$b         = PORTAL_URL;

$year  = (int)($_GET['year'] ?? date('Y'));
$years = $db->prepare("SELECT DISTINCT YEAR(r.entry_date) AS y FROM revenue_entries r JOIN client_games cg ON cg.id=r.client_game_id WHERE cg.client_id=? ORDER BY y DESC");
$years->execute([$clientId]);
$years = $years->fetchAll(PDO::FETCH_COLUMN);
if (empty($years)) $years = [date('Y')];

// Monthly breakdown
$monthly = $db->prepare("
    SELECT MONTH(r.entry_date) AS mo,
           MONTHNAME(r.entry_date) AS month_name,
           COALESCE(SUM(r.cash_in),0)     AS cash_in,
           COALESCE(SUM(r.cash_out),0)    AS cash_out,
           COALESCE(SUM(r.net_revenue),0) AS net,
           COALESCE(SUM(r.venue_share),0) AS venue,
           COUNT(r.id) AS entries
    FROM revenue_entries r
    JOIN client_games cg ON cg.id=r.client_game_id
    WHERE cg.client_id=? AND YEAR(r.entry_date)=?
    GROUP BY mo ORDER BY mo
");
$monthly->execute([$clientId, $year]);
$monthly = $monthly->fetchAll();

$yearTotals = ['cash_in'=>0,'cash_out'=>0,'net'=>0,'venue'=>0,'entries'=>0];
foreach ($monthly as $m) foreach ($yearTotals as $k=>$v) $yearTotals[$k] += $m[$k];

$maxMonth = !empty($monthly) ? max(array_column($monthly,'net')) : 1;
if ($maxMonth == 0) $maxMonth = 1;

// Machine breakdown
$byMachine = $db->prepare("
    SELECT cg.machine_number, g.game_name,
           COALESCE(SUM(r.cash_in),0)     AS cash_in,
           COALESCE(SUM(r.net_revenue),0) AS net,
           COALESCE(SUM(r.venue_share),0) AS venue,
           COUNT(r.id) AS entries
    FROM client_games cg
    JOIN games g ON g.id=cg.game_id
    LEFT JOIN revenue_entries r ON r.client_game_id=cg.id AND YEAR(r.entry_date)=?
    WHERE cg.client_id=?
    GROUP BY cg.id
    ORDER BY venue DESC
");
$byMachine->execute([$year, $clientId]);
$byMachine = $byMachine->fetchAll();

$maxMachine = !empty($byMachine) ? max(array_column($byMachine,'venue')) : 1;
if ($maxMachine == 0) $maxMachine = 1;
?>

<div class="page-header">
  <div>
    <h1><span class="accent">Revenue</span> Reports</h1>
    <div class="page-subtitle">Annual performance overview</div>
  </div>
  <form method="GET">
    <select name="year" onchange="this.form.submit()"
      style="padding:8px 14px;border:1px solid var(--border);border-radius:var(--radius);background:var(--bg-white);color:var(--text-dark);font-family:'Barlow Condensed',sans-serif;font-size:.9rem;font-weight:700;cursor:pointer">
      <?php foreach ($years as $y): ?>
      <option value="<?= $y ?>" <?= $y==$year?'selected':'' ?>><?= $y ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<!-- YEAR TOTALS -->
<div class="stats-grid section-gap">
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-arrow-down"></i></div>
    <div class="stat-value"><?= formatMoney($yearTotals['cash_in']) ?></div>
    <div class="stat-label">Gross Cash In <?= $year ?></div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-dollar-sign"></i></div>
    <div class="stat-value"><?= formatMoney($yearTotals['net']) ?></div>
    <div class="stat-label">Net Revenue <?= $year ?></div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green"><i class="fa-solid fa-hand-holding-dollar"></i></div>
    <div class="stat-value"><?= formatMoney($yearTotals['venue']) ?></div>
    <div class="stat-label">Your Earnings <?= $year ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fa-solid fa-receipt"></i></div>
    <div class="stat-value"><?= $yearTotals['entries'] ?></div>
    <div class="stat-label">Collections <?= $year ?></div>
  </div>
</div>

<div class="grid-7-5 section-gap">
  <!-- Monthly chart -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-chart-column"></i> Monthly Breakdown — <?= $year ?></div>
    </div>
    <div class="card-body">
      <?php if (empty($monthly)): ?>
      <div class="empty-state"><i class="fa-solid fa-chart-bar"></i><h3>No Data for <?= $year ?></h3></div>
      <?php else: ?>
      <div class="bar-chart">
        <?php foreach ($monthly as $m): ?>
        <div class="bar-row">
          <div class="bar-label"><?= $m['month_name'] ?></div>
          <div class="bar-track">
            <div class="bar-fill gold" data-target="<?= round(($m['net']/$maxMonth)*100) ?>" style="width:0"></div>
          </div>
          <div class="bar-val"><?= formatMoney($m['net']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- By machine -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-gamepad"></i> Your Earnings by Machine</div>
    </div>
    <div class="card-body">
      <?php if (empty($byMachine)): ?>
      <div class="empty-state"><i class="fa-solid fa-gamepad"></i><h3>No Data</h3></div>
      <?php else: ?>
      <div class="bar-chart">
        <?php foreach ($byMachine as $bm): ?>
        <div class="bar-row">
          <div class="bar-label"><?= sanitize($bm['machine_number'] ?: $bm['game_name']) ?></div>
          <div class="bar-track">
            <div class="bar-fill green" data-target="<?= round(($bm['venue']/$maxMachine)*100) ?>" style="width:0"></div>
          </div>
          <div class="bar-val"><?= formatMoney($bm['venue']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Monthly table -->
<div class="card section-gap">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-table"></i> Month-by-Month — <?= $year ?></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Month</th><th>Cash In</th><th>Payouts</th><th>Net Revenue</th><th>Your Earnings</th><th>Collections</th></tr>
      </thead>
      <tbody>
        <?php if (empty($monthly)): ?>
        <tr><td colspan="6" class="text-center td-muted" style="padding:30px">No data for <?= $year ?></td></tr>
        <?php else: ?>
        <?php foreach ($monthly as $m): ?>
        <tr>
          <td class="fw-600"><?= $m['month_name'] ?></td>
          <td><?= formatMoney($m['cash_in']) ?></td>
          <td class="text-red">-<?= formatMoney($m['cash_out']) ?></td>
          <td class="fw-600"><?= formatMoney($m['net']) ?></td>
          <td class="text-green fw-700"><?= formatMoney($m['venue']) ?></td>
          <td class="td-muted text-center"><?= $m['entries'] ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="border-top:2px solid var(--border);background:#FAFAF8;font-weight:700">
          <td style="color:var(--gold-dark);font-family:'Barlow Condensed',sans-serif;letter-spacing:.04em">TOTAL <?= $year ?></td>
          <td><?= formatMoney($yearTotals['cash_in']) ?></td>
          <td class="text-red">-<?= formatMoney($yearTotals['cash_out']) ?></td>
          <td><?= formatMoney($yearTotals['net']) ?></td>
          <td class="text-green"><?= formatMoney($yearTotals['venue']) ?></td>
          <td class="text-center td-muted"><?= $yearTotals['entries'] ?></td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/portal_footer.php'; ?>
