<?php
require_once __DIR__ . '/includes/sales_header.php';
$pageTitle = 'Messages';
$repId = $rep['id'];
$b     = SALES_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'send') {
        $body = trim($_POST['body'] ?? '');
        $subj = trim($_POST['subject'] ?? 'Message from '.$rep['name']);
        if ($body) {
            $db->prepare("INSERT INTO sales_messages (rep_id,direction,subject,body) VALUES (?,?,?,?)")
               ->execute([$repId,'rep_to_admin',$subj,$body]);
            flashMessage('success','Message sent to Texas Skill Masters!');
        }
        redirect($b.'/messages.php');
    }
}

// Mark admin messages as read
$db->prepare("UPDATE sales_messages SET is_read=1 WHERE rep_id=? AND direction='admin_to_rep'")->execute([$repId]);

$messages = $db->prepare("SELECT * FROM sales_messages WHERE rep_id=? ORDER BY created_at DESC");
$messages->execute([$repId]); $messages = $messages->fetchAll();
?>

<div class="page-header">
  <div>
    <h1><span class="accent">Messages</span></h1>
    <div class="page-subtitle">Communication with Texas Skill Masters Admin</div>
  </div>
  <button class="btn btn-primary" onclick="openModal('newMsgModal')"><i class="fa-solid fa-pen-to-square"></i> New Message</button>
</div>

<?php if (empty($messages)): ?>
<div class="card"><div class="empty-state"><i class="fa-solid fa-envelope-open"></i><h3>No Messages Yet</h3><p>Send a message to the TSM admin team.</p><button class="btn btn-primary mt-4" onclick="openModal('newMsgModal')"><i class="fa-solid fa-plus"></i> Send First Message</button></div></div>
<?php else: ?>
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fa-solid fa-inbox"></i> Message Thread</div></div>
  <div style="display:flex;flex-direction:column;gap:0">
    <?php foreach ($messages as $msg):
      $fromAdmin = $msg['direction'] === 'admin_to_rep';
    ?>
    <div style="padding:18px 20px;border-bottom:1px solid var(--border);<?= (!$fromAdmin && $msg['is_read']==0)?'background:var(--blue-pale)':'' ?>">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:8px">
        <div style="display:flex;align-items:center;gap:10px">
          <?php if ($fromAdmin): ?>
          <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--gold-dark),var(--gold));display:flex;align-items:center;justify-content:center;font-family:'Barlow Condensed',sans-serif;font-size:.75rem;font-weight:700;color:#0D0F14;flex-shrink:0">TSM</div>
          <?php else: ?>
          <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--blue),var(--blue-light));display:flex;align-items:center;justify-content:center;font-family:'Barlow Condensed',sans-serif;font-size:.72rem;font-weight:700;color:#fff;flex-shrink:0"><?= strtoupper(substr($rep['firstname'],0,1)) ?></div>
          <?php endif; ?>
          <div>
            <div style="font-weight:700;font-size:.88rem"><?= $fromAdmin ? 'Texas Skill Masters' : sanitize($rep['name']) ?></div>
            <div class="fs-xs text-muted"><?= sanitize($msg['subject']) ?></div>
          </div>
        </div>
        <div class="fs-xs text-muted" style="flex-shrink:0"><?= date('M j, Y g:ia', strtotime($msg['created_at'])) ?></div>
      </div>
      <div style="font-size:.88rem;color:var(--text-mid);line-height:1.65;padding-left:42px"><?= nl2br(sanitize($msg['body'])) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- NEW MESSAGE MODAL -->
<div class="modal-overlay" id="newMsgModal">
  <div class="modal" style="max-width:520px">
    <div class="modal-header"><div class="modal-title"><i class="fa-solid fa-pen-to-square"></i> Message to TSM Admin</div><button class="modal-close" onclick="closeModal('newMsgModal')"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="send">
      <div class="modal-body">
        <div class="form-group" style="margin-bottom:14px">
          <label>Subject</label>
          <select name="subject"><option>General Question</option><option>Lead Update</option><option>Need Support</option><option>Client Issue</option><option>Territory Question</option><option>Other</option></select>
        </div>
        <div class="form-group"><label>Message *</label><textarea name="body" rows="5" required placeholder="Type your message to the admin team…"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('newMsgModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send</button></div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/sales_footer.php'; ?>
