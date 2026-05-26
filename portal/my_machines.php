<?php
require_once __DIR__ . '/includes/portal_header.php';
portalRequireLogin();
$pageTitle = 'My Machines';
$clientId  = $portalUser['client_id'];
$db        = getDB();
$b         = PORTAL_URL;

$machines = $db->prepare("
    SELECT cg.*, g.game_name, g.manufacturer, g.model, g.serial_number, g.game_type,
           COALESCE(SUM(r.cash_in),0)      AS gross_in,
           COALESCE(SUM(r.cash_out),0)     AS gross_out,
           COALESCE(SUM(r.net_revenue),0)  AS total_net,
           COALESCE(SUM(r.tsm_share),0)    AS tsm_rev,
           COALESCE(SUM(r.venue_share),0)  AS venue_rev,
           COUNT(r.id) AS collections,
           MAX(r.entry_date) AS last_collection,
           MIN(r.entry_date) AS first_collection
    FROM client_games cg
    JOIN games g ON g.id=cg.game_id
    LEFT JOIN revenue_entries r ON r.client_game_id=cg.id
    WHERE cg.client_id=?
    GROUP BY cg.id
    ORDER BY cg.is_active DESC, venue_rev DESC
");
$machines->execute([$clientId]);
$machines = $machines->fetchAll();
?>

<div class="page-header">
  <div>
    <h1><span class="accent">My</span> Machines</h1>
    <div class="page-subtitle">All skill game units at your location</div>
  </div>
</div>

<?php foreach ($machines as $m): ?>
<div class="card section-gap">
  <div class="card-header">
    <div class="card-title">
      <i class="fa-solid fa-gamepad"></i>
      <?= sanitize($m['game_name']) ?>
      <?php if ($m['machine_number']): ?>
        <span style="font-weight:400;color:var(--text-muted);font-size:0.75rem">· <?= sanitize($m['machine_number']) ?></span>
      <?php endif; ?>
    </div>
    <?= $m['is_active'] ? '<span class="badge badge-green"><i class="fa-solid fa-circle" style="font-size:.5rem"></i> Active</span>' : '<span class="badge badge-gray">Removed</span>' ?>
  </div>
  <div class="card-body">
    <div class="grid-2" style="gap:24px">
      <div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px">
          <div>
            <div class="fs-xs text-muted" style="margin-bottom:3px">Manufacturer</div>
            <div class="fw-600"><?= sanitize($m['manufacturer']) ?></div>
          </div>
          <div>
            <div class="fs-xs text-muted" style="margin-bottom:3px">Model</div>
            <div class="fw-600"><?= sanitize($m['model']) ?></div>
          </div>
          <div>
            <div class="fs-xs text-muted" style="margin-bottom:3px">Serial #</div>
            <div class="fw-600 fs-sm"><?= sanitize($m['serial_number']) ?></div>
          </div>
          <div>
            <div class="fs-xs text-muted" style="margin-bottom:3px">Installed</div>
            <div class="fw-600"><?= formatDate($m['installed_date']) ?></div>
          </div>
          <div>
            <div class="fs-xs text-muted" style="margin-bottom:3px">Your Split</div>
            <div class="fw-700 text-green font-cond" style="font-size:1.1rem"><?= number_format(100 - $m['revenue_split'], 1) ?>%</div>
          </div>
          <div>
            <div class="fs-xs text-muted" style="margin-bottom:3px">Last Collection</div>
            <div class="fw-600"><?= $m['last_collection'] ? formatDate($m['last_collection']) : '—' ?></div>
          </div>
        </div>
      </div>
      <div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div style="background:var(--bg);border-radius:var(--radius);padding:14px;border:1px solid var(--border)">
            <div class="fs-xs text-muted">Total Net Revenue</div>
            <div class="fw-700 font-bebas" style="font-size:1.4rem;color:var(--text-dark)"><?= formatMoney($m['total_net']) ?></div>
          </div>
          <div style="background:var(--green-pale);border-radius:var(--radius);padding:14px;border:1px solid rgba(39,174,96,0.2)">
            <div class="fs-xs" style="color:var(--green)">Your Earnings</div>
            <div class="fw-700 font-bebas text-green" style="font-size:1.4rem"><?= formatMoney($m['venue_rev']) ?></div>
          </div>
          <div style="background:var(--bg);border-radius:var(--radius);padding:14px;border:1px solid var(--border)">
            <div class="fs-xs text-muted">Gross Cash In</div>
            <div class="fw-600 font-cond"><?= formatMoney($m['gross_in']) ?></div>
          </div>
          <div style="background:var(--bg);border-radius:var(--radius);padding:14px;border:1px solid var(--border)">
            <div class="fs-xs text-muted">Collections</div>
            <div class="fw-600 font-cond"><?= $m['collections'] ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<?php if (empty($machines)): ?>
<div class="card"><div class="empty-state"><i class="fa-solid fa-gamepad"></i><h3>No Machines On Record</h3><p>Contact Texas Skill Masters if you believe this is an error.</p></div></div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/portal_footer.php'; ?>
