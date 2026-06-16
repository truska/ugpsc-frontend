<?php
require_once __DIR__ . '/includes/member/ui.php';
require_once __DIR__ . '/includes/member/account-claims.php';
require_once __DIR__ . '/includes/member/campaigns.php';

mem_require_login();
$admin = mem_current_member();
if (empty($admin['is_admin'])) {
  http_response_code(403);
  mem_page_header('UGPSC Members | Admin', ['active' => 'admin']);
  ?>
  <div class="container" style="max-width:760px;">
    <div class="mem-card p-4">
      <h1 class="display-font h3 mb-2">Admin Access Required</h1>
      <p class="text-secondary">This area is reserved for membership administrators.</p>
    </div>
  </div>
  <?php
  mem_page_footer();
  exit;
}

global $pdo, $DB_OK;
$ready = mem_account_claim_ready() && $DB_OK && ($pdo instanceof PDO);
$errors = [];
$notice = null;
$status = trim((string) ($_GET['status'] ?? 'open'));
$search = trim((string) ($_GET['q'] ?? ''));
$validStatuses = ['open', 'pending_letter', 'letter_printed', 'identity_verified', 'email_pending', 'email_verified', 'completed', 'cancelled', 'all'];
if (!in_array($status, $validStatuses, true)) {
  $status = 'open';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = (string) ($_POST['csrf_token'] ?? '');
  $action = (string) ($_POST['action'] ?? '');
  if (!mem_verify_csrf($csrf)) {
    $errors[] = 'Session check failed. Please try again.';
  } elseif (!$ready) {
    $errors[] = 'The account claim queue is not installed yet.';
  } elseif ($action === 'request_claim') {
    $membershipNumber = max(0, (int) ($_POST['membership_number'] ?? 0));
    if ($membershipNumber <= 0) {
      $errors[] = 'Enter a membership number.';
    } else {
      mem_account_claim_request($membershipNumber, true);
      $notice = 'The claim request was checked and queued if the member is eligible.';
    }
  } elseif ($action === 'cancel_claim') {
    $claimId = max(0, (int) ($_POST['claim_id'] ?? 0));
    $stmt = $pdo->prepare(
      'UPDATE mem_account_claim
       SET status = "cancelled", modified = NOW()
       WHERE id = :id
         AND status NOT IN ("completed", "cancelled")
       LIMIT 1'
    );
    $stmt->execute([':id' => $claimId]);
    if ($stmt->rowCount() === 1) {
      mem_log_event('account_claim_cancelled', 'Account claim cancelled by administrator');
      $notice = 'Account claim cancelled.';
    }
  }
}

