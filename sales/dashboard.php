<?php
require_once __DIR__ . '/includes/sales_header.php';
$pageTitle = 'Dashboard';
$repId     = $rep['id'];
$b         = SALES_URL;

// ── Stats ────────────────────────────────────────────────────
$myProspects = $db->prepare("SELECT COUNT(*) FROM prospects WHERE rep_id=?");
$myProspects->execute([$repId]); $myProspects = $myProspects->fetchColumn();

$hotLeads = $db->prepare("SELECT COUNT(*) FROM prospects WHERE rep_id=? AND status IN ('Interested','Negotiating','Proposal Sent')");
$hotLeads->execute([$repId]); $hotLeads = $hotLeads->fetchColumn();

$converted = $db->prepare("SELECT COUNT(*) FROM prospects WHERE rep_id=? AND status='Converted'");
$converted->execute([$repId]); $converted = $converted->fetchColumn();

$followUpsDue = $db->prepare("SELECT COUNT(*) FROM prospects WHERE rep_id=? AND follow_up_date<=CURDATE() AND status NOT IN ('Converted','Not Interested','No Response')");
$followUpsDue->execute([$repId]); $followUpsDue = $followUpsDue->fetchColumn();

$contactedThisMonth = $db->prepare("SELECT COUNT(*) FROM prospects WHERE rep_id=? AND status!='New Lead' AND MONTH(updated_at)=MONTH(CURDATE()) AND YEAR(updated_at)=YEAR(CURDATE())");
$contactedThisMonth->execute([$repId]); $contactedThisMonth = $contactedThisMonth->fetchColumn();

$myClients = $db->prepare("SELECT COUNT(*) FROM clients c JOIN prospects p ON p.client_id=c.id WHERE p.rep_id=?");
$myClients->execute([$repId]); $myClients = $myClients->fetchColumn();

// Monthly target
$target = $db->prepare("SELECT * FROM sales_targets WHERE rep_id=? AND target_month=DATE_FORMAT(CURDATE(),'%Y-%m-01')");
$target->execute([$repId]); $target = $target->fetch();

// Pipeline by status
$pipeline = $db->prepare("SELECT status, COUNT(*) AS c FROM prospects WHERE rep_id=? GROUP BY status");
$pipeline->execute([$repId]);
$pipelineData = [];
foreach ($pipeline->fetchAll() as $r) $pipelineData[$r['status']] = $r['c'];

// Follow-ups due
$followUps = $db->prepare("SELECT * FROM prospects WHERE rep_id=? AND follow_up_date<=CURDATE() AND status NOT IN ('Converted','Not Interested','No Response') ORDER BY follow_up_date ASC LIMIT 8");
$followUps->execute([$repId]); $followUps = $followUps->fetchAll();

