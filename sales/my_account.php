<?php
require_once __DIR__ . '/includes/sales_header.php';
$pageTitle = 'My Account';
$repId = $rep['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'change_password') {
    $cur  = $_POST['current_password'] ?? '';
    $new  = $_POST['new_password']     ?? '';
    $conf = $_POST['confirm_password'] ?? '';
    $stmt = $db->prepare("SELECT password_hash FROM sales_reps WHERE id=?");
    $stmt->execute([$repId]); $hash = $stmt->fetchColumn();
    if (!password_verify($cur, $hash))  flashMessage('error','Current password incorrect.');
    elseif (strlen($new) < 8)           flashMessage('error','Password must be at least 8 characters.');
    elseif ($new !== $conf)             flashMessage('error','New passwords do not match.');
    else { salesUpdatePassword($repId, $new); flashMessage('success','Password updated!'); }
    redirect(SALES_URL.'/my_account.php');
}

$fullRep = $db->prepare("SELECT * FROM sales_reps WHERE id=?");
$fullRep->execute([$repId]); $fullRep = $fullRep->fetch();
?>

<div class="page-header">
  <div><h1><span class="accent">My</span> Account</h1><div class="page-subtitle">Manage your credentials</div></div>
</div>

<div class="grid-2" style="gap:20px;align-items:start">
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fa-solid fa-user-circle"></i> Rep Profile</div></div>
    <div class="card-body">
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;padding-bottom:18px;border-bottom:1px solid var(--border)">
        <div style="width:56px;height:56px;background:linear-gradient(135deg,var(--gold-dark),var(--gold));border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Barlow Condensed',sans-serif;font-size:1.2rem;font-weight:700;color:#0D0F14">
          <?= strtoupper(substr($rep['firstname'],0,1).substr($fullRep['last_name']??'',0,1)) ?>
        </div>
        <div>
          <div style="font-size:1.05rem;font-weight:700"><?= sanitize($rep['name']) ?></div>
          <div class="fs-sm text-muted">Sales Representative</div>
          <span class="badge badge-green mt-4"><i class="fa-solid fa-circle" style="font-size:.5rem"></i> Active</span>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <?php
        $items = ['Username'=>$fullRep['username'],'Email'=>$fullRep['email']?:'-','Phone'=>$fullRep['phone']?:'-','Territory'=>$fullRep['territory']?:'-','Hired'=>formatDate($fullRep['hired_date']),'Last Login'=>$fullRep['last_login']?date('M j, Y g:ia',strtotime($fullRep['last_login'])):'First session'];
        foreach ($items as $l => $v): ?>
        <div><div class="fs-xs text-muted" style="margin-bottom:3px"><?= $l ?></div><div class="fs-sm fw-600"><?= sanitize($v) ?></div></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fa-solid fa-key"></i> Change Password</div></div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="action" value="change_password">
        <div class="form-group" style="margin-bottom:13px"><label>Current Password</label><input type="password" name="current_password" required></div>
        <div class="form-group" style="margin-bottom:13px"><label>New Password (min 8 chars)</label><input type="password" name="new_password" required minlength="8"></div>
        <div class="form-group" style="margin-bottom:18px"><label>Confirm New Password</label><input type="password" name="confirm_password" required></div>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-lock"></i> Update Password</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/sales_footer.php'; ?>
