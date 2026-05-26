<?php
require_once __DIR__ . '/includes/portal_header.php';
portalRequireLogin();
$pageTitle = 'Revenue';
$clientId  = $portalUser['client_id'];
$db        = getDB();
$b         = PORTAL_URL;

// Filters
$monthFilter = $_GET['month'] ?? '';
$machineFilter = $_GET['machine'] ?? '';

$where  = ['cg.client_id=?'];
$params = [$clientId];

if ($monthFilter) {
    $where[]  = 'YEAR(r.entry_date)=? AND MONTH(r.entry_date)=?';
    $params[] = substr($monthFilter,0,4);
    $params[] = substr($monthFilter,5,2);
}
if ($machineFilter) {
    $where[]  = 'cg.id=?';
    $params[] = (int)$machineFilter;
}

$entries = $db->prepare("
    SELECT r.entry_date, r.cash_in, r.cash_out, r.net_revenue,
           r.tsm_share, r.venue_share, r.collected_by, r.notes,
           g.game_name, cg.machine_number, cg.revenue_split
    FROM revenue_entries r
    JOIN client_games cg ON cg.id=r.client_game_id
    JOIN games g ON g.id=cg.game_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY r.entry_date DESC, r.created_at DESC
");
$entries->execute($params);
$entries = $entries->fetchAll();

$grossNet   = array_sum(array_column($entries,'net_revenue'));
$grossVenue = array_sum(array_column($entries,'venue_share'));
$grossIn    = array_sum(array_column($entries,'cash_in'));
$grossOut   = array_sum(array_column($entries,'cash_out'));

// My machines for filter
$myMachines = $db->prepare("
    SELECT cg.id, cg.machine_number, g.game_name
    FROM client_games cg JOIN games g ON g.id=cg.game_id
    WHERE cg.client_id=? ORDER BY cg.machine_number
");
$myMachines->execute([$clientId]);
$myMachines = $myMachines->fetchAll();
?>

<div class="page-header">
  <div>
    <h1><span class="accent">Revenue</span> History</h1>
    <div class="page-subtitle">All collections logged at your location</div>
  </div>
</div>

<!-- TOTALS -->
<div class="stats-grid section-gap">
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-arrow-down-to-line"></i></div>
    <div class="stat-value"><?= formatMoney($grossIn) ?></div>
    <div class="stat-label">Gross Cash In</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-dollar-sign"></i></div>
    <div class="stat-value"><?= formatMoney($grossNet) ?></div>
    <div class="stat-label">Net Revenue</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green"><i class="fa-solid fa-hand-holding-dollar"></i></div>
    <div class="stat-value"><?= formatMoney($grossVenue) ?></div>
    <div class="stat-label">Your Earnings</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fa-solid fa-receipt"></i></div>
    <div class="stat-value"><?= count($entries) ?></div>
    <div class="stat-label">Collections</div>
  </div>
</div>

<!-- FILTERS -->
<form method="GET" style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
  <input type="month" name="month" value="<?= htmlspecialchars($monthFilter) ?>"
         style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);background:var(--bg-white);color:var(--text-dark);font-family:'Barlow',sans-serif;font-size:.85rem">
  <select name="machine" style="padding:8px 12px;border:1px solid var(--border);border-radius:var(--radius);background:var(--bg-white);color:var(--text-dark);font-family:'Barlow',sans-serif;font-size:.85rem">
    <option value="">All Machines</option>
    <?php foreach ($myMachines as $mm): ?>
    <option value="<?= $mm['id'] ?>" <?= $machineFilter==$mm['id']?'selected':'' ?>><?= sanitize($mm['machine_number'] ?: $mm['game_name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
  <a href="<?= $b ?>/my_revenue.php" class="btn btn-ghost btn-sm">Reset</a>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-money-bill-transfer"></i> Collection Log (<?= count($entries) ?> entries)</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Machine</th>
          <th>Game</th>
          <th>Cash In</th>
          <th>Payouts</th>
          <th>Net Revenue</th>
          <th>Your Share</th>
          <th>Collected By</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($entries)): ?>
        <tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-receipt"></i><h3>No Collections Found</h3><p>Try adjusting the filters.</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($entries as $e): ?>
        <tr>
          <td><?= formatDate($e['entry_date']) ?></td>
          <td class="fw-600"><?= sanitize($e['machine_number'] ?: '—') ?></td>
          <td class="td-muted fs-sm"><?= sanitize($e['game_name']) ?></td>
          <td><?= formatMoney($e['cash_in']) ?></td>
          <td class="text-red">-<?= formatMoney($e['cash_out']) ?></td>
          <td class="fw-600"><?= formatMoney($e['net_revenue']) ?></td>
          <td class="text-green fw-700"><?= formatMoney($e['venue_share']) ?></td>
          <td class="td-muted"><?= sanitize($e['collected_by']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($entries)): ?>
  <div style="padding:14px 20px;border-top:1px solid var(--border);background:#FAFAF8;display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
    <div><div class="fs-xs text-muted">Total Cash In</div><div class="fw-600 font-cond"><?= formatMoney($grossIn) ?></div></div>
    <div><div class="fs-xs text-muted">Total Payouts</div><div class="fw-600 font-cond text-red"><?= formatMoney($grossOut) ?></div></div>
    <div><div class="fs-xs text-muted">Net Revenue</div><div class="fw-700 font-cond"><?= formatMoney($grossNet) ?></div></div>
    <div><div class="fs-xs text-muted">Your Earnings</div><div class="fw-700 font-cond text-green"><?= formatMoney($grossVenue) ?></div></div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/portal_footer.php'; ?>