// Recent activity (my prospect notes)
$recentActivity = $db->prepare("
    SELECT pn.*, p.store_name, p.id AS prospect_id
    FROM prospect_notes pn
    JOIN prospects p ON p.id=pn.prospect_id
    WHERE p.rep_id=?
    ORDER BY pn.created_at DESC LIMIT 8
");
$recentActivity->execute([$repId]); $recentActivity = $recentActivity->fetchAll();

// Newest leads
$newLeads = $db->prepare("SELECT * FROM prospects WHERE rep_id=? AND status='New Lead' ORDER BY created_at DESC LIMIT 5");
$newLeads->execute([$repId]); $newLeads = $newLeads->fetchAll();

$statusColors = ['New Lead'=>'badge-blue','Contacted'=>'badge-gold','Interested'=>'badge-green','Proposal Sent'=>'badge-orange','Negotiating'=>'badge-orange','Converted'=>'badge-green','Not Interested'=>'badge-red','No Response'=>'badge-gray'];
$pipelineStages = ['New Lead','Contacted','Interested','Proposal Sent','Negotiating','Converted'];
$stageColors = ['New Lead'=>'#3B82F6','Contacted'=>'#C9A84C','Interested'=>'#22C55E','Proposal Sent'=>'#EA580C','Negotiating'=>'#7C3AED','Converted'=>'#16A34A'];

$closesGoal  = $target['closes_target']   ?? 3;
$leadsGoal   = $target['leads_target']    ?? 10;
$contactGoal = $target['contacts_target'] ?? 20;

$closesPct   = $closesGoal   > 0 ? min(100, round(($converted/$closesGoal)*100))   : 0;
$leadsPct    = $leadsGoal    > 0 ? min(100, round(($myProspects/$leadsGoal)*100))   : 0;
$contactPct  = $contactGoal  > 0 ? min(100, round(($contactedThisMonth/$contactGoal)*100)) : 0;
?>

<div class="page-header">
  <div>
    <h1><span class="accent">My</span> Dashboard</h1>
    <div class="page-subtitle"><?= date('l, F j, Y') ?> · <?= sanitize($rep['territory']) ?></div>
  </div>
  <a href="<?= $b ?>/add_prospect.php" class="btn btn-primary">
    <i class="fa-solid fa-plus"></i> Add New Lead
  </a>
</div>

<!-- KPI STAT CARDS -->
<div class="stats-grid section-gap">
  <div class="stat-card blue">
    <div class="stat-icon blue"><i class="fa-solid fa-bullseye"></i></div>
    <div class="stat-value"><?= $myProspects ?></div>
    <div class="stat-label">My Total Leads</div>
  </div>
  <div class="stat-card orange">
    <div class="stat-icon orange"><i class="fa-solid fa-fire"></i></div>
    <div class="stat-value"><?= $hotLeads ?></div>
    <div class="stat-label">Hot Leads</div>
    <div class="stat-sub warn"><i class="fa-solid fa-arrow-trend-up"></i> Interested + Proposal + Negotiating</div>
  </div>
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fa-solid fa-bell"></i></div>
    <div class="stat-value"><?= $followUpsDue ?></div>
    <div class="stat-label">Follow-Ups Due</div>
    <?php if ($followUpsDue > 0): ?>
    <div class="stat-sub warn"><i class="fa-solid fa-circle-exclamation"></i> Action required</div>
    <?php endif; ?>
  </div>
  <div class="stat-card green">
    <div class="stat-icon green"><i class="fa-solid fa-handshake"></i></div>
    <div class="stat-value"><?= $converted ?></div>
    <div class="stat-label">Deals Closed</div>
    <div class="stat-sub up"><i class="fa-solid fa-star"></i> <?= $myClients ?> active client<?= $myClients!=1?'s':'' ?></div>
  </div>
</div>

<!-- MONTHLY GOALS -->
<?php if ($target): ?>
<div class="card section-gap">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-trophy"></i> Monthly Goals — <?= date('F Y') ?></div>
    <span class="badge badge-gold"><?= date('F') ?></span>
  </div>
  <div class="card-body">
    <div class="grid-3" style="gap:24px">
      <div>
        <div class="progress-label"><span>Leads Added</span><span><?= $myProspects ?> / <?= $leadsGoal ?></span></div>
        <div class="progress-bar"><div class="progress-fill <?= $leadsPct>=100?'green':($leadsPct>=60?'gold':'orange') ?>" data-progress="<?= $leadsPct ?>" style="width:0"></div></div>
        <div style="font-size:.7rem;color:var(--text-muted);margin-top:4px"><?= $leadsPct ?>% of goal</div>
      </div>
      <div>
        <div class="progress-label"><span>Prospects Contacted</span><span><?= $contactedThisMonth ?> / <?= $contactGoal ?></span></div>
        <div class="progress-bar"><div class="progress-fill <?= $contactPct>=100?'green':($contactPct>=60?'gold':'orange') ?>" data-progress="<?= $contactPct ?>" style="width:0"></div></div>
        <div style="font-size:.7rem;color:var(--text-muted);margin-top:4px"><?= $contactPct ?>% of goal</div>
      </div>
      <div>
        <div class="progress-label"><span>Deals Closed</span><span><?= $converted ?> / <?= $closesGoal ?></span></div>
        <div class="progress-bar"><div class="progress-fill <?= $closesPct>=100?'green':($closesPct>=60?'gold':'red') ?>" data-progress="<?= $closesPct ?>" style="width:0"></div></div>
        <div style="font-size:.7rem;color:var(--text-muted);margin-top:4px"><?= $closesPct ?>% of goal</div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- FOLLOW-UPS + PIPELINE -->
<div class="grid-7-5 section-gap">

  <!-- Follow-Ups Due -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-bell"></i> Follow-Ups Due</div>
      <a href="<?= $b ?>/my_prospects.php?filter=followup" class="btn btn-ghost btn-xs">View All</a>
    </div>
    <?php if (empty($followUps)): ?>
    <div class="empty-state" style="padding:32px">
      <i class="fa-solid fa-check-circle" style="color:var(--green)"></i>
      <h3>All Clear!</h3><p>No follow-ups overdue. Keep it up.</p>
    </div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Store</th><th>Type</th><th>Status</th><th>Due</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($followUps as $f): ?>
          <?php $overdue = strtotime($f['follow_up_date']) < strtotime('today'); ?>
          <tr style="<?= $overdue?'background:rgba(220,38,38,.04)':'' ?>">
            <td class="fw-600"><?= sanitize($f['store_name']) ?><br><span class="td-muted fs-sm"><?= sanitize($f['city']) ?></span></td>
            <td><span class="badge badge-blue" style="font-size:.62rem"><?= sanitize($f['store_type']) ?></span></td>
            <td><span class="badge <?= $statusColors[$f['status']] ?? 'badge-gray' ?>" style="font-size:.62rem"><?= $f['status'] ?></span></td>
            <td class="<?= $overdue?'text-red':'td-muted' ?> fs-sm fw-600"><?= date('M j', strtotime($f['follow_up_date'])) ?><?= $overdue?' <i class="fa-solid fa-triangle-exclamation"></i>':'' ?></td>
            <td><a href="<?= $b ?>/prospect_detail.php?id=<?= $f['id'] ?>" class="btn btn-outline btn-xs"><i class="fa-solid fa-arrow-right"></i></a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Pipeline Summary -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-filter"></i> My Pipeline</div>
      <a href="<?= $b ?>/my_prospects.php?view=pipeline" class="btn btn-ghost btn-xs">Full View</a>
    </div>
    <div class="card-body">
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($pipelineStages as $stage):
          $cnt = $pipelineData[$stage] ?? 0;
          $max = max(1, max(array_values($pipelineData) ?: [1]));
          $pct = min(100, round(($cnt/$max)*100));
        ?>
        <div>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
            <span style="font-size:.78rem;color:var(--text-mid);font-family:'Barlow Condensed',sans-serif;font-weight:600"><?= $stage ?></span>
            <span style="font-family:'Bebas Neue',sans-serif;font-size:1.1rem;color:<?= $stageColors[$stage] ?>"><?= $cnt ?></span>
          </div>
          <div class="progress-bar" style="height:8px">
            <div style="height:100%;border-radius:4px;background:<?= $stageColors[$stage] ?>;width:0;transition:width .6s ease" data-progress="<?= $pct ?>"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- RECENT ACTIVITY + NEW LEADS -->
<div class="grid-7-5 section-gap">
  <!-- Recent Activity -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-timeline"></i> Recent Activity</div>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (empty($recentActivity)): ?>
      <div class="empty-state" style="padding:28px"><i class="fa-solid fa-timeline"></i><h3>No Activity Yet</h3><p>Log calls and visits on your prospects.</p></div>
      <?php else: ?>
      <div style="display:flex;flex-direction:column">
        <?php
        $noteIcons  = ['Call'=>'fa-phone','Email'=>'fa-envelope','Visit'=>'fa-location-dot','Follow Up'=>'fa-clock','Other'=>'fa-note-sticky'];
        $noteColors = ['Call'=>'#3B82F6','Email'=>'#22C55E','Visit'=>'#C9A84C','Follow Up'=>'#EA580C','Other'=>'#9CA3AF'];
        foreach ($recentActivity as $a):
        ?>
        <div style="display:flex;align-items:flex-start;gap:12px;padding:13px 18px;border-bottom:1px solid var(--border)">
          <div style="width:32px;height:32px;border-radius:50%;background:<?= $noteColors[$a['note_type']] ?>18;border:2px solid <?= $noteColors[$a['note_type']] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fa-solid <?= $noteIcons[$a['note_type']] ?>" style="color:<?= $noteColors[$a['note_type']] ?>;font-size:.7rem"></i>
          </div>
          <div style="flex:1;min-width:0">
            <div style="font-size:.85rem;font-weight:600;color:var(--text-dark)"><?= sanitize($a['store_name']) ?></div>
            <div class="fs-sm text-muted" style="margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:360px"><?= sanitize($a['note_text']) ?></div>
          </div>
          <div style="font-size:.68rem;color:var(--text-light);white-space:nowrap;text-align:right;flex-shrink:0">
            <?= $a['note_type'] ?><br><?= date('M j g:ia', strtotime($a['created_at'])) ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- New Leads -->
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-star"></i> New Leads</div>
      <a href="<?= $b ?>/add_prospect.php" class="btn btn-primary btn-xs"><i class="fa-solid fa-plus"></i> Add</a>
    </div>
    <div class="card-body" style="padding:0">
      <?php if (empty($newLeads)): ?>
      <div class="empty-state" style="padding:28px"><i class="fa-solid fa-plus-circle"></i><h3>Add Your First Lead</h3></div>
      <?php else: ?>
      <?php foreach ($newLeads as $nl): ?>
      <a href="<?= $b ?>/prospect_detail.php?id=<?= $nl['id'] ?>" style="display:flex;align-items:center;justify-content:space-between;padding:13px 18px;border-bottom:1px solid var(--border);text-decoration:none;color:inherit;transition:background .12s" onmouseover="this.style.background='#FAFAF8'" onmouseout="this.style.background=''">
        <div>
          <div style="font-size:.85rem;font-weight:600;color:var(--text-dark)"><?= sanitize($nl['store_name']) ?></div>
          <div class="fs-xs text-muted" style="margin-top:2px"><?= sanitize($nl['city']) ?> · <?= sanitize($nl['store_type']) ?></div>
        </div>
        <span class="badge badge-blue" style="font-size:.62rem">New</span>
      </a>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/sales_footer.php'; ?>
