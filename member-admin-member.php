<?php
require_once __DIR__ . '/includes/member/ui.php';

mem_require_login();
$memberSession = mem_current_member();
if (empty($memberSession['is_admin'])) {
  http_response_code(403);
  mem_page_header('UGPSC Members | Admin', ['active' => 'admin']);
  ?>
  <div class="container" style="max-width:760px;">
    <div class="mem-card p-4">
      <h1 class="display-font h3 mb-2">Admin Access Required</h1>
      <p class="text-secondary mb-3">This area is reserved for membership administrators.</p>
      <a class="btn btn-outline-secondary" href="<?php echo mem_h(mem_base_url('/member-dashboard.php')); ?>">Back to dashboard</a>
    </div>
  </div>
  <?php
  mem_page_footer();
  exit;
}

$memberId = max(0, (int) ($_GET['member_id'] ?? 0));
$activeTab = (string) ($_GET['tab'] ?? 'overview');
$validTabs = ['overview', 'history', 'actions', 'email'];
if (!in_array($activeTab, $validTabs, true)) {
  $activeTab = 'overview';
}
$notices = [];
$errors = [];
$member = null;
$history = [];
$transactions = [];
$memberTransactions = [];
$quickRenewLink = null;
$notices = [];
$activeTab = (string) $activeTab;

if ($memberId <= 0) {
  $errors[] = 'No member selected.';
} else {
  $member = mem_load_member($memberId);
  if (!$member) {
    $errors[] = 'Member not found.';
  }
}

