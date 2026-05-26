<?php
require_once '../includes/config.php';
$pageTitle = 'Revenue';
$db = getDB();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare("INSERT INTO revenue_entries (client_game_id,entry_date,cash_in,cash_out,tsm_share,venue_share,collected_by,notes)
            VALUES (?,?,?,?,?,?,?,?)")->execute([
            (int)$_POST['client_game_id'],
            $_POST['entry_date'],
            (float)$_POST['cash_in'],
            (float)$_POST['cash_out'],
            (float)$_POST['tsm_share'],
            (float)$_POST['venue_share'],
            $_POST['collected_by'] ?? '',
            $_POST['notes'] ?? ''
        ]);
        flashMessage('success', 'Revenue entry saved!');
        $redir = $_POST['redirect'] ?? BASE_URL.'/pages/revenue.php';
        redirect($redir);
    }
    if ($action === 'delete') {
        $db->prepare("DELETE FROM revenue_entries WHERE id=?")->execute([(int)$_POST['id']]);
        flashMessage('success', 'Entry deleted.');
        redirect(BASE_URL.'/pages/revenue.php');
    }
}

// Filters
$clientFilter = $_GET['client']   ?? '';
$monthFilter  = $_GET['month']    ?? date('Y-m');
$yearOnly     = substr($monthFilter,0,4);
$monOnly      = substr($monthFilter,5,2);

$where  = ['1=1'];
$params = [];

if ($monthFilter) {
    $where[]  = 'YEAR(r.entry_date)=? AND MONTH(r.entry_date)=?';
    $params[] = $yearOnly;
    $params[] = $monOnly;
}
if ($clientFilter) {
    $where[]  = 'c.id=?';
    $params[] = $clientFilter;
}

$whereStr = implode(' AND ', $where);

