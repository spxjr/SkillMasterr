<?php
require_once '../includes/config.php';
$pageTitle = 'Clients';
$db = getDB();

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $fields = [
            'business_name'  => $_POST['business_name'] ?? '',
            'address'        => $_POST['address']        ?? '',
            'city'           => $_POST['city']           ?? '',
            'state'          => $_POST['state']          ?? 'TX',
            'zip'            => $_POST['zip']            ?? '',
            'phone'          => $_POST['phone']          ?? '',
            'email'          => $_POST['email']          ?? '',
            'contact_name'   => $_POST['contact_name']   ?? '',
            'contact_title'  => $_POST['contact_title']  ?? '',
            'contact_phone'  => $_POST['contact_phone']  ?? '',
            'contact_email'  => $_POST['contact_email']  ?? '',
            'venue_type'     => $_POST['venue_type']     ?? 'Bar',
            'status'         => $_POST['status']         ?? 'Active',
            'contract_start' => $_POST['contract_start'] ?: null,
            'contract_end'   => $_POST['contract_end']   ?: null,
            'notes'          => $_POST['notes']          ?? '',
        ];

        if ($action === 'add') {
            $sql = "INSERT INTO clients (business_name,address,city,state,zip,phone,email,contact_name,contact_title,contact_phone,contact_email,venue_type,status,contract_start,contract_end,notes)
                    VALUES (:business_name,:address,:city,:state,:zip,:phone,:email,:contact_name,:contact_title,:contact_phone,:contact_email,:venue_type,:status,:contract_start,:contract_end,:notes)";
            $db->prepare($sql)->execute($fields);
            flashMessage('success', 'Client added successfully!');
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $fields['id'] = $id;
            $sql = "UPDATE clients SET business_name=:business_name,address=:address,city=:city,state=:state,zip=:zip,phone=:phone,email=:email,contact_name=:contact_name,contact_title=:contact_title,contact_phone=:contact_phone,contact_email=:contact_email,venue_type=:venue_type,status=:status,contract_start=:contract_start,contract_end=:contract_end,notes=:notes WHERE id=:id";
            $db->prepare($sql)->execute($fields);
            flashMessage('success', 'Client updated successfully!');
        }
        redirect(BASE_URL.'/pages/clients.php');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $db->prepare("DELETE FROM clients WHERE id=?")->execute([$id]);
        flashMessage('success', 'Client removed.');
        redirect(BASE_URL.'/pages/clients.php');
    }
}

// ── Filters ──────────────────────────────────────────────────
$statusFilter = $_GET['status'] ?? '';
$typeFilter   = $_GET['type']   ?? '';
$search       = $_GET['search'] ?? '';

