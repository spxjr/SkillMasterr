<?php
require_once '../includes/config.php';
$db = getDB();
$b  = BASE_URL;

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect($b . '/pages/prospects.php');

$prospect = $db->prepare("SELECT * FROM prospects WHERE id=?");
$prospect->execute([$id]);
$prospect = $prospect->fetch();
if (!$prospect) redirect($b . '/pages/prospects.php');

$pageTitle = $prospect['store_name'];

// Activity notes
$notes = $db->prepare("SELECT * FROM prospect_notes WHERE prospect_id=? ORDER BY created_at DESC");
$notes->execute([$id]);
$notes = $notes->fetchAll();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_note') {
        $db->prepare("INSERT INTO prospect_notes (prospect_id,note_type,note_text,created_by) VALUES (?,?,?,?)")
           ->execute([$id, $_POST['note_type'], trim($_POST['note_text']), trim($_POST['created_by'])]);
        // Update last contact date
        if (!empty($_POST['update_last_contact'])) {
            $db->prepare("UPDATE prospects SET last_contact=CURDATE() WHERE id=?")->execute([$id]);
        }
        flashMessage('success', 'Activity logged.');
        redirect($b . '/pages/prospect_detail.php?id=' . $id);
    }

    if ($action === 'update_status') {
        $newStatus = $_POST['status'] ?? '';
        $followUp  = !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null;
        $db->prepare("UPDATE prospects SET status=?, follow_up_date=? WHERE id=?")->execute([$newStatus, $followUp, $id]);
        flashMessage('success', 'Status updated to: ' . $newStatus);
        redirect($b . '/pages/prospect_detail.php?id=' . $id);
    }

    if ($action === 'convert') {
        $db->prepare("INSERT INTO clients (business_name,address,city,state,zip,phone,contact_name,contact_title,contact_phone,contact_email,venue_type,status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
           ->execute([
               $prospect['store_name'], $prospect['address'], $prospect['city'], $prospect['state'],
               $prospect['zip'], $prospect['contact_phone'], $prospect['contact_name'],
               $prospect['contact_title'], $prospect['contact_phone'], $prospect['contact_email'],
               in_array($prospect['store_type'],['Bar','Restaurant','Convenience Store','Gaming Lounge','Other']) ? $prospect['store_type'] : 'Other',
               'Active'
           ]);
        $newClientId = $db->lastInsertId();
        $db->prepare("UPDATE prospects SET status='Converted', converted_at=NOW(), client_id=? WHERE id=?")->execute([$newClientId, $id]);
        flashMessage('success', "'{$prospect['store_name']}' converted to a client! Set up their machines and contract.");
        redirect($b . '/pages/client_detail.php?id=' . $newClientId);
    }

    if ($action === 'delete_note') {
        $db->prepare("DELETE FROM prospect_notes WHERE id=? AND prospect_id=?")->execute([(int)$_POST['note_id'], $id]);
        redirect($b . '/pages/prospect_detail.php?id=' . $id);
    }
}

$statuses = ['New Lead','Contacted','Interested','Proposal Sent','Negotiating','Converted','Not Interested','No Response'];
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
$noteTypeIcons  = ['Call'=>'fa-phone','Email'=>'fa-envelope','Visit'=>'fa-location-dot','Follow Up'=>'fa-clock','Other'=>'fa-note-sticky'];
$noteTypeColors = ['Call'=>'#2980B9','Email'=>'#27AE60','Visit'=>'#C9A84C','Follow Up'=>'#E67E22','Other'=>'#7A8099'];

$followUpOverdue = $prospect['follow_up_date'] && strtotime($prospect['follow_up_date']) < strtotime('today')
                   && !in_array($prospect['status'],['Converted','Not Interested','No Response']);

require_once '../includes/header.php';
?>

