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

$baseUrl = mem_base_url('/member-admin-transactions.php');
$tab = (string) ($_GET['tab'] ?? 'summary');
$reportKey = trim((string) ($_GET['report'] ?? ''));
$selectedReportYear = (int) ($_GET['year'] ?? (int) date('Y'));
$printReport = (bool) ($_GET['print'] ?? false);
$validTabs = ['summary', 'transactions', 'reports'];
if (!in_array($tab, $validTabs, true)) {
  $tab = 'summary';
}
if ($reportKey !== '') {
  $tab = 'reports';
}
$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? '');
$typeFilter = (string) ($_GET['type'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 25);
$allowedPerPage = [10, 25, 50, 100];
if (!in_array($perPage, $allowedPerPage, true)) {
  $perPage = 25;
}
$offset = ($page - 1) * $perPage;

$errors = [];
$notices = [];
$transactions = [];
$total = 0;
$typeOptions = [];
$yearOptions = [];
$reportRows = [];

global $pdo, $DB_OK;
if (!$DB_OK || !($pdo instanceof PDO)) {
  $errors[] = 'Database unavailable.';
} elseif (!mem_ready() || !mem_table_exists('mem_transaction')) {
  $errors[] = 'Transaction tables are not installed yet.';
} else {
  // Populate membership years for reports.
  if (mem_table_exists('mem_membership_year')) {
    $yearStmt = $pdo->query('SELECT DISTINCT membership_year FROM mem_membership_year WHERE archived = 0 ORDER BY membership_year DESC');
    $yearOptions = array_map('intval', $yearStmt->fetchAll(PDO::FETCH_COLUMN));
    if (!$yearOptions) {
      $yearOptions = [(int) date('Y'), (int) date('Y') - 1];
    }
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = (string) ($_POST['csrf_token'] ?? '');
    if (!mem_verify_csrf($csrf)) {
      $errors[] = 'Session check failed. Please try again.';
    } else {
      $action = (string) ($_POST['action'] ?? '');
      if ($action === 'refund_transaction') {
        $txId = (int) ($_POST['transaction_id'] ?? 0);
        $refundAmount = (float) ($_POST['refund_amount'] ?? 0);
        $reason = trim((string) ($_POST['refund_reason'] ?? ''));
        $comment = trim((string) ($_POST['refund_comment'] ?? ''));
        if ($txId <= 0) {
          $errors[] = 'No transaction selected for refund.';
        } elseif (!mem_stripe_ready()) {
          $errors[] = 'Stripe is not configured for refunds.';
        } else {
          $txStmt = $pdo->prepare('SELECT * FROM mem_transaction WHERE id = :id AND archived = 0 LIMIT 1');
          $txStmt->execute([':id' => $txId]);
          $tx = $txStmt->fetch(PDO::FETCH_ASSOC);
          if (!$tx) {
            $errors[] = 'Transaction not found.';
          } elseif (strtolower((string) ($tx['payment_provider'] ?? '')) !== 'stripe') {
            $errors[] = 'Only Stripe transactions can be refunded here.';
          } elseif (strtolower((string) ($tx['status'] ?? '')) === 'refunded') {
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
                mem_log_event('admin_refund', 'Refunded transaction ' . $txId . ' via Stripe', null, (int) ($tx['member_id'] ?? 0));
                mem_log_change('transaction_refund', 'Refunded ' . mem_money_display($refundAmount, $currency), $note, (int) ($tx['member_id'] ?? 0));
                $notices[] = 'Refund requested for ' . mem_money_display($refundAmount, $currency) . '.';
              }
            }
          }
        }
      }
    }
  }

  $where = ['t.archived = 0'];
  $params = [];

  if ($search !== '') {
    $where[] = '(LOWER(t.provider_reference) LIKE :like
                OR LOWER(t.payment_provider) LIKE :like
                OR LOWER(t.payment_method) LIKE :like
                OR LOWER(m.email) LIKE :like
                OR LOWER(CONCAT(m.firstname, " ", m.surname)) LIKE :like
                OR m.membership_number = :member_number)';
    $params[':like'] = '%' . strtolower($search) . '%';
    $params[':member_number'] = ctype_digit($search) ? (int) $search : 0;
  }

  if ($statusFilter !== '') {
    $where[] = 't.status = :status';
    $params[':status'] = $statusFilter;
  }

  if ($typeFilter !== '') {
    $where[] = 't.transaction_type = :type';
    $params[':type'] = $typeFilter;
  }

  $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

  // Type options for filter dropdown.
  $typeSql = 'SELECT DISTINCT transaction_type FROM mem_transaction WHERE archived = 0 ORDER BY transaction_type ASC';
  $typeStmt = $pdo->query($typeSql);
  $typeOptions = array_filter(array_map('trim', $typeStmt->fetchAll(PDO::FETCH_COLUMN)));

  $countSql = 'SELECT COUNT(*) FROM mem_transaction t
               LEFT JOIN mem_member m ON m.id = t.member_id
               ' . $whereSql;
  $countStmt = $pdo->prepare($countSql);
  foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
  }
  $countStmt->execute();
  $total = (int) $countStmt->fetchColumn();

  $listSql = 'SELECT
                t.id, t.member_id, t.transaction_type, t.payment_provider, t.payment_method,
                t.provider_reference, t.amount, t.currency, t.status, t.paid_at, t.notes, t.created,
                m.membership_number, m.firstname, m.surname
              FROM mem_transaction t
              LEFT JOIN mem_member m ON m.id = t.member_id
              ' . $whereSql . '
              ORDER BY COALESCE(t.paid_at, t.created) DESC, t.id DESC
              LIMIT :limit OFFSET :offset';
  $listStmt = $pdo->prepare($listSql);
  foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
  }
  $listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
  $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $listStmt->execute();
  $transactions = $listStmt->fetchAll(PDO::FETCH_ASSOC);

  // Reports data (member list by year).
  if ($tab === 'reports' && $reportKey === 'member_list' && mem_table_exists('mem_membership_year')) {
    $reportYear = $selectedReportYear > 0 ? $selectedReportYear : (int) date('Y');
    $stmt = $pdo->prepare(
      'SELECT m.membership_number, m.firstname, m.surname, m.email, m.tel1, m.postcode
       FROM mem_membership_year y
       JOIN mem_member m ON m.id = y.member_id
       WHERE y.archived = 0
         AND y.membership_year = :year
       ORDER BY m.membership_number ASC'
    );
    $stmt->execute([':year' => $reportYear]);
    $reportRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$reportRows) {
      $notices[] = 'No members found for ' . mem_h((string) $reportYear) . '.';
    }
  }
}