if (!$errors && $member) {
  global $pdo, $DB_OK;
  $currentLoginEnabled = mem_member_has_login_access($member ?? []);
  $currentIsAdmin = !empty($member['is_admin']);
  if (!$DB_OK || !($pdo instanceof PDO)) {
    $errors[] = 'Database unavailable.';
  } elseif (!mem_ready()) {
    $errors[] = 'Membership tables are not installed yet.';
  } else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $activeTab = 'actions';
      $csrf = (string) ($_POST['csrf_token'] ?? '');
      if (!mem_verify_csrf($csrf)) {
        $errors[] = 'Session check failed. Please try again.';
      } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'update_profile_admin') {
          $emailInput = strtolower(trim((string) ($_POST['email'] ?? '')));
          if ($emailInput === '' || !filter_var($emailInput, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
          } else {
            $stmt = $pdo->prepare(
              'UPDATE mem_member
               SET email = :email,
                   firstname = :firstname,
                   surname = :surname,
                   address1 = :address1,
                   address2 = :address2,
                   town = :town,
                   county = :county,
                   postcode = :postcode,
                   tel1 = :tel1,
                   tel2 = :tel2,
                   modified = NOW()
               WHERE id = :id
               LIMIT 1'
            );
            $stmt->execute([
              ':email' => $emailInput,
              ':firstname' => trim((string) ($_POST['firstname'] ?? '')),
              ':surname' => trim((string) ($_POST['surname'] ?? '')),
              ':address1' => trim((string) ($_POST['address1'] ?? '')),
              ':address2' => trim((string) ($_POST['address2'] ?? '')),
              ':town' => trim((string) ($_POST['town'] ?? '')),
              ':county' => trim((string) ($_POST['county'] ?? '')),
              ':postcode' => trim((string) ($_POST['postcode'] ?? '')),
              ':tel1' => trim((string) ($_POST['tel1'] ?? '')),
              ':tel2' => trim((string) ($_POST['tel2'] ?? '')),
              ':id' => $memberId,
            ]);
            mem_log_event('admin_profile_update', 'Admin updated member profile', null, $memberId);
            mem_log_change('profile_update', 'Admin updated member profile', null, $memberId);
            $notices[] = 'Member details updated.';
          }
        } elseif ($action === 'reset_password_admin') {
          $newPassword = mem_generate_random_password(12);
          $hash = password_hash($newPassword, PASSWORD_DEFAULT);
          $stmt = $pdo->prepare('UPDATE mem_member SET password_hash = :hash, modified = NOW() WHERE id = :id LIMIT 1');
          $stmt->execute([':hash' => $hash, ':id' => $memberId]);
          mem_log_event('admin_password_reset', 'Admin reset password', null, $memberId);
          mem_log_change('password_reset', 'Admin reset password (new password generated)', null, $memberId);
          $notices[] = 'Password reset. New password: ' . $newPassword;
        } elseif ($action === 'quick_renew_link' || $action === 'quick_renew_link_email') {
          $token = mem_create_magic_link($memberId, 'renewal', 24 * 14);
          if (!$token) {
            $errors[] = 'Could not generate quick renewal link.';
          } else {
            $quickRenewLink = mem_base_url('/member-renew-quick.php?token=' . urlencode($token));
            if ($action === 'quick_renew_link_email') {
              $emailTarget = strtolower(trim((string) ($member['email'] ?? '')));
              if ($emailTarget === '' || !filter_var($emailTarget, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Member email is not set or invalid.';
              } else {
                $subject = 'UGPSC quick renewal link';
                $html = '<p>Hello,</p>'
                  . '<p>Use this one-click renewal link:</p>'
                  . '<p><a href="' . mem_h($quickRenewLink) . '">' . mem_h($quickRenewLink) . '</a></p>'
                  . '<p>This link expires in 14 days and can only be used once.</p>';
                $text = "Use this one-click renewal link:\n" . $quickRenewLink . "\n\nThis link expires in 14 days and can only be used once.";
                $mailOk = mem_send_mail($emailTarget, $subject, $html, $text);
                if ($mailOk) {
                  mem_log_event('admin_quick_renew_email_sent', 'Quick renew link emailed', null, $memberId);
                  mem_log_change('quick_renew_email', 'Quick renew link emailed', null, $memberId);
                  $notices[] = 'Quick renew link emailed to ' . $emailTarget . '.';
                } else {
                  $errors[] = 'Email send failed on this environment. Copy the link below.';
                }
              }
            } else {
              $notices[] = 'Quick renew link generated below.';
            }
            mem_log_change('quick_renew_link', 'Quick renew link generated', null, $memberId);
          }
        } elseif ($action === 'toggle_login') {
          $newStatus = $currentLoginEnabled ? 0 : 1;
          $stmt = $pdo->prepare('UPDATE mem_member SET login_enabled = :status, modified = NOW() WHERE id = :id LIMIT 1');
          $stmt->execute([':status' => $newStatus, ':id' => $memberId]);
          mem_log_event('admin_toggle_login', 'Admin toggled login to ' . $newStatus, null, $memberId);
          mem_log_change('toggle_login', 'Admin toggled login to ' . ($newStatus ? 'enabled' : 'disabled'), null, $memberId);
          $notices[] = 'Login access ' . ($newStatus ? 'enabled' : 'disabled') . '.';
        } elseif ($action === 'toggle_admin') {
          $newStatus = $currentIsAdmin ? 0 : 1;
          $stmt = $pdo->prepare('UPDATE mem_member SET is_admin = :status, modified = NOW() WHERE id = :id LIMIT 1');
          $stmt->execute([':status' => $newStatus, ':id' => $memberId]);
          mem_log_event('admin_toggle_admin', 'Admin toggled admin flag to ' . $newStatus, null, $memberId);
          mem_log_change('toggle_admin', 'Admin toggled admin flag to ' . ($newStatus ? 'enabled' : 'disabled'), null, $memberId);
          $notices[] = 'Admin access ' . ($newStatus ? 'granted' : 'revoked') . '.';
        } elseif ($action === 'refund_transaction') {
          $txId = (int) ($_POST['transaction_id'] ?? 0);
          $refundAmount = (float) ($_POST['refund_amount'] ?? 0);
          $reason = trim((string) ($_POST['refund_reason'] ?? ''));
          $comment = trim((string) ($_POST['refund_comment'] ?? ''));
          if ($txId <= 0) {
            $errors[] = 'No transaction selected for refund.';
          } elseif (!mem_stripe_ready()) {
            $errors[] = 'Stripe is not configured for refunds.';
          } else {
            $txStmt = $pdo->prepare('SELECT * FROM mem_transaction WHERE id = :id AND member_id = :member_id AND archived = 0 LIMIT 1');
            $txStmt->execute([':id' => $txId, ':member_id' => $memberId]);
            $tx = $txStmt->fetch(PDO::FETCH_ASSOC);
            if (!$tx) {
              $errors[] = 'Transaction not found.';
            } elseif (strtolower((string) ($tx['payment_provider'] ?? '')) !== 'stripe') {
              $errors[] = 'Only Stripe transactions can be refunded here.';
            } elseif ((string) ($tx['status'] ?? '') === 'refunded') {
              $errors[] = 'Transaction already refunded.';
            } else {
              $intentId = trim((string) ($tx['provider_reference'] ?? ''));
              $txAmount = (float) ($tx['amount'] ?? 0);
              $currency = (string) ($tx['currency'] ?? 'GBP');
              if ($intentId === '') {
                $errors[] = 'Missing provider reference for refund.';
              } else {
                if ($refundAmount <= 0 || $refundAmount > $txAmount) {
                  $refundAmount = $txAmount;
                }
                $stripeError = null;
                $refund = mem_stripe_refund_payment_intent($intentId, $refundAmount, $reason, $stripeError);
                if (!$refund) {
                  $errors[] = $stripeError ?: 'Refund failed.';
                } else {
                  $note = 'Refunded ' . mem_money_display($refundAmount, $currency);
                  if ($reason !== '') {
                    $note .= ' (' . $reason . ')';
                  }
                  if ($comment !== '') {
                    $note .= ' | ' . $comment;
                  }
                  $upd = $pdo->prepare('UPDATE mem_transaction SET status = "refunded", notes = :notes, modified = NOW() WHERE id = :id LIMIT 1');
                  $upd->execute([
                    ':notes' => $note,
                    ':id' => $txId,
                  ]);
                  mem_log_event('admin_refund', 'Refunded transaction ' . $txId . ' via Stripe', null, $memberId);
                  mem_log_change('transaction_refund', 'Refunded ' . mem_money_display($refundAmount, $currency), $note, $memberId);
                  $notices[] = 'Refund requested for ' . mem_money_display($refundAmount, $currency) . '.';
                }
              }
            }
          }
        }
      }
    }

    // Refresh member data after any updates.
    $member = mem_load_member($memberId);

    if (mem_table_exists('mem_membership_year')) {
      try {
        $stmt = $pdo->prepare(
          'SELECT membership_year, source, transaction_id, notes, created,
                  amount, currency, paid_via
           FROM mem_membership_year
           WHERE member_id = :member_id AND archived = 0
           ORDER BY membership_year DESC'
        );
        $stmt->execute([':member_id' => $memberId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (Throwable $e) {
        // Fallback for older schemas without amount/currency/paid_via columns.
        $stmt = $pdo->prepare(
          'SELECT membership_year, source, transaction_id, notes, created
           FROM mem_membership_year
           WHERE member_id = :member_id AND archived = 0
           ORDER BY membership_year DESC'
        );
        $stmt->execute([':member_id' => $memberId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }
    }

    if (mem_table_exists('mem_transaction')) {
      $stmt = $pdo->prepare(
        'SELECT id, transaction_type, payment_provider, payment_method, provider_reference, amount, currency, status, paid_at, notes, created
         FROM mem_transaction
         WHERE member_id = :member_id
           AND archived = 0
         ORDER BY COALESCE(paid_at, created) DESC
         LIMIT 25'
      );
      $stmt->execute([':member_id' => $memberId]);
      $memberTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
  }
}

$adminDashboard = mem_base_url('/member-admin-dashboard.php');
$adminSearch = mem_base_url('/member-admin.php');

$fullName = mem_member_full_name($member ?? []);
$membershipNumber = (int) ($member['membership_number'] ?? 0);
$status = (string) ($member['membership_status'] ?? '');
$expires = mem_format_date_uk((string) ($member['membership_expires_at'] ?? ''));
$yearsPaid = (int) ($member['years_paid_count'] ?? 0);
$addressLine = trim((string) ($member['address1'] ?? '') . ' ' . (string) ($member['address2'] ?? '') . ' ' . (string) ($member['town'] ?? ''));
$postcode = trim((string) ($member['postcode'] ?? ''));
$county = trim((string) ($member['county'] ?? ''));
$country = trim((string) ($member['country'] ?? ''));
$loginEnabled = mem_member_has_login_access($member ?? []);
$isAdminFlag = !empty($member['is_admin']);
$email = (string) ($member['email'] ?? '');
$tel1 = trim((string) ($member['tel1'] ?? ''));
$tel2 = trim((string) ($member['tel2'] ?? ''));
$tel1Href = $tel1 !== '' ? preg_replace('/[^0-9+]/', '', $tel1) : '';
$tel2Href = $tel2 !== '' ? preg_replace('/[^0-9+]/', '', $tel2) : '';
$avatarInitial = strtoupper(substr($fullName !== '' ? $fullName : (string) ($member['firstname'] ?? 'M'), 0, 1));

mem_page_header('UGPSC Admin | Member', ['active' => 'admin']);
?>
<div class="container">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo mem_h($adminDashboard); ?>">Admin Dashboard</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo mem_h($adminSearch); ?>">Back to Find Member</a>
    </div>
    <span class="badge text-bg-light">Admin</span>
  </div>

  <?php if ($notices): ?>
  <div class="alert alert-success" role="alert">
    <?php echo mem_h(implode(' ', $notices)); ?>
  </div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="mem-card p-4">
      <h1 class="display-font h4 mb-3">Member</h1>
      <div class="alert alert-danger mb-0" role="alert"><?php echo mem_h(implode(' ', $errors)); ?></div>
    </div>
  <?php elseif ($member): ?>
    <div class="mem-card p-4 p-lg-5 mb-4">
      <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
          <h1 class="display-font h3 mb-1"><?php echo mem_h($fullName !== '' ? $fullName : 'Member'); ?></h1>
          <div class="text-secondary small">Member #<?php echo mem_h((string) $membershipNumber); ?> · ID <?php echo (int) $member['id']; ?></div>
          <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
            <span class="badge text-bg-light text-capitalize"><?php echo mem_h($status !== '' ? $status : 'unknown'); ?></span>
            <span class="badge bg-success-subtle text-success-emphasis">Expires <?php echo $expires !== '' ? mem_h($expires) : '—'; ?></span>
            <span class="badge bg-primary-subtle text-primary-emphasis">Years paid: <?php echo $yearsPaid; ?></span>
            <span class="badge <?php echo $loginEnabled ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis'; ?>">
              <?php echo $loginEnabled ? 'Login enabled' : 'Login disabled'; ?>
            </span>
            <span class="badge <?php echo $isAdminFlag ? 'bg-success-subtle text-success-emphasis' : 'bg-light text-secondary'; ?>">
              <?php echo $isAdminFlag ? 'Admin' : 'Non-admin'; ?>
            </span>
          </div>
        </div>
        <div>
          <div style="width:110px;height:110px;border-radius:12px;border:1px solid var(--mem-line);background:linear-gradient(135deg,#e9f2ed,#f8faf9);display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:700;color:#1f5a3f;">
            <?php echo mem_h($avatarInitial); ?>
          </div>
          <div class="text-secondary small mt-1 text-center">Photo upload coming soon</div>
        </div>
      </div>

      <div class="row g-3 g-lg-4 mt-3">
        <div class="col-md-5 col-lg-3">
          <div class="mem-label">Email</div>
          <?php if ($email !== ''): ?>
            <div class="fw-semibold"><a href="mailto:<?php echo mem_h($email); ?>"><?php echo mem_h($email); ?></a></div>
          <?php else: ?>
            <div class="fw-semibold text-secondary">Not set</div>
          <?php endif; ?>
        </div>
        <div class="col-md-3 col-lg-3">
          <div class="mem-label">Telephone</div>
          <?php if ($tel1 !== ''): ?>
            <div><a href="tel:<?php echo mem_h($tel1Href); ?>"><?php echo mem_h($tel1); ?></a></div>
          <?php else: ?>
            <div class="text-secondary">Not set</div>
          <?php endif; ?>
          <?php if ($tel2 !== ''): ?>
            <div class="text-secondary small"><a href="tel:<?php echo mem_h($tel2Href); ?>"><?php echo mem_h($tel2); ?></a></div>
          <?php endif; ?>
        </div>
        <div class="col-md-6 col-lg-6">
          <div class="mem-label">Address</div>
          <div>
            <?php echo mem_h($addressLine !== '' ? $addressLine : 'Not set'); ?>
            <?php if ($county !== '' || $country !== ''): ?>
              <?php echo ' ' . mem_h(trim($county . ' ' . $country)); ?>
            <?php endif; ?>
          </div>
          <?php if ($postcode !== ''): ?>
            <div class="text-secondary small"><?php echo mem_h($postcode); ?></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="mem-card p-4 p-lg-5">
      <ul class="nav nav-tabs" id="memberTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <a class="nav-link <?php echo $activeTab === 'overview' ? 'active' : ''; ?>" id="overview-tab" data-admin-tab="overview" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="<?php echo $activeTab === 'overview' ? 'true' : 'false'; ?>">Overview</a>
        </li>
        <li class="nav-item" role="presentation">
          <a class="nav-link <?php echo $activeTab === 'history' ? 'active' : ''; ?>" id="history-tab" data-admin-tab="history" data-bs-toggle="tab" href="#history" role="tab" aria-controls="history" aria-selected="<?php echo $activeTab === 'history' ? 'true' : 'false'; ?>">Membership History</a>
        </li>
        <li class="nav-item" role="presentation">
          <a class="nav-link <?php echo $activeTab === 'actions' ? 'active' : ''; ?>" id="actions-tab" data-admin-tab="actions" data-bs-toggle="tab" href="#actions" role="tab" aria-controls="actions" aria-selected="<?php echo $activeTab === 'actions' ? 'true' : 'false'; ?>">Actions</a>
        </li>
        <li class="nav-item" role="presentation">
          <a class="nav-link <?php echo $activeTab === 'email' ? 'active' : ''; ?>" id="email-tab" data-admin-tab="email" data-bs-toggle="tab" href="#email" role="tab" aria-controls="email" aria-selected="<?php echo $activeTab === 'email' ? 'true' : 'false'; ?>">Email Marketing</a>
        </li>
      </ul>
      <div class="tab-content pt-3">
        <div class="tab-pane fade <?php echo $activeTab === 'overview' ? 'show active' : ''; ?>" id="overview" role="tabpanel" aria-labelledby="overview-tab" data-admin-pane="overview">
          <div class="row g-3 g-lg-4">
            <div class="col-md-6 col-lg-4">
              <div class="p-3 border rounded h-100">
                <div class="mem-label">Created Via</div>
                <div><?php echo mem_h((string) ($member['created_via'] ?? 'n/a')); ?></div>
                <div class="text-secondary small">Show on web: <?php echo mem_h((string) ($member['showonweb'] ?? '')); ?></div>
              </div>
            </div>
            <div class="col-md-6 col-lg-4">
              <div class="p-3 border rounded h-100">
                <div class="mem-label">GDPR</div>
                <div class="text-secondary small">Policy accepted: <?php echo !empty($member['gdpr_policy_accepted']) ? 'Yes' : 'No'; ?></div>
                <div class="text-secondary small">Marketing opt-in: <?php echo !empty($member['gdpr_marketing_opt_in']) ? 'Yes' : 'No'; ?></div>
              </div>
            </div>
            <div class="col-md-6 col-lg-4">
              <div class="p-3 border rounded h-100">
                <div class="mem-label">Notes</div>
                <div class="small text-secondary"><?php echo mem_h((string) ($member['notes'] ?? 'None')); ?></div>
              </div>
            </div>
          </div>
        </div>

        <div class="tab-pane fade <?php echo $activeTab === 'history' ? 'show active' : ''; ?>" id="history" role="tabpanel" aria-labelledby="history-tab" data-admin-pane="history">
          <?php if ($history): ?>
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th scope="col">Year</th>
                    <th scope="col">Source</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Paid Via</th>
                    <th scope="col">Notes</th>
                    <th scope="col">Added</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($history as $row): ?>
                    <?php
                      $year = (int) ($row['membership_year'] ?? 0);
                      $created = mem_format_date_uk((string) ($row['created'] ?? ''));
                      $amount = (float) ($row['amount'] ?? 0.0);
                      $currency = (string) ($row['currency'] ?? 'GBP');
                      $paidVia = (string) ($row['paid_via'] ?? ($row['source'] ?? ''));
                    ?>
                    <tr>
                      <td><?php echo $year; ?></td>
                      <td class="text-capitalize"><?php echo mem_h((string) ($row['source'] ?? '')); ?></td>
                      <td><?php echo $amount > 0 ? mem_h(mem_money_display($amount, $currency)) : '—'; ?></td>
                      <td class="small text-secondary"><?php echo mem_h($paidVia); ?></td>
                      <td class="small text-secondary"><?php echo mem_h((string) ($row['notes'] ?? '')); ?></td>
                      <td class="text-secondary small"><?php echo $created !== '' ? mem_h($created) : '—'; ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-light border text-secondary" role="alert">No membership history recorded yet.</div>
          <?php endif; ?>
        </div>

        <div class="tab-pane fade <?php echo $activeTab === 'actions' ? 'show active' : ''; ?>" id="actions" role="tabpanel" aria-labelledby="actions-tab" data-admin-pane="actions">
          <div class="row g-3 g-lg-4">
            <div class="col-lg-6">
              <div class="p-3 border rounded h-100">
                <div class="mem-label mb-2">Update Details</div>
                <form method="post" class="row g-2">
                  <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
                  <input type="hidden" name="member_id" value="<?php echo (int) $memberId; ?>">
                  <input type="hidden" name="tab" value="actions">
                  <input type="hidden" name="action" value="update_profile_admin">
                  <div class="col-sm-6">
                    <label class="form-label mem-label mb-1" for="firstname">First name</label>
                    <input type="text" class="form-control form-control-sm" id="firstname" name="firstname" value="<?php echo mem_h((string) ($member['firstname'] ?? '')); ?>">
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label mem-label mb-1" for="surname">Surname</label>
                    <input type="text" class="form-control form-control-sm" id="surname" name="surname" value="<?php echo mem_h((string) ($member['surname'] ?? '')); ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label mem-label mb-1" for="email">Email</label>
                    <input type="email" class="form-control form-control-sm" id="email" name="email" value="<?php echo mem_h($email); ?>" required>
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label mem-label mb-1" for="tel1">Telephone</label>
                    <input type="text" class="form-control form-control-sm" id="tel1" name="tel1" value="<?php echo mem_h($tel1); ?>">
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label mem-label mb-1" for="tel2">Telephone 2</label>
                    <input type="text" class="form-control form-control-sm" id="tel2" name="tel2" value="<?php echo mem_h($tel2); ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label mem-label mb-1" for="address1">Address</label>
                    <input type="text" class="form-control form-control-sm" id="address1" name="address1" value="<?php echo mem_h((string) ($member['address1'] ?? '')); ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label mem-label mb-1" for="address2">Address 2</label>
                    <input type="text" class="form-control form-control-sm" id="address2" name="address2" value="<?php echo mem_h((string) ($member['address2'] ?? '')); ?>">
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label mem-label mb-1" for="town">Town/City</label>
                    <input type="text" class="form-control form-control-sm" id="town" name="town" value="<?php echo mem_h((string) ($member['town'] ?? '')); ?>">
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label mem-label mb-1" for="county">County</label>
                    <input type="text" class="form-control form-control-sm" id="county" name="county" value="<?php echo mem_h((string) ($member['county'] ?? '')); ?>">
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label mem-label mb-1" for="country">Country</label>
                    <input type="text" class="form-control form-control-sm" id="country" name="country" value="<?php echo mem_h((string) ($member['country'] ?? '')); ?>">
                  </div>
                  <div class="col-sm-6">
                    <label class="form-label mem-label mb-1" for="postcode">Postcode</label>
                    <input type="text" class="form-control form-control-sm" id="postcode" name="postcode" value="<?php echo mem_h($postcode); ?>">
                  </div>
                  <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-sm btn-mem-primary">Save changes</button>
                  </div>
                </form>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="p-3 border rounded h-100 d-flex flex-column gap-3">
                <div>
                  <div class="mem-label mb-1">Reset Password</div>
                  <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
                    <input type="hidden" name="member_id" value="<?php echo (int) $memberId; ?>">
                    <input type="hidden" name="tab" value="actions">
                    <input type="hidden" name="action" value="reset_password_admin">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Generate new password</button>
                  </form>
                  <div class="text-secondary small mt-1">A new random password will be generated and shown here for you to share.</div>
                </div>
                <div>
                  <div class="mem-label mb-1">Quick Renew Link</div>
                  <div class="d-flex flex-wrap gap-2">
                    <form method="post">
                      <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
                    <input type="hidden" name="member_id" value="<?php echo (int) $memberId; ?>">
                    <input type="hidden" name="tab" value="actions">
                    <input type="hidden" name="action" value="quick_renew_link">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Generate link</button>
                  </form>
                  <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
                    <input type="hidden" name="member_id" value="<?php echo (int) $memberId; ?>">
                    <input type="hidden" name="tab" value="actions">
                    <input type="hidden" name="action" value="quick_renew_link_email">
                    <button type="submit" class="btn btn-sm btn-outline-secondary" <?php echo $email === '' ? 'disabled' : ''; ?>>Generate &amp; email</button>
                    </form>
                  </div>
                  <div class="text-secondary small mt-1">14-day one-click renewal link using the current member email.</div>
                  <?php if ($quickRenewLink): ?>
                    <div class="alert alert-success py-2 mt-2 small" role="alert">
                      <div class="fw-semibold">Quick Renew Link</div>
                      <a href="<?php echo mem_h($quickRenewLink); ?>"><?php echo mem_h($quickRenewLink); ?></a>
                    </div>
                  <?php endif; ?>
                </div>
                <div>
                  <div class="mem-label mb-1">Login Access</div>
                  <form method="post" class="d-flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
                    <input type="hidden" name="member_id" value="<?php echo (int) $memberId; ?>">
                    <input type="hidden" name="tab" value="actions">
                    <input type="hidden" name="action" value="toggle_login">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                      <?php echo $loginEnabled ? 'Disable login' : 'Enable login'; ?>
                    </button>
                  </form>
                  <div class="text-secondary small mt-1">Current: <?php echo $loginEnabled ? 'Enabled' : 'Disabled'; ?></div>
                </div>
                <div>
                  <div class="mem-label mb-1">Admin Access</div>
                  <form method="post" class="d-flex gap-2" id="toggleAdminForm">
                    <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
                    <input type="hidden" name="member_id" value="<?php echo (int) $memberId; ?>">
                    <input type="hidden" name="tab" value="actions">
                    <input type="hidden" name="action" value="toggle_admin">
                    <button type="submit" class="btn btn-sm btn-outline-secondary" id="toggleAdminBtn">
                      <?php echo $isAdminFlag ? 'Revoke admin' : 'Grant admin'; ?>
                    </button>
                  </form>
                  <div class="text-secondary small mt-1">Current: <?php echo $isAdminFlag ? 'Admin' : 'Non-admin'; ?></div>
                </div>
              </div>
            </div>
          </div>
          <div class="row g-3 g-lg-4 mt-2">
            <div class="col-12">
              <div class="p-3 border rounded h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="mem-label mb-0">Recent Transactions</div>
                  <span class="badge text-bg-light">Stripe refunds supported</span>
                </div>
                <?php if ($memberTransactions): ?>
                  <div class="table-responsive">
                    <table class="table align-middle mb-0">
                      <thead>
                        <tr>
                          <th scope="col">Date</th>
                          <th scope="col">Type</th>
                          <th scope="col">Amount</th>
                          <th scope="col">Status</th>
                          <th scope="col">Provider</th>
                          <th scope="col">Reference</th>
                          <th scope="col">Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($memberTransactions as $tx): ?>
                          <?php
                            $paidAt = mem_format_date_uk((string) ($tx['paid_at'] ?? $tx['created'] ?? ''));
                            $amount = isset($tx['amount']) ? (float) $tx['amount'] : 0.0;
                            $currency = (string) ($tx['currency'] ?? 'GBP');
                            $isRefundable = strtolower((string) ($tx['payment_provider'] ?? '')) === 'stripe' && (string) ($tx['status'] ?? '') !== 'refunded';
                          ?>
                          <tr>
                            <td><?php echo mem_h($paidAt !== '' ? $paidAt : '—'); ?></td>
                            <td class="text-capitalize"><?php echo mem_h((string) ($tx['transaction_type'] ?? '')); ?></td>
                            <td><?php echo mem_h(mem_money_display($amount, $currency)); ?></td>
                            <td class="text-capitalize"><?php echo mem_h((string) ($tx['status'] ?? '')); ?></td>
                            <td><?php echo mem_h((string) ($tx['payment_provider'] ?? '')); ?></td>
                            <td class="small"><?php echo mem_h((string) ($tx['provider_reference'] ?? '')); ?></td>
                            <td>
                              <?php if ($isRefundable): ?>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#refundModal"
                                        data-tx-id="<?php echo (int) ($tx['id'] ?? 0); ?>"
                                        data-amount="<?php echo mem_h((string) $amount); ?>"
                                        data-currency="<?php echo mem_h($currency); ?>">
                                  Refund
                                </button>
                              <?php else: ?>
                                <span class="text-secondary small">—</span>
                              <?php endif; ?>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="text-secondary small">No transactions for this member yet.</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="tab-pane fade <?php echo $activeTab === 'email' ? 'show active' : ''; ?>" id="email" role="tabpanel" aria-labelledby="email-tab" data-admin-pane="email">
          <div class="alert alert-light border text-secondary" role="alert">
            Email marketing tools (lists and sends) will be added here soon.
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<style>
  /* Fallback modal styling if Bootstrap JS is unavailable */
  .admin-fallback-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1050;
    background: rgba(0, 0, 0, 0.45);
    align-items: center;
    justify-content: center;
  }
  .admin-fallback-modal.show {
    display: flex;
  }
  .admin-fallback-modal .modal-dialog {
    width: 100%;
    max-width: 420px;
    margin: 0 1rem;
  }
  .admin-fallback-modal .modal-content {
    border-radius: 12px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.18);
  }
</style>

<div class="modal fade admin-fallback-modal" id="adminConfirmModal" tabindex="-1" aria-labelledby="adminConfirmLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title display-font h5 mb-0" id="adminConfirmLabel">Confirm admin change</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="adminConfirmBody">
        Are you sure you want to change admin access?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-mem-primary" id="adminConfirmSubmit">Yes, proceed</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    const tabLinks = document.querySelectorAll('[data-admin-tab]');
    const panes = document.querySelectorAll('[data-admin-pane]');
    const toggleAdminBtn = document.getElementById('toggleAdminBtn');
    const toggleAdminForm = document.getElementById('toggleAdminForm');
    const adminConfirmBody = document.getElementById('adminConfirmBody');
    const adminConfirmSubmit = document.getElementById('adminConfirmSubmit');
    const adminConfirmModalEl = document.getElementById('adminConfirmModal');
    let adminModal = null;
    let usingFallbackModal = false;

    function ensureAdminModal() {
      if (adminModal || !adminConfirmModalEl) {
        return adminModal;
      }
      if (window.bootstrap && window.bootstrap.Modal) {
        adminModal = new window.bootstrap.Modal(adminConfirmModalEl);
        usingFallbackModal = false;
        return adminModal;
      }
      usingFallbackModal = true;
      return adminModal;
    }

    if (toggleAdminBtn && toggleAdminForm && adminConfirmBody && adminConfirmSubmit) {
      toggleAdminBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const modalInstance = ensureAdminModal();
        const isGrant = this.textContent.toLowerCase().includes('grant');
        const confirmText = isGrant
          ? 'Granting admin gives full access to member data and tools. Proceed?'
          : 'Revoke admin access for this member?';
        adminConfirmBody.textContent = confirmText;
        adminConfirmSubmit.onclick = function() {
          if (modalInstance && !usingFallbackModal) {
            modalInstance.hide();
          }
          if (usingFallbackModal && adminConfirmModalEl) {
            adminConfirmModalEl.classList.remove('show');
            adminConfirmModalEl.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
          }
          toggleAdminForm.submit();
        };
        if (modalInstance && !usingFallbackModal) {
          modalInstance.show();
        } else if (usingFallbackModal && adminConfirmModalEl) {
          adminConfirmModalEl.classList.add('show');
          adminConfirmModalEl.setAttribute('aria-hidden', 'false');
          document.body.classList.add('modal-open');
        } else if (window.confirm(confirmText)) {
          toggleAdminForm.submit();
        }
      });
    }

    function activateTab(target) {
      tabLinks.forEach(link => {
        const isActive = link.getAttribute('data-admin-tab') === target;
        link.classList.toggle('active', isActive);
        link.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
      panes.forEach(pane => {
        const isActive = pane.getAttribute('data-admin-pane') === target;
        pane.classList.toggle('show', isActive);
        pane.classList.toggle('active', isActive);
      });
      const url = new URL(window.location);
      url.searchParams.set('tab', target);
      window.history.replaceState({}, '', url);
    }
    tabLinks.forEach(link => {
      link.addEventListener('click', function(event) {
        event.preventDefault();
        const target = this.getAttribute('data-admin-tab');
        activateTab(target);
      });
    });
  })();
