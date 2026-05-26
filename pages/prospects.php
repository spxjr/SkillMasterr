<?php
require_once '../includes/config.php';
$pageTitle = 'Prospects & Leads';
$db = getDB();
$b  = BASE_URL;

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $fields = [
            'store_name'      => trim($_POST['store_name']    ?? ''),
            'store_type'      => $_POST['store_type']         ?? 'Bar',
            'address'         => trim($_POST['address']        ?? ''),
            'city'            => trim($_POST['city']           ?? ''),
            'state'           => trim($_POST['state']          ?? 'TX'),
            'zip'             => trim($_POST['zip']            ?? ''),
            'county'          => trim($_POST['county']         ?? ''),
            'contact_name'    => trim($_POST['contact_name']   ?? ''),
            'contact_title'   => trim($_POST['contact_title']  ?? ''),
            'contact_phone'   => trim($_POST['contact_phone']  ?? ''),
            'contact_email'   => trim($_POST['contact_email']  ?? ''),
            'status'          => $_POST['status']              ?? 'New Lead',
            'priority'        => $_POST['priority']            ?? 'Medium',
            'source'          => $_POST['source']              ?? 'Cold Call',
            'assigned_to'     => trim($_POST['assigned_to']    ?? ''),
            'machines_wanted' => !empty($_POST['machines_wanted']) ? (int)$_POST['machines_wanted'] : null,
            'notes'           => trim($_POST['notes']          ?? ''),
            'last_contact'    => !empty($_POST['last_contact'])    ? $_POST['last_contact']    : null,
            'follow_up_date'  => !empty($_POST['follow_up_date'])  ? $_POST['follow_up_date']  : null,
        ];
        if ($action === 'add') {
            $db->prepare("INSERT INTO prospects
                (store_name,store_type,address,city,state,zip,county,contact_name,contact_title,contact_phone,contact_email,status,priority,source,assigned_to,machines_wanted,notes,last_contact,follow_up_date)
                VALUES (:store_name,:store_type,:address,:city,:state,:zip,:county,:contact_name,:contact_title,:contact_phone,:contact_email,:status,:priority,:source,:assigned_to,:machines_wanted,:notes,:last_contact,:follow_up_date)")
                ->execute($fields);
            flashMessage('success', 'Prospect added!');
        } else {
            $fields['id'] = (int)($_POST['id'] ?? 0);
            $db->prepare("UPDATE prospects SET
                store_name=:store_name,store_type=:store_type,address=:address,city=:city,state=:state,zip=:zip,county=:county,
                contact_name=:contact_name,contact_title=:contact_title,contact_phone=:contact_phone,contact_email=:contact_email,
                status=:status,priority=:priority,source=:source,assigned_to=:assigned_to,machines_wanted=:machines_wanted,
                notes=:notes,last_contact=:last_contact,follow_up_date=:follow_up_date
                WHERE id=:id")->execute($fields);
            flashMessage('success', 'Prospect updated!');
        }
        redirect($b . '/pages/prospects.php');
    }

    if ($action === 'delete') {
        $db->prepare("DELETE FROM prospects WHERE id=?")->execute([(int)$_POST['id']]);
        flashMessage('success', 'Prospect deleted.');
        redirect($b . '/pages/prospects.php');
    }

    if ($action === 'quick_status') {
        $db->prepare("UPDATE prospects SET status=? WHERE id=?")
           ->execute([$_POST['status'], (int)$_POST['id']]);
        redirect($b . '/pages/prospects.php');
    }

    if ($action === 'convert') {
        // Convert prospect to client
        $pid = (int)$_POST['id'];
        $p   = $db->prepare("SELECT * FROM prospects WHERE id=?");
        $p->execute([$pid]);
        $p = $p->fetch();
        if ($p) {
            $db->prepare("INSERT INTO clients (business_name,address,city,state,zip,phone,contact_name,contact_title,contact_phone,contact_email,venue_type,status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([
                   $p['store_name'], $p['address'], $p['city'], $p['state'], $p['zip'],
                   $p['contact_phone'], $p['contact_name'], $p['contact_title'],
                   $p['contact_phone'], $p['contact_email'],
                   in_array($p['store_type'],['Bar','Restaurant','Convenience Store','Gaming Lounge','Other']) ? $p['store_type'] : 'Other',
                   'Active'
               ]);
            $newClientId = $db->lastInsertId();
            $db->prepare("UPDATE prospects SET status='Converted', converted_at=NOW(), client_id=? WHERE id=?")->execute([$newClientId, $pid]);
            flashMessage('success', "'{$p['store_name']}' converted to a client!");
        }
        redirect($b . '/pages/clients.php');
    }
}

