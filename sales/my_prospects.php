<?php
require_once __DIR__ . '/includes/sales_header.php';
$pageTitle = 'My Prospects';
$repId = $rep['id'];
$b     = SALES_URL;

// POST: update status, log note, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'quick_status') {
        $db->prepare("UPDATE prospects SET status=?, follow_up_date=? WHERE id=? AND rep_id=?")
           ->execute([$_POST['status'], ($_POST['follow_up_date']?:null), (int)$_POST['id'], $repId]);
        flashMessage('success', 'Status updated.');
        redirect($b . '/my_prospects.php');
    }
    if ($action === 'add_note') {
        $db->prepare("INSERT INTO prospect_notes (prospect_id,note_type,note_text,created_by) VALUES (?,?,?,?)")
           ->execute([(int)$_POST['prospect_id'], $_POST['note_type'], trim($_POST['note_text']), $rep['name']]);
        if (!empty($_POST['update_last_contact'])) {
            $db->prepare("UPDATE prospects SET last_contact=CURDATE() WHERE id=? AND rep_id=?")->execute([(int)$_POST['prospect_id'], $repId]);
        }
        flashMessage('success', 'Activity logged.');
        redirect($b . '/my_prospects.php');
    }
    if ($action === 'delete') {
        $db->prepare("DELETE FROM prospects WHERE id=? AND rep_id=?")->execute([(int)$_POST['id'], $repId]);
        flashMessage('success', 'Lead deleted.');
        redirect($b . '/my_prospects.php');
    }
}

$view    = $_GET['view']   ?? 'list';
$filter  = $_GET['filter'] ?? '';
$search  = $_GET['search'] ?? '';
$status  = $_GET['status'] ?? '';

$where  = ['p.rep_id=?'];
$params = [$repId];

