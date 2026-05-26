<?php
require_once '../includes/config.php';
$pageTitle = 'Portal Users';
$db = getDB();
$b  = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $ok = createPortalUser(
            (int)$_POST['client_id'],
            trim($_POST['username']),
            $_POST['password'],
            trim($_POST['full_name']),
            trim($_POST['email']),
            $_POST['role'] ?? 'owner'
        );
        flashMessage($ok ? 'success' : 'error', $ok ? 'Portal user created!' : 'Username already exists or error occurred.');
        redirect($b . '/pages/portal_users.php');
    }

    if ($action === 'toggle') {
        $cu = $db->prepare("SELECT is_active FROM client_users WHERE id=?");
        $cu->execute([(int)$_POST['id']]);
        $cur = $cu->fetchColumn();
        $db->prepare("UPDATE client_users SET is_active=? WHERE id=?")->execute([$cur ? 0 : 1, (int)$_POST['id']]);
        flashMessage('success', 'Account ' . ($cur ? 'deactivated.' : 'activated.'));
        redirect($b . '/pages/portal_users.php');
    }

    if ($action === 'reset_password') {
        updatePortalPassword((int)$_POST['id'], $_POST['new_password']);
        flashMessage('success', 'Password reset successfully.');
        redirect($b . '/pages/portal_users.php');
    }

    if ($action === 'delete') {
        $db->prepare("DELETE FROM client_users WHERE id=?")->execute([(int)$_POST['id']]);
        flashMessage('success', 'Portal user deleted.');
        redirect($b . '/pages/portal_users.php');
    }

    if ($action === 'reply') {
        $msgId = (int)$_POST['message_id'];
        $reply = trim($_POST['reply_body'] ?? '');
        if ($reply) {
            $db->prepare("UPDATE portal_messages SET reply_body=?, replied_at=NOW(), is_read=0 WHERE id=?")
               ->execute([$reply, $msgId]);
            flashMessage('success', 'Reply sent to client.');
        }
        redirect($b . '/pages/portal_users.php?tab=messages');
    }
}