// Printable report view (minimal chrome)
if ($reportKey === 'member_list' && $printReport) {
  if (!$reportRows && mem_table_exists('mem_membership_year')) {
    $reportYear = $selectedReportYear > 0 ? $selectedReportYear : (int) date('Y');
    $stmt = $pdo->prepare(
      'SELECT m.membership_number, m.firstname, m.surname, m.email, m.tel1, m.postcode
       FROM mem_membership_year y
       JOIN mem_member m ON m.id = y.member_id
       WHERE y.archived = 0
         AND y.membership_year = :year
       ORDER BY m.membership_number ASC'
    );
    $stmt->execute([':year' => $reportYear]);
    $reportRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  $reportYear = $selectedReportYear > 0 ? $selectedReportYear : (int) date('Y');
  $totalMembers = count($reportRows);
  ?>
  <!doctype html>
  <html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo mem_h($siteName); ?> Member List <?php echo mem_h((string) $reportYear); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
      body { padding: 24px; }
      @media print {
        .hide-print { display: none !important; }
      }
    </style>
  </head>
  <body>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-2">
        <img src="<?php echo mem_h($logoUrl); ?>" alt="<?php echo mem_h($logoName); ?> logo" style="height:48px;width:auto;">
        <div>
          <div class="text-uppercase text-secondary small fw-semibold">Report</div>
          <h1 class="h4 mb-0">Member List — <?php echo mem_h((string) $reportYear); ?></h1>
          <div class="small text-secondary">Total members: <?php echo (int) $totalMembers; ?></div>
        </div>
      </div>
      <div class="hide-print">
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print / PDF</button>
      </div>
    </div>
    <?php if ($reportRows): ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead class="table-light">
            <tr>
              <th scope="col">Member #</th>
              <th scope="col">Name</th>
              <th scope="col">Email</th>
              <th scope="col">Tel</th>
              <th scope="col">Postcode</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reportRows as $row): ?>
              <tr>
                <td><?php echo mem_h((string) ($row['membership_number'] ?? '')); ?></td>
                <td><?php echo mem_h(trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['surname'] ?? ''))); ?></td>
                <td><?php echo mem_h((string) ($row['email'] ?? '')); ?></td>
                <td><?php echo mem_h((string) ($row['tel1'] ?? '')); ?></td>
                <td><?php echo mem_h((string) ($row['postcode'] ?? '')); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-light border text-secondary" role="alert">
        No data found for <?php echo mem_h((string) $reportYear); ?>.
      </div>
    <?php endif; ?>
  </body>
  </html>
  <?php
  exit;
}