</script>
<div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title" id="refundModalLabel">Refund Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
          <input type="hidden" name="member_id" value="<?php echo (int) $memberId; ?>">
          <input type="hidden" name="tab" value="actions">
          <input type="hidden" name="action" value="refund_transaction">
          <input type="hidden" name="transaction_id" id="refund-tx-id" value="">
          <div class="mb-3">
            <label for="refund-amount" class="form-label mem-label">Amount</label>
            <input type="number" step="0.01" min="0.01" class="form-control" id="refund-amount" name="refund_amount" required>
            <div class="form-text">Max: <span id="refund-max"></span></div>
          </div>
          <div class="mb-3">
            <label for="refund-reason" class="form-label mem-label">Reason</label>
            <select class="form-select" id="refund-reason" name="refund_reason" required>
              <option value="requested_by_customer">Requested by customer</option>
              <option value="duplicate">Duplicate</option>
              <option value="fraudulent">Fraudulent</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="refund-comment" class="form-label mem-label">Comment (optional)</label>
            <input type="text" class="form-control" id="refund-comment" name="refund_comment" placeholder="Notes for internal tracking">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Process Refund</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const modalEl = document.getElementById('refundModal');
  if (!modalEl) return;
  const modalInstance = (window.bootstrap && window.bootstrap.Modal) ? new window.bootstrap.Modal(modalEl) : null;
  const triggerButtons = document.querySelectorAll('[data-bs-target="#refundModal"]');
  triggerButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      if (modalInstance) {
        modalInstance.show(btn);
      }
    });
  });

  modalEl.addEventListener('show.bs.modal', (event) => {
    const button = event.relatedTarget;
    const txId = button?.getAttribute('data-tx-id') || '';
    const amount = button?.getAttribute('data-amount') || '';
    const currency = button?.getAttribute('data-currency') || 'GBP';
    const txInput = modalEl.querySelector('#refund-tx-id');
    const amtInput = modalEl.querySelector('#refund-amount');
    const maxSpan = modalEl.querySelector('#refund-max');
    if (txInput) txInput.value = txId;
    if (amtInput) {
      amtInput.value = amount;
      amtInput.max = amount;
    }
    if (maxSpan) maxSpan.textContent = amount + ' ' + currency;
  });
});
</script>
<?php mem_page_footer(); ?>
