<?php
require_once __DIR__ . '/includes/member/ui.php';

mem_require_login();
$member = mem_current_member();
if (empty($member['is_admin'])) {
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

mem_page_header('UGPSC Members | Admin', ['active' => 'admin']);

global $pdo, $DB_OK;

$search = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 25);
$allowedPerPage = [10, 25, 50, 100];
if (!in_array($perPage, $allowedPerPage, true)) {
  $perPage = 25;
}
$offset = ($page - 1) * $perPage;

$errors = [];
$members = [];
$total = 0;
$selectedMember = null;
$selectedId = max(0, (int) ($_GET['member_id'] ?? 0));

if (!$DB_OK || !($pdo instanceof PDO)) {
  $errors[] = 'Database unavailable.';
} elseif (!mem_ready()) {
  $errors[] = 'Membership tables are not installed yet.';
} else {
  $whereParts = ['archived = 0'];
  $params = [];

  if ($search !== '') {
    $searchNormalized = strtoupper(str_replace(' ', '', $search));
    $searchSql = [
      'LOWER(email) LIKE :like',
      'LOWER(firstname) LIKE :like',
      'LOWER(surname) LIKE :like',
      'LOWER(CONCAT(firstname, " ", surname)) LIKE :like',
      'LOWER(postcode) LIKE :like',
      'REPLACE(UPPER(postcode), " ", "") LIKE :postcode_nospace',
    ];

    if (ctype_digit($search)) {
      $searchSql[] = 'membership_number = :member_number';
      $params[':member_number'] = (int) $search;
    }

    $params[':like'] = '%' . strtolower($search) . '%';
    $params[':like_raw'] = '%' . $search . '%';
    $params[':postcode_nospace'] = '%' . $searchNormalized . '%';
    $whereParts[] = '(' . implode(' OR ', $searchSql) . ')';
  }

  $whereSql = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

  $countSql = 'SELECT COUNT(*) FROM mem_member ' . $whereSql;
  $countStmt = $pdo->prepare($countSql);
  foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
  }
  $countStmt->execute();
  $total = (int) $countStmt->fetchColumn();

  $listSql = 'SELECT id, membership_number, firstname, surname, email, tel1, tel2, membership_status, membership_expires_at, years_paid_count, login_enabled, showonweb
              FROM mem_member ' . $whereSql . '
              ORDER BY membership_number ASC
              LIMIT :limit OFFSET :offset';
  $listStmt = $pdo->prepare($listSql);
  foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
  }
  $listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
  $listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $listStmt->execute();
  $members = $listStmt->fetchAll(PDO::FETCH_ASSOC);

  if ($selectedId > 0) {
    $selected = mem_load_member($selectedId);
    if ($selected) {
      $selectedMember = $selected;
    }
  }
}