if ($search) { $where[] = '(p.store_name LIKE ? OR p.city LIKE ? OR p.contact_name LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($status) { $where[] = 'p.status=?'; $params[] = $status; }
if ($filter === 'followup') { $where[] = "p.follow_up_date<=CURDATE() AND p.status NOT IN ('Converted','Not Interested','No Response')"; }
if ($filter === 'hot') { $where[] = "p.status IN ('Interested','Negotiating','Proposal Sent')"; }

$prospects = $db->prepare("SELECT p.* FROM prospects p WHERE " . implode(' AND ', $where) . " ORDER BY CASE p.priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END, p.follow_up_date ASC");
$prospects->execute($params);
$prospects = $prospects->fetchAll();

$statusCounts = [];
$allStatuses  = ['New Lead','Contacted','Interested','Proposal Sent','Negotiating','Converted','Not Interested','No Response'];
foreach ($allStatuses as $s) $statusCounts[$s] = 0;
$sc = $db->prepare("SELECT status, COUNT(*) AS c FROM prospects WHERE rep_id=? GROUP BY status");
$sc->execute([$repId]);
foreach ($sc->fetchAll() as $r) $statusCounts[$r['status']] = (int)$r['c'];

$statusColors   = ['New Lead'=>'badge-blue','Contacted'=>'badge-gold','Interested'=>'badge-green','Proposal Sent'=>'badge-orange','Negotiating'=>'badge-orange','Converted'=>'badge-green','Not Interested'=>'badge-red','No Response'=>'badge-gray'];
$priorityColors = ['High'=>'badge-red','Medium'=>'badge-gold','Low'=>'badge-gray'];
$stageColors    = ['New Lead'=>'#3B82F6','Contacted'=>'#C9A84C','Interested'=>'#22C55E','Proposal Sent'=>'#EA580C','Negotiating'=>'#7C3AED'];
?>

<div class="page-header">
  <div>
    <h1><span class="accent">My</span> Prospects</h1>
    <div class="page-subtitle"><?= array_sum(array_values($statusCounts)) ?> total leads in your pipeline</div>
  </div>
  <div class="btn-group">
    <div style="display:flex;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
      <a href="?view=list" class="btn btn-xs <?= $view==='list'?'btn-primary':'btn-ghost' ?>" style="border-radius:0;border:none"><i class="fa-solid fa-list"></i> List</a>
      <a href="?view=pipeline" class="btn btn-xs <?= $view==='pipeline'?'btn-primary':'btn-ghost' ?>" style="border-radius:0;border:none;border-left:1px solid var(--border)"><i class="fa-solid fa-columns"></i> Pipeline</a>
    </div>
    <a href="<?= $b ?>/add_prospect.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Lead</a>
  </div>
</div>

<!-- QUICK FILTER PILLS -->
<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
  <a href="?view=<?= $view ?>" class="btn btn-xs <?= !$filter&&!$status?'btn-primary':'btn-ghost' ?>">All (<?= array_sum(array_values($statusCounts)) ?>)</a>
  <a href="?filter=followup&view=<?= $view ?>" class="btn btn-xs <?= $filter==='followup'?'btn-primary':'btn-ghost' ?>"><i class="fa-solid fa-bell"></i> Follow-Up Due</a>
  <a href="?filter=hot&view=<?= $view ?>" class="btn btn-xs <?= $filter==='hot'?'btn-primary':'btn-ghost' ?>"><i class="fa-solid fa-fire"></i> Hot Leads</a>
  <?php foreach (['New Lead','Contacted','Interested','Converted'] as $qs): ?>
  <a href="?status=<?= urlencode($qs) ?>&view=<?= $view ?>" class="btn btn-xs <?= $status===$qs?'btn-primary':'btn-ghost' ?>"><?= $qs ?> (<?= $statusCounts[$qs] ?>)</a>
  <?php endforeach; ?>
</div>

<!-- SEARCH -->
<form method="GET" style="display:flex;gap:10px;margin-bottom:18px">
  <input type="hidden" name="view" value="<?= $view ?>">
  <div style="position:relative;flex:1;max-width:400px">
    <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.85rem"></i>
    <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Search store, city, contact…" style="width:100%;padding:9px 14px 9px 36px;border:1px solid var(--border);border-radius:var(--radius);background:var(--bg-white);font-size:.88rem;color:var(--text-dark)">
  </div>
  <button type="submit" class="btn btn-outline btn-sm"><i class="fa-solid fa-search"></i></button>
  <a href="?view=<?= $view ?>" class="btn btn-ghost btn-sm">Reset</a>
</form>

<?php if ($view === 'pipeline'): ?>
<!-- PIPELINE KANBAN -->
<?php
$pipelineStatuses = ['New Lead','Contacted','Interested','Proposal Sent','Negotiating'];
$byStage = [];
foreach ($pipelineStatuses as $s) $byStage[$s] = [];
$all = $db->prepare("SELECT * FROM prospects WHERE rep_id=? AND status NOT IN ('Converted','Not Interested','No Response') ORDER BY CASE priority WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 ELSE 3 END, follow_up_date ASC");
$all->execute([$repId]);
foreach ($all->fetchAll() as $p) { if (isset($byStage[$p['status']])) $byStage[$p['status']][] = $p; }
?>
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;overflow-x:auto;padding-bottom:8px">
  <?php foreach ($pipelineStatuses as $stage): ?>
  <div style="min-width:190px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;padding:8px 12px;background:var(--bg-white);border-radius:var(--radius);border-top:3px solid <?= $stageColors[$stage] ?>;box-shadow:var(--shadow-sm)">
      <div style="font-family:'Barlow Condensed',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-mid)"><?= $stage ?></div>
      <div style="background:<?= $stageColors[$stage] ?>18;color:<?= $stageColors[$stage] ?>;border-radius:12px;padding:2px 8px;font-size:.7rem;font-weight:700;font-family:'Barlow Condensed',sans-serif"><?= count($byStage[$stage]) ?></div>
    </div>
    <div style="display:flex;flex-direction:column;gap:8px">
      <?php foreach ($byStage[$stage] as $card): ?>
      <?php $overdue = $card['follow_up_date'] && strtotime($card['follow_up_date']) < time(); ?>
      <div style="background:var(--bg-white);border:1px solid <?= $overdue?'rgba(220,38,38,.35)':'var(--border)' ?>;border-radius:var(--radius);padding:12px;cursor:pointer;transition:box-shadow .15s" onclick="window.location='prospect_detail.php?id=<?= $card['id'] ?>'" onmouseover="this.style.boxShadow='var(--shadow)'" onmouseout="this.style.boxShadow=''">
        <div style="font-weight:600;font-size:.85rem;color:var(--text-dark);margin-bottom:3px"><?= sanitize($card['store_name']) ?></div>
        <div class="fs-xs text-muted" style="margin-bottom:8px"><?= sanitize($card['city']) ?> · <?= sanitize($card['store_type']) ?></div>
        <?php if ($card['contact_name']): ?>
        <div class="fs-xs text-muted" style="margin-bottom:6px"><i class="fa-solid fa-user" style="width:12px"></i> <?= sanitize($card['contact_name']) ?></div>
        <?php endif; ?>
        <div style="display:flex;align-items:center;justify-content:space-between">
          <span class="badge <?= $priorityColors[$card['priority']] ?>" style="font-size:.58rem"><?= $card['priority'] ?></span>
          <?php if ($card['follow_up_date']): ?>
          <span class="fs-xs <?= $overdue?'text-red fw-600':'text-muted' ?>"><i class="fa-solid fa-calendar"></i> <?= date('M j', strtotime($card['follow_up_date'])) ?></span>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($byStage[$stage])): ?>
      <div style="text-align:center;padding:18px;color:var(--text-light);font-size:.75rem;border:1px dashed var(--border);border-radius:var(--radius)">Empty</div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php else: ?>
