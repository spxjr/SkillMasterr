<?php
require_once __DIR__ . '/includes/portal_header.php';
portalRequireLogin();
$pageTitle = 'Service History';
$clientId  = $portalUser['client_id'];
$db        = getDB();

$logs = $db->prepare("
    SELECT sl.service_date, sl.service_type, sl.technician,
           sl.description, sl.resolved, sl.created_at,
           g.game_name, cg.machine_number
    FROM service_logs sl
    LEFT JOIN client_games cg ON cg.id=sl.client_game_id
    LEFT JOIN games g ON g.id=cg.game_id
    WHERE sl.client_id=?
    ORDER BY sl.service_date DESC, sl.created_at DESC
");
$logs->execute([$clientId]);
$logs = $logs->fetchAll();

$openCount = count(array_filter($logs, fn($l)=>!$l['resolved']));
$typeCount = array_count_values(array_column($logs,'service_type'));
arsort($typeCount);
?>

<div class="page-header">
  <div>
    <h1><span class="accent">Service</span> History</h1>
    <div class="page-subtitle">All visits and maintenance records for your location</div>
  </div>
</div>

<?php if ($openCount > 0): ?>
<div class="alert alert-info" style="margin-bottom:20px">
  <i class="fa-solid fa-circle-info"></i>
  You have <strong><?= $openCount ?> open service ticket<?= $openCount!=1?'s':'' ?></strong> in progress. Texas Skill Masters will be in touch shortly.
</div>
<?php endif; ?>

<div class="grid-2 section-gap" style="grid-template-columns:3fr 1fr">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-screwdriver-wrench"></i> All Service Logs (<?= count($logs) ?>)</div>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Date</th><th>Type</th><th>Machine</th><th>Technician</th><th>Description</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
          <tr><td colspan="6"><div class="empty-state"><i class="fa-solid fa-wrench"></i><h3>No Service Logs</h3><p>Service visits will appear here once logged.</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($logs as $l): ?>
          <tr>
            <td class="td-muted"><?= formatDate($l['service_date']) ?></td>
            <td><span class="badge badge-blue"><?= sanitize($l['service_type']) ?></span></td>
            <td class="td-muted fs-sm"><?= $l['machine_number'] ? sanitize($l['machine_number']) : '—' ?><?= $l['game_name'] ? '<br><span style="color:var(--text-light)">'.sanitize($l['game_name']).'</span>' : '' ?></td>
            <td><?= sanitize($l['technician']) ?: '—' ?></td>
            <td class="td-muted fs-sm" style="max-width:260px"><?= sanitize($l['description']) ?></td>
            <td><?= $l['resolved'] ? '<span class="badge badge-green"><i class="fa-solid fa-check" style="font-size:.6rem"></i> Resolved</span>' : '<span class="badge badge-gold"><i class="fa-solid fa-clock" style="font-size:.6rem"></i> In Progress</span>' ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-chart-pie"></i> By Type</div></div>
      <div class="card-body">
        <?php if (empty($typeCount)): ?>
        <div class="text-muted fs-sm text-center">No data</div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px">
          <?php foreach ($typeCount as $type=>$count): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid var(--border)">
            <span class="fs-sm text-muted"><?= sanitize($type) ?></span>
            <span class="badge badge-blue"><?= $count ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fa-solid fa-info-circle"></i> Need Service?</div></div>
      <div class="card-body">
        <p class="fs-sm text-muted" style="line-height:1.6;margin-bottom:14px">
          If you notice an issue with one of your machines, send us a message and we'll schedule a visit.
        </p>
        <a href="<?= PORTAL_URL ?>/messages.php?new=1" class="btn btn-primary btn-sm" style="width:100%;justify-content:center">
          <i class="fa-solid fa-envelope"></i> Contact TSM
        </a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/portal_footer.php'; ?>
