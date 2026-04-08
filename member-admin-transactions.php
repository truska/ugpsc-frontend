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

$search = trim((string) ($_GET['q'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 25);
$allowedPerPage = [10, 25, 50, 100];
if (!in_array($perPage, $allowedPerPage, true)) {
  $perPage = 25;
}
$offset = ($page - 1) * $perPage;

$errors = [];
$transactions = [];
$total = 0;

global $pdo, $DB_OK;
if (!$DB_OK || !($pdo instanceof PDO)) {
  $errors[] = 'Database unavailable.';
} elseif (!mem_ready() || !mem_table_exists('mem_transaction')) {
  $errors[] = 'Transaction tables are not installed yet.';
} else {
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

  $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

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
}

$adminDashboard = mem_base_url('/member-admin-dashboard.php');
$statusOptions = ['', 'paid', 'pending', 'failed', 'refunded', 'cancelled'];
mem_page_header('UGPSC Admin | Transactions', ['active' => 'admin']);
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

    <?php if ($errors): ?>
      <div class="alert alert-danger" role="alert"><?php echo mem_h(implode(' ', $errors)); ?></div>
    <?php else: ?>
      <form class="row g-3 align-items-end mb-4" method="get" action="<?php echo mem_h(mem_base_url('/member-admin-transactions.php')); ?>">
        <div class="col-lg-5">
          <label for="q" class="form-label mem-label mb-1">Search (ref, provider, member name/number, email)</label>
          <input type="text" class="form-control" id="q" name="q" value="<?php echo mem_h($search); ?>" placeholder="e.g. SUMUP ref or member number">
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
            $baseParams = ['q' => $search, 'status' => $statusFilter, 'per_page' => $perPage];
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
              </tr>
            </thead>
            <tbody>
              <?php foreach ($transactions as $row): ?>
                <?php
                  $paidAt = mem_format_date_uk((string) ($row['paid_at'] ?? $row['created'] ?? ''));
                  $amount = isset($row['amount']) ? (float) $row['amount'] : 0.0;
                  $currency = (string) ($row['currency'] ?? 'GBP');
                  $memberName = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['surname'] ?? ''));
                ?>
                <tr>
                  <td><?php echo mem_h($paidAt !== '' ? $paidAt : '—'); ?></td>
                  <td>
                    <div class="fw-semibold"><?php echo mem_h($memberName !== '' ? $memberName : 'Member'); ?></div>
                    <div class="text-secondary small">Member #<?php echo (int) ($row['membership_number'] ?? 0); ?></div>
                  </td>
                  <td class="text-capitalize"><?php echo mem_h((string) ($row['transaction_type'] ?? '')); ?></td>
                  <td><?php echo mem_h(mem_money_display($amount, $currency)); ?></td>
                  <td class="text-capitalize"><?php echo mem_h((string) ($row['status'] ?? '')); ?></td>
                  <td><?php echo mem_h((string) ($row['payment_provider'] ?? '')); ?> · <?php echo mem_h((string) ($row['payment_method'] ?? '')); ?></td>
                  <td class="small"><?php echo mem_h((string) ($row['provider_reference'] ?? '')); ?></td>
                  <td class="small text-secondary"><?php echo mem_h((string) ($row['notes'] ?? '')); ?></td>
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
