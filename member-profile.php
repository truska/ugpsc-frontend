<?php
require_once __DIR__ . '/includes/member/ui.php';

mem_require_login();
$memberId = (int) ($_SESSION['mem_member']['id'] ?? 0);
$member = mem_load_member($memberId);
if (!$member) {
  mem_logout();
  header('Location: ' . mem_base_url('/member-login.php'));
  exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = (string) ($_POST['csrf_token'] ?? '');
  if (!mem_verify_csrf($csrf)) {
    $error = 'Session check failed. Please try again.';
  } else {
    $payload = [
      'salutation' => trim((string) ($_POST['salutation'] ?? '')),
      'firstname' => trim((string) ($_POST['firstname'] ?? '')),
      'surname' => trim((string) ($_POST['surname'] ?? '')),
      'address1' => trim((string) ($_POST['address1'] ?? '')),
      'address2' => trim((string) ($_POST['address2'] ?? '')),
      'town' => trim((string) ($_POST['town'] ?? '')),
      'county' => trim((string) ($_POST['county'] ?? '')),
      'country' => trim((string) ($_POST['country'] ?? '')),
      'postcode' => trim((string) ($_POST['postcode'] ?? '')),
      'tel1' => trim((string) ($_POST['tel1'] ?? '')),
      'tel2' => trim((string) ($_POST['tel2'] ?? '')),
      'gdpr_marketing_opt_in' => (string) ($_POST['gdpr_marketing_opt_in'] ?? ''),
    ];

    if (mem_update_profile($memberId, $payload, $error)) {
      $success = 'Your details have been updated.';
      $member = mem_load_member($memberId);
      $_SESSION['mem_member']['firstname'] = (string) ($member['firstname'] ?? '');
      $_SESSION['mem_member']['surname'] = (string) ($member['surname'] ?? '');
    }
  }
}

mem_page_header('UGPSC Members | My Details', ['active' => 'profile']);
?>
<div class="container" style="max-width:920px;">
  <div class="mem-card p-4 p-lg-5">
    <h1 class="display-font h3 mb-2">My Details</h1>
    <p class="text-secondary mb-4">Update your personal and contact information.</p>

    <?php if ($success): ?>
      <div class="alert alert-success" role="alert"><?php echo mem_h($success); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger" role="alert"><?php echo mem_h($error); ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="mem-label" for="salutation">Salutation</label>
          <input type="text" class="form-control" id="salutation" name="salutation" value="<?php echo mem_h((string) ($member['salutation'] ?? '')); ?>">
        </div>
        <div class="col-md-4">
          <label class="mem-label" for="firstname">First name(s)</label>
          <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo mem_h((string) ($member['firstname'] ?? '')); ?>">
        </div>
        <div class="col-md-5">
          <label class="mem-label" for="surname">Surname</label>
          <input type="text" class="form-control" id="surname" name="surname" value="<?php echo mem_h((string) ($member['surname'] ?? '')); ?>">
        </div>

        <div class="col-md-6">
          <label class="mem-label" for="address1">Address 1</label>
          <input type="text" class="form-control" id="address1" name="address1" value="<?php echo mem_h((string) ($member['address1'] ?? '')); ?>">
        </div>
        <div class="col-md-6">
          <label class="mem-label" for="address2">Address 2</label>
          <input type="text" class="form-control" id="address2" name="address2" value="<?php echo mem_h((string) ($member['address2'] ?? '')); ?>">
        </div>

        <div class="col-md-4">
          <label class="mem-label" for="town">Town</label>
          <input type="text" class="form-control" id="town" name="town" value="<?php echo mem_h((string) ($member['town'] ?? '')); ?>">
        </div>
        <div class="col-md-4">
          <label class="mem-label" for="county">County</label>
          <input type="text" class="form-control" id="county" name="county" value="<?php echo mem_h((string) ($member['county'] ?? '')); ?>">
        </div>
        <div class="col-md-4">
          <label class="mem-label" for="country">Country</label>
          <input type="text" class="form-control" id="country" name="country" value="<?php echo mem_h((string) ($member['country'] ?? '')); ?>">
        </div>

        <div class="col-md-4">
          <label class="mem-label" for="postcode">Post/Zip Eircode</label>
          <input type="text" class="form-control" id="postcode" name="postcode" value="<?php echo mem_h((string) ($member['postcode'] ?? '')); ?>">
        </div>
        <div class="col-md-4">
          <label class="mem-label" for="tel1">Tel 1</label>
          <input type="text" class="form-control" id="tel1" name="tel1" value="<?php echo mem_h((string) ($member['tel1'] ?? '')); ?>">
        </div>
        <div class="col-md-4">
          <label class="mem-label" for="tel2">Tel 2</label>
          <input type="text" class="form-control" id="tel2" name="tel2" value="<?php echo mem_h((string) ($member['tel2'] ?? '')); ?>">
        </div>
      </div>

      <div class="form-check mt-3">
        <input class="form-check-input" type="checkbox" value="1" id="gdpr_marketing_opt_in" name="gdpr_marketing_opt_in" <?php echo !empty($member['gdpr_marketing_opt_in']) ? 'checked' : ''; ?>>
        <label class="form-check-label" for="gdpr_marketing_opt_in">I agree to receive membership updates.</label>
      </div>

      <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-mem-primary">Save Changes</button>
        <a href="<?php echo mem_h(mem_base_url('/member-dashboard.php')); ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
      </div>
    </form>
  </div>
</div>
<?php mem_page_footer(); ?>
