<?php
require_once __DIR__ . '/includes/sales_header.php';
$repId = $rep['id'];
$b     = SALES_URL;

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect($b . '/my_prospects.php');

// Only allow rep to see their own prospects
$prospect = $db->prepare("SELECT * FROM prospects WHERE id=? AND rep_id=?");
$prospect->execute([$id, $repId]);
$prospect = $prospect->fetch();
if (!$prospect) { flashMessage('error','Prospect not found.'); redirect($b.'/my_prospects.php'); }

$pageTitle = $prospect['store_name'];

$notes = $db->prepare("SELECT * FROM prospect_notes WHERE prospect_id=? ORDER BY created_at DESC");
$notes->execute([$id]); $notes = $notes->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_note') {
        $db->prepare("INSERT INTO prospect_notes (prospect_id,note_type,note_text,created_by) VALUES (?,?,?,?)")
           ->execute([$id, $_POST['note_type'], trim($_POST['note_text']), $rep['name']]);
        if (!empty($_POST['update_last_contact'])) $db->prepare("UPDATE prospects SET last_contact=CURDATE() WHERE id=?")->execute([$id]);
        flashMessage('success', 'Activity logged!');
        redirect($b . '/prospect_detail.php?id=' . $id);
    }
    if ($action === 'update_status') {
        $db->prepare("UPDATE prospects SET status=?, follow_up_date=?, priority=? WHERE id=? AND rep_id=?")
           ->execute([$_POST['status'], ($_POST['follow_up_date']?:null), $_POST['priority'], $id, $repId]);
        flashMessage('success', 'Updated!');
        redirect($b . '/prospect_detail.php?id=' . $id);
    }
    if ($action === 'edit_prospect') {
        $db->prepare("UPDATE prospects SET store_name=?,store_type=?,address=?,city=?,state=?,zip=?,county=?,contact_name=?,contact_title=?,contact_phone=?,contact_email=?,machines_wanted=?,notes=? WHERE id=? AND rep_id=?")
           ->execute([
               trim($_POST['store_name']), $_POST['store_type'], trim($_POST['address']),
               trim($_POST['city']), trim($_POST['state']??'TX'), trim($_POST['zip']),
               trim($_POST['county']), trim($_POST['contact_name']), trim($_POST['contact_title']),
               trim($_POST['contact_phone']), trim($_POST['contact_email']),
               !empty($_POST['machines_wanted'])?(int)$_POST['machines_wanted']:null,
               trim($_POST['notes']), $id, $repId
           ]);
        flashMessage('success', 'Prospect updated!');
        redirect($b . '/prospect_detail.php?id=' . $id);
    }
}

$allStatuses = ['New Lead','Contacted','Interested','Proposal Sent','Negotiating','Converted','Not Interested','No Response'];
$storeTypes  = ['Bar','Restaurant','Convenience Store','Gas Station','Club / Nightclub','Supermarket','Smoke Shop','Other'];
$noteIcons   = ['Call'=>'fa-phone','Email'=>'fa-envelope','Visit'=>'fa-location-dot','Follow Up'=>'fa-clock','Other'=>'fa-note-sticky'];
$noteColors  = ['Call'=>'#3B82F6','Email'=>'#22C55E','Visit'=>'#C9A84C','Follow Up'=>'#EA580C','Other'=>'#9CA3AF'];
$statusColors= ['New Lead'=>'badge-blue','Contacted'=>'badge-gold','Interested'=>'badge-green','Proposal Sent'=>'badge-orange','Negotiating'=>'badge-orange','Converted'=>'badge-green','Not Interested'=>'badge-red','No Response'=>'badge-gray'];
$priColors   = ['High'=>'badge-red','Medium'=>'badge-gold','Low'=>'badge-gray'];
$overdue     = $prospect['follow_up_date'] && strtotime($prospect['follow_up_date']) < strtotime('today') && !in_array($prospect['status'],['Converted','Not Interested','No Response']);
?>

<div class="page-header">
  <div>
    <a href="<?= $b ?>/my_prospects.php" style="color:var(--text-muted);text-decoration:none;font-size:.8rem"><i class="fa-solid fa-arrow-left"></i> My Prospects</a>
    <h1 style="margin-top:4px"><span><?= sanitize($prospect['store_name']) ?></span></h1>
    <div style="display:flex;gap:8px;align-items:center;margin-top:6px;flex-wrap:wrap">
      <span class="badge <?= $statusColors[$prospect['status']] ?>"><?= $prospect['status'] ?></span>
      <span class="badge <?= $priColors[$prospect['priority']] ?>"><?= $prospect['priority'] ?> Priority</span>
      <span class="badge badge-blue"><?= sanitize($prospect['store_type']) ?></span>
      <span class="td-muted fs-sm"><?= sanitize($prospect['city']) ?>, <?= sanitize($prospect['state']) ?></span>
      <?php if ($overdue): ?><span class="badge badge-red"><i class="fa-solid fa-bell"></i> Follow-Up Overdue</span><?php endif; ?>
    </div>
  </div>
  <div class="btn-group">
    <button class="btn btn-outline" onclick="openModal('editModal')"><i class="fa-solid fa-pen"></i> Edit</button>
    <button class="btn btn-primary" onclick="openModal('addNoteModal')"><i class="fa-solid fa-plus"></i> Log Activity</button>
  </div>
