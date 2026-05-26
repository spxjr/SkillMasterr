<?php
require_once '../includes/config.php';
$pageTitle = 'Reports';
$db = getDB();

$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? 0); // 0 = full year

// Monthly breakdown
$monthlyData = $db->prepare("
    SELECT MONTH(r.entry_date) AS mo,
           MONTHNAME(r.entry_date) AS month_name,
           COALESCE(SUM(r.cash_in),0)      AS cash_in,
           COALESCE(SUM(r.cash_out),0)     AS cash_out,
           COALESCE(SUM(r.net_revenue),0)  AS net,
           COALESCE(SUM(r.tsm_share),0)    AS tsm,
           COALESCE(SUM(r.venue_share),0)  AS venue,
           COUNT(r.id) AS entries
    FROM revenue_entries r
    WHERE YEAR(r.entry_date)=?
    GROUP BY mo
    ORDER BY mo
");
$monthlyData->execute([$year]);
$monthlyData = $monthlyData->fetchAll();

$yearTotals = [
    'cash_in'=>0,'cash_out'=>0,'net'=>0,'tsm'=>0,'venue'=>0,'entries'=>0
];
foreach ($monthlyData as $m) {
    foreach ($yearTotals as $k=>$v) $yearTotals[$k] += $m[$k];
}

$maxMonth = !empty($monthlyData) ? max(array_column($monthlyData,'net')) : 1;
if ($maxMonth == 0) $maxMonth = 1;

// Client revenue summary
$clientSummary = $db->prepare("
    SELECT c.business_name, c.city, c.venue_type,
           COUNT(DISTINCT cg.id) AS machines,
           COALESCE(SUM(r.cash_in),0)     AS cash_in,
           COALESCE(SUM(r.net_revenue),0) AS net,
           COALESCE(SUM(r.tsm_share),0)   AS tsm,
           COALESCE(SUM(r.venue_share),0) AS venue,
           COUNT(r.id) AS entries
    FROM clients c
    LEFT JOIN client_games cg ON cg.client_id=c.id
    LEFT JOIN revenue_entries r ON r.client_game_id=cg.id AND YEAR(r.entry_date)=?
    " . ($month ? "AND MONTH(r.entry_date)=?" : "") . "
    GROUP BY c.id
    ORDER BY net DESC
");
$params = [$year];
if ($month) $params[] = $month;
$clientSummary->execute($params);
$clientSummary = $clientSummary->fetchAll();

// Game performance
$gameSummary = $db->prepare("
    SELECT g.game_name, g.manufacturer,
           COUNT(DISTINCT cg.client_id) AS locations,
           COALESCE(SUM(r.net_revenue),0) AS net,
           COALESCE(SUM(r.tsm_share),0)   AS tsm,
           COUNT(r.id) AS entries
    FROM games g
    LEFT JOIN client_games cg ON cg.game_id=g.id
    LEFT JOIN revenue_entries r ON r.client_game_id=cg.id AND YEAR(r.entry_date)=?
    GROUP BY g.id
    ORDER BY net DESC
    LIMIT 10
");
$gameSummary->execute([$year]);
$gameSummary = $gameSummary->fetchAll();

$maxGame = !empty($gameSummary) ? max(array_column($gameSummary,'net')) : 1;
if ($maxGame == 0) $maxGame = 1;

// Available years
$years = $db->query("SELECT DISTINCT YEAR(entry_date) AS y FROM revenue_entries ORDER BY y DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($years)) $years = [date('Y')];

require_once '../includes/header.php';
?>

<div class="page-header">
  <h1><span>REVENUE</span> REPORTS</h1>
  <form method="GET" class="btn-group">
    <select name="year" class="filter-select" onchange="this.form.submit()">
      <?php foreach ($years as $y): ?>
      <option value="<?= $y ?>" <?= $y==$year?'selected':'' ?>><?= $y ?></option>
      <?php endforeach; ?>
      <option value="<?= date('Y') ?>" <?= !in_array(date('Y'),$years)?'':'hidden' ?>><?= date('Y') ?></option>
    </select>
    <select name="month" class="filter-select" onchange="this.form.submit()">
      <option value="0" <?= !$month?'selected':'' ?>>Full Year</option>
      <?php for ($i=1;$i<=12;$i++): ?>
      <option value="<?= $i ?>" <?= $month==$i?'selected':'' ?>><?= date('F', mktime(0,0,0,$i,1)) ?></option>
      <?php endfor; ?>
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
  <div class="stat-card green">
    <div class="stat-icon green"><i class="fa-solid fa-dollar-sign"></i></div>
    <div class="stat-value"><?= formatMoney($yearTotals['net']) ?></div>
    <div class="stat-label">Net Revenue <?= $year ?></div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-star"></i></div>
    <div class="stat-value"><?= formatMoney($yearTotals['tsm']) ?></div>
    <div class="stat-label">TSM Earnings <?= $year ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fa-solid fa-handshake"></i></div>
    <div class="stat-value"><?= formatMoney($yearTotals['venue']) ?></div>
    <div class="stat-label">Venue Payouts <?= $year ?></div>
  </div>
</div>

<div class="grid-7-5 section-gap">

  <!-- Monthly Bar Chart -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-chart-column"></i> Monthly Net Revenue — <?= $year ?></div>
    </div>
    <div class="card-body">
      <?php if (empty($monthlyData)): ?>
      <div class="empty-state"><i class="fa-solid fa-chart-bar"></i><h3>No Data for <?= $year ?></h3></div>
      <?php else: ?>
      <div class="bar-chart">
        <?php foreach ($monthlyData as $m): ?>
        <div class="bar-row">
          <div class="bar-label"><?= $m['month_name'] ?></div>
          <div class="bar-track">
            <div class="bar-fill gold" data-w="0" style="width:0%" data-target="<?= round(($m['net']/$maxMonth)*100) ?>"></div>
          </div>
          <div class="bar-val"><?= formatMoney($m['net']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Game Performance -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-gamepad"></i> Game Performance</div>
    </div>
    <div class="card-body">
      <?php if (empty($gameSummary)): ?>
      <div class="empty-state"><i class="fa-solid fa-gamepad"></i><h3>No Data</h3></div>
      <?php else: ?>
      <div class="bar-chart">
        <?php foreach ($gameSummary as $g): ?>
        <div class="bar-row">
          <div class="bar-label" title="<?= sanitize($g['game_name']) ?>"><?= sanitize($g['game_name']) ?></div>
          <div class="bar-track">
            <div class="bar-fill green" data-w="0" style="width:0%" data-target="<?= round(($g['net']/$maxGame)*100) ?>"></div>
          </div>
          <div class="bar-val"><?= formatMoney($g['net']) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- MONTHLY BREAKDOWN TABLE -->
<div class="card section-gap">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-table"></i> Monthly Breakdown — <?= $year ?></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Month</th><th>Cash In</th><th>Cash Out</th><th>Net Revenue</th><th>TSM Earnings</th><th>Venue Payouts</th><th>Entries</th></tr>
      </thead>
      <tbody>
        <?php if (empty($monthlyData)): ?>
        <tr><td colspan="7" class="text-center td-muted" style="padding:30px">No data for <?= $year ?></td></tr>
        <?php else: ?>
        <?php foreach ($monthlyData as $m): ?>
        <tr>
          <td class="fw-600"><?= $m['month_name'] ?></td>
          <td><?= formatMoney($m['cash_in']) ?></td>
          <td class="money-negative">-<?= formatMoney($m['cash_out']) ?></td>
          <td class="money-positive fw-600"><?= formatMoney($m['net']) ?></td>
          <td class="text-gold fw-600"><?= formatMoney($m['tsm']) ?></td>
          <td><?= formatMoney($m['venue']) ?></td>
          <td class="td-muted text-center"><?= $m['entries'] ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="border-top:2px solid var(--border-gold);background:rgba(201,168,76,0.04)">
          <td class="fw-600 text-gold font-cond" style="letter-spacing:.05em">TOTAL <?= $year ?></td>
          <td class="fw-600"><?= formatMoney($yearTotals['cash_in']) ?></td>
          <td class="money-negative fw-600">-<?= formatMoney($yearTotals['cash_out']) ?></td>
          <td class="money-positive fw-600"><?= formatMoney($yearTotals['net']) ?></td>
          <td class="text-gold fw-600"><?= formatMoney($yearTotals['tsm']) ?></td>
          <td class="fw-600"><?= formatMoney($yearTotals['venue']) ?></td>
          <td class="td-muted text-center"><?= $yearTotals['entries'] ?></td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- CLIENT SUMMARY TABLE -->
<div class="card section-gap">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-building-user"></i> Client Revenue Summary — <?= $year ?><?= $month ? ' / '.date('F',mktime(0,0,0,$month,1)) : '' ?></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Client</th><th>Location</th><th>Type</th><th>Machines</th><th>Gross In</th><th>Net Revenue</th><th>TSM Share</th><th>Venue Share</th><th>Collections</th></tr>
      </thead>
      <tbody>
        <?php foreach ($clientSummary as $cs): ?>
        <tr>
          <td class="fw-600"><?= sanitize($cs['business_name']) ?></td>
          <td class="td-muted"><?= sanitize($cs['city']) ?></td>
          <td><span class="badge badge-blue"><?= sanitize($cs['venue_type']) ?></span></td>
          <td class="text-center"><?= $cs['machines'] ?></td>
          <td><?= formatMoney($cs['cash_in']) ?></td>
          <td class="money-positive fw-600"><?= formatMoney($cs['net']) ?></td>
          <td class="text-gold fw-600"><?= formatMoney($cs['tsm']) ?></td>
          <td><?= formatMoney($cs['venue']) ?></td>
          <td class="td-muted text-center"><?= $cs['entries'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(() => {
    document.querySelectorAll('.bar-fill[data-target]').forEach(el => {
      el.style.transition = 'width .7s ease';
      el.style.width = el.dataset.target + '%';
    });
  }, 200);
});
</script>

<?php require_once '../includes/footer.php'; ?>
