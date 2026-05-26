<?php
require_once '../includes/config.php';
$db = getDB();
$b  = BASE_URL;

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect($b.'/pages/clients.php'); }

$client = $db->prepare("SELECT * FROM clients WHERE id=?");
$client->execute([$id]);
$client = $client->fetch();
if (!$client) { redirect($b.'/pages/clients.php'); }

$pageTitle = $client['business_name'];

// Games at this location
$placements = $db->prepare("
    SELECT cg.*, g.game_name, g.manufacturer, g.model, g.serial_number,
           COALESCE(SUM(r.net_revenue),0) AS total_rev,
           COALESCE(SUM(r.tsm_share),0)  AS tsm_rev,
           COUNT(r.id) AS entry_count
    FROM client_games cg
    JOIN games g ON g.id=cg.game_id
    LEFT JOIN revenue_entries r ON r.client_game_id=cg.id
    WHERE cg.client_id=?
    GROUP BY cg.id
    ORDER BY cg.is_active DESC, cg.installed_date DESC
");
$placements->execute([$id]);
$placements = $placements->fetchAll();

// Revenue history
$revenue = $db->prepare("
    SELECT r.*, g.game_name, cg.machine_number
    FROM revenue_entries r
    JOIN client_games cg ON cg.id=r.client_game_id
    JOIN games g ON g.id=cg.game_id
    WHERE cg.client_id=?
    ORDER BY r.entry_date DESC
    LIMIT 50
");
$revenue->execute([$id]);
$revenue = $revenue->fetchAll();

// Revenue totals
$revTotals = $db->prepare("
    SELECT COALESCE(SUM(r.cash_in),0) AS gross_in,
           COALESCE(SUM(r.cash_out),0) AS gross_out,
           COALESCE(SUM(r.net_revenue),0) AS net,
           COALESCE(SUM(r.tsm_share),0)   AS tsm,
           COALESCE(SUM(r.venue_share),0) AS venue,
           COUNT(r.id) AS total_entries
    FROM revenue_entries r
    JOIN client_games cg ON cg.id=r.client_game_id
    WHERE cg.client_id=?
");
$revTotals->execute([$id]);
$revTotals = $revTotals->fetch();

// Service logs
$services = $db->prepare("
    SELECT sl.*, g.game_name
    FROM service_logs sl
    LEFT JOIN client_games cg ON cg.id=sl.client_game_id
    LEFT JOIN games g ON g.id=cg.game_id
    WHERE sl.client_id=?
    ORDER BY sl.service_date DESC
");
$services->execute([$id]);
$services = $services->fetchAll();

// All available games for placement
$allGames = $db->query("SELECT * FROM games WHERE status='Active' ORDER BY game_name")->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header">
  <div>
    <a href="<?= $b ?>/pages/clients.php" style="color:var(--text-muted);text-decoration:none;font-size:0.8rem"><i class="fa-solid fa-arrow-left"></i> Back to Clients</a>
    <h1 style="margin-top:4px"><span><?= sanitize($client['business_name']) ?></span></h1>
    <div style="display:flex;gap:10px;align-items:center;margin-top:6px">
      <?php $bc = ['Active'=>'badge-green','Inactive'=>'badge-red','Pending'=>'badge-gold'][$client['status']] ?? 'badge-gray'; ?>
      <span class="badge <?= $bc ?>"><?= $client['status'] ?></span>
      <span class="badge badge-blue"><?= sanitize($client['venue_type']) ?></span>
      <span class="td-muted fs-sm"><?= sanitize($client['city']) ?>, <?= sanitize($client['state']) ?></span>
    </div>
  </div>
  <div class="btn-group">
    <a href="<?= $b ?>/pages/clients.php?edit=<?= $id ?>" class="btn btn-outline"><i class="fa-solid fa-pen"></i> Edit Client</a>
    <button class="btn btn-primary" onclick="openModal('addPlacementModal')"><i class="fa-solid fa-plus"></i> Add Game</button>
  </div>
</div>

<!-- REVENUE QUICK STATS -->
<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-gamepad"></i></div>
    <div class="stat-value"><?= count(array_filter($placements, fn($p)=>$p['is_active'])) ?></div>
    <div class="stat-label">Active Machines</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green"><i class="fa-solid fa-dollar-sign"></i></div>
    <div class="stat-value"><?= formatMoney($revTotals['net']) ?></div>
    <div class="stat-label">Total Net Revenue</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-star"></i></div>
    <div class="stat-value"><?= formatMoney($revTotals['tsm']) ?></div>
    <div class="stat-label">TSM Earnings</div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fa-solid fa-handshake"></i></div>
    <div class="stat-value"><?= formatMoney($revTotals['venue']) ?></div>
    <div class="stat-label">Venue Share</div>
  </div>
</div>

<div class="tab-container">
  <div class="tab-nav">
    <button class="tab-btn active" data-tab="info">Client Info</button>
    <button class="tab-btn" data-tab="games">Machines (<?= count($placements) ?>)</button>
    <button class="tab-btn" data-tab="revenue">Revenue (<?= count($revenue) ?>)</button>
    <button class="tab-btn" data-tab="service">Service Logs (<?= count($services) ?>)</button>
  </div>

  <!-- INFO TAB -->
  <div class="tab-panel active" data-panel="info">
    <div class="grid-2" style="gap:20px">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa-solid fa-building"></i> Business Details</div></div>
        <div class="card-body">
          <div class="detail-grid">
            <div class="detail-item"><label>Business Name</label><div class="val"><?= sanitize($client['business_name']) ?></div></div>
            <div class="detail-item"><label>Phone</label><div class="val"><?= sanitize($client['phone']) ?: '—' ?></div></div>
            <div class="detail-item"><label>Email</label><div class="val"><?= sanitize($client['email']) ?: '—' ?></div></div>
            <div class="detail-item"><label>Address</label><div class="val"><?= sanitize($client['address']) ?><br><?= sanitize($client['city']) ?>, <?= sanitize($client['state']) ?> <?= sanitize($client['zip']) ?></div></div>
            <div class="detail-item"><label>Venue Type</label><div class="val"><?= sanitize($client['venue_type']) ?></div></div>
            <div class="detail-item"><label>Status</label><div class="val"><?= sanitize($client['status']) ?></div></div>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa-solid fa-user-tie"></i> Point of Contact</div></div>
        <div class="card-body">
          <div class="detail-grid">
            <div class="detail-item"><label>Name</label><div class="val"><?= sanitize($client['contact_name']) ?: '—' ?></div></div>
            <div class="detail-item"><label>Title</label><div class="val"><?= sanitize($client['contact_title']) ?: '—' ?></div></div>
            <div class="detail-item"><label>Phone</label><div class="val"><?= sanitize($client['contact_phone']) ?: '—' ?></div></div>
            <div class="detail-item"><label>Email</label><div class="val"><?= sanitize($client['contact_email']) ?: '—' ?></div></div>
          </div>
          <?php if ($client['notes']): ?>
          <hr class="gold-line">
          <div class="detail-item"><label>Notes</label><div class="val" style="line-height:1.6"><?= nl2br(sanitize($client['notes'])) ?></div></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa-solid fa-file-contract"></i> Contract</div></div>
        <div class="card-body">
          <div class="detail-grid">
            <div class="detail-item"><label>Start Date</label><div class="val"><?= formatDate($client['contract_start']) ?></div></div>
            <div class="detail-item"><label>End Date</label><div class="val"><?= formatDate($client['contract_end']) ?></div></div>
            <div class="detail-item"><label>Client Since</label><div class="val"><?= formatDate($client['created_at']) ?></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- MACHINES TAB -->
  <div class="tab-panel" data-panel="games">
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-gamepad"></i> Placed Machines</div>
        <button class="btn btn-primary btn-sm" onclick="openModal('addPlacementModal')"><i class="fa-solid fa-plus"></i> Add Machine</button>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Machine #</th><th>Game</th><th>Manufacturer</th><th>Serial</th><th>Installed</th><th>Split (TSM%)</th><th>Revenue</th><th>TSM Earnings</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php if (empty($placements)): ?>
            <tr><td colspan="9"><div class="empty-state"><i class="fa-solid fa-gamepad"></i><h3>No Machines Placed</h3><p>Add a machine to this location.</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($placements as $p): ?>
            <tr>
              <td class="fw-600"><?= sanitize($p['machine_number']) ?: '—' ?></td>
              <td><?= sanitize($p['game_name']) ?></td>
              <td class="td-muted"><?= sanitize($p['manufacturer']) ?></td>
              <td class="td-muted fs-sm"><?= sanitize($p['serial_number']) ?></td>
              <td class="td-muted"><?= formatDate($p['installed_date']) ?></td>
              <td class="text-center"><?= number_format($p['revenue_split'],1) ?>%</td>
              <td class="money-positive"><?= formatMoney($p['total_rev']) ?></td>
              <td class="text-gold fw-600"><?= formatMoney($p['tsm_rev']) ?></td>
              <td><?= $p['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Removed</span>' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- REVENUE TAB -->
  <div class="tab-panel" data-panel="revenue">
    <div class="card" style="margin-bottom:16px">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-money-bill-transfer"></i> Revenue History</div>
        <button class="btn btn-primary btn-sm" onclick="openModal('addRevenueModal')"><i class="fa-solid fa-plus"></i> Log Revenue</button>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Date</th><th>Machine</th><th>Game</th><th>Cash In</th><th>Cash Out</th><th>Net Revenue</th><th>TSM Share</th><th>Venue Share</th><th>Collected By</th></tr>
          </thead>
          <tbody>
            <?php if (empty($revenue)): ?>
            <tr><td colspan="9"><div class="empty-state"><i class="fa-solid fa-dollar-sign"></i><h3>No Revenue Entries</h3></div></td></tr>
            <?php else: ?>
            <?php foreach ($revenue as $r): ?>
            <tr>
              <td><?= formatDate($r['entry_date']) ?></td>
              <td class="td-muted"><?= sanitize($r['machine_number']) ?></td>
              <td class="td-muted fs-sm"><?= sanitize($r['game_name']) ?></td>
              <td class="money-neutral"><?= formatMoney($r['cash_in']) ?></td>
              <td class="money-negative">-<?= formatMoney($r['cash_out']) ?></td>
              <td class="money-positive fw-600"><?= formatMoney($r['net_revenue']) ?></td>
              <td class="text-gold fw-600"><?= formatMoney($r['tsm_share']) ?></td>
              <td><?= formatMoney($r['venue_share']) ?></td>
              <td class="td-muted"><?= sanitize($r['collected_by']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- SERVICE TAB -->
  <div class="tab-panel" data-panel="service">
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-screwdriver-wrench"></i> Service History</div>
        <button class="btn btn-primary btn-sm" onclick="openModal('addServiceModal')"><i class="fa-solid fa-plus"></i> Log Service</button>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Date</th><th>Type</th><th>Machine</th><th>Technician</th><th>Description</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php if (empty($services)): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-wrench"></i><h3>No Service Logs</h3></div></td></tr>
            <?php else: ?>
            <?php foreach ($services as $s): ?>
            <tr>
              <td><?= formatDate($s['service_date']) ?></td>
              <td><span class="badge badge-blue"><?= sanitize($s['service_type']) ?></span></td>
              <td class="td-muted"><?= sanitize($s['game_name'] ?? '—') ?></td>
              <td><?= sanitize($s['technician']) ?></td>
              <td class="td-muted fs-sm" style="max-width:260px"><?= sanitize($s['description']) ?></td>
              <td><?= $s['resolved'] ? '<span class="badge badge-green">Resolved</span>' : '<span class="badge badge-red">Open</span>' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ADD PLACEMENT MODAL -->
<div class="modal-overlay" id="addPlacementModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Machine to Location</div>
      <button class="modal-close" onclick="closeModal('addPlacementModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?= $b ?>/pages/placements.php">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="client_id" value="<?= $id ?>">
      <input type="hidden" name="redirect" value="<?= $b ?>/pages/client_detail.php?id=<?= $id ?>">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group col-span-2">
            <label>Select Game *</label>
            <select name="game_id" required>
              <option value="">— Choose Game —</option>
              <?php foreach ($allGames as $g): ?>
              <option value="<?= $g['id'] ?>"><?= sanitize($g['game_name']) ?> (<?= sanitize($g['serial_number']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Machine Number</label>
            <input type="text" name="machine_number" placeholder="e.g. LOC-M01">
          </div>
          <div class="form-group">
            <label>Install Date</label>
            <input type="date" name="installed_date" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label>TSM Revenue Split %</label>
            <input type="number" name="revenue_split" value="50" min="0" max="100" step="0.5">
          </div>
          <div class="form-group col-span-2">
            <label>Notes</label>
            <textarea name="notes" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addPlacementModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Placement</button>
      </div>
    </form>
  </div>
</div>

<!-- ADD REVENUE MODAL -->
<div class="modal-overlay" id="addRevenueModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Log Revenue Collection</div>
      <button class="modal-close" onclick="closeModal('addRevenueModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?= $b ?>/pages/revenue.php">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="redirect" value="<?= $b ?>/pages/client_detail.php?id=<?= $id ?>&tab=revenue">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group col-span-2">
            <label>Machine / Game *</label>
            <select name="client_game_id" required>
              <option value="">— Select Machine —</option>
              <?php foreach ($placements as $p): if (!$p['is_active']) continue; ?>
              <option value="<?= $p['id'] ?>"><?= sanitize($p['machine_number']) ?> — <?= sanitize($p['game_name']) ?></option>
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
            <input type="text" name="collected_by" placeholder="Technician name">
          </div>
          <div class="form-group col-span-2">
            <label>Notes</label>
            <textarea name="notes" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addRevenueModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Entry</button>
      </div>
    </form>
  </div>
</div>

<!-- ADD SERVICE MODAL -->
<div class="modal-overlay" id="addServiceModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Log Service Visit</div>
      <button class="modal-close" onclick="closeModal('addServiceModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" action="<?= $b ?>/pages/service.php">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="client_id" value="<?= $id ?>">
      <input type="hidden" name="redirect" value="<?= $b ?>/pages/client_detail.php?id=<?= $id ?>&tab=service">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Service Date *</label>
            <input type="date" name="service_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label>Service Type</label>
            <select name="service_type">
              <?php foreach (['Routine Collection','Repair','Installation','Removal','Inspection','Other'] as $st): ?>
              <option value="<?= $st ?>"><?= $st ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Machine (optional)</label>
            <select name="client_game_id">
              <option value="">— All / General —</option>
              <?php foreach ($placements as $p): ?>
              <option value="<?= $p['id'] ?>"><?= sanitize($p['machine_number']) ?> — <?= sanitize($p['game_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Technician</label>
            <input type="text" name="technician" placeholder="Technician name">
          </div>
          <div class="form-group col-span-2">
            <label>Description</label>
            <textarea name="description" rows="3" required></textarea>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="resolved">
              <option value="1">Resolved</option>
              <option value="0">Open / Pending</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addServiceModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Log</button>
      </div>
    </form>
  </div>
</div>

<script>
const urlParams = new URLSearchParams(window.location.search);
const tab = urlParams.get('tab');
if (tab) {
  document.querySelectorAll('.tab-btn').forEach(b => {
    if (b.dataset.tab === tab) b.click();
  });
}
</script>

<?php require_once '../includes/footer.php'; ?>