$printValue = trim((string) ($_GET['print'] ?? ''));
if ($ready && $printValue !== '') {
  $params = [];
  if ($printValue === 'pending') {
    $where = 'c.status = "pending_letter"';
  } elseif (ctype_digit($printValue) && (int) $printValue > 0) {
    $where = 'c.id = :id';
    $params[':id'] = (int) $printValue;
  } else {
    http_response_code(400);
    exit('Invalid print request.');
  }

  $stmt = $pdo->prepare(
    'SELECT c.*
     FROM mem_account_claim c
     WHERE ' . $where . '
       AND c.archived = 0
       AND c.status NOT IN ("completed", "cancelled")
       AND c.expires_at > NOW()
     ORDER BY c.requested_at ASC'
  );
  $stmt->execute($params);
  $letters = $stmt->fetchAll(PDO::FETCH_ASSOC);
  if ($letters) {
    $ids = array_map(static fn(array $row): int => (int) $row['id'], $letters);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $update = $pdo->prepare(
      'UPDATE mem_account_claim
       SET status = CASE WHEN status = "pending_letter" THEN "letter_printed" ELSE status END,
           printed_at = NOW(),
           printed_by_member_id = ?,
           modified = NOW()
       WHERE id IN (' . $placeholders . ')'
    );
    $update->execute(array_merge([(int) $admin['id']], $ids));
    foreach ($letters as $letter) {
      mem_log_event('account_claim_letter_printed', 'Account claim letter opened for printing', null, (int) $letter['member_id']);
    }
  }
  $stationery = mem_campaign_letter_stationery();
  ?>
  <!doctype html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Account Claim Letters</title>
    <style>
      @page { size: A4 portrait; margin: 20mm; }
      * { box-sizing: border-box; }
      body { margin: 0; color: #111; font: 11pt Arial, sans-serif; }
      .letter { break-after: page; page-break-after: always; }
      .letter:last-child { break-after: auto; page-break-after: auto; }
      .letterhead { display: flex; align-items: flex-start; justify-content: space-between; gap: 10mm; padding-bottom: 4mm; margin-bottom: 9mm; border-bottom: 1px solid #bbb; }
      .letterhead-logo { width: 30mm; max-height: 25mm; height: auto; object-fit: contain; object-position: left top; }
      .organisation { flex: 1; text-align: right; font-size: 8.5pt; line-height: 1.35; }
      .organisation-name { margin-bottom: 1mm; font-size: 12pt; font-weight: 700; }
      .address { margin: 0 0 14mm; line-height: 1.35; }
      .content { line-height: 1.5; }
      .claim-access { display: flex; align-items: center; justify-content: space-between; gap: 8mm; margin-top: 8mm; padding: 4mm; border: 1px solid #999; background: #f7f7f7; break-inside: avoid; page-break-inside: avoid; }
      .claim-details { flex: 1; }
      .claim-code { margin-top: 2mm; font-size: 16pt; font-weight: 700; letter-spacing: 0.18em; }
      .qr-code { width: 27mm; min-width: 27mm; height: 27mm; }
      .qr-code img, .qr-code canvas { width: 27mm !important; height: 27mm !important; }
      .sign-off { margin-top: 10mm; line-height: 1.5; }
      .tools { margin: 12px; }
      @media print { .tools { display: none; } }
    </style>
  </head>
  <body>
    <div class="tools"><button type="button" onclick="window.print()">Print Letters</button></div>
    <?php if (!$letters): ?>
      <div class="tools">No account claim letters are waiting to be printed.</div>
    <?php endif; ?>
    <?php foreach ($letters as $letter): ?>
      <section class="letter">
        <header class="letterhead">
          <div>
            <?php if ((string) ($stationery['logo_url'] ?? '') !== ''): ?>
              <img class="letterhead-logo" src="<?php echo mem_h((string) $stationery['logo_url']); ?>" alt="<?php echo mem_h((string) $stationery['company']); ?> logo">
            <?php endif; ?>
          </div>
          <div class="organisation">
            <div class="organisation-name"><?php echo mem_h((string) $stationery['company']); ?></div>
            <div><?php echo mem_h(implode(', ', (array) $stationery['address'])); ?></div>
            <div>
              <?php echo mem_h((string) $stationery['email']); ?>
              <?php if ((string) ($stationery['email'] ?? '') !== ''): ?>&nbsp; | &nbsp;<?php endif; ?>
              <?php echo mem_h((string) $stationery['website']); ?>
            </div>
          </div>
        </header>
        <div class="address">
          <?php foreach (['recipient_name', 'address1', 'address2', 'town', 'county', 'postcode', 'country'] as $field): ?>
            <?php if (trim((string) ($letter[$field] ?? '')) !== ''): ?>
              <div><?php echo mem_h((string) $letter[$field]); ?></div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <div class="content">
          <p>Dear <?php echo mem_h((string) $letter['recipient_name']); ?>,</p>
          <p>You asked to set up online access to your existing UGPSC membership account.</p>
          <p>Your membership number is <strong><?php echo (int) $letter['membership_number']; ?></strong>.</p>
          <p>Use the secure details below to confirm that you received this letter. You will then be asked to add and verify your email address before creating a password.</p>
        </div>
        <div class="claim-access">
          <div class="claim-details">
            <strong>Claim your account:</strong><br>
            Visit <?php echo mem_h(mem_base_url('/member-claim-account.php')); ?><br>
            Membership number: <strong><?php echo (int) $letter['membership_number']; ?></strong>
            <div class="claim-code"><?php echo mem_h((string) $letter['access_code']); ?></div>
          </div>
          <div class="qr-code" data-qr-url="<?php echo mem_h((string) $letter['claim_url']); ?>" aria-label="QR code to claim member account"></div>
        </div>
        <div class="sign-off">
          Kind regards<br>
          <strong><?php echo mem_h((string) $stationery['company']); ?></strong>
        </div>
      </section>
    <?php endforeach; ?>
    <?php if ($letters): ?>
      <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
      <script>
        window.addEventListener('load', function () {
          document.querySelectorAll('[data-qr-url]').forEach(function (element) {
            if (typeof QRCode !== 'undefined') {
              new QRCode(element, {
                text: element.getAttribute('data-qr-url'),
                width: 128,
                height: 128,
                correctLevel: QRCode.CorrectLevel.M
              });
            }
          });
          window.setTimeout(function () { window.print(); }, 350);
        });
      </script>
    <?php endif; ?>
  </body>
  </html>
  <?php
  exit;
}

$claims = [];
$counts = [];
if ($ready) {
  $countRows = $pdo->query(
    'SELECT status, COUNT(*) AS total
     FROM mem_account_claim
     WHERE archived = 0
     GROUP BY status'
  )->fetchAll(PDO::FETCH_KEY_PAIR);
  $counts = $countRows ?: [];

  $where = ['c.archived = 0'];
  $params = [];
  if ($status === 'open') {
    $where[] = 'c.status NOT IN ("completed", "cancelled")';
  } elseif ($status !== 'all') {
    $where[] = 'c.status = :status';
    $params[':status'] = $status;
  }
  if ($search !== '') {
    $where[] = '(c.membership_number = :membership_number OR LOWER(c.recipient_name) LIKE :search OR LOWER(c.postcode) LIKE :search)';
    $params[':membership_number'] = ctype_digit($search) ? (int) $search : 0;
    $params[':search'] = '%' . strtolower($search) . '%';
  }
  $stmt = $pdo->prepare(
    'SELECT c.*
     FROM mem_account_claim c
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY c.requested_at DESC
     LIMIT 250'
  );
  $stmt->execute($params);
  $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

mem_page_header('UGPSC Admin | Account Claims', ['active' => 'admin']);
?>
<div class="container">
  <div class="mem-card p-4 p-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
      <div>
        <h1 class="display-font h3 mb-1">Account Claim Letters</h1>
        <p class="text-secondary mb-0">Queue and track secure letters for imported members who need online access.</p>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?php echo mem_h(mem_base_url('/member-admin-dashboard.php')); ?>">Dashboard</a>
        <a class="btn btn-card-action" href="<?php echo mem_h(mem_base_url('/member-admin-account-claims.php?print=pending')); ?>" target="_blank">Print Pending</a>
      </div>
    </div>

    <?php foreach ($errors as $error): ?>
      <div class="alert alert-danger"><?php echo mem_h($error); ?></div>
    <?php endforeach; ?>
    <?php if ($notice): ?>
      <div class="alert alert-success"><?php echo mem_h($notice); ?></div>
    <?php endif; ?>
    <?php if (!$ready): ?>
      <div class="alert alert-warning">The account claim migration has not been installed.</div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
      <div class="col-lg-4">
        <form method="post" class="border rounded p-3 h-100 bg-light-subtle">
          <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
          <input type="hidden" name="action" value="request_claim">
          <div class="mem-label mb-2">Initiate A Claim</div>
          <label class="form-label" for="membership_number">Membership Number</label>
          <div class="input-group">
            <input class="form-control" type="number" id="membership_number" name="membership_number" min="1" required>
            <button class="btn btn-card-action" type="submit">Queue Letter</button>
          </div>
        </form>
      </div>
      <div class="col-lg-8">
        <div class="row g-2 h-100">
          <div class="col-6 col-md-3"><div class="border rounded p-3 h-100"><div class="mem-label">Pending</div><div class="fs-3 fw-bold"><?php echo (int) ($counts['pending_letter'] ?? 0); ?></div></div></div>
          <div class="col-6 col-md-3"><div class="border rounded p-3 h-100"><div class="mem-label">Printed</div><div class="fs-3 fw-bold"><?php echo (int) ($counts['letter_printed'] ?? 0); ?></div></div></div>
          <div class="col-6 col-md-3"><div class="border rounded p-3 h-100"><div class="mem-label">In Progress</div><div class="fs-3 fw-bold"><?php echo (int) (($counts['identity_verified'] ?? 0) + ($counts['email_pending'] ?? 0) + ($counts['email_verified'] ?? 0)); ?></div></div></div>
          <div class="col-6 col-md-3"><div class="border rounded p-3 h-100"><div class="mem-label">Completed</div><div class="fs-3 fw-bold"><?php echo (int) ($counts['completed'] ?? 0); ?></div></div></div>
        </div>
      </div>
    </div>

    <form method="get" class="row g-2 align-items-end mb-4">
      <div class="col-md-5">
        <label class="form-label mem-label" for="q">Search</label>
        <input class="form-control" id="q" name="q" value="<?php echo mem_h($search); ?>" placeholder="Member number, name or postcode">
      </div>
      <div class="col-md-4">
        <label class="form-label mem-label" for="status">Status</label>
        <select class="form-select" id="status" name="status">
          <?php foreach (['open' => 'Open', 'pending_letter' => 'Pending Letter', 'letter_printed' => 'Letter Printed', 'identity_verified' => 'Identity Verified', 'email_pending' => 'Email Pending', 'email_verified' => 'Email Verified', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'all' => 'All'] as $value => $label): ?>
            <option value="<?php echo mem_h($value); ?>" <?php echo $status === $value ? 'selected' : ''; ?>><?php echo mem_h($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-card-action" type="submit">Filter</button>
        <a class="btn btn-outline-secondary" href="<?php echo mem_h(mem_base_url('/member-admin-account-claims.php')); ?>">Reset</a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Member</th>
            <th>Address</th>
            <th>Requested</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$claims): ?>
            <tr><td colspan="5" class="text-secondary">No account claims match this view.</td></tr>
          <?php endif; ?>
          <?php foreach ($claims as $claim): ?>
            <tr>
              <td>
                <strong>#<?php echo (int) $claim['membership_number']; ?></strong><br>
                <?php echo mem_h((string) $claim['recipient_name']); ?>
              </td>
              <td class="small">
                <?php echo mem_h(implode(', ', array_filter([
                  $claim['address1'] ?? '',
                  $claim['address2'] ?? '',
                  $claim['town'] ?? '',
                  $claim['county'] ?? '',
                  $claim['postcode'] ?? '',
                  $claim['country'] ?? '',
                ]))); ?>
              </td>
              <td><?php echo mem_h(mem_format_date_uk((string) $claim['requested_at'])); ?></td>
              <td><span class="badge text-bg-light"><?php echo mem_h(ucwords(str_replace('_', ' ', (string) $claim['status']))); ?></span></td>
              <td>
                <div class="d-flex flex-wrap gap-2">
                  <?php if (!in_array((string) $claim['status'], ['completed', 'cancelled'], true)): ?>
                    <a class="btn btn-sm btn-card-action" target="_blank" href="<?php echo mem_h(mem_base_url('/member-admin-account-claims.php?print=' . (int) $claim['id'])); ?>">Print</a>
                    <form method="post">
                      <input type="hidden" name="csrf_token" value="<?php echo mem_h(mem_csrf_token()); ?>">
                      <input type="hidden" name="action" value="cancel_claim">
                      <input type="hidden" name="claim_id" value="<?php echo (int) $claim['id']; ?>">
                      <button class="btn btn-sm btn-outline-secondary" type="submit">Cancel</button>
                    </form>
                  <?php else: ?>
                    <span class="text-secondary small">No action needed</span>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php mem_page_footer(); ?>
