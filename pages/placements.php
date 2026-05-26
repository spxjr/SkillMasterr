<?php
require_once '../includes/config.php';
$pageTitle = 'Placements';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $db->prepare("INSERT INTO client_games (client_id,game_id,machine_number,installed_date,revenue_split,notes) VALUES (?,?,?,?,?,?)")->execute([
            (int)$_POST['client_id'],
            (int)$_POST['game_id'],
            $_POST['machine_number'] ?? '',
            $_POST['installed_date'] ?: date('Y-m-d'),
            (float)($$_POST['revenue_split'] ?? 50),
            $_POST['notes'] ?? ''
        ]);
        flashMessage('success', 'Placement added!');
    }
    if ($action === 'deactivate') {
        $db->prepare("UPDATE client_games SET is_active=0, removed_date=CURDATE() WHERE id=?")->execute([(int)$_POST['id']]);
        flashMessage('success', 'Machine marked as removed.');
    }
    $redir = $_POST['redirect'] ?? BASE_URL.'/pages/placements.php';
    redirect($redir);
}

$placements = $db->query("
    SELECT cg.*, c.business_name, c.city, g.game_name, g.manufacturer, g.serial_number,
           COALESCE(SUM(r.net_revenue),0) AS total_rev,
           COALESCE(SUM(r.tsm_share),0)  AS tsm_rev,
           COUNT(r.id) AS collections
    FROM client_games cg
    JOIN clients c ON c.id=cg.client_id
    JOIN games g ON g.id=cg.game_id
    LEFT JOIN revenue_entries r ON r.client_game_id=cg.id
    GROUP BY cg.id
    ORDER BY cg.is_active DESC, c.business_name, cg.machine_number
")->fetchAll();

require_once '../includes/header.php';
?>

<div class="page-header">
  <h1><span>MACHINE</span> PLACEMENTS</h1>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-map-pin"></i> All Placements (<?= count($placements) ?>)</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Machine #</th><th>Client</th><th>Game</th><th>Serial</th><th>Installed</th><th>TSM Split</th><th>Collections</th><th>Total Revenue</th><th>TSM Earnings</th><th>Active</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($placements as $p): ?>
        <tr style="<?= !$p['is_active']?'opacity:0.5':'' ?>">
          <td class="fw-600"><?= sanitize($p['machine_number']) ?></td>
          <td><a href="<?= BASE_URL ?>/pages/client_detail.php?id=<?= $p['client_id'] ?>" style="color:var(--gold-light);text-decoration:none"><?= sanitize($p['business_name']) ?></a><br><span class="td-muted fs-sm"><?= sanitize($p['city']) ?></span></td>
          <td><?= sanitize($p['game_name']) ?><br><span class="td-muted fs-sm"><?= sanitize($p['manufacturer']) ?></span></td>
          <td class="td-muted fs-sm"><?= sanitize($p['serial_number']) ?></td>
          <td class="td-muted"><?= formatDate($p['installed_date']) ?></td>
          <td class="text-center"><?= number_format($p['revenue_split'],1) ?>%</td>
          <td class="text-center"><?= $p['collections'] ?></td>
          <td class="money-positive"><?= formatMoney($p['total_rev']) ?></td>
          <td class="text-gold fw-600"><?= formatMoney($p['tsm_rev']) ?></td>
          <td><?= $p['is_active'] ? '<span class="badge badge-green">Active</span>' : '<span class="badge badge-gray">Removed</span>' ?></td>
          <td>
            <?php if ($p['is_active']): ?>
            <form method="POST" style="display:inline" onsubmit="return confirmDelete('Mark this machine as removed?')">
              <input type="hidden" name="action" value="deactivate">
              <input type="hidden" name="id" value="<?= $p['id'] ?>">
              <button class="btn btn-danger btn-xs" type="submit"><i class="fa-solid fa-minus-circle"></i> Remove</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
