<?php
$v  = $formData ?? [];
$gf = fn($k,$d='') => htmlspecialchars($v[$k] ?? $d, ENT_QUOTES);
?>
<div class="form-grid">
  <div class="form-group col-span-2">
    <label>Game Name *</label>
    <input type="text" name="game_name" value="<?= $gf('game_name') ?>" required>
  </div>
  <div class="form-group">
    <label>Manufacturer</label>
    <input type="text" name="manufacturer" value="<?= $gf('manufacturer') ?>">
  </div>
  <div class="form-group">
    <label>Model</label>
    <input type="text" name="model" value="<?= $gf('model') ?>">
  </div>
  <div class="form-group">
    <label>Serial Number</label>
    <input type="text" name="serial_number" value="<?= $gf('serial_number') ?>">
  </div>
  <div class="form-group">
    <label>Purchase Date</label>
    <input type="date" name="purchase_date" value="<?= $gf('purchase_date') ?>">
  </div>
  <div class="form-group">
    <label>Game Type</label>
    <select name="game_type">
      <?php foreach (['Skill Game','Redemption','Arcade','Other'] as $t): ?>
      <option value="<?= $t ?>" <?= $gf('game_type','Skill Game')===$t?'selected':'' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group">
    <label>Status</label>
    <select name="status">
      <?php foreach (['Active','Inactive','Maintenance','Retired'] as $s): ?>
      <option value="<?= $s ?>" <?= $gf('status','Active')===$s?'selected':'' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group col-span-2">
    <label>Notes</label>
    <textarea name="notes"><?= $gf('notes') ?></textarea>
  </div>
</div>
