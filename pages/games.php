<?php
require_once '../includes/config.php';
$pageTitle = 'Games';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $fields = [
            'game_name'     => $_POST['game_name']     ?? '',
            'manufacturer'  => $_POST['manufacturer']  ?? '',
            'model'         => $_POST['model']         ?? '',
            'serial_number' => $_POST['serial_number'] ?? '',
            'game_type'     => $_POST['game_type']     ?? 'Skill Game',
            'status'        => $_POST['status']        ?? 'Active',
            'purchase_date' => $_POST['purchase_date'] ?: null,
            'notes'         => $_POST['notes']         ?? '',
        ];
        if ($action === 'add') {
            $db->prepare("INSERT INTO games (game_name,manufacturer,model,serial_number,game_type,status,purchase_date,notes) VALUES (:game_name,:manufacturer,:model,:serial_number,:game_type,:status,:purchase_date,:notes)")->execute($fields);
            flashMessage('success', 'Game added!');
        } else {
            $fields['id'] = (int)$_POST['id'];
            $db->prepare("UPDATE games SET game_name=:game_name,manufacturer=:manufacturer,model=:model,serial_number=:serial_number,game_type=:game_type,status=:status,purchase_date=:purchase_date,notes=:notes WHERE id=:id")->execute($fields);
            flashMessage('success', 'Game updated!');
        }
        redirect(BASE_URL.'/pages/games.php');
    }
    if ($action === 'delete') {
        $db->prepare("DELETE FROM games WHERE id=?")->execute([(int)$_POST['id']]);
        flashMessage('success', 'Game removed.');
        redirect(BASE_URL.'/pages/games.php');
    }
}

$games = $db->query("
    SELECT g.*,
           COUNT(DISTINCT cg.client_id) AS location_count,
           SUM(CASE WHEN cg.is_active=1 THEN 1 ELSE 0 END) AS active_placements,
           COALESCE(SUM(r.net_revenue),0) AS total_rev
    FROM games g
    LEFT JOIN client_games cg ON cg.game_id=g.id
    LEFT JOIN revenue_entries r ON r.client_game_id=cg.id
    GROUP BY g.id
    ORDER BY g.game_name
")->fetchAll();

$editGame = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM games WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editGame = $stmt->fetch();
}

require_once '../includes/header.php';
?>

<div class="page-header">
  <h1><span>GAME</span> INVENTORY</h1>
  <button class="btn btn-primary" onclick="openModal('addGameModal')"><i class="fa-solid fa-plus"></i> Add Game</button>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fa-solid fa-gamepad"></i> All Games (<?= count($games) ?>)</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Game Name</th><th>Manufacturer</th><th>Model</th><th>Serial #</th><th>Type</th><th>Locations</th><th>Active Units</th><th>Total Revenue</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($games as $g): ?>
        <tr>
          <td class="fw-600"><?= sanitize($g['game_name']) ?></td>
          <td class="td-muted"><?= sanitize($g['manufacturer']) ?></td>
          <td class="td-muted"><?= sanitize($g['model']) ?></td>
          <td class="td-muted fs-sm"><?= sanitize($g['serial_number']) ?></td>
          <td><span class="badge badge-blue"><?= sanitize($g['game_type']) ?></span></td>
          <td class="text-center"><?= $g['location_count'] ?></td>
          <td class="text-center"><?= $g['active_placements'] ?></td>
          <td class="money-positive"><?= formatMoney($g['total_rev']) ?></td>
          <td>
            <?php $bc = ['Active'=>'badge-green','Inactive'=>'badge-red','Maintenance'=>'badge-gold','Retired'=>'badge-gray'][$g['status']] ?? 'badge-gray'; ?>
            <span class="badge <?= $bc ?>"><?= $g['status'] ?></span>
          </td>
          <td>
            <div class="btn-group">
              <a href="<?= BASE_URL ?>/pages/games.php?edit=<?= $g['id'] ?>" class="btn btn-outline btn-xs"><i class="fa-solid fa-pen"></i></a>
              <form method="POST" style="display:inline" onsubmit="return confirmDelete('Delete this game?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $g['id'] ?>">
                <button class="btn btn-danger btn-xs" type="submit"><i class="fa-solid fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php $formData = $editGame; ?>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addGameModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <div class="modal-title">Add New Game</div>
      <button class="modal-close" onclick="closeModal('addGameModal')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <?php $formData = null; include '_game_form.php'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addGameModal')">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Game</button>
      </div>
    </form>
  </div>
</div>

<?php if ($editGame): ?>
<div class="modal-overlay open" id="editGameModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-header">
      <div class="modal-title">Edit Game</div>
      <button class="modal-close" onclick="window.location='<?= BASE_URL ?>/pages/games.php'"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= $editGame['id'] ?>">
      <div class="modal-body">
        <?php $formData = $editGame; include '_game_form.php'; ?>
      </div>
      <div class="modal-footer">
        <a href="<?= BASE_URL ?>/pages/games.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Update</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
