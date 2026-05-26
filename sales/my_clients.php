<?php
require_once __DIR__ . '/includes/sales_header.php';
$pageTitle = 'My Clients';
$repId = $rep['id'];

$clients = $db->prepare("
    SELECT c.*, COUNT(DISTINCT cg.id) AS machine_count,
           COALESCE(SUM(r.net_revenue),0) AS total_rev,
           COALESCE(SUM(r.venue_share),0) AS venue_rev,
           p.status AS lead_status, p.id AS prospect_id
    FROM clients c
    JOIN prospects p ON p.client_id=c.id AND p.rep_id=?
    LEFT JOIN client_games cg ON cg.client_id=c.id AND cg.is_active=1
    LEFT JOIN revenue_entries r ON r.client_game_id=cg.id
    GROUP BY c.id ORDER BY c.business_name
");
$clients->execute([$repId]); $clients = $clients->fetchAll();
?>

<div class="page-header">
  <div>
    <h1><span class="accent">My</span> Clients</h1>
    <div class="page-subtitle">Clients you've converted — <?= count($clients) ?> total</div>
  </div>
</div>

<?php if (empty($clients)): ?>
<div class="card"><div class="empty-state" style="padding:60px"><i class="fa-solid fa-building-user"></i><h3>No Clients Yet</h3><p>Close a prospect to see your converted clients here.</p><a href="<?= SALES_URL ?>/my_prospects.php?filter=hot" class="btn btn-primary mt-4"><i class="fa-solid fa-fire"></i> Work Your Hot Leads</a></div></div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
  <?php foreach ($clients as $c): ?>
  <div class="card" style="transition:box-shadow .2s" onmouseover="this.style.boxShadow='var(--shadow)'" onmouseout="this.style.boxShadow=''">
    <div style="padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div>
        <div style="font-family:'Barlow Condensed',sans-serif;font-size:.68rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--gold-dark);margin-bottom:3px">Client</div>
        <div style="font-weight:700;font-size:1rem;color:var(--text-dark)"><?= sanitize($c['business_name']) ?></div>
        <div class="fs-xs text-muted"><?= sanitize($c['city']) ?>, <?= sanitize($c['state']) ?></div>
      </div>
      <span class="badge <?= $c['status']==='Active'?'badge-green':'badge-gray' ?>"><?= $c['status'] ?></span>
    </div>
    <div style="padding:16px 20px">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:14px">
        <div style="text-align:center">
          <div style="font-family:'Bebas Neue',sans-serif;font-size:1.5rem;color:var(--blue)"><?= $c['machine_count'] ?></div>
          <div class="fs-xs text-muted">Machines</div>
        </div>
        <div style="text-align:center">
          <div style="font-family:'Bebas Neue',sans-serif;font-size:1.3rem;color:var(--green);letter-spacing:.02em"><?= formatMoney($c['total_rev']) ?></div>
          <div class="fs-xs text-muted">Net Revenue</div>
        </div>
        <div style="text-align:center">
          <div style="font-family:'Bebas Neue',sans-serif;font-size:1.3rem;color:var(--gold-dark);letter-spacing:.02em"><?= formatMoney($c['venue_rev']) ?></div>
          <div class="fs-xs text-muted">Venue Share</div>
        </div>
      </div>
      <?php if ($c['contact_name']): ?>
      <div class="fs-sm text-muted" style="margin-bottom:12px"><i class="fa-solid fa-user" style="width:14px;color:var(--gold-dark)"></i> <?= sanitize($c['contact_name']) ?><?= $c['contact_phone']?' · '.'<a href="tel:'.sanitize($c['contact_phone']).'" style="color:var(--gold-dark);text-decoration:none">'.sanitize($c['contact_phone']).'</a>':'' ?></div>
      <?php endif; ?>
      <div style="display:flex;gap:8px">
        <span class="badge badge-blue"><?= sanitize($c['venue_type']) ?></span>
        <span class="badge badge-green"><i class="fa-solid fa-handshake" style="font-size:.6rem"></i> Converted</span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/sales_footer.php'; ?>
