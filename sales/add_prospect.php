<?php
require_once __DIR__ . '/includes/sales_header.php';
$pageTitle = 'Add Lead';
$b = SALES_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db->prepare("INSERT INTO prospects
        (store_name,store_type,address,city,state,zip,county,contact_name,contact_title,contact_phone,contact_email,status,priority,source,rep_id,assigned_to,machines_wanted,notes,follow_up_date)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([
           trim($_POST['store_name']),     $_POST['store_type'],
           trim($_POST['address']),        trim($_POST['city']),
           trim($_POST['state']??'TX'),    trim($_POST['zip']),
           trim($_POST['county']),         trim($_POST['contact_name']),
           trim($_POST['contact_title']),  trim($_POST['contact_phone']),
           trim($_POST['contact_email']),  $_POST['status']??'New Lead',
           $_POST['priority']??'Medium',   $_POST['source']??'Cold Call',
           $rep['id'],                     $rep['name'],
           !empty($_POST['machines_wanted'])?(int)$_POST['machines_wanted']:null,
           trim($_POST['notes']),          !empty($_POST['follow_up_date'])?$_POST['follow_up_date']:null,
       ]);
    flashMessage('success', "Lead '{$_POST['store_name']}' added! Log your first activity.");
    redirect($b . '/my_prospects.php');
}

$storeTypes = ['Bar','Restaurant','Convenience Store','Gas Station','Club / Nightclub','Supermarket','Smoke Shop','Other'];
$sources    = ['Cold Call','Drive By','Referral','Social Media','Website','Walk In','Other'];
$priorities = ['High','Medium','Low'];
?>

<div class="page-header">
  <div>
    <h1><span class="accent">Add</span> New Lead</h1>
    <div class="page-subtitle">Adding to your pipeline as <?= sanitize($rep['name']) ?></div>
  </div>
  <a href="<?= $b ?>/my_prospects.php" class="btn btn-ghost"><i class="fa-solid fa-arrow-left"></i> Back</a>
</div>

<form method="POST">
  <div class="grid-2" style="gap:20px;align-items:start">

    <div style="display:flex;flex-direction:column;gap:18px">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa-solid fa-store"></i> Store Information</div></div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-group col-span-2"><label>Store Name *</label><input type="text" name="store_name" required placeholder="e.g. Lone Star Bar & Grill" autofocus></div>
            <div class="form-group"><label>Store Type *</label><select name="store_type" required><?php foreach ($storeTypes as $t): ?><option><?= $t ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Machines Wanted</label><input type="number" name="machines_wanted" min="0" max="20" placeholder="# machines"></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa-solid fa-map-pin"></i> Location</div></div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-group col-span-2"><label>Address</label><input type="text" name="address" placeholder="Street address"></div>
            <div class="form-group"><label>City</label><input type="text" name="city"></div>
            <div class="form-group"><label>County</label><input type="text" name="county" placeholder="e.g. Travis, Williamson"></div>
            <div class="form-group" style="max-width:100px"><label>State</label><input type="text" name="state" value="TX" maxlength="2"></div>
            <div class="form-group" style="max-width:130px"><label>ZIP</label><input type="text" name="zip" maxlength="10"></div>
          </div>
        </div>
      </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:18px">
      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa-solid fa-user-tie"></i> Contact</div></div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-group"><label>Contact Name</label><input type="text" name="contact_name"></div>
            <div class="form-group"><label>Title</label><input type="text" name="contact_title" placeholder="Owner, Manager…"></div>
            <div class="form-group"><label>Phone</label><input type="text" name="contact_phone"></div>
            <div class="form-group"><label>Email</label><input type="email" name="contact_email"></div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header"><div class="card-title"><i class="fa-solid fa-tag"></i> Lead Details</div></div>
        <div class="card-body">
          <div class="form-grid">
            <div class="form-group"><label>Status</label><select name="status"><option>New Lead</option><option>Contacted</option><option>Interested</option></select></div>
            <div class="form-group"><label>Priority</label><select name="priority"><?php foreach ($priorities as $p): ?><option><?= $p ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Source</label><select name="source"><?php foreach ($sources as $s): ?><option><?= $s ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label>Follow-Up Date</label><input type="date" name="follow_up_date" value="<?= date('Y-m-d', strtotime('+3 days')) ?>"></div>
            <div class="form-group col-span-2"><label>Notes</label><textarea name="notes" rows="3" placeholder="First impression, observations, next steps…"></textarea></div>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:10px;justify-content:flex-end">
        <a href="<?= $b ?>/my_prospects.php" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary" style="min-width:160px"><i class="fa-solid fa-plus"></i> Add Lead</button>
      </div>
    </div>

  </div>
</form>

<?php require_once __DIR__ . '/includes/sales_footer.php'; ?>
