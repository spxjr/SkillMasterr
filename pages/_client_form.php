<?php
// Shared form for add/edit client
// $editClient is set for edits, null for new
$v = $editClient ?? [];
$g = fn($k,$d='') => htmlspecialchars($v[$k] ?? $d, ENT_QUOTES);
?>
<div class="form-grid">
  <div class="form-section-title">Business Information</div>

  <div class="form-group col-span-2">
    <label>Business Name *</label>
    <input type="text" name="business_name" value="<?= $g('business_name') ?>" required>
  </div>

  <div class="form-group col-span-2">
    <label>Address</label>
    <input type="text" name="address" value="<?= $g('address') ?>">
  </div>

  <div class="form-group">
    <label>City</label>
    <input type="text" name="city" value="<?= $g('city') ?>">
  </div>

  <div class="form-group" style="max-width:120px">
    <label>State</label>
    <input type="text" name="state" value="<?= $g('state','TX') ?>" maxlength="2">
  </div>

  <div class="form-group" style="max-width:140px">
    <label>ZIP</label>
    <input type="text" name="zip" value="<?= $g('zip') ?>" maxlength="10">
  </div>

  <div class="form-group">
    <label>Business Phone</label>
    <input type="text" name="phone" value="<?= $g('phone') ?>">
  </div>

  <div class="form-group">
    <label>Business Email</label>
    <input type="email" name="email" value="<?= $g('email') ?>">
  </div>

  <div class="form-group">
    <label>Venue Type</label>
    <select name="venue_type">
      <?php foreach (['Bar','Restaurant','Convenience Store','Gaming Lounge','Other'] as $vt): ?>
      <option value="<?= $vt ?>" <?= ($g('venue_type','Bar')===$vt)?'selected':'' ?>><?= $vt ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group">
    <label>Status</label>
    <select name="status">
      <?php foreach (['Active','Inactive','Pending'] as $st): ?>
      <option value="<?= $st ?>" <?= ($g('status','Active')===$st)?'selected':'' ?>><?= $st ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <hr class="form-divider">
  <div class="form-section-title">Point of Contact</div>

  <div class="form-group">
    <label>Contact Name</label>
    <input type="text" name="contact_name" value="<?= $g('contact_name') ?>">
  </div>

  <div class="form-group">
    <label>Contact Title</label>
    <input type="text" name="contact_title" value="<?= $g('contact_title') ?>">
  </div>

  <div class="form-group">
    <label>Contact Phone</label>
    <input type="text" name="contact_phone" value="<?= $g('contact_phone') ?>">
  </div>

  <div class="form-group">
    <label>Contact Email</label>
    <input type="email" name="contact_email" value="<?= $g('contact_email') ?>">
  </div>

  <hr class="form-divider">
  <div class="form-section-title">Contract Details</div>

  <div class="form-group">
    <label>Contract Start</label>
    <input type="date" name="contract_start" value="<?= $g('contract_start') ?>">
  </div>

  <div class="form-group">
    <label>Contract End</label>
    <input type="date" name="contract_end" value="<?= $g('contract_end') ?>">
  </div>

  <div class="form-group col-span-2">
    <label>Notes</label>
    <textarea name="notes"><?= $g('notes') ?></textarea>
  </div>
</div>