// Load users
$users = $db->query("
    SELECT cu.*, c.business_name, c.city, c.status AS client_status
    FROM client_users cu
    JOIN clients c ON c.id=cu.client_id
    ORDER BY c.business_name, cu.username
")->fetchAll();

// Load messages
$messages = $db->query("
    SELECT pm.*, c.business_name, cu.full_name AS sender_name
    FROM portal_messages pm
    JOIN clients c ON c.id=pm.client_id
    JOIN client_users cu ON cu.id=pm.user_id
    ORDER BY pm.created_at DESC
")->fetchAll();

$unreplied = count(array_filter($messages, fn($m)=>!$m['reply_body']));

$allClients = $db->query("SELECT id, business_name FROM clients WHERE status='Active' ORDER BY business_name")->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header">
  <h1><span>PORTAL</span> MANAGEMENT</h1>
  <button class="btn btn-primary" onclick="openModal('createUserModal')">
    <i class="fa-solid fa-user-plus"></i> Create Portal User
  </button>
</div>

<div class="tab-container">
  <div class="tab-nav">
    <button class="tab-btn active" data-tab="users">Portal Users (<?= count($users) ?>)</button>
    <button class="tab-btn" data-tab="messages">
      Client Messages (<?= count($messages) ?>)
      <?php if ($unreplied > 0): ?>
        <span class="badge badge-red" style="margin-left:4px"><?= $unreplied ?> pending</span>
      <?php endif; ?>
    </button>
  </div>

  <!-- USERS TAB -->
  <div class="tab-panel active" data-panel="users">
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-users"></i> Client Portal Accounts</div>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Username</th><th>Full Name</th><th>Client</th><th>Role</th><th>Last Login</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
            <tr><td colspan="7"><div class="empty-state"><i class="fa-solid fa-users"></i><h3>No Portal Users</h3><p>Create accounts for your clients.</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($users as $u): ?>
            <tr>
              <td class="fw-600"><?= sanitize($u['username']) ?></td>
              <td><?= sanitize($u['full_name']) ?><br><span class="td-muted fs-sm"><?= sanitize($u['email']) ?></span></td>
              <td><?= sanitize($u['business_name']) ?><br><span class="td-muted fs-sm"><?= sanitize($u['city']) ?></span></td>
              <td><span class="badge badge-blue"><?= ucfirst($u['role']) ?></span></td>
              <td class="td-muted"><?= $u['last_login'] ? date('M j, Y g:ia', strtotime($u['last_login'])) : 'Never' ?></td>
              <td><?= $u['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Disabled</span>' ?></td>
              <td>
                <div class="btn-group">
                  <!-- Reset Password -->
                  <button class="btn btn-outline btn-xs" onclick="openModal('resetPw<?= $u['id'] ?>')"><i class="fa-solid fa-key"></i></button>
                  <!-- Toggle active -->
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button class="btn btn-outline btn-xs" type="submit" title="<?= $u['is_active']?'Disable':'Enable' ?>">
                      <i class="fa-solid fa-<?= $u['is_active']?'ban':'circle-check' ?>"></i>
                    </button>
                  </form>
                  <!-- Delete -->
                  <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete portal access for <?= addslashes($u['username']) ?>?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button class="btn btn-danger btn-xs" type="submit"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </div>
                <!-- Reset password modal -->
                <div class="modal-overlay" id="resetPw<?= $u['id'] ?>">
                  <div class="modal" style="max-width:420px">
                    <div class="modal-header">
                      <div class="modal-title">Reset Password — <?= sanitize($u['username']) ?></div>
                      <button class="modal-close" onclick="closeModal('resetPw<?= $u['id'] ?>')"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <form method="POST">
                      <input type="hidden" name="action" value="reset_password">
                      <input type="hidden" name="id" value="<?= $u['id'] ?>">
                      <div class="modal-body">
                        <div class="form-group">
                          <label>New Password *</label>
                          <input type="text" name="new_password" placeholder="Enter new password" required minlength="6">
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-outline" onclick="closeModal('resetPw<?= $u['id'] ?>')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Reset</button>
                      </div>
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
  </div>

  <!-- MESSAGES TAB -->
  <div class="tab-panel" data-panel="messages">
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-envelope"></i> Client Messages</div>
      </div>
      <?php if (empty($messages)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><h3>No Messages Yet</h3></div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Date</th><th>Client</th><th>From</th><th>Subject</th><th>Message</th><th>Status</th><th>Reply</th></tr>
          </thead>
          <tbody>
            <?php foreach ($messages as $msg): ?>
            <tr>
              <td class="td-muted"><?= date('M j, Y', strtotime($msg['created_at'])) ?><br><span class="fs-sm"><?= date('g:ia', strtotime($msg['created_at'])) ?></span></td>
              <td class="fw-600"><?= sanitize($msg['business_name']) ?></td>
              <td class="td-muted"><?= sanitize($msg['sender_name']) ?></td>
              <td><?= sanitize($msg['subject']) ?></td>
              <td class="td-muted fs-sm" style="max-width:220px"><?= sanitize(substr($msg['body'],0,80)) ?>…</td>
              <td>
                <?php if ($msg['reply_body']): ?>
                  <span class="badge badge-green">Replied</span>
                <?php else: ?>
                  <span class="badge badge-gold">Pending</span>
                <?php endif; ?>
              </td>
              <td>
                <button class="btn btn-outline btn-xs" onclick="openModal('replyModal<?= $msg['id'] ?>')">
                  <i class="fa-solid fa-reply"></i> Reply
                </button>
                <!-- Reply modal -->
                <div class="modal-overlay" id="replyModal<?= $msg['id'] ?>">
                  <div class="modal">
                    <div class="modal-header">
                      <div class="modal-title"><i class="fa-solid fa-reply"></i> Reply to <?= sanitize($msg['business_name']) ?></div>
                      <button class="modal-close" onclick="closeModal('replyModal<?= $msg['id'] ?>')"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div class="modal-body">
                      <div style="background:var(--bg-card2);border-radius:var(--radius);padding:14px;margin-bottom:18px;border:1px solid var(--border)">
                        <div class="td-muted fs-sm" style="margin-bottom:5px"><strong><?= sanitize($msg['subject']) ?></strong> · <?= sanitize($msg['sender_name']) ?></div>
                        <div class="fs-sm"><?= nl2br(sanitize($msg['body'])) ?></div>
                      </div>
                      <?php if ($msg['reply_body']): ?>
                      <div style="background:rgba(201,168,76,0.08);border-radius:var(--radius);padding:12px;margin-bottom:14px;border:1px solid var(--border-gold)">
                        <div class="td-muted fs-sm" style="margin-bottom:5px">Previous Reply · <?= date('M j, Y', strtotime($msg['replied_at'])) ?></div>
                        <div class="fs-sm"><?= nl2br(sanitize($msg['reply_body'])) ?></div>
                      </div>
                      <?php endif; ?>
                      <form method="POST">
                        <input type="hidden" name="action" value="reply">
                        <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                        <div class="form-group">
                          <label>Your Reply</label>
                          <textarea name="reply_body" rows="4" required placeholder="Type your reply…"></textarea>
                        </div>
                        <div style="margin-top:14px;display:flex;gap:10px;justify-content:flex-end">
                          <button type="button" class="btn btn-outline" onclick="closeModal('replyModal<?= $msg['id'] ?>')">Cancel</button>
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

<!-- CREATE USER MODAL -->
<div class="modal-overlay" id="createUserModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title"><i class="fa-solid fa-user-plus"></i> Create Portal Account</div>
      <button class="modal-close" onclick="closeModal('createUserModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group col-span-2">
            <label>Client *</label>
            <select name="client_id" required>
              <option value="">— Select Client —</option>
              <?php foreach ($allClients as $c): ?>
              <option value="<?= $c['id'] ?>"><?= sanitize($c['business_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email">
          </div>
          <div class="form-group">
            <label>Username *</label>
            <input type="text" name="username" required placeholder="No spaces">
          </div>
          <div class="form-group">
            <label>Password *</label>
            <input type="text" name="password" required minlength="6" placeholder="Min 6 characters">
          </div>
          <div class="form-group">
            <label>Role</label>
            <select name="role">
              <option value="owner">Owner</option>
              <option value="manager">Manager</option>
              <option value="viewer">Viewer (Read-only)</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('createUserModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Create Account</button>
      </div>
    </form>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