$adminBase = mem_base_url('/member-admin.php');
$baseParams = [
  'q' => $search,
  'per_page' => $perPage,
];
$adminDashboard = mem_base_url('/member-admin-dashboard.php');
?>
<div class="container">
  <div class="mem-card p-4 p-lg-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <div>
        <h1 class="display-font h3 mb-1">Find Member</h1>
        <p class="text-secondary mb-0">Search, skim, and select a member to manage next.</p>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a class="btn btn-sm btn-outline-secondary" href="<?php echo mem_h($adminDashboard); ?>">Return to Admin Dashboard</a>
        <span class="badge text-bg-light">Admin</span>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger" role="alert">
        <?php echo mem_h(implode(' ', $errors)); ?>
      </div>
    <?php endif; ?>

    <?php if (!$errors): ?>
      <form class="row g-3 align-items-end mb-4" method="get" action="<?php echo mem_h($adminBase); ?>">
        <div class="col-lg-6">
          <label for="q" class="form-label mem-label mb-1">Search (name, email, number, postcode)</label>
          <input type="text" class="form-control" id="q" name="q" value="<?php echo mem_h($search); ?>" placeholder="e.g. 123, jane@, Smith, or AB12">
        </div>
        <div class="col-sm-4 col-lg-2">
          <label for="per_page" class="form-label mem-label mb-1">Page Size</label>
          <select class="form-select" id="per_page" name="per_page">
            <?php foreach ($allowedPerPage as $size): ?>
              <option value="<?php echo (int) $size; ?>" <?php echo $perPage === $size ? 'selected' : ''; ?>><?php echo (int) $size; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-sm-4 col-lg-2">
          <button type="submit" class="btn btn-mem-primary w-100">Search</button>
        </div>
        <div class="col-sm-4 col-lg-2">
          <a class="btn btn-outline-secondary w-100" href="<?php echo mem_h($adminBase); ?>">Reset</a>
        </div>
      </form>

      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="text-secondary small">
          <?php
            $from = $total > 0 ? $offset + 1 : 0;
            $to = min($offset + $perPage, $total);
          ?>
          Showing <?php echo mem_h((string) $from); ?>–<?php echo mem_h((string) $to); ?> of <?php echo mem_h((string) $total); ?> members
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
          ?>
          <a class="btn btn-outline-secondary btn-sm <?php echo $prevDisabled; ?>"
             href="<?php echo mem_h($adminBase . '?' . http_build_query(array_merge($baseParams, ['page' => $prevPage]))); ?>">Prev</a>
          <a class="btn btn-outline-secondary btn-sm <?php echo $nextDisabled; ?>"
             href="<?php echo mem_h($adminBase . '?' . http_build_query(array_merge($baseParams, ['page' => $nextPage]))); ?>">Next</a>
        </div>
      </div>

      <?php if ($members): ?>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">Member</th>
                <th scope="col">Contact</th>
                <th scope="col">Status</th>
                <th scope="col" class="text-end">Years</th>
                <th scope="col" class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($members as $row): ?>
                <?php
                  $name = trim((string) ($row['firstname'] ?? '') . ' ' . (string) ($row['surname'] ?? ''));
                  $expiry = mem_format_date_uk((string) ($row['membership_expires_at'] ?? ''));
                  $status = (string) ($row['membership_status'] ?? '');
                ?>
                <tr>
                  <td class="fw-semibold"><?php echo (int) ($row['membership_number'] ?? 0); ?></td>
                  <td>
                    <div class="fw-semibold"><?php echo mem_h($name !== '' ? $name : 'Not set'); ?></div>
                    <div class="text-secondary small">ID <?php echo (int) $row['id']; ?></div>
                  </td>
                  <td>
                    <div><?php echo mem_h((string) ($row['email'] ?? '')); ?></div>
                    <div class="text-secondary small"><?php echo mem_h((string) ($row['tel1'] ?? '')); ?></div>
                  </td>
                  <td>
                    <div class="badge text-bg-light text-capitalize"><?php echo mem_h($status !== '' ? $status : 'unknown'); ?></div>
                    <div class="text-secondary small">Expires <?php echo $expiry !== '' ? mem_h($expiry) : '—'; ?></div>
                  </td>
                  <td class="text-end"><?php echo (int) ($row['years_paid_count'] ?? 0); ?></td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-secondary"
                       href="<?php echo mem_h(mem_base_url('/member-admin-member.php') . '?' . http_build_query(['member_id' => (int) $row['id']])); ?>">Select</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-light border text-secondary" role="alert">
          No members found. Adjust your search and try again.
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php if ($selectedMember): ?>
    <div class="mem-card p-4 p-lg-5 mt-4">
      <h2 class="h5 display-font mb-3">Selected Member</h2>
      <div class="row g-3 g-md-4">
        <div class="col-md-6">
          <div class="mem-label">Member</div>
          <div class="fw-semibold"><?php echo mem_h(mem_member_reference($selectedMember)); ?></div>
          <div class="text-secondary small">ID <?php echo (int) $selectedMember['id']; ?></div>
        </div>
        <div class="col-md-6">
          <div class="mem-label">Contact</div>
          <div><?php echo mem_h((string) ($selectedMember['email'] ?? '')); ?></div>
          <div class="text-secondary small"><?php echo mem_h((string) ($selectedMember['tel1'] ?? '')); ?></div>
        </div>
        <div class="col-md-6">
          <div class="mem-label">Status</div>
          <div class="text-capitalize"><?php echo mem_h((string) ($selectedMember['membership_status'] ?? '')); ?></div>
          <div class="text-secondary small">
            Expires <?php echo mem_h(mem_format_date_uk((string) ($selectedMember['membership_expires_at'] ?? '')) ?: '—'); ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="mem-label">Next Actions</div>
          <div class="text-secondary small">Plug actions in here (renewal toggle, manual payment, history) in next phase.</div>
        </div>
      </div>
    </div>
  <?php elseif ($selectedId > 0): ?>
    <div class="mem-card p-4 p-lg-5 mt-4">
      <div class="alert alert-warning mb-0" role="alert">
        Member ID <?php echo (int) $selectedId; ?> could not be found.
      </div>
    </div>
  <?php endif; ?>
</div>
<?php mem_page_footer(); ?>