$siteBaseUrl = cms_base_url();
$siteName = trim((string) cms_pref('prefSiteName', 'WCCMS'));
$logoName = trim((string) cms_pref('prefLogoName', $siteName));
$logoFile = trim((string) cms_pref('prefLogo', ''));
if ($logoFile === '') {
  $logoFile = trim((string) cms_pref('prefLogo1', ''));
}
if ($logoFile === '') {
  $logoFile = 'ugpsc-logo.png';
}
if (preg_match('#^https?://#i', $logoFile) || str_starts_with($logoFile, '/')) {
  $logoUrl = $logoFile;
} else {
  $logoUrl = $siteBaseUrl . '/filestore/images/logos/' . ltrim($logoFile, '/');
}
$adminDashboard = mem_base_url('/member-admin-dashboard.php');
$statusOptions = ['', 'paid', 'pending', 'failed', 'refunded', 'cancelled'];
$pageTitle = 'UGPSC Admin | Financial';
mem_page_header($pageTitle, ['active' => 'admin']);
?>
<div class="container">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo mem_h($adminDashboard); ?>">Admin Dashboard</a>
    </div>
    <span class="badge text-bg-light">Admin</span>
  </div>

  <div class="mem-card p-4 p-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="display-font h3 mb-1">Transactions</h1>
        <p class="text-secondary mb-0">Membership payments via card (currently emulated Stripe).</p>
      </div>
      <div><span class="badge text-bg-light">Financial</span></div>
    </div>

    <ul class="nav nav-tabs mb-3">
      <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'summary' ? 'active' : ''; ?>" href="<?php echo mem_h($baseUrl . '?tab=summary'); ?>">Summary</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'transactions' ? 'active' : ''; ?>" href="<?php echo mem_h($baseUrl . '?tab=transactions'); ?>">Transactions</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $tab === 'reports' ? 'active' : ''; ?>" href="<?php echo mem_h($baseUrl . '?tab=reports'); ?>">Reports</a>
      </li>
    </ul>

    <?php if ($errors): ?>
      <div class="alert alert-danger" role="alert"><?php echo mem_h(implode(' ', $errors)); ?></div>
    <?php endif; ?>
    <?php if ($notices): ?>
      <div class="alert alert-success" role="alert"><?php echo mem_h(implode(' ', $notices)); ?></div>
    <?php endif; ?>

    <?php if ($tab === 'summary'): ?>
      <div class="alert alert-light border text-secondary" role="alert">
        Financial summary widgets will appear here (totals, recent activity, charts).
      </div>
    <?php elseif ($tab === 'reports'): ?>
      <div class="row g-3">
        <div class="col-lg-6 col-xl-5">
          <div class="card shadow-sm">
            <div class="card-body">
              <h5 class="card-title mb-2">Member List</h5>
              <p class="card-text text-secondary small mb-3">Generate a printable list of members for the selected membership year.</p>
              <form method="get" action="<?php echo mem_h($baseUrl); ?>">
                <input type="hidden" name="tab" value="reports">
                <input type="hidden" name="report" value="member_list">
                <div class="mb-3">
                  <label for="report-year" class="form-label mem-label mb-1">Membership Year</label>
                  <select class="form-select" id="report-year" name="year">
                    <?php foreach ($yearOptions as $yearOpt): ?>
                      <option value="<?php echo (int) $yearOpt; ?>" <?php echo (int) $yearOpt === $selectedReportYear ? 'selected' : ''; ?>>
                        <?php echo (int) $yearOpt; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <button type="submit" class="btn btn-mem-primary">Generate Report</button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <?php if ($reportKey === 'member_list'): ?>
        <div class="mem-card p-3 p-lg-4 mt-4 report-output">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
              <div class="d-flex align-items-center gap-2">
                <img src="<?php echo mem_h($logoUrl); ?>" alt="<?php echo mem_h($logoName); ?> logo" style="height:42px;width:auto;">
                <div>
                  <div class="mem-label mb-1">Report</div>
                  <h2 class="h4 mb-0">Member List — <?php echo mem_h((string) $selectedReportYear); ?></h2>
                </div>
              </div>
              <div class="d-flex gap-2 hide-print">
                <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?php echo mem_h(mem_base_url('/member-admin-report-member-list.php') . '?year=' . (int) $selectedReportYear); ?>">Open Print View</a>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="export-csv-btn">Export CSV</button>
              </div>
            </div>
            <div class="mb-3">
              <div class="fw-semibold">Total members in <?php echo mem_h((string) $selectedReportYear); ?>: <?php echo count($reportRows); ?></div>
          </div>
          <?php if ($reportRows): ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle" id="member-report-table">
                <thead class="table-light">
                  <tr>
                    <th scope="col">Member #</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Tel</th>
                    <th scope="col">Postcode</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($reportRows as $row): ?>
                    <tr>
                      <td><?php echo mem_h((string) ($row['membership_number'] ?? '')); ?></td>
                      <td><?php echo mem_h(trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['surname'] ?? ''))); ?></td>
                      <td><?php echo mem_h((string) ($row['email'] ?? '')); ?></td>
                      <td><?php echo mem_h((string) ($row['tel1'] ?? '')); ?></td>
                      <td><?php echo mem_h((string) ($row['postcode'] ?? '')); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-light border text-secondary mb-0" role="alert">
              No data found for <?php echo mem_h((string) $selectedReportYear); ?>.
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php elseif (!$errors): ?>
      <form class="row g-3 align-items-end mb-4" method="get" action="<?php echo mem_h($baseUrl); ?>">
        <div class="col-lg-5">
          <label for="q" class="form-label mem-label mb-1">Search (ref, provider, member name/number, email)</label>
          <input type="text" class="form-control" id="q" name="q" value="<?php echo mem_h($search); ?>" placeholder="e.g. SUMUP ref or member number">
        </div>
        <div class="col-sm-4 col-lg-2">
          <label for="type" class="form-label mem-label mb-1">Type</label>
          <select class="form-select" id="type" name="type">
            <option value="">Any</option>
            <?php foreach ($typeOptions as $opt): ?>
              <option value="<?php echo mem_h($opt); ?>" <?php echo $typeFilter === $opt ? 'selected' : ''; ?>>
                <?php echo mem_h(ucfirst($opt)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-4 col-lg-2">
          <label for="status" class="form-label mem-label mb-1">Status</label>
          <select class="form-select" id="status" name="status">
            <?php foreach ($statusOptions as $opt): ?>
              <option value="<?php echo mem_h($opt); ?>" <?php echo $statusFilter === $opt ? 'selected' : ''; ?>>
                <?php echo $opt === '' ? 'Any' : ucfirst($opt); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-4 col-lg-2">
          <label for="per_page" class="form-label mem-label mb-1">Page Size</label>
          <select class="form-select" id="per_page" name="per_page">
            <?php foreach ($allowedPerPage as $size): ?>
              <option value="<?php echo (int) $size; ?>" <?php echo $perPage === $size ? 'selected' : ''; ?>><?php echo (int) $size; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-4 col-lg-3 d-flex gap-2">
          <button type="submit" class="btn btn-mem-primary flex-grow-1">Filter</button>
          <a class="btn btn-outline-secondary" href="<?php echo mem_h(mem_base_url('/member-admin-transactions.php')); ?>">Reset</a>
        </div>
      </form>

      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="text-secondary small">
          <?php
            $from = $total > 0 ? $offset + 1 : 0;
            $to = min($offset + $perPage, $total);
          ?>
          Showing <?php echo mem_h((string) $from); ?>–<?php echo mem_h((string) $to); ?> of <?php echo mem_h((string) $total); ?> transactions
          <?php if ($search !== ''): ?>
            for “<?php echo mem_h($search); ?>”
          <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
          <?php
            $prevPage = max(1, $page - 1);
            $nextPage = $page + 1;
            $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
            $prevDisabled = $page <= 1 ? 'disabled' : '';
            $nextDisabled = $page >= $totalPages ? 'disabled' : '';
            $baseParams = ['q' => $search, 'status' => $statusFilter, 'type' => $typeFilter, 'per_page' => $perPage];
          ?>
          <a class="btn btn-outline-secondary btn-sm <?php echo $prevDisabled; ?>"
             href="<?php echo mem_h(mem_base_url('/member-admin-transactions.php') . '?' . http_build_query(array_merge($baseParams, ['page' => $prevPage]))); ?>">Prev</a>
          <a class="btn btn-outline-secondary btn-sm <?php echo $nextDisabled; ?>"
             href="<?php echo mem_h(mem_base_url('/member-admin-transactions.php') . '?' . http_build_query(array_merge($baseParams, ['page' => $nextPage]))); ?>">Next</a>
        </div>
      </div>

      <?php if ($transactions): ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th scope="col">Date</th>
                <th scope="col">Member</th>
                <th scope="col">Type</th>
                <th scope="col">Amount</th>
                <th scope="col">Status</th>
                <th scope="col">Provider</th>
                <th scope="col">Reference</th>
                <th scope="col">Notes</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($transactions as $row): ?>
                <?php
                  $paidAt = mem_format_date_uk((string) ($row['paid_at'] ?? $row['created'] ?? ''));
                  $amount = isset($row['amount']) ? (float) $row['amount'] : 0.0;
                  $currency = (string) ($row['currency'] ?? 'GBP');
                  $status = strtolower((string) ($row['status'] ?? ''));
                  $isRefund = $status === 'refunded';
                  $displayAmount = $isRefund ? '−' . mem_money_display($amount, $currency) : mem_money_display($amount, $currency);
                  $isRefundable = strtolower((string) ($row['payment_provider'] ?? '')) === 'stripe' && !$isRefund;
                  $memberName = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['surname'] ?? ''));
                ?>
                <tr>
                  <td><?php echo mem_h($paidAt !== '' ? $paidAt : '—'); ?></td>
                  <td>
                    <div class="fw-semibold"><?php echo mem_h($memberName !== '' ? $memberName : 'Member'); ?></div>
                    <div class="text-secondary small">Member #<?php echo (int) ($row['membership_number'] ?? 0); ?></div>
                  </td>
                  <td class="text-capitalize"><?php echo mem_h((string) ($row['transaction_type'] ?? '')); ?></td>
                  <td><?php echo mem_h($displayAmount); ?></td>
                  <td class="text-capitalize <?php echo $isRefund ? 'text-danger fw-semibold' : ''; ?>"><?php echo mem_h((string) ($row['status'] ?? '')); ?></td>
                  <td><?php echo mem_h((string) ($row['payment_provider'] ?? '')); ?> · <?php echo mem_h((string) ($row['payment_method'] ?? '')); ?></td>
                  <td class="small"><?php echo mem_h((string) ($row['provider_reference'] ?? '')); ?></td>
                  <td class="small text-secondary"><?php echo mem_h((string) ($row['notes'] ?? '')); ?></td>
                  <td>
                    <?php if ($isRefundable): ?>
                      <button type="button"
                              class="btn btn-sm btn-outline-danger"
                              data-bs-toggle="modal"
                              data-bs-target="#refundModal"
                              data-tx-id="<?php echo (int) ($row['id'] ?? 0); ?>"
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
        <div class="alert alert-light border text-secondary" role="alert">
          No transactions found. Adjust your filters and try again.
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php mem_page_footer(); ?>
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
          <input type="hidden" name="action" value="refund_transaction">
          <input type="hidden" name="transaction_id" id="refund-tx-id" value="">
          <input type="hidden" name="q" value="<?php echo mem_h($search); ?>">
          <input type="hidden" name="status" value="<?php echo mem_h($statusFilter); ?>">
          <input type="hidden" name="type" value="<?php echo mem_h($typeFilter); ?>">
          <input type="hidden" name="per_page" value="<?php echo (int) $perPage; ?>">
          <input type="hidden" name="page" value="<?php echo (int) $page; ?>">
          <input type="hidden" name="type" value="<?php echo mem_h($typeFilter); ?>">
          <input type="hidden" name="per_page" value="<?php echo (int) $perPage; ?>">
          <input type="hidden" name="page" value="<?php echo (int) $page; ?>">
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
            <textarea class="form-control" id="refund-comment" name="refund_comment" rows="3" placeholder="Notes for internal tracking"></textarea>
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
  const hasBootstrap = !!(window.bootstrap && window.bootstrap.Modal);
  const modalInstance = hasBootstrap ? new window.bootstrap.Modal(modalEl) : null;
  const body = document.body;

  const fillModal = (button) => {
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
  };

  const fallbackShow = () => {
    modalEl.classList.add('show');
    modalEl.style.display = 'block';
    modalEl.removeAttribute('aria-hidden');
    body.classList.add('modal-open');
  };

  const fallbackHide = () => {
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
    modalEl.setAttribute('aria-hidden', 'true');
    body.classList.remove('modal-open');
  };

  const triggerButtons = document.querySelectorAll('[data-bs-target="#refundModal"]');
  triggerButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      fillModal(btn);
      if (modalInstance) {
        modalInstance.show(btn);
      } else {
        fallbackShow();
      }
    });
  });

  modalEl.addEventListener('show.bs.modal', (event) => {
    const button = event.relatedTarget;
    fillModal(button);
  });

  const closeButtons = modalEl.querySelectorAll('[data-bs-dismiss="modal"]');
  closeButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      if (modalInstance) {
        modalInstance.hide();
      } else {
        fallbackHide();
      }
    });
  });
});

// CSV export for reports.
(() => {
  const btn = document.getElementById('export-csv-btn');
  const table = document.getElementById('member-report-table');
  if (!btn || !table) return;

  btn.addEventListener('click', () => {
    const rows = Array.from(table.querySelectorAll('tr'));
    const csv = rows.map(row => {
      return Array.from(row.querySelectorAll('th,td'))
        .map(cell => {
          const text = cell.textContent.trim().replace(/"/g, '""');
          return `"${text}"`;
        })
        .join(',');
    }).join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'member-list-<?php echo mem_h((string) $selectedReportYear); ?>.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  });
})();
</script>