// ── Filters ──────────────────────────────────────────────────
$statusFilter   = $_GET['status']   ?? '';
$typeFilter     = $_GET['type']     ?? '';
$priorityFilter = $_GET['priority'] ?? '';
$assignedFilter = $_GET['assigned'] ?? '';
$search         = $_GET['search']   ?? '';
$view           = $_GET['view']     ?? 'list'; // list or pipeline

$where  = ['1=1'];
$params = [];
if ($statusFilter)   { $where[] = 'p.status=?';   $params[] = $statusFilter; }
if ($typeFilter)     { $where[] = 'p.store_type=?';$params[] = $typeFilter; }
if ($priorityFilter) { $where[] = 'p.priority=?';  $params[] = $priorityFilter; }
if ($assignedFilter) { $where[] = 'p.assigned_to=?';$params[] = $assignedFilter; }
if ($search)         { $where[] = '(p.store_name LIKE ? OR p.city LIKE ? OR p.contact_name LIKE ? OR p.county LIKE ?)';
                       $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$whereStr = implode(' AND ', $where);

$prospects = $db->prepare("SELECT p.* FROM prospects p WHERE $whereStr ORDER BY
    CASE p.priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END,
    p.follow_up_date ASC, p.created_at DESC");
$prospects->execute($params);
$prospects = $prospects->fetchAll();

// Status counts for pipeline
$statusCounts = [];
$allStatuses  = ['New Lead','Contacted','Interested','Proposal Sent','Negotiating','Converted','Not Interested','No Response'];
foreach ($allStatuses as $s) $statusCounts[$s] = 0;
$allRows = $db->query("SELECT status, COUNT(*) AS c FROM prospects GROUP BY status")->fetchAll();
foreach ($allRows as $r) $statusCounts[$r['status']] = (int)$r['c'];

$totalProspects = array_sum(array_values($statusCounts));
$hotLeads       = $statusCounts['Interested'] + $statusCounts['Negotiating'] + $statusCounts['Proposal Sent'];
$converted      = $statusCounts['Converted'];
$followUpToday  = $db->query("SELECT COUNT(*) FROM prospects WHERE follow_up_date <= CURDATE() AND status NOT IN ('Converted','Not Interested')")->fetchColumn();

// Distinct assigned users for filter
$assignees = $db->query("SELECT DISTINCT assigned_to FROM prospects WHERE assigned_to IS NOT NULL AND assigned_to != '' ORDER BY assigned_to")->fetchAll(PDO::FETCH_COLUMN);

// Edit data
$editProspect = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM prospects WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editProspect = $stmt->fetch();
}

$storeTypes = ['Bar','Restaurant','Convenience Store','Gas Station','Club / Nightclub','Supermarket','Smoke Shop','Other'];
$statuses   = ['New Lead','Contacted','Interested','Proposal Sent','Negotiating','Converted','Not Interested','No Response'];
$priorities = ['High','Medium','Low'];
$sources    = ['Cold Call','Drive By','Referral','Social Media','Website','Walk In','Other'];

$statusColors = [
    'New Lead'       => 'badge-blue',
    'Contacted'      => 'badge-gold',
    'Interested'     => 'badge-green',
    'Proposal Sent'  => 'badge-gold',
    'Negotiating'    => 'badge-gold',
    'Converted'      => 'badge-green',
    'Not Interested' => 'badge-red',
    'No Response'    => 'badge-gray',
];

$priorityColors = ['High'=>'badge-red','Medium'=>'badge-gold','Low'=>'badge-gray'];

require_once '../includes/header.php';
?>

<div class="page-header">
  <div>
    <h1><span>PROSPECTS</span> & LEADS</h1>
    <div style="font-size:0.8rem;color:var(--text-muted);margin-top:3px">
      <?= $totalProspects ?> total &nbsp;·&nbsp;
      <span style="color:var(--green-light)"><?= $hotLeads ?> hot</span> &nbsp;·&nbsp;
      <?= $converted ?> converted
      <?php if ($followUpToday > 0): ?>
        &nbsp;·&nbsp; <span style="color:var(--red-light)"><i class="fa-solid fa-bell"></i> <?= $followUpToday ?> follow-up<?= $followUpToday!=1?'s':'' ?> due</span>
      <?php endif; ?>
    </div>
  </div>
  <div class="btn-group">
    <!-- View toggle -->
    <div style="display:flex;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
      <a href="?view=list<?= $statusFilter?"&status=$statusFilter":'' ?>" class="btn btn-xs <?= $view==='list'?'btn-primary':'btn-outline' ?>" style="border-radius:0;border:none">
        <i class="fa-solid fa-list"></i> List
      </a>
      <a href="?view=pipeline<?= $statusFilter?"&status=$statusFilter":'' ?>" class="btn btn-xs <?= $view==='pipeline'?'btn-primary':'btn-outline' ?>" style="border-radius:0;border:none;border-left:1px solid var(--border)">
        <i class="fa-solid fa-columns"></i> Pipeline
      </a>
    </div>
    <button class="btn btn-primary" onclick="openModal('addProspectModal')">
      <i class="fa-solid fa-plus"></i> Add Prospect
    </button>
  </div>
</div>

<!-- STAT CARDS -->
<div class="stats-grid" style="margin-bottom:22px">
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fa-solid fa-bullseye"></i></div>
    <div class="stat-value"><?= $totalProspects ?></div>
    <div class="stat-label">Total Leads</div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon red"><i class="fa-solid fa-fire"></i></div>
    <div class="stat-value"><?= $hotLeads ?></div>
    <div class="stat-label">Hot Leads</div>
    <div class="stat-sub" style="color:var(--text-muted)">Interested + Negotiating + Proposal</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-clock"></i></div>
    <div class="stat-value"><?= $followUpToday ?></div>
    <div class="stat-label">Follow-Ups Due</div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green"><i class="fa-solid fa-handshake"></i></div>
    <div class="stat-value"><?= $converted ?></div>
    <div class="stat-label">Converted to Clients</div>
  </div>
</div>

<?php if ($followUpToday > 0): ?>
<div class="alert alert-error" style="margin-bottom:18px">
  <i class="fa-solid fa-bell"></i>
  <strong><?= $followUpToday ?> prospect<?= $followUpToday!=1?'s':'' ?></strong> have a follow-up due today or overdue.
  <a href="?status=&view=<?= $view ?>" style="color:inherit;margin-left:8px;text-decoration:underline">View all active</a>
</div>
<?php endif; ?>

<!-- FILTERS -->
<form method="GET" class="filter-bar" style="margin-bottom:18px">
  <input type="hidden" name="view" value="<?= $view ?>">
  <div class="search-wrap">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search name, city, contact, county…">
  </div>
  <select name="status" class="filter-select" onchange="this.form.submit()">
    <option value="">All Statuses</option>
    <?php foreach ($statuses as $s): ?>
    <option value="<?= $s ?>" <?= $statusFilter===$s?'selected':'' ?>><?= $s ?> (<?= $statusCounts[$s] ?>)</option>
    <?php endforeach; ?>
  </select>
  <select name="type" class="filter-select" onchange="this.form.submit()">
    <option value="">All Types</option>
    <?php foreach ($storeTypes as $t): ?>
    <option value="<?= $t ?>" <?= $typeFilter===$t?'selected':'' ?>><?= $t ?></option>
    <?php endforeach; ?>
  </select>
  <select name="priority" class="filter-select" onchange="this.form.submit()">
    <option value="">All Priorities</option>
    <?php foreach ($priorities as $p): ?>
    <option value="<?= $p ?>" <?= $priorityFilter===$p?'selected':'' ?>><?= $p ?></option>
    <?php endforeach; ?>
  </select>
  <?php if (!empty($assignees)): ?>
  <select name="assigned" class="filter-select" onchange="this.form.submit()">
    <option value="">All Reps</option>
    <?php foreach ($assignees as $a): ?>
    <option value="<?= sanitize($a) ?>" <?= $assignedFilter===$a?'selected':'' ?>><?= sanitize($a) ?></option>
    <?php endforeach; ?>
  </select>
  <?php endif; ?>
  <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-filter"></i></button>
  <a href="prospects.php?view=<?= $view ?>" class="btn btn-outline btn-sm">Reset</a>
</form>

<?php if ($view === 'pipeline'): ?>
<!-- ═══════════════ PIPELINE VIEW ═══════════════ -->
<?php
$pipelineStatuses = ['New Lead','Contacted','Interested','Proposal Sent','Negotiating'];
$allForPipeline   = $db->query("SELECT * FROM prospects WHERE status NOT IN ('Converted','Not Interested','No Response') ORDER BY CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END, follow_up_date ASC")->fetchAll();
$byStatus = [];
foreach ($pipelineStatuses as $s) $byStatus[$s] = [];
foreach ($allForPipeline as $p) {
    if (isset($byStatus[$p['status']])) $byStatus[$p['status']][] = $p;
}
$pipelineColors = [
    'New Lead'      => '#2980B9',
    'Contacted'     => '#C9A84C',
    'Interested'    => '#27AE60',
    'Proposal Sent' => '#E67E22',
    'Negotiating'   => '#8E44AD',
];
?>
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;overflow-x:auto;padding-bottom:8px">
  <?php foreach ($pipelineStatuses as $stage): ?>
  <div style="min-width:200px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;padding:8px 12px;background:var(--bg-card);border-radius:var(--radius);border-top:3px solid <?= $pipelineColors[$stage] ?>">
      <div style="font-family:'Barlow Condensed',sans-serif;font-size:0.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-white)"><?= $stage ?></div>
      <div style="background:<?= $pipelineColors[$stage] ?>22;color:<?= $pipelineColors[$stage] ?>;border-radius:12px;padding:2px 8px;font-family:'Barlow Condensed',sans-serif;font-size:0.72rem;font-weight:700"><?= count($byStatus[$stage]) ?></div>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
      <?php foreach ($byStatus[$stage] as $card): ?>
      <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:12px;transition:border-color .15s;cursor:pointer" onclick="window.location='prospect_detail.php?id=<?= $card['id'] ?>'"
           onmouseover="this.style.borderColor='var(--gold-dark)'" onmouseout="this.style.borderColor='var(--border)'">
        <div style="font-weight:600;font-size:0.85rem;margin-bottom:4px;color:var(--text-white)"><?= sanitize($card['store_name']) ?></div>
        <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:8px"><?= sanitize($card['city']) ?> · <?= sanitize($card['store_type']) ?></div>
        <?php if ($card['contact_name']): ?>
        <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:6px"><i class="fa-solid fa-user" style="width:12px"></i> <?= sanitize($card['contact_name']) ?></div>
        <?php endif; ?>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px">
          <span class="badge <?= $priorityColors[$card['priority']] ?>" style="font-size:.6rem"><?= $card['priority'] ?></span>
          <?php if ($card['follow_up_date']): ?>
          <?php $overdue = strtotime($card['follow_up_date']) < time(); ?>
          <span style="font-size:0.65rem;color:<?= $overdue?'var(--red-light)':'var(--text-muted)' ?>">
            <i class="fa-solid fa-calendar"></i> <?= date('M j', strtotime($card['follow_up_date'])) ?>
          </span>
          <?php endif; ?>
        </div>
        <?php if ($card['assigned_to']): ?>
        <div style="margin-top:6px;font-size:0.65rem;color:var(--text-dim)"><i class="fa-solid fa-user-tie"></i> <?= sanitize($card['assigned_to']) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php if (empty($byStatus[$stage])): ?>
      <div style="text-align:center;padding:20px;color:var(--text-dim);font-size:0.75rem;border:1px dashed var(--border);border-radius:var(--radius)">
        No prospects
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php else: ?>
<!-- ═══════════════ LIST VIEW ═══════════════ -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-bullseye"></i> Prospects (<?= count($prospects) ?>)</div>
    <div class="btn-group">
      <a href="prospects.php?status=Converted&view=list" class="btn btn-outline btn-xs"><i class="fa-solid fa-handshake"></i> Converted</a>
    </div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Store Name</th>
          <th>Type</th>
          <th>Location</th>
          <th>County</th>
          <th>Contact</th>
          <th>Phone</th>
          <th>Status</th>
          <th>Priority</th>
          <th>Follow-Up</th>
          <th>Assigned</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($prospects)): ?>
        <tr><td colspan="11">
          <div class="empty-state">
            <i class="fa-solid fa-bullseye"></i>
            <h3>No Prospects Found</h3>
            <p>Add your first lead or adjust the filters.</p>
          </div>
        </td></tr>
        <?php else: ?>
        <?php foreach ($prospects as $p): ?>
        <?php
          $followUpOverdue = $p['follow_up_date'] && strtotime($p['follow_up_date']) < strtotime('today') && !in_array($p['status'],['Converted','Not Interested','No Response']);
        ?>
        <tr style="<?= $followUpOverdue ? 'background:rgba(192,57,43,0.05)' : '' ?>">
          <td>
            <a href="prospect_detail.php?id=<?= $p['id'] ?>" style="color:var(--gold-light);text-decoration:none;font-weight:600">
              <?= sanitize($p['store_name']) ?>
            </a>
            <?php if ($followUpOverdue): ?>
            <span class="badge badge-red" style="font-size:.6rem;margin-left:4px"><i class="fa-solid fa-bell"></i> Due</span>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-blue" style="font-size:.68rem"><?= sanitize($p['store_type']) ?></span></td>
          <td class="td-muted"><?= sanitize($p['city']) ?>, <?= sanitize($p['state']) ?><br><span class="fs-sm" style="color:var(--text-dim)"><?= sanitize($p['zip']) ?></span></td>
          <td class="td-muted"><?= sanitize($p['county']) ?: '—' ?></td>
          <td><?= sanitize($p['contact_name']) ?: '<span class="td-muted">—</span>' ?><br><span class="td-muted fs-sm"><?= sanitize($p['contact_title']) ?></span></td>
          <td class="td-muted"><?= sanitize($p['contact_phone']) ?: '—' ?></td>
          <td>
            <span class="badge <?= $statusColors[$p['status']] ?? 'badge-gray' ?>"><?= sanitize($p['status']) ?></span>
          </td>
          <td><span class="badge <?= $priorityColors[$p['priority']] ?>"><?= $p['priority'] ?></span></td>
          <td class="<?= $followUpOverdue?'text-red':'td-muted' ?> fs-sm">
            <?= $p['follow_up_date'] ? date('M j, Y', strtotime($p['follow_up_date'])) : '—' ?>
          </td>
          <td class="td-muted fs-sm"><?= sanitize($p['assigned_to']) ?: '—' ?></td>
          <td>
            <div class="btn-group">
              <a href="prospect_detail.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-xs"><i class="fa-solid fa-eye"></i></a>
              <a href="prospects.php?edit=<?= $p['id'] ?>" class="btn btn-outline btn-xs"><i class="fa-solid fa-pen"></i></a>
              <?php if (!in_array($p['status'],['Converted','Not Interested'])): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Convert <?= addslashes($p['store_name']) ?> to a client?')">
                <input type="hidden" name="action" value="convert">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button class="btn btn-outline btn-xs" style="color:var(--green-light);border-color:rgba(39,174,96,0.3)" title="Convert to Client"><i class="fa-solid fa-handshake"></i></button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete <?= addslashes($p['store_name']) ?>?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
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
<?php endif; ?>

<!-- ══ ADD PROSPECT MODAL ══ -->
<div class="modal-overlay" id="addProspectModal">
  <div class="modal" style="max-width:800px">
    <div class="modal-header">
      <div class="modal-title"><i class="fa-solid fa-bullseye"></i> Add New Prospect</div>
      <button class="modal-close" onclick="closeModal('addProspectModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <?php $formData = null; include '_prospect_form.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addProspectModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Prospect</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ EDIT PROSPECT MODAL ══ -->
<?php if ($editProspect): ?>
<div class="modal-overlay open" id="editProspectModal">
  <div class="modal" style="max-width:800px">
    <div class="modal-header">
      <div class="modal-title"><i class="fa-solid fa-pen"></i> Edit — <?= sanitize($editProspect['store_name']) ?></div>
      <button class="modal-close" onclick="window.location='prospects.php'"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $editProspect['id'] ?>">
      <div class="modal-body">
        <?php $formData = $editProspect; include '_prospect_form.php'; ?>
      </div>
      <div class="modal-footer">
        <a href="prospects.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update Prospect</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
