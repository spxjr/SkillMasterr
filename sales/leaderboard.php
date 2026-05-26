<?php
require_once __DIR__ . '/includes/sales_header.php';
$pageTitle = 'Leaderboard';
$b = SALES_URL;

// All reps with stats
$reps = $db->query("
    SELECT sr.*,
           COUNT(DISTINCT p.id)                                          AS total_leads,
           SUM(CASE WHEN p.status='Converted' THEN 1 ELSE 0 END)        AS closed,
           SUM(CASE WHEN p.status IN ('Interested','Negotiating','Proposal Sent') THEN 1 ELSE 0 END) AS hot,
           SUM(CASE WHEN p.status='Contacted' THEN 1 ELSE 0 END)        AS contacted,
           SUM(CASE WHEN p.follow_up_date<=CURDATE() AND p.status NOT IN ('Converted','Not Interested','No Response') THEN 1 ELSE 0 END) AS overdue,
           SUM(CASE WHEN MONTH(p.created_at)=MONTH(CURDATE()) AND YEAR(p.created_at)=YEAR(CURDATE()) THEN 1 ELSE 0 END) AS leads_this_month
    FROM sales_reps sr
    LEFT JOIN prospects p ON p.rep_id=sr.id
    WHERE sr.is_active=1
    GROUP BY sr.id
    ORDER BY closed DESC, hot DESC, total_leads DESC
")->fetchAll();

// Monthly targets for this month
$targets = [];
foreach ($reps as $r) {
    $t = $db->prepare("SELECT * FROM sales_targets WHERE rep_id=? AND target_month=DATE_FORMAT(CURDATE(),'%Y-%m-01')");
    $t->execute([$r['id']]); $targets[$r['id']] = $t->fetch();
}

// Recent activity across all reps
$recentAll = $db->query("
    SELECT pn.note_type, pn.note_text, pn.created_at, pn.created_by,
           p.store_name, sr.first_name AS rep_first, sr.full_name AS rep_name
    FROM prospect_notes pn
    JOIN prospects p ON p.id=pn.prospect_id
    JOIN sales_reps sr ON sr.id=p.rep_id
    ORDER BY pn.created_at DESC LIMIT 12
")->fetchAll();

$rankColors = ['#C9A84C','#9CA3AF','#B45309'];
$rankLabels = ['🥇','🥈','🥉'];
$avatarBgs  = ['linear-gradient(135deg,#1D4ED8,#3B82F6)','linear-gradient(135deg,#16A34A,#22C55E)','linear-gradient(135deg,#7C3AED,#A78BFA)'];

$noteIcons  = ['Call'=>'fa-phone','Email'=>'fa-envelope','Visit'=>'fa-location-dot','Follow Up'=>'fa-clock','Other'=>'fa-note-sticky'];
$noteColors = ['Call'=>'#3B82F6','Email'=>'#22C55E','Visit'=>'#C9A84C','Follow Up'=>'#EA580C','Other'=>'#9CA3AF'];
?>

<div class="page-header">
  <div>
    <h1><span class="accent">Sales</span> Leaderboard</h1>
    <div class="page-subtitle"><?= date('F Y') ?> — See how the team is performing</div>
  </div>
</div>

<!-- PODIUM CARDS -->
<div style="display:grid;grid-template-columns:repeat(<?= count($reps) ?>,1fr);gap:16px;margin-bottom:22px">
  <?php foreach ($reps as $i => $r):
    $initials = strtoupper(substr($r['first_name'],0,1).substr($r['last_name']??'',0,1));
    $isMe = ($r['id'] == $rep['id']);
    $t    = $targets[$r['id']] ?? null;
    $closeGoal = $t['closes_target'] ?? 3;
    $closePct  = $closeGoal > 0 ? min(100, round(($r['closed']/$closeGoal)*100)) : 0;
  ?>
  <div class="card" style="<?= $isMe?'border-color:var(--gold);box-shadow:0 0 0 2px rgba(201,168,76,.2)':'' ?>">
    <?php if ($isMe): ?>
    <div style="background:linear-gradient(90deg,var(--gold-dark),var(--gold));padding:5px 14px;font-family:'Barlow Condensed',sans-serif;font-size:.68rem;font-weight:700;letter-spacing:.1em;color:#0D0F14;text-align:center">YOU</div>
    <?php endif; ?>
    <div class="card-body" style="text-align:center;padding:24px 20px">
      <!-- Rank medal -->
      <div style="font-size:2rem;margin-bottom:10px"><?= $rankLabels[$i] ?? '#'.($i+1) ?></div>
      <!-- Avatar -->
      <div style="width:64px;height:64px;border-radius:50%;background:<?= $avatarBgs[$i % count($avatarBgs)] ?>;display:flex;align-items:center;justify-content:center;font-family:'Barlow Condensed',sans-serif;font-size:1.3rem;font-weight:700;color:#fff;margin:0 auto 12px">
        <?= $initials ?>
      </div>
      <div style="font-family:'Bebas Neue',sans-serif;font-size:1.3rem;letter-spacing:.05em;color:var(--text-dark)"><?= sanitize($r['full_name']) ?></div>
      <div style="font-size:.72rem;color:var(--text-muted);margin-bottom:18px"><?= sanitize($r['territory']) ?></div>

      <!-- Key metrics -->
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:18px">
        <div style="background:var(--green-pale);border-radius:var(--radius);padding:10px 6px">
          <div style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;color:var(--green);line-height:1"><?= $r['closed'] ?></div>
          <div style="font-size:.62rem;color:var(--green);font-family:'Barlow Condensed',sans-serif;letter-spacing:.06em;text-transform:uppercase">Closed</div>
        </div>
        <div style="background:var(--orange-pale);border-radius:var(--radius);padding:10px 6px">
          <div style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;color:var(--orange);line-height:1"><?= $r['hot'] ?></div>
          <div style="font-size:.62rem;color:var(--orange);font-family:'Barlow Condensed',sans-serif;letter-spacing:.06em;text-transform:uppercase">Hot</div>
        </div>
        <div style="background:var(--blue-pale);border-radius:var(--radius);padding:10px 6px">
          <div style="font-family:'Bebas Neue',sans-serif;font-size:1.6rem;color:var(--blue);line-height:1"><?= $r['total_leads'] ?></div>
          <div style="font-size:.62rem;color:var(--blue);font-family:'Barlow Condensed',sans-serif;letter-spacing:.06em;text-transform:uppercase">Total</div>
        </div>
      </div>

      <!-- Close rate progress -->
      <div>
        <div class="progress-label" style="margin-bottom:5px">
          <span style="font-size:.68rem;color:var(--text-muted)">Monthly Close Goal</span>
          <span style="font-size:.68rem;font-weight:700;color:var(--text-dark)"><?= $r['closed'] ?>/<?= $closeGoal ?></span>
        </div>
        <div class="progress-bar">
          <div class="progress-fill <?= $closePct>=100?'green':($closePct>=60?'gold':'orange') ?>" data-progress="<?= $closePct ?>" style="width:0"></div>
        </div>
        <div style="font-size:.65rem;color:var(--text-muted);margin-top:3px;text-align:right"><?= $closePct ?>%</div>
      </div>

      <?php if ($r['overdue'] > 0): ?>
      <div style="margin-top:12px;padding:6px 10px;background:var(--red-pale);border-radius:var(--radius);font-size:.72rem;color:var(--red);font-family:'Barlow Condensed',sans-serif">
        <i class="fa-solid fa-bell"></i> <?= $r['overdue'] ?> follow-up<?= $r['overdue']!=1?'s':'' ?> overdue
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- DETAILED COMPARISON TABLE -->
<div class="card section-gap">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-table"></i> Detailed Stats — <?= date('F Y') ?></div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Rank</th><th>Rep</th><th>Territory</th>
          <th>Leads This Mo.</th><th>Total Leads</th><th>Contacted</th>
          <th>Hot Leads</th><th>Closed</th><th>Overdue F/U</th><th>Last Active</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reps as $i => $r): ?>
        <tr style="<?= $r['id']==$rep['id']?'background:rgba(201,168,76,.05)':'' ?>">
          <td class="font-bebas" style="font-size:1.2rem;color:<?= $rankColors[$i] ?? 'var(--text-muted)' ?>">#<?= $i+1 ?></td>
          <td>
            <div class="fw-600"><?= sanitize($r['full_name']) ?></div>
            <?php if ($r['id']==$rep['id']): ?><span class="badge badge-gold" style="font-size:.58rem">You</span><?php endif; ?>
          </td>
          <td class="td-muted fs-sm"><?= sanitize($r['territory']) ?></td>
          <td class="text-center fw-600"><?= $r['leads_this_month'] ?></td>
          <td class="text-center"><?= $r['total_leads'] ?></td>
          <td class="text-center"><?= $r['contacted'] ?></td>
          <td class="text-center"><span class="<?= $r['hot']>0?'text-green fw-600':'' ?>"><?= $r['hot'] ?></span></td>
          <td class="text-center"><span class="<?= $r['closed']>0?'font-bebas text-green':'text-muted' ?>" style="<?= $r['closed']>0?'font-size:1.1rem':'' ?>"><?= $r['closed'] ?></span></td>
          <td class="text-center"><span class="<?= $r['overdue']>0?'text-red fw-600':'' ?>"><?= $r['overdue'] ?></span></td>
          <td class="td-muted fs-sm"><?= $r['last_login'] ? date('M j g:ia', strtotime($r['last_login'])) : 'Never' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- RECENT TEAM ACTIVITY -->
<div class="card section-gap">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-bolt"></i> Team Activity Feed</div>
  </div>
  <div class="card-body" style="padding:0">
    <?php if (empty($recentAll)): ?>
    <div class="empty-state" style="padding:28px"><i class="fa-solid fa-timeline"></i><h3>No Activity Yet</h3></div>
    <?php else: ?>
    <div>
      <?php foreach ($recentAll as $a): ?>
      <div style="display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid var(--border)">
        <div style="width:28px;height:28px;border-radius:50%;background:<?= $noteColors[$a['note_type']] ?>18;border:2px solid <?= $noteColors[$a['note_type']] ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="fa-solid <?= $noteIcons[$a['note_type']] ?>" style="color:<?= $noteColors[$a['note_type']] ?>;font-size:.65rem"></i>
        </div>
        <div style="flex:1;min-width:0">
          <div style="font-size:.82rem;color:var(--text-dark)">
            <strong><?= sanitize($a['rep_first']) ?></strong> logged a <strong><?= $a['note_type'] ?></strong> on <strong><?= sanitize($a['store_name']) ?></strong>
          </div>
          <div class="fs-xs text-muted" style="margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:500px"><?= sanitize($a['note_text']) ?></div>
        </div>
        <div class="fs-xs text-muted" style="flex-shrink:0"><?= date('M j g:ia', strtotime($a['created_at'])) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/sales_footer.php'; ?>