<!-- LIST VIEW -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-bullseye"></i> <?= count($prospects) ?> Prospects</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Store</th><th>Type</th><th>City</th><th>County</th><th>Contact</th><th>Status</th><th>Priority</th><th>Follow-Up</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($prospects)): ?>
        <tr><td colspan="9"><div class="empty-state"><i class="fa-solid fa-bullseye"></i><h3>No Leads Found</h3><p>Add your first prospect or adjust the filters.</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($prospects as $p): ?>
        <?php $overdue = $p['follow_up_date'] && strtotime($p['follow_up_date']) < strtotime('today') && !in_array($p['status'],['Converted','Not Interested','No Response']); ?>
        <tr style="<?= $overdue?'background:rgba(220,38,38,.03)':'' ?>">
          <td>
            <a href="prospect_detail.php?id=<?= $p['id'] ?>" style="color:var(--gold-dark);text-decoration:none;font-weight:600"><?= sanitize($p['store_name']) ?></a>
            <?php if ($overdue): ?><span class="badge badge-red" style="font-size:.58rem;margin-left:4px"><i class="fa-solid fa-bell"></i></span><?php endif; ?>
          </td>
          <td><span class="badge badge-blue" style="font-size:.62rem"><?= sanitize($p['store_type']) ?></span></td>
          <td class="td-muted"><?= sanitize($p['city']) ?>, <?= sanitize($p['state']) ?></td>
          <td class="td-muted fs-sm"><?= sanitize($p['county']) ?: '—' ?></td>
          <td><?= sanitize($p['contact_name']) ?: '<span class="td-muted">—</span>' ?><br><span class="td-muted fs-sm"><?= sanitize($p['contact_phone']) ?></span></td>
          <td><span class="badge <?= $statusColors[$p['status']] ?? 'badge-gray' ?>" style="font-size:.62rem"><?= $p['status'] ?></span></td>
          <td><span class="badge <?= $priorityColors[$p['priority']] ?>" style="font-size:.62rem"><?= $p['priority'] ?></span></td>
          <td class="<?= $overdue?'text-red fw-600':'td-muted' ?> fs-sm"><?= $p['follow_up_date'] ? date('M j, Y', strtotime($p['follow_up_date'])) : '—' ?></td>
          <td>
            <div class="btn-group">
              <a href="prospect_detail.php?id=<?= $p['id'] ?>" class="btn btn-outline btn-xs"><i class="fa-solid fa-eye"></i></a>
              <button class="btn btn-outline btn-xs" onclick="openModal('qs<?= $p['id'] ?>')"><i class="fa-solid fa-sliders"></i></button>
              <button class="btn btn-outline btn-xs" onclick="openModal('note<?= $p['id'] ?>')"><i class="fa-solid fa-plus"></i></button>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this lead?')">
                <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                <button class="btn btn-danger btn-xs"><i class="fa-solid fa-trash"></i></button>
              </form>
            </div>
            <!-- Quick Status Modal -->
            <div class="modal-overlay" id="qs<?= $p['id'] ?>">
              <div class="modal" style="max-width:380px">
                <div class="modal-header"><div class="modal-title"><i class="fa-solid fa-sliders"></i> Update Status</div><button class="modal-close" onclick="closeModal('qs<?= $p['id'] ?>')"><i class="fa-solid fa-xmark"></i></button></div>
                <form method="POST">
                  <input type="hidden" name="action" value="quick_status"><input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <div class="modal-body">
                    <div class="form-grid" style="grid-template-columns:1fr">
                      <div class="form-group"><label>Status</label>
                        <select name="status"><?php foreach ($allStatuses as $s): ?><option value="<?= $s ?>" <?= $p['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select>
                      </div>
                      <div class="form-group"><label>Follow-Up Date</label><input type="date" name="follow_up_date" value="<?= $p['follow_up_date'] ?>"></div>
                    </div>
                  </div>
                  <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('qs<?= $p['id'] ?>')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Update</button></div>
                </form>
              </div>
            </div>
            <!-- Quick Note Modal -->
            <div class="modal-overlay" id="note<?= $p['id'] ?>">
              <div class="modal" style="max-width:420px">
                <div class="modal-header"><div class="modal-title"><i class="fa-solid fa-plus"></i> Log Activity</div><button class="modal-close" onclick="closeModal('note<?= $p['id'] ?>')"><i class="fa-solid fa-xmark"></i></button></div>
                <form method="POST">
                  <input type="hidden" name="action" value="add_note"><input type="hidden" name="prospect_id" value="<?= $p['id'] ?>">
                  <div class="modal-body">
                    <div class="form-grid" style="grid-template-columns:1fr">
                      <div class="form-group"><label>Type</label><select name="note_type"><?php foreach (['Call','Email','Visit','Follow Up','Other'] as $t): ?><option><?= $t ?></option><?php endforeach; ?></select></div>
                      <div class="form-group"><label>Notes *</label><textarea name="note_text" rows="3" required placeholder="What happened?"></textarea></div>
                      <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;color:var(--text-muted);cursor:pointer"><input type="checkbox" name="update_last_contact" value="1" checked> Update last contact to today</label>
                    </div>
                  </div>
                  <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('note<?= $p['id'] ?>')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save</button></div>
                </form>
              </div>
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

<?php require_once __DIR__ . '/includes/sales_footer.php'; ?>
