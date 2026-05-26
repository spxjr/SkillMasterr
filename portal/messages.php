<?php
require_once __DIR__ . '/includes/portal_header.php';
portalRequireLogin();
$pageTitle = 'Messages';
$clientId  = $portalUser['client_id'];
$userId    = $portalUser['id'];
$db        = getDB();
$b         = PORTAL_URL;

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'send') {
    $subject = trim($_POST['subject'] ?? 'General Inquiry');
    $body    = trim($_POST['body']    ?? '');
    if ($body) {
        $db->prepare("INSERT INTO portal_messages (client_id,user_id,subject,body) VALUES (?,?,?,?)")
           ->execute([$clientId, $userId, $subject, $body]);
        flashMessage('success', 'Message sent! Texas Skill Masters will reply soon.');
    }
    redirect($b . '/messages.php');
}

// Mark replies as read
$db->prepare("UPDATE portal_messages SET is_read=1 WHERE client_id=? AND reply_body IS NOT NULL AND is_read=0")->execute([$clientId]);

// Load messages
$messages = $db->prepare("
    SELECT * FROM portal_messages
    WHERE client_id=?
    ORDER BY created_at DESC
");
$messages->execute([$clientId]);
$messages = $messages->fetchAll();

$openNew = isset($_GET['new']) && $_GET['new'];
$selectedId = (int)($_GET['id'] ?? 0);
$selected = null;
if ($selectedId) {
    foreach ($messages as $msg) {
        if ($msg['id'] === $selectedId) { $selected = $msg; break; }
    }
}
?>

<div class="page-header">
  <div>
    <h1><span class="accent">Messages</span></h1>
    <div class="page-subtitle">Direct communication with Texas Skill Masters</div>
  </div>
  <button class="btn btn-primary" onclick="openModal('newMessageModal')">
    <i class="fa-solid fa-pen-to-square"></i> New Message
  </button>
</div>

<div class="grid-2" style="grid-template-columns:2fr 3fr;gap:20px;align-items:start">

  <!-- MESSAGE LIST -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-inbox"></i> Inbox (<?= count($messages) ?>)</div>
    </div>
    <?php if (empty($messages)): ?>
    <div class="empty-state">
      <i class="fa-solid fa-envelope-open"></i>
      <h3>No Messages Yet</h3>
      <p>Start a conversation with Texas Skill Masters.</p>
      <button class="btn btn-primary btn-sm mt-4" onclick="openModal('newMessageModal')"><i class="fa-solid fa-plus"></i> Send First Message</button>
    </div>
    <?php else: ?>
    <div class="message-list">
      <?php foreach ($messages as $msg): ?>
      <a href="<?= $b ?>/messages.php?id=<?= $msg['id'] ?>"
         class="message-item <?= ($msg['reply_body'] && !$msg['is_read']) ? 'unread' : '' ?>"
         style="text-decoration:none;color:inherit">
        <div>
          <div class="msg-subject"><?= sanitize($msg['subject']) ?></div>
          <div class="msg-preview"><?= sanitize(substr($msg['body'],0,80)) ?>…</div>
          <?php if ($msg['reply_body']): ?>
          <div style="margin-top:4px"><span class="badge badge-green" style="font-size:.6rem"><i class="fa-solid fa-reply" style="font-size:.55rem"></i> Replied</span></div>
          <?php endif; ?>
        </div>
        <div class="msg-date"><?= date('M j', strtotime($msg['created_at'])) ?><br><?= date('g:ia', strtotime($msg['created_at'])) ?></div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- MESSAGE THREAD VIEW -->
  <div class="card">
    <?php if ($selected): ?>
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-envelope-open-text"></i> <?= sanitize($selected['subject']) ?></div>
      <span class="td-muted fs-sm"><?= date('M j, Y g:ia', strtotime($selected['created_at'])) ?></span>
    </div>
    <div class="card-body">
      <div style="display:flex;flex-direction:column;gap:16px">
        <!-- Client message -->
        <div>
          <div class="fs-xs text-muted" style="margin-bottom:6px">
            <i class="fa-solid fa-user"></i> <?= sanitize($portalUser['full_name']) ?>
            <span style="margin-left:8px"><?= date('M j, Y g:ia', strtotime($selected['created_at'])) ?></span>
          </div>
          <div class="message-bubble bubble-client">
            <?= nl2br(sanitize($selected['body'])) ?>
          </div>
        </div>

        <?php if ($selected['reply_body']): ?>
        <!-- TSM reply -->
        <div style="display:flex;flex-direction:column;align-items:flex-end">
          <div class="fs-xs text-muted" style="margin-bottom:6px;text-align:right">
            <i class="fa-solid fa-star" style="color:var(--gold-dark)"></i> Texas Skill Masters
            <span style="margin-left:8px"><?= date('M j, Y g:ia', strtotime($selected['replied_at'])) ?></span>
          </div>
          <div class="message-bubble bubble-tsm">
            <?= nl2br(sanitize($selected['reply_body'])) ?>
          </div>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:20px;background:var(--bg);border-radius:var(--radius);border:1px dashed var(--border)">
          <i class="fa-solid fa-clock text-muted" style="font-size:1.2rem;margin-bottom:6px;display:block"></i>
          <div class="fs-sm text-muted">Awaiting reply from Texas Skill Masters</div>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php else: ?>
    <div class="empty-state" style="padding:60px 30px">
      <i class="fa-solid fa-comment-dots"></i>
      <h3>Select a Message</h3>
      <p>Click a conversation to view the full thread.</p>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- NEW MESSAGE MODAL -->
<div class="modal-overlay <?= $openNew ? 'open' : '' ?>" id="newMessageModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <div class="modal-title"><i class="fa-solid fa-pen-to-square"></i> New Message to TSM</div>
      <button class="modal-close" onclick="closeModal('newMessageModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="send">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group col-span-2">
            <label>Subject</label>
            <select name="subject">
              <option>General Inquiry</option>
              <option>Machine Issue / Repair Request</option>
              <option>Collection Question</option>
              <option>Revenue / Payout Question</option>
              <option>New Machine Request</option>
              <option>Contract Question</option>
              <option>Other</option>
            </select>
          </div>
          <div class="form-group col-span-2">
            <label>Message *</label>
            <textarea name="body" rows="5" placeholder="Describe your question or issue…" required></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('newMessageModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/portal_footer.php'; ?>