$entries = $db->prepare("
    SELECT r.*, c.business_name, c.city, g.game_name, cg.machine_number
    FROM revenue_entries r
    JOIN client_games cg ON cg.id=r.client_game_id
    JOIN clients c ON c.id=cg.client_id
    JOIN games g ON g.id=cg.game_id
    WHERE $whereStr
    ORDER BY r.entry_date DESC, r.created_at DESC
");
$entries->execute($params);
$entries = $entries->fetchAll();

// Totals
$totalGross  = array_sum(array_column($entries, 'cash_in'));
$totalPayout = array_sum(array_column($entries, 'cash_out'));
$totalNet    = array_sum(array_column($entries, 'net_revenue'));
$totalTSM    = array_sum(array_column($entries, 'tsm_share'));
$totalVenue  = array_sum(array_column($entries, 'venue_share'));

$allClients = $db->query("SELECT id, business_name FROM clients ORDER BY business_name")->fetchAll();
$allPlacements = $db->query("
    SELECT cg.id, cg.machine_number, c.business_name, g.game_name
    FROM client_games cg
    JOIN clients c ON c.id=cg.client_id
    JOIN games g ON g.id=cg.game_id
    WHERE cg.is_active=1
    ORDER BY c.business_name, cg.machine_number
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header">
  <h1><span>REVENUE</span> ENTRIES</h1>
  <button class="btn btn-primary" onclick="openModal('addRevModal')"><i class="fa-solid fa-plus"></i> Log Collection</button>
</div>

<!-- SUMMARY STATS -->
<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-arrow-down"></i></div>
    <div class="stat-value"><?= formatMoney($totalGross) ?></div>
    <div class="stat-label">Total Cash In</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon red"><i class="fa-solid fa-arrow-up"></i></div>
    <div class="stat-value"><?= formatMoney($totalPayout) ?></div>
    <div class="stat-label">Total Payouts</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green"><i class="fa-solid fa-dollar-sign"></i></div>
    <div class="stat-value"><?= formatMoney($totalNet) ?></div>
    <div class="stat-label">Net Revenue</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-star"></i></div>
    <div class="stat-value"><?= formatMoney($totalTSM) ?></div>
    <div class="stat-label">TSM Earnings</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fa-solid fa-handshake"></i></div>
    <div class="stat-value"><?= formatMoney($totalVenue) ?></div>
    <div class="stat-label">Venue Payouts</div>
  </div>
</div>

<!-- FILTERS -->
<form method="GET" class="filter-bar">
  <input type="month" name="month" value="<?= sanitize($monthFilter) ?>" class="filter-select">
  <select name="client" class="filter-select">
    <option value="">All Clients</option>
    <?php foreach ($allClients as $cl): ?>
    <option value="<?= $cl['id'] ?>" <?= $clientFilter==$cl['id']?'selected':'' ?>><?= sanitize($cl['business_name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
  <a href="revenue.php" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-money-bill-transfer"></i> Revenue Entries (<?= count($entries) ?>)</div>
    <a href="<?= BASE_URL ?>/pages/reports.php" class="btn btn-outline btn-xs"><i class="fa-solid fa-chart-bar"></i> Reports</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Date</th><th>Client</th><th>Machine</th><th>Game</th><th>Cash In</th><th>Cash Out</th><th>Net</th><th>TSM</th><th>Venue</th><th>Collected By</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($entries)): ?>
        <tr><td colspan="11"><div class="empty-state"><i class="fa-solid fa-dollar-sign"></i><h3>No Entries</h3><p>Log your first collection.</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($entries as $r): ?>
        <tr>
          <td class="td-muted"><?= formatDate($r['entry_date']) ?></td>
          <td>
            <a href="client_detail.php?id=<?= /* need client id */ '' ?>" style="color:var(--text-white);text-decoration:none">
              <?= sanitize($r['business_name']) ?>
            </a><br>
            <span class="td-muted fs-sm"><?= sanitize($r['city']) ?></span>
          </td>
          <td class="td-muted"><?= sanitize($r['machine_number']) ?></td>
          <td class="td-muted fs-sm"><?= sanitize($r['game_name']) ?></td>
          <td><?= formatMoney($r['cash_in']) ?></td>
          <td class="money-negative">-<?= formatMoney($r['cash_out']) ?></td>
          <td class="money-positive fw-600"><?= formatMoney($r['net_revenue']) ?></td>
          <td class="text-gold fw-600"><?= formatMoney($r['tsm_share']) ?></td>
          <td><?= formatMoney($r['venue_share']) ?></td>
          <td class="td-muted"><?= sanitize($r['collected_by']) ?></td>
          <td>
            <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete this entry?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-danger btn-xs" type="submit"><i class="fa-solid fa-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($entries)): ?>
  <div style="padding:14px 22px;border-top:1px solid var(--border);display:grid;grid-template-columns:repeat(5,1fr);gap:16px">
    <div><div class="td-muted fs-sm">Cash In</div><div class="fw-600"><?= formatMoney($totalGross) ?></div></div>
    <div><div class="td-muted fs-sm">Cash Out</div><div class="fw-600 money-negative"><?= formatMoney($totalPayout) ?></div></div>
    <div><div class="td-muted fs-sm">Net</div><div class="fw-600 money-positive"><?= formatMoney($totalNet) ?></div></div>
    <div><div class="td-muted fs-sm">TSM Total</div><div class="fw-600 text-gold"><?= formatMoney($totalTSM) ?></div></div>
    <div><div class="td-muted fs-sm">Venue Total</div><div class="fw-600"><?= formatMoney($totalVenue) ?></div></div>
  </div>
  <?php endif; ?>
</div>

<!-- ADD REVENUE MODAL -->
<div class="modal-overlay" id="addRevModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Log Revenue Collection</div>
      <button class="modal-close" onclick="closeModal('addRevModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group col-span-2">
            <label>Machine / Game *</label>
            <select name="client_game_id" required>
              <option value="">— Select Machine —</option>
              <?php foreach ($allPlacements as $p): ?>
              <option value="<?= $p['id'] ?>"><?= sanitize($p['business_name']) ?> — <?= sanitize($p['machine_number']) ?> (<?= sanitize($p['game_name']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Collection Date *</label>
            <input type="date" name="entry_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label>Cash In ($)</label>
            <input type="number" name="cash_in" step="0.01" min="0" value="0.00" data-currency>
          </div>
          <div class="form-group">
            <label>Cash Out / Payouts ($)</label>
            <input type="number" name="cash_out" step="0.01" min="0" value="0.00" data-currency>
          </div>
          <div class="form-group">
            <label>TSM Share ($)</label>
            <input type="number" name="tsm_share" step="0.01" min="0" value="0.00" data-currency>
          </div>
          <div class="form-group">
            <label>Venue Share ($)</label>
            <input type="number" name="venue_share" step="0.01" min="0" value="0.00" data-currency>
          </div>
          <div class="form-group">
            <label>Collected By</label>
            <input type="text" name="collected_by">
          </div>
          <div class="form-group col-span-2">
            <label>Notes</label>
            <textarea name="notes" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addRevModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Entry</button>
      </div>
    </form>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
