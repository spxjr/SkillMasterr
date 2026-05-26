<?php
require_once '../includes/config.php';
$pageTitle = 'Sales Reps';
$db = getDB();
$b  = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_rep') {
        $hash = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost'=>10]);
        try {
            $db->prepare("INSERT INTO sales_reps (username,password_hash,full_name,first_name,last_name,email,phone,territory,hired_date) VALUES (?,?,?,?,?,?,?,?,?)")
               ->execute([
                   trim($_POST['username']), $hash,
                   trim($_POST['full_name']), trim($_POST['first_name']), trim($_POST['last_name']),
                   trim($_POST['email']), trim($_POST['phone']),
                   trim($_POST['territory']), ($_POST['hired_date']?:date('Y-m-d'))
               ]);
            flashMessage('success', 'Sales rep created!');
        } catch (PDOException $e) { flashMessage('error','Username already exists.'); }
        redirect($b.'/pages/sales_admin.php');
    }

    if ($action === 'reset_pw') {
        salesUpdatePassword((int)$_POST['id'], $_POST['new_password']);
        flashMessage('success','Password reset.');
        redirect($b.'/pages/sales_admin.php');
    }

    if ($action === 'toggle') {
        $cur = $db->prepare("SELECT is_active FROM sales_reps WHERE id=?");
        $cur->execute([(int)$_POST['id']]); $cur = $cur->fetchColumn();
        $db->prepare("UPDATE sales_reps SET is_active=? WHERE id=?")->execute([$cur?0:1,(int)$_POST['id']]);
        flashMessage('success','Rep account '.($cur?'deactivated':'activated').'.');
        redirect($b.'/pages/sales_admin.php');
    }

    if ($action === 'set_target') {
        $rid  = (int)$_POST['rep_id'];
        $month = date('Y-m-01');
        $db->prepare("INSERT INTO sales_targets (rep_id,target_month,leads_target,contacts_target,closes_target,revenue_target)
            VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE leads_target=VALUES(leads_target),contacts_target=VALUES(contacts_target),closes_target=VALUES(closes_target),revenue_target=VALUES(revenue_target)")
           ->execute([$rid,$month,(int)$_POST['leads'],(int)$_POST['contacts'],(int)$_POST['closes'],(float)$_POST['revenue']]);
        flashMessage('success','Monthly targets updated!');
        redirect($b.'/pages/sales_admin.php');
    }

    if ($action === 'reply_msg') {
        $db->prepare("INSERT INTO sales_messages (rep_id,direction,subject,body) VALUES (?,'admin_to_rep',?,?)")
           ->execute([(int)$_POST['rep_id'],'Re: '.sanitize($_POST['orig_subject']),trim($_POST['reply'])]);
        flashMessage('success','Reply sent!');
        redirect($b.'/pages/sales_admin.php?tab=messages');
    }
}