<!-- BACK + HEADER -->
<div class="page-header">
  <div>
    <a href="prospects.php" style="color:var(--text-muted);text-decoration:none;font-size:0.8rem"><i class="fa-solid fa-arrow-left"></i> Back to Prospects</a>
    <h1 style="margin-top:4px"><span><?= sanitize($prospect['store_name']) ?></span></h1>
    <div style="display:flex;gap:8px;align-items:center;margin-top:6px;flex-wrap:wrap">
      <span class="badge <?= $statusColors[$prospect['status']] ?? 'badge-gray' ?>"><?= $prospect['status'] ?></span>
      <span class="badge <?= $priorityColors[$prospect['priority']] ?>"><?= $prospect['priority'] ?> Priority</span>
      <span class="badge badge-blue"><?= sanitize($prospect['store_type']) ?></span>
      <span class="td-muted fs-sm"><?= sanitize($prospect['city']) ?>, <?= sanitize($prospect['state']) ?></span>
      <?php if ($followUpOverdue): ?>
      <span class="badge badge-red"><i class="fa-solid fa-bell"></i> Follow-Up Overdue</span>
      <?php endif; ?>
    </div>
  </div>
  <div class="btn-group">
    <a href="prospects.php?edit=<?= $id ?>" class="btn btn-outline"><i class="fa-solid fa-pen"></i> Edit</a>
    <?php if (!in_array($prospect['status'],['Converted','Not Interested'])): ?>
    <form method="POST" style="display:inline" onsubmit="return confirm('Convert to a client? This will create a new client record.')">
      <input type="hidden" name="action" value="convert">
      <button class="btn btn-primary" type="submit"><i class="fa-solid fa-handshake"></i> Convert to Client</button>
    </form>
    <?php endif; ?>
    <?php if ($prospect['status']==='Converted' && $prospect['client_id']): ?>
    <a href="client_detail.php?id=<?= $prospect['client_id'] ?>" class="btn btn-primary"><i class="fa-solid fa-building-user"></i> View Client</a>
    <?php endif; ?>
  </div>
</div>