$where  = ['1=1'];
$params = [];
if ($statusFilter) { $where[] = 'c.status=?'; $params[] = $statusFilter; }
if ($typeFilter)   { $where[] = 'c.venue_type=?'; $params[] = $typeFilter; }
if ($search)       { $where[] = '(c.business_name LIKE ? OR c.city LIKE ? OR c.contact_name LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$whereStr = implode(' AND ', $where);

$clients = $db->prepare("
    SELECT c.*,
           COUNT(DISTINCT cg.id) AS game_count,
           COALESCE(SUM(r.net_revenue),0) AS total_rev,
           COALESCE(SUM(r.tsm_share),0)  AS tsm_rev
    FROM clients c
    LEFT JOIN client_games cg ON cg.client_id=c.id AND cg.is_active=1
    LEFT JOIN revenue_entries r ON r.client_game_id=cg.id
    WHERE $whereStr
    GROUP BY c.id
    ORDER BY c.business_name
");
$clients->execute($params);
$clients = $clients->fetchAll();

// Edit modal data
$editClient = null;
if (isset($_GET['edit'])) {
    $editClient = $db->prepare("SELECT * FROM clients WHERE id=?")->execute([(int)$_GET['edit']]) ? $db->prepare("SELECT * FROM clients WHERE id=?"): null;
    $stmt = $db->prepare("SELECT * FROM clients WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editClient = $stmt->fetch();
}

require_once '../includes/header.php';
?>

<div class="page-header">
  <h1><span>CLIENT</span> MANAGEMENT</h1>
  <div class="btn-group">
    <button class="btn btn-primary" onclick="openModal('addClientModal')">
      <i class="fa-solid fa-plus"></i> Add Client
    </button>
  </div>
</div>

<!-- FILTER BAR -->
<form method="GET" class="filter-bar">
  <div class="search-wrap">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search clients, cities, contacts…">
  </div>
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="">All Statuses</option>
    <option value="Active"   <?= $statusFilter==='Active'  ?'selected':'' ?>>Active</option>
    <option value="Inactive" <?= $statusFilter==='Inactive'?'selected':'' ?>>Inactive</option>
    <option value="Pending"  <?= $statusFilter==='Pending' ?'selected':'' ?>>Pending</option>
  </select>
  <select name="type" class="filter-select" onchange="this.form.submit()">
    <option value="">All Venue Types</option>
    <option value="Bar"              <?= $typeFilter==='Bar'             ?'selected':'' ?>>Bar</option>
    <option value="Restaurant"       <?= $typeFilter==='Restaurant'      ?'selected':'' ?>>Restaurant</option>
    <option value="Convenience Store"<?= $typeFilter==='Convenience Store'?'selected':'' ?>>Convenience Store</option>
    <option value="Gaming Lounge"    <?= $typeFilter==='Gaming Lounge'   ?'selected':'' ?>>Gaming Lounge</option>
    <option value="Other"            <?= $typeFilter==='Other'           ?'selected':'' ?>>Other</option>
  </select>
  <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
  <a href="<?= BASE_URL ?>/pages/clients.php" class="btn btn-outline btn-sm">Reset</a>
</form>

<!-- CLIENTS TABLE -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-building-user"></i> All Clients (<?= count($clients) ?>)</div>
  </div>
  <div class="table-wrap">
    <table id="clientsTable">
      <thead>
        <tr>
          <th>Business Name</th>
          <th>Location</th>
          <th>Type</th>
          <th>Contact</th>
          <th>Phone</th>
          <th>Machines</th>
          <th>Total Revenue</th>
          <th>TSM Earnings</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($clients)): ?>
        <tr><td colspan="10"><div class="empty-state"><i class="fa-solid fa-building-circle-xmark"></i><h3>No Clients Found</h3><p>Add your first client to get started.</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($clients as $c): ?>
        <tr>
          <td>
            <a href="<?= BASE_URL ?>/pages/client_detail.php?id=<?= $c['id'] ?>" style="color:var(--gold-light);text-decoration:none;font-weight:600">
              <?= sanitize($c['business_name']) ?>
            </a>
          </td>
          <td class="td-muted"><?= sanitize($c['city']) ?>, <?= sanitize($c['state']) ?></td>
          <td><span class="badge badge-blue"><?= sanitize($c['venue_type']) ?></span></td>
          <td><?= sanitize($c['contact_name']) ?><br><span class="td-muted fs-sm"><?= sanitize($c['contact_title']) ?></span></td>
          <td class="td-muted"><?= sanitize($c['phone']) ?></td>
          <td class="text-center font-cond" style="font-size:1.1rem"><?= $c['game_count'] ?></td>
          <td class="money-positive"><?= formatMoney($c['total_rev']) ?></td>
          <td class="text-gold fw-600"><?= formatMoney($c['tsm_rev']) ?></td>
          <td>
            <?php $bc = ['Active'=>'badge-green','Inactive'=>'badge-red','Pending'=>'badge-gold'][$c['status']] ?? 'badge-gray'; ?>
            <span class="badge <?= $bc ?>"><?= $c['status'] ?></span>
          </td>
          <td>
            <div class="btn-group">
              <a href="<?= BASE_URL ?>/pages/client_detail.php?id=<?= $c['id'] ?>" class="btn btn-outline btn-xs"><i class="fa-solid fa-eye"></i></a>
              <a href="<?= BASE_URL ?>/pages/clients.php?edit=<?= $c['id'] ?>" class="btn btn-outline btn-xs"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete <?= addslashes($c['business_name']) ?>?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= $c['id'] ?>">
                <button class="btn btn-danger btn-xs" type="submit"><i class="fa-solid fa-trash"></i></button>
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

<!-- ── ADD CLIENT MODAL ── -->
<div class="modal-overlay" id="addClientModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="fa-solid fa-building-user"></i> Add New Client</div>
      <button class="modal-close" onclick="closeModal('addClientModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <?php include '_client_form.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addClientModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Client</button>
      </div>
    </form>
  </div>
</div>

<!-- ── EDIT CLIENT MODAL ── -->
<?php if ($editClient): ?>
<div class="modal-overlay open" id="editClientModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="fa-solid fa-pen"></i> Edit Client</div>
      <button class="modal-close" onclick="window.location='<?= BASE_URL ?>/pages/clients.php'"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id"     value="<?= $editClient['id'] ?>">
      <div class="modal-body">
        <?php include '_client_form.php'; ?>
      </div>
      <div class="modal-footer">
        <a href="<?= BASE_URL ?>/pages/clients.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update Client</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
