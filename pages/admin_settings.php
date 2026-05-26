<?php
require_once '../includes/config.php';
$pageTitle = 'Admin Settings';
$db    = getDB();
$admin = adminGetUser();
$b     = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE id=?");
        $stmt->execute([$admin['id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            flashMessage('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 8) {
            flashMessage('error', 'New password must be at least 8 characters.');
        } elseif ($new !== $confirm) {
            flashMessage('error', 'New passwords do not match.');
        } else {
            adminUpdatePassword($admin['id'], $new);
            flashMessage('success', 'Password updated successfully!');
        }
        redirect($b . '/pages/admin_settings.php');
    }

    if ($action === 'create_admin') {
        $uname  = trim($_POST['username']  ?? '');
        $pw     = $_POST['password']       ?? '';
        $name   = trim($_POST['full_name'] ?? '');
        $email  = trim($_POST['email']     ?? '');
        $role   = $_POST['role']           ?? 'admin';
        if ($uname && $pw && $name) {
            $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 10]);
            try {
                $db->prepare("INSERT INTO admin_users (username,password_hash,full_name,email,role) VALUES (?,?,?,?,?)")
                   ->execute([$uname, $hash, $name, $email, $role]);
                flashMessage('success', "Admin user '$uname' created.");
            } catch (PDOException $e) {
                flashMessage('error', 'Username already exists.');
            }
        }
        redirect($b . '/pages/admin_settings.php');
    }

    if ($action === 'toggle_admin') {
        $tid = (int)$_POST['id'];
        if ($tid !== $admin['id']) { // can't disable yourself
            $cur = $db->prepare("SELECT is_active FROM admin_users WHERE id=?");
            $cur->execute([$tid]);
            $isActive = $cur->fetchColumn();
            $db->prepare("UPDATE admin_users SET is_active=? WHERE id=?")->execute([$isActive ? 0 : 1, $tid]);
            flashMessage('success', 'Account updated.');
        }
        redirect($b . '/pages/admin_settings.php');
    }

    if ($action === 'delete_admin') {
        $tid = (int)$_POST['id'];
        if ($tid !== $admin['id']) {
            $db->prepare("DELETE FROM admin_users WHERE id=?")->execute([$tid]);
            flashMessage('success', 'Admin user deleted.');
        }
        redirect($b . '/pages/admin_settings.php');
    }
}

$allAdmins = $db->query("SELECT * FROM admin_users ORDER BY created_at")->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header">
  <h1><span>ADMIN</span> SETTINGS</h1>
</div>

<div class="grid-2" style="gap:24px;align-items:start">

  <!-- Change Password -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-key"></i> Change Your Password</div>
    </div>
    <div class="card-body">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:22px;padding-bottom:18px;border-bottom:1px solid var(--border)">
        <div style="width:46px;height:46px;background:linear-gradient(135deg,var(--gold-dark),var(--gold));border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Barlow Condensed',sans-serif;font-size:1rem;font-weight:700;color:#0D0F14">
          <?= strtoupper(substr($admin['name'],0,1)) ?>
        </div>
        <div>
          <div style="font-weight:600;color:var(--text-white)"><?= sanitize($admin['name']) ?></div>
          <div class="td-muted fs-sm">@<?= sanitize($admin['username']) ?> · <?= ucfirst($admin['role']) ?></div>
        </div>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group" style="margin-bottom:14px">
          <label>Current Password</label>
          <input type="password" name="current_password" required>
        </div>
        <div class="form-group" style="margin-bottom:14px">
          <label>New Password</label>
          <input type="password" name="new_password" required minlength="8">
        </div>
        <div class="form-group" style="margin-bottom:20px">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-lock"></i> Update Password</button>
      </form>
    </div>
  </div>

  <!-- Admin Users -->
  <div style="display:flex;flex-direction:column;gap:20px">
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-users-gear"></i> Admin Accounts (<?= count($allAdmins) ?>)</div>
        <button class="btn btn-primary btn-sm" onclick="openModal('addAdminModal')"><i class="fa-solid fa-plus"></i> Add Admin</button>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Username</th><th>Name</th><th>Role</th><th>Last Login</th><th>Status</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($allAdmins as $a): ?>
            <tr>
              <td class="fw-600"><?= sanitize($a['username']) ?></td>
              <td class="td-muted"><?= sanitize($a['full_name']) ?></td>
              <td><span class="badge <?= $a['role']==='superadmin'?'badge-gold':'badge-blue' ?>"><?= ucfirst($a['role']) ?></span></td>
              <td class="td-muted fs-sm"><?= $a['last_login'] ? date('M j, Y g:ia', strtotime($a['last_login'])) : 'Never' ?></td>
              <td><?= $a['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-red">Disabled</span>' ?></td>
              <td>
                <?php if ($a['id'] !== $admin['id']): ?>
                <div class="btn-group">
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle_admin">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <button class="btn btn-outline btn-xs"><i class="fa-solid fa-<?= $a['is_active']?'ban':'circle-check' ?>"></i></button>
                  </form>
                  <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete admin <?= addslashes($a['username']) ?>?')">
                    <input type="hidden" name="action" value="delete_admin">
                    <input type="hidden" name="id" value="<?= $a['id'] ?>">
                    <button class="btn btn-danger btn-xs"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </div>
                <?php else: ?>
                <span class="td-muted fs-sm">You</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- System Info -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-circle-info"></i> System Info</div></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div><div class="td-muted fs-sm">CRM Version</div><div class="fw-600">v<?= APP_VERSION ?></div></div>
          <div><div class="td-muted fs-sm">Timezone</div><div class="fw-600"><?= TIMEZONE ?></div></div>
          <div><div class="td-muted fs-sm">Database</div><div class="fw-600 fs-sm"><?= DB_NAME ?></div></div>
          <div><div class="td-muted fs-sm">Server Time</div><div class="fw-600"><?= date('M j, Y g:ia') ?></div></div>
        </div>
        <hr style="border:none;border-top:1px solid var(--border);margin:16px 0">
        <a href="<?= BASE_URL ?>/portal/login.php" target="_blank" class="btn btn-outline btn-sm">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Client Portal
        </a>
      </div>
    </div>
  </div>

</div>

<!-- ADD ADMIN MODAL -->
<div class="modal-overlay" id="addAdminModal">
  <div class="modal" style="max-width:500px">
    <div class="modal-header">
      <div class="modal-title"><i class="fa-solid fa-user-plus"></i> Add Admin User</div>
      <button class="modal-close" onclick="closeModal('addAdminModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create_admin">
      <div class="modal-body">
        <div class="form-grid">
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
            <input type="text" name="username" required>
          </div>
          <div class="form-group">
            <label>Password *</label>
            <input type="text" name="password" required minlength="8">
          </div>
          <div class="form-group col-span-2">
            <label>Role</label>
            <select name="role">
              <option value="admin">Admin</option>
              <option value="superadmin">Super Admin</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addAdminModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Create Admin</button>
      </div>
    </form>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