<div class="grid-7-5" style="gap:22px;align-items:start">

  <!-- LEFT: Details -->
  <div style="display:flex;flex-direction:column;gap:18px">

    <!-- Store Info -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-store"></i> Store Details</div>
      </div>
      <div class="card-body">
        <div class="detail-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px">
          <div><label style="font-family:'Barlow Condensed',sans-serif;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);display:block;margin-bottom:3px">Store Name</label><div style="font-weight:600"><?= sanitize($prospect['store_name']) ?></div></div>
          <div><label style="font-family:'Barlow Condensed',sans-serif;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);display:block;margin-bottom:3px">Store Type</label><div><?= sanitize($prospect['store_type']) ?></div></div>
          <div><label style="font-family:'Barlow Condensed',sans-serif;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);display:block;margin-bottom:3px">Address</label><div class="fs-sm"><?= sanitize($prospect['address']) ?><br><?= sanitize($prospect['city']) ?>, <?= sanitize($prospect['state']) ?> <?= sanitize($prospect['zip']) ?></div></div>
          <div><label style="font-family:'Barlow Condensed',sans-serif;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);display:block;margin-bottom:3px">County</label><div><?= sanitize($prospect['county']) ?: '—' ?></div></div>
          <div><label style="font-family:'Barlow Condensed',sans-serif;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);display:block;margin-bottom:3px">Machines Wanted</label><div class="fw-600" style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;color:var(--gold)"><?= $prospect['machines_wanted'] ?: '—' ?></div></div>
          <div><label style="font-family:'Barlow Condensed',sans-serif;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);display:block;margin-bottom:3px">Lead Source</label><div><?= sanitize($prospect['source']) ?></div></div>
        </div>
      </div>
    </div>

    <!-- Contact Info -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-user-tie"></i> Contact Information</div>
      </div>
      <div class="card-body">
        <?php if (!$prospect['contact_name'] && !$prospect['contact_phone']): ?>
        <div class="td-muted fs-sm">No contact info on file.</div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:16px">
          <div><label style="font-family:'Barlow Condensed',sans-serif;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);display:block;margin-bottom:3px">Name</label><div class="fw-600"><?= sanitize($prospect['contact_name']) ?: '—' ?></div></div>
          <div><label style="font-family:'Barlow Condensed',sans-serif;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);display:block;margin-bottom:3px">Title</label><div><?= sanitize($prospect['contact_title']) ?: '—' ?></div></div>
          <div><label style="font-family:'Barlow Condensed',sans-serif;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);display:block;margin-bottom:3px">Phone</label>
            <div><?php if ($prospect['contact_phone']): ?>
              <a href="tel:<?= sanitize($prospect['contact_phone']) ?>" style="color:var(--gold-light);text-decoration:none"><?= sanitize($prospect['contact_phone']) ?></a>
            <?php else: ?>—<?php endif; ?></div>
          </div>
          <div><label style="font-family:'Barlow Condensed',sans-serif;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);display:block;margin-bottom:3px">Email</label>
            <div class="fs-sm"><?php if ($prospect['contact_email']): ?>
              <a href="mailto:<?= sanitize($prospect['contact_email']) ?>" style="color:var(--gold-light);text-decoration:none"><?= sanitize($prospect['contact_email']) ?></a>
            <?php else: ?>—<?php endif; ?></div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Notes -->
    <?php if ($prospect['notes']): ?>
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-note-sticky"></i> Notes</div></div>
      <div class="card-body"><div style="font-size:0.88rem;line-height:1.7;color:var(--text-muted)"><?= nl2br(sanitize($prospect['notes'])) ?></div></div>
    </div>
    <?php endif; ?>

    <!-- Activity Log -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-timeline"></i> Activity Log (<?= count($notes) ?>)</div>
        <button class="btn btn-primary btn-sm" onclick="openModal('addNoteModal')"><i class="fa-solid fa-plus"></i> Log Activity</button>
      </div>
      <div class="card-body" style="padding:0">
        <?php if (empty($notes)): ?>
        <div class="empty-state" style="padding:32px">
          <i class="fa-solid fa-timeline"></i>
          <h3>No Activity Yet</h3>
          <p>Log your first call, visit, or email.</p>
        </div>
        <?php else: ?>
        <div style="padding:20px;display:flex;flex-direction:column;gap:0">
          <?php foreach ($notes as $i => $n): ?>
          <div style="display:flex;gap:14px;padding-bottom:<?= $i<count($notes)-1?'20':'0' ?>px;position:relative">
            <!-- Timeline dot & line -->
            <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;width:32px">
              <div style="width:32px;height:32px;border-radius:50%;background:<?= $noteTypeColors[$n['note_type']] ?>22;border:2px solid <?= $noteTypeColors[$n['note_type']] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="fa-solid <?= $noteTypeIcons[$n['note_type']] ?>" style="color:<?= $noteTypeColors[$n['note_type']] ?>;font-size:0.75rem"></i>
              </div>
              <?php if ($i < count($notes)-1): ?>
              <div style="width:2px;flex:1;background:var(--border);margin-top:6px;min-height:20px"></div>
              <?php endif; ?>
            </div>
            <!-- Content -->
            <div style="flex:1;padding-top:5px">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
                <div style="display:flex;align-items:center;gap:8px">
                  <span class="fw-600 fs-sm" style="color:<?= $noteTypeColors[$n['note_type']] ?>"><?= $n['note_type'] ?></span>
                  <?php if ($n['created_by']): ?>
                  <span class="td-muted fs-sm">by <?= sanitize($n['created_by']) ?></span>
                  <?php endif; ?>
                </div>
                <div style="display:flex;align-items:center;gap:8px">
                  <span class="td-muted" style="font-size:0.72rem"><?= date('M j, Y g:ia', strtotime($n['created_at'])) ?></span>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Delete this activity log?')">
                    <input type="hidden" name="action" value="delete_note">
                    <input type="hidden" name="note_id" value="<?= $n['id'] ?>">
                    <button style="background:none;border:none;cursor:pointer;color:var(--text-dim);font-size:0.72rem;padding:2px 4px" title="Delete"><i class="fa-solid fa-xmark"></i></button>
                  </form>
                </div>
              </div>
              <div style="font-size:0.85rem;color:var(--text-muted);line-height:1.6;background:var(--bg-card2);padding:10px 14px;border-radius:var(--radius);border:1px solid var(--border)">
                <?= nl2br(sanitize($n['note_text'])) ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /left -->

  <!-- RIGHT: Quick Actions + Status -->
  <div style="display:flex;flex-direction:column;gap:18px;position:sticky;top:calc(var(--topbar-h) + 20px)">

    <!-- Quick Status Update -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-sliders"></i> Quick Update</div></div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_status">
          <div class="form-group" style="margin-bottom:14px">
            <label>Status</label>
            <select name="status">
              <?php foreach ($statuses as $s): ?>
              <option value="<?= $s ?>" <?= $prospect['status']===$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="margin-bottom:16px">
            <label>Follow-Up Date</label>
            <input type="date" name="follow_up_date" value="<?= sanitize($prospect['follow_up_date'] ?? '') ?>">
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center"><i class="fa-solid fa-check"></i> Update</button>
        </form>
      </div>
    </div>

    <!-- Lead Summary -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-circle-info"></i> Lead Summary</div></div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:12px">
          <?php
          $summaryItems = [
            ['label'=>'Assigned To',    'val'=> $prospect['assigned_to'] ?: '—'],
            ['label'=>'Lead Source',    'val'=> $prospect['source']],
            ['label'=>'Last Contact',   'val'=> $prospect['last_contact']  ? formatDate($prospect['last_contact'])  : '—'],
            ['label'=>'Follow-Up',      'val'=> $prospect['follow_up_date']? formatDate($prospect['follow_up_date']): '—'],
            ['label'=>'Added',          'val'=> formatDate($prospect['created_at'])],
            ['label'=>'Machines Wanted','val'=> $prospect['machines_wanted'] ?: '—'],
          ];
          if ($prospect['converted_at']) $summaryItems[] = ['label'=>'Converted','val'=>formatDate($prospect['converted_at'])];
          foreach ($summaryItems as $item): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
            <span class="td-muted fs-sm"><?= $item['label'] ?></span>
            <span class="fs-sm fw-600"><?= sanitize($item['val']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-bolt"></i> Quick Log</div></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
        <?php foreach (['Call','Email','Visit','Follow Up'] as $qtype): ?>
        <form method="POST">
          <input type="hidden" name="action" value="add_note">
          <input type="hidden" name="note_type" value="<?= $qtype ?>">
          <input type="hidden" name="update_last_contact" value="1">
          <input type="hidden" name="created_by" value="<?= sanitize($admin['name'] ?? 'Admin') ?>">
          <input type="hidden" name="note_text" value="<?= $qtype ?> made on <?= date('M j, Y') ?>">
          <button type="submit" class="btn btn-outline btn-sm" style="width:100%;justify-content:flex-start">
            <i class="fa-solid <?= $noteTypeIcons[$qtype] ?>" style="color:<?= $noteTypeColors[$qtype] ?>;width:16px"></i>
            Log <?= $qtype ?>
          </button>
        </form>
        <?php endforeach; ?>
      </div>
    </div>

  </div><!-- /right -->

</div>

<!-- ADD NOTE MODAL -->
<div class="modal-overlay" id="addNoteModal">
  <div class="modal" style="max-width:520px">
    <div class="modal-header">
      <div class="modal-title"><i class="fa-solid fa-plus"></i> Log Activity</div>
      <button class="modal-close" onclick="closeModal('addNoteModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_note">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Activity Type *</label>
            <select name="note_type" required>
              <?php foreach (['Call','Email','Visit','Follow Up','Other'] as $t): ?>
              <option value="<?= $t ?>"><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Logged By</label>
            <input type="text" name="created_by" value="<?= sanitize($admin['name'] ?? '') ?>" placeholder="Your name">
          </div>
          <div class="form-group col-span-2">
            <label>Notes *</label>
            <textarea name="note_text" rows="4" required placeholder="What happened? Key points, responses, next steps…"></textarea>
          </div>
          <div class="form-group col-span-2" style="flex-direction:row;align-items:center;gap:10px;background:var(--bg-card2);padding:10px 14px;border-radius:var(--radius);border:1px solid var(--border)">
            <input type="checkbox" name="update_last_contact" value="1" id="updateLastContact" checked style="width:auto">
            <label for="updateLastContact" style="text-transform:none;letter-spacing:0;color:var(--text-muted);font-size:0.85rem;cursor:pointer">Update "Last Contact" date to today</label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addNoteModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Activity</button>
      </div>
    </form>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