// Load reps with full stats
$reps = $db->query("
    SELECT sr.*,
           COUNT(DISTINCT p.id) AS total_leads,
           SUM(CASE WHEN p.status='Converted' THEN 1 ELSE 0 END) AS closed,
           SUM(CASE WHEN p.status IN ('Interested','Negotiating','Proposal Sent') THEN 1 ELSE 0 END) AS hot,
           SUM(CASE WHEN p.follow_up_date<=CURDATE() AND p.status NOT IN ('Converted','Not Interested','No Response') THEN 1 ELSE 0 END) AS overdue
    FROM sales_reps sr LEFT JOIN prospects p ON p.rep_id=sr.id
    GROUP BY sr.id ORDER BY sr.full_name
")->fetchAll();

$targets = [];
foreach ($reps as $r) {
    $t = $db->prepare("SELECT * FROM sales_targets WHERE rep_id=? AND target_month=DATE_FORMAT(CURDATE(),'%Y-%m-01')");
    $t->execute([$r['id']]); $targets[$r['id']] = $t->fetch();
}

// Messages
$messages = $db->query("
    SELECT sm.*, sr.full_name AS rep_name, sr.first_name
    FROM sales_messages sm JOIN sales_reps sr ON sr.id=sm.rep_id
    WHERE sm.direction='rep_to_admin'
    ORDER BY sm.created_at DESC
")->fetchAll();
$unreadCount = count(array_filter($messages, fn($m)=>!$m['is_read']));
// Mark all read
$db->query("UPDATE sales_messages SET is_read=1 WHERE direction='rep_to_admin'");

require_once '../includes/header.php';
?>

<div class="page-header">
  <h1><span>SALES</span> REP MANAGEMENT</h1>
  <div class="btn-group">
    <button class="btn btn-primary" onclick="openModal('createRepModal')"><i class="fa-solid fa-user-plus"></i> Add Sales Rep</button>
    <a href="<?= BASE_URL ?>/sales/login.php" target="_blank" class="btn btn-outline"><i class="fa-solid fa-arrow-up-right-from-square"></i> Sales Portal</a>
  </div>
</div>

<div class="tab-container">
  <div class="tab-nav">
    <button class="tab-btn active" data-tab="reps">Sales Reps (<?= count($reps) ?>)</button>
    <button class="tab-btn" data-tab="messages">
      Rep Messages (<?= count($messages) ?>)
      <?php if ($unreadCount>0): ?><span class="badge badge-red" style="margin-left:5px"><?= $unreadCount ?> new</span><?php endif; ?>
    </button>
  </div>

  <!-- REPS TAB -->
  <div class="tab-panel active" data-panel="reps">

    <!-- Leaderboard Summary -->
    <div style="display:grid;grid-template-columns:repeat(<?= count($reps) ?>,1fr);gap:16px;margin-bottom:22px">
      <?php foreach ($reps as $i=>$r):
        $t = $targets[$r['id']] ?? null;
        $closeGoal = $t['closes_target']??3;
        $closePct  = $closeGoal>0?min(100,round(($r['closed']/$closeGoal)*100)):0;
        $avatarBgs = ['linear-gradient(135deg,#1D4ED8,#3B82F6)','linear-gradient(135deg,#16A34A,#22C55E)','linear-gradient(135deg,#7C3AED,#A78BFA)'];
      ?>
      <div class="card">
        <div class="card-body" style="text-align:center;padding:22px 18px">
          <div style="width:52px;height:52px;border-radius:50%;background:<?= $avatarBgs[$i%3] ?>;display:flex;align-items:center;justify-content:center;font-family:'Barlow Condensed',sans-serif;font-size:1rem;font-weight:700;color:#fff;margin:0 auto 10px">
            <?= strtoupper(substr($r['first_name'],0,1).substr($r['last_name']??'',0,1)) ?>
          </div>
          <div style="font-family:'Bebas Neue',sans-serif;font-size:1.1rem;letter-spacing:.05em"><?= sanitize($r['full_name']) ?></div>
          <div class="td-muted fs-sm" style="margin-bottom:14px"><?= sanitize($r['territory']) ?></div>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:14px">
            <div style="background:var(--bg-card2);border-radius:var(--radius);padding:8px 4px"><div style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;color:var(--green-light)"><?= $r['closed'] ?></div><div class="fs-sm" style="color:var(--text-dim)">Closed</div></div>
            <div style="background:var(--bg-card2);border-radius:var(--radius);padding:8px 4px"><div style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;color:var(--gold)"><?= $r['hot'] ?></div><div class="fs-sm" style="color:var(--text-dim)">Hot</div></div>
            <div style="background:var(--bg-card2);border-radius:var(--radius);padding:8px 4px"><div style="font-family:'Bebas Neue',sans-serif;font-size:1.4rem;color:#5DADE2"><?= $r['total_leads'] ?></div><div class="fs-sm" style="color:var(--text-dim)">Total</div></div>
          </div>
          <div style="margin-bottom:6px">
            <div style="display:flex;justify-content:space-between;font-size:.68rem;color:var(--text-muted);margin-bottom:4px"><span>Monthly Goal</span><span><?= $r['closed'] ?>/<?= $closeGoal ?></span></div>
            <div style="height:6px;background:var(--bg-dark);border-radius:4px"><div style="height:100%;border-radius:4px;background:<?= $closePct>=100?'var(--green)':($closePct>=60?'var(--gold)':'var(--red-light)') ?>;width:<?= $closePct ?>%"></div></div>
          </div>
          <?= $r['is_active']?'<span class="badge badge-green">Active</span>':'<span class="badge badge-red">Inactive</span>' ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Rep Management Table -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-users"></i> Rep Accounts</div></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Name</th><th>Username</th><th>Territory</th><th>Leads</th><th>Closed</th><th>Hot</th><th>Overdue F/U</th><th>Last Login</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach ($reps as $r): ?>
            <tr>
              <td class="fw-600"><?= sanitize($r['full_name']) ?><br><span class="td-muted fs-sm"><?= sanitize($r['email']) ?></span></td>
              <td class="td-muted">@<?= sanitize($r['username']) ?></td>
              <td class="td-muted fs-sm"><?= sanitize($r['territory']) ?></td>
              <td class="text-center"><?= $r['total_leads'] ?></td>
              <td class="text-center fw-600" style="color:var(--green-light)"><?= $r['closed'] ?></td>
              <td class="text-center" style="color:var(--gold)"><?= $r['hot'] ?></td>
              <td class="text-center <?= $r['overdue']>0?'text-red fw-600':'' ?>"><?= $r['overdue'] ?></td>
              <td class="td-muted fs-sm"><?= $r['last_login']?date('M j g:ia',strtotime($r['last_login'])):'Never' ?></td>
              <td><?= $r['is_active']?'<span class="badge badge-green">Active</span>':'<span class="badge badge-red">Disabled</span>' ?></td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-outline btn-xs" onclick="openModal('target<?= $r['id'] ?>')"><i class="fa-solid fa-bullseye"></i></button>
                  <button class="btn btn-outline btn-xs" onclick="openModal('pwRep<?= $r['id'] ?>')"><i class="fa-solid fa-key"></i></button>
                  <form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-outline btn-xs"><i class="fa-solid fa-<?= $r['is_active']?'ban':'circle-check' ?>"></i></button></form>
                </div>
                <!-- Set Target Modal -->
                <div class="modal-overlay" id="target<?= $r['id'] ?>">
                  <div class="modal" style="max-width:420px">
                    <div class="modal-header"><div class="modal-title"><i class="fa-solid fa-bullseye"></i> Monthly Targets — <?= sanitize($r['first_name']) ?></div><button class="modal-close" onclick="closeModal('target<?= $r['id'] ?>')"><i class="fa-solid fa-xmark"></i></button></div>
                    <form method="POST"><input type="hidden" name="action" value="set_target"><input type="hidden" name="rep_id" value="<?= $r['id'] ?>">
                      <div class="modal-body">
                        <?php $t = $targets[$r['id']] ?? []; ?>
                        <div class="form-grid">
                          <div class="form-group"><label>Leads Goal</label><input type="number" name="leads" value="<?= $t['leads_target']??10 ?>" min="1"></div>
                          <div class="form-group"><label>Contacts Goal</label><input type="number" name="contacts" value="<?= $t['contacts_target']??20 ?>" min="1"></div>
                          <div class="form-group"><label>Closes Goal</label><input type="number" name="closes" value="<?= $t['closes_target']??3 ?>" min="0"></div>
                          <div class="form-group"><label>Revenue Goal ($)</label><input type="number" name="revenue" value="<?= $t['revenue_target']??5000 ?>" min="0" step="100"></div>
                        </div>
                      </div>
                      <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('target<?= $r['id'] ?>')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Targets</button></div>
                    </form>
                  </div>
                </div>
                <!-- Reset PW Modal -->
                <div class="modal-overlay" id="pwRep<?= $r['id'] ?>">
                  <div class="modal" style="max-width:380px">
                    <div class="modal-header"><div class="modal-title"><i class="fa-solid fa-key"></i> Reset Password</div><button class="modal-close" onclick="closeModal('pwRep<?= $r['id'] ?>')"><i class="fa-solid fa-xmark"></i></button></div>
                    <form method="POST"><input type="hidden" name="action" value="reset_pw"><input type="hidden" name="id" value="<?= $r['id'] ?>">
                      <div class="modal-body"><div class="form-group"><label>New Password *</label><input type="text" name="new_password" required minlength="6"></div></div>
                      <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('pwRep<?= $r['id'] ?>')">Cancel</button><button type="submit" class="btn btn-primary">Reset</button></div>
                    </form>
                  </div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- MESSAGES TAB -->
  <div class="tab-panel" data-panel="messages">
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-envelope"></i> Messages from Sales Reps</div></div>
      <?php if (empty($messages)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><h3>No Messages</h3></div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Date</th><th>Reply</th></tr></thead>
          <tbody>
            <?php foreach ($messages as $msg): ?>
            <tr>
              <td class="fw-600"><?= sanitize($msg['rep_name']) ?></td>
              <td><?= sanitize($msg['subject']) ?></td>
              <td class="td-muted fs-sm" style="max-width:260px"><?= sanitize(substr($msg['body'],0,90)) ?>…</td>
              <td class="td-muted fs-sm"><?= date('M j, Y g:ia',strtotime($msg['created_at'])) ?></td>
              <td>
                <button class="btn btn-outline btn-xs" onclick="openModal('replyMsg<?= $msg['id'] ?>')"><i class="fa-solid fa-reply"></i> Reply</button>
                <div class="modal-overlay" id="replyMsg<?= $msg['id'] ?>">
                  <div class="modal">
                    <div class="modal-header"><div class="modal-title"><i class="fa-solid fa-reply"></i> Reply to <?= sanitize($msg['rep_name']) ?></div><button class="modal-close" onclick="closeModal('replyMsg<?= $msg['id'] ?>')"><i class="fa-solid fa-xmark"></i></button></div>
                    <div class="modal-body">
                      <div style="background:var(--bg-card2);padding:12px;border-radius:var(--radius);border:1px solid var(--border);margin-bottom:16px">
                        <div class="td-muted fs-sm" style="margin-bottom:5px"><strong><?= sanitize($msg['subject']) ?></strong></div>
                        <div class="fs-sm"><?= nl2br(sanitize($msg['body'])) ?></div>
                      </div>
                      <form method="POST">
                        <input type="hidden" name="action" value="reply_msg">
                        <input type="hidden" name="rep_id" value="<?= $msg['rep_id'] ?>">
                        <input type="hidden" name="orig_subject" value="<?= sanitize($msg['subject']) ?>">
                        <div class="form-group">
                          <label>Your Reply</label>
                          <textarea name="reply" rows="4" required placeholder="Type your reply…"></textarea>
                        </div>
                        <div style="margin-top:14px;display:flex;gap:10px;justify-content:flex-end">
                          <button type="button" class="btn btn-outline" onclick="closeModal('replyMsg<?= $msg['id'] ?>')">Cancel</button>
                          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send Reply</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- CREATE REP MODAL -->
<div class="modal-overlay" id="createRepModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title"><i class="fa-solid fa-user-plus"></i> Add Sales Rep</div><button class="modal-close" onclick="closeModal('createRepModal')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST"><input type="hidden" name="action" value="create_rep">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label>First Name *</label><input type="text" name="first_name" required></div>
          <div class="form-group"><label>Last Name</label><input type="text" name="last_name"></div>
          <div class="form-group col-span-2"><label>Full Name (Display) *</label><input type="text" name="full_name" required placeholder="e.g. Danny A"></div>
          <div class="form-group"><label>Email</label><input type="email" name="email"></div>
          <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
          <div class="form-group"><label>Username *</label><input type="text" name="username" required></div>
          <div class="form-group"><label>Password *</label><input type="text" name="password" required minlength="6"></div>
          <div class="form-group col-span-2"><label>Territory</label><input type="text" name="territory" placeholder="e.g. Austin Central / Travis County"></div>
          <div class="form-group"><label>Hired Date</label><input type="date" name="hired_date" value="<?= date('Y-m-d') ?>"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('createRepModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Create Rep</button></div>
    </form>
  </div>
</div>

<?php
// open messages tab if requested
if (isset($_GET['tab']) && $_GET['tab']==='messages'): ?>
<script>document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('.tab-btn').forEach(b=>{if(b.dataset.tab==='messages')b.click();});})</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
