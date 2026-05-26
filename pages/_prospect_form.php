<?php
// Shared form partial for add/edit prospect
// $formData = null (add) or array (edit)
$v  = $formData ?? [];
$gf = fn($k, $d='') => htmlspecialchars($v[$k] ?? $d, ENT_QUOTES);

$storeTypes = ['Bar','Restaurant','Convenience Store','Gas Station','Club / Nightclub','Supermarket','Smoke Shop','Other'];
$statuses   = ['New Lead','Contacted','Interested','Proposal Sent','Negotiating','Converted','Not Interested','No Response'];
$priorities = ['High','Medium','Low'];
$sources    = ['Cold Call','Drive By','Referral','Social Media','Website','Walk In','Other'];
?>
<div class="form-grid">

  <div class="form-section-title">Store Information</div>

  <div class="form-group col-span-2">
    <label>Store / Business Name *</label>
    <input type="text" name="store_name" value="<?= $gf('store_name') ?>" required placeholder="e.g. Cold Beer & Whiskey">
  </div>

  <div class="form-group">
    <label>Store Type *</label>
    <select name="store_type" required>
      <?php foreach ($storeTypes as $t): ?>
      <option value="<?= $t ?>" <?= $gf('store_type','Bar')===$t?'selected':'' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group">
    <label>Machines Wanted</label>
    <input type="number" name="machines_wanted" value="<?= $gf('machines_wanted') ?>" min="0" max="20" placeholder="# of machines">
  </div>

  <hr class="form-divider">
  <div class="form-section-title">Location</div>

  <div class="form-group col-span-2">
    <label>Address</label>
    <input type="text" name="address" value="<?= $gf('address') ?>" placeholder="Street address">
  </div>

  <div class="form-group">
    <label>City</label>
    <input type="text" name="city" value="<?= $gf('city') ?>">
  </div>

  <div class="form-group">
    <label>County</label>
    <input type="text" name="county" value="<?= $gf('county') ?>" placeholder="e.g. Travis, Williamson">
  </div>

  <div class="form-group" style="max-width:120px">
    <label>State</label>
    <input type="text" name="state" value="<?= $gf('state','TX') ?>" maxlength="2">
  </div>

  <div class="form-group" style="max-width:140px">
    <label>ZIP</label>
    <input type="text" name="zip" value="<?= $gf('zip') ?>" maxlength="10">
  </div>

  <hr class="form-divider">
  <div class="form-section-title">Contact Information</div>

  <div class="form-group">
    <label>Contact Name</label>
    <input type="text" name="contact_name" value="<?= $gf('contact_name') ?>">
  </div>

  <div class="form-group">
    <label>Contact Title</label>
    <input type="text" name="contact_title" value="<?= $gf('contact_title') ?>" placeholder="Owner, Manager, GM…">
  </div>

  <div class="form-group">
    <label>Phone</label>
    <input type="text" name="contact_phone" value="<?= $gf('contact_phone') ?>">
  </div>

  <div class="form-group">
    <label>Email</label>
    <input type="email" name="contact_email" value="<?= $gf('contact_email') ?>">
  </div>

  <hr class="form-divider">
  <div class="form-section-title">Lead Details</div>

  <div class="form-group">
    <label>Status</label>
    <select name="status">
      <?php foreach ($statuses as $s): ?>
      <option value="<?= $s ?>" <?= $gf('status','New Lead')===$s?'selected':'' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group">
    <label>Priority</label>
    <select name="priority">
      <?php foreach ($priorities as $p): ?>
      <option value="<?= $p ?>" <?= $gf('priority','Medium')===$p?'selected':'' ?>><?= $p ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group">
    <label>Lead Source</label>
    <select name="source">
      <?php foreach ($sources as $s): ?>
      <option value="<?= $s ?>" <?= $gf('source','Cold Call')===$s?'selected':'' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="form-group">
    <label>Assigned To</label>
    <input type="text" name="assigned_to" value="<?= $gf('assigned_to') ?>" placeholder="Rep name">
  </div>

  <div class="form-group">
    <label>Last Contact Date</label>
    <input type="date" name="last_contact" value="<?= $gf('last_contact') ?>">
  </div>

  <div class="form-group">
    <label>Follow-Up Date</label>
    <input type="date" name="follow_up_date" value="<?= $gf('follow_up_date') ?>">
  </div>

  <div class="form-group col-span-2">
    <label>Notes</label>
    <textarea name="notes" rows="3" placeholder="Observations, objections, next steps…"><?= $gf('notes') ?></textarea>
  </div>

</div>