</div>

<div class="grid-7-5" style="gap:20px;align-items:start">
  <!-- LEFT -->
  <div style="display:flex;flex-direction:column;gap:18px">

    <!-- Info -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-store"></i> Store & Contact</div></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px">
          <?php
          $items = [
            'Address'  => sanitize($prospect['address']).'<br>'.sanitize($prospect['city']).', '.sanitize($prospect['state']).' '.sanitize($prospect['zip']),
            'County'   => sanitize($prospect['county']) ?: '—',
            'Type'     => sanitize($prospect['store_type']),
            'Contact'  => sanitize($prospect['contact_name']) ?: '—',
            'Title'    => sanitize($prospect['contact_title']) ?: '—',
            'Phone'    => $prospect['contact_phone'] ? '<a href="tel:'.sanitize($prospect['contact_phone']).'" style="color:var(--gold-dark);text-decoration:none">'.sanitize($prospect['contact_phone']).'</a>' : '—',
            'Email'    => $prospect['contact_email'] ? '<a href="mailto:'.sanitize($prospect['contact_email']).'" style="color:var(--gold-dark);text-decoration:none;font-size:.82rem">'.sanitize($prospect['contact_email']).'</a>' : '—',
            'Machines' => $prospect['machines_wanted'] ?: '—',
            'Source'   => sanitize($prospect['source']),
          ];
          foreach ($items as $label => $val): ?>
          <div>
            <div style="font-family:'Barlow Condensed',sans-serif;font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);margin-bottom:3px"><?= $label ?></div>
            <div class="fs-sm fw-600"><?= $val ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if ($prospect['notes']): ?>
        <hr class="divider">
        <div style="font-size:.85rem;color:var(--text-muted);line-height:1.6"><?= nl2br(sanitize($prospect['notes'])) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Activity Log -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-timeline"></i> Activity Log (<?= count($notes) ?>)</div>
        <button class="btn btn-primary btn-sm" onclick="openModal('addNoteModal')"><i class="fa-solid fa-plus"></i> Log</button>
      </div>
      <div class="card-body" style="padding:<?= empty($notes)?'0':'18px' ?>">
        <?php if (empty($notes)): ?>
        <div class="empty-state" style="padding:28px"><i class="fa-solid fa-timeline"></i><h3>No Activity Yet</h3><p>Log your first call or visit.</p></div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:0">
          <?php foreach ($notes as $i => $n): ?>
          <div style="display:flex;gap:12px;padding-bottom:<?= $i<count($notes)-1?'18':'0' ?>px">
            <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;width:30px">
              <div style="width:30px;height:30px;border-radius:50%;background:<?= $noteColors[$n['note_type']] ?>18;border:2px solid <?= $noteColors[$n['note_type']] ?>;display:flex;align-items:center;justify-content:center">
                <i class="fa-solid <?= $noteIcons[$n['note_type']] ?>" style="color:<?= $noteColors[$n['note_type']] ?>;font-size:.68rem"></i>
              </div>
              <?php if ($i < count($notes)-1): ?><div style="width:2px;flex:1;background:var(--border);margin-top:5px;min-height:16px"></div><?php endif; ?>
            </div>
            <div style="flex:1;padding-top:4px">
              <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                <span style="font-size:.78rem;font-weight:700;color:<?= $noteColors[$n['note_type']] ?>"><?= $n['note_type'] ?></span>
                <span class="fs-xs text-muted"><?= date('M j, Y g:ia', strtotime($n['created_at'])) ?></span>
              </div>
              <div style="font-size:.84rem;color:var(--text-muted);background:var(--bg);padding:9px 12px;border-radius:var(--radius);border:1px solid var(--border);line-height:1.55"><?= nl2br(sanitize($n['note_text'])) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- RIGHT -->
  <div style="display:flex;flex-direction:column;gap:18px;position:sticky;top:calc(var(--topbar-h)+16px)">

    <!-- Update Status -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-sliders"></i> Update Lead</div></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_status">
          <div class="form-group" style="margin-bottom:12px"><label>Status</label>
            <select name="status"><?php foreach ($allStatuses as $s): ?><option value="<?= $s ?>" <?= $prospect['status']===$s?'selected':'' ?>><?= $s ?></option><?php endforeach; ?></select>
          </div>
          <div class="form-group" style="margin-bottom:12px"><label>Priority</label>
            <select name="priority"><option value="High" <?= $prospect['priority']==='High'?'selected':'' ?>>High</option><option value="Medium" <?= $prospect['priority']==='Medium'?'selected':'' ?>>Medium</option><option value="Low" <?= $prospect['priority']==='Low'?'selected':'' ?>>Low</option></select>
          </div>
          <div class="form-group" style="margin-bottom:16px"><label>Follow-Up Date</label>
            <input type="date" name="follow_up_date" value="<?= sanitize($prospect['follow_up_date']??'') ?>">
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center"><i class="fa-solid fa-check"></i> Save Changes</button>
        </form>
      </div>
    </div>

    <!-- Quick Log -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-bolt"></i> Quick Log</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:7px">
        <?php foreach (['Call','Email','Visit','Follow Up'] as $qt): ?>
        <form method="POST">
          <input type="hidden" name="action" value="add_note">
          <input type="hidden" name="note_type" value="<?= $qt ?>">
          <input type="hidden" name="update_last_contact" value="1">
          <input type="hidden" name="note_text" value="<?= $qt ?> completed on <?= date('M j, Y') ?>">
          <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;justify-content:flex-start">
            <i class="fa-solid <?= $noteIcons[$qt] ?>" style="color:<?= $noteColors[$qt] ?>;width:16px"></i> Log <?= $qt ?>
          </button>
        </form>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Summary -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-circle-info"></i> Summary</div></div>
      <div class="card-body">
        <?php $summaryItems = [
          'Last Contact'  => $prospect['last_contact']   ? formatDate($prospect['last_contact'])   : '—',
          'Follow-Up'     => $prospect['follow_up_date'] ? formatDate($prospect['follow_up_date']) : '—',
          'Added'         => formatDate($prospect['created_at']),
          'Activities'    => count($notes),
        ]; foreach ($summaryItems as $label => $val): ?>
        <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid var(--border)">
          <span class="fs-sm text-muted"><?= $label ?></span>
          <span class="fs-sm fw-600"><?= $val ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- LOG ACTIVITY MODAL -->
