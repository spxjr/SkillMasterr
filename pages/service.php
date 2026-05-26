<?php
require_once '../includes/config.php';
$pageTitle = 'Service Logs';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $cgId = !empty($_POST['client_game_id']) ? (int)$_POST['client_game_id'] : null;
        $db->prepare("INSERT INTO service_logs (client_id,client_game_id,service_date,service_type,technician,description,resolved) VALUES (?,?,?,?,?,?,?)")->execute([
            (int)$_POST['client_id'],
            $cgId,
            $_POST['service_date'],
            $_POST['service_type'] ?? 'Routine Collection',
            $_POST['technician'] ?? '',
            $_POST['description'] ?? '',
            (int)($_POST['resolved'] ?? 1)
        ]);
        flashMessage('success', 'Service log saved!');
    }
    if ($action === 'resolve') {
        $db->prepare("UPDATE service_logs SET resolved=1 WHERE id=?")->execute([(int)$_POST['id']]);
        flashMessage('success', 'Ticket marked resolved.');
    }
    if ($action === 'delete') {
        $db->prepare("DELETE FROM service_logs WHERE id=?")->execute([(int)$_POST['id']]);
        flashMessage('success', 'Log deleted.');
    }
    $redir = $_POST['redirect'] ?? BASE_URL.'/pages/service.php';
    redirect($redir);
}

$typeFilter   = $_GET['type']   ?? '';
$statusFilter = $_GET['status'] ?? '';

$where  = ['1=1'];
$params = [];
if ($typeFilter)   { $where[] = 'sl.service_type=?'; $params[] = $typeFilter; }
if ($statusFilter !== '') { $where[] = 'sl.resolved=?'; $params[] = $statusFilter; }

$logs = $db->prepare("
    SELECT sl.*, c.business_name, c.city,
           g.game_name, cg.machine_number
    FROM service_logs sl
    JOIN clients c ON c.id=sl.client_id
    LEFT JOIN client_games cg ON cg.id=sl.client_game_id
    LEFT JOIN games g ON g.id=cg.game_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY sl.service_date DESC, sl.created_at DESC
");
$logs->execute($params);
$logs = $logs->fetchAll();

$allClients    = $db->query("SELECT id, business_name FROM clients ORDER BY business_name")->fetchAll();
$allPlacements = $db->query("
    SELECT cg.id, cg.machine_number, c.business_name, g.game_name, cg.client_id
    FROM client_games cg JOIN clients c ON c.id=cg.client_id JOIN games g ON g.id=cg.game_id
    WHERE cg.is_active=1 ORDER BY c.business_name
")->fetchAll();

$openCount = $db->query("SELECT COUNT(*) FROM service_logs WHERE resolved=0")->fetchColumn();

require_once '../includes/header.php';
?>

<div class="page-header">
  <h1><span>SERVICE</span> LOGS</h1>
  <button class="btn btn-primary" onclick="openModal('addServiceModal')"><i class="fa-solid fa-plus"></i> Log Service</button>
</div>

<?php if ($openCount > 0): ?>
<div class="alert alert-error" style="margin-bottom:20px">
  <i class="fa-solid fa-triangle-exclamation"></i>
  <strong><?= $openCount ?> open service ticket<?= $openCount!=1?'s':'' ?></strong> require attention.
  <a href="<?= BASE_URL ?>/pages/service.php?status=0" style="color:inherit;margin-left:8px;text-decoration:underline">View open tickets</a>
</div>
<?php endif; ?>

<!-- FILTERS -->
<form method="GET" class="filter-bar">
  <select name="type" class="filter-select" onchange="this.form.submit()">
    <option value="">All Types</option>
    <?php foreach (['Routine Collection','Repair','Installation','Removal','Inspection','Other'] as $t): ?>
    <option value="<?= $t ?>" <?= $typeFilter===$t?'selected':'' ?>><?= $t ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="">All Status</option>
    <option value="0" <?= $statusFilter==='0'?'selected':'' ?>>Open</option>
    <option value="1" <?= $statusFilter==='1'?'selected':'' ?>>Resolved</option>
  </select>
  <a href="service.php" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-screwdriver-wrench"></i> Service History (<?= count($logs) ?>)</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Date</th><th>Client</th><th>Machine</th><th>Type</th><th>Technician</th><th>Description</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
        <tr><td colspan="8"><div class="empty-state"><i class="fa-solid fa-wrench"></i><h3>No Service Logs</h3></div></td></tr>
        <?php else: ?>
        <?php foreach ($logs as $l): ?>
        <tr>
          <td class="td-muted"><?= formatDate($l['service_date']) ?></td>
          <td><a href="<?= BASE_URL ?>/pages/client_detail.php?id=<?= $l['client_id'] ?>" style="color:var(--gold-light);text-decoration:none"><?= sanitize($l['business_name']) ?></a></td>
          <td class="td-muted"><?= $l['machine_number'] ? sanitize($l['machine_number']).' — '.sanitize($l['game_name']) : '—' ?></td>
          <td><span class="badge badge-blue"><?= sanitize($l['service_type']) ?></span></td>
          <td><?= sanitize($l['technician']) ?></td>
          <td class="td-muted fs-sm" style="max-width:280px"><?= sanitize($l['description']) ?></td>
          <td><?= $l['resolved'] ? '<span class="badge badge-green">Resolved</span>' : '<span class="badge badge-red">Open</span>' ?></td>
          <td>
            <div class="btn-group">
              <?php if (!$l['resolved']): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="resolve">
                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                <button class="btn btn-outline btn-xs"><i class="fa-solid fa-check"></i> Resolve</button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete this log?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                <button class="btn btn-danger btn-xs"><i class="fa-solid fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ADD SERVICE MODAL -->
<div class="modal-overlay" id="addServiceModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Log Service Visit</div>
      <button class="modal-close" onclick="closeModal('addServiceModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group col-span-2">
            <label>Client *</label>
            <select name="client_id" required id="svcClientSelect" onchange="filterMachines(this.value)">
              <option value="">— Select Client —</option>
              <?php foreach ($allClients as $c): ?>
              <option value="<?= $c['id'] ?>"><?= sanitize($c['business_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-span-2">
            <label>Machine (optional)</label>
            <select name="client_game_id" id="svcMachineSelect">
              <option value="">— All / General Visit —</option>
              <?php foreach ($allPlacements as $p): ?>
              <option value="<?= $p['id'] ?>" data-client="<?= $p['client_id'] ?>"><?= sanitize($p['business_name']) ?> — <?= sanitize($p['machine_number']) ?> (<?= sanitize($p['game_name']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Date *</label>
            <input type="date" name="service_date" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label>Type</label>
            <select name="service_type">
              <?php foreach (['Routine Collection','Repair','Installation','Removal','Inspection','Other'] as $t): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Technician</label>
            <input type="text" name="technician">
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="resolved">
              <option value="1">Resolved</option>
              <option value="0">Open</option>
            </select>
          </div>
          <div class="form-group col-span-2">
            <label>Description *</label>
            <textarea name="description" rows="3" required></textarea>
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
function filterMachines(clientId) {
  const sel = document.getElementById('svcMachineSelect');
  sel.querySelectorAll('option').forEach(opt => {
    if (!opt.value) { opt.style.display = ''; return; }
    opt.style.display = (!clientId || opt.dataset.client === clientId) ? '' : 'none';
  });
  sel.value = '';
}
</script>

<?php require_once '../includes/footer.php'; ?>
