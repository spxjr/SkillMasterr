<?php
require_once __DIR__ . '/includes/portal_header.php';
portalRequireLogin();
$pageTitle = 'My Account';
$userId    = $portalUser['id'];
$db        = getDB();
$b         = PORTAL_URL;

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $portalUser['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            updatePortalPassword($userId, $new);
            flashMessage('success', 'Password updated successfully!');
            redirect($b . '/my_account.php');
        }
    }
}
?>

<div class="page-header">
  <div>
    <h1><span class="accent">My</span> Account</h1>
    <div class="page-subtitle">Manage your portal credentials and preferences</div>
  </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error" style="margin-bottom:20px">
  <i class="fa-solid fa-circle-exclamation"></i>
  <div><?= implode('<br>', array_map('sanitize', $errors)) ?></div>
</div>
<?php endif; ?>

<div class="grid-2" style="gap:20px;align-items:start">

  <!-- Profile Info (read-only) -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-user-circle"></i> Account Info</div>
    </div>
    <div class="card-body">
      <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;padding-bottom:20px;border-bottom:1px solid var(--border)">
        <div style="width:56px;height:56px;background:linear-gradient(135deg,var(--gold-dark),var(--gold));border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Barlow Condensed',sans-serif;font-size:1.2rem;font-weight:700;color:#0D0F14">
          <?php
          $parts = explode(' ', trim($portalUser['full_name']));
          echo strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
          ?>
        </div>
        <div>
          <div style="font-size:1.05rem;font-weight:700;color:var(--text-dark)"><?= sanitize($portalUser['full_name']) ?></div>
          <div class="fs-sm text-muted"><?= sanitize($portalUser['business_name']) ?></div>
          <div style="margin-top:4px"><span class="badge badge-gold"><?= ucfirst($portalUser['role']) ?></span></div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <div class="fs-xs text-muted" style="margin-bottom:3px">Username</div>
          <div class="fw-600 font-cond"><?= sanitize($portalUser['username']) ?></div>
        </div>
        <div>
          <div class="fs-xs text-muted" style="margin-bottom:3px">Email</div>
          <div class="fw-600"><?= sanitize($portalUser['email'] ?: '—') ?></div>
        </div>
        <div>
          <div class="fs-xs text-muted" style="margin-bottom:3px">Last Login</div>
          <div class="fw-600"><?= $portalUser['last_login'] ? date('M j, Y g:ia', strtotime($portalUser['last_login'])) : 'First time' ?></div>
        </div>
        <div>
          <div class="fs-xs text-muted" style="margin-bottom:3px">Access Level</div>
          <div class="fw-600"><?= ucfirst($portalUser['role']) ?></div>
        </div>
      </div>

      <hr class="divider">
      <div style="font-family:'Barlow Condensed',sans-serif;font-size:0.7rem;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);margin-bottom:10px">Your Venue</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <div class="fs-xs text-muted" style="margin-bottom:3px">Business</div>
          <div class="fw-600 fs-sm"><?= sanitize($portalUser['business_name']) ?></div>
        </div>
        <div>
          <div class="fs-xs text-muted" style="margin-bottom:3px">Type</div>
          <div class="fw-600 fs-sm"><?= sanitize($portalUser['venue_type']) ?></div>
        </div>
        <div>
          <div class="fs-xs text-muted" style="margin-bottom:3px">Location</div>
          <div class="fw-600 fs-sm"><?= sanitize($portalUser['city']) ?>, <?= sanitize($portalUser['state']) ?></div>
        </div>
        <div>
          <div class="fs-xs text-muted" style="margin-bottom:3px">Status</div>
          <span class="badge badge-green"><?= sanitize($portalUser['client_status']) ?></span>
        </div>
      </div>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:20px">
    <!-- Change Password -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-key"></i> Change Password</div>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="change_password">
          <div class="form-group" style="margin-bottom:14px">
            <label>Current Password</label>
            <input type="password" name="current_password" required autocomplete="current-password">
          </div>
          <div class="form-group" style="margin-bottom:14px">
            <label>New Password</label>
            <input type="password" name="new_password" required autocomplete="new-password" minlength="8">
          </div>
          <div class="form-group" style="margin-bottom:18px">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required autocomplete="new-password">
          </div>
          <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-lock"></i> Update Password</button>
        </form>
      </div>
    </div>

    <!-- Contract Info -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fa-solid fa-file-contract"></i> Contract Details</div>
      </div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
          <div>
            <div class="fs-xs text-muted" style="margin-bottom:3px">Start Date</div>
            <div class="fw-600"><?= formatDate($portalUser['contract_start']) ?></div>
          </div>
          <div>
            <div class="fs-xs text-muted" style="margin-bottom:3px">End Date</div>
            <div class="fw-600"><?= formatDate($portalUser['contract_end']) ?></div>
          </div>
        </div>
        <div style="background:var(--gold-pale);border:1px solid rgba(201,168,76,0.3);border-radius:var(--radius);padding:12px;font-size:0.8rem;color:var(--text-mid)">
          <i class="fa-solid fa-circle-info" style="color:var(--gold-dark)"></i>
          For contract changes or renewals, <a href="<?= $b ?>/messages.php?new=1" style="color:var(--gold-dark);font-weight:600">send us a message</a>.
        </div>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/portal_footer.php'; ?>