<div class="modal-overlay" id="addNoteModal">
  <div class="modal" style="max-width:480px">
    <div class="modal-header"><div class="modal-title"><i class="fa-solid fa-plus"></i> Log Activity</div><button class="modal-close" onclick="closeModal('addNoteModal')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="add_note">
      <div class="modal-body">
        <div class="form-grid" style="grid-template-columns:1fr">
          <div class="form-group"><label>Type *</label><select name="note_type"><?php foreach (['Call','Email','Visit','Follow Up','Other'] as $t): ?><option><?= $t ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label>What happened? *</label><textarea name="note_text" rows="4" required placeholder="Key points, response, next steps…"></textarea></div>
          <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;color:var(--text-muted);cursor:pointer"><input type="checkbox" name="update_last_contact" value="1" checked> Update last contact date to today</label>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('addNoteModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Activity</button></div>
    </form>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
  <div class="modal" style="max-width:700px">
    <div class="modal-header"><div class="modal-title"><i class="fa-solid fa-pen"></i> Edit Prospect</div><button class="modal-close" onclick="closeModal('editModal')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="edit_prospect">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group col-span-2"><label>Store Name *</label><input type="text" name="store_name" value="<?= sanitize($prospect['store_name']) ?>" required></div>
          <div class="form-group"><label>Store Type</label><select name="store_type"><?php foreach ($storeTypes as $t): ?><option value="<?= $t ?>" <?= $prospect['store_type']===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label>Machines Wanted</label><input type="number" name="machines_wanted" value="<?= $prospect['machines_wanted'] ?>" min="0"></div>
          <div class="form-group col-span-2"><label>Address</label><input type="text" name="address" value="<?= sanitize($prospect['address']) ?>"></div>
          <div class="form-group"><label>City</label><input type="text" name="city" value="<?= sanitize($prospect['city']) ?>"></div>
          <div class="form-group"><label>County</label><input type="text" name="county" value="<?= sanitize($prospect['county']) ?>"></div>
          <div class="form-group"><label>State</label><input type="text" name="state" value="<?= sanitize($prospect['state']) ?>" maxlength="2"></div>
          <div class="form-group"><label>ZIP</label><input type="text" name="zip" value="<?= sanitize($prospect['zip']) ?>"></div>
          <div class="form-group"><label>Contact Name</label><input type="text" name="contact_name" value="<?= sanitize($prospect['contact_name']) ?>"></div>
          <div class="form-group"><label>Title</label><input type="text" name="contact_title" value="<?= sanitize($prospect['contact_title']) ?>"></div>
          <div class="form-group"><label>Phone</label><input type="text" name="contact_phone" value="<?= sanitize($prospect['contact_phone']) ?>"></div>
          <div class="form-group"><label>Email</label><input type="email" name="contact_email" value="<?= sanitize($prospect['contact_email']) ?>"></div>
          <div class="form-group col-span-2"><label>Notes</label><textarea name="notes"><?= sanitize($prospect['notes']) ?></textarea></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button></div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/sales_footer.php'; ?>
