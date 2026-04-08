<?php
require_once __DIR__ . '/includes/member/ui.php';

mem_require_login();
$member = mem_load_member((int) ($_SESSION['mem_member']['id'] ?? 0));
if (!$member) {
  mem_logout();
  header('Location: ' . mem_base_url('/member-login.php'));
  exit;
}

$fullName = trim((string) ($member['firstname'] ?? '') . ' ' . (string) ($member['surname'] ?? ''));
$status = (string) ($member['membership_status'] ?? 'pending');
$expires = mem_format_date_uk((string) ($member['membership_expires_at'] ?? ''));
$quickRenewLink = null;
$quickRenewError = null;
$quickRenewNotice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'generate_quick_renew_link') {
  $csrf = (string) ($_POST['csrf_token'] ?? '');
  if (!mem_verify_csrf($csrf)) {
    $quickRenewError = 'Session check failed. Please try again.';
  } else {
    $token = mem_create_magic_link((int) $member['id'], 'renewal', 24 * 14);
    if ($token) {
      $quickRenewLink = mem_base_url('/member-renew-quick.php?token=' . urlencode($token));
    } else {
      $quickRenewError = 'Could not generate quick renew link.';
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string) ($_POST['action'] ?? '') === 'email_quick_renew_link') {
  $csrf = (string) ($_POST['csrf_token'] ?? '');
  if (!mem_verify_csrf($csrf)) {
    $quickRenewError = 'Session check failed. Please try again.';
  } else {
    $token = mem_create_magic_link((int) $member['id'], 'renewal', 24 * 14);
    if (!$token) {
      $quickRenewError = 'Could not generate quick renew link for email.';
    } else {
      $email = trim((string) ($member['email'] ?? ''));
      $quickRenewLink = mem_base_url('/member-renew-quick.php?token=' . urlencode($token));
      $subject = 'UGPSC quick renewal link';
      $html = '<p>Hello,</p>'
        . '<p>Use this one-click renewal link:</p>'
        . '<p><a href="' . mem_h($quickRenewLink) . '">' . mem_h($quickRenewLink) . '</a></p>'
        . '<p>This link expires in 14 days and can only be used once.</p>';
      $text = "Use this one-click renewal link:\n" . $quickRenewLink . "\n\nThis link expires in 14 days and can only be used once.";
      if ($email === '') {
        $quickRenewError = 'No member email is set for this account.';
      } else {
        $mailOk = mem_send_mail($email, $subject, $html, $text);
        if ($mailOk) {
          $quickRenewNotice = 'Quick renew link emailed to ' . $email . '.';
          mem_log_event('quick_renew_email_sent', 'Quick renew link email sent', null, (int) $member['id']);
        } else {
          $quickRenewError = 'Email send failed on this environment. Use the generated link manually below.';
        }
      }
    }
  }
}

mem_page_header('UGPSC Members | Dashboard', ['active' => 'dashboard']);
?>
<div class="container">
  <div class="row g-3 g-lg-4">
    <div class="col-lg-8">
      <div class="mem-card p-4">
        <h1 class="display-font h3 mb-1">Welcome<?php echo $fullName !== '' ? ', ' . mem_h($fullName) : ''; ?></h1>
        <p class="text-secondary mb-4">This is your member dashboard v1. Renewals and payment actions plug into this area next.</p>

        <div class="row g-3">
          <div class="col-md-6">
            <div class="p-3 rounded border h-100 bg-light-subtle">
              <div class="mem-label">Membership Status</div>
              <div class="fs-5 text-capitalize"><?php echo mem_h($status); ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-3 rounded border h-100 bg-light-subtle">
              <div class="mem-label">Expiry Date</div>
              <div class="fs-5"><?php echo $expires !== '' ? mem_h($expires) : 'Not set'; ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-3 rounded border h-100 bg-light-subtle">
              <div class="mem-label">Payment Method</div>
              <div class="fs-5"><?php echo mem_h((string) ($member['payment_method'] ?? 'Not set')); ?></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="p-3 rounded border h-100 bg-light-subtle">
              <div class="mem-label">Years Paid</div>
              <div class="fs-5"><?php echo (int) ($member['years_paid_count'] ?? 0); ?></div>
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
          <a class="btn btn-mem-primary" href="<?php echo mem_h(mem_base_url('/member-profile.php')); ?>">Update My Details</a>
          <a class="btn btn-outline-secondary" href="<?php echo mem_h(mem_base_url('/member-renew.php')); ?>">Renew Membership</a>
          <a class="btn btn-outline-secondary" href="#" aria-disabled="true">Payment History (next phase)</a>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="mem-card p-4 h-100">
        <h2 class="h5 display-font mb-3">Account Snapshot</h2>
        <div class="mb-2"><span class="mem-label d-block">Membership Number</span><?php echo (int) ($member['membership_number'] ?? 0); ?></div>
        <div class="mb-2"><span class="mem-label d-block">Email</span><?php echo mem_h((string) ($member['email'] ?? '')); ?></div>
        <div class="mb-2"><span class="mem-label d-block">Telephone</span><?php echo mem_h((string) ($member['tel1'] ?? 'Not set')); ?></div>
        <div class="mb-2"><span class="mem-label d-block">Mobile</span><?php echo mem_h((string) ($member['tel2'] ?? 'Not set')); ?></div>
        <div class="mb-0"><span class="mem-label d-block">Address</span><?php echo mem_h(trim((string) ($member['address1'] ?? '') . ' ' . (string) ($member['town'] ?? '') . ' ' . (string) ($member['postcode'] ?? ''))); ?></div>
        <hr>
        <div class="mem-label mb-2">Quick Renew Link (Dev)</div>
        <?php if ($quickRenewError): ?>
          <div class="alert alert-danger py-2 small" role="alert"><?php echo mem_h($quickRenewError); ?></div>
        <?php endif; ?>
        <?php if ($quickRenewNotice): ?>
          <div class="alert alert-success py-2 small" role="alert"><?php echo mem_h($quickRenewNotice); ?></div>
        <?php endif; ?>
        <?php if ($quickRenewLink): ?>
          <div class="alert alert-success py-2 small" role="alert">
            <strong>Generated:</strong><br>
            <a href="<?php echo mem_h($quickRenewLink); ?>"><?php echo mem_h($quickRenewLink); ?></a>
          </div>
        <?php endif; ?>
        <form method="post" class="mt-2">
          <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
          <input type="hidden" name="action" value="generate_quick_renew_link">
          <button type="submit" class="btn btn-sm btn-outline-secondary">Generate Quick Renew Link</button>
        </form>
        <?php if (mem_member_has_login_access($member) && (string) ($member['email'] ?? '') !== ''): ?>
          <form method="post" class="mt-2">
            <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
            <input type="hidden" name="action" value="email_quick_renew_link">
            <button type="submit" class="btn btn-sm btn-outline-secondary">Email Renew Link (Test)</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php mem_page_footer(); ?>
